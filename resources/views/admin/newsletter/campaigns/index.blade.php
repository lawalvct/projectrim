@extends('admin.layouts.app')

@section('title', 'Campaigns')

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <div class="flex gap-2">
            <a href="{{ route('admin.newsletter.subscribers') }}" class="rounded-lg border px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Subscribers</a>
            <a href="{{ route('admin.campaigns.index') }}" class="rounded-lg bg-brand-primary px-4 py-2 text-sm font-medium text-white">Campaigns</a>
        </div>
        <a href="{{ route('admin.campaigns.create') }}" class="rounded-lg bg-brand-accent px-4 py-2 text-sm font-medium text-white hover:bg-brand-primary">+ New Campaign</a>
    </div>

    <div class="rounded-xl border bg-white shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="border-b bg-gray-50 text-left text-xs font-medium uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">Subject</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Recipients</th>
                    <th class="px-4 py-3">Sent At</th>
                    <th class="px-4 py-3">Created</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($campaigns as $campaign)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium">
                            <a href="{{ route('admin.campaigns.show', $campaign) }}" class="text-brand-light hover:underline">{{ $campaign->subject }}</a>
                        </td>
                        <td class="px-4 py-3">
                            @if ($campaign->sent_at)
                                <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">Sent</span>
                            @else
                                <span class="rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-700">Draft</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $campaign->recipients_count ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-400 text-xs">{{ $campaign->sent_at?->format('M d, Y H:i') ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-400 text-xs">{{ $campaign->created_at->format('M d, Y') }}</td>
                        <td class="px-4 py-3">
                            <div class="flex gap-2">
                                <a href="{{ route('admin.campaigns.edit', $campaign) }}" class="text-brand-light hover:underline text-xs">Edit</a>
                                <form method="POST" action="{{ route('admin.campaigns.destroy', $campaign) }}" onsubmit="return confirm('Delete?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:underline text-xs">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No campaigns yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $campaigns->links() }}</div>
@endsection
