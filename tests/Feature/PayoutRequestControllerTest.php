<?php

use App\Models\PaymentMethod;
use App\Models\PayoutRequest;
use App\Models\Product;
use App\Models\Revenue;
use App\Models\Setting;
use App\Models\User;

test('seller cannot request a payout below the configured minimum', function () {
    $seller = User::factory()->create(['role' => 'seller']);
    $product = Product::create([
        'user_id' => $seller->id,
        'title' => 'Payout Minimum Product',
        'slug' => 'payout-minimum-product',
        'status' => 'published',
    ]);
    $paymentMethod = PaymentMethod::create(['name' => 'Bank Transfer', 'is_active' => true]);

    Setting::setValue('minimum_payout', '5', 'seller', 'number');
    Revenue::create([
        'product_id' => $product->id,
        'user_id' => $seller->id,
        'type' => 'sale',
        'amount_usd' => 10,
    ]);

    $response = $this->actingAs($seller)
        ->from(route('seller.payouts.index'))
        ->post(route('seller.payouts.store'), [
            'amount_usd' => '4.99',
            'payment_method_id' => $paymentMethod->id,
        ]);

    $response->assertRedirect(route('seller.payouts.index'));
    $response->assertSessionHasErrors('amount_usd');
    expect(PayoutRequest::count())->toBe(0);
});

test('seller can request a payout at the configured minimum', function () {
    $seller = User::factory()->create(['role' => 'seller']);
    $product = Product::create([
        'user_id' => $seller->id,
        'title' => 'Payout Exact Minimum Product',
        'slug' => 'payout-exact-minimum-product',
        'status' => 'published',
    ]);
    $paymentMethod = PaymentMethod::create(['name' => 'Bank Transfer', 'is_active' => true]);

    Setting::setValue('minimum_payout', '5', 'seller', 'number');
    Revenue::create([
        'product_id' => $product->id,
        'user_id' => $seller->id,
        'type' => 'sale',
        'amount_usd' => 10,
    ]);

    $response = $this->actingAs($seller)
        ->from(route('seller.payouts.index'))
        ->post(route('seller.payouts.store'), [
            'amount_usd' => '5.00',
            'payment_method_id' => $paymentMethod->id,
        ]);

    $response->assertRedirect(route('seller.payouts.index'));
    $response->assertSessionHasNoErrors();

    expect(PayoutRequest::query()->sole()->amount_usd)->toBe('5.00');
});
