@extends('admin.layouts.app')

@section('title', $product->title)

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.products.index') }}" class="text-sm text-brand-light hover:underline">← Back to Products</a>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            {{-- Product Info --}}
            <div class="rounded-xl border bg-white p-6 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-xl font-bold">{{ $product->title }}</h2>
                        <p class="mt-1 text-sm text-gray-500">by {{ $product->user->name }} · {{ $product->created_at->format('M d, Y') }}</p>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-medium
                        {{ $product->status === 'published' ? 'bg-green-100 text-green-700' : ($product->status === 'pending' ? 'bg-yellow-100 text-yellow-700' : ($product->status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600')) }}">
                        {{ ucfirst($product->status) }}
                    </span>
                </div>

                @if ($product->abstract)
                    <div class="mt-4">
                        <h4 class="text-sm font-semibold text-gray-700">Abstract</h4>
                        <div class="mt-1 text-sm text-gray-600 prose prose-sm max-w-none">{!! $product->abstract !!}</div>
                    </div>
                @endif

                <div class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                    <div><span class="text-gray-500">Faculty:</span> {{ $product->faculty?->name ?? '—' }}</div>
                    <div><span class="text-gray-500">Department:</span> {{ $product->department?->name ?? '—' }}</div>
                    <div><span class="text-gray-500">Type:</span> {{ $product->document_type ?? '—' }}</div>
                    <div><span class="text-gray-500">Institution:</span> {{ $product->institution ?? '—' }}</div>
                    <div><span class="text-gray-500">Country:</span> {{ $product->location_country ?? '—' }}</div>
                    <div><span class="text-gray-500">Class of Degree:</span> {{ $product->class_of_degree ?? '—' }}</div>
                </div>

                @if ($product->tags->count())
                    <div class="mt-4 flex flex-wrap gap-1">
                        @foreach ($product->tags as $tag)
                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">{{ $tag->name }}</span>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Authors --}}
            @if ($product->authors->count())
                <div class="rounded-xl border bg-white p-6 shadow-sm">
                    <h3 class="mb-3 font-semibold">Authors</h3>
                    <div class="divide-y">
                        @foreach ($product->authors as $author)
                            <div class="flex items-center justify-between py-2 text-sm">
                                <div>
                                    <span class="font-medium">{{ $author->user->name }}</span>
                                    <span class="text-gray-500">({{ $author->user->email }})</span>
                                    @if ($author->is_primary) <span class="ml-1 rounded bg-blue-100 px-1.5 py-0.5 text-xs text-blue-700">Primary</span> @endif
                                </div>
                                <span class="text-gray-600">{{ $author->contribution_percentage }}%</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Reviews --}}
            @if ($product->reviews->count())
                <div class="rounded-xl border bg-white p-6 shadow-sm">
                    <h3 class="mb-3 font-semibold">Reviews ({{ $product->reviews_count }})</h3>
                    <div class="divide-y">
                        @foreach ($product->reviews as $review)
                            <div class="py-3">
                                <div class="flex items-center justify-between">
                                    <div class="text-sm"><strong>{{ $review->user->name }}</strong> · <span class="text-yellow-500">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</span></div>
                                    <span class="text-xs text-gray-400">{{ $review->created_at->format('M d, Y') }}</span>
                                </div>
                                @if ($review->comment) <p class="mt-1 text-sm text-gray-600">{{ $review->comment }}</p> @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Buyers --}}
            @if ($buyers->count())
                <div class="rounded-xl border bg-white p-6 shadow-sm">
                    <h3 class="mb-3 font-semibold">Buyers ({{ $buyers->count() }})</h3>
                    <div class="divide-y">
                        @foreach ($buyers as $buyer)
                            <div class="flex items-center justify-between py-2 text-sm">
                                <span class="font-medium">{{ $buyer->name }}</span>
                                <span class="text-gray-500">{{ $buyer->email }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            <div class="rounded-xl border bg-white p-6 shadow-sm text-center">
                <div class="text-3xl font-bold {{ $product->is_paid ? 'text-brand-primary' : 'text-green-600' }}">
                    {{ $product->is_paid ? '$' . number_format($product->price, 2) : 'Free' }}
                </div>
            </div>

            <div class="rounded-xl border bg-white p-6 shadow-sm">
                <h3 class="mb-3 font-semibold text-sm">Statistics</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500">Views</span><span class="font-medium">{{ number_format($product->views_count) }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Downloads</span><span class="font-medium">{{ number_format($product->downloads_count) }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Likes</span><span class="font-medium">{{ number_format($product->likes_count) }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Reviews</span><span class="font-medium">{{ $product->reviews_count }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Orders</span><span class="font-medium">{{ $product->order_items_count }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Revenue</span><span class="font-medium">${{ number_format($totalRevenue, 2) }}</span></div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="rounded-xl border bg-white p-6 shadow-sm space-y-2">
                @if ($product->status === 'pending')
                    <form method="POST" action="{{ route('admin.products.approve', $product) }}">@csrf
                        <button type="submit" class="w-full rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">Approve</button>
                    </form>
                    <form method="POST" action="{{ route('admin.products.reject', $product) }}">@csrf
                        <button type="submit" class="w-full rounded-lg bg-brand-danger px-4 py-2 text-sm font-medium text-white hover:bg-brand-danger-dark">Reject</button>
                    </form>
                @endif
                <a href="{{ route('admin.products.edit', $product) }}" class="block w-full rounded-lg bg-brand-accent px-4 py-2 text-center text-sm font-medium text-white hover:bg-brand-primary">Edit</a>
                <form method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Delete this product?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="w-full rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50">Delete</button>
                </form>
            </div>

            {{-- Files --}}
            @if ($product->files->count())
                <div class="rounded-xl border bg-white p-6 shadow-sm">
                    <h3 class="mb-3 font-semibold text-sm">Files</h3>
                    @foreach ($product->files as $file)
                        <div class="text-sm">
                            <div class="font-medium">{{ $file->file_name }}</div>
                            <div class="text-xs text-gray-500">{{ strtoupper($file->file_type) }} · {{ number_format($file->file_size / 1024, 1) }} KB</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
