<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Faculty;
use App\Models\Product;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BrowseController extends Controller
{
    public function faculty(string $slug, Request $request): Response
    {
        $faculty = Faculty::where('slug', $slug)->firstOrFail();

        $products = Product::published()
            ->where('faculty_id', $faculty->id)
            ->with(['user:id,name', 'faculty:id,name,slug', 'images'])
            ->latest('published_at')
            ->paginate(20)
            ->withQueryString();

        $departments = Department::where('faculty_id', $faculty->id)
            ->withCount(['products' => fn ($q) => $q->where('status', 'published')])
            ->having('products_count', '>', 0)
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'faculty_id']);

        return Inertia::render('browse/Faculty', [
            'faculty' => $faculty,
            'products' => $products,
            'departments' => $departments,
        ]);
    }

    public function department(string $slug, Request $request): Response
    {
        $department = Department::where('slug', $slug)->with('faculty:id,name,slug')->firstOrFail();

        $products = Product::published()
            ->where('department_id', $department->id)
            ->with(['user:id,name', 'faculty:id,name,slug', 'images'])
            ->latest('published_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('browse/Department', [
            'department' => $department,
            'products' => $products,
        ]);
    }

    public function institution(string $name, Request $request): Response
    {
        $decodedName = urldecode($name);

        $products = Product::published()
            ->where('institution', $decodedName)
            ->with(['user:id,name', 'faculty:id,name,slug', 'images'])
            ->latest('published_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('browse/Institution', [
            'institution' => $decodedName,
            'products' => $products,
        ]);
    }

    public function author(int $id, Request $request): Response
    {
        $author = User::findOrFail($id);

        $products = Product::published()
            ->where(function ($q) use ($id) {
                $q->where('user_id', $id)
                    ->orWhereHas('authors', fn ($a) => $a->where('user_id', $id));
            })
            ->with(['user:id,name', 'faculty:id,name,slug', 'images'])
            ->latest('published_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('browse/Author', [
            'author' => [
                'id' => $author->id,
                'name' => $author->name,
                'email' => $author->email,
                'avatar' => $author->avatar,
            ],
            'products' => $products,
        ]);
    }

    public function country(string $code, Request $request): Response
    {
        $products = Product::published()
            ->where('location_country', $code)
            ->with(['user:id,name', 'faculty:id,name,slug', 'images'])
            ->latest('published_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('browse/Country', [
            'country' => $code,
            'products' => $products,
        ]);
    }

    public function tag(string $slug, Request $request): Response
    {
        $tag = Tag::where('slug', $slug)->firstOrFail();

        $products = Product::published()
            ->whereHas('tags', fn ($q) => $q->where('tags.id', $tag->id))
            ->with(['user:id,name', 'faculty:id,name,slug', 'images'])
            ->latest('published_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('browse/Tag', [
            'tag' => $tag,
            'products' => $products,
        ]);
    }
}
