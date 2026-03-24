<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Download;
use App\Models\Order;
use App\Models\Revenue;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CheckoutController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $cart = Cart::where('user_id', $user->id)->first();

        if (! $cart || $cart->items()->count() === 0) {
            return redirect('/cart')->with('flash', ['info' => 'Your cart is empty.']);
        }

        $items = $cart->items()
            ->with(['product:id,title,slug,price,is_paid', 'product.images', 'product.user:id,name'])
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'price' => $item->price,
                'product' => [
                    'id' => $item->product->id,
                    'title' => $item->product->title,
                    'slug' => $item->product->slug,
                    'image' => $item->product->images->first()?->path,
                    'author' => $item->product->user?->name,
                ],
            ]);

        $total = $items->sum('price');

        // Gather enabled gateways
        $gateways = collect([
            ['key' => 'stripe', 'name' => 'Stripe (Card)', 'enabled' => Setting::getValue('stripe_enabled', 'false') === 'true'],
            ['key' => 'paypal', 'name' => 'PayPal', 'enabled' => Setting::getValue('paypal_enabled', 'false') === 'true'],
            ['key' => 'paystack', 'name' => 'Paystack', 'enabled' => Setting::getValue('paystack_enabled', 'false') === 'true'],
            ['key' => 'flutterwave', 'name' => 'Flutterwave', 'enabled' => Setting::getValue('flutterwave_enabled', 'false') === 'true'],
            ['key' => 'bank_transfer', 'name' => 'Bank Transfer', 'enabled' => Setting::getValue('bank_transfer_enabled', 'true') === 'true'],
        ])->filter(fn ($g) => $g['enabled'])->values();

        return Inertia::render('Checkout', [
            'items' => $items,
            'total' => (float) $total,
            'gateways' => $gateways,
            'bankDetails' => Setting::getValue('bank_transfer_details', ''),
        ]);
    }

    public function process(Request $request)
    {
        $request->validate([
            'gateway' => ['required', 'in:stripe,paypal,paystack,flutterwave,bank_transfer'],
        ]);

        $user = $request->user();
        $cart = Cart::where('user_id', $user->id)->first();

        if (! $cart || $cart->items()->count() === 0) {
            return redirect('/cart')->with('flash', ['info' => 'Your cart is empty.']);
        }

        $cartItems = $cart->items()->with('product')->get();
        $total = $cartItems->sum('price');

        // Create order
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-' . strtoupper(Str::random(10)),
            'status' => 'pending',
            'subtotal' => $total,
            'total' => $total,
            'payment_gateway' => $request->gateway,
        ]);

        foreach ($cartItems as $item) {
            $order->items()->create([
                'product_id' => $item->product_id,
                'price' => $item->price,
            ]);
        }

        $gateway = $request->gateway;

        // For bank transfer, just finalize as pending
        if ($gateway === 'bank_transfer') {
            return redirect()->route('checkout.success', ['order' => $order->order_number])
                ->with('flash', ['success' => 'Order placed! Please complete bank transfer to finalize.']);
        }

        // For online gateways — redirect to gateway-specific init
        return $this->initiateGatewayPayment($order, $gateway, $request);
    }

    /**
     * Gateway-specific payment initiation.
     */
    private function initiateGatewayPayment(Order $order, string $gateway, Request $request)
    {
        return match ($gateway) {
            'stripe' => $this->initiateStripe($order, $request),
            'paypal' => $this->initiatePaypal($order, $request),
            'paystack' => $this->initiatePaystack($order, $request),
            'flutterwave' => $this->initiateFlutterwave($order, $request),
            default => redirect('/cart')->withErrors(['gateway' => 'Invalid payment gateway.']),
        };
    }

    // ----- Stripe -----

    private function initiateStripe(Order $order, Request $request)
    {
        $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));

        $lineItems = $order->items->map(fn ($item) => [
            'price_data' => [
                'currency' => strtolower(Setting::getValue('currency', 'NGN')),
                'product_data' => [
                    'name' => $item->product->title ?? 'Product #' . $item->product_id,
                ],
                'unit_amount' => (int) ($item->price * 100),
            ],
            'quantity' => 1,
        ])->toArray();

        $session = $stripe->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => route('checkout.callback', ['gateway' => 'stripe', 'order' => $order->order_number]) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('checkout.cancel', ['order' => $order->order_number]),
            'metadata' => [
                'order_number' => $order->order_number,
            ],
        ]);

        return Inertia::location($session->url);
    }

    // ----- PayPal -----

    private function initiatePaypal(Order $order, Request $request)
    {
        // PayPal redirect-based flow
        $clientId = config('services.paypal.client_id');
        $secret = config('services.paypal.secret');
        $baseUrl = config('services.paypal.mode') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';

        // Get access token
        $response = \Illuminate\Support\Facades\Http::withBasicAuth($clientId, $secret)
            ->asForm()
            ->post("{$baseUrl}/v1/oauth2/token", ['grant_type' => 'client_credentials']);

        $accessToken = $response->json('access_token');

        // Create order
        $paypalOrder = \Illuminate\Support\Facades\Http::withToken($accessToken)
            ->post("{$baseUrl}/v2/checkout/orders", [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => $order->order_number,
                    'amount' => [
                        'currency_code' => Setting::getValue('currency', 'NGN'),
                        'value' => number_format($order->total, 2, '.', ''),
                    ],
                ]],
                'application_context' => [
                    'return_url' => route('checkout.callback', ['gateway' => 'paypal', 'order' => $order->order_number]),
                    'cancel_url' => route('checkout.cancel', ['order' => $order->order_number]),
                ],
            ]);

        $approvalLink = collect($paypalOrder->json('links'))->firstWhere('rel', 'approve');

        if ($approvalLink) {
            $order->update(['payment_reference' => $paypalOrder->json('id')]);

            return Inertia::location($approvalLink['href']);
        }

        return redirect('/cart')->withErrors(['gateway' => 'Failed to create PayPal order.']);
    }

    // ----- Paystack -----

    private function initiatePaystack(Order $order, Request $request)
    {
        $response = \Illuminate\Support\Facades\Http::withToken(config('services.paystack.secret_key'))
            ->post('https://api.paystack.co/transaction/initialize', [
                'email' => $request->user()->email,
                'amount' => (int) ($order->total * 100), // Paystack uses kobo
                'currency' => Setting::getValue('currency', 'NGN'),
                'reference' => $order->order_number,
                'callback_url' => route('checkout.callback', ['gateway' => 'paystack', 'order' => $order->order_number]),
                'metadata' => [
                    'order_number' => $order->order_number,
                ],
            ]);

        $data = $response->json('data');

        if ($response->successful() && $data && isset($data['authorization_url'])) {
            $order->update(['payment_reference' => $data['reference']]);

            return Inertia::location($data['authorization_url']);
        }

        return redirect('/cart')->withErrors(['gateway' => 'Failed to initialize Paystack payment.']);
    }

    // ----- Flutterwave -----

    private function initiateFlutterwave(Order $order, Request $request)
    {
        $response = \Illuminate\Support\Facades\Http::withToken(config('services.flutterwave.secret_key'))
            ->post('https://api.flutterwave.com/v3/payments', [
                'tx_ref' => $order->order_number,
                'amount' => (string) $order->total,
                'currency' => Setting::getValue('currency', 'NGN'),
                'redirect_url' => route('checkout.callback', ['gateway' => 'flutterwave', 'order' => $order->order_number]),
                'customer' => [
                    'email' => $request->user()->email,
                    'name' => $request->user()->name,
                ],
                'meta' => [
                    'order_number' => $order->order_number,
                ],
                'customizations' => [
                    'title' => config('app.name', 'ProjectRim'),
                ],
            ]);

        $data = $response->json('data');

        if ($response->successful() && $data && isset($data['link'])) {
            return Inertia::location($data['link']);
        }

        return redirect('/cart')->withErrors(['gateway' => 'Failed to initialize Flutterwave payment.']);
    }

    // =================== CALLBACKS ===================

    public function callback(Request $request, string $gateway, string $order)
    {
        $orderModel = Order::where('order_number', $order)->where('status', 'pending')->firstOrFail();

        $verified = match ($gateway) {
            'stripe' => $this->verifyStripe($request, $orderModel),
            'paypal' => $this->verifyPaypal($request, $orderModel),
            'paystack' => $this->verifyPaystack($request, $orderModel),
            'flutterwave' => $this->verifyFlutterwave($request, $orderModel),
            default => false,
        };

        if ($verified) {
            $this->completeOrder($orderModel);

            return redirect()->route('checkout.success', ['order' => $orderModel->order_number])
                ->with('flash', ['success' => 'Payment successful! Your order is complete.']);
        }

        $orderModel->update(['status' => 'failed']);

        return redirect()->route('checkout.failed', ['order' => $orderModel->order_number])
            ->with('flash', ['error' => 'Payment verification failed.']);
    }

    private function verifyStripe(Request $request, Order $order): bool
    {
        $sessionId = $request->get('session_id');

        if (! $sessionId) {
            return false;
        }

        $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));

        try {
            $session = $stripe->checkout->sessions->retrieve($sessionId);

            if ($session->payment_status === 'paid') {
                $order->update(['payment_reference' => $session->payment_intent]);

                return true;
            }
        } catch (\Exception $e) {
            report($e);
        }

        return false;
    }

    private function verifyPaypal(Request $request, Order $order): bool
    {
        $token = $request->get('token');

        if (! $token) {
            return false;
        }

        $clientId = config('services.paypal.client_id');
        $secret = config('services.paypal.secret');
        $baseUrl = config('services.paypal.mode') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';

        try {
            $tokenResponse = \Illuminate\Support\Facades\Http::withBasicAuth($clientId, $secret)
                ->asForm()
                ->post("{$baseUrl}/v1/oauth2/token", ['grant_type' => 'client_credentials']);

            $accessToken = $tokenResponse->json('access_token');

            // Capture the order
            $captureResponse = \Illuminate\Support\Facades\Http::withToken($accessToken)
                ->post("{$baseUrl}/v2/checkout/orders/{$token}/capture");

            if ($captureResponse->successful() && $captureResponse->json('status') === 'COMPLETED') {
                $order->update(['payment_reference' => $token]);

                return true;
            }
        } catch (\Exception $e) {
            report($e);
        }

        return false;
    }

    private function verifyPaystack(Request $request, Order $order): bool
    {
        $reference = $request->get('reference', $order->payment_reference);

        if (! $reference) {
            return false;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::withToken(config('services.paystack.secret_key'))
                ->get("https://api.paystack.co/transaction/verify/{$reference}");

            $data = $response->json('data');

            if ($response->successful() && $data && ($data['status'] ?? '') === 'success') {
                $order->update(['payment_reference' => $reference]);

                return true;
            }
        } catch (\Exception $e) {
            report($e);
        }

        return false;
    }

    private function verifyFlutterwave(Request $request, Order $order): bool
    {
        $transactionId = $request->get('transaction_id');

        if (! $transactionId) {
            return false;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::withToken(config('services.flutterwave.secret_key'))
                ->get("https://api.flutterwave.com/v3/transactions/{$transactionId}/verify");

            $data = $response->json('data');

            if ($response->successful() && $data && ($data['status'] ?? '') === 'successful') {
                $order->update(['payment_reference' => $transactionId]);

                return true;
            }
        } catch (\Exception $e) {
            report($e);
        }

        return false;
    }

    // =================== ORDER COMPLETION ===================

    private function completeOrder(Order $order): void
    {
        $order->update([
            'status' => 'completed',
            'paid_at' => now(),
        ]);

        // Grant download access + record sale revenue for each item
        foreach ($order->items()->with('product.authors')->get() as $item) {
            // Grant download record
            Download::firstOrCreate([
                'user_id' => $order->user_id,
                'product_id' => $item->product_id,
            ], [
                'ip_address' => request()->ip(),
            ]);

            // Record sale revenue distributed among authors
            $product = $item->product;
            $authors = $product->authors;

            if ($authors->isEmpty()) {
                // Fallback: all revenue to product owner
                Revenue::create([
                    'product_id' => $product->id,
                    'user_id' => $product->user_id,
                    'type' => 'sale',
                    'amount_usd' => $item->price,
                    'visitor_ip' => request()->ip(),
                ]);
            } else {
                foreach ($authors as $author) {
                    $share = ($author->contribution_percentage / 100) * $item->price;
                    Revenue::create([
                        'product_id' => $product->id,
                        'user_id' => $author->user_id,
                        'type' => 'sale',
                        'amount_usd' => round($share, 4),
                        'visitor_ip' => request()->ip(),
                    ]);
                }
            }
        }

        // Clear the cart
        $cart = Cart::where('user_id', $order->user_id)->first();
        if ($cart) {
            $cart->items()->delete();
        }
    }

    public function cancel(Request $request, string $order)
    {
        return Inertia::render('checkout/Cancel', [
            'orderNumber' => $order,
        ]);
    }

    public function success(Request $request, string $order)
    {
        $orderModel = Order::where('order_number', $order)
            ->where('user_id', $request->user()->id)
            ->with('items.product')
            ->firstOrFail();

        return Inertia::render('checkout/Success', [
            'order' => [
                'order_number' => $orderModel->order_number,
                'status' => $orderModel->status,
                'total' => $orderModel->total,
                'payment_gateway' => $orderModel->payment_gateway,
                'paid_at' => $orderModel->paid_at?->format('M d, Y H:i'),
                'items' => $orderModel->items->map(fn ($item) => [
                    'product_title' => $item->product->title ?? 'Product',
                    'product_slug' => $item->product->slug ?? '',
                    'price' => $item->price,
                ]),
            ],
        ]);
    }

    public function failed(Request $request, string $order)
    {
        return Inertia::render('checkout/Failed', [
            'orderNumber' => $order,
        ]);
    }
}
