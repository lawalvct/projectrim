@extends('admin.layouts.app')

@section('title', 'Edit Campaign')

@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="rounded-xl border bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold">Edit Campaign</h2>

            <form method="POST" action="{{ route('admin.campaigns.update', $campaign) }}">
                @csrf @method('PUT')

                <div class="mb-4">
                    <label class="mb-1 block text-sm font-medium">Subject</label>
                    <input type="text" name="subject" value="{{ old('subject', $campaign->subject) }}" class="w-full rounded-lg border px-3 py-2 text-sm" required />
                    @error('subject') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="mb-4">
                    <label class="mb-1 block text-sm font-medium">Body</label>
                    <textarea name="body" rows="10" class="w-full rounded-lg border px-3 py-2 text-sm" required>{{ old('body', $campaign->body) }}</textarea>
                    @error('body') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="rounded-lg bg-brand-primary px-5 py-2 text-sm font-medium text-white hover:bg-brand-accent">Update</button>
                    <a href="{{ route('admin.campaigns.show', $campaign) }}" class="rounded-lg border px-5 py-2 text-sm text-gray-600 hover:bg-gray-50">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
