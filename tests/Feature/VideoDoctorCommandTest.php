<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

test('video doctor reports the dedicated queue and waiting work', function () {
    config()->set('video.ffmpeg', base_path('missing-ffmpeg-binary'));
    config()->set('video.ffprobe', base_path('missing-ffprobe-binary'));
    config()->set('video.queue_connection', 'video_database');
    config()->set('video.queue', 'videos');
    config()->set('queue.connections.video_database.retry_after', 7500);

    $seller = User::factory()->create(['role' => 'seller']);
    DB::table('products')->insert([
        'user_id' => $seller->id,
        'title' => 'Queued Video Product',
        'slug' => 'queued-video-product',
        'status' => 'published',
        'preview_video_status' => 'queued',
        'preview_video_source_path' => 'product-video-uploads/test/source.mp4',
        'preview_video_processing_token' => '00000000-0000-4000-8000-000000000001',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('jobs')->insert([
        'queue' => 'videos',
        'payload' => '{}',
        'attempts' => 0,
        'reserved_at' => null,
        'available_at' => now()->timestamp,
        'created_at' => now()->timestamp,
    ]);

    $this->artisan('videos:doctor')
        ->expectsOutputToContain('ProjectRim video processing diagnostics')
        ->expectsOutputToContain('connection=video_database; queue=videos')
        ->expectsOutputToContain('The existing default notification worker does not consume video jobs.')
        ->assertFailed();
});
