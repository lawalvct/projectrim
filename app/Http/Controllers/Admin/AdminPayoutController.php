<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\PayoutApprovedNotification;
use App\Models\PaymentGiven;
use App\Models\PayoutRequest;
use App\Support\AdminSidebarBadges;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AdminPayoutController extends Controller
{
    public function index(Request $request)
    {
        AdminSidebarBadges::markAsSeen('payouts');
        $query = PayoutRequest::with('user', 'paymentMethod');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $payouts = $query->latest()->paginate(20)->withQueryString();

        return view('admin.payouts.index', compact('payouts'));
    }

    public function show(PayoutRequest $payout)
    {
        $payout->load('user.sellerProfile.preferredPaymentMethod', 'paymentMethod');

        $totalEarned = $payout->user->revenues()->sum('amount_usd');
        $totalPaid = PaymentGiven::where('user_id', $payout->user_id)->sum('amount_usd');

        return view('admin.payouts.show', compact('payout', 'totalEarned', 'totalPaid'));
    }

    public function approve(PayoutRequest $payout)
    {
        $statusChanged = PayoutRequest::query()
            ->whereKey($payout->id)
            ->where('status', '!=', 'approved')
            ->update(['status' => 'approved']) === 1;

        $payout->refresh();

        if ($statusChanged) {
            $recipient = $payout->user;

            if ($recipient?->email) {
                try {
                    $notification = (new PayoutApprovedNotification($payout))
                        ->onConnection((string) config('queue.notifications.connection', 'database'))
                        ->onQueue((string) config('queue.notifications.queue', 'default'));

                    Mail::to($recipient->email)->queue($notification);
                } catch (\Throwable $exception) {
                    Log::warning('Payout approved, but its email notification could not be queued.', [
                        'payout_request_id' => $payout->id,
                        'recipient_id' => $recipient->id,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }
        }

        return back()->with('success', 'Payout request approved.');
    }

    public function pay(PayoutRequest $payout)
    {
        $payout->update(['status' => 'paid', 'processed_at' => now()]);

        PaymentGiven::create([
            'user_id' => $payout->user_id,
            'payout_request_id' => $payout->id,
            'amount_usd' => $payout->amount_usd,
            'payment_method' => $payout->paymentMethod?->name ?? 'bank_transfer',
            'reference' => 'ADMIN-'.now()->format('YmdHis'),
        ]);

        return back()->with('success', 'Payout marked as paid and payment recorded.');
    }

    public function reject(PayoutRequest $payout)
    {
        $payout->update(['status' => 'rejected']);

        return back()->with('success', 'Payout request rejected.');
    }
}
