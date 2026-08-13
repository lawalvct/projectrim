<?php

use App\Models\Product;
use App\Models\Review;
use App\Models\User;

function reviewTestProduct(User $owner): Product
{
    return Product::create([
        'user_id' => $owner->id,
        'title' => 'Repeat Review Product',
        'slug' => 'repeat-review-product',
        'status' => 'published',
    ]);
}

function createReviewHistory(Product $product, User $reviewer, int $count, DateTimeInterface $createdAt): void
{
    foreach (range(1, $count) as $sequence) {
        Review::create([
            'product_id' => $product->id,
            'user_id' => $reviewer->id,
            'rating' => 4,
            'comment' => "Previous review {$sequence}",
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }
}

test('a user can review the same product more than once', function () {
    $owner = User::factory()->create(['role' => 'seller']);
    $reviewer = User::factory()->create();
    $product = reviewTestProduct($owner);

    foreach ([5, 4] as $rating) {
        $this->actingAs($reviewer)
            ->postJson(route('products.reviews.store', $product), [
                'rating' => $rating,
                'comment' => "Review rated {$rating}",
            ])
            ->assertOk();
    }

    expect($product->reviews()->where('user_id', $reviewer->id)->count())->toBe(2);
});

test('a user is limited to five reviews per product per hour', function () {
    $owner = User::factory()->create(['role' => 'seller']);
    $reviewer = User::factory()->create();
    $product = reviewTestProduct($owner);

    createReviewHistory($product, $reviewer, 5, now()->subMinutes(10));

    $this->actingAs($reviewer)
        ->postJson(route('products.reviews.store', $product), [
            'rating' => 5,
            'comment' => 'This review should be rate limited.',
        ])
        ->assertStatus(429)
        ->assertJsonPath(
            'message',
            'You can submit up to 5 reviews for this product per hour. Please try again later.'
        );

    expect($product->reviews()->where('user_id', $reviewer->id)->count())->toBe(5);
});

test('reviews older than one hour do not count against the limit', function () {
    $owner = User::factory()->create(['role' => 'seller']);
    $reviewer = User::factory()->create();
    $product = reviewTestProduct($owner);

    createReviewHistory($product, $reviewer, 5, now()->subMinutes(61));

    $this->actingAs($reviewer)
        ->postJson(route('products.reviews.store', $product), [
            'rating' => 3,
            'comment' => 'A new review after the rolling window.',
        ])
        ->assertOk();

    expect($product->reviews()->where('user_id', $reviewer->id)->count())->toBe(6);
});
