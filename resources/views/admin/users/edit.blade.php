@extends('admin.layouts.app')

@section('title', 'Edit ' . $user->name)

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.users.show', $user) }}" class="text-sm text-brand-light hover:underline">← Back to User</a>
    </div>

    <div class="mx-auto max-w-xl rounded-xl border bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('admin.users.update', $user) }}">
            @csrf @method('PUT')

            <div class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium">Name</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" class="w-full rounded-lg border px-3 py-2 text-sm focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent" required>
                    @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" class="w-full rounded-lg border px-3 py-2 text-sm focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent" required>
                    @error('email') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Role</label>
                    <select name="role" class="w-full rounded-lg border px-3 py-2 text-sm">
                        <option value="user" @selected(old('role', $user->role) === 'user')>User</option>
                        <option value="seller" @selected(old('role', $user->role) === 'seller')>Seller</option>
                        <option value="admin" @selected(old('role', $user->role) === 'admin')>Admin</option>
                    </select>
                    @error('role') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-6">
                <button type="submit" class="rounded-lg bg-brand-accent px-6 py-2 text-sm font-medium text-white hover:bg-brand-primary">Save Changes</button>
            </div>
        </form>
    </div>
@endsection
