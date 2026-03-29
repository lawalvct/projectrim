@extends('admin.layouts.app')

@section('title', 'Edit Product')

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.products.show', $product) }}" class="text-sm text-brand-light hover:underline">← Back to Product</a>
    </div>

    <div class="mx-auto max-w-xl rounded-xl border bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('admin.products.update', $product) }}">
            @csrf @method('PUT')

            <div class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium">Title</label>
                    <input type="text" name="title" value="{{ old('title', $product->title) }}" class="w-full rounded-lg border px-3 py-2 text-sm focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent" required>
                    @error('title') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Price ($)</label>
                    <input type="number" name="price" value="{{ old('price', $product->price) }}" step="0.01" min="0" class="w-full rounded-lg border px-3 py-2 text-sm focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent" required>
                    @error('price') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="hidden" name="is_paid" value="0">
                        <input type="checkbox" name="is_paid" value="1" @checked(old('is_paid', $product->is_paid)) class="rounded">
                        Paid product
                    </label>
                </div>

                <div>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="hidden" name="is_featured" value="0">
                        <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $product->is_featured)) class="rounded">
                        Featured on homepage
                    </label>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Status</label>
                    <select name="status" class="w-full rounded-lg border px-3 py-2 text-sm">
                        <option value="draft" @selected(old('status', $product->status) === 'draft')>Draft</option>
                        <option value="pending" @selected(old('status', $product->status) === 'pending')>Pending</option>
                        <option value="published" @selected(old('status', $product->status) === 'published')>Published</option>
                        <option value="rejected" @selected(old('status', $product->status) === 'rejected')>Rejected</option>
                    </select>
                    @error('status') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-6">
                <button type="submit" class="rounded-lg bg-brand-accent px-6 py-2 text-sm font-medium text-white hover:bg-brand-primary">Save Changes</button>
            </div>
        </form>
    </div>
@endsection
