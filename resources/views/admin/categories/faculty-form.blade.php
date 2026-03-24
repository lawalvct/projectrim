@extends('admin.layouts.app')

@section('title', $faculty ? 'Edit Faculty' : 'New Faculty')

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.faculties.index') }}" class="text-sm text-brand-light hover:underline">← Back to Faculties</a>
    </div>

    <div class="mx-auto max-w-md rounded-xl border bg-white p-6 shadow-sm">
        <form method="POST" action="{{ $faculty ? route('admin.faculties.update', $faculty) : route('admin.faculties.store') }}">
            @csrf
            @if ($faculty) @method('PUT') @endif

            <div class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium">Name</label>
                    <input type="text" name="name" value="{{ old('name', $faculty->name ?? '') }}" class="w-full rounded-lg border px-3 py-2 text-sm focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent" required>
                    @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Slug</label>
                    <input type="text" name="slug" value="{{ old('slug', $faculty->slug ?? '') }}" placeholder="auto-generated" class="w-full rounded-lg border px-3 py-2 text-sm">
                    @error('slug') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-6">
                <button type="submit" class="rounded-lg bg-brand-accent px-6 py-2 text-sm font-medium text-white hover:bg-brand-primary">
                    {{ $faculty ? 'Update' : 'Create' }}
                </button>
            </div>
        </form>
    </div>
@endsection
