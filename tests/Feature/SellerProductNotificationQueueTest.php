<?php

use App\Jobs\SendNewProductNotification;
use App\Mail\NewProductNotification;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

test('seller product creation queues notifications after the product is saved', function () {
    Storage::fake('public');
    Queue::fake();

    config()->set('queue.notifications.connection', 'database');
    config()->set('queue.notifications.queue', 'default');

    $seller = User::factory()->create(['role' => 'seller']);

    $response = $this->actingAs($seller)->post(route('seller.products.store'), [
        'title' => 'Queued Product Announcement',
        'price' => 0,
        'project_file' => UploadedFile::fake()->create('project.pdf', 20, 'application/pdf'),
        'notify_users' => true,
    ]);

    $product = Product::query()->sole();

    $response
        ->assertRedirect(route('seller.products.index'))
        ->assertSessionHas('status', 'Product created successfully. User notifications have been queued.');

    expect($product->status)->toBe('published');

    Queue::assertPushed(SendNewProductNotification::class, function (SendNewProductNotification $job) use ($product) {
        return $job->productId === $product->id
            && $job->connection === 'database'
            && $job->queue === 'default';
    });
});

test('product creation remains successful when notifications cannot be queued', function () {
    Storage::fake('public');

    config()->set('queue.notifications.connection', 'missing-notification-connection');

    $seller = User::factory()->create(['role' => 'seller']);

    $response = $this->actingAs($seller)->post(route('seller.products.store'), [
        'title' => 'Product Survives Queue Failure',
        'price' => 0,
        'project_file' => UploadedFile::fake()->create('project.pdf', 20, 'application/pdf'),
        'notify_users' => true,
    ]);

    $response
        ->assertRedirect(route('seller.products.index'))
        ->assertSessionHas('warning', 'Product created, but notifications could not be queued.');

    $this->assertDatabaseHas('products', [
        'user_id' => $seller->id,
        'title' => 'Product Survives Queue Failure',
        'status' => 'published',
    ]);
});

test('notification job queues one email for every user except the product owner', function () {
    Mail::fake();

    config()->set('queue.notifications.connection', 'database');
    config()->set('queue.notifications.queue', 'default');

    $seller = User::factory()->create(['role' => 'seller']);
    $recipients = User::factory()->count(3)->create();
    $product = Product::create([
        'user_id' => $seller->id,
        'title' => 'Background Email Product',
        'slug' => 'background-email-product',
        'status' => 'published',
        'published_at' => now(),
    ]);

    (new SendNewProductNotification($product->id))->handle();

    Mail::assertQueuedCount(3);

    foreach ($recipients as $recipient) {
        Mail::assertQueued(NewProductNotification::class, function (NewProductNotification $mail) use ($product, $recipient) {
            return $mail->product->is($product)
                && $mail->hasTo($recipient->email)
                && $mail->connection === 'database'
                && $mail->queue === 'default';
        });
    }

    Mail::assertNotQueued(NewProductNotification::class, fn (NewProductNotification $mail) => $mail->hasTo($seller->email));
});

test('notification job safely ignores products that are no longer published', function () {
    Mail::fake();

    $seller = User::factory()->create(['role' => 'seller']);
    User::factory()->count(2)->create();
    $product = Product::create([
        'user_id' => $seller->id,
        'title' => 'Draft Product',
        'slug' => 'draft-product',
        'status' => 'draft',
    ]);

    (new SendNewProductNotification($product->id))->handle();

    Mail::assertNothingQueued();
});
