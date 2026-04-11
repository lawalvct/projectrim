@extends('admin.layouts.app')

@section('title', 'Message Detail')

@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="rounded-xl border bg-white p-6 shadow-sm">
            <div class="mb-4">
                <h2 class="text-lg font-semibold">{{ $message->subject ?? 'Message' }}</h2>
                <p class="text-xs text-gray-400">{{ $message->created_at->format('M d, Y H:i') }}</p>
            </div>

            @if ($message->product)
                <div class="mb-4 rounded-lg border bg-gray-50 p-3">
                    <span class="text-xs text-gray-500">Product:</span>
                    <a href="{{ route('admin.products.show', $message->product) }}" class="ml-1 text-sm text-brand-light hover:underline">{{ $message->product->title }}</a>
                </div>
            @endif

            <div class="mb-4 grid gap-3 sm:grid-cols-2">
                <div class="rounded-lg border bg-gray-50 p-3">
                    <p class="text-xs text-gray-500">Sender Name</p>
                    <p class="mt-1 text-sm font-medium text-gray-900">{{ $message->sender_name ?: '—' }}</p>
                </div>
                <div class="rounded-lg border bg-gray-50 p-3">
                    <p class="text-xs text-gray-500">Sender Email</p>
                    <p class="mt-1 text-sm font-medium text-gray-900">{{ $message->sender_email ?: '—' }}</p>
                </div>
            </div>

            <div class="mb-6 rounded-lg border bg-gray-50 p-4 text-sm whitespace-pre-wrap">{{ $message->body }}</div>

            @if ($message->recipients->count())
                <h3 class="mb-2 text-sm font-medium text-gray-600">Recipients ({{ $message->recipients->count() }})</h3>
                <div class="rounded-lg border overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-gray-50 text-left text-xs font-medium uppercase text-gray-500">
                            <tr>
                                <th class="px-4 py-2">User</th>
                                <th class="px-4 py-2">Email</th>
                                <th class="px-4 py-2">Read</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($message->recipients as $recipient)
                                <tr>
                                    <td class="px-4 py-2">{{ $recipient->user->name ?? '—' }}</td>
                                    <td class="px-4 py-2">{{ $recipient->user->email ?? '—' }}</td>
                                    <td class="px-4 py-2">
                                        @if ($recipient->read_at)
                                            <span class="text-green-600 text-xs">{{ $recipient->read_at->format('M d, Y') }}</span>
                                        @else
                                            <span class="text-gray-400 text-xs">Unread</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="mt-4">
                <a href="{{ route('admin.messages.index') }}" class="rounded-lg border px-5 py-2 text-sm text-gray-600 hover:bg-gray-50">Back</a>
            </div>
        </div>
    </div>
@endsection
