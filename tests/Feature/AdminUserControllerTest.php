<?php

use App\Models\PayoutRequest;
use App\Models\Product;
use App\Models\Revenue;
use App\Models\User;

test('admin can view a user with paid payout totals', function () {
    /** @var User $admin */
    $admin = User::factory()->create(['role' => 'admin']);
    /** @var User $user */
    $user = User::factory()->create(['role' => 'seller']);
    $product = Product::create([
        'user_id' => $user->id,
        'title' => 'Seller Product',
        'slug' => 'seller-product',
        'status' => 'published',
    ]);

    Revenue::create([
        'product_id' => $product->id,
        'user_id' => $user->id,
        'type' => 'sale',
        'amount_usd' => 12.50,
    ]);

    PayoutRequest::create([
        'user_id' => $user->id,
        'amount_usd' => 3.25,
        'status' => 'paid',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.users.show', $user));

    $response->assertOk();
    $response->assertSee('Impersonate');
    $response->assertViewHas('totalRevenue', fn ($value) => (float) $value === 12.5);
    $response->assertViewHas('totalPaidOut', fn ($value) => (float) $value === 3.25);
    $response->assertViewHas('balance', fn ($value) => (float) $value === 9.25);
});

test('admin can impersonate a non admin user', function () {
    /** @var User $admin */
    $admin = User::factory()->create(['role' => 'admin']);
    /** @var User $user */
    $user = User::factory()->create(['role' => 'user']);

    $response = $this->actingAs($admin)->post(route('admin.users.impersonate', $user));

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('impersonator_id', $admin->id);
    $this->assertAuthenticatedAs($user);
});

test('impersonated user can return to the admin account', function () {
    /** @var User $admin */
    $admin = User::factory()->create(['role' => 'admin']);
    /** @var User $user */
    $user = User::factory()->create(['role' => 'user']);

    $this->actingAs($admin)->post(route('admin.users.impersonate', $user));

    $response = $this->post(route('impersonation.stop'));

    $response->assertRedirect(route('admin.users.index'));
    $response->assertSessionMissing('impersonator_id');
    $this->assertAuthenticatedAs($admin);
});

test('admin cannot impersonate another admin', function () {
    /** @var User $admin */
    $admin = User::factory()->create(['role' => 'admin']);
    /** @var User $targetAdmin */
    $targetAdmin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post(route('admin.users.impersonate', $targetAdmin));

    $this->assertAuthenticatedAs($admin);
});
