<?php

namespace App\Services\Video;

use App\Contracts\VideoProcessor;
use App\Services\Video\Data\ProcessedVideo;
use App\Services\Video\Exceptions\VideoProcessingException;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

final class FfmpegVideoProcessor implements VideoProcessor
{
    public function process(string $sourcePath, string $token): ProcessedVideo
    {
        $sourcePath = $this->validatedSourcePath($sourcePath);
        $metadata = $this->probe($sourcePath);
        $duration = $metadata['duration'];

        if ($duration <= 0 || $duration > (float) config('video.max_duration_seconds')) {
            throw new VideoProcessingException('The preview video duration is outside the permitted range.');
        }

        $workDirectory = $this->workDirectory($token);
        $maxOutputBytes = min((int) config('video.max_output_bytes'), 100_000_000);
        $targetBytes = min((int) config('video.target_output_bytes'), (int) floor($maxOutputBytes * 0.9));

        if ($this->canPreserveMp4($sourcePath, $metadata, $maxOutputBytes)) {
            return $this->processedVideo($sourcePath);
        }

        if ($this->canRemuxMov($sourcePath, $metadata)) {
            $output = $workDirectory.DIRECTORY_SEPARATOR.'preview-remuxed.mp4';
            $this->remux($sourcePath, $output);

            if ($this->isValidOutput($output, $maxOutputBytes)) {
                return $this->processedVideo($output);
            }

            @unlink($output);
        }

        $videoKbps = max(250, min(12_000, (int) floor((($targetBytes * 8) / $duration / 1000) - (int) config('video.audio_bitrate_kbps'))));

        foreach ($this->profilesFor($metadata, $videoKbps) as $profile) {
            $output = $workDirectory.DIRECTORY_SEPARATOR."preview-{$profile['name']}.mp4";
            $this->encode($sourcePath, $output, $workDirectory, $metadata['width'], $metadata['height'], $profile['width'], $profile['height'], $videoKbps);

            if ($this->isValidOutput($output, $maxOutputBytes)) {
                return $this->processedVideo($output);
            }

            @unlink($output);
            $videoKbps = max(180, (int) floor($videoKbps * 0.72));
        }

        throw new VideoProcessingException('Unable to produce a preview video smaller than 100 MB.');
    }

    private function probe(string $path): array
    {
        $output = $this->runForOutput([
            $this->binary('ffprobe'), '-v', 'error',
            '-show_entries', 'format=duration,format_name:stream=codec_type,codec_name,width,height,duration',
            '-of', 'json', $path,
        ]);
        $data = json_decode($output, true);
        if (! is_array($data)) {
            throw new VideoProcessingException('The video tool returned invalid metadata.');
        }
        $streams = is_array($data['streams'] ?? null) ? $data['streams'] : [];
        $video = collect($streams)->firstWhere('codec_type', 'video');
        $audio = collect($streams)->firstWhere('codec_type', 'audio');
        $duration = (float) ($data['format']['duration'] ?? $video['duration'] ?? 0);

        if (! is_array($video) || ! isset($video['width'], $video['height'])) {
            throw new VideoProcessingException('The uploaded file does not contain a readable video stream.');
        }

        return [
            'duration' => $duration,
            'width' => (int) $video['width'],
            'height' => (int) $video['height'],
            'video_codec' => (string) ($video['codec_name'] ?? 'unknown'),
            'audio_codec' => is_array($audio) ? (string) ($audio['codec_name'] ?? 'unknown') : null,
            'format_name' => (string) ($data['format']['format_name'] ?? ''),
        ];
    }

    private function canPreserveMp4(string $source, array $metadata, int $maxOutputBytes): bool
    {
        return $this->hasExtension($source, 'mp4')
            && $this->isValidOutput($source, $maxOutputBytes);
    }

    private function canRemuxMov(string $source, array $metadata): bool
    {
        return $this->hasExtension($source, 'mov')
            && $this->isWithinMaximumResolution($metadata)
            && $metadata['video_codec'] === 'h264'
            && in_array($metadata['audio_codec'], [null, 'aac'], true);
    }

    private function remux(string $source, string $output): void
    {
        $this->run([
            $this->binary('ffmpeg'), '-hide_banner', '-y', '-i', $source,
            '-map', '0:v:0', '-map', '0:a:0?', '-c', 'copy', '-movflags', '+faststart',
            $output,
        ]);
    }

    private function isValidOutput(string $path, int $maxOutputBytes): bool
    {
        clearstatcache(true, $path);

        if (! is_file($path) || filesize($path) >= $maxOutputBytes) {
            return false;
        }

        $metadata = $this->probe($path);

        return $this->hasExtension($path, 'mp4')
            && $metadata['duration'] > 0
            && $this->isWithinMaximumResolution($metadata)
            && $metadata['video_codec'] === 'h264'
            && in_array($metadata['audio_codec'], [null, 'aac'], true);
    }

    private function isWithinMaximumResolution(array $metadata): bool
    {
        if ($metadata['width'] >= $metadata['height']) {
            return $metadata['width'] <= (int) config('video.max_landscape_width')
                && $metadata['height'] <= (int) config('video.max_landscape_height');
        }

        return $metadata['width'] <= (int) config('video.max_portrait_width')
            && $metadata['height'] <= (int) config('video.max_portrait_height');
    }

    private function processedVideo(string $path): ProcessedVideo
    {
        $metadata = $this->probe($path);

        return new ProcessedVideo(
            temporaryPath: $path,
            size: filesize($path),
            durationMs: (int) round($metadata['duration'] * 1000),
            width: $metadata['width'],
            height: $metadata['height'],
            videoCodec: $metadata['video_codec'],
            audioCodec: $metadata['audio_codec'],
        );
    }

    private function hasExtension(string $path, string $extension): bool
    {
        return strtolower(pathinfo($path, PATHINFO_EXTENSION)) === $extension;
    }

    private function encode(string $source, string $output, string $workDirectory, int $sourceWidth, int $sourceHeight, int $maximumWidth, int $maximumHeight, int $videoKbps): void
    {
        $scale = min($maximumWidth / $sourceWidth, $maximumHeight / $sourceHeight, 1);
        $targetWidth = max(2, (int) (round(($sourceWidth * $scale) / 2) * 2));
        $targetHeight = max(2, (int) (round(($sourceHeight * $scale) / 2) * 2));
        $passLog = $workDirectory.DIRECTORY_SEPARATOR.'ffmpeg-pass';
        $passOne = $workDirectory.DIRECTORY_SEPARATOR.'pass-one.mp4';
        $common = ['-hide_banner', '-y', '-i', $source, '-map', '0:v:0', '-vf', "scale={$targetWidth}:{$targetHeight}", '-c:v', 'libx264', '-pix_fmt', 'yuv420p', '-b:v', "{$videoKbps}k", '-maxrate', "{$videoKbps}k", '-bufsize', ($videoKbps * 2).'k', '-passlogfile', $passLog];

        try {
            $this->run([$this->binary('ffmpeg'), ...$common, '-pass', '1', '-an', '-f', 'mp4', $passOne]);
            $this->run([$this->binary('ffmpeg'), ...$common, '-map', '0:a:0?', '-pass', '2', '-c:a', 'aac', '-b:a', ((int) config('video.audio_bitrate_kbps')).'k', '-movflags', '+faststart', $output]);
        } finally {
            @unlink($passOne);
            foreach (glob($passLog.'*') ?: [] as $file) {
                @unlink($file);
            }
        }
    }

    private function run(array $command): Process
    {
        $process = new Process($command, timeout: (float) config('video.process_timeout'));
        $process->run();

        if (! $process->isSuccessful()) {
            throw new VideoProcessingException('Video tool failed: '.Str::limit(trim($process->getErrorOutput()), 1_000));
        }

        return $process;
    }

    private function runForOutput(array $command): string
    {
        $process = new Process($command, timeout: (float) config('video.process_timeout'));
        $process->disableOutput();
        $output = '';
        $error = '';
        $tooLarge = false;
        $maximumBytes = max(4_096, (int) config('video.probe_max_output_bytes', 131_072));

        $process->run(function (string $type, string $data) use (&$output, &$error, &$tooLarge, $maximumBytes): void {
            if ($type === Process::OUT) {
                $remaining = $maximumBytes - strlen($output);
                if ($remaining <= 0 || strlen($data) > $remaining) {
                    $tooLarge = true;

                    return;
                }
                $output .= $data;

                return;
            }

            $error = Str::limit($error.$data, 1_000, '');
        });

        if ($tooLarge) {
            throw new VideoProcessingException('The video metadata exceeds the permitted size.');
        }
        if (! $process->isSuccessful()) {
            throw new VideoProcessingException('Video tool failed: '.Str::limit(trim($error), 1_000));
        }

        return $output;
    }

    private function validatedSourcePath(string $path): string
    {
        $realPath = realpath($path);
        $maxBytes = (int) config('video.max_source_bytes');

        if ($realPath === false || ! is_file($realPath) || filesize($realPath) > $maxBytes) {
            throw new VideoProcessingException('The staged source video is missing or exceeds 400 MB.');
        }

        return $realPath;
    }

    private function workDirectory(string $token): string
    {
        if (! preg_match('/^[a-zA-Z0-9-]{16,64}$/', $token)) {
            throw new VideoProcessingException('Invalid video processing token.');
        }

        $base = (string) config('video.work_directory');
        if (! str_starts_with($base, DIRECTORY_SEPARATOR) && ! preg_match('/^[A-Za-z]:\\\\/', $base)) {
            throw new VideoProcessingException('The video work directory must be an absolute path.');
        }

        $directory = rtrim($base, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$token;
        if (! is_dir($directory) && ! mkdir($directory, 0750, true) && ! is_dir($directory)) {
            throw new VideoProcessingException('Unable to create the video work directory.');
        }

        return $directory;
    }

    private function binary(string $name): string
    {
        $path = (string) config("video.{$name}");
        if (! str_starts_with($path, DIRECTORY_SEPARATOR) && ! preg_match('/^[A-Za-z]:\\\\/', $path)) {
            throw new VideoProcessingException("The {$name} binary must use an absolute path.");
        }

        return $path;
    }

    private function profilesFor(array $metadata, int $videoKbps): array
    {
        $profiles = $metadata['width'] >= $metadata['height']
            ? [
                ['name' => '1080', 'width' => (int) config('video.max_landscape_width'), 'height' => (int) config('video.max_landscape_height'), 'minimum_kbps' => 1_800],
                ['name' => '720', 'width' => 1_280, 'height' => 720, 'minimum_kbps' => 850],
                ['name' => '480', 'width' => 854, 'height' => 480, 'minimum_kbps' => 250],
            ]
            : [
                ['name' => '1080', 'width' => (int) config('video.max_portrait_width'), 'height' => (int) config('video.max_portrait_height'), 'minimum_kbps' => 1_800],
                ['name' => '720', 'width' => 720, 'height' => 1_280, 'minimum_kbps' => 850],
                ['name' => '480', 'width' => 480, 'height' => 854, 'minimum_kbps' => 250],
            ];

        foreach ($profiles as $index => $profile) {
            if ($videoKbps >= $profile['minimum_kbps']) {
                return array_slice($profiles, $index);
            }
        }

        return [end($profiles)];
    }
}
