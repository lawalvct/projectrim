<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\PayoutRequest;
use App\Models\Product;
use App\Models\Revenue;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SellerOverviewController extends Controller
{
    public function index(Request $request): Response
    {
        $userId = $request->user()->id;

        $totalRevenue = Revenue::where('user_id', $userId)->sum('amount_usd');
        $saleRevenue = Revenue::where('user_id', $userId)->where('type', 'sale')->sum('amount_usd');
        $viewRevenue = Revenue::where('user_id', $userId)->where('type', 'view')->sum('amount_usd');
        $downloadRevenue = Revenue::where('user_id', $userId)->where('type', 'download')->sum('amount_usd');

        $totalPaidOut = PayoutRequest::where('user_id', $userId)
            ->where('status', 'paid')
            ->sum('amount_usd');

        $pendingPayout = PayoutRequest::where('user_id', $userId)
            ->where('status', 'pending')
            ->sum('amount_usd');

        $availableBalance = $totalRevenue - $totalPaidOut - $pendingPayout;

        $totalProducts = Product::where('user_id', $userId)->count();
        $publishedProducts = Product::where('user_id', $userId)->where('status', 'published')->count();

        $totalOrders = OrderItem::whereHas('product', fn ($q) => $q->where('user_id', $userId))->count();

        // Revenue over last 6 months
        $monthlyRevenue = Revenue::where('user_id', $userId)
            ->where('created_at', '>=', now()->subMonths(6))
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, SUM(amount_usd) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn ($r) => ['month' => $r->month, 'total' => (float) $r->total]);

        // Top performing products
        $topProducts = Product::where('user_id', $userId)
            ->where('status', 'published')
            ->withCount(['downloads', 'reviews'])
            ->orderByDesc('downloads_count')
            ->take(5)
            ->get(['id', 'title', 'slug', 'views_count', 'downloads_count', 'likes_count']);

        // Recent revenue entries
        $recentRevenue = Revenue::with('product:id,title,slug')
            ->where('user_id', $userId)
            ->latest()
            ->take(10)
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'type' => $r->type,
                'amount_usd' => (float) $r->amount_usd,
                'product' => $r->product ? [
                    'title' => $r->product->title,
                    'slug' => $r->product->slug,
                ] : null,
                'created_at' => $r->created_at->diffForHumans(),
            ]);

        return Inertia::render('dashboard/seller/Overview', [
            'stats' => [
                'total_revenue' => (float) $totalRevenue,
                'sale_revenue' => (float) $saleRevenue,
                'view_revenue' => (float) $viewRevenue,
                'download_revenue' => (float) $downloadRevenue,
                'total_paid_out' => (float) $totalPaidOut,
                'pending_payout' => (float) $pendingPayout,
                'available_balance' => (float) $availableBalance,
                'total_products' => $totalProducts,
                'published_products' => $publishedProducts,
                'total_orders' => $totalOrders,
            ],
            'monthlyRevenue' => $monthlyRevenue,
            'topProducts' => $topProducts,
            'recentRevenue' => $recentRevenue,
        ]);
    }
}
