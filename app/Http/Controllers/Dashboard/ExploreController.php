<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExploreController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Product::with(['faculty:id,name,slug', 'images', 'user:id,name'])
            ->where('status', 'published');

        // Keyword search
        if ($request->filled('q')) {
            $keyword = $request->q;
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                    ->orWhere('abstract', 'like', "%{$keyword}%")
                    ->orWhere('institution', 'like', "%{$keyword}%");
            });
        }

        // Filters
        if ($request->filled('faculty')) {
            $query->whereHas('faculty', fn ($q) => $q->where('slug', $request->faculty));
        }

        if ($request->filled('document_type')) {
            $query->where('document_type', $request->document_type);
        }

        if ($request->filled('price')) {
            if ($request->price === 'free') {
                $query->where('is_paid', false);
            } elseif ($request->price === 'paid') {
                $query->where('is_paid', true);
            }
        }

        // Sorting
        $sort = $request->get('sort', 'newest');
        $query = match ($sort) {
            'oldest' => $query->oldest(),
            'downloads' => $query->orderByDesc('downloads_count'),
            'views' => $query->orderByDesc('views_count'),
            'title_asc' => $query->orderBy('title'),
            default => $query->latest(),
        };

        $products = $query->paginate(20)->withQueryString();

        $faculties = Faculty::whereHas('products', fn ($q) => $q->where('status', 'published'))
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return Inertia::render('dashboard/Explore', [
            'products' => $products,
            'faculties' => $faculties,
            'filters' => [
                'q' => $request->q,
                'faculty' => $request->faculty,
                'document_type' => $request->document_type,
                'price' => $request->price,
                'sort' => $sort,
            ],
        ]);
    }
}
