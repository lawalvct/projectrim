<?php

use App\Mail\PayoutApprovedNotification;
use App\Mail\ProductDownloadedNotification;
use App\Models\PayoutRequest;
use App\Models\Product;
use App\Models\ProductFile;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

function downloadableProduct(User $creator): Product
{
    $product = Product::create([
        'user_id' => $creator->id,
        'title' => 'Creator Download Notification',
        'slug' => 'creator-download-notification',
        'status' => 'published',
        'price' => 0,
        'is_paid' => false,
    ]);

    ProductFile::create([
        'product_id' => $product->id,
        'file_path' => 'products/files/creator-download.pdf',
        'file_name' => 'creator-download.pdf',
        'file_size' => 12,
        'file_type' => 'pdf',
    ]);

    Storage::disk('public')->put('products/files/creator-download.pdf', 'project file');

    return $product;
}

test('a product creator receives a queued email when another user downloads the product', function () {
    Storage::fake('public');
    Mail::fake();

    config()->set('queue.notifications.connection', 'database');
    config()->set('queue.notifications.queue', 'default');

    $creator = User::factory()->create(['role' => 'seller']);
    $downloader = User::factory()->create();
    $product = downloadableProduct($creator);

    $this->actingAs($downloader)
        ->get(route('download.product', $product))
        ->assertOk();

    $this->assertDatabaseHas('downloads', [
        'product_id' => $product->id,
        'user_id' => $downloader->id,
    ]);

    Mail::assertQueued(ProductDownloadedNotification::class, function (ProductDownloadedNotification $mail) use ($creator, $downloader, $product) {
        return $mail->hasTo($creator->email)
            && $mail->product->is($product)
            && $mail->downloader->is($downloader)
            && $mail->connection === 'database'
            && $mail->queue === 'default';
    });
});

test('downloading your own product does not email you', function () {
    Storage::fake('public');
    Mail::fake();

    $creator = User::factory()->create(['role' => 'seller']);
    $product = downloadableProduct($creator);

    $this->actingAs($creator)
        ->get(route('download.product', $product))
        ->assertOk();

    Mail::assertNothingQueued();
});

test('download succeeds when its email notification cannot be queued', function () {
    Storage::fake('public');

    config()->set('queue.notifications.connection', 'missing-download-queue');

    $creator = User::factory()->create(['role' => 'seller']);
    $downloader = User::factory()->create();
    $product = downloadableProduct($creator);

    $this->actingAs($downloader)
        ->get(route('download.product', $product))
        ->assertOk();

    $this->assertDatabaseHas('downloads', [
        'product_id' => $product->id,
        'user_id' => $downloader->id,
    ]);
});

test('a payout owner receives one queued email when the payout is approved', function () {
    Mail::fake();

    config()->set('queue.notifications.connection', 'database');
    config()->set('queue.notifications.queue', 'default');

    $admin = User::factory()->create(['role' => 'admin']);
    $seller = User::factory()->create(['role' => 'seller']);
    $payout = PayoutRequest::create([
        'user_id' => $seller->id,
        'amount_usd' => 42.50,
        'status' => 'pending',
    ]);

    $this->actingAs($admin)
        ->from(route('admin.payouts.show', $payout))
        ->post(route('admin.payouts.approve', $payout))
        ->assertRedirect(route('admin.payouts.show', $payout));

    expect($payout->fresh()->status)->toBe('approved');

    Mail::assertQueued(PayoutApprovedNotification::class, function (PayoutApprovedNotification $mail) use ($payout, $seller) {
        return $mail->hasTo($seller->email)
            && $mail->payout->is($payout)
            && $mail->connection === 'database'
            && $mail->queue === 'default';
    });

    $this->actingAs($admin)->post(route('admin.payouts.approve', $payout));

    Mail::assertQueuedCount(1);
});

test('payout approval succeeds when its email notification cannot be queued', function () {
    config()->set('queue.notifications.connection', 'missing-payout-queue');

    $admin = User::factory()->create(['role' => 'admin']);
    $seller = User::factory()->create(['role' => 'seller']);
    $payout = PayoutRequest::create([
        'user_id' => $seller->id,
        'amount_usd' => 20,
        'status' => 'pending',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.payouts.approve', $payout))
        ->assertRedirect();

    expect($payout->fresh()->status)->toBe('approved');
});
