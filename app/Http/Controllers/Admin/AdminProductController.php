<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class AdminProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('user', 'faculty', 'department');

        if ($search = $request->input('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($request->input('is_paid') !== null) {
            $query->where('is_paid', $request->boolean('is_paid'));
        }

        $products = $query->latest()->paginate(20)->withQueryString();

        return view('admin.products.index', compact('products'));
    }

    public function show(Product $product)
    {
        $product->load('user', 'faculty', 'department', 'images', 'files', 'tags', 'authors.user', 'reviews.user');
        $product->loadCount(['reviews', 'orderItems', 'likes', 'downloads']);

        $totalRevenue = $product->revenues()->sum('amount_usd');
        $buyers = $product->orderItems()
            ->with('order.user')
            ->get()
            ->map(fn ($item) => $item->order?->user)
            ->filter()
            ->unique('id');

        return view('admin.products.show', compact('product', 'totalRevenue', 'buyers'));
    }

    public function edit(Product $product)
    {
        $product->load('tags', 'faculty', 'department');

        return view('admin.products.edit', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'is_paid' => 'boolean',
            'is_featured' => 'boolean',
            'status' => 'required|in:draft,pending,published,rejected',
        ]);

        $product->update($validated);

        return redirect()->route('admin.products.show', $product)->with('success', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Product deleted.');
    }

    public function approve(Product $product)
    {
        $product->update(['status' => 'published', 'published_at' => now()]);

        return back()->with('success', 'Product approved and published.');
    }

    public function reject(Product $product)
    {
        $product->update(['status' => 'rejected']);

        return back()->with('success', 'Product rejected.');
    }
}
