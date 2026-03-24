@extends('admin.layouts.app')

@section('title', 'Seller Settings')

@section('content')
    {{-- Settings Nav --}}
    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('admin.settings.general') }}" class="rounded-lg border px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">General</a>
        <a href="{{ route('admin.settings.monetization') }}" class="rounded-lg border px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Monetization</a>
        <a href="{{ route('admin.settings.payment') }}" class="rounded-lg border px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Payment</a>
        <a href="{{ route('admin.settings.seller') }}" class="rounded-lg bg-brand-primary px-4 py-2 text-sm font-medium text-white">Seller</a>
        <a href="{{ route('admin.settings.carousel') }}" class="rounded-lg border px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Carousel</a>
        <a href="{{ route('admin.payment-methods.index') }}" class="rounded-lg border px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Payment Methods</a>
    </div>

    <div class="mx-auto max-w-2xl">
        <div class="rounded-xl border bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold">Seller Settings</h2>

            <form method="POST" action="{{ route('admin.settings.update') }}">
                @csrf
                <input type="hidden" name="group" value="seller" />

                <div class="mb-4">
                    <label class="flex items-center gap-2 text-sm font-medium">
                        <input type="hidden" name="settings[auto_approve_sellers]" value="0" />
                        <input type="checkbox" name="settings[auto_approve_sellers]" value="1" @checked(($settings['auto_approve_sellers'] ?? '0') == '1') class="rounded" />
                        Auto-approve seller applications
                    </label>
                    <p class="mt-1 pl-6 text-xs text-gray-400">When enabled, sellers are approved immediately without admin review.</p>
                </div>

                <div class="mb-4">
                    <label class="flex items-center gap-2 text-sm font-medium">
                        <input type="hidden" name="settings[allow_paid_products]" value="0" />
                        <input type="checkbox" name="settings[allow_paid_products]" value="1" @checked(($settings['allow_paid_products'] ?? '1') == '1') class="rounded" />
                        Allow sellers to upload paid products
                    </label>
                </div>

                <div class="mb-4">
                    <label class="mb-1 block text-sm font-medium">Commission Rate (%)</label>
                    <input type="number" step="0.1" min="0" max="100" name="settings[commission_rate]" value="{{ $settings['commission_rate'] ?? '10' }}" class="w-full rounded-lg border px-3 py-2 text-sm max-w-[200px]" />
                    <p class="mt-1 text-xs text-gray-400">Percentage the platform takes from each sale.</p>
                </div>

                <div class="mb-4">
                    <label class="mb-1 block text-sm font-medium">Minimum Payout Amount</label>
                    <input type="number" step="0.01" min="0" name="settings[minimum_payout]" value="{{ $settings['minimum_payout'] ?? '50' }}" class="w-full rounded-lg border px-3 py-2 text-sm max-w-[200px]" />
                </div>

                <button type="submit" class="rounded-lg bg-brand-primary px-5 py-2 text-sm font-medium text-white hover:bg-brand-accent">Save Settings</button>
            </form>
        </div>
    </div>
@endsection
