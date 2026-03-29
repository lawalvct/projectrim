<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\PaymentGiven;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentHistoryController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $payments = PaymentGiven::where('user_id', $userId)
            ->with('payoutRequest')
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($payment) => [
                'id' => $payment->id,
                'amount_usd' => $payment->amount_usd,
                'payment_method' => $payment->payment_method,
                'reference' => $payment->reference,
                'paid_at' => $payment->created_at?->format('M d, Y'),
                'paid_at_diff' => $payment->created_at?->diffForHumans(),
                'payout_request' => $payment->payoutRequest ? [
                    'id' => $payment->payoutRequest->id,
                    'amount_usd' => $payment->payoutRequest->amount_usd,
                    'status' => $payment->payoutRequest->status,
                ] : null,
            ]);

        $totalReceived = PaymentGiven::where('user_id', $userId)->sum('amount_usd');

        return Inertia::render('dashboard/seller/Payments', [
            'payments' => $payments,
            'totalReceived' => (float) $totalReceived,
        ]);
    }
}
