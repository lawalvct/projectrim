<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\PaymentMethod;
use App\Models\SellerProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SellerProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $profile = $request->user()->sellerProfile ?? new SellerProfile();

        $paymentMethods = PaymentMethod::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $countries = Country::orderBy('name')->get(['id', 'name', 'code']);

        return Inertia::render('dashboard/seller/Profile', [
            'profile' => [
                'bio' => $profile->bio,
                'company' => $profile->company,
                'phone' => $profile->phone,
                'country' => $profile->country,
                'region_state' => $profile->region_state,
                'preferred_payment_method_id' => $profile->preferred_payment_method_id,
                'bank_account_details' => $profile->bank_account_details,
                'company_logo' => $profile->company_logo,
                'banner' => $profile->banner,
            ],
            'paymentMethods' => $paymentMethods,
            'countries' => $countries,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bio' => ['nullable', 'string', 'max:5000'],
            'company' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'country' => ['nullable', 'string', 'max:100'],
            'region_state' => ['nullable', 'string', 'max:100'],
            'preferred_payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
            'bank_account_details' => ['nullable', 'string', 'max:2000'],
            'company_logo' => ['nullable', 'image', 'max:2048'],
            'banner' => ['nullable', 'image', 'max:4096'],
        ]);

        $profile = SellerProfile::firstOrNew(['user_id' => $request->user()->id]);

        // Handle logo upload
        if ($request->hasFile('company_logo')) {
            if ($profile->company_logo) {
                Storage::disk('public')->delete($profile->company_logo);
            }
            $validated['company_logo'] = $request->file('company_logo')->store('seller-logos', 'public');
        }

        // Handle banner upload
        if ($request->hasFile('banner')) {
            if ($profile->banner) {
                Storage::disk('public')->delete($profile->banner);
            }
            $validated['banner'] = $request->file('banner')->store('seller-banners', 'public');
        }

        $profile->fill($validated);
        $profile->user_id = $request->user()->id;
        $profile->save();

        return back()->with('success', 'Seller profile updated successfully.');
    }
}
