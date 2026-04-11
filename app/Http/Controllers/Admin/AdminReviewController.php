<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminReviewController extends Controller
{
    public function index(Request $request)
    {
        $query = Review::with('user', 'product');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('comment', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($sub) => $sub->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('product', fn ($sub) => $sub->where('title', 'like', "%{$search}%"));
            });
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('reviews.created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('reviews.created_at', '<=', $dateTo);
        }

        $sort = $request->input('sort', 'newest');

        match ($sort) {
            'reviews_desc' => $query->select('reviews.*')
                ->join('products', 'reviews.product_id', '=', 'products.id')
                ->withCount([])
                ->addSelect(DB::raw('(SELECT COUNT(*) FROM reviews AS r WHERE r.product_id = products.id) as product_reviews_count'))
                ->orderByDesc('product_reviews_count'),
            'reviews_asc' => $query->select('reviews.*')
                ->join('products', 'reviews.product_id', '=', 'products.id')
                ->withCount([])
                ->addSelect(DB::raw('(SELECT COUNT(*) FROM reviews AS r WHERE r.product_id = products.id) as product_reviews_count'))
                ->orderBy('product_reviews_count'),
            'oldest' => $query->oldest(),
            default => $query->latest(),
        };

        $reviews = $query->paginate(20)->withQueryString();

        return view('admin.reviews.index', compact('reviews'));
    }

    public function destroy(Review $review)
    {
        $review->delete();

        return back()->with('success', 'Review deleted.');
    }
}
