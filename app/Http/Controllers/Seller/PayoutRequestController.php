<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Models\PayoutRequest;
use App\Models\Revenue;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PayoutRequestController extends Controller
{
    public function index(Request $request): Response
    {
        $userId = $request->user()->id;
        $minimumPayout = $this->minimumPayoutAmount();

        $totalRevenue = (float) Revenue::where('user_id', $userId)->sum('amount_usd');
        $totalPaidOut = (float) PayoutRequest::where('user_id', $userId)
            ->where('status', 'paid')
            ->sum('amount_usd');
        $pendingPayout = (float) PayoutRequest::where('user_id', $userId)
            ->where('status', 'pending')
            ->sum('amount_usd');
        $availableBalance = $totalRevenue - $totalPaidOut - $pendingPayout;

        $query = PayoutRequest::with('paymentMethod:id,name')
            ->where('user_id', $userId);

        if ($request->filled('status') && in_array($request->status, ['pending', 'approved', 'paid', 'rejected'])) {
            $query->where('status', $request->status);
        }

        $payouts = $query->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($p) => [
                'id' => $p->id,
                'amount_usd' => (float) $p->amount_usd,
                'status' => $p->status,
                'payment_method' => $p->paymentMethod?->name,
                'payment_details' => $p->payment_details,
                'admin_note' => $p->admin_note,
                'processed_at' => $p->processed_at?->format('M d, Y'),
                'created_at' => $p->created_at->format('M d, Y'),
                'created_at_diff' => $p->created_at->diffForHumans(),
            ]);

        $paymentMethods = PaymentMethod::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return Inertia::render('dashboard/seller/Payouts', [
            'payouts' => $payouts,
            'balance' => [
                'total_revenue' => $totalRevenue,
                'total_paid_out' => $totalPaidOut,
                'pending_payout' => $pendingPayout,
                'available' => $availableBalance,
            ],
            'minimumPayout' => $minimumPayout,
            'paymentMethods' => $paymentMethods,
            'filters' => ['status' => $request->status],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $userId = $request->user()->id;
        $minimumPayout = $this->minimumPayoutAmount();

        $validated = $request->validate([
            'amount_usd' => ['required', 'numeric', 'gt:0', 'min:'.$minimumPayout],
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
            'payment_details' => ['nullable', 'string', 'max:2000'],
        ], [
            'amount_usd.gt' => 'Enter a payout amount greater than zero.',
            'amount_usd.min' => 'The minimum payout amount is '.$this->currencySymbol().number_format($minimumPayout, 2).'.',
        ]);

        // Verify available balance
        $totalRevenue = (float) Revenue::where('user_id', $userId)->sum('amount_usd');
        $totalPaidOut = (float) PayoutRequest::where('user_id', $userId)->where('status', 'paid')->sum('amount_usd');
        $pendingPayout = (float) PayoutRequest::where('user_id', $userId)->where('status', 'pending')->sum('amount_usd');
        $available = $totalRevenue - $totalPaidOut - $pendingPayout;

        if ($validated['amount_usd'] > $available) {
            return back()->withErrors(['amount_usd' => 'Insufficient available balance.']);
        }

        PayoutRequest::create([
            'user_id' => $userId,
            'amount_usd' => $validated['amount_usd'],
            'status' => 'pending',
            'payment_method_id' => $validated['payment_method_id'],
            'payment_details' => $validated['payment_details'] ?? null,
        ]);

        return back()->with('success', 'Payout request submitted successfully.');
    }

    private function minimumPayoutAmount(): float
    {
        $minimumPayout = Setting::getValue('minimum_payout', '5');

        return max((float) $minimumPayout, 0.0);
    }

    private function currencySymbol(): string
    {
        return (string) Setting::getValue('currency_symbol', '$');
    }
}
