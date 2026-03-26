<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Download;
use App\Models\Order;
use App\Models\PayoutRequest;
use App\Models\Product;
use App\Models\Revenue;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    private function monthExpression(): string
    {
        return DB::getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', created_at)"
            : "DATE_FORMAT(created_at, '%Y-%m')";
    }

    public function index()
    {
        $now = now();
        $monthStart = $now->copy()->startOfMonth();

        $stats = [
            'total_users' => User::count(),
            'users_this_month' => User::where('created_at', '>=', $monthStart)->count(),
            'total_products' => Product::count(),
            'products_this_month' => Product::where('created_at', '>=', $monthStart)->count(),
            'total_downloads' => Download::count(),
            'downloads_this_month' => Download::where('created_at', '>=', $monthStart)->count(),
            'total_orders' => Order::count(),
            'orders_this_month' => Order::where('created_at', '>=', $monthStart)->count(),
            'total_revenue' => Revenue::sum('amount_usd'),
            'revenue_this_month' => Revenue::where('created_at', '>=', $monthStart)->sum('amount_usd'),
            'orders_revenue' => Order::where('status', 'completed')->sum('total'),
            'orders_revenue_this_month' => Order::where('status', 'completed')->where('created_at', '>=', $monthStart)->sum('total'),
        ];

        $pending = [
            'reviews' => Review::where('is_approved', false)->count(),
            'seller_apps' => User::where('role', 'user')->whereHas('sellerProfile')->where('is_seller_approved', false)->count(),
            'payouts' => PayoutRequest::where('status', 'pending')->count(),
        ];

        $recentUsers = User::latest()->take(10)->get();
        $recentProducts = Product::with('user')->latest()->take(10)->get();
        $popularProducts = Product::orderByDesc('views_count')->take(10)->get();

        $yearStart = $now->copy()->startOfYear();
        $monthExpr = $this->monthExpression();

        $monthlyViews = Revenue::where('type', 'view')
            ->where('created_at', '>=', $yearStart)
            ->selectRaw("{$monthExpr} as month, COUNT(*) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        $monthlyDownloads = Download::where('created_at', '>=', $yearStart)
            ->selectRaw("{$monthExpr} as month, COUNT(*) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        // Fill all months of the current year
        $allMonths = collect();
        for ($m = 1; $m <= 12; $m++) {
            $key = $now->copy()->month($m)->format('Y-m');
            $allMonths[$key] = [
                'views' => $monthlyViews[$key] ?? 0,
                'downloads' => $monthlyDownloads[$key] ?? 0,
            ];
        }

        return view('admin.dashboard', compact('stats', 'pending', 'recentUsers', 'recentProducts', 'popularProducts', 'allMonths'));
    }
}
