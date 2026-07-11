<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminSidebarBadges;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        AdminSidebarBadges::markAsSeen('users');
        $query = User::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        $users = $query->withCount(['products', 'orders', 'revenues'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user)
    {
        $user->loadCount(['products', 'orders', 'downloads', 'revenues']);
        $user->load('sellerProfile');

        $totalRevenue = $user->revenues()->sum('amount_usd');
        $totalPaidOut = $user->payoutRequests()->where('status', 'paid')->sum('amount_usd');
        $balance = $totalRevenue - $totalPaidOut;

        return view('admin.users.show', compact('user', 'totalRevenue', 'totalPaidOut', 'balance'));
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.$user->id,
            'role' => 'required|in:user,seller,admin',
        ]);

        $user->update($validated);

        return redirect()->route('admin.users.show', $user)->with('success', 'User updated successfully.');
    }

    public function impersonate(Request $request, User $user)
    {
        if ($user->isAdmin()) {
            return back()->with('error', 'Admin accounts cannot be impersonated.');
        }

        if ((string) $user->getKey() === (string) $request->user()?->getAuthIdentifier()) {
            return back()->with('error', 'You cannot impersonate your own account.');
        }

        $impersonatorId = $request->user()?->getAuthIdentifier();

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('impersonator_id', $impersonatorId);

        return redirect()->route('dashboard')->with('status', "You are now logged in as {$user->name}.");
    }

    public function stopImpersonating(Request $request)
    {
        $impersonatorId = $request->session()->pull('impersonator_id');

        if (! $impersonatorId) {
            return redirect()->route('dashboard');
        }

        $admin = User::query()
            ->whereKey($impersonatorId)
            ->where('role', 'admin')
            ->firstOrFail();

        Auth::login($admin);
        $request->session()->regenerate();

        return redirect()->route('admin.users.index')->with('success', 'Returned to your admin account.');
    }

    public function destroy(Request $request, User $user)
    {
        if ((string) $user->getKey() === (string) $request->user()?->getAuthIdentifier()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        User::destroy($user->getKey());

        return redirect()->route('admin.users.index')->with('success', 'User deleted.');
    }
}
