<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiSearchController extends Controller
{
    public function autocomplete(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $products = Product::published()
            ->where('title', 'LIKE', "%{$query}%")
            ->take(5)
            ->get(['id', 'title', 'slug'])
            ->map(fn ($p) => ['type' => 'product', 'title' => $p->title, 'url' => "/products/{$p->slug}"]);

        $institutions = Product::published()
            ->whereNotNull('institution')
            ->where('institution', 'LIKE', "%{$query}%")
            ->select('institution')
            ->distinct()
            ->take(3)
            ->pluck('institution')
            ->map(fn ($i) => ['type' => 'institution', 'title' => $i, 'url' => '/institution/' . urlencode($i)]);

        $authors = User::where('name', 'LIKE', "%{$query}%")
            ->whereHas('products', fn ($q) => $q->where('status', 'published'))
            ->take(3)
            ->get(['id', 'name'])
            ->map(fn ($a) => ['type' => 'author', 'title' => $a->name, 'url' => "/author/{$a->id}"]);

        return response()->json(
            $products->concat($institutions)->concat($authors)->values()
        );
    }

    public function institutions(Request $request): JsonResponse
    {
        $query = $request->input('q', '');

        $institutions = Product::published()
            ->whereNotNull('institution')
            ->where('institution', 'LIKE', "%{$query}%")
            ->select('institution')
            ->distinct()
            ->orderBy('institution')
            ->take(20)
            ->pluck('institution');

        return response()->json($institutions);
    }
}
