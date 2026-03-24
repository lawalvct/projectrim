@extends('admin.layouts.app')

@section('title', 'Products')

@section('content')
    <div class="mb-4">
        <form method="GET" class="flex flex-wrap gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search title..." class="rounded-lg border px-3 py-2 text-sm focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent">
            <select name="status" class="rounded-lg border px-3 py-2 text-sm">
                <option value="">All Status</option>
                <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                <option value="published" @selected(request('status') === 'published')>Published</option>
                <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
            </select>
            <select name="is_paid" class="rounded-lg border px-3 py-2 text-sm">
                <option value="">All Types</option>
                <option value="1" @selected(request('is_paid') === '1')>Paid</option>
                <option value="0" @selected(request('is_paid') === '0')>Free</option>
            </select>
            <button type="submit" class="rounded-lg bg-brand-accent px-4 py-2 text-sm font-medium text-white hover:bg-brand-primary">Filter</button>
        </form>
    </div>

    <div class="rounded-xl border bg-white shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="border-b bg-gray-50 text-left text-xs font-medium uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">Title</th>
                    <th class="px-4 py-3">Author</th>
                    <th class="px-4 py-3">Faculty</th>
                    <th class="px-4 py-3">Price</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Views</th>
                    <th class="px-4 py-3">Downloads</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($products as $product)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.products.show', $product) }}" class="font-medium hover:text-brand-primary">{{ Str::limit($product->title, 40) }}</a>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $product->user->name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $product->faculty?->name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $product->is_paid ? '$' . number_format($product->price, 2) : 'Free' }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium
                                {{ $product->status === 'published' ? 'bg-green-100 text-green-700' : ($product->status === 'pending' ? 'bg-yellow-100 text-yellow-700' : ($product->status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600')) }}">
                                {{ ucfirst($product->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">{{ number_format($product->views_count) }}</td>
                        <td class="px-4 py-3">{{ number_format($product->downloads_count) }}</td>
                        <td class="px-4 py-3">
                            <div class="flex gap-2">
                                @if ($product->status === 'pending')
                                    <form method="POST" action="{{ route('admin.products.approve', $product) }}">@csrf <button class="text-green-600 hover:underline text-xs">Approve</button></form>
                                    <form method="POST" action="{{ route('admin.products.reject', $product) }}">@csrf <button class="text-red-600 hover:underline text-xs">Reject</button></form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">No products found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $products->links() }}</div>
@endsection
