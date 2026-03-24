<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function show(string $slug): Response
    {
        $page = Page::published()->where('slug', $slug)->firstOrFail();

        return Inertia::render('CmsPage', [
            'page' => $page->only('id', 'title', 'slug', 'body', 'meta_description', 'meta_keywords'),
        ]);
    }
}
