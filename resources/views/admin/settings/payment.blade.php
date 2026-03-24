@extends('admin.layouts.app')

@section('title', 'Payment Settings')

@section('content')
    {{-- Settings Nav --}}
    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('admin.settings.general') }}" class="rounded-lg border px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">General</a>
        <a href="{{ route('admin.settings.monetization') }}" class="rounded-lg border px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Monetization</a>
        <a href="{{ route('admin.settings.payment') }}" class="rounded-lg bg-brand-primary px-4 py-2 text-sm font-medium text-white">Payment</a>
        <a href="{{ route('admin.settings.seller') }}" class="rounded-lg border px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Seller</a>
        <a href="{{ route('admin.settings.carousel') }}" class="rounded-lg border px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Carousel</a>
        <a href="{{ route('admin.payment-methods.index') }}" class="rounded-lg border px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Payment Methods</a>
    </div>

    <div class="mx-auto max-w-2xl">
        <div class="rounded-xl border bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold">Payment Gateway Settings</h2>

            <form method="POST" action="{{ route('admin.settings.update') }}">
                @csrf
                <input type="hidden" name="group" value="payment" />

                @php
                    $gateways = ['stripe', 'paypal', 'paystack', 'flutterwave', 'bank_transfer'];
                @endphp

                @foreach ($gateways as $gw)
                    <div class="mb-4 rounded-lg border p-4">
                        <label class="flex items-center gap-2 text-sm font-medium mb-2">
                            <input type="hidden" name="settings[{{ $gw }}_enabled]" value="0" />
                            <input type="checkbox" name="settings[{{ $gw }}_enabled]" value="1" @checked(($settings[$gw . '_enabled'] ?? '0') == '1') class="rounded" />
                            Enable {{ ucwords(str_replace('_', ' ', $gw)) }}
                        </label>

                        @if ($gw !== 'bank_transfer')
                            <div class="mt-2 space-y-2 pl-6">
                                <div>
                                    <label class="mb-1 block text-xs text-gray-500">Public Key</label>
                                    <input type="text" name="settings[{{ $gw }}_public_key]" value="{{ $settings[$gw . '_public_key'] ?? '' }}" class="w-full rounded-lg border px-3 py-2 text-sm" />
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs text-gray-500">Secret Key</label>
                                    <input type="password" name="settings[{{ $gw }}_secret_key]" value="{{ $settings[$gw . '_secret_key'] ?? '' }}" class="w-full rounded-lg border px-3 py-2 text-sm" />
                                </div>
                            </div>
                        @else
                            <div class="mt-2 pl-6">
                                <label class="mb-1 block text-xs text-gray-500">Bank Transfer Instructions</label>
                                <textarea name="settings[bank_transfer_instructions]" rows="3" class="w-full rounded-lg border px-3 py-2 text-sm">{{ $settings['bank_transfer_instructions'] ?? '' }}</textarea>
                            </div>
                        @endif
                    </div>
                @endforeach

                <button type="submit" class="rounded-lg bg-brand-primary px-5 py-2 text-sm font-medium text-white hover:bg-brand-accent">Save Settings</button>
            </form>
        </div>
    </div>
@endsection
