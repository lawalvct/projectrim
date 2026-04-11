<?php

use App\Http\Controllers\Admin\AdminAnalyticsController;
use App\Http\Controllers\Admin\AdminCampaignController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminDepartmentController;
use App\Http\Controllers\Admin\AdminFacultyController;
use App\Http\Controllers\Admin\AdminMessageController;
use App\Http\Controllers\Admin\AdminNewsletterController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminPageController;
use App\Http\Controllers\Admin\AdminPaymentMethodController;
use App\Http\Controllers\Admin\AdminPayoutController;
use App\Http\Controllers\Admin\AdminProductController;
use App\Http\Controllers\Admin\AdminReportController;
use App\Http\Controllers\Admin\AdminReviewController;
use App\Http\Controllers\Admin\AdminSellerApplicationController;
use App\Http\Controllers\Admin\AdminSettingController;
use App\Http\Controllers\Admin\AdminUserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Users
    Route::resource('users', AdminUserController::class)->only(['index', 'show', 'edit', 'update', 'destroy']);

    // Products
    Route::resource('products', AdminProductController::class)->only(['index', 'show', 'edit', 'update', 'destroy']);
    Route::post('products/{product}/approve', [AdminProductController::class, 'approve'])->name('products.approve');
    Route::post('products/{product}/reject', [AdminProductController::class, 'reject'])->name('products.reject');

    // Orders
    Route::resource('orders', AdminOrderController::class)->only(['index', 'show']);

    // Seller Applications
    Route::get('seller-applications', [AdminSellerApplicationController::class, 'index'])->name('seller-applications.index');
    Route::get('seller-applications/{user}', [AdminSellerApplicationController::class, 'show'])->name('seller-applications.show');
    Route::post('seller-applications/{user}/approve', [AdminSellerApplicationController::class, 'approve'])->name('seller-applications.approve');
    Route::post('seller-applications/{user}/reject', [AdminSellerApplicationController::class, 'reject'])->name('seller-applications.reject');

    // Payouts
    Route::get('payouts', [AdminPayoutController::class, 'index'])->name('payouts.index');
    Route::get('payouts/{payout}', [AdminPayoutController::class, 'show'])->name('payouts.show');
    Route::post('payouts/{payout}/approve', [AdminPayoutController::class, 'approve'])->name('payouts.approve');
    Route::post('payouts/{payout}/pay', [AdminPayoutController::class, 'pay'])->name('payouts.pay');
    Route::post('payouts/{payout}/reject', [AdminPayoutController::class, 'reject'])->name('payouts.reject');

    // CMS Pages
    Route::resource('pages', AdminPageController::class);

    // Categories
    Route::resource('faculties', AdminFacultyController::class);
    Route::resource('departments', AdminDepartmentController::class);

    // Reviews
    Route::get('reviews', [AdminReviewController::class, 'index'])->name('reviews.index');
    Route::delete('reviews/{review}', [AdminReviewController::class, 'destroy'])->name('reviews.destroy');

    // Reports
    Route::get('reports', [AdminReportController::class, 'index'])->name('reports.index');
    Route::get('reports/{report}', [AdminReportController::class, 'show'])->name('reports.show');
    Route::post('reports/{report}/reviewed', [AdminReportController::class, 'reviewed'])->name('reports.reviewed');
    Route::post('reports/{report}/dismiss', [AdminReportController::class, 'dismiss'])->name('reports.dismiss');
    Route::delete('reports/{report}', [AdminReportController::class, 'destroy'])->name('reports.destroy');

    // Newsletter
    Route::get('newsletter/subscribers', [AdminNewsletterController::class, 'subscribers'])->name('newsletter.subscribers');
    Route::delete('newsletter/subscribers/{subscriber}', [AdminNewsletterController::class, 'destroySubscriber'])->name('newsletter.subscribers.destroy');
    Route::resource('newsletter/campaigns', AdminCampaignController::class);
    Route::post('newsletter/campaigns/{campaign}/send', [AdminCampaignController::class, 'send'])->name('campaigns.send');

    // Messages
    Route::get('messages', [AdminMessageController::class, 'index'])->name('messages.index');
    Route::get('messages/{message}', [AdminMessageController::class, 'show'])->name('messages.show');

    // Settings
    Route::get('settings/general', [AdminSettingController::class, 'general'])->name('settings.general');
    Route::get('settings/monetization', [AdminSettingController::class, 'monetization'])->name('settings.monetization');
    Route::get('settings/payment', [AdminSettingController::class, 'payment'])->name('settings.payment');
    Route::get('settings/seller', [AdminSettingController::class, 'seller'])->name('settings.seller');
    Route::get('settings/carousel', [AdminSettingController::class, 'carousel'])->name('settings.carousel');
    Route::post('settings', [AdminSettingController::class, 'update'])->name('settings.update');

    // Payment Methods (payout methods)
    Route::resource('payment-methods', AdminPaymentMethodController::class)->only(['index', 'store', 'update', 'destroy']);

    // Analytics
    Route::get('analytics/products', [AdminAnalyticsController::class, 'products'])->name('analytics.products');
    Route::get('analytics/users', [AdminAnalyticsController::class, 'users'])->name('analytics.users');
});
