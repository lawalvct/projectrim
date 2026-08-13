<?php

namespace App\Jobs;

use App\Contracts\VideoProcessor;
use App\Models\Product;
use App\Models\ProductVideoUpload;
use App\Services\Video\Data\ProcessedVideo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ProcessProductPreviewVideo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 7200;

    public int $backoff = 300;

    public function __construct(public int $productId)
    {
        $this->onConnection((string) config('video.queue_connection', 'video_database'));
        $this->onQueue((string) config('video.queue', 'videos'));
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('product-preview-video:'.$this->productId))
                ->expireAfter($this->timeout + 300),
        ];
    }

    public function handle(VideoProcessor $processor): void
    {
        $product = Product::query()->find($this->productId);
        if (! $product || ! $this->isClaimable($product)) {
            return;
        }

        $token = (string) $product->preview_video_processing_token;
        $sourcePath = (string) $product->preview_video_source_path;
        if ($token === '' || $sourcePath === '') {
            return;
        }

        $claimed = Product::query()
            ->whereKey($product->id)
            ->where('preview_video_processing_token', $token)
            ->where('preview_video_source_path', $sourcePath)
            ->where(function ($query): void {
                $query->whereIn('preview_video_status', ['queued', 'failed'])
                    ->orWhere(function ($stale): void {
                        $stale->where('preview_video_status', 'processing')
                            ->where('updated_at', '<=', now()->subSeconds($this->timeout + 300));
                    });
            })
            ->update(['preview_video_status' => 'processing', 'preview_video_error' => null]);

        if ($claimed !== 1) {
            return;
        }

        try {
            $processed = $processor->process($this->privateSourcePath($sourcePath), $token);
            $this->publish($token, $sourcePath, $processed);
        } catch (Throwable $exception) {
            $this->markFailed($token, $sourcePath, $exception);

            throw $exception;
        }
    }

    private function publish(string $token, string $sourcePath, ProcessedVideo $processed): void
    {
        $relativePath = trim((string) config('video.output_directory'), '/').'/'.$token.'.mp4';
        $outputDisk = Storage::disk((string) config('video.output_disk', 'public'));
        $stream = fopen($processed->temporaryPath, 'rb');
        if ($stream === false || ! $outputDisk->put($relativePath, $stream)) {
            if (is_resource($stream)) {
                fclose($stream);
            }

            throw new \RuntimeException('Unable to publish the processed preview video.');
        }
        fclose($stream);

        $oldVideo = null;
        $published = false;

        try {
            DB::transaction(function () use ($token, $sourcePath, $relativePath, &$oldVideo, &$published): void {
                $product = Product::query()->lockForUpdate()->find($this->productId);
                if (! $product
                    || $product->preview_video_processing_token !== $token
                    || $product->preview_video_source_path !== $sourcePath
                    || $product->preview_video_status !== 'processing') {
                    return;
                }

                $oldVideo = $product->preview_video;
                $product->forceFill([
                    'preview_video' => $relativePath,
                    'preview_video_source_path' => null,
                    'preview_video_status' => 'ready',
                    'preview_video_processing_token' => null,
                    'preview_video_error' => null,
                    'preview_video_processed_at' => now(),
                ])->save();
                $published = true;
            });
        } catch (Throwable $exception) {
            $outputDisk->delete($relativePath);
            throw $exception;
        }

        @unlink($processed->temporaryPath);
        Storage::disk((string) config('video.source_disk', 'local'))->delete($sourcePath);
        ProductVideoUpload::query()
            ->where('token', $token)
            ->where('product_id', $this->productId)
            ->delete();

        if (! $published) {
            $outputDisk->delete($relativePath);
        } elseif ($oldVideo && $oldVideo !== $relativePath) {
            $outputDisk->delete($oldVideo);
        }
    }

    private function markFailed(string $token, string $sourcePath, Throwable $exception): void
    {
        Product::query()
            ->whereKey($this->productId)
            ->where('preview_video_processing_token', $token)
            ->where('preview_video_source_path', $sourcePath)
            ->update([
                'preview_video_status' => 'failed',
                'preview_video_error' => Str::limit($exception->getMessage(), 1_000),
            ]);
    }

    private function privateSourcePath(string $path): string
    {
        $disk = Storage::disk((string) config('video.source_disk', 'local'));
        $absolutePath = $disk->path(ltrim($path, '/\\'));
        $root = realpath($disk->path(''));
        $realPath = realpath($absolutePath);

        if ($root === false || $realPath === false || ! str_starts_with($realPath, rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR)) {
            throw new \RuntimeException('The preview source path is outside private storage.');
        }

        return $realPath;
    }

    private function isClaimable(Product $product): bool
    {
        if (in_array($product->preview_video_status, ['queued', 'failed'], true)) {
            return true;
        }

        return $product->preview_video_status === 'processing'
            && $product->updated_at?->lte(now()->subSeconds($this->timeout + 300));
    }
}
