@extends('admin.layouts.app')

@section('title', 'Payment Methods')

@section('content')
    {{-- Settings Nav --}}
    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('admin.settings.general') }}" class="rounded-lg border px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">General</a>
        <a href="{{ route('admin.settings.monetization') }}" class="rounded-lg border px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Monetization</a>
        <a href="{{ route('admin.settings.payment') }}" class="rounded-lg border px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Payment</a>
        <a href="{{ route('admin.settings.seller') }}" class="rounded-lg border px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Seller</a>
        <a href="{{ route('admin.settings.carousel') }}" class="rounded-lg border px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Carousel</a>
        <a href="{{ route('admin.payment-methods.index') }}" class="rounded-lg bg-brand-primary px-4 py-2 text-sm font-medium text-white">Payment Methods</a>
    </div>

    <div class="mx-auto max-w-2xl">
        {{-- Add New --}}
        <div class="rounded-xl border bg-white p-6 shadow-sm mb-4">
            <h2 class="mb-3 text-sm font-semibold">Add Payout Method</h2>
            <form method="POST" action="{{ route('admin.payment-methods.store') }}" class="flex items-end gap-3">
                @csrf
                <div class="flex-1">
                    <label class="mb-1 block text-xs text-gray-500">Method Name</label>
                    <input type="text" name="name" class="w-full rounded-lg border px-3 py-2 text-sm" required placeholder="e.g. Bank Transfer, PayPal" />
                </div>
                <label class="flex items-center gap-1 text-sm">
                    <input type="checkbox" name="is_active" value="1" checked class="rounded" />
                    Active
                </label>
                <button type="submit" class="rounded-lg bg-brand-accent px-4 py-2 text-sm font-medium text-white hover:bg-brand-primary">Add</button>
            </form>
            @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- List --}}
        <div class="rounded-xl border bg-white shadow-sm overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b bg-gray-50 text-left text-xs font-medium uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($methods as $method)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium">{{ $method->name }}</td>
                            <td class="px-4 py-3">
                                <form method="POST" action="{{ route('admin.payment-methods.update', $method) }}" class="inline">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="name" value="{{ $method->name }}" />
                                    <input type="hidden" name="is_active" value="{{ $method->is_active ? '0' : '1' }}" />
                                    <button class="rounded-full px-2 py-0.5 text-xs font-medium {{ $method->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                        {{ $method->is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </form>
                            </td>
                            <td class="px-4 py-3">
                                <form method="POST" action="{{ route('admin.payment-methods.destroy', $method) }}" onsubmit="return confirm('Delete?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:underline text-xs">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-8 text-center text-gray-400">No payment methods defined.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
