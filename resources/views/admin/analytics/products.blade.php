@extends('admin.layouts.app')

@section('title', 'Product Analytics')

@section('content')
    @php
        $dateQuery = array_filter([
            'date_from' => $dateFilters['date_from'] ?? null,
            'date_to' => $dateFilters['date_to'] ?? null,
        ]);
    @endphp

    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex gap-1 rounded-lg border bg-white p-1 shadow-sm w-fit">
            <a href="{{ route('admin.analytics.products', $dateQuery) }}" class="rounded-md bg-brand-primary px-4 py-1.5 text-sm font-medium text-white">Products</a>
            <a href="{{ route('admin.analytics.users', $dateQuery) }}" class="rounded-md px-4 py-1.5 text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition-colors">Users</a>
        </div>

        <form method="GET" action="{{ route('admin.analytics.products') }}" class="flex flex-wrap items-end gap-3 rounded-xl border bg-white px-4 py-3 shadow-sm">
            <div class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-gray-400 self-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Filter
            </div>
            <div class="h-5 w-px bg-gray-200 self-center"></div>
            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <label for="date_from" class="mb-1 block text-xs font-medium text-gray-500">From</label>
                    <input id="date_from" type="date" name="date_from" value="{{ $dateFilters['date_from'] ?? '' }}"
                        class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-sm text-gray-700 shadow-sm transition focus:border-brand-primary focus:bg-white focus:outline-none focus:ring-1 focus:ring-brand-primary">
                </div>
                <span class="mb-2 text-gray-400 text-sm self-end">→</span>
                <div>
                    <label for="date_to" class="mb-1 block text-xs font-medium text-gray-500">To</label>
                    <input id="date_to" type="date" name="date_to" value="{{ $dateFilters['date_to'] ?? '' }}"
                        class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-sm text-gray-700 shadow-sm transition focus:border-brand-primary focus:bg-white focus:outline-none focus:ring-1 focus:ring-brand-primary">
                </div>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="rounded-lg bg-brand-primary px-4 py-2 text-sm font-medium text-white shadow-sm hover:opacity-90 transition-opacity">Apply</button>
                @if ($dateQuery)
                    <a href="{{ route('admin.analytics.products') }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition-colors">Reset</a>
                @endif
            </div>
        </form>
    </div>

    @error('date_to')
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-700">{{ $message }}</div>
    @enderror

    @error('date_from')
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-700">{{ $message }}</div>
    @enderror

    {{-- Monthly Uploads Chart --}}
    <div class="rounded-xl border bg-white p-6 shadow-sm mb-6">
        <h3 class="mb-3 text-sm font-semibold text-gray-600 uppercase">{{ $dateQuery ? 'Monthly Uploads' : 'Monthly Uploads (Last 12 Months)' }}</h3>
        @php $maxUploads = $monthlyUploads->max() ?: 1; @endphp
        <div class="overflow-x-auto">
            <div class="flex min-w-180 items-end gap-2" style="height: 180px;">
                @foreach ($monthlyUploads as $month => $count)
                    @php $barHeight = max(($count / $maxUploads) * 150, $count > 0 ? 4 : 0); @endphp
                    <div class="flex-1 flex flex-col items-center justify-end h-full">
                        <span class="text-[11px] font-medium text-gray-600 mb-1">{{ $count }}</span>
                        <div class="w-full max-w-9 mx-auto bg-brand-accent rounded-t transition-all" style="height: {{ $barHeight }}px; min-width: 12px;"></div>
                        <span class="text-[10px] text-gray-500 mt-1.5 font-medium">{{ \Carbon\Carbon::parse($month . '-01')->format('M') }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-4">
        {{-- Top by Views --}}
        <div class="rounded-xl border bg-white shadow-sm">
            <div class="border-b px-4 py-3">
                <h3 class="text-sm font-semibold">Top by Views</h3>
            </div>
            <div class="divide-y">
                @foreach ($topByViews as $i => $product)
                    <div class="flex items-center justify-between px-4 py-2">
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-400 w-5">{{ $i + 1 }}.</span>
                            <a href="{{ route('admin.products.show', $product) }}" title="{{ $product->title }}" class="text-sm text-brand-light hover:underline truncate max-w-40">{{ $product->title }}</a>
                        </div>
                        <span class="text-xs font-medium text-gray-500">{{ number_format($product->views_count) }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Top by Likes --}}
        <div class="rounded-xl border bg-white shadow-sm">
            <div class="border-b px-4 py-3">
                <h3 class="text-sm font-semibold">Top by Likes</h3>
            </div>
            <div class="divide-y">
                @foreach ($topByLikes as $i => $product)
                    <div class="flex items-center justify-between px-4 py-2">
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-400 w-5">{{ $i + 1 }}.</span>
                            <a href="{{ route('admin.products.show', $product) }}" title="{{ $product->title }}" class="text-sm text-brand-light hover:underline truncate max-w-40">{{ $product->title }}</a>
                        </div>
                        <span class="text-xs font-medium text-gray-500">{{ number_format($product->likes_count) }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Top by Downloads --}}
        <div class="rounded-xl border bg-white shadow-sm">
            <div class="border-b px-4 py-3">
                <h3 class="text-sm font-semibold">Top by Downloads</h3>
            </div>
            <div class="divide-y">
                @foreach ($topByDownloads as $i => $product)
                    <div class="flex items-center justify-between px-4 py-2">
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-400 w-5">{{ $i + 1 }}.</span>
                            <a href="{{ route('admin.products.show', $product) }}" title="{{ $product->title }}" class="text-sm text-brand-light hover:underline truncate max-w-40">{{ $product->title }}</a>
                        </div>
                        <span class="text-xs font-medium text-gray-500">{{ number_format($product->downloads_count) }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Top by Revenue --}}
        <div class="rounded-xl border bg-white shadow-sm">
            <div class="border-b px-4 py-3">
                <h3 class="text-sm font-semibold">Top by Revenue</h3>
            </div>
            <div class="divide-y">
                @foreach ($topByRevenue as $i => $product)
                    <div class="flex items-center justify-between px-4 py-2">
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-400 w-5">{{ $i + 1 }}.</span>
                            <a href="{{ route('admin.products.show', $product) }}" title="{{ $product->title }}" class="text-sm text-brand-light hover:underline truncate max-w-40">{{ $product->title }}</a>
                        </div>
                        <span class="text-xs font-medium text-gray-500">₦{{ number_format($product->total_revenue, 2) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
