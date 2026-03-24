<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentGiven;
use App\Models\PayoutRequest;
use Illuminate\Http\Request;

class AdminPayoutController extends Controller
{
    public function index(Request $request)
    {
        $query = PayoutRequest::with('user');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $payouts = $query->latest()->paginate(20)->withQueryString();

        return view('admin.payouts.index', compact('payouts'));
    }

    public function show(PayoutRequest $payout)
    {
        $payout->load('user.sellerProfile');

        $totalEarned = $payout->user->revenues()->sum('amount_usd');
        $totalPaid = PaymentGiven::where('user_id', $payout->user_id)->sum('amount');

        return view('admin.payouts.show', compact('payout', 'totalEarned', 'totalPaid'));
    }

    public function approve(PayoutRequest $payout)
    {
        $payout->update(['status' => 'approved']);

        return back()->with('success', 'Payout request approved.');
    }

    public function pay(PayoutRequest $payout)
    {
        $payout->update(['status' => 'paid', 'paid_at' => now()]);

        PaymentGiven::create([
            'user_id' => $payout->user_id,
            'payout_request_id' => $payout->id,
            'amount' => $payout->amount,
            'method' => $payout->method ?? 'bank_transfer',
            'reference' => 'ADMIN-' . now()->format('YmdHis'),
        ]);

        return back()->with('success', 'Payout marked as paid and payment recorded.');
    }

    public function reject(PayoutRequest $payout)
    {
        $payout->update(['status' => 'rejected']);

        return back()->with('success', 'Payout request rejected.');
    }
}
