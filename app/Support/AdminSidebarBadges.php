<?php

namespace App\Support;

use App\Models\Message;
use App\Models\Order;
use App\Models\PayoutRequest;
use App\Models\Product;
use App\Models\Report;
use App\Models\Review;
use App\Models\SellerProfile;
use App\Models\User;

class AdminSidebarBadges
{
    private const KEYS = [
        'users',
        'products',
        'orders',
        'creator-applications',
        'payouts',
        'reviews',
        'reports',
        'messages',
    ];

    public static function counts(): array
    {
        self::ensureTrackingIsInitialized();

        $seen = session('admin_sidebar_seen', []);

        return [
            'users' => User::where('created_at', '>', $seen['users'])->count(),
            'products' => Product::where('created_at', '>', $seen['products'])->count(),
            'orders' => Order::where('created_at', '>', $seen['orders'])->count(),
            'creator-applications' => SellerProfile::where('created_at', '>', $seen['creator-applications'])
                ->whereHas('user', fn ($query) => $query->where('role', 'user')->where('is_seller_approved', false))
                ->count(),
            'payouts' => PayoutRequest::where('created_at', '>', $seen['payouts'])->count(),
            'reviews' => Review::where('created_at', '>', $seen['reviews'])->count(),
            'reports' => Report::where('created_at', '>', $seen['reports'])->count(),
            'messages' => Message::where('created_at', '>', $seen['messages'])->count(),
        ];
    }

    public static function markAsSeen(string $key): void
    {
        self::ensureTrackingIsInitialized();

        $seen = session('admin_sidebar_seen', []);
        $seen[$key] = now()->toDateTimeString();

        session(['admin_sidebar_seen' => $seen]);
    }

    private static function ensureTrackingIsInitialized(): void
    {
        $seen = session('admin_sidebar_seen');

        if (is_array($seen)) {
            return;
        }

        $timestamp = now()->toDateTimeString();
        session(['admin_sidebar_seen' => array_fill_keys(self::KEYS, $timestamp)]);
    }
}