<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    private const PROVIDERS = ['google', 'facebook', 'twitter', 'apple'];

    public function redirect(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, self::PROVIDERS), 404);

        $driver = match ($provider) {
            'twitter' => 'twitter-oauth-2',
            default => $provider,
        };

        return Socialite::driver($driver)->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, self::PROVIDERS), 404);

        $driver = match ($provider) {
            'twitter' => 'twitter-oauth-2',
            default => $provider,
        };

        try {
            $socialUser = Socialite::driver($driver)->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->with('status', 'Social login failed. Please try again.');
        }

        $user = User::where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        if ($user) {
            Auth::login($user, remember: true);

            return redirect()->intended('/dashboard');
        }

        // Check if a user with this email already exists (link accounts)
        $existingUser = User::where('email', $socialUser->getEmail())->first();

        if ($existingUser) {
            $existingUser->update([
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                'avatar' => $existingUser->avatar ?: $socialUser->getAvatar(),
            ]);

            Auth::login($existingUser, remember: true);

            return redirect()->intended('/dashboard');
        }

        // Create new user
        $user = User::create([
            'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'User',
            'email' => $socialUser->getEmail(),
            'avatar' => $socialUser->getAvatar(),
            'provider' => $provider,
            'provider_id' => $socialUser->getId(),
            'email_verified_at' => now(),
        ]);

        Auth::login($user, remember: true);

        return redirect('/dashboard');
    }
}
