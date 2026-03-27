@extends('admin.layouts.app')

@section('title', isset($page) ? 'Edit Page' : 'Create Page')

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.pages.index') }}" class="text-sm text-brand-light hover:underline">← Back to Pages</a>
    </div>

    <div class="mx-auto max-w-2xl rounded-xl border bg-white p-6 shadow-sm">
        <form method="POST" action="{{ isset($page) ? route('admin.pages.update', $page) : route('admin.pages.store') }}">
            @csrf
            @if (isset($page)) @method('PUT') @endif

            <div class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium">Title</label>
                    <input type="text" name="title" value="{{ old('title', $page->title ?? '') }}" class="w-full rounded-lg border px-3 py-2 text-sm focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent" required>
                    @error('title') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Slug</label>
                    <input type="text" name="slug" value="{{ old('slug', $page->slug ?? '') }}" placeholder="auto-generated from title" class="w-full rounded-lg border px-3 py-2 text-sm focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent">
                    @error('slug') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Body</label>
                    <textarea id="body-editor" name="body" rows="12" class="w-full rounded-lg border px-3 py-2 text-sm" required>{{ old('body', $page->body ?? '') }}</textarea>
                    @error('body') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium">Position</label>
                        <select name="position" class="w-full rounded-lg border px-3 py-2 text-sm">
                            <option value="none" @selected(old('position', $page->position ?? '') === 'none')>None</option>
                            <option value="nav" @selected(old('position', $page->position ?? '') === 'nav')>Nav</option>
                            <option value="footer" @selected(old('position', $page->position ?? '') === 'footer')>Footer</option>
                            <option value="both" @selected(old('position', $page->position ?? '') === 'both')>Both</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Sort Order</label>
                        <input type="number" name="sort_order" value="{{ old('sort_order', $page->sort_order ?? 0) }}" min="0" class="w-full rounded-lg border px-3 py-2 text-sm">
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="hidden" name="is_published" value="0">
                            <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $page->is_published ?? true)) class="rounded">
                            Published
                        </label>
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Meta Description</label>
                    <input type="text" name="meta_description" value="{{ old('meta_description', $page->meta_description ?? '') }}" class="w-full rounded-lg border px-3 py-2 text-sm" maxlength="300">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Meta Keywords</label>
                    <input type="text" name="meta_keywords" value="{{ old('meta_keywords', $page->meta_keywords ?? '') }}" class="w-full rounded-lg border px-3 py-2 text-sm" maxlength="255">
                </div>
            </div>

            <div class="mt-6">
                <button type="submit" class="rounded-lg bg-brand-accent px-6 py-2 text-sm font-medium text-white hover:bg-brand-primary">
                    {{ isset($page) ? 'Update Page' : 'Create Page' }}
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    tinymce.init({
        selector: '#body-editor',
        height: 400,
        menubar: false,
        plugins: 'lists link image table code fullscreen preview wordcount',
        toolbar: 'undo redo | blocks | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image table | code fullscreen',
        content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 14px; }',
        branding: false,
        promotion: false,
    });
</script>
@endpush
