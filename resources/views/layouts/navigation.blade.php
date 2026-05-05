@php
    $user = auth()->user();
    $role = $user->role;

    $isActive = function ($path, $exact = false) {
        $path = trim($path, '/');

        if ($exact) {
            return request()->is($path);
        }

        return request()->is($path) || request()->is($path . '/*');
    };

    $unreadNotificationCount = \App\Models\Notification::where('user_id', auth()->id())
        ->where('is_read', false)
        ->count();

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
                        'exact' => true,
                        'icon' => 'layout-dashboard',
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
                        'exact' => false,
                        'icon' => 'users',
                    ],
                    [
                        'label' => 'Team',
                        'url' => url('/admin/team'),
                        'path' => '/admin/team',
                        'exact' => false,
                        'icon' => 'network',
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
                        'exact' => false,
                        'icon' => 'target',
                    ],
                    [
                        'label' => 'RK Ketua',
                        'url' => url('/admin/rk-ketua'),
                        'path' => '/admin/rk-ketua',
                        'exact' => false,
                        'icon' => 'clipboard-check',
                    ],
                    [
                        'label' => 'Project',
                        'url' => url('/admin/project'),
                        'path' => '/admin/project',
                        'exact' => false,
                        'icon' => 'folder-kanban',
                    ],
                    [
                        'label' => 'RK Anggota',
                        'url' => url('/admin/rk-anggota'),
                        'path' => '/admin/rk-anggota',
                        'exact' => false,
                        'icon' => 'file-text',
                    ],
                    [
                        'label' => 'Daily Task',
                        'url' => url('/admin/daily-task'),
                        'path' => '/admin/daily-task',
                        'exact' => false,
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
                        'exact' => true,
                        'icon' => 'layout-dashboard',
                    ],
                ],
            ],
            [
                'label' => 'Ketua Tim',
                'items' => [
                    [
                        'label' => 'RK Ketua',
                        'url' => url('/ketua/rk-ketua'),
                        'path' => '/ketua/rk-ketua',
                        'exact' => false,
                        'icon' => 'clipboard-check',
                    ],
                    [
                        'label' => 'Project Tim',
                        'url' => url('/ketua/project'),
                        'path' => '/ketua/project',
                        'exact' => false,
                        'icon' => 'folder-kanban',
                    ],
                    [
                        'label' => 'Review RK Anggota',
                        'url' => url('/ketua/rk-anggota'),
                        'path' => '/ketua/rk-anggota',
                        'exact' => false,
                        'icon' => 'file-check-2',
                        'custom_active' => request()->is('ketua/rk-anggota*') && request('mode') !== 'mine',
                    ],
                    [
                        'label' => 'Monitoring Daily Task',
                        'url' => url('/ketua/daily-task'),
                        'path' => '/ketua/daily-task',
                        'exact' => false,
                        'icon' => 'activity',
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
                        'exact' => false,
                        'icon' => 'file-pen-line',
                        'custom_active' => request()->is('ketua/rk-anggota*') && request('mode') === 'mine',
                    ],
                    [
                        'label' => 'Daily Task Saya',
                        'url' => url('/ketua/daily-task?mode=mine'),
                        'path' => '/ketua/daily-task',
                        'exact' => false,
                        'icon' => 'list-todo',
                        'custom_active' => request()->is('ketua/daily-task*') && request('mode') === 'mine',
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
                        'exact' => true,
                        'icon' => 'layout-dashboard',
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
                        'exact' => false,
                        'icon' => 'folder-kanban',
                    ],
                    [
                        'label' => 'RK Pribadi Saya',
                        'url' => url('/anggota/rk-anggota'),
                        'path' => '/anggota/rk-anggota',
                        'exact' => false,
                        'icon' => 'file-pen-line',
                    ],
                    [
                        'label' => 'Daily Task Saya',
                        'url' => url('/anggota/daily-task'),
                        'path' => '/anggota/daily-task',
                        'exact' => false,
                        'icon' => 'list-todo',
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
                        'exact' => true,
                        'icon' => 'layout-dashboard',
                    ],
                    [
                        'label' => 'Ringkasan Kinerja',
                        'url' => url('/kepala'),
                        'path' => '/kepala',
                        'exact' => true,
                        'icon' => 'bar-chart-3',
                    ],
                ],
            ],
        ];
    }

    $notificationActive = $isActive('/notification');
@endphp

<!-- Vue + Lucide CDN -->
<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
<script src="https://unpkg.com/lucide@latest"></script>

<style>
    [v-cloak] {
        display: none;
    }
</style>

<div id="app-navigation" v-cloak class="min-h-screen bg-slate-100">

    <!-- ================= TOPBAR ================= -->
    <header class="sticky top-0 z-40 bg-white/95 backdrop-blur border-b shadow-sm">
        <div class="px-4 sm:px-6 lg:px-8 py-3 flex items-center justify-between gap-4">

            <div class="flex items-center gap-3 min-w-0">
                <button type="button"
                    v-on:click="openSidebar"
                    class="inline-flex items-center justify-center w-11 h-11 rounded-2xl bg-slate-900 text-white hover:bg-slate-800 transition shadow-sm"
                    aria-label="Open sidebar">
                    <i data-lucide="menu" class="w-5 h-5"></i>
                </button>

                <div class="min-w-0">
                    <div class="text-base sm:text-lg font-bold text-gray-900 truncate">
                        {{ $pageTitle }}
                    </div>

                    <div class="text-xs text-gray-500 capitalize truncate">
                        BPS Monitoring · {{ $role }}
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2 sm:gap-4">

                <!-- Notification -->
                <a href="{{ url('/notification') }}"
                    class="relative inline-flex items-center justify-center w-11 h-11 rounded-2xl bg-gray-100 hover:bg-gray-200 text-gray-700 transition">
                    <i data-lucide="bell" class="w-5 h-5"></i>

                    @if($unreadNotificationCount > 0)
                        <span class="absolute -top-1 -right-1 min-w-5 h-5 bg-red-500 text-white rounded-full text-[10px] px-1.5 flex items-center justify-center">
                            {{ $unreadNotificationCount }}
                        </span>
                    @endif
                </a>

                <!-- User Desktop -->
                <div class="hidden sm:flex items-center gap-3">
                    <div class="text-right">
                        <div class="text-sm font-semibold text-gray-900 max-w-[160px] truncate">
                            {{ $user->name }}
                        </div>
                        <div class="text-xs text-gray-500 capitalize">
                            {{ $role }}
                        </div>
                    </div>

                    <div class="w-10 h-10 rounded-2xl bg-slate-900 text-white flex items-center justify-center font-bold">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                </div>

            </div>
        </div>
    </header>

    <!-- ================= SIDEBAR OVERLAY ================= -->
    <transition name="fade">
        <div v-if="sidebarOpen"
            v-on:click="closeSidebar"
            class="fixed inset-0 z-50 bg-black/50 backdrop-blur-[1px]">
        </div>
    </transition>

    <!-- ================= POPUP SIDEBAR / DRAWER ================= -->
    <transition name="drawer">
        <aside v-if="sidebarOpen"
            class="fixed top-0 left-0 z-[60] h-screen w-[88vw] max-w-[340px] bg-slate-950 text-white shadow-2xl flex flex-col">

            <!-- Sidebar Header -->
            <div class="p-5 border-b border-slate-800">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-3">
                            <div class="w-11 h-11 rounded-2xl bg-blue-600 text-white flex items-center justify-center">
                                <i data-lucide="monitor-check" class="w-6 h-6"></i>
                            </div>

                            <div>
                                <h1 class="text-lg font-bold">
                                    BPS Monitoring
                                </h1>
                                <p class="text-xs text-slate-400 mt-0.5">
                                    Sistem Monitoring Kinerja
                                </p>
                            </div>
                        </div>
                    </div>

                    <button type="button"
                        v-on:click="closeSidebar"
                        class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-slate-900 hover:bg-slate-800 transition"
                        aria-label="Close sidebar">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>

            <!-- User Card -->
            <div class="px-5 py-4 border-b border-slate-800">
                <div class="rounded-2xl bg-slate-900 border border-slate-800 p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-2xl bg-slate-700 text-white flex items-center justify-center font-bold">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>

                        <div class="min-w-0">
                            <div class="text-sm font-semibold truncate">
                                {{ $user->name }}
                            </div>

                            <div class="text-xs text-slate-400 capitalize mt-0.5">
                                Role: {{ $role }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Menu -->
            <nav class="flex-1 overflow-y-auto px-4 py-4 space-y-1">

                @foreach($menuGroups as $group)
                    <div class="text-[11px] uppercase tracking-wider text-slate-500 mt-5 mb-2 px-3 font-semibold first:mt-0">
                        {{ $group['label'] }}
                    </div>

                    @foreach($group['items'] as $item)
                        @php
                            $active = $item['custom_active'] ?? $isActive($item['path'], $item['exact'] ?? false);
                        @endphp

                        <a href="{{ $item['url'] }}"
                            class="{{ $active
                                ? 'flex items-center gap-3 px-3 py-3 rounded-2xl bg-blue-600 text-white font-semibold shadow-lg shadow-blue-950/30'
                                : 'flex items-center gap-3 px-3 py-3 rounded-2xl text-slate-300 hover:bg-slate-900 hover:text-white transition' }}"
                            v-on:click="closeSidebar">

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
                            <span>Flow Anggota</span>
                        </div>

                        <p class="text-[11px] text-blue-100/80 mt-2 leading-relaxed">
                            Project Saya → RK Pribadi Saya → Daily Task Saya → Submit RK.
                        </p>
                    </div>
                @endif

                <div class="text-[11px] uppercase tracking-wider text-slate-500 mt-5 mb-2 px-3 font-semibold">
                    Lainnya
                </div>

                <a href="{{ url('/notification') }}"
                    class="{{ $notificationActive
                        ? 'flex items-center gap-3 px-3 py-3 rounded-2xl bg-blue-600 text-white font-semibold shadow-lg shadow-blue-950/30'
                        : 'flex items-center gap-3 px-3 py-3 rounded-2xl text-slate-300 hover:bg-slate-900 hover:text-white transition' }}"
                    v-on:click="closeSidebar">

                    <i data-lucide="bell" class="w-5 h-5 shrink-0"></i>

                    <span class="flex-1 truncate">
                        Notifications
                    </span>

                    @if($unreadNotificationCount > 0)
                        <span class="bg-red-500 text-white rounded-full text-[11px] px-2 py-0.5">
                            {{ $unreadNotificationCount }}
                        </span>
                    @endif
                </a>

            </nav>

            <!-- Logout -->
            <div class="p-4 border-t border-slate-800">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <button class="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-2xl bg-red-600 hover:bg-red-700 text-white text-sm font-semibold transition">
                        <i data-lucide="log-out" class="w-5 h-5"></i>
                        <span>Logout</span>
                    </button>
                </form>
            </div>

        </aside>
    </transition>

    <!-- ================= MAIN CONTENT ================= -->
    <main class="p-4 sm:p-6 lg:p-8">
        {{ $slot }}
    </main>

</div>

<style>
    .fade-enter-active,
    .fade-leave-active {
        transition: opacity 0.18s ease;
    }

    .fade-enter-from,
    .fade-leave-to {
        opacity: 0;
    }

    .drawer-enter-active,
    .drawer-leave-active {
        transition: transform 0.22s ease, opacity 0.22s ease;
    }

    .drawer-enter-from,
    .drawer-leave-to {
        transform: translateX(-100%);
        opacity: 0;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const { createApp, nextTick } = Vue;

    createApp({
        data() {
            return {
                sidebarOpen: false,
            };
        },

        methods: {
            openSidebar() {
                this.sidebarOpen = true;
                this.refreshIcons();
            },

            closeSidebar() {
                this.sidebarOpen = false;
                this.refreshIcons();
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
            this.refreshIcons();

            window.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    this.closeSidebar();
                }
            });
        },
    }).mount('#app-navigation');
});
</script>