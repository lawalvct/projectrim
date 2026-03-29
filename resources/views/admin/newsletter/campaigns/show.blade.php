@extends('admin.layouts.app')

@section('title', $campaign->subject)

@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="rounded-xl border bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold">{{ $campaign->subject }}</h2>
                @if ($campaign->sent_at)
                    <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700">Sent {{ $campaign->sent_at->format('M d, Y H:i') }}</span>
                @else
                    <span class="rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium text-yellow-700">Draft</span>
                @endif
            </div>

            <div class="mb-6 rounded-lg border bg-gray-50 p-4 text-sm whitespace-pre-wrap">{{ $campaign->body }}</div>

            <div class="mb-4 flex flex-wrap gap-3 text-sm text-gray-500">
                <span>Audience: <strong class="text-gray-700">{{ ucfirst($campaign->audience ?? 'subscribers') }}</strong></span>
                @if ($campaign->recipients_count)
                    <span>&middot; Sent to <strong>{{ $campaign->recipients_count }}</strong> recipients</span>
                @endif
            </div>

            <div class="flex gap-2">
                @unless ($campaign->sent_at)
                    <form method="POST" action="{{ route('admin.campaigns.send', $campaign) }}" onsubmit="return confirm('Send this campaign to all subscribers?')">
                        @csrf
                        <button class="rounded-lg bg-green-600 px-5 py-2 text-sm font-medium text-white hover:bg-green-700">Send Now</button>
                    </form>
                    <a href="{{ route('admin.campaigns.edit', $campaign) }}" class="rounded-lg border px-5 py-2 text-sm text-gray-600 hover:bg-gray-50">Edit</a>
                @endunless
                <a href="{{ route('admin.campaigns.index') }}" class="rounded-lg border px-5 py-2 text-sm text-gray-600 hover:bg-gray-50">Back</a>
            </div>
        </div>
    </div>
@endsection
