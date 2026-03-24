<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Revenue;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SellerTransactionController extends Controller
{
    public function index(Request $request): Response
    {
        $userId = $request->user()->id;

        $query = Revenue::with('product:id,title,slug')
            ->where('user_id', $userId);

        if ($request->filled('type') && in_array($request->type, ['sale', 'view', 'download'])) {
            $query->where('type', $request->type);
        }

        $transactions = $query->latest()
            ->paginate(25)
            ->withQueryString()
            ->through(fn ($r) => [
                'id' => $r->id,
                'type' => $r->type,
                'amount_usd' => (float) $r->amount_usd,
                'product' => $r->product ? [
                    'id' => $r->product->id,
                    'title' => $r->product->title,
                    'slug' => $r->product->slug,
                ] : null,
                'created_at' => $r->created_at->format('M d, Y'),
                'created_at_diff' => $r->created_at->diffForHumans(),
            ]);

        // Summary stats
        $summary = [
            'total' => (float) Revenue::where('user_id', $userId)->sum('amount_usd'),
            'sales' => (float) Revenue::where('user_id', $userId)->where('type', 'sale')->sum('amount_usd'),
            'views' => (float) Revenue::where('user_id', $userId)->where('type', 'view')->sum('amount_usd'),
            'downloads' => (float) Revenue::where('user_id', $userId)->where('type', 'download')->sum('amount_usd'),
        ];

        return Inertia::render('dashboard/seller/Transactions', [
            'transactions' => $transactions,
            'summary' => $summary,
            'filters' => [
                'type' => $request->type,
            ],
        ]);
    }
}
