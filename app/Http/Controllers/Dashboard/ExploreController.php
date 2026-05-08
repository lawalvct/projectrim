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
        $facultyFilter = $request->input('faculty');
        $facultyId = null;

        if ($request->filled('faculty')) {
            $facultyId = is_numeric($facultyFilter)
                ? (int) $facultyFilter
                : Faculty::where('slug', $facultyFilter)->value('id');
        }

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
        if ($facultyId) {
            $query->where('faculty_id', $facultyId);
        }

        if ($request->filled('department')) {
            $query->where('department_id', $request->integer('department'));
        }

        if ($request->filled('document_type')) {
            $query->where('document_type', $request->document_type);
        }

        if ($request->filled('class_of_degree')) {
            $query->where('class_of_degree', $request->class_of_degree);
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
            'likes' => $query->orderByDesc('likes_count'),
            'title_asc' => $query->orderBy('title'),
            'title_desc' => $query->orderByDesc('title'),
            default => $query->latest(),
        };

        $products = $query->paginate(20)->withQueryString();

        $faculties = Faculty::withCount(['products' => fn ($query) => $query->where('status', 'published')])
            ->having('products_count', '>', 0)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return Inertia::render('dashboard/Explore', [
            'products' => $products,
            'faculties' => $faculties,
            'filters' => [
                'q' => $request->q,
                'faculty' => $facultyId ? (string) $facultyId : null,
                'department' => $request->department,
                'document_type' => $request->document_type,
                'class_of_degree' => $request->class_of_degree,
                'price' => $request->price,
                'sort' => $sort,
            ],
        ]);
    }
}
