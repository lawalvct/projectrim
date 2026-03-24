<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiTagController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q', '');

        $tags = Tag::where('name', 'like', "%{$query}%")
            ->orderBy('name')
            ->take(20)
            ->get(['id', 'name', 'slug']);

        return response()->json($tags);
    }
}
