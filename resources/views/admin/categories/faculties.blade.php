@extends('admin.layouts.app')

@section('title', 'Faculties')

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <div class="flex gap-2">
            <a href="{{ route('admin.faculties.index') }}" class="rounded-lg bg-brand-primary px-4 py-2 text-sm font-medium text-white">Faculties</a>
            <a href="{{ route('admin.departments.index') }}" class="rounded-lg border px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Departments</a>
        </div>
        <a href="{{ route('admin.faculties.create') }}" class="rounded-lg bg-brand-accent px-4 py-2 text-sm font-medium text-white hover:bg-brand-primary">+ New Faculty</a>
    </div>

    <div class="rounded-xl border bg-white shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="border-b bg-gray-50 text-left text-xs font-medium uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Slug</th>
                    <th class="px-4 py-3">Departments</th>
                    <th class="px-4 py-3">Products</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($faculties as $faculty)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium">{{ $faculty->name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $faculty->slug }}</td>
                        <td class="px-4 py-3">{{ $faculty->departments_count }}</td>
                        <td class="px-4 py-3">{{ $faculty->products_count }}</td>
                        <td class="px-4 py-3">
                            <div class="flex gap-2">
                                <a href="{{ route('admin.faculties.edit', $faculty) }}" class="text-brand-light hover:underline text-xs">Edit</a>
                                <form method="POST" action="{{ route('admin.faculties.destroy', $faculty) }}" onsubmit="return confirm('Delete this faculty?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:underline text-xs">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">No faculties yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $faculties->links() }}</div>
@endsection
