<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Faculty;
use App\Models\Product;
use App\Models\Tag;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Product::published()
            ->with(['user:id,name', 'faculty:id,name,slug', 'images']);

        // Filter by faculty
        if ($request->filled('faculty')) {
            $query->where('faculty_id', $request->integer('faculty'));
        }

        // Filter by department
        if ($request->filled('department')) {
            $query->where('department_id', $request->integer('department'));
        }

        // Filter by document type
        if ($request->filled('document_type')) {
            $query->where('document_type', $request->input('document_type'));
        }

        // Filter by class of degree
        if ($request->filled('class_of_degree')) {
            $query->where('class_of_degree', $request->input('class_of_degree'));
        }

        // Filter by price (free / paid)
        if ($request->input('price') === 'free') {
            $query->where('is_paid', false);
        } elseif ($request->input('price') === 'paid') {
            $query->where('is_paid', true);
        }

        // Sort
        $sort = $request->input('sort', 'newest');
        $query = match ($sort) {
            'oldest' => $query->oldest('published_at'),
            'downloads' => $query->orderByDesc('downloads_count'),
            'views' => $query->orderByDesc('views_count'),
            'likes' => $query->orderByDesc('likes_count'),
            'title_asc' => $query->orderBy('title'),
            'title_desc' => $query->orderByDesc('title'),
            default => $query->latest('published_at'),
        };

        $products = $query->paginate(20)->withQueryString();

        $faculties = Faculty::withCount(['products' => fn ($q) => $q->where('status', 'published')])
            ->having('products_count', '>', 0)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return Inertia::render('products/Index', [
            'products' => $products,
            'faculties' => $faculties,
            'filters' => $request->only(['faculty', 'department', 'document_type', 'class_of_degree', 'price', 'sort']),
        ]);
    }

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
