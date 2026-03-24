<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Order::with(['items.product:id,title,slug'])
            ->where('user_id', $request->user()->id);

        if ($request->filled('status') && in_array($request->status, ['pending', 'completed', 'failed', 'refunded'])) {
            $query->where('status', $request->status);
        }

        $orders = $query->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'subtotal' => $order->subtotal,
                'total' => $order->total,
                'payment_gateway' => $order->payment_gateway,
                'paid_at' => $order->paid_at?->format('M d, Y'),
                'created_at' => $order->created_at->format('M d, Y'),
                'created_at_diff' => $order->created_at->diffForHumans(),
                'items' => $order->items->map(fn ($item) => [
                    'id' => $item->id,
                    'price' => $item->price,
                    'product' => $item->product ? [
                        'id' => $item->product->id,
                        'title' => $item->product->title,
                        'slug' => $item->product->slug,
                    ] : null,
                ]),
            ]);

        return Inertia::render('dashboard/Orders', [
            'orders' => $orders,
            'filters' => [
                'status' => $request->status,
            ],
        ]);
    }
}
