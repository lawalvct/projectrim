@extends('admin.layouts.app')

@section('title', 'Monetization Settings')

@section('content')
    {{-- Settings Nav --}}
    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('admin.settings.general') }}" class="rounded-lg border px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">General</a>
        <a href="{{ route('admin.settings.monetization') }}" class="rounded-lg bg-brand-primary px-4 py-2 text-sm font-medium text-white">Monetization</a>
        <a href="{{ route('admin.settings.payment') }}" class="rounded-lg border px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Payment</a>
        <a href="{{ route('admin.settings.seller') }}" class="rounded-lg border px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Seller</a>
        <a href="{{ route('admin.settings.carousel') }}" class="rounded-lg border px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Carousel</a>
        <a href="{{ route('admin.payment-methods.index') }}" class="rounded-lg border px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Payment Methods</a>
    </div>

    <div class="mx-auto max-w-2xl">
        <div class="rounded-xl border bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold">Monetization Settings</h2>

            <form method="POST" action="{{ route('admin.settings.update') }}">
                @csrf
                <input type="hidden" name="group" value="monetization" />

                <div class="mb-4">
                    <label class="flex items-center gap-2 text-sm font-medium">
                        <input type="hidden" name="settings[smart_link_enabled]" value="0" />
                        <input type="checkbox" name="settings[smart_link_enabled]" value="1" @checked(($settings['smart_link_enabled'] ?? '0') == '1') class="rounded" />
                        Enable Smart Link Monetization
                    </label>
                </div>

                <div class="mb-4">
                    <label class="mb-1 block text-sm font-medium">Smart Link URL</label>
                    <input type="url" name="settings[smart_link_url]" value="{{ $settings['smart_link_url'] ?? '' }}" class="w-full rounded-lg border px-3 py-2 text-sm" placeholder="https://example.com/smart-link" />
                </div>

                <div class="mb-4">
                    <label class="mb-1 block text-sm font-medium">Smart Link Code</label>
                    <textarea name="settings[smart_link_code]" rows="4" class="w-full rounded-lg border px-3 py-2 text-sm font-mono text-xs" placeholder="Paste JS/HTML code here">{{ $settings['smart_link_code'] ?? '' }}</textarea>
                </div>

                <div class="mb-4">
                    <label class="mb-1 block text-sm font-medium">View Reward Rate (per 1000 views)</label>
                    <input type="number" step="0.01" name="settings[view_reward_rate]" value="{{ $settings['view_reward_rate'] ?? '0' }}" class="w-full rounded-lg border px-3 py-2 text-sm max-w-[200px]" />
                </div>

                <div class="mb-4">
                    <label class="mb-1 block text-sm font-medium">Download Reward Rate (per download)</label>
                    <input type="number" step="0.01" name="settings[download_reward_rate]" value="{{ $settings['download_reward_rate'] ?? '0' }}" class="w-full rounded-lg border px-3 py-2 text-sm max-w-[200px]" />
                </div>

                <button type="submit" class="rounded-lg bg-brand-primary px-5 py-2 text-sm font-medium text-white hover:bg-brand-accent">Save Settings</button>
            </form>
        </div>
    </div>
@endsection
