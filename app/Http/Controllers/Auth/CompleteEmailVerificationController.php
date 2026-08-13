<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CompleteEmailVerificationController extends Controller
{
    public function __invoke(Request $request, int $id, string $hash): RedirectResponse
    {
        $user = User::query()->findOrFail($id);

        abort_unless(
            hash_equals(sha1($user->getEmailForVerification()), $hash),
            403,
            'This email verification link is invalid.'
        );

        if (! $user->hasVerifiedEmail() && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        if ($request->user()?->is($user)) {
            return redirect()->route('dashboard', ['verified' => 1]);
        }

        if ($request->user()) {
            return redirect()->route('home')
                ->with('success', 'The email address has been verified successfully.');
        }

        return redirect()->route('login')
            ->with('status', 'Your email address has been verified. Please log in to continue.');
    }
}
