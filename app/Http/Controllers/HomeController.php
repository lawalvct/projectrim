<?php

namespace App\Http\Controllers;

use App\Models\Faculty;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Laravel\Fortify\Features;

class HomeController extends Controller
{
    public function index()
    {
        $featuredProducts = Product::published()
            ->featured()
            ->with(['user:id,name', 'faculty:id,name', 'images'])
            ->latest('published_at')
            ->take(8)
            ->get();

        $recentProducts = Product::published()
            ->with(['user:id,name', 'faculty:id,name', 'images'])
            ->latest('published_at')
            ->take(8)
            ->get();

        $faculties = Faculty::withCount(['products' => fn ($q) => $q->where('status', 'published')])
            ->having('products_count', '>', 0)
            ->orderByDesc('products_count')
            ->take(10)
            ->get(['id', 'name', 'slug']);

        $stats = [
            'products' => Product::where('status', 'published')->count(),
            'authors' => User::whereHas('products', fn ($q) => $q->where('status', 'published'))->count(),
            'downloads' => (int) Product::where('status', 'published')->sum('downloads_count'),
        ];

        return Inertia::render('Welcome', [
            'canRegister' => Features::enabled(Features::registration()),
            'featuredProducts' => $featuredProducts,
            'recentProducts' => $recentProducts,
            'faculties' => $faculties,
            'stats' => $stats,
        ]);
    }
}
