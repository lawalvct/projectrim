<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;

class AdminSellerApplicationController extends Controller
{
    public function index()
    {
        $applications = User::where('role', 'user')
            ->whereHas('sellerProfile')
            ->where('is_seller_approved', false)
            ->with('sellerProfile')
            ->latest()
            ->paginate(20);

        return view('admin.seller-applications.index', compact('applications'));
    }

    public function show(User $user)
    {
        $user->load('sellerProfile');

        return view('admin.seller-applications.show', compact('user'));
    }

    public function approve(User $user)
    {
        $user->update([
            'role' => 'seller',
            'is_seller_approved' => true,
        ]);

        return redirect()->route('admin.seller-applications.index')->with('success', "{$user->name}'s seller application approved.");
    }

    public function reject(User $user)
    {
        $user->sellerProfile?->delete();
        $user->update(['is_seller_approved' => false]);

        return redirect()->route('admin.seller-applications.index')->with('success', "{$user->name}'s application rejected.");
    }
}
