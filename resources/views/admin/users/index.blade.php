@extends('admin.layouts.app')

@section('title', 'Users')

@section('content')
    @php($importResult = session('user_import_result'))

    <div class="mb-5 rounded-xl border border-blue-100 bg-blue-50/70 p-4 shadow-sm">
        <details @if ($errors->has('import_file') || $importResult) open @endif>
            <summary class="flex cursor-pointer list-none items-center justify-between gap-4">
                <div>
                    <h2 class="font-semibold text-gray-900">Import users and creators</h2>
                    <p class="mt-0.5 text-sm text-gray-600">Upload the ProjectRim Excel template to create up to 1,000 accounts at once.</p>
                </div>
                <span class="rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-brand-primary shadow-sm">Open importer</span>
            </summary>

            <div class="mt-4 grid gap-4 border-t border-blue-100 pt-4 lg:grid-cols-[1fr_auto] lg:items-end">
                <form method="POST" action="{{ route('admin.users.import') }}" enctype="multipart/form-data" class="grid gap-3 sm:grid-cols-[1fr_auto] sm:items-end">
                    @csrf
                    <div>
                        <label for="import_file" class="mb-1.5 block text-sm font-medium text-gray-700">Excel workbook</label>
                        <input id="import_file" type="file" name="import_file" accept=".xlsx,.xls" required
                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-blue-100 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-brand-primary hover:file:bg-blue-200 focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent">
                        <p class="mt-1 text-xs text-gray-500">Accepted: .xlsx or .xls, maximum 5 MB. Existing emails are skipped.</p>
                        @error('import_file')
                            <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="rounded-lg bg-brand-accent px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-primary">
                        Upload and import
                    </button>
                </form>

                <a href="{{ route('admin.users.import-template') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-brand-accent bg-white px-4 py-2.5 text-sm font-semibold text-brand-primary hover:bg-blue-50">
                    Download Excel template
                </a>
            </div>

            <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                Passwords are not kept in the workbook. Imported account owners should use <strong>Forgot password</strong> to create their password, then verify their email.
            </div>

            <div class="mt-4 grid gap-3 lg:grid-cols-2">
                <section class="rounded-lg border border-rose-200 bg-white p-3" aria-labelledby="required-import-columns">
                    <div class="flex items-center gap-2">
                        <span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs font-bold text-rose-700">Required</span>
                        <h3 id="required-import-columns" class="text-sm font-semibold text-gray-900">Complete these for every row</h3>
                    </div>
                    <dl class="mt-2 grid grid-cols-[auto_1fr] gap-x-3 gap-y-1 text-xs text-gray-700">
                        <dt class="font-mono font-semibold">name</dt><dd>Account owner’s full name.</dd>
                        <dt class="font-mono font-semibold">email</dt><dd>A valid, unique email address.</dd>
                        <dt class="font-mono font-semibold">role</dt><dd>Use only <code class="rounded bg-gray-100 px-1 font-semibold">user</code> or <code class="rounded bg-gray-100 px-1 font-semibold">creator</code>. Admin is not accepted.</dd>
                    </dl>
                </section>

                <section class="rounded-lg border border-blue-200 bg-white p-3" aria-labelledby="optional-import-columns">
                    <div class="flex items-center gap-2">
                        <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-bold text-blue-700">Optional</span>
                        <h3 id="optional-import-columns" class="text-sm font-semibold text-gray-900">Creator profile columns</h3>
                    </div>
                    <p class="mt-2 text-xs leading-5 text-gray-700">
                        <code>company</code>, <code>phone</code>, <code>country</code>, <code>region_state</code> and <code>bio</code>.
                        These fields apply only to creators and are ignored for users. Country must be a system country name or two-letter ISO code, such as <strong>Nigeria</strong> or <strong>NG</strong>.
                    </p>
                </section>
            </div>

            <section class="mt-3 rounded-lg border border-blue-200 bg-blue-50/60 p-3" aria-labelledby="successful-import-steps">
                <h3 id="successful-import-steps" class="text-sm font-semibold text-gray-900">For a successful upload</h3>
                <ol class="mt-2 grid list-decimal gap-x-8 gap-y-1 pl-5 text-xs leading-5 text-gray-700 sm:grid-cols-2">
                    <li>Download a fresh template and review its Examples sheet.</li>
                    <li>Keep all headers in row 1 unchanged and in the same order.</li>
                    <li>Enter one account per row and do not add an admin role.</li>
                    <li>Save as .xlsx or .xls, maximum 1,000 rows and 5 MB.</li>
                    <li>Existing emails are skipped and never overwritten.</li>
                    <li>Review the result and re-upload only corrected failed rows.</li>
                </ol>
            </section>
        </details>
    </div>

    @if ($importResult && !empty($importResult['errors']))
        <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="font-semibold text-amber-900">Some rows need attention</h2>
                    <p class="text-sm text-amber-800">{{ $importResult['failed'] }} row(s) failed. Valid rows were imported.</p>
                </div>
                <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-amber-800">{{ count($importResult['errors']) }} shown</span>
            </div>
            <ul class="mt-3 max-h-48 list-disc space-y-1 overflow-y-auto pl-5 text-sm text-amber-900">
                @foreach ($importResult['errors'] as $importError)
                    <li>{{ $importError }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" class="flex flex-wrap gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or email..." class="rounded-lg border px-3 py-2 text-sm focus:border-brand-accent focus:outline-none focus:ring-1 focus:ring-brand-accent">
            <select name="role" class="rounded-lg border px-3 py-2 text-sm">
                <option value="">All Roles</option>
                <option value="user" @selected(request('role') === 'user')>User</option>
                <option value="seller" @selected(request('role') === 'seller')>Creator</option>
                <option value="admin" @selected(request('role') === 'admin')>Admin</option>
            </select>
            <button type="submit" class="rounded-lg bg-brand-accent px-4 py-2 text-sm font-medium text-white hover:bg-brand-primary">Filter</button>
        </form>
    </div>

    <div class="rounded-xl border bg-white shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="border-b bg-gray-50 text-left text-xs font-medium uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Role</th>
                    <th class="px-4 py-3">Products</th>
                    <th class="px-4 py-3">Orders</th>
                    <th class="px-4 py-3">Joined</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($users as $user)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium">{{ $user->name }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $user->email }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium
                                {{ $user->role === 'admin' ? 'bg-purple-100 text-purple-700' : ($user->role === 'seller' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600') }}">
                                {{ $user->role === 'seller' ? 'Creator' : ucfirst($user->role) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">{{ $user->products_count }}</td>
                        <td class="px-4 py-3">{{ $user->orders_count }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $user->created_at->format('M d, Y') }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <a href="{{ route('admin.users.show', $user) }}" class="text-brand-light hover:underline">View</a>
                                @if ($user->role !== 'admin' && $user->id !== auth()->id())
                                    <form method="POST" action="{{ route('admin.users.impersonate', $user) }}" target="_blank" rel="noopener noreferrer" onsubmit="return confirm('Open this user dashboard in a new tab?')">
                                        @csrf
                                        <button type="submit" class="text-brand-accent hover:underline">Impersonate</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
@endsection
