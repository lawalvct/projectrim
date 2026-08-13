<?php

use App\Contracts\VideoProcessor;
use App\Jobs\ProcessProductPreviewVideo;
use App\Models\Product;
use App\Models\ProductVideoUpload;
use App\Models\User;
use App\Services\Video\Data\ProcessedVideo;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

function completedProductVideoUpload(User $seller, string $sourcePath = 'product-video-uploads/source.mp4'): ProductVideoUpload
{
    Storage::disk('local')->put($sourcePath, 'source video');

    return ProductVideoUpload::create([
        'user_id' => $seller->id,
        'token' => (string) Str::uuid(),
        'original_name' => 'preview.mp4',
        'mime_type' => 'video/mp4',
        'expected_size' => 12,
        'received_size' => 12,
        'total_chunks' => 1,
        'uploaded_chunks' => [0],
        'source_path' => $sourcePath,
        'status' => 'completed',
        'completed_at' => now(),
        'expires_at' => now()->addDay(),
    ]);
}

function videoProduct(User $seller, array $attributes = []): Product
{
    return Product::create(array_merge([
        'user_id' => $seller->id,
        'title' => 'Video product',
        'slug' => 'video-product-'.Str::random(8),
        'status' => 'published',
        'published_at' => now(),
    ], $attributes));
}

test('a seller can initialize an owned upload and the source size limit is enforced', function () {
    config()->set('video.chunk_size', 4);
    config()->set('video.max_source_size', 6);

    $seller = User::factory()->create(['role' => 'seller']);

    $response = $this->actingAs($seller)->postJson(route('seller.product-video-uploads.initialize'), [
        'name' => 'preview.mp4',
        'size' => 6,
        'mime_type' => 'video/mp4',
    ]);

    $response->assertCreated()->assertJsonPath('expected_size', 6)->assertJsonPath('total_chunks', 2)->assertJsonPath('chunk_size', 4);

    $upload = ProductVideoUpload::query()->sole();
    expect($upload->user_id)->toBe($seller->id)
        ->and($upload->received_size)->toBe(0)
        ->and($upload->uploaded_chunks)->toBe([]);

    $this->actingAs($seller)->postJson(route('seller.product-video-uploads.initialize'), [
        'name' => 'too-large.mp4',
        'size' => 7,
        'mime_type' => 'video/mp4',
    ])->assertUnprocessable()->assertJsonValidationErrors('size');
});

test('chunk uploads are idempotent and completion assembles the private source in order', function () {
    Storage::fake('local');
    config()->set('video.chunk_size', 4);
    config()->set('video.max_source_size', 6);

    $seller = User::factory()->create(['role' => 'seller']);
    $upload = $this->actingAs($seller)->postJson(route('seller.product-video-uploads.initialize'), [
        'name' => 'preview.mp4',
        'size' => 6,
        'mime_type' => 'video/mp4',
    ])->json();

    $firstChunk = UploadedFile::fake()->createWithContent('0.part', 'ABCD');
    $this->actingAs($seller)->put(route('seller.product-video-uploads.chunks.store', [$upload['token'], 0]), [
        'chunk' => $firstChunk,
        'checksum' => hash('sha256', 'ABCD'),
    ])->assertOk()->assertJsonPath('received_size', 4);

    $this->actingAs($seller)->put(route('seller.product-video-uploads.chunks.store', [$upload['token'], 0]), [
        'chunk' => UploadedFile::fake()->createWithContent('0.part', 'ABCD'),
        'checksum' => hash('sha256', 'ABCD'),
    ])->assertOk();

    $record = ProductVideoUpload::query()->where('token', $upload['token'])->sole();
    expect($record->received_size)->toBe(4)->and($record->uploaded_chunks)->toBe([0]);

    $this->actingAs($seller)->put(route('seller.product-video-uploads.chunks.store', [$upload['token'], 1]), [
        'chunk' => UploadedFile::fake()->createWithContent('1.part', 'EF'),
        'checksum' => hash('sha256', 'EF'),
    ])->assertOk()->assertJsonPath('received_size', 6);

    $this->actingAs($seller)->postJson(route('seller.product-video-uploads.complete', $upload['token']))
        ->assertOk()->assertJsonPath('status', 'completed');

    $record->refresh();
    expect($record->status)->toBe('completed')
        ->and(Storage::disk('local')->get($record->source_path))->toBe('ABCDEF');
    Storage::disk('local')->assertMissing('product-video-uploads/'.$record->token.'/chunks/0.part');
    $this->actingAs($seller)->getJson(route('seller.product-video-uploads.show', $upload['token']))
        ->assertOk()->assertJsonPath('status', 'completed');
    $completedAt = $record->completed_at;

    $this->actingAs($seller)->postJson(route('seller.product-video-uploads.complete', $upload['token']))
        ->assertOk()->assertJsonPath('status', 'completed');

    $record->refresh();
    expect($record->completed_at->equalTo($completedAt))->toBeTrue()
        ->and(Storage::disk('local')->get($record->source_path))->toBe('ABCDEF');
});

test('a different seller cannot access an upload they do not own', function () {
    $owner = User::factory()->create(['role' => 'seller']);
    $otherSeller = User::factory()->create(['role' => 'seller']);
    $upload = completedProductVideoUpload($owner);

    $this->actingAs($otherSeller)->getJson(route('seller.product-video-uploads.show', $upload->token))->assertNotFound();
    $this->actingAs($otherSeller)->postJson(route('seller.product-video-uploads.complete', $upload->token))->assertNotFound();
});

test('a completed upload is claimed and queued when creating a product', function () {
    Storage::fake('local');
    Storage::fake('public');
    Queue::fake();

    $seller = User::factory()->create(['role' => 'seller']);
    $upload = completedProductVideoUpload($seller);

    $response = $this->actingAs($seller)->post(route('seller.products.store'), [
        'title' => 'Product with staged video',
        'project_file' => UploadedFile::fake()->create('project.pdf', 20, 'application/pdf'),
        'preview_video_upload_token' => $upload->token,
    ]);

    $response->assertRedirect(route('seller.products.index'));
    $product = Product::query()->sole();
    $upload->refresh();

    expect($upload->status)->toBe('claimed')->and($upload->product_id)->toBe($product->id);
    expect($product->preview_video_status)->toBe('queued')
        ->and($product->preview_video_processing_token)->toBe($upload->token)
        ->and($product->preview_video_source_path)->toBe($upload->source_path);
    Queue::assertPushed(ProcessProductPreviewVideo::class, fn (ProcessProductPreviewVideo $job) => $job->productId === $product->id);
});

test('claiming a replacement upload keeps the current preview available until the job publishes', function () {
    Storage::fake('local');
    Storage::fake('public');
    Queue::fake();

    $seller = User::factory()->create(['role' => 'seller']);
    $product = videoProduct($seller, ['preview_video' => 'products/preview-videos/old.mp4', 'preview_video_status' => 'ready']);
    Storage::disk('public')->put($product->preview_video, 'old preview');
    $upload = completedProductVideoUpload($seller, 'product-video-uploads/replacement/source.mp4');

    $this->actingAs($seller)->put(route('seller.products.update', $product), [
        'title' => $product->title,
        'project_file' => UploadedFile::fake()->create('project.pdf', 20, 'application/pdf'),
        'preview_video_upload_token' => $upload->token,
    ])->assertRedirect(route('seller.products.index'));

    $product->refresh();
    $upload->refresh();
    expect($upload->status)->toBe('claimed')
        ->and($product->preview_video)->toBe('products/preview-videos/old.mp4')
        ->and($product->preview_video_status)->toBe('queued');
    Storage::disk('public')->assertExists('products/preview-videos/old.mp4');
    Queue::assertPushed(ProcessProductPreviewVideo::class, fn (ProcessProductPreviewVideo $job) => $job->productId === $product->id);
});

test('the video job publishes a processed preview and retires the previous file', function () {
    Storage::fake('local');
    Storage::fake('public');

    $seller = User::factory()->create(['role' => 'seller']);
    $token = (string) Str::uuid();
    $sourcePath = 'product-video-uploads/'.$token.'/source.mp4';
    Storage::disk('local')->put($sourcePath, 'source');
    Storage::disk('local')->put('processed/'.$token.'.mp4', 'processed');
    Storage::disk('public')->put('products/preview-videos/old.mp4', 'old');
    $product = videoProduct($seller, [
        'preview_video' => 'products/preview-videos/old.mp4',
        'preview_video_source_path' => $sourcePath,
        'preview_video_processing_token' => $token,
        'preview_video_status' => 'queued',
    ]);

    $processor = new class implements VideoProcessor
    {
        public function process(string $sourcePath, string $token): ProcessedVideo
        {
            return new ProcessedVideo(Storage::disk('local')->path('processed/'.$token.'.mp4'), 9, 1000, 640, 360, 'h264', 'aac');
        }
    };

    (new ProcessProductPreviewVideo($product->id))->handle($processor);

    $product->refresh();
    $newPath = 'products/preview-videos/'.$token.'.mp4';
    expect($product->preview_video_status)->toBe('ready')
        ->and($product->preview_video)->toBe($newPath)
        ->and($product->preview_video_source_path)->toBeNull()
        ->and($product->preview_video_processing_token)->toBeNull();
    Storage::disk('public')->assertExists($newPath);
    Storage::disk('public')->assertMissing('products/preview-videos/old.mp4');
    Storage::disk('local')->assertMissing($sourcePath);
});

test('the video job records a processor failure and rethrows it for the queue worker', function () {
    Storage::fake('local');

    $seller = User::factory()->create(['role' => 'seller']);
    $token = (string) Str::uuid();
    $sourcePath = 'product-video-uploads/'.$token.'/source.mp4';
    Storage::disk('local')->put($sourcePath, 'source');
    $product = videoProduct($seller, [
        'preview_video_source_path' => $sourcePath,
        'preview_video_processing_token' => $token,
        'preview_video_status' => 'queued',
    ]);

    $processor = new class implements VideoProcessor
    {
        public function process(string $sourcePath, string $token): ProcessedVideo
        {
            throw new RuntimeException('transcoder failed');
        }
    };

    expect(fn () => (new ProcessProductPreviewVideo($product->id))->handle($processor))->toThrow(RuntimeException::class, 'transcoder failed');

    $product->refresh();
    expect($product->preview_video_status)->toBe('failed')->and($product->preview_video_error)->toBe('transcoder failed');
});

test('a stale processing result cannot overwrite a newly claimed video token', function () {
    Storage::fake('local');
    Storage::fake('public');

    $seller = User::factory()->create(['role' => 'seller']);
    $staleToken = (string) Str::uuid();
    $replacementToken = (string) Str::uuid();
    $staleSource = 'product-video-uploads/'.$staleToken.'/source.mp4';
    $replacementSource = 'product-video-uploads/'.$replacementToken.'/source.mp4';
    Storage::disk('local')->put($staleSource, 'stale source');
    Storage::disk('local')->put($replacementSource, 'replacement source');
    Storage::disk('local')->put('processed/'.$staleToken.'.mp4', 'stale processed');
    $product = videoProduct($seller, [
        'preview_video' => 'products/preview-videos/current.mp4',
        'preview_video_source_path' => $staleSource,
        'preview_video_processing_token' => $staleToken,
        'preview_video_status' => 'queued',
    ]);

    $processor = new class($product, $replacementToken, $replacementSource) implements VideoProcessor
    {
        public function __construct(private Product $product, private string $replacementToken, private string $replacementSource) {}

        public function process(string $sourcePath, string $token): ProcessedVideo
        {
            Product::query()->whereKey($this->product->id)->update([
                'preview_video_processing_token' => $this->replacementToken,
                'preview_video_source_path' => $this->replacementSource,
                'preview_video_status' => 'queued',
            ]);

            return new ProcessedVideo(Storage::disk('local')->path('processed/'.$token.'.mp4'), 15, 1000, 640, 360, 'h264', 'aac');
        }
    };

    (new ProcessProductPreviewVideo($product->id))->handle($processor);

    $product->refresh();
    expect($product->preview_video_processing_token)->toBe($replacementToken)
        ->and($product->preview_video_source_path)->toBe($replacementSource)
        ->and($product->preview_video_status)->toBe('queued')
        ->and($product->preview_video)->toBe('products/preview-videos/current.mp4');
    Storage::disk('public')->assertMissing('products/preview-videos/'.$staleToken.'.mp4');
});

test('video output and queue timing configuration preserve the processing safety margins', function () {
    $job = new ProcessProductPreviewVideo(1);

    expect((int) config('video.target_output_bytes'))->toBeLessThan((int) config('video.max_output_bytes'))
        ->and($job->timeout)->toBeLessThan((int) config('queue.connections.video_database.retry_after'));
});

test('active upload quotas apply per seller without blocking other sellers', function () {
    config()->set('video.max_source_size', 10);
    config()->set('video.max_active_uploads', 1);
    config()->set('video.max_active_upload_bytes', 6);

    $seller = User::factory()->create(['role' => 'seller']);
    $otherSeller = User::factory()->create(['role' => 'seller']);
    ProductVideoUpload::create([
        'user_id' => $seller->id,
        'token' => (string) Str::uuid(),
        'original_name' => 'existing.mp4',
        'mime_type' => 'video/mp4',
        'expected_size' => 4,
        'total_chunks' => 1,
        'uploaded_chunks' => [],
        'expires_at' => now()->addDay(),
    ]);

    $this->actingAs($seller)->postJson(route('seller.product-video-uploads.initialize'), [
        'name' => 'count-over-quota.mp4',
        'size' => 2,
        'mime_type' => 'video/mp4',
    ])->assertUnprocessable();

    $this->actingAs($otherSeller)->postJson(route('seller.product-video-uploads.initialize'), [
        'name' => 'other-seller.mp4',
        'size' => 2,
        'mime_type' => 'video/mp4',
    ])->assertCreated();

    config()->set('video.max_active_uploads', 3);
    config()->set('video.max_active_upload_bytes', 5);

    $this->actingAs($seller)->postJson(route('seller.product-video-uploads.initialize'), [
        'name' => 'bytes-over-quota.mp4',
        'size' => 2,
        'mime_type' => 'video/mp4',
    ])->assertUnprocessable();

    expect(ProductVideoUpload::query()->where('user_id', $seller->id)->count())->toBe(1)
        ->and(ProductVideoUpload::query()->where('user_id', $otherSeller->id)->count())->toBe(1);
});

test('the video job uses an overlap lock and does not claim a product already being processed', function () {
    Storage::fake('local');

    $seller = User::factory()->create(['role' => 'seller']);
    $token = (string) Str::uuid();
    $sourcePath = 'product-video-uploads/'.$token.'/source.mp4';
    Storage::disk('local')->put($sourcePath, 'source');
    $product = videoProduct($seller, [
        'preview_video_source_path' => $sourcePath,
        'preview_video_processing_token' => $token,
        'preview_video_status' => 'processing',
    ]);
    $job = new ProcessProductPreviewVideo($product->id);
    $middleware = $job->middleware();
    $processor = Mockery::mock(VideoProcessor::class);
    $processor->shouldNotReceive('process');

    expect($middleware)->toHaveCount(1)
        ->and($middleware[0])->toBeInstanceOf(WithoutOverlapping::class)
        ->and($middleware[0]->key)->toContain((string) $product->id);

    $job->handle($processor);

    $product->refresh();
    expect($product->preview_video_status)->toBe('processing')
        ->and($product->preview_video_processing_token)->toBe($token);
});

test('preview video processing migration backfills legacy preview records', function () {
    $connection = 'legacy_video_migration_'.Str::random(8);
    $database = tempnam(sys_get_temp_dir(), 'projectrim-video-migration-');
    $originalConnection = config('database.default');

    config()->set("database.connections.{$connection}", [
        'driver' => 'sqlite',
        'database' => $database,
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]);

    try {
        Schema::connection($connection)->create('products', function ($table): void {
            $table->id();
            $table->string('preview_video')->nullable();
        });
        DB::connection($connection)->table('products')->insert([
            ['preview_video' => 'products/preview-videos/legacy.mp4'],
            ['preview_video' => null],
        ]);

        config()->set('database.default', $connection);
        DB::setDefaultConnection($connection);
        $migration = require database_path('migrations/2026_08_13_000002_add_preview_video_processing_columns_to_products_table.php');
        $migration->up();

        expect(Schema::connection($connection)->hasColumns('products', [
            'preview_video_source_path',
            'preview_video_processing_token',
            'preview_video_status',
            'preview_video_error',
            'preview_video_processed_at',
        ]))->toBeTrue()
            ->and(DB::connection($connection)->table('products')->where('preview_video', 'products/preview-videos/legacy.mp4')->value('preview_video_status'))->toBe('ready')
            ->and(DB::connection($connection)->table('products')->whereNull('preview_video')->value('preview_video_status'))->toBe('none');
    } finally {
        config()->set('database.default', $originalConnection);
        DB::setDefaultConnection($originalConnection);
        DB::purge($connection);
        @unlink($database);
    }
});

test('a stale assembling upload is reset, its temporary assembly is removed, and completion resumes', function () {
    config()->set('video.assembly_stale_seconds', 60);
    config()->set('video.chunk_size', 4);
    Storage::fake('local');
    $seller = User::factory()->create(['role' => 'seller']);
    $token = (string) Str::uuid();
    $source = 'product-video-uploads/'.$token.'/source.mp4';
    $upload = ProductVideoUpload::create([
        'user_id' => $seller->id,
        'token' => $token,
        'original_name' => 'preview.mp4',
        'mime_type' => 'video/mp4',
        'expected_size' => 6,
        'received_size' => 6,
        'total_chunks' => 2,
        'uploaded_chunks' => [0, 1],
        'status' => 'assembling',
        'expires_at' => now()->addDay(),
    ]);
    ProductVideoUpload::query()->whereKey($upload->id)->update(['updated_at' => now()->subSeconds(61)]);
    Storage::disk('local')->put('product-video-uploads/'.$token.'/chunks/0.part', 'ABCD');
    Storage::disk('local')->put('product-video-uploads/'.$token.'/chunks/1.part', 'EF');
    Storage::disk('local')->put($source.'.assembling', 'ABC');

    $this->actingAs($seller)->postJson(route('seller.product-video-uploads.complete', $token))
        ->assertOk()->assertJsonPath('status', 'completed');

    $upload->refresh();
    expect($upload->status)->toBe('completed')
        ->and($upload->received_size)->toBe(6)
        ->and($upload->uploaded_chunks)->toBe([0, 1]);
    Storage::disk('local')->assertMissing($source.'.assembling');
    Storage::disk('local')->assertExists($source);
    expect(Storage::disk('local')->get($source))->toBe('ABCDEF');

});
test('a fresh assembling upload remains protected from another completion attempt', function () {
    config()->set('video.assembly_stale_seconds', 60);
    Storage::fake('local');
    $seller = User::factory()->create(['role' => 'seller']);
    $token = (string) Str::uuid();
    $source = 'product-video-uploads/'.$token.'/source.mp4';
    $upload = ProductVideoUpload::create([
        'user_id' => $seller->id,
        'token' => $token,
        'original_name' => 'preview.mp4',
        'mime_type' => 'video/mp4',
        'expected_size' => 6,
        'received_size' => 6,
        'total_chunks' => 2,
        'uploaded_chunks' => [0, 1],
        'status' => 'assembling',
        'expires_at' => now()->addDay(),
    ]);
    ProductVideoUpload::query()->whereKey($upload->id)->update(['updated_at' => now()->subSeconds(59)]);
    Storage::disk('local')->put($source.'.assembling', 'ABC');

    $this->actingAs($seller)->postJson(route('seller.product-video-uploads.complete', $token))
        ->assertUnprocessable();

    $upload->refresh();
    expect($upload->status)->toBe('assembling')
        ->and($upload->received_size)->toBe(6);
    Storage::disk('local')->assertExists($source.'.assembling');

});
test('claiming a replacement removes the superseded claimed upload row and private source', function () {
    Storage::fake('local');
    Storage::fake('public');
    Queue::fake();

    $seller = User::factory()->create(['role' => 'seller']);
    $oldToken = (string) Str::uuid();
    $oldSource = 'product-video-uploads/'.$oldToken.'/source.mp4';
    Storage::disk('local')->put($oldSource, 'old source');
    $product = videoProduct($seller, [
        'preview_video_source_path' => $oldSource,
        'preview_video_processing_token' => $oldToken,
        'preview_video_status' => 'queued',
    ]);
    $oldUpload = ProductVideoUpload::create([
        'user_id' => $seller->id,
        'product_id' => $product->id,
        'token' => $oldToken,
        'original_name' => 'old.mp4',
        'mime_type' => 'video/mp4',
        'expected_size' => 10,
        'received_size' => 10,
        'total_chunks' => 1,
        'uploaded_chunks' => [0],
        'source_path' => $oldSource,
        'status' => 'claimed',
        'completed_at' => now(),
        'claimed_at' => now(),
        'expires_at' => now()->addDay(),
    ]);
    $replacement = completedProductVideoUpload($seller, 'product-video-uploads/replacement/source.mp4');

    $this->actingAs($seller)->put(route('seller.products.update', $product), [
        'title' => $product->title,
        'project_file' => UploadedFile::fake()->create('project.pdf', 20, 'application/pdf'),
        'preview_video_upload_token' => $replacement->token,
    ])->assertRedirect(route('seller.products.index'));

    $this->assertDatabaseMissing('product_video_uploads', ['id' => $oldUpload->id]);
    $product->refresh();
    $replacement->refresh();
    expect($product->preview_video_processing_token)->toBe($replacement->token)
        ->and($replacement->status)->toBe('claimed');
    Storage::disk('local')->assertMissing($oldSource);
    Queue::assertPushed(ProcessProductPreviewVideo::class, function (ProcessProductPreviewVideo $job) use ($product): bool {
        return $job->productId === $product->id;
    });

});
test('removing a product preview deletes its attached upload row and private source', function () {
    Storage::fake('local');
    Storage::fake('public');
    $seller = User::factory()->create(['role' => 'seller']);
    $token = (string) Str::uuid();
    $source = 'product-video-uploads/'.$token.'/source.mp4';
    Storage::disk('local')->put($source, 'private source');
    Storage::disk('public')->put('products/preview-videos/current.mp4', 'current preview');
    $product = videoProduct($seller, [
        'preview_video' => 'products/preview-videos/current.mp4',
        'preview_video_source_path' => $source,
        'preview_video_processing_token' => $token,
        'preview_video_status' => 'queued',
    ]);
    $upload = ProductVideoUpload::create([
        'user_id' => $seller->id,
        'product_id' => $product->id,
        'token' => $token,
        'original_name' => 'current.mp4',
        'mime_type' => 'video/mp4',
        'expected_size' => 14,
        'received_size' => 14,
        'total_chunks' => 1,
        'uploaded_chunks' => [0],
        'source_path' => $source,
        'status' => 'claimed',
        'completed_at' => now(),
        'claimed_at' => now(),
        'expires_at' => now()->addDay(),
    ]);

    $this->actingAs($seller)->put(route('seller.products.update', $product), [
        'title' => $product->title,
        'project_file' => UploadedFile::fake()->create('project.pdf', 20, 'application/pdf'),
        'remove_video' => true,
    ])->assertRedirect(route('seller.products.index'));

    $this->assertDatabaseMissing('product_video_uploads', ['id' => $upload->id]);
    $product->refresh();
    expect($product->preview_video)->toBeNull()
        ->and($product->preview_video_status)->toBe('none');
    Storage::disk('local')->assertMissing($source);
    Storage::disk('public')->assertMissing('products/preview-videos/current.mp4');
});

test('assembling uploads consume seller and global active byte quotas', function () {
    config()->set('video.max_source_size', 20);
    config()->set('video.max_active_uploads', 10);
    config()->set('video.max_active_upload_bytes', 5);
    config()->set('video.max_global_active_upload_bytes', 100);
    config()->set('video.minimum_free_bytes', 0);
    $seller = User::factory()->create(['role' => 'seller']);
    $otherSeller = User::factory()->create(['role' => 'seller']);
    ProductVideoUpload::create([
        'user_id' => $seller->id,
        'token' => (string) Str::uuid(),
        'original_name' => 'assembling.mp4',
        'mime_type' => 'video/mp4',
        'expected_size' => 4,
        'received_size' => 4,
        'total_chunks' => 1,
        'uploaded_chunks' => [0],
        'status' => 'assembling',
        'expires_at' => now()->addDay(),
    ]);

    $this->actingAs($seller)->postJson(route('seller.product-video-uploads.initialize'), [
        'name' => 'seller-over-quota.mp4',
        'size' => 2,
        'mime_type' => 'video/mp4',
    ])->assertUnprocessable();

    $this->actingAs($otherSeller)->postJson(route('seller.product-video-uploads.initialize'), [
        'name' => 'other-seller.mp4',
        'size' => 2,
        'mime_type' => 'video/mp4',
    ])->assertCreated();

    config()->set('video.max_global_active_upload_bytes', 7);
    $this->actingAs($otherSeller)->postJson(route('seller.product-video-uploads.initialize'), [
        'name' => 'global-over-quota.mp4',
        'size' => 2,
        'mime_type' => 'video/mp4',
    ])->assertUnprocessable();

    expect(ProductVideoUpload::query()->where('user_id', $seller->id)->count())->toBe(1)
        ->and(ProductVideoUpload::query()->where('user_id', $otherSeller->id)->count())->toBe(1);
});
test('initialize refuses uploads when the configured free-disk reserve is unavailable', function () {
    config()->set('video.max_source_size', 20);
    config()->set('video.max_active_uploads', 10);
    config()->set('video.max_active_upload_bytes', PHP_INT_MAX);
    config()->set('video.max_global_active_upload_bytes', PHP_INT_MAX);
    config()->set('video.minimum_free_bytes', PHP_INT_MAX);
    $seller = User::factory()->create(['role' => 'seller']);

    $this->actingAs($seller)->postJson(route('seller.product-video-uploads.initialize'), [
        'name' => 'reserve-blocked.mp4',
        'size' => 1,
        'mime_type' => 'video/mp4',
    ])->assertUnprocessable();

    $this->assertDatabaseCount('product_video_uploads', 0);
});

test('completion respects the global assembly lock without changing resumable upload state', function () {
    config()->set('video.chunk_size', 4);
    config()->set('video.minimum_free_bytes', 0);
    config()->set('video.assembly_lock_seconds', 60);
    Storage::fake('local');
    $seller = User::factory()->create(['role' => 'seller']);
    $token = (string) Str::uuid();
    $upload = ProductVideoUpload::create([
        'user_id' => $seller->id,
        'token' => $token,
        'original_name' => 'preview.mp4',
        'mime_type' => 'video/mp4',
        'expected_size' => 6,
        'received_size' => 6,
        'total_chunks' => 2,
        'uploaded_chunks' => [0, 1],
        'expires_at' => now()->addDay(),
    ]);
    $directory = 'product-video-uploads/'.$token.'/chunks';
    Storage::disk('local')->put($directory.'/0.part', 'ABCD');
    Storage::disk('local')->put($directory.'/1.part', 'EF');
    $lock = Cache::lock('product-video-assembly', 60);
    expect($lock->get())->toBeTrue();

    try {
        $this->actingAs($seller)->postJson(route('seller.product-video-uploads.complete', $token))
            ->assertUnprocessable();
    } finally {
        $lock->release();
    }

    $upload->refresh();
    expect($upload->status)->toBe('uploading')
        ->and($upload->received_size)->toBe(6)
        ->and($upload->uploaded_chunks)->toBe([0, 1]);
    Storage::disk('local')->assertExists($directory.'/0.part');
    Storage::disk('local')->assertExists($directory.'/1.part');
});

test('completion refuses before assembly when the safe storage reserve is unavailable', function () {
    config()->set('video.chunk_size', 4);
    config()->set('video.minimum_free_bytes', PHP_INT_MAX);
    Storage::fake('local');
    $seller = User::factory()->create(['role' => 'seller']);
    $token = (string) Str::uuid();
    $upload = ProductVideoUpload::create([
        'user_id' => $seller->id,
        'token' => $token,
        'original_name' => 'preview.mp4',
        'mime_type' => 'video/mp4',
        'expected_size' => 6,
        'received_size' => 6,
        'total_chunks' => 2,
        'uploaded_chunks' => [0, 1],
        'expires_at' => now()->addDay(),
    ]);
    $directory = 'product-video-uploads/'.$token.'/chunks';
    Storage::disk('local')->put($directory.'/0.part', 'ABCD');
    Storage::disk('local')->put($directory.'/1.part', 'EF');

    $this->actingAs($seller)->postJson(route('seller.product-video-uploads.complete', $token))
        ->assertUnprocessable();

    $upload->refresh();
    expect($upload->status)->toBe('uploading')
        ->and($upload->received_size)->toBe(6)
        ->and($upload->uploaded_chunks)->toBe([0, 1]);
    Storage::disk('local')->assertExists($directory.'/0.part');
    Storage::disk('local')->assertExists($directory.'/1.part');
    Storage::disk('local')->assertMissing('product-video-uploads/'.$token.'/source.mp4.assembling');
});

test('videos prune removes expired stale assemblies but preserves fresh assemblies', function () {
    config()->set('video.assembly_stale_seconds', 60);
    Storage::fake('local');
    $seller = User::factory()->create(['role' => 'seller']);
    $stale = ProductVideoUpload::create([
        'user_id' => $seller->id,
        'token' => (string) Str::uuid(),
        'original_name' => 'stale.mp4',
        'mime_type' => 'video/mp4',
        'expected_size' => 4,
        'total_chunks' => 1,
        'uploaded_chunks' => [0],
        'status' => 'assembling',
        'expires_at' => now()->subMinute(),
    ]);
    $fresh = ProductVideoUpload::create([
        'user_id' => $seller->id,
        'token' => (string) Str::uuid(),
        'original_name' => 'fresh.mp4',
        'mime_type' => 'video/mp4',
        'expected_size' => 4,
        'total_chunks' => 1,
        'uploaded_chunks' => [0],
        'status' => 'assembling',
        'expires_at' => now()->subMinute(),
    ]);
    ProductVideoUpload::query()->whereKey($stale->id)->update(['updated_at' => now()->subSeconds(61)]);
    ProductVideoUpload::query()->whereKey($fresh->id)->update(['updated_at' => now()->subSeconds(59)]);
    $staleDirectory = 'product-video-uploads/'.$stale->token;
    $freshDirectory = 'product-video-uploads/'.$fresh->token;
    Storage::disk('local')->put($staleDirectory.'/source.mp4.assembling', 'stale');
    Storage::disk('local')->put($freshDirectory.'/source.mp4.assembling', 'fresh');

    $this->artisan('videos:prune')->assertExitCode(0);

    $this->assertDatabaseMissing('product_video_uploads', ['id' => $stale->id]);
    $this->assertDatabaseHas('product_video_uploads', ['id' => $fresh->id, 'status' => 'assembling']);
    Storage::disk('local')->assertMissing($staleDirectory.'/source.mp4.assembling');
    Storage::disk('local')->assertExists($freshDirectory.'/source.mp4.assembling');
});

test('retrying a valid orphan chunk reconciles upload progress after a crash', function () {
    config()->set('video.chunk_size', 4);
    config()->set('video.minimum_free_bytes', 0);
    Storage::fake('local');
    $seller = User::factory()->create(['role' => 'seller']);
    $token = (string) Str::uuid();
    $upload = ProductVideoUpload::create([
        'user_id' => $seller->id,
        'token' => $token,
        'original_name' => 'preview.mp4',
        'mime_type' => 'video/mp4',
        'expected_size' => 4,
        'received_size' => 0,
        'total_chunks' => 1,
        'uploaded_chunks' => [],
        'expires_at' => now()->addDay(),
    ]);
    $chunkPath = 'product-video-uploads/'.$token.'/chunks/0.part';
    Storage::disk('local')->put($chunkPath, 'ABCD');

    $this->actingAs($seller)->put(route('seller.product-video-uploads.chunks.store', [$token, 0]), [
        'chunk' => UploadedFile::fake()->createWithContent('0.part', 'ABCD'),
        'checksum' => hash('sha256', 'ABCD'),
    ])->assertOk()
        ->assertJsonPath('received_size', 4)
        ->assertJsonPath('uploaded_chunks', [0]);

    $upload->refresh();
    expect($upload->received_size)->toBe(4)
        ->and($upload->uploaded_chunks)->toBe([0]);

    $this->actingAs($seller)->postJson(route('seller.product-video-uploads.complete', $token))
        ->assertOk()->assertJsonPath('status', 'completed');

    $upload->refresh();
    expect($upload->status)->toBe('completed')
        ->and(Storage::disk('local')->get($upload->source_path))->toBe('ABCD');
});

test('retrying replaces a wrong-sized orphan chunk and restores resumable progress', function () {
    config()->set('video.chunk_size', 4);
    config()->set('video.minimum_free_bytes', 0);
    Storage::fake('local');
    $seller = User::factory()->create(['role' => 'seller']);
    $token = (string) Str::uuid();
    $upload = ProductVideoUpload::create([
        'user_id' => $seller->id,
        'token' => $token,
        'original_name' => 'preview.mp4',
        'mime_type' => 'video/mp4',
        'expected_size' => 4,
        'received_size' => 0,
        'total_chunks' => 1,
        'uploaded_chunks' => [],
        'expires_at' => now()->addDay(),
    ]);
    $chunkPath = 'product-video-uploads/'.$token.'/chunks/0.part';
    Storage::disk('local')->put($chunkPath, 'BAD');

    $this->actingAs($seller)->put(route('seller.product-video-uploads.chunks.store', [$token, 0]), [
        'chunk' => UploadedFile::fake()->createWithContent('0.part', 'ABCD'),
        'checksum' => hash('sha256', 'ABCD'),
    ])->assertOk()
        ->assertJsonPath('received_size', 4)
        ->assertJsonPath('uploaded_chunks', [0]);

    $upload->refresh();
    expect($upload->received_size)->toBe(4)
        ->and($upload->uploaded_chunks)->toBe([0])
        ->and(Storage::disk('local')->get($chunkPath))->toBe('ABCD');

    $this->actingAs($seller)->postJson(route('seller.product-video-uploads.complete', $token))
        ->assertOk()->assertJsonPath('status', 'completed');

    $upload->refresh();
    expect($upload->status)->toBe('completed')
        ->and(Storage::disk('local')->get($upload->source_path))->toBe('ABCD');
});
