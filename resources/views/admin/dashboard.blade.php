@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        <div class="rounded-xl border bg-white p-4 shadow-sm">
            <div class="text-xs font-medium text-gray-500 uppercase">Total Users</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($stats['total_users']) }}</div>
            <div class="mt-1 text-xs text-green-600">+{{ $stats['users_this_month'] }} this month</div>
        </div>
        <div class="rounded-xl border bg-white p-4 shadow-sm">
            <div class="text-xs font-medium text-gray-500 uppercase">Total Products</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($stats['total_products']) }}</div>
            <div class="mt-1 text-xs text-green-600">+{{ $stats['products_this_month'] }} this month</div>
        </div>
        <div class="rounded-xl border bg-white p-4 shadow-sm">
            <div class="text-xs font-medium text-gray-500 uppercase">Downloads</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($stats['total_downloads']) }}</div>
            <div class="mt-1 text-xs text-green-600">+{{ $stats['downloads_this_month'] }} this month</div>
        </div>
        <div class="rounded-xl border bg-white p-4 shadow-sm">
            <div class="text-xs font-medium text-gray-500 uppercase">Orders</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($stats['total_orders']) }}</div>
            <div class="mt-1 text-xs text-green-600">+{{ $stats['orders_this_month'] }} this month</div>
        </div>
        <div class="rounded-xl border bg-white p-4 shadow-sm">
            <div class="text-xs font-medium text-gray-500 uppercase">Revenue (Earnings)</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">${{ number_format($stats['total_revenue'], 2) }}</div>
            <div class="mt-1 text-xs text-green-600">+${{ number_format($stats['revenue_this_month'], 2) }} this month</div>
        </div>
        <div class="rounded-xl border bg-white p-4 shadow-sm">
            <div class="text-xs font-medium text-gray-500 uppercase">Sales Revenue</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">${{ number_format($stats['orders_revenue'], 2) }}</div>
            <div class="mt-1 text-xs text-green-600">+${{ number_format($stats['orders_revenue_this_month'], 2) }} this month</div>
        </div>
    </div>

    {{-- Pending Actions --}}
    @if ($pending['reviews'] > 0 || $pending['seller_apps'] > 0 || $pending['payouts'] > 0)
        <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4">
            <h3 class="text-sm font-semibold text-amber-800">Pending Actions</h3>
            <div class="mt-2 flex flex-wrap gap-4 text-sm">
                @if ($pending['seller_apps'] > 0)
                    <a href="{{ route('admin.seller-applications.index') }}" class="text-amber-700 hover:underline">{{ $pending['seller_apps'] }} seller application(s)</a>
                @endif
                @if ($pending['payouts'] > 0)
                    <a href="{{ route('admin.payouts.index') }}" class="text-amber-700 hover:underline">{{ $pending['payouts'] }} payout request(s)</a>
                @endif
                @if ($pending['reviews'] > 0)
                    <a href="{{ route('admin.reviews.index') }}" class="text-amber-700 hover:underline">{{ $pending['reviews'] }} review(s) to moderate</a>
                @endif
            </div>
        </div>
    @endif

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        {{-- Recent Users --}}
        <div class="rounded-xl border bg-white shadow-sm">
            <div class="flex items-center justify-between border-b px-4 py-3">
                <h3 class="font-semibold text-gray-800">Recent Users</h3>
                <a href="{{ route('admin.users.index') }}" class="text-xs text-brand-light hover:underline">View all →</a>
            </div>
            <div class="divide-y">
                @foreach ($recentUsers as $user)
                    <div class="flex items-center justify-between px-4 py-2.5">
                        <div>
                            <div class="text-sm font-medium">{{ $user->name }}</div>
                            <div class="text-xs text-gray-500">{{ $user->email }}</div>
                        </div>
                        <span class="rounded-full px-2 py-0.5 text-xs font-medium
                            {{ $user->role === 'admin' ? 'bg-purple-100 text-purple-700' : ($user->role === 'seller' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600') }}">
                            {{ ucfirst($user->role) }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Recent Products --}}
        <div class="rounded-xl border bg-white shadow-sm">
            <div class="flex items-center justify-between border-b px-4 py-3">
                <h3 class="font-semibold text-gray-800">Recent Products</h3>
                <a href="{{ route('admin.products.index') }}" class="text-xs text-brand-light hover:underline">View all →</a>
            </div>
            <div class="divide-y">
                @foreach ($recentProducts as $product)
                    <div class="flex items-center justify-between px-4 py-2.5">
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('admin.products.show', $product) }}" class="text-sm font-medium hover:text-brand-primary truncate block">{{ $product->title }}</a>
                            <div class="text-xs text-gray-500">by {{ $product->user->name }}</div>
                        </div>
                        <span class="ml-2 rounded-full px-2 py-0.5 text-xs font-medium
                            {{ $product->status === 'published' ? 'bg-green-100 text-green-700' : ($product->status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-600') }}">
                            {{ ucfirst($product->status) }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Recent Orders --}}
        <div class="rounded-xl border bg-white shadow-sm">
            <div class="flex items-center justify-between border-b px-4 py-3">
                <h3 class="font-semibold text-gray-800">Recent Orders</h3>
                <a href="{{ route('admin.orders.index') }}" class="text-xs text-brand-light hover:underline">View all →</a>
            </div>
            <div class="divide-y">
                @foreach ($recentOrders as $order)
                    <div class="flex items-center justify-between px-4 py-2.5">
                        <div>
                            <a href="{{ route('admin.orders.show', $order) }}" class="text-sm font-medium hover:text-brand-primary">{{ $order->order_number }}</a>
                            <div class="text-xs text-gray-500">{{ $order->user->name ?? 'Guest' }} · ${{ number_format($order->total, 2) }}</div>
                        </div>
                        <span class="rounded-full px-2 py-0.5 text-xs font-medium
                            {{ $order->status === 'completed' ? 'bg-green-100 text-green-700' : ($order->status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                            {{ ucfirst($order->status) }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Popular Products --}}
        <div class="rounded-xl border bg-white shadow-sm">
            <div class="flex items-center justify-between border-b px-4 py-3">
                <h3 class="font-semibold text-gray-800">Popular Products</h3>
            </div>
            <div class="divide-y">
                @foreach ($popularProducts as $product)
                    <div class="flex items-center justify-between px-4 py-2.5">
                        <a href="{{ route('admin.products.show', $product) }}" class="text-sm font-medium hover:text-brand-primary truncate">{{ $product->title }}</a>
                        <div class="ml-2 flex gap-3 text-xs text-gray-500 shrink-0">
                            <span>{{ number_format($product->views_count) }} views</span>
                            <span>{{ number_format($product->downloads_count) }} downloads</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
