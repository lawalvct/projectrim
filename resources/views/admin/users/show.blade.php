@extends('admin.layouts.app')

@section('title', $user->name)

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.users.index') }}" class="text-sm text-brand-light hover:underline">← Back to Users</a>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- User Info --}}
        <div class="rounded-xl border bg-white p-6 shadow-sm lg:col-span-1">
            <div class="flex items-center gap-4">
                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-brand-primary text-xl font-bold text-white">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div>
                    <h2 class="text-lg font-bold">{{ $user->name }}</h2>
                    <p class="text-sm text-gray-500">{{ $user->email }}</p>
                    <span class="mt-1 inline-block rounded-full px-2 py-0.5 text-xs font-medium
                        {{ $user->role === 'admin' ? 'bg-purple-100 text-purple-700' : ($user->role === 'seller' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600') }}">
                        {{ ucfirst($user->role) }}
                    </span>
                </div>
            </div>

            <div class="mt-6 space-y-3 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">Registered</span><span>{{ $user->created_at->format('M d, Y') }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Provider</span><span>{{ $user->provider ? ucfirst($user->provider) : 'Email' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Products</span><span>{{ $user->products_count }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Downloads</span><span>{{ $user->downloads_count }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Orders</span><span>{{ $user->orders_count }}</span></div>
            </div>

            <div class="mt-6 flex gap-2">
                <a href="{{ route('admin.users.edit', $user) }}" class="rounded-lg bg-brand-accent px-4 py-2 text-sm font-medium text-white hover:bg-brand-primary">Edit</a>
                @if ($user->id !== auth()->id())
                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Delete this user?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="rounded-lg bg-brand-danger px-4 py-2 text-sm font-medium text-white hover:bg-brand-danger-dark">Delete</button>
                    </form>
                @endif
            </div>
        </div>

        {{-- Stats --}}
        <div class="space-y-6 lg:col-span-2">
            <div class="grid grid-cols-3 gap-4">
                <div class="rounded-xl border bg-white p-4 shadow-sm text-center">
                    <div class="text-2xl font-bold text-brand-primary">${{ number_format($totalRevenue, 2) }}</div>
                    <div class="text-xs text-gray-500">Total Earned</div>
                </div>
                <div class="rounded-xl border bg-white p-4 shadow-sm text-center">
                    <div class="text-2xl font-bold text-green-600">${{ number_format($totalPaidOut, 2) }}</div>
                    <div class="text-xs text-gray-500">Total Paid Out</div>
                </div>
                <div class="rounded-xl border bg-white p-4 shadow-sm text-center">
                    <div class="text-2xl font-bold text-amber-600">${{ number_format($balance, 2) }}</div>
                    <div class="text-xs text-gray-500">Balance</div>
                </div>
            </div>

            @if ($user->sellerProfile)
                <div class="rounded-xl border bg-white p-6 shadow-sm">
                    <h3 class="mb-3 font-semibold">Seller Profile</h3>
                    <div class="grid gap-3 text-sm sm:grid-cols-2">
                        <div><span class="text-gray-500">Bio:</span> {{ $user->sellerProfile->bio ?? '—' }}</div>
                        <div><span class="text-gray-500">Company:</span> {{ $user->sellerProfile->company ?? '—' }}</div>
                        <div><span class="text-gray-500">Phone:</span> {{ $user->sellerProfile->phone ?? '—' }}</div>
                        <div><span class="text-gray-500">Country:</span> {{ $user->sellerProfile->country ?? '—' }}</div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
