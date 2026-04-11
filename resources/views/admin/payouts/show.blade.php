@extends('admin.layouts.app')

@section('title', 'Payout Request')

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.payouts.index') }}" class="text-sm text-brand-light hover:underline">← Back to Payouts</a>
    </div>

    <div class="mx-auto max-w-2xl space-y-6">
        <div class="rounded-xl border bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold">Payout #{{ $payout->id }}</h2>
                <span class="rounded-full px-3 py-1 text-xs font-medium
                    {{ $payout->status === 'paid' ? 'bg-green-100 text-green-700' : ($payout->status === 'approved' ? 'bg-blue-100 text-blue-700' : ($payout->status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700')) }}">
                    {{ ucfirst($payout->status) }}
                </span>
            </div>

            <div class="grid gap-3 text-sm sm:grid-cols-2">
                <div><span class="text-gray-500">Seller:</span> {{ $payout->user->name }}</div>
                <div><span class="text-gray-500">Email:</span> {{ $payout->user->email }}</div>
                <div><span class="text-gray-500">Amount:</span> <strong>${{ number_format($payout->amount_usd, 2) }}</strong></div>
                <div><span class="text-gray-500">Payment Method:</span> {{ $payout->paymentMethod->name ?? '—' }}</div>
                <div><span class="text-gray-500">Total Earned:</span> ${{ number_format($totalEarned, 2) }}</div>
                <div><span class="text-gray-500">Total Paid:</span> ${{ number_format($totalPaid, 2) }}</div>
                <div><span class="text-gray-500">Requested:</span> {{ $payout->created_at->format('M d, Y H:i') }}</div>
                @if ($payout->processed_at)
                    <div><span class="text-gray-500">Paid at:</span> {{ $payout->processed_at->format('M d, Y H:i') }}</div>
                @endif
            </div>

            @if ($payout->payment_details)
                <div class="mt-4 rounded-lg bg-gray-50 p-3">
                    <h4 class="text-xs font-semibold text-gray-600 uppercase mb-1">Payment Details (from request)</h4>
                    <p class="text-sm whitespace-pre-wrap">{{ $payout->payment_details }}</p>
                </div>
            @endif

            @if ($payout->user->sellerProfile?->preferredPaymentMethod || $payout->user->sellerProfile?->bank_account_details)
                <div class="mt-4 rounded-lg bg-blue-50 border border-blue-100 p-3">
                    <h4 class="text-xs font-semibold text-blue-700 uppercase mb-2">Seller's Payment Profile</h4>
                    @if ($payout->user->sellerProfile?->preferredPaymentMethod)
                        <p class="text-sm mb-1"><span class="text-gray-500">Preferred Method:</span> {{ $payout->user->sellerProfile->preferredPaymentMethod->name }}</p>
                    @endif
                    @if ($payout->user->sellerProfile?->bank_account_details)
                        <p class="text-sm whitespace-pre-wrap">{{ $payout->user->sellerProfile->bank_account_details }}</p>
                    @endif
                </div>
            @endif
        </div>

        @if ($payout->status === 'pending' || $payout->status === 'approved')
            <div class="rounded-xl border bg-white p-6 shadow-sm flex flex-wrap gap-2">
                @if ($payout->status === 'pending')
                    <form method="POST" action="{{ route('admin.payouts.approve', $payout) }}">@csrf
                        <button type="submit" class="rounded-lg bg-blue-600 px-6 py-2 text-sm font-medium text-white hover:bg-blue-700">Approve</button>
                    </form>
                @endif
                @if ($payout->status === 'pending' || $payout->status === 'approved')
                    <form method="POST" action="{{ route('admin.payouts.pay', $payout) }}" onsubmit="return confirm('Mark as paid?')">@csrf
                        <button type="submit" class="rounded-lg bg-green-600 px-6 py-2 text-sm font-medium text-white hover:bg-green-700">Mark as Paid</button>
                    </form>
                @endif
                @if ($payout->status === 'pending')
                    <form method="POST" action="{{ route('admin.payouts.reject', $payout) }}" onsubmit="return confirm('Reject this payout?')">@csrf
                        <button type="submit" class="rounded-lg bg-brand-danger px-6 py-2 text-sm font-medium text-white hover:bg-brand-danger-dark">Reject</button>
                    </form>
                @endif
            </div>
        @endif
    </div>
@endsection
