@extends('admin.layouts.app')

@section('title', 'Messages')

@section('content')
    <div class="mb-4">
        <h2 class="text-lg font-semibold">Product Messages</h2>
    </div>

    <div class="rounded-xl border bg-white shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="border-b bg-gray-50 text-left text-xs font-medium uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">Subject</th>
                    <th class="px-4 py-3">Product</th>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($messages as $msg)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium">{{ Str::limit($msg->subject ?? $msg->body, 60) }}</td>
                        <td class="px-4 py-3">
                            @if ($msg->product)
                                <a href="{{ route('admin.products.show', $msg->product) }}" class="text-brand-light hover:underline">{{ Str::limit($msg->product->title, 40) }}</a>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-400 text-xs">{{ $msg->created_at->format('M d, Y H:i') }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.messages.show', $msg) }}" class="text-brand-light hover:underline text-xs">View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">No messages yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $messages->links() }}</div>
@endsection
