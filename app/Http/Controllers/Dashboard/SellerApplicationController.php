<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SellerApplicationController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user->isSeller() || $user->isAdmin()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('dashboard/ApplySeller', [
            'isApproved' => $user->is_seller_approved,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->isSeller() || $user->isAdmin()) {
            return redirect()->route('dashboard');
        }

        $autoApprove = Setting::getValue('auto_approve_sellers', 'true') === 'true';

        if ($autoApprove) {
            $user->update([
                'role' => 'seller',
                'is_seller_approved' => true,
            ]);

            return redirect()->route('dashboard')->with('status', 'You are now a seller! Start uploading your projects.');
        }

        // Manual approval — mark as pending
        $user->update([
            'is_seller_approved' => false,
        ]);

        return redirect()->route('dashboard')->with('status', 'Your seller application has been submitted. We will review it shortly.');
    }
}
