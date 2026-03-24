@extends('admin.layouts.app')

@section('title', 'Order ' . $order->order_number)

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.orders.index') }}" class="text-sm text-brand-light hover:underline">← Back to Orders</a>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 rounded-xl border bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold">{{ $order->order_number }}</h2>
                <span class="rounded-full px-3 py-1 text-xs font-medium
                    {{ $order->status === 'completed' ? 'bg-green-100 text-green-700' : ($order->status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                    {{ ucfirst($order->status) }}
                </span>
            </div>

            <h3 class="mb-2 font-semibold text-sm">Items</h3>
            <div class="rounded-lg border overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b bg-gray-50 text-left text-xs font-medium uppercase text-gray-500">
                        <tr>
                            <th class="px-4 py-2">Product</th>
                            <th class="px-4 py-2">Price</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($order->items as $item)
                            <tr>
                                <td class="px-4 py-2">
                                    @if ($item->product)
                                        <a href="{{ route('admin.products.show', $item->product) }}" class="text-brand-light hover:underline">{{ $item->product->title }}</a>
                                    @else
                                        <span class="text-gray-400">Deleted product</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2">${{ number_format($item->price, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t bg-gray-50">
                        <tr>
                            <td class="px-4 py-2 font-semibold">Total</td>
                            <td class="px-4 py-2 font-semibold">${{ number_format($order->total, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-xl border bg-white p-6 shadow-sm">
                <h3 class="mb-3 font-semibold text-sm">Customer</h3>
                <div class="space-y-2 text-sm">
                    <div><span class="text-gray-500">Name:</span> {{ $order->user?->name ?? 'Guest' }}</div>
                    <div><span class="text-gray-500">Email:</span> {{ $order->user?->email ?? '—' }}</div>
                </div>
            </div>

            <div class="rounded-xl border bg-white p-6 shadow-sm">
                <h3 class="mb-3 font-semibold text-sm">Payment</h3>
                <div class="space-y-2 text-sm">
                    <div><span class="text-gray-500">Gateway:</span> {{ ucfirst($order->payment_gateway ?? '—') }}</div>
                    <div><span class="text-gray-500">Reference:</span> {{ $order->payment_reference ?? '—' }}</div>
                    <div><span class="text-gray-500">Date:</span> {{ $order->created_at->format('M d, Y H:i') }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
