@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        <a href="{{ route('admin.users.index') }}" class="block rounded-xl border bg-white p-4 shadow-sm hover:shadow-md transition-shadow">
            <div class="text-xs font-medium text-gray-500 uppercase">Total Users</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($stats['total_users']) }}</div>
            <div class="mt-1 text-xs text-green-600">+{{ $stats['users_this_month'] }} this month</div>
        </a>
        <a href="{{ route('admin.products.index') }}" class="block rounded-xl border bg-white p-4 shadow-sm hover:shadow-md transition-shadow">
            <div class="text-xs font-medium text-gray-500 uppercase">Total Products</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($stats['total_products']) }}</div>
            <div class="mt-1 text-xs text-green-600">+{{ $stats['products_this_month'] }} this month</div>
        </a>
        <a href="{{ route('admin.analytics.products') }}" class="block rounded-xl border bg-white p-4 shadow-sm transition-shadow hover:shadow-md">
            <div class="text-xs font-medium text-gray-500 uppercase">Downloads</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($stats['total_downloads']) }}</div>
            <div class="mt-1 text-xs text-green-600">+{{ $stats['downloads_this_month'] }} this month</div>
        </a>
        <a href="{{ route('admin.orders.index') }}" class="block rounded-xl border bg-white p-4 shadow-sm transition-shadow hover:shadow-md">
            <div class="text-xs font-medium text-gray-500 uppercase">Orders</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($stats['total_orders']) }}</div>
            <div class="mt-1 text-xs text-green-600">+{{ $stats['orders_this_month'] }} this month</div>
        </a>
        <a href="{{ route('admin.analytics.products') }}" class="block rounded-xl border bg-white p-4 shadow-sm transition-shadow hover:shadow-md">
            <div class="text-xs font-medium text-gray-500 uppercase">Revenue (Earnings)</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">${{ number_format($stats['total_revenue'], 2) }}</div>
            <div class="mt-1 text-xs text-green-600">+${{ number_format($stats['revenue_this_month'], 2) }} this month</div>
        </a>
        <a href="{{ route('admin.orders.index') }}" class="block rounded-xl border bg-white p-4 shadow-sm transition-shadow hover:shadow-md">
            <div class="text-xs font-medium text-gray-500 uppercase">Sales Revenue</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">${{ number_format($stats['orders_revenue'], 2) }}</div>
            <div class="mt-1 text-xs text-green-600">+${{ number_format($stats['orders_revenue_this_month'], 2) }} this month</div>
        </a>
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

        {{-- Monthly Views & Downloads Chart --}}
        <div class="rounded-xl border bg-white shadow-sm">
            <div class="flex items-center justify-between border-b px-4 py-3">
                <h3 class="font-semibold text-gray-800">Views &amp; Downloads ({{ now()->year }})</h3>
                <div class="flex items-center gap-3 text-xs">
                    <span class="flex items-center gap-1"><span class="inline-block h-2.5 w-2.5 rounded-full bg-brand-primary"></span> Views</span>
                    <span class="flex items-center gap-1"><span class="inline-block h-2.5 w-2.5 rounded-full bg-brand-light"></span> Downloads</span>
                </div>
            </div>
            <div class="p-4">
                @php
                    $months = $allMonths->keys()->values();
                    $viewsData = $allMonths->pluck('views')->values();
                    $downloadsData = $allMonths->pluck('downloads')->values();
                    $maxVal = max(1, max($viewsData->max(), $downloadsData->max()));

                    // Chart dimensions
                    $chartW = 540;
                    $chartH = 180;
                    $padL = 40; // left padding for Y-axis
                    $padR = 10;
                    $padT = 10;
                    $padB = 24; // bottom padding for X-axis
                    $plotW = $chartW - $padL - $padR;
                    $plotH = $chartH - $padT - $padB;

                    // Y-axis ticks (5 steps)
                    $ySteps = 4;
                    $niceMax = $maxVal;
                    $yTicks = [];
                    for ($i = 0; $i <= $ySteps; $i++) {
                        $yTicks[] = round(($niceMax / $ySteps) * $i);
                    }

                    // Build polyline points
                    $viewsPoints = [];
                    $downloadsPoints = [];
                    foreach ($months as $i => $m) {
                        $x = $padL + ($plotW / max(1, count($months) - 1)) * $i;
                        $yV = $padT + $plotH - (($viewsData[$i] / $niceMax) * $plotH);
                        $yD = $padT + $plotH - (($downloadsData[$i] / $niceMax) * $plotH);
                        $viewsPoints[] = round($x, 1) . ',' . round($yV, 1);
                        $downloadsPoints[] = round($x, 1) . ',' . round($yD, 1);
                    }
                @endphp
                <svg viewBox="0 0 {{ $chartW }} {{ $chartH }}" class="w-full h-auto" preserveAspectRatio="xMidYMid meet">
                    {{-- Grid lines & Y-axis labels --}}
                    @foreach ($yTicks as $i => $tick)
                        @php $y = $padT + $plotH - (($tick / $niceMax) * $plotH); @endphp
                        <line x1="{{ $padL }}" y1="{{ $y }}" x2="{{ $chartW - $padR }}" y2="{{ $y }}" stroke="#e5e7eb" stroke-width="0.5" />
                        <text x="{{ $padL - 4 }}" y="{{ $y + 3 }}" text-anchor="end" class="fill-gray-400" style="font-size: 9px;">{{ number_format($tick) }}</text>
                    @endforeach

                    {{-- X-axis month labels --}}
                    @foreach ($months as $i => $m)
                        @php $x = $padL + ($plotW / max(1, count($months) - 1)) * $i; @endphp
                        <text x="{{ $x }}" y="{{ $chartH - 4 }}" text-anchor="middle" class="fill-gray-400" style="font-size: 9px;">{{ \Carbon\Carbon::parse($m . '-01')->format('M') }}</text>
                    @endforeach

                    {{-- Views line --}}
                    <polyline points="{{ implode(' ', $viewsPoints) }}" fill="none" stroke="var(--color-brand-primary, #0a4b76)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    {{-- Downloads line --}}
                    <polyline points="{{ implode(' ', $downloadsPoints) }}" fill="none" stroke="var(--color-brand-light, #1f90bb)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />

                    {{-- Data point dots --}}
                    @foreach ($months as $i => $m)
                        @php
                            $x = $padL + ($plotW / max(1, count($months) - 1)) * $i;
                            $yV = $padT + $plotH - (($viewsData[$i] / $niceMax) * $plotH);
                            $yD = $padT + $plotH - (($downloadsData[$i] / $niceMax) * $plotH);
                        @endphp
                        <circle cx="{{ round($x, 1) }}" cy="{{ round($yV, 1) }}" r="3" fill="var(--color-brand-primary, #0a4b76)">
                            <title>{{ \Carbon\Carbon::parse($m . '-01')->format('M') }}: {{ number_format($viewsData[$i]) }} views</title>
                        </circle>
                        <circle cx="{{ round($x, 1) }}" cy="{{ round($yD, 1) }}" r="3" fill="var(--color-brand-light, #1f90bb)">
                            <title>{{ \Carbon\Carbon::parse($m . '-01')->format('M') }}: {{ number_format($downloadsData[$i]) }} downloads</title>
                        </circle>
                    @endforeach
                </svg>
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
