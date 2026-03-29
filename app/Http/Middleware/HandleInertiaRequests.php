<?php

namespace App\Http\Middleware;

use App\Models\Cart;
use App\Models\Page;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'flash' => [
                'status' => fn () => $request->session()->get('status'),
                'success' => fn () => $request->session()->get('success'),
                'info' => fn () => $request->session()->get('info'),
            ],
            'cartCount' => fn () => $this->getCartCount($request),
            'navPages' => fn () => Page::published()->nav()->orderBy('sort_order')->get(['id', 'title', 'slug']),
            'footerPages' => fn () => Page::published()->footer()->orderBy('sort_order')->get(['id', 'title', 'slug']),
            'settings' => fn () => Setting::getValue('site_name') ? [
                'site_name' => Setting::getValue('site_name'),
                'site_tagline' => Setting::getValue('site_tagline'),
                'site_description' => Setting::getValue('site_description'),
                'contact_email' => Setting::getValue('contact_email'),
                'contact_phone' => Setting::getValue('contact_phone'),
                'facebook_url' => Setting::getValue('facebook_url'),
                'twitter_url' => Setting::getValue('twitter_url'),
                'linkedin_url' => Setting::getValue('linkedin_url'),
                'currency_symbol' => Setting::getValue('currency_symbol', '₦'),
                'smart_link_enabled' => Setting::getValue('smart_link_enabled', '0'),
                'smart_link_url' => Setting::getValue('smart_link_url', ''),
            ] : [],
        ];
    }

    private function getCartCount(Request $request): int
    {
        if ($user = $request->user()) {
            $cart = Cart::where('user_id', $user->id)->first();
        } else {
            $cart = Cart::where('session_id', $request->session()->getId())->first();
        }

        return $cart ? $cart->items()->count() : 0;
    }
}
