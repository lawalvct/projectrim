@extends('admin.layouts.app')

@section('title', 'General Settings')

@section('content')
    {{-- Settings Nav --}}
    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('admin.settings.general') }}" class="rounded-lg bg-brand-primary px-4 py-2 text-sm font-medium text-white">General</a>
        <a href="{{ route('admin.settings.monetization') }}" class="rounded-lg border px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Monetization</a>
        <a href="{{ route('admin.settings.payment') }}" class="rounded-lg border px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Payment</a>
        <a href="{{ route('admin.settings.seller') }}" class="rounded-lg border px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Seller</a>
        <a href="{{ route('admin.settings.carousel') }}" class="rounded-lg border px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Carousel</a>
        <a href="{{ route('admin.payment-methods.index') }}" class="rounded-lg border px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Payment Methods</a>
    </div>

    <div class="mx-auto max-w-2xl">
        <div class="rounded-xl border bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold">General Settings</h2>

            <form method="POST" action="{{ route('admin.settings.update') }}">
                @csrf
                <input type="hidden" name="group" value="general" />

                <div class="mb-4">
                    <label class="mb-1 block text-sm font-medium">Site Name</label>
                    <input type="text" name="settings[site_name]" value="{{ $settings['site_name'] ?? '' }}" class="w-full rounded-lg border px-3 py-2 text-sm" />
                </div>

                <div class="mb-4">
                    <label class="mb-1 block text-sm font-medium">Site Description</label>
                    <textarea name="settings[site_description]" rows="3" class="w-full rounded-lg border px-3 py-2 text-sm">{{ $settings['site_description'] ?? '' }}</textarea>
                </div>

                <div class="mb-4">
                    <label class="mb-1 block text-sm font-medium">Contact Email</label>
                    <input type="email" name="settings[contact_email]" value="{{ $settings['contact_email'] ?? '' }}" class="w-full rounded-lg border px-3 py-2 text-sm" />
                </div>

                <div class="mb-4">
                    <label class="mb-1 block text-sm font-medium">Currency Symbol</label>
                    <input type="text" name="settings[currency_symbol]" value="{{ $settings['currency_symbol'] ?? '₦' }}" class="w-full rounded-lg border px-3 py-2 text-sm max-w-[100px]" />
                </div>

                <button type="submit" class="rounded-lg bg-brand-primary px-5 py-2 text-sm font-medium text-white hover:bg-brand-accent">Save Settings</button>
            </form>
        </div>
    </div>
@endsection
