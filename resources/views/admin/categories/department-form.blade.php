@extends('admin.layouts.app')

@section('title', isset($department) ? 'Edit Department' : 'New Department')

@section('content')
    <div class="mx-auto max-w-lg">
        <div class="rounded-xl border bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold">{{ isset($department) ? 'Edit Department' : 'Create Department' }}</h2>

            <form method="POST" action="{{ isset($department) ? route('admin.departments.update', $department) : route('admin.departments.store') }}">
                @csrf
                @if(isset($department)) @method('PUT') @endif

                <div class="mb-4">
                    <label class="mb-1 block text-sm font-medium">Faculty</label>
                    <select name="faculty_id" class="w-full rounded-lg border px-3 py-2 text-sm" required>
                        <option value="">— Select Faculty —</option>
                        @foreach ($faculties as $fac)
                            <option value="{{ $fac->id }}" @selected(old('faculty_id', $department->faculty_id ?? '') == $fac->id)>{{ $fac->name }}</option>
                        @endforeach
                    </select>
                    @error('faculty_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="mb-4">
                    <label class="mb-1 block text-sm font-medium">Name</label>
                    <input type="text" name="name" value="{{ old('name', $department->name ?? '') }}" class="w-full rounded-lg border px-3 py-2 text-sm" required />
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="mb-4">
                    <label class="mb-1 block text-sm font-medium">Slug</label>
                    <input type="text" name="slug" value="{{ old('slug', $department->slug ?? '') }}" class="w-full rounded-lg border px-3 py-2 text-sm" />
                    @error('slug') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="rounded-lg bg-brand-primary px-5 py-2 text-sm font-medium text-white hover:bg-brand-accent">Save</button>
                    <a href="{{ route('admin.departments.index') }}" class="rounded-lg border px-5 py-2 text-sm text-gray-600 hover:bg-gray-50">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
