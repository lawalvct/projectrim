<?php

use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Dashboard\SellerApplicationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\Seller\SellerProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Public product detail
Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');

// Social authentication
Route::get('/auth/social/{provider}/redirect', [SocialAuthController::class, 'redirect'])->name('social.redirect');
Route::get('/auth/social/{provider}/callback', [SocialAuthController::class, 'callback'])->name('social.callback');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    // Seller application
    Route::get('dashboard/apply-seller', [SellerApplicationController::class, 'create'])->name('seller.apply');
    Route::post('dashboard/apply-seller', [SellerApplicationController::class, 'store'])->name('seller.apply.store');

    // Seller product management
    Route::middleware('role:seller')->prefix('dashboard/seller')->name('seller.')->group(function () {
        Route::resource('products', SellerProductController::class);
    });
});

require __DIR__.'/settings.php';
