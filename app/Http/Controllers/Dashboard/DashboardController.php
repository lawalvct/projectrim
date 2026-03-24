<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Download;
use App\Models\Order;
use App\Models\MessageRecipient;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $stats = [
            'total_downloads' => Download::where('user_id', $user->id)->count(),
            'total_orders' => Order::where('user_id', $user->id)->count(),
            'unread_messages' => MessageRecipient::where('user_id', $user->id)
                ->where('is_read', false)
                ->count(),
            'total_spent' => Order::where('user_id', $user->id)
                ->where('status', 'completed')
                ->sum('total'),
        ];

        $recentDownloads = Download::with('product:id,title,slug')
            ->where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get()
            ->map(fn ($d) => [
                'id' => $d->id,
                'product' => $d->product ? [
                    'title' => $d->product->title,
                    'slug' => $d->product->slug,
                ] : null,
                'created_at' => $d->created_at->diffForHumans(),
            ]);

        $recentOrders = Order::with('items.product:id,title,slug')
            ->where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get()
            ->map(fn ($o) => [
                'id' => $o->id,
                'order_number' => $o->order_number,
                'status' => $o->status,
                'total' => $o->total,
                'items_count' => $o->items->count(),
                'created_at' => $o->created_at->diffForHumans(),
            ]);

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentDownloads' => $recentDownloads,
            'recentOrders' => $recentOrders,
            'isSeller' => $user->role === 'seller' || $user->role === 'admin',
        ]);
    }
}
