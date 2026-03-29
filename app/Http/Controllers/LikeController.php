<?php

namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    public function toggle(Request $request, Product $product): JsonResponse
    {
        $user = $request->user();

        $existing = Like::where('product_id', $product->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $product->decrement('likes_count');
            $liked = false;
        } else {
            Like::create([
                'product_id' => $product->id,
                'user_id' => $user->id,
            ]);
            $product->increment('likes_count');
            $liked = true;
        }

        return response()->json([
            'liked' => $liked,
            'likes_count' => Like::where('product_id', $product->id)->count(),
        ]);
    }
}
