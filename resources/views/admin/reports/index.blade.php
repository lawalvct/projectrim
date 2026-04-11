@extends('admin.layouts.app')

@section('title', 'Reports')

@section('content')
    <div class="mb-4 flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold">Product Reports</h2>
        </div>
        <form class="flex flex-wrap items-end gap-3 rounded-lg border bg-gray-50 p-4" method="GET">
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search reports…" class="rounded-lg border px-3 py-2 text-sm w-56" />
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500">Status</label>
                <select name="status" class="rounded-lg border px-3 py-2 text-sm">
                    <option value="">All</option>
                    <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                    <option value="reviewed" @selected(request('status') === 'reviewed')>Reviewed</option>
                    <option value="dismissed" @selected(request('status') === 'dismissed')>Dismissed</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button class="rounded-lg bg-brand-primary px-4 py-2 text-sm text-white">Filter</button>
                <a href="{{ route('admin.reports.index') }}" class="rounded-lg border px-4 py-2 text-sm text-gray-600 hover:bg-white">Clear</a>
            </div>
        </form>
    </div>

    <div class="rounded-xl border bg-white shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="border-b bg-gray-50 text-left text-xs font-medium uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">Reporter</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Product</th>
                    <th class="px-4 py-3">Reason</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($reports as $report)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium">{{ $report->user->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $report->user->email ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @if ($report->product)
                                <a href="{{ route('admin.products.show', $report->product) }}" class="text-brand-light hover:underline">{{ Str::limit($report->product->title, 35) }}</a>
                            @else
                                <span class="text-gray-400">Deleted</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ Str::limit($report->reason, 60) }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium
                                {{ $report->status === 'pending' ? 'bg-yellow-100 text-yellow-700' : '' }}
                                {{ $report->status === 'reviewed' ? 'bg-green-100 text-green-700' : '' }}
                                {{ $report->status === 'dismissed' ? 'bg-gray-100 text-gray-600' : '' }}
                            ">{{ ucfirst($report->status) }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-400 text-xs">{{ $report->created_at->format('M d, Y') }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.reports.show', $report) }}" class="text-brand-light hover:underline text-xs">View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">No reports found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $reports->withQueryString()->links() }}</div>
@endsection
