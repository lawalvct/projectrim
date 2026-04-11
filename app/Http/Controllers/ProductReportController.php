<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductReportController extends Controller
{
    public function store(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:2000',
        ]);

        $user = $request->user();

        $exists = Report::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'You have already reported this product.'], 422);
        }

        Report::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'reason' => $validated['reason'],
            'product_url' => url("/products/{$product->slug}"),
        ]);

        return response()->json(['message' => 'Report submitted successfully.']);
    }
}
