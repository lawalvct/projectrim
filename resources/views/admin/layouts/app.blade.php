<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — ProjectRim</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            primary: '#0a4b76',
                            light: '#337ab7',
                            accent: '#1f90bb',
                            danger: '#da3539',
                            'danger-dark': '#a94442',
                        },
                    },
                },
            },
        };
    </script>
    <style>
        [x-cloak] { display: none !important; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #337ab7; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #0a4b76; }
        * { scrollbar-width: thin; scrollbar-color: #337ab7 transparent; }
    </style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @stack('styles')
</head>
<body class="min-h-screen bg-gray-50">
    <div x-data="{ sidebarOpen: true, mobileSidebar: false }" class="flex min-h-screen">
        {{-- Sidebar --}}
        <aside
            :class="sidebarOpen ? 'w-64' : 'w-20'"
            class="fixed inset-y-0 left-0 z-30 hidden lg:flex flex-col bg-brand-primary text-white transition-all duration-300"
        >
            {{-- Logo --}}
            <div class="flex h-16 items-center justify-between px-4 border-b border-white/10">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
                    <img src="/images/icon.png" alt="ProjectRim" class="h-8 w-8">
                    <span x-show="sidebarOpen" x-cloak class="text-lg font-bold">ProjectRim</span>
                </a>
                <button @click="sidebarOpen = !sidebarOpen" class="text-white/70 hover:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
                @php
                    $nav = [
                        ['route' => 'admin.dashboard', 'label' => 'Dashboard', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1'],
                        ['route' => 'admin.users.index', 'label' => 'Users', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
                        ['route' => 'admin.products.index', 'label' => 'Products', 'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
                        ['route' => 'admin.orders.index', 'label' => 'Orders', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
                        ['route' => 'admin.seller-applications.index', 'label' => 'Seller Applications', 'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
                        ['route' => 'admin.payouts.index', 'label' => 'Payouts', 'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],
                        ['route' => 'admin.pages.index', 'label' => 'CMS Pages', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                        ['route' => 'admin.faculties.index', 'label' => 'Categories', 'icon' => 'M7 7h.01M7 3h5c.512 0 1.023.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z'],
                        ['route' => 'admin.reviews.index', 'label' => 'Reviews', 'icon' => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z'],
                        ['route' => 'admin.newsletter.subscribers', 'label' => 'Newsletter', 'icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
                        ['route' => 'admin.messages.index', 'label' => 'Messages', 'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'],
                        ['route' => 'admin.settings.general', 'label' => 'Settings', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'],
                        ['route' => 'admin.analytics.products', 'label' => 'Analytics', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                    ];
                @endphp

                @foreach ($nav as $item)
                    @php $active = request()->routeIs($item['route'] . '*') || request()->routeIs(str_replace('.index', '', $item['route']) . '.*'); @endphp
                    <a
                        href="{{ route($item['route']) }}"
                        class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition-colors {{ $active ? 'bg-white/15 text-white font-medium' : 'text-white/70 hover:bg-white/10 hover:text-white' }}"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/></svg>
                        <span x-show="sidebarOpen" x-cloak>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>

            {{-- User --}}
            <div class="border-t border-white/10 p-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-white/20 text-sm font-bold">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div x-show="sidebarOpen" x-cloak class="min-w-0">
                        <div class="truncate text-sm font-medium">{{ auth()->user()->name }}</div>
                        <div class="truncate text-xs text-white/60">Admin</div>
                    </div>
                </div>
            </div>
        </aside>

        {{-- Mobile sidebar overlay --}}
        <div x-show="mobileSidebar" x-cloak @click="mobileSidebar = false" class="fixed inset-0 z-40 bg-black/50 lg:hidden"></div>
        <aside
            x-show="mobileSidebar" x-cloak
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
            class="fixed inset-y-0 left-0 z-50 w-64 flex flex-col bg-brand-primary text-white lg:hidden"
        >
            <div class="flex h-16 items-center justify-between px-4 border-b border-white/10">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
                    <img src="/images/icon.png" alt="ProjectRim" class="h-8 w-8">
                    <span class="text-lg font-bold">ProjectRim</span>
                </a>
                <button @click="mobileSidebar = false" class="text-white/70 hover:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
                @foreach ($nav as $item)
                    @php $active = request()->routeIs($item['route'] . '*') || request()->routeIs(str_replace('.index', '', $item['route']) . '.*'); @endphp
                    <a
                        href="{{ route($item['route']) }}"
                        @click="mobileSidebar = false"
                        class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition-colors {{ $active ? 'bg-white/15 text-white font-medium' : 'text-white/70 hover:bg-white/10 hover:text-white' }}"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/></svg>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>
        </aside>

        {{-- Main content --}}
        <div :class="sidebarOpen ? 'lg:pl-64' : 'lg:pl-20'" class="flex flex-1 flex-col transition-all duration-300">
            {{-- Topbar --}}
            <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b bg-white px-4 shadow-sm lg:px-6">
                <div class="flex items-center gap-3">
                    <button @click="mobileSidebar = true" class="lg:hidden text-gray-500 hover:text-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <h1 class="text-lg font-semibold text-gray-800">@yield('title', 'Dashboard')</h1>
                </div>
                <div class="flex items-center gap-4">
                    <a href="{{ url('/') }}" target="_blank" class="text-sm text-gray-500 hover:text-brand-primary">View Site →</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm text-gray-500 hover:text-red-600">Logout</button>
                    </form>
                </div>
            </header>

            {{-- Flash messages --}}
            @if (session('success'))
                <div class="mx-4 mt-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700 lg:mx-6">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mx-4 mt-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700 lg:mx-6">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Page content --}}
            <main class="flex-1 p-4 lg:p-6">
                @yield('content')
            </main>

            {{-- Footer --}}
            <footer class="border-t bg-white px-4 py-3 text-center text-xs text-gray-400 lg:px-6">
                ProjectRim Admin &copy; {{ date('Y') }}
            </footer>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
