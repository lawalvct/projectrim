@extends('admin.layouts.app')

@section('title', 'Seller Applications')

@section('content')
    <div class="rounded-xl border bg-white shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="border-b bg-gray-50 text-left text-xs font-medium uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Company</th>
                    <th class="px-4 py-3">Country</th>
                    <th class="px-4 py-3">Applied</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($applications as $user)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium">{{ $user->name }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $user->email }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $user->sellerProfile?->company ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $user->sellerProfile?->country ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $user->sellerProfile?->created_at?->format('M d, Y') }}</td>
                        <td class="px-4 py-3">
                            <div class="flex gap-2">
                                <a href="{{ route('admin.seller-applications.show', $user) }}" class="text-brand-light hover:underline text-xs">View</a>
                                <form method="POST" action="{{ route('admin.seller-applications.approve', $user) }}">@csrf <button class="text-green-600 hover:underline text-xs">Approve</button></form>
                                <form method="POST" action="{{ route('admin.seller-applications.reject', $user) }}" onsubmit="return confirm('Reject this application?')">@csrf <button class="text-red-600 hover:underline text-xs">Reject</button></form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No pending applications.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $applications->links() }}</div>
@endsection
