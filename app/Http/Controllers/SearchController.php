<?php

namespace App\Http\Controllers;

use App\Models\Faculty;
use App\Models\Product;
use App\Models\ProductAuthor;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Product::published()
            ->with(['user:id,name', 'faculty:id,name,slug', 'images']);

        // Keyword search (full-text on title, abstract, meta_keywords, institution)
        if ($request->filled('q')) {
            $keyword = $request->input('q');
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'LIKE', "%{$keyword}%")
                    ->orWhere('abstract', 'LIKE', "%{$keyword}%")
                    ->orWhere('meta_keywords', 'LIKE', "%{$keyword}%")
                    ->orWhere('institution', 'LIKE', "%{$keyword}%");
            });
        }

        // Author name search
        if ($request->filled('author_name')) {
            $authorName = $request->input('author_name');
            $query->where(function ($q) use ($authorName) {
                $q->whereHas('user', fn ($u) => $u->where('name', 'LIKE', "%{$authorName}%"))
                    ->orWhereHas('authors.user', fn ($u) => $u->where('name', 'LIKE', "%{$authorName}%"));
            });
        }

        // Author email search
        if ($request->filled('author_email')) {
            $authorEmail = $request->input('author_email');
            $query->where(function ($q) use ($authorEmail) {
                $q->whereHas('user', fn ($u) => $u->where('email', $authorEmail))
                    ->orWhereHas('authors.user', fn ($u) => $u->where('email', $authorEmail));
            });
        }

        // Faculty filter
        if ($request->filled('faculty')) {
            $query->where('faculty_id', $request->integer('faculty'));
        }

        // Department filter
        if ($request->filled('department')) {
            $query->where('department_id', $request->integer('department'));
        }

        // Institution filter
        if ($request->filled('institution')) {
            $query->where('institution', 'LIKE', "%{$request->input('institution')}%");
        }

        // Class of degree filter
        if ($request->filled('class_of_degree')) {
            $query->where('class_of_degree', $request->input('class_of_degree'));
        }

        // Document type filter
        if ($request->filled('document_type')) {
            $query->where('document_type', $request->input('document_type'));
        }

        // Country filter
        if ($request->filled('country')) {
            $query->where('location_country', $request->input('country'));
        }

        // Tags filter
        if ($request->filled('tags')) {
            $tagIds = (array) $request->input('tags');
            $query->whereHas('tags', fn ($q) => $q->whereIn('tags.id', $tagIds));
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('date_available', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date_available', '<=', $request->input('date_to'));
        }

        // Sort
        $sort = $request->input('sort', 'relevance');
        $query = match ($sort) {
            'newest' => $query->latest('published_at'),
            'downloads' => $query->orderByDesc('downloads_count'),
            'views' => $query->orderByDesc('views_count'),
            'likes' => $query->orderByDesc('likes_count'),
            default => $query->latest('published_at'), // relevance fallback
        };

        $products = $query->paginate(20)->withQueryString();

        $faculties = Faculty::withCount(['products' => fn ($q) => $q->where('status', 'published')])
            ->having('products_count', '>', 0)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return Inertia::render('Search', [
            'products' => $products,
            'faculties' => $faculties,
            'filters' => $request->only([
                'q', 'author_name', 'author_email', 'faculty', 'department',
                'institution', 'class_of_degree', 'document_type', 'country',
                'tags', 'date_from', 'date_to', 'sort',
            ]),
        ]);
    }
}
