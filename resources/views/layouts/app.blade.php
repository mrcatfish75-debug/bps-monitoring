<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BPS Monitoring</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Vue + Lucide Icons CDN -->
    <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        [v-cloak] {
            display: none;
        }

        .fade-enter-active,
        .fade-leave-active {
            transition: opacity 180ms ease;
        }

        .fade-enter-from,
        .fade-leave-to {
            opacity: 0;
        }

        .drawer-enter-active,
        .drawer-leave-active {
            transition: transform 220ms ease, opacity 220ms ease;
        }

        .drawer-enter-from,
        .drawer-leave-to {
            transform: translateX(-100%);
            opacity: 0;
        }
    </style>
</head>

<body class="font-sans antialiased bg-gray-100">

@php
    $user = auth()->user();
    $role = $user?->role;

    $roleLabel = match ($role) {
        'admin' => 'Admin',
        'ketua' => 'Ketua Tim',
        'anggota' => 'Anggota',
        'kepala' => 'Kepala',
        default => 'User',
    };

    $notificationCount = \App\Models\Notification::where('user_id', auth()->id())
        ->where('is_read', false)
        ->count();

    /*
    |--------------------------------------------------------------------------
    | Active Helper
    |--------------------------------------------------------------------------
    */
    $isActive = function ($path, $mode = null, $exact = false) {
        $path = trim($path, '/');
        $currentPath = request()->path();

        if ($mode !== null) {
            return $currentPath === $path && request('mode') === $mode;
        }

        if (request('mode') === 'mine') {
            return false;
        }

        if ($exact) {
            return $currentPath === $path;
        }

        return $currentPath === $path || str_starts_with($currentPath, $path . '/');
    };

    /*
    |--------------------------------------------------------------------------
    | Page Title
    |--------------------------------------------------------------------------
    */
    $pageTitle = match (true) {
        request()->is('admin') => 'Dashboard Admin',
        request()->is('admin/users*') => 'Kelola Users',
        request()->is('admin/team*') => 'Kelola Team',
        request()->is('admin/iku*') => 'Kelola IKU',
        request()->is('admin/rk-ketua*') => 'RK Ketua',
        request()->is('admin/project*') => 'Project',
        request()->is('admin/rk-anggota*') => 'RK Anggota',
        request()->is('admin/daily-task*') => 'Daily Task',

        request()->is('ketua') => 'Dashboard Ketua Tim',
        request()->is('ketua/rk-ketua*') => 'RK Ketua',
        request()->is('ketua/project*') => 'Project Tim',
        request()->is('ketua/rk-anggota*') && request('mode') === 'mine' => 'RK Pribadi Saya',
        request()->is('ketua/rk-anggota*') => 'Review RK Anggota',
        request()->is('ketua/daily-task*') && request('mode') === 'mine' => 'Daily Task Saya',
        request()->is('ketua/daily-task*') => 'Monitoring Daily Task',

        request()->is('anggota') => 'Dashboard Anggota',
        request()->is('anggota/project*') => 'Project Saya',
        request()->is('anggota/rk-anggota*') => 'RK Pribadi Saya',
        request()->is('anggota/daily-task*') => 'Daily Task Saya',

        request()->is('kepala') => 'Dashboard Kepala',
        request()->is('notification*') => 'Notifications',

        default => 'BPS Monitoring',
    };

    /*
    |--------------------------------------------------------------------------
    | Menu Groups
    |--------------------------------------------------------------------------
    */
    $menuGroups = [];

    if ($role === 'admin') {
        $menuGroups = [
            [
                'label' => 'Utama',
                'items' => [
                    [
                        'label' => 'Dashboard',
                        'url' => url('/admin'),
                        'path' => '/admin',
                        'icon' => 'layout-dashboard',
                        'exact' => true,
                    ],
                ],
            ],
            [
                'label' => 'Pengguna',
                'items' => [
                    [
                        'label' => 'Users',
                        'url' => url('/admin/users'),
                        'path' => '/admin/users',
                        'icon' => 'users',
                    ],
                    [
                        'label' => 'Team',
                        'url' => url('/admin/team'),
                        'path' => '/admin/team',
                        'icon' => 'building-2',
                    ],
                ],
            ],
            [
                'label' => 'Kelola Sistem',
                'items' => [
                    [
                        'label' => 'IKU',
                        'url' => url('/admin/iku'),
                        'path' => '/admin/iku',
                        'icon' => 'bar-chart-3',
                    ],
                    [
                        'label' => 'RK Ketua',
                        'url' => url('/admin/rk-ketua'),
                        'path' => '/admin/rk-ketua',
                        'icon' => 'target',
                    ],
                    [
                        'label' => 'Project',
                        'url' => url('/admin/project'),
                        'path' => '/admin/project',
                        'icon' => 'folder-kanban',
                    ],
                    [
                        'label' => 'RK Anggota',
                        'url' => url('/admin/rk-anggota'),
                        'path' => '/admin/rk-anggota',
                        'icon' => 'clipboard-check',
                    ],
                    [
                        'label' => 'Daily Task',
                        'url' => url('/admin/daily-task'),
                        'path' => '/admin/daily-task',
                        'icon' => 'list-checks',
                    ],
                ],
            ],
        ];
    }

    if ($role === 'ketua') {
        $menuGroups = [
            [
                'label' => 'Utama',
                'items' => [
                    [
                        'label' => 'Dashboard',
                        'url' => url('/ketua'),
                        'path' => '/ketua',
                        'icon' => 'layout-dashboard',
                        'exact' => true,
                    ],
                ],
            ],
            [
                'label' => 'Mode Ketua Tim',
                'items' => [
                    [
                        'label' => 'RK Ketua',
                        'url' => url('/ketua/rk-ketua'),
                        'path' => '/ketua/rk-ketua',
                        'icon' => 'target',
                    ],
                    [
                        'label' => 'Project Tim',
                        'url' => url('/ketua/project'),
                        'path' => '/ketua/project',
                        'icon' => 'folder-kanban',
                    ],
                    [
                        'label' => 'Review RK Anggota',
                        'url' => url('/ketua/rk-anggota'),
                        'path' => '/ketua/rk-anggota',
                        'icon' => 'clipboard-check',
                        'custom_active' => request()->is('ketua/rk-anggota*') && request('mode') !== 'mine',
                    ],
                    [
                        'label' => 'Monitoring Daily Task',
                        'url' => url('/ketua/daily-task'),
                        'path' => '/ketua/daily-task',
                        'icon' => 'list-checks',
                        'custom_active' => request()->is('ketua/daily-task*') && request('mode') !== 'mine',
                    ],
                ],
            ],
            [
                'label' => 'Pekerjaan Saya',
                'items' => [
                    [
                        'label' => 'RK Pribadi Saya',
                        'url' => url('/ketua/rk-anggota?mode=mine'),
                        'path' => '/ketua/rk-anggota',
                        'mode' => 'mine',
                        'icon' => 'user-check',
                    ],
                    [
                        'label' => 'Daily Task Saya',
                        'url' => url('/ketua/daily-task?mode=mine'),
                        'path' => '/ketua/daily-task',
                        'mode' => 'mine',
                        'icon' => 'notebook-pen',
                    ],
                ],
            ],
        ];
    }

    if ($role === 'anggota') {
        $menuGroups = [
            [
                'label' => 'Utama',
                'items' => [
                    [
                        'label' => 'Dashboard',
                        'url' => url('/anggota'),
                        'path' => '/anggota',
                        'icon' => 'layout-dashboard',
                        'exact' => true,
                    ],
                ],
            ],
            [
                'label' => 'Pekerjaan Saya',
                'items' => [
                    [
                        'label' => 'Project Saya',
                        'url' => url('/anggota/project'),
                        'path' => '/anggota/project',
                        'icon' => 'folder-kanban',
                    ],
                    [
                        'label' => 'RK Pribadi Saya',
                        'url' => url('/anggota/rk-anggota'),
                        'path' => '/anggota/rk-anggota',
                        'icon' => 'file-pen-line',
                    ],
                    [
                        'label' => 'Daily Task Saya',
                        'url' => url('/anggota/daily-task'),
                        'path' => '/anggota/daily-task',
                        'icon' => 'list-checks',
                    ],
                ],
            ],
        ];
    }

    if ($role === 'kepala') {
        $menuGroups = [
            [
                'label' => 'Monitoring',
                'items' => [
                    [
                        'label' => 'Dashboard',
                        'url' => url('/kepala'),
                        'path' => '/kepala',
                        'icon' => 'layout-dashboard',
                        'exact' => true,
                    ],
                    [
                        'label' => 'Ringkasan Kinerja',
                        'url' => url('/kepala'),
                        'path' => '/kepala',
                        'icon' => 'line-chart',
                        'exact' => true,
                    ],
                ],
            ],
        ];
    }

    $notificationActive = request()->is('notification') || request()->is('notification/*');

    $menuItemClass = function ($active) {
        return $active
            ? 'flex items-center gap-3 px-3 py-3 rounded-2xl bg-blue-600 text-white font-semibold shadow-lg shadow-blue-950/30'
            : 'flex items-center gap-3 px-3 py-3 rounded-2xl text-slate-300 hover:bg-slate-900 hover:text-white transition';
    };
@endphp

<div id="app-shell" v-cloak class="min-h-screen bg-gray-100">

    <!-- ================= TOPBAR ================= -->
    <header class="sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-gray-200 shadow-sm">
        <div class="h-16 px-4 lg:px-6 flex items-center justify-between gap-4">

            <div class="flex items-center gap-3 min-w-0">
                <button type="button"
                    v-on:click="toggleSidebar"
                    class="w-11 h-11 rounded-2xl bg-slate-950 text-white flex items-center justify-center hover:bg-slate-800 transition shadow-sm shrink-0"
                    aria-label="Toggle menu">
                    <i data-lucide="menu" class="w-5 h-5"></i>
                </button>

                <div class="min-w-0">
                    <h2 class="font-bold text-base sm:text-lg leading-tight truncate text-gray-900">
                        {{ $pageTitle }}
                    </h2>

                    <p class="text-xs text-gray-500 hidden sm:block">
                        {{ $roleLabel }} Panel
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2 sm:gap-4">
                <a href="{{ url('/notification') }}"
                    class="relative w-11 h-11 rounded-2xl border border-gray-200 flex items-center justify-center hover:bg-gray-100 transition text-gray-700">
                    <i data-lucide="bell" class="w-5 h-5"></i>

                    @if($notificationCount > 0)
                        <span class="absolute -top-1 -right-1 min-w-5 h-5 bg-red-500 text-white text-[10px] px-1.5 rounded-full flex items-center justify-center">
                            {{ $notificationCount }}
                        </span>
                    @endif
                </a>

                <div class="hidden md:flex items-center gap-3">
                    <div class="text-right max-w-[220px]">
                        <div class="text-sm font-semibold truncate text-gray-900">
                            {{ $user->name ?? '-' }}
                        </div>

                        <div class="text-xs text-gray-500">
                            {{ $roleLabel }}
                        </div>
                    </div>

                    <div class="w-10 h-10 rounded-2xl bg-slate-950 text-white flex items-center justify-center font-bold">
                        {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
                    </div>
                </div>
            </div>

        </div>
    </header>

    <!-- ================= OVERLAY ================= -->
    <transition name="fade">
        <div v-show="sidebarOpen"
            v-on:click="closeSidebar"
            class="fixed inset-0 z-50 bg-black/50 backdrop-blur-[1px]">
        </div>
    </transition>

    <!-- ================= DRAWER SIDEBAR ================= -->
    <transition name="drawer">
    <aside v-show="sidebarOpen"
        class="fixed inset-y-0 left-0 z-[60] w-[88vw] max-w-[360px] bg-slate-950 text-white shadow-2xl flex flex-col">

            <div class="h-16 px-5 flex items-center justify-between border-b border-slate-800 shrink-0">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-10 h-10 rounded-2xl bg-blue-600 flex items-center justify-center shrink-0">
                        <i data-lucide="bar-chart-3" class="w-5 h-5"></i>
                    </div>

                    <div class="min-w-0">
                        <h1 class="text-lg font-bold leading-tight truncate">
                            BPS Monitoring
                        </h1>

                        <p class="text-xs text-slate-400 mt-0.5">
                            Performance System
                        </p>
                    </div>
                </div>

                <button type="button"
                    v-on:click="closeSidebar"
                    class="w-10 h-10 rounded-xl bg-slate-900 hover:bg-slate-800 flex items-center justify-center text-slate-300 hover:text-white transition"
                    aria-label="Tutup menu">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <div class="px-5 py-4 border-b border-slate-800 shrink-0">
                <div class="rounded-2xl bg-slate-900 border border-slate-800 p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-2xl bg-slate-700 flex items-center justify-center font-bold shrink-0">
                            {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
                        </div>

                        <div class="min-w-0">
                            <div class="font-semibold text-sm truncate">
                                {{ $user->name ?? '-' }}
                            </div>

                            <div class="text-xs text-slate-400 mt-0.5">
                                {{ $roleLabel }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <nav class="flex-1 overflow-y-auto px-4 py-4 text-sm space-y-1">
                @foreach($menuGroups as $group)
                    <div class="px-3 pt-4 pb-1 text-xs font-semibold text-slate-500 uppercase tracking-wider first:pt-0">
                        {{ $group['label'] }}
                    </div>

                    @foreach($group['items'] as $item)
                        @php
                            $active = $item['custom_active'] ?? $isActive(
                                $item['path'],
                                $item['mode'] ?? null,
                                $item['exact'] ?? false
                            );
                        @endphp

                        <a href="{{ $item['url'] }}"
                            v-on:click="keepSidebarOpenAfterNavigation"
                            class="{{ $menuItemClass($active) }}">
                            <i data-lucide="{{ $item['icon'] }}" class="w-5 h-5 shrink-0"></i>

                            <span class="truncate">
                                {{ $item['label'] }}
                            </span>
                        </a>
                    @endforeach
                @endforeach

                @if($role === 'anggota')
                    <div class="mt-5 rounded-2xl bg-blue-950/60 border border-blue-900 p-4">
                        <div class="flex items-center gap-2 text-blue-200 font-semibold text-xs">
                            <i data-lucide="route" class="w-4 h-4"></i>
                            <span>Alur Anggota</span>
                        </div>

                        <p class="text-[11px] text-blue-100/80 mt-2 leading-relaxed">
                            Project Saya → RK Pribadi Saya → Daily Task Saya → Submit RK.
                        </p>
                    </div>
                @endif

                <div class="px-3 pt-5 pb-1 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                    Lainnya
                </div>

                <a href="{{ url('/notification') }}"
                    v-on:click="keepSidebarOpenAfterNavigation"
                    class="{{ $menuItemClass($notificationActive) }}">
                    <i data-lucide="bell" class="w-5 h-5 shrink-0"></i>

                    <span class="flex-1 truncate">
                        Notifications
                    </span>

                    @if($notificationCount > 0)
                        <span class="bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">
                            {{ $notificationCount }}
                        </span>
                    @endif
                </a>
            </nav>

            <div class="p-4 border-t border-slate-800 shrink-0">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <button class="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-2xl bg-red-600 hover:bg-red-700 text-white text-sm font-semibold transition">
                        <i data-lucide="log-out" class="w-5 h-5"></i>
                        <span>Logout</span>
                    </button>
                </form>

                <div class="text-[11px] text-slate-500 text-center mt-3">
                    BPS Monitoring © {{ date('Y') }}
                </div>
            </div>

        </aside>
    </transition>

    <!-- ================= CONTENT ================= -->
    <main class="p-4 sm:p-6 lg:p-8">
        {{ $slot }}
    </main>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const { createApp, nextTick } = Vue;

    createApp({
        data() {
            return {
                sidebarOpen: false,
                storageKey: 'bps_sidebar_open',
            };
        },

        methods: {
            toggleSidebar() {
                this.sidebarOpen = !this.sidebarOpen;

                if (this.sidebarOpen) {
                    sessionStorage.setItem(this.storageKey, 'true');
                } else {
                    sessionStorage.removeItem(this.storageKey);
                }

                this.refreshIcons();
            },

            openSidebar() {
                this.sidebarOpen = true;
                sessionStorage.setItem(this.storageKey, 'true');
                this.refreshIcons();
            },

            closeSidebar() {
                this.sidebarOpen = false;
                sessionStorage.removeItem(this.storageKey);
                this.refreshIcons();
            },

            keepSidebarOpenAfterNavigation() {
                sessionStorage.setItem(this.storageKey, 'true');
            },

            refreshIcons() {
                nextTick(() => {
                    if (window.lucide) {
                        window.lucide.createIcons();
                    }
                });
            },
        },

        mounted() {
            this.sidebarOpen = sessionStorage.getItem(this.storageKey) === 'true';

            this.refreshIcons();

            window.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    this.closeSidebar();
                }
            });
        },
    }).mount('#app-shell');
});
</script>

</body>
</html>