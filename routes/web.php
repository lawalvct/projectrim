<?php

use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\BrowseController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\ExploreController;
use App\Http\Controllers\Dashboard\SellerApplicationController;
use App\Http\Controllers\Dashboard\UserDownloadController;
use App\Http\Controllers\Dashboard\UserMessageController;
use App\Http\Controllers\Dashboard\UserOrderController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Seller\SellerProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Public product catalog & detail
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');

// Search
Route::get('/search', [SearchController::class, 'index'])->name('search');

// Browse by
Route::get('/faculty/{slug}', [BrowseController::class, 'faculty'])->name('browse.faculty');
Route::get('/department/{slug}', [BrowseController::class, 'department'])->name('browse.department');
Route::get('/institution/{name}', [BrowseController::class, 'institution'])->name('browse.institution');
Route::get('/author/{id}', [BrowseController::class, 'author'])->name('browse.author');
Route::get('/country/{code}', [BrowseController::class, 'country'])->name('browse.country');
Route::get('/tags/{slug}', [BrowseController::class, 'tag'])->name('browse.tag');

// CMS pages
Route::get('/pages/{slug}', [PageController::class, 'show'])->name('page.show');

// Social authentication
Route::get('/auth/social/{provider}/redirect', [SocialAuthController::class, 'redirect'])->name('social.redirect');
Route::get('/auth/social/{provider}/callback', [SocialAuthController::class, 'callback'])->name('social.callback');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('dashboard/downloads', [UserDownloadController::class, 'index'])->name('dashboard.downloads');
    Route::get('dashboard/orders', [UserOrderController::class, 'index'])->name('dashboard.orders');
    Route::get('dashboard/messages', [UserMessageController::class, 'index'])->name('dashboard.messages');
    Route::patch('dashboard/messages/{id}/read', [UserMessageController::class, 'markAsRead'])->name('dashboard.messages.read');
    Route::post('dashboard/messages/mark-all-read', [UserMessageController::class, 'markAllAsRead'])->name('dashboard.messages.mark-all-read');
    Route::get('dashboard/explore', [ExploreController::class, 'index'])->name('dashboard.explore');

    // Seller application
    Route::get('dashboard/apply-seller', [SellerApplicationController::class, 'create'])->name('seller.apply');
    Route::post('dashboard/apply-seller', [SellerApplicationController::class, 'store'])->name('seller.apply.store');

    // Seller product management
    Route::middleware('role:seller')->prefix('dashboard/seller')->name('seller.')->group(function () {
        Route::resource('products', SellerProductController::class);
    });
});

require __DIR__.'/settings.php';
