<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Download;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserDownloadController extends Controller
{
    public function index(Request $request): Response
    {
        $downloads = Download::with(['product:id,title,slug,price,is_paid,user_id', 'product.faculty:id,name', 'product.images', 'product.user:id,name'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20)
            ->through(fn ($download) => [
                'id' => $download->id,
                'product' => $download->product ? [
                    'id' => $download->product->id,
                    'title' => $download->product->title,
                    'slug' => $download->product->slug,
                    'price' => $download->product->price,
                    'is_paid' => $download->product->is_paid,
                    'faculty' => $download->product->faculty,
                    'author_name' => $download->product->user?->name,
                    'image' => $download->product->images->first()?->path,
                ] : null,
                'created_at' => $download->created_at->format('M d, Y'),
                'created_at_diff' => $download->created_at->diffForHumans(),
            ]);

        return Inertia::render('dashboard/Downloads', [
            'downloads' => $downloads,
        ]);
    }
}
