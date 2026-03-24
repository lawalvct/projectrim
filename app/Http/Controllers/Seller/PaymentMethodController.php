<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentMethodController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user();
        $profile = $user->sellerProfile;

        $paymentMethods = PaymentMethod::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('dashboard/seller/PaymentMethodPage', [
            'paymentMethods' => $paymentMethods,
            'currentMethodId' => $profile?->preferred_payment_method_id,
            'bankAccountDetails' => $profile?->bank_account_details,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'preferred_payment_method_id' => ['required', 'exists:payment_methods,id'],
            'bank_account_details' => ['nullable', 'string', 'max:2000'],
        ]);

        $profile = $request->user()->sellerProfile;

        if (!$profile) {
            return back()->withErrors(['general' => 'Seller profile not found.']);
        }

        $profile->update($validated);

        return back()->with('success', 'Payment method updated successfully.');
    }
}
