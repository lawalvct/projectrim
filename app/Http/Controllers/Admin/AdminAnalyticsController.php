<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Download;
use App\Models\Product;
use App\Models\Revenue;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdminAnalyticsController extends Controller
{
    private function monthExpression(): string
    {
        return DB::getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', created_at)"
            : "DATE_FORMAT(created_at, '%Y-%m')";
    }

    public function products()
    {
        $topByViews = Product::orderByDesc('views_count')->take(20)->get(['id', 'title', 'slug', 'views_count', 'downloads_count', 'likes_count']);
        $topByDownloads = Product::orderByDesc('downloads_count')->take(20)->get(['id', 'title', 'slug', 'views_count', 'downloads_count', 'likes_count']);
        $topByRevenue = Product::select('products.id', 'products.title', 'products.slug')
            ->selectRaw('COALESCE(SUM(revenues.amount_usd), 0) as total_revenue')
            ->leftJoin('revenues', 'products.id', '=', 'revenues.product_id')
            ->groupBy('products.id', 'products.title', 'products.slug')
            ->orderByDesc('total_revenue')
            ->take(20)
            ->get();

        $monthlyUploads = Product::selectRaw($this->monthExpression().' as month, count(*) as count')
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');

        return view('admin.analytics.products', compact('topByViews', 'topByDownloads', 'topByRevenue', 'monthlyUploads'));
    }

    public function users()
    {
        $topSellers = User::where('role', 'seller')
            ->withCount('products')
            ->with(['revenues' => fn ($q) => $q->selectRaw('user_id, SUM(amount_usd) as total')->groupBy('user_id')])
            ->orderByDesc('products_count')
            ->take(20)
            ->get();

        $monthlyRegistrations = User::selectRaw($this->monthExpression().' as month, count(*) as count')
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');

        $roleCounts = User::selectRaw('role, count(*) as count')->groupBy('role')->pluck('count', 'role');

        return view('admin.analytics.users', compact('topSellers', 'monthlyRegistrations', 'roleCounts'));
    }
}
