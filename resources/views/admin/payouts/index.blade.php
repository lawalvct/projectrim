@extends('admin.layouts.app')

@section('title', 'Payouts')

@section('content')
    <div class="mb-4">
        <form method="GET" class="flex gap-2">
            <select name="status" class="rounded-lg border px-3 py-2 text-sm">
                <option value="">All Status</option>
                <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                <option value="paid" @selected(request('status') === 'paid')>Paid</option>
                <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
            </select>
            <button type="submit" class="rounded-lg bg-brand-accent px-4 py-2 text-sm font-medium text-white hover:bg-brand-primary">Filter</button>
        </form>
    </div>

    <div class="rounded-xl border bg-white shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="border-b bg-gray-50 text-left text-xs font-medium uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">Seller</th>
                    <th class="px-4 py-3">Amount</th>
                    <th class="px-4 py-3">Payment Method</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Requested</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($payouts as $payout)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium">{{ $payout->user->name }}</td>
                        <td class="px-4 py-3">${{ number_format($payout->amount_usd, 2) }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $payout->paymentMethod->name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium
                                {{ $payout->status === 'paid' ? 'bg-green-100 text-green-700' : ($payout->status === 'approved' ? 'bg-blue-100 text-blue-700' : ($payout->status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700')) }}">
                                {{ ucfirst($payout->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ $payout->created_at->format('M d, Y') }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.payouts.show', $payout) }}" class="text-brand-light hover:underline">View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No payout requests.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $payouts->links() }}</div>
@endsection
