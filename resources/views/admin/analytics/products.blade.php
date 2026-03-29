@extends('admin.layouts.app')

@section('title', 'Product Analytics')

@section('content')
    <div class="mb-4 flex gap-2">
        <a href="{{ route('admin.analytics.products') }}" class="rounded-lg bg-brand-primary px-4 py-2 text-sm font-medium text-white">Products</a>
        <a href="{{ route('admin.analytics.users') }}" class="rounded-lg border px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Users</a>
    </div>

    {{-- Monthly Uploads Chart --}}
    <div class="rounded-xl border bg-white p-6 shadow-sm mb-6">
        <h3 class="mb-3 text-sm font-semibold text-gray-600 uppercase">Monthly Uploads (Last 12 Months)</h3>
        @php $maxUploads = $monthlyUploads->max() ?: 1; @endphp
        <div class="flex items-end gap-2" style="height: 180px;">
            @foreach ($monthlyUploads as $month => $count)
                @php $barHeight = max(($count / $maxUploads) * 150, $count > 0 ? 4 : 0); @endphp
                <div class="flex-1 flex flex-col items-center justify-end h-full">
                    <span class="text-[11px] font-medium text-gray-600 mb-1">{{ $count }}</span>
                    <div class="w-full max-w-[36px] mx-auto bg-brand-accent rounded-t transition-all" style="height: {{ $barHeight }}px; min-width: 12px;"></div>
                    <span class="text-[10px] text-gray-500 mt-1.5 font-medium">{{ \Carbon\Carbon::parse($month . '-01')->format('M') }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
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
                            <a href="{{ route('admin.products.show', $product) }}" class="text-sm text-brand-light hover:underline truncate max-w-[160px]">{{ $product->title }}</a>
                        </div>
                        <span class="text-xs font-medium text-gray-500">{{ number_format($product->views_count) }}</span>
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
                            <a href="{{ route('admin.products.show', $product) }}" class="text-sm text-brand-light hover:underline truncate max-w-[160px]">{{ $product->title }}</a>
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
                            <a href="{{ route('admin.products.show', $product) }}" class="text-sm text-brand-light hover:underline truncate max-w-[160px]">{{ $product->title }}</a>
                        </div>
                        <span class="text-xs font-medium text-gray-500">₦{{ number_format($product->total_revenue, 2) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
