<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SellerOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $userId = $request->user()->id;

        $query = OrderItem::with([
            'order:id,order_number,status,total,payment_gateway,paid_at,created_at',
            'order.user:id,name,email',
            'product:id,title,slug',
        ])->whereHas('product', fn ($q) => $q->where('user_id', $userId));

        if ($request->filled('status')) {
            $query->whereHas('order', fn ($q) => $q->where('status', $request->status));
        }

        $orderItems = $query->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($item) => [
                'id' => $item->id,
                'price' => $item->price,
                'order' => $item->order ? [
                    'id' => $item->order->id,
                    'order_number' => $item->order->order_number,
                    'status' => $item->order->status,
                    'total' => $item->order->total,
                    'payment_gateway' => $item->order->payment_gateway,
                    'paid_at' => $item->order->paid_at?->format('M d, Y'),
                    'created_at' => $item->order->created_at->format('M d, Y'),
                    'buyer' => $item->order->user ? [
                        'name' => $item->order->user->name,
                        'email' => $item->order->user->email,
                    ] : null,
                ] : null,
                'product' => $item->product ? [
                    'id' => $item->product->id,
                    'title' => $item->product->title,
                    'slug' => $item->product->slug,
                ] : null,
            ]);

        return Inertia::render('dashboard/seller/Orders', [
            'orderItems' => $orderItems,
            'filters' => [
                'status' => $request->status,
            ],
        ]);
    }
}
