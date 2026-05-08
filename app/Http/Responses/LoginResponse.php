<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        $user = $request->user();
        $home = $user?->isAdmin() ? '/admin' : '/dashboard';

        // Pull the intended URL captured by the auth middleware (if any).
        $intended = $request->session()->pull('url.intended');

        // If user came from a guarded action (e.g., /download/123), land them on
        // the dashboard and surface a modal asking whether to stay or continue
        // back to the page they were trying to reach.
        if ($intended && ! $this->pointsToHome($intended, $home)) {
            $request->session()->flash('login_return_url', $intended);

            return redirect($home);
        }

        return redirect($intended ?: $home);
    }

    private function pointsToHome(string $url, string $home): bool
    {
        $path = parse_url($url, PHP_URL_PATH) ?? $url;

        return $path === $home
            || str_starts_with($path, '/dashboard')
            || str_starts_with($path, '/admin')
            || $path === '/';
    }
}
