<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function show(string $slug): Response
    {
        $product = Product::where('slug', $slug)
            ->where('status', 'published')
            ->with([
                'user:id,name,email,avatar',
                'faculty:id,name,slug',
                'department:id,name,slug',
                'images',
                'files:id,product_id,file_name,file_size,file_type',
                'tags:id,name,slug',
                'authors.user:id,name,email',
                'reviews' => fn ($q) => $q->latest()->take(10),
                'reviews.user:id,name,avatar',
            ])
            ->withCount(['reviews', 'likes', 'downloads'])
            ->firstOrFail();

        // Increment views
        $product->increment('views_count');

        $relatedProducts = Product::published()
            ->where('id', '!=', $product->id)
            ->where(function ($q) use ($product) {
                $q->where('faculty_id', $product->faculty_id)
                    ->orWhere('department_id', $product->department_id);
            })
            ->with(['user:id,name', 'images'])
            ->take(4)
            ->get();

        return Inertia::render('products/Show', [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
        ]);
    }
}
