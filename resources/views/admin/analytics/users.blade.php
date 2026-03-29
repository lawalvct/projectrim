@extends('admin.layouts.app')

@section('title', 'User Analytics')

@section('content')
    <div class="mb-4 flex gap-2">
        <a href="{{ route('admin.analytics.products') }}" class="rounded-lg border px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Products</a>
        <a href="{{ route('admin.analytics.users') }}" class="rounded-lg bg-brand-primary px-4 py-2 text-sm font-medium text-white">Users</a>
    </div>

    {{-- Role Distribution --}}
    <div class="grid gap-4 sm:grid-cols-3 mb-6">
        @foreach ($roleCounts as $role => $count)
            <div class="rounded-xl border bg-white p-4 shadow-sm text-center">
                <p class="text-2xl font-bold text-brand-primary">{{ number_format($count) }}</p>
                <p class="text-sm text-gray-500 capitalize">{{ $role }}s</p>
            </div>
        @endforeach
    </div>

    {{-- Monthly Registrations Chart --}}
    <div class="rounded-xl border bg-white p-6 shadow-sm mb-6">
        <h3 class="mb-3 text-sm font-semibold text-gray-600 uppercase">Monthly Registrations (Last 12 Months)</h3>
        @php $maxReg = $monthlyRegistrations->max() ?: 1; @endphp
        <div class="flex items-end gap-2" style="height: 180px;">
            @foreach ($monthlyRegistrations as $month => $count)
                @php $barHeight = max(($count / $maxReg) * 150, $count > 0 ? 4 : 0); @endphp
                <div class="flex-1 flex flex-col items-center justify-end h-full">
                    <span class="text-[11px] font-medium text-gray-600 mb-1">{{ $count }}</span>
                    <div class="w-full max-w-[36px] mx-auto bg-brand-light rounded-t transition-all" style="height: {{ $barHeight }}px; min-width: 12px;"></div>
                    <span class="text-[10px] text-gray-500 mt-1.5 font-medium">{{ \Carbon\Carbon::parse($month . '-01')->format('M') }}</span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Top Sellers --}}
    <div class="rounded-xl border bg-white shadow-sm">
        <div class="border-b px-4 py-3">
            <h3 class="text-sm font-semibold">Top Sellers</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b bg-gray-50 text-left text-xs font-medium uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">Seller</th>
                        <th class="px-4 py-3">Products</th>
                        <th class="px-4 py-3">Revenue</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach ($topSellers as $i => $seller)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-400">{{ $i + 1 }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.users.show', $seller) }}" class="text-brand-light hover:underline font-medium">{{ $seller->name }}</a>
                            </td>
                            <td class="px-4 py-3">{{ $seller->products_count }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $revenue = $seller->revenues->first()?->total ?? 0;
                                @endphp
                                ₦{{ number_format($revenue, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
