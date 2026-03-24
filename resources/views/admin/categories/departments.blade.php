@extends('admin.layouts.app')

@section('title', 'Departments')

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <div class="flex gap-2">
            <a href="{{ route('admin.faculties.index') }}" class="rounded-lg border px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Faculties</a>
            <a href="{{ route('admin.departments.index') }}" class="rounded-lg bg-brand-primary px-4 py-2 text-sm font-medium text-white">Departments</a>
        </div>
        <a href="{{ route('admin.departments.create') }}" class="rounded-lg bg-brand-accent px-4 py-2 text-sm font-medium text-white hover:bg-brand-primary">+ New Department</a>
    </div>

    <div class="rounded-xl border bg-white shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="border-b bg-gray-50 text-left text-xs font-medium uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Faculty</th>
                    <th class="px-4 py-3">Slug</th>
                    <th class="px-4 py-3">Products</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($departments as $dept)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium">{{ $dept->name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $dept->faculty->name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $dept->slug }}</td>
                        <td class="px-4 py-3">{{ $dept->products_count }}</td>
                        <td class="px-4 py-3">
                            <div class="flex gap-2">
                                <a href="{{ route('admin.departments.edit', $dept) }}" class="text-brand-light hover:underline text-xs">Edit</a>
                                <form method="POST" action="{{ route('admin.departments.destroy', $dept) }}" onsubmit="return confirm('Delete?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:underline text-xs">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">No departments yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $departments->links() }}</div>
@endsection
