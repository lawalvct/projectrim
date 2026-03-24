@extends('admin.layouts.app')

@section('title', 'Application: ' . $user->name)

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.seller-applications.index') }}" class="text-sm text-brand-light hover:underline">← Back to Applications</a>
    </div>

    <div class="mx-auto max-w-2xl rounded-xl border bg-white p-6 shadow-sm">
        <div class="flex items-center gap-4 mb-6">
            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-brand-primary text-lg font-bold text-white">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div>
                <h2 class="text-lg font-bold">{{ $user->name }}</h2>
                <p class="text-sm text-gray-500">{{ $user->email }}</p>
            </div>
        </div>

        @if ($user->sellerProfile)
            <div class="grid gap-4 text-sm sm:grid-cols-2">
                <div><span class="text-gray-500">Bio:</span> {{ $user->sellerProfile->bio ?? '—' }}</div>
                <div><span class="text-gray-500">Company:</span> {{ $user->sellerProfile->company ?? '—' }}</div>
                <div><span class="text-gray-500">Phone:</span> {{ $user->sellerProfile->phone ?? '—' }}</div>
                <div><span class="text-gray-500">Country:</span> {{ $user->sellerProfile->country ?? '—' }}</div>
                <div><span class="text-gray-500">Region/State:</span> {{ $user->sellerProfile->region_state ?? '—' }}</div>
                <div><span class="text-gray-500">Bank Details:</span> {{ $user->sellerProfile->bank_account_details ?? '—' }}</div>
            </div>
        @endif

        <div class="mt-6 flex gap-2">
            <form method="POST" action="{{ route('admin.seller-applications.approve', $user) }}">@csrf
                <button type="submit" class="rounded-lg bg-green-600 px-6 py-2 text-sm font-medium text-white hover:bg-green-700">Approve</button>
            </form>
            <form method="POST" action="{{ route('admin.seller-applications.reject', $user) }}" onsubmit="return confirm('Reject this application?')">@csrf
                <button type="submit" class="rounded-lg bg-brand-danger px-6 py-2 text-sm font-medium text-white hover:bg-brand-danger-dark">Reject</button>
            </form>
        </div>
    </div>
@endsection
