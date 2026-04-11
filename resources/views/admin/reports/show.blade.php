@extends('admin.layouts.app')

@section('title', 'Report Details')

@section('content')
    <a href="{{ route('admin.reports.index') }}" class="mb-4 inline-flex items-center text-sm text-brand-light hover:underline">← Back to Reports</a>

    <div class="mx-auto max-w-2xl space-y-6">
        <!-- Report Info -->
        <div class="rounded-xl border bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold">Report #{{ $report->id }}</h2>
                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium
                    {{ $report->status === 'pending' ? 'bg-yellow-100 text-yellow-700' : '' }}
                    {{ $report->status === 'reviewed' ? 'bg-green-100 text-green-700' : '' }}
                    {{ $report->status === 'dismissed' ? 'bg-gray-100 text-gray-600' : '' }}
                ">{{ ucfirst($report->status) }}</span>
            </div>

            <div class="grid gap-3 text-sm sm:grid-cols-2">
                <div>
                    <span class="text-gray-500">Reporter Name:</span>
                    <span class="ml-1 font-medium">{{ $report->user->name ?? '—' }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Reporter Email:</span>
                    <span class="ml-1 font-medium">{{ $report->user->email ?? '—' }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Product:</span>
                    @if ($report->product)
                        <a href="{{ route('admin.products.show', $report->product) }}" class="ml-1 text-brand-light hover:underline">{{ $report->product->title }}</a>
                    @else
                        <span class="ml-1 text-gray-400">Deleted</span>
                    @endif
                </div>
                <div>
                    <span class="text-gray-500">Product URL:</span>
                    <a href="{{ $report->product_url }}" target="_blank" class="ml-1 text-brand-light hover:underline break-all">{{ $report->product_url }}</a>
                </div>
                <div>
                    <span class="text-gray-500">Submitted:</span>
                    <span class="ml-1">{{ $report->created_at->format('M d, Y \a\t h:i A') }}</span>
                </div>
            </div>
        </div>

        <!-- Complaint -->
        <div class="rounded-xl border bg-white p-6 shadow-sm">
            <h3 class="mb-2 font-semibold">Complaint</h3>
            <p class="text-sm text-gray-700 whitespace-pre-line">{{ $report->reason }}</p>
        </div>

        <!-- Actions -->
        @if ($report->status === 'pending')
            <div class="rounded-xl border bg-white p-6 shadow-sm">
                <h3 class="mb-3 font-semibold">Actions</h3>
                <div class="flex gap-3">
                    <form method="POST" action="{{ route('admin.reports.reviewed', $report) }}">
                        @csrf
                        <button class="rounded-lg bg-green-600 px-4 py-2 text-sm text-white hover:bg-green-700">Mark as Reviewed</button>
                    </form>
                    <form method="POST" action="{{ route('admin.reports.dismiss', $report) }}">
                        @csrf
                        <button class="rounded-lg bg-gray-500 px-4 py-2 text-sm text-white hover:bg-gray-600">Dismiss</button>
                    </form>
                </div>
            </div>
        @endif

        <!-- Delete -->
        <div class="text-right">
            <form method="POST" action="{{ route('admin.reports.destroy', $report) }}" onsubmit="return confirm('Delete this report permanently?')">
                @csrf @method('DELETE')
                <button class="text-red-600 hover:underline text-sm">Delete Report</button>
            </form>
        </div>
    </div>
@endsection
