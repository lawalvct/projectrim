<?php

use App\Models\ProductVideoUpload;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

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
