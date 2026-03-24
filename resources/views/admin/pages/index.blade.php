@extends('admin.layouts.app')

@section('title', 'CMS Pages')

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <p class="text-sm text-gray-500">Manage dynamic site pages (About, Terms, FAQ, etc.)</p>
        <a href="{{ route('admin.pages.create') }}" class="rounded-lg bg-brand-accent px-4 py-2 text-sm font-medium text-white hover:bg-brand-primary">+ New Page</a>
    </div>

    <div class="rounded-xl border bg-white shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="border-b bg-gray-50 text-left text-xs font-medium uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">Title</th>
                    <th class="px-4 py-3">Slug</th>
                    <th class="px-4 py-3">Position</th>
                    <th class="px-4 py-3">Order</th>
                    <th class="px-4 py-3">Published</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($pages as $page)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium">{{ $page->title }}</td>
                        <td class="px-4 py-3 text-gray-500">/pages/{{ $page->slug }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs">{{ ucfirst($page->position) }}</span>
                        </td>
                        <td class="px-4 py-3">{{ $page->sort_order }}</td>
                        <td class="px-4 py-3">
                            @if ($page->is_published)
                                <span class="text-green-600">✓</span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex gap-2">
                                <a href="{{ route('admin.pages.edit', $page) }}" class="text-brand-light hover:underline text-xs">Edit</a>
                                <form method="POST" action="{{ route('admin.pages.destroy', $page) }}" onsubmit="return confirm('Delete this page?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:underline text-xs">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No pages yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $pages->links() }}</div>
@endsection
