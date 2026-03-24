@extends('admin.layouts.app')

@section('title', 'Review Moderation')

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-lg font-semibold">Reviews</h2>
        <form class="flex gap-2" method="GET">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search reviews…" class="rounded-lg border px-3 py-2 text-sm w-64" />
            <button class="rounded-lg bg-brand-primary px-4 py-2 text-sm text-white">Search</button>
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
