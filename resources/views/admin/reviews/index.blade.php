@extends('admin.layouts.app')

@section('title', 'Review Moderation')

@section('content')
    <div class="mb-4 flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold">Reviews</h2>
        </div>
        <form class="flex flex-wrap items-end gap-3 rounded-lg border bg-gray-50 p-4" method="GET">
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search reviews…" class="rounded-lg border px-3 py-2 text-sm w-56" />
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500">Date From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="rounded-lg border px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500">Date To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="rounded-lg border px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500">Sort By</label>
                <select name="sort" class="rounded-lg border px-3 py-2 text-sm">
                    <option value="newest" {{ request('sort', 'newest') === 'newest' ? 'selected' : '' }}>Newest First</option>
                    <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Oldest First</option>
                    <option value="reviews_desc" {{ request('sort') === 'reviews_desc' ? 'selected' : '' }}>Most Reviewed Products</option>
                    <option value="reviews_asc" {{ request('sort') === 'reviews_asc' ? 'selected' : '' }}>Least Reviewed Products</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button class="rounded-lg bg-brand-primary px-4 py-2 text-sm text-white">Filter</button>
                <a href="{{ route('admin.reviews.index') }}" class="rounded-lg border px-4 py-2 text-sm text-gray-600 hover:bg-white">Clear</a>
            </div>
        </form>
    </div>

    <div class="rounded-xl border bg-white shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="border-b bg-gray-50 text-left text-xs font-medium uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">User</th>
                    <th class="px-4 py-3">Product</th>
                    <th class="px-4 py-3">Rating</th>
                    <th class="px-4 py-3">Comment</th>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($reviews as $review)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium">{{ $review->user->name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.products.show', $review->product) }}" class="text-brand-light hover:underline">{{ Str::limit($review->product->title ?? '—', 40) }}</a>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-yellow-500">
                                @for ($i = 1; $i <= 5; $i++)
                                    @if ($i <= $review->rating) ★ @else ☆ @endif
                                @endfor
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ Str::limit($review->comment, 80) }}</td>
                        <td class="px-4 py-3 text-gray-400 text-xs">{{ $review->created_at->format('M d, Y') }}</td>
                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('admin.reviews.destroy', $review) }}" onsubmit="return confirm('Delete this review?')">
                                @csrf @method('DELETE')
                                <button class="text-red-600 hover:underline text-xs">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No reviews found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $reviews->withQueryString()->links() }}</div>
@endsection
