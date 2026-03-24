<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $cart = $this->getOrCreateCart($request);

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
                    'price' => $item->product->price,
                    'is_paid' => $item->product->is_paid,
                    'image' => $item->product->images->first()?->path,
                    'author' => $item->product->user?->name,
                ],
            ]);

        return Inertia::render('Cart', [
            'items' => $items,
            'total' => $items->sum('price'),
        ]);
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
        ]);

        $product = Product::findOrFail($request->product_id);

        if (! $product->is_paid || $product->price <= 0) {
            return back()->withErrors(['product_id' => 'This product is free and cannot be added to cart.']);
        }

        $cart = $this->getOrCreateCart($request);

        // Check if already in cart
        $existing = $cart->items()->where('product_id', $product->id)->first();

        if ($existing) {
            return back()->with('flash', ['info' => 'Product is already in your cart.']);
        }

        $cart->items()->create([
            'product_id' => $product->id,
            'price' => $product->price,
        ]);

        return back()->with('flash', ['success' => 'Added to cart!']);
    }

    public function remove(Request $request, int $item)
    {
        $cart = $this->getCart($request);

        if ($cart) {
            $cart->items()->where('id', $item)->delete();
        }

        return back()->with('flash', ['success' => 'Item removed from cart.']);
    }

    public function count(Request $request)
    {
        $cart = $this->getCart($request);

        return response()->json([
            'count' => $cart ? $cart->items()->count() : 0,
        ]);
    }

    /**
     * Get existing cart or create a new one.
     */
    private function getOrCreateCart(Request $request): Cart
    {
        if ($user = $request->user()) {
            return Cart::firstOrCreate(['user_id' => $user->id]);
        }

        $sessionId = $request->session()->getId();

        return Cart::firstOrCreate(['session_id' => $sessionId]);
    }

    /**
     * Get existing cart (nullable).
     */
    private function getCart(Request $request): ?Cart
    {
        if ($user = $request->user()) {
            return Cart::where('user_id', $user->id)->first();
        }

        return Cart::where('session_id', $request->session()->getId())->first();
    }

    /**
     * Merge guest cart into user cart on login.
     */
    public static function mergeGuestCart(Request $request): void
    {
        $user = $request->user();
        if (! $user) {
            return;
        }

        $sessionId = $request->session()->getId();
        $guestCart = Cart::where('session_id', $sessionId)->first();

        if (! $guestCart) {
            return;
        }

        $userCart = Cart::firstOrCreate(['user_id' => $user->id]);

        // Move items from guest cart to user cart (skip duplicates)
        $existingProductIds = $userCart->items()->pluck('product_id')->toArray();

        $guestCart->items()
            ->whereNotIn('product_id', $existingProductIds)
            ->update(['cart_id' => $userCart->id]);

        // Delete remaining guest cart items and cart
        $guestCart->items()->delete();
        $guestCart->delete();
    }
}
