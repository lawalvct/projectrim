<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Jobs\SendNewProductNotification;
use App\Models\Country;
use App\Models\Faculty;
use App\Models\Product;
use App\Models\ProductAuthor;
use App\Models\ProductFile;
use App\Models\ProductImage;
use App\Models\Setting;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SellerProductController extends Controller
{
    public function index(): Response
    {
        $products = Product::where('user_id', auth()->id())
            ->with(['faculty:id,name', 'images'])
            ->withCount(['downloads', 'reviews'])
            ->latest()
            ->paginate(15);

        return Inertia::render('dashboard/seller/Products', [
            'products' => $products,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('dashboard/seller/ProductForm', [
            'faculties' => Faculty::with('departments:id,faculty_id,name')->orderBy('name')->get(['id', 'name']),
            'countries' => Country::orderBy('name')->get(['id', 'name', 'code']),
            'allowPaidProducts' => Setting::getValue('allow_paid_products', 'true') === 'true',
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        return DB::transaction(function () use ($validated, $request) {
            $slug = Str::slug($validated['title']);
            $originalSlug = $slug;
            $counter = 1;
            while (Product::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter++;
            }

            $isPaid = !empty($validated['price']) && $validated['price'] > 0;

            $product = Product::create([
                'user_id' => auth()->id(),
                'title' => $validated['title'],
                'slug' => $slug,
                'faculty_id' => $validated['faculty_id'] ?? null,
                'department_id' => $validated['department_id'] ?? null,
                'abstract' => $validated['abstract'] ?? null,
                'table_of_content' => $validated['table_of_content'] ?? null,
                'chapter_one' => $validated['chapter_one'] ?? null,
                'meta_description' => $validated['meta_description'] ?? null,
                'meta_keywords' => $validated['meta_keywords'] ?? null,
                'document_type' => $validated['document_type'] ?? null,
                'class_of_degree' => $validated['class_of_degree'] ?? null,
                'institution' => $validated['institution'] ?? null,
                'location_country' => $validated['location_country'] ?? null,
                'location_region' => $validated['location_region'] ?? null,
                'date_available' => $validated['date_available'] ?? null,
                'price' => $validated['price'] ?? 0,
                'is_paid' => $isPaid,
                'status' => $validated['status'] ?? 'draft',
            ]);

            // Handle images
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $image) {
                    $path = $image->store('products/images', 'public');
                    ProductImage::create([
                        'product_id' => $product->id,
                        'path' => $path,
                        'sort_order' => $index,
                    ]);
                }
            }

            // Handle project file
            if ($request->hasFile('project_file')) {
                $file = $request->file('project_file');
                $path = $file->store('products/files', 'public');
                ProductFile::create([
                    'product_id' => $product->id,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'file_type' => $file->getClientOriginalExtension(),
                ]);
            }

            // Handle tags
            if (!empty($validated['tags'])) {
                $tagIds = [];
                foreach ($validated['tags'] as $tagName) {
                    $tag = Tag::firstOrCreate(
                        ['slug' => Str::slug($tagName)],
                        ['name' => $tagName]
                    );
                    $tagIds[] = $tag->id;
                }
                $product->tags()->sync($tagIds);
            }

            // Handle co-authors
            $this->syncCoAuthors($product, $validated['co_authors'] ?? []);

            // Dispatch notification job if requested
            if (!empty($validated['notify_users'])) {
                SendNewProductNotification::dispatch($product);
            }

            return redirect()->route('seller.products.index')
                ->with('status', 'Product created successfully.');
        });
    }

    public function edit(Product $product): Response|RedirectResponse
    {
        if ($product->user_id !== auth()->id()) {
            abort(403);
        }

        $product->load(['images', 'files', 'tags', 'authors.user:id,name,email']);

        return Inertia::render('dashboard/seller/ProductForm', [
            'product' => $product,
            'faculties' => Faculty::with('departments:id,faculty_id,name')->orderBy('name')->get(['id', 'name']),
            'countries' => Country::orderBy('name')->get(['id', 'name', 'code']),
            'allowPaidProducts' => Setting::getValue('allow_paid_products', 'true') === 'true',
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $validated = $request->validated();

        return DB::transaction(function () use ($validated, $request, $product) {
            $isPaid = !empty($validated['price']) && $validated['price'] > 0;

            // Only regenerate slug if title changed
            $slug = $product->slug;
            if ($product->title !== $validated['title']) {
                $slug = Str::slug($validated['title']);
                $originalSlug = $slug;
                $counter = 1;
                while (Product::where('slug', $slug)->where('id', '!=', $product->id)->exists()) {
                    $slug = $originalSlug . '-' . $counter++;
                }
            }

            $product->update([
                'title' => $validated['title'],
                'slug' => $slug,
                'faculty_id' => $validated['faculty_id'] ?? null,
                'department_id' => $validated['department_id'] ?? null,
                'abstract' => $validated['abstract'] ?? null,
                'table_of_content' => $validated['table_of_content'] ?? null,
                'chapter_one' => $validated['chapter_one'] ?? null,
                'meta_description' => $validated['meta_description'] ?? null,
                'meta_keywords' => $validated['meta_keywords'] ?? null,
                'document_type' => $validated['document_type'] ?? null,
                'class_of_degree' => $validated['class_of_degree'] ?? null,
                'institution' => $validated['institution'] ?? null,
                'location_country' => $validated['location_country'] ?? null,
                'location_region' => $validated['location_region'] ?? null,
                'date_available' => $validated['date_available'] ?? null,
                'price' => $validated['price'] ?? 0,
                'is_paid' => $isPaid,
                'status' => $validated['status'] ?? $product->status,
            ]);

            // Remove specified images
            if (!empty($validated['remove_images'])) {
                $images = ProductImage::whereIn('id', $validated['remove_images'])
                    ->where('product_id', $product->id)
                    ->get();
                foreach ($images as $image) {
                    Storage::disk('public')->delete($image->path);
                    $image->delete();
                }
            }

            // Handle new images
            if ($request->hasFile('images')) {
                $maxSort = $product->images()->max('sort_order') ?? -1;
                foreach ($request->file('images') as $index => $image) {
                    $path = $image->store('products/images', 'public');
                    ProductImage::create([
                        'product_id' => $product->id,
                        'path' => $path,
                        'sort_order' => $maxSort + $index + 1,
                    ]);
                }
            }

            // Handle project file replacement
            if ($request->hasFile('project_file')) {
                // Remove old files
                foreach ($product->files as $oldFile) {
                    Storage::disk('public')->delete($oldFile->file_path);
                    $oldFile->delete();
                }

                $file = $request->file('project_file');
                $path = $file->store('products/files', 'public');
                ProductFile::create([
                    'product_id' => $product->id,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'file_type' => $file->getClientOriginalExtension(),
                ]);
            }

            // Handle tags
            if (isset($validated['tags'])) {
                $tagIds = [];
                foreach ($validated['tags'] as $tagName) {
                    $tag = Tag::firstOrCreate(
                        ['slug' => Str::slug($tagName)],
                        ['name' => $tagName]
                    );
                    $tagIds[] = $tag->id;
                }
                $product->tags()->sync($tagIds);
            }

            // Handle co-authors
            if (isset($validated['co_authors'])) {
                $this->syncCoAuthors($product, $validated['co_authors']);
            }

            return redirect()->route('seller.products.index')
                ->with('status', 'Product updated successfully.');
        });
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->user_id !== auth()->id()) {
            abort(403);
        }

        // Delete associated files from storage
        foreach ($product->images as $image) {
            Storage::disk('public')->delete($image->path);
        }
        foreach ($product->files as $file) {
            Storage::disk('public')->delete($file->file_path);
        }

        $product->delete();

        return redirect()->route('seller.products.index')
            ->with('status', 'Product deleted successfully.');
    }

    private function syncCoAuthors(Product $product, array $coAuthors): void
    {
        // Remove existing authors
        $product->authors()->delete();

        // Calculate primary author's percentage
        $totalCoAuthorPercentage = collect($coAuthors)->sum('contribution_percentage');
        $primaryPercentage = max(1, 100 - $totalCoAuthorPercentage);

        // Create primary author entry
        ProductAuthor::create([
            'product_id' => $product->id,
            'user_id' => $product->user_id,
            'is_primary' => true,
            'contribution_percentage' => $primaryPercentage,
        ]);

        // Create co-author entries
        foreach ($coAuthors as $coAuthor) {
            ProductAuthor::create([
                'product_id' => $product->id,
                'user_id' => $coAuthor['user_id'],
                'is_primary' => false,
                'contribution_percentage' => $coAuthor['contribution_percentage'],
            ]);
        }
    }
}
