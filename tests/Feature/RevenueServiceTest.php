<?php

use App\Models\Product;
use App\Models\Revenue;
use App\Models\Setting;
use App\Models\User;
use App\Services\RevenueService;

test('view revenue uses the admin configured rate per thousand views', function () {
    $owner = User::factory()->create();
    $product = Product::create([
        'user_id' => $owner->id,
        'title' => 'Admin Rate Product',
        'slug' => 'admin-rate-product',
        'status' => 'published',
    ]);

    Setting::setValue('view_reward_rate', '5.00', 'monetization', 'number');

    app(RevenueService::class)->recordViewRevenue($product, '203.0.113.10');

    expect((float) Revenue::query()->sole()->amount_usd)->toBe(0.005);
});

test('download revenue uses the admin configured rate per download', function () {
    $owner = User::factory()->create();
    $product = Product::create([
        'user_id' => $owner->id,
        'title' => 'Download Rate Product',
        'slug' => 'download-rate-product',
        'status' => 'published',
    ]);

    Setting::setValue('download_reward_rate', '2.75', 'monetization', 'number');

    app(RevenueService::class)->recordDownloadRevenue($product, $owner->id, '203.0.113.11');

    expect((float) Revenue::query()->sole()->amount_usd)->toBe(2.75);
});
