@extends('admin.layouts.app')

@section('title', 'Newsletter Subscribers')

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <div class="flex gap-2">
            <a href="{{ route('admin.newsletter.subscribers') }}" class="rounded-lg bg-brand-primary px-4 py-2 text-sm font-medium text-white">Subscribers</a>
            <a href="{{ route('admin.campaigns.index') }}" class="rounded-lg border px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Campaigns</a>
        </div>
        <span class="text-sm text-gray-500">{{ $subscribers->total() }} total</span>
    </div>

    <div class="rounded-xl border bg-white shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="border-b bg-gray-50 text-left text-xs font-medium uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Subscribed</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($subscribers as $sub)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium">{{ $sub->email }}</td>
                        <td class="px-4 py-3 text-gray-400 text-xs">{{ $sub->created_at->format('M d, Y') }}</td>
                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('admin.newsletter.subscribers.destroy', $sub) }}" onsubmit="return confirm('Remove subscriber?')">
                                @csrf @method('DELETE')
                                <button class="text-red-600 hover:underline text-xs">Remove</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-8 text-center text-gray-400">No subscribers yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $subscribers->links() }}</div>
@endsection
