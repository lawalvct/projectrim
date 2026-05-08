@extends('admin.layouts.app')

@section('title', 'User Analytics')

@section('content')
    @php
        $dateQuery = array_filter([
            'date_from' => $dateFilters['date_from'] ?? null,
            'date_to' => $dateFilters['date_to'] ?? null,
        ]);
    @endphp

    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex gap-1 rounded-lg border bg-white p-1 shadow-sm w-fit">
            <a href="{{ route('admin.analytics.products', $dateQuery) }}" class="rounded-md px-4 py-1.5 text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition-colors">Products</a>
            <a href="{{ route('admin.analytics.users', $dateQuery) }}" class="rounded-md bg-brand-primary px-4 py-1.5 text-sm font-medium text-white">Users</a>
        </div>

        <form method="GET" action="{{ route('admin.analytics.users') }}" class="flex flex-wrap items-end gap-3 rounded-xl border bg-white px-4 py-3 shadow-sm">
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
                    <a href="{{ route('admin.analytics.users') }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition-colors">Reset</a>
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
        <h3 class="mb-3 text-sm font-semibold text-gray-600 uppercase">{{ $dateQuery ? 'Monthly Registrations' : 'Monthly Registrations (Last 12 Months)' }}</h3>
        @php $maxReg = $monthlyRegistrations->max() ?: 1; @endphp
        <div class="overflow-x-auto">
            <div class="flex min-w-180 items-end gap-2" style="height: 180px;">
                @foreach ($monthlyRegistrations as $month => $count)
                    @php $barHeight = max(($count / $maxReg) * 150, $count > 0 ? 4 : 0); @endphp
                    <div class="flex-1 flex flex-col items-center justify-end h-full">
                        <span class="text-[11px] font-medium text-gray-600 mb-1">{{ $count }}</span>
                        <div class="w-full max-w-9 mx-auto bg-brand-light rounded-t transition-all" style="height: {{ $barHeight }}px; min-width: 12px;"></div>
                        <span class="text-[10px] text-gray-500 mt-1.5 font-medium">{{ \Carbon\Carbon::parse($month . '-01')->format('M') }}</span>
                    </div>
                @endforeach
            </div>
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
