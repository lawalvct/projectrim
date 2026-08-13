<?php

use App\Models\Product;
use App\Models\ProductVideoUpload;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Process\Process;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('videos:doctor', function (): int {
    $connectionName = (string) config('video.queue_connection', 'video_database');
    $queueName = (string) config('video.queue', 'videos');
    $queueConfig = config("queue.connections.{$connectionName}");
    $workerTimeout = 7200;
    $checks = [];
    $healthy = true;

    foreach (['ffmpeg', 'ffprobe'] as $tool) {
        $binary = (string) config("video.{$tool}");
        $ok = false;
        $detail = $binary;

        if (! is_file($binary) || ! is_executable($binary)) {
            $detail .= ' — executable not found or not executable';
        } else {
            try {
                $process = new Process([$binary, '-version']);
                $process->setTimeout(15);
                $process->run();
                $ok = $process->isSuccessful();

                if (! $ok) {
                    $detail .= ' — '.trim($process->getErrorOutput() ?: $process->getOutput());
                }
            } catch (Throwable $exception) {
                $detail .= ' — '.$exception->getMessage();
            }
        }

        $checks[] = [strtoupper($tool), $ok ? 'OK' : 'FAILED', $detail];
        $healthy = $healthy && $ok;
    }

    $queueConfigured = is_array($queueConfig)
        && ($queueConfig['driver'] ?? null) === 'database';
    $retryAfter = (int) ($queueConfig['retry_after'] ?? 0);
    $retrySafe = $retryAfter > $workerTimeout;
    $checks[] = [
        'Video queue',
        $queueConfigured ? 'OK' : 'FAILED',
        "connection={$connectionName}; queue={$queueName}",
    ];
    $checks[] = [
        'Retry window',
        $retrySafe ? 'OK' : 'FAILED',
        "retry_after={$retryAfter}s; required > {$workerTimeout}s",
    ];
    $healthy = $healthy && $queueConfigured && $retrySafe;

    $queuedProducts = 0;
    $processingProducts = 0;
    $failedProducts = 0;
    $queuedJobs = null;

    if (Schema::hasTable('products') && Schema::hasColumn('products', 'preview_video_status')) {
        $queuedProducts = Product::query()->where('preview_video_status', 'queued')->count();
        $processingProducts = Product::query()->where('preview_video_status', 'processing')->count();
        $failedProducts = Product::query()->where('preview_video_status', 'failed')->count();
    } else {
        $healthy = false;
        $checks[] = ['Video database migration', 'FAILED', 'Run php artisan migrate --force'];
    }

    if ($queueConfigured) {
        try {
            $databaseConnection = $queueConfig['connection'] ?? config('database.default');
            $table = (string) ($queueConfig['table'] ?? 'jobs');
            $queuedJobs = DB::connection($databaseConnection)
                ->table($table)
                ->where('queue', $queueName)
                ->count();
        } catch (Throwable $exception) {
            $checks[] = ['Queue table', 'FAILED', $exception->getMessage()];
            $healthy = false;
        }
    }

    $this->newLine();
    $this->info('ProjectRim video processing diagnostics');
    $this->table(['Check', 'Result', 'Details'], $checks);
    $this->table(['Queued products', 'Processing products', 'Failed products', 'Jobs waiting on videos queue'], [[
        $queuedProducts,
        $processingProducts,
        $failedProducts,
        $queuedJobs ?? 'unavailable',
    ]]);

    $this->newLine();
    $this->line('The existing default notification worker does not consume video jobs.');
    $this->line('Run this as a separate Supervisor daemon:');
    $this->comment(PHP_BINARY.' '.base_path('artisan').' queue:work '.$connectionName.' --queue='.$queueName.' --sleep=3 --tries=2 --timeout='.$workerTimeout.' --memory=512 --max-jobs=10');

    if ($queuedProducts > 0 && $queuedJobs === 0) {
        $this->warn('Queued products exist without a waiting queue row. Re-save the video or dispatch its processing job after checking the upload source.');
        $healthy = false;
    } elseif ($queuedJobs !== null && $queuedJobs > 0) {
        $this->warn('Waiting video jobs were found. If this number does not fall, the dedicated videos worker is not running or is using stale configuration.');
    }

    return $healthy ? Command::SUCCESS : Command::FAILURE;
})->purpose('Check FFmpeg, video queue configuration, and waiting video jobs');

Artisan::command('videos:prune', function (): void {
    if (! Schema::hasTable('product_video_uploads')) {
        $this->warn('Video upload storage is not initialized. Run php artisan migrate first.');

        return;
    }

    $sourceDisk = Storage::disk((string) config('video.source_disk', 'local'));
    $uploadDirectory = trim((string) config('video.upload_directory', 'product-video-uploads'), '/');
    $expiredUploads = ProductVideoUpload::query()
        ->whereIn('status', ['uploading', 'completed', 'expired'])
        ->where('expires_at', '<', now())
        ->get();
    $staleAssemblies = ProductVideoUpload::query()
        ->where('status', 'assembling')
        ->where('expires_at', '<', now())
        ->where('updated_at', '<=', now()->subSeconds(max(1, (int) config('video.assembly_stale_seconds', 900))))
        ->get();
    $expiredUploads = $expiredUploads->concat($staleAssemblies);

    foreach ($expiredUploads as $upload) {
        $sourceDisk->deleteDirectory($uploadDirectory.'/'.$upload->token);
        $upload->delete();
    }

    $failedUploads = ProductVideoUpload::query()
        ->where('status', 'claimed')
        ->where('created_at', '<', now()->subDays(max(1, (int) config('video.retention_days', 7))))
        ->whereHas('product', fn ($query) => $query->where('preview_video_status', 'failed'))
        ->get();
    foreach ($failedUploads as $upload) {
        $sourceDisk->deleteDirectory($uploadDirectory.'/'.$upload->token);
        $upload->delete();
    }

    $workDirectory = (string) config('video.work_directory');
    $workCutoff = now()->subDays(max(1, (int) config('video.retention_days', 7)))->getTimestamp();
    $removedWorkDirectories = 0;
    if (File::isDirectory($workDirectory)) {
        foreach (File::directories($workDirectory) as $directory) {
            if (File::lastModified($directory) < $workCutoff) {
                File::deleteDirectory($directory);
                $removedWorkDirectories++;
            }
        }
    }

    $this->info(sprintf(
        'Pruned %d video upload(s) and %d abandoned work director%s.',
        $expiredUploads->count() + $failedUploads->count(),
        $removedWorkDirectories,
        $removedWorkDirectories === 1 ? 'y' : 'ies',
    ));
})->purpose('Remove expired unclaimed video uploads and abandoned FFmpeg work directories');

Schedule::command('videos:prune')->dailyAt('03:20')->withoutOverlapping();
