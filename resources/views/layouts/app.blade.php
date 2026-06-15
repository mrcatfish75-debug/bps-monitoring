<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BPS Monitoring</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    <!-- Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .sidebar-scroll::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar-scroll::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.45);
            border-radius: 9999px;
        }

        .sidebar-scroll::-webkit-scrollbar-track {
            background: transparent;
        }

        .notification-scroll::-webkit-scrollbar {
            width: 6px;
        }

        .notification-scroll::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.5);
            border-radius: 9999px;
        }

        .notification-scroll::-webkit-scrollbar-track {
            background: transparent;
        }

        /*
        |--------------------------------------------------------------------------
        | Sidebar Theme
        |--------------------------------------------------------------------------
        */

        #sidebar-shell[data-sidebar-theme="dark"] .sidebar-rail {
            background: #020617 !important;
            color: #ffffff !important;
            border-color: #1e293b !important;
        }

        #sidebar-shell[data-sidebar-theme="light"] .sidebar-rail {
            background: #ffffff !important;
            color: #0f172a !important;
            border-color: #e2e8f0 !important;
        }

        #sidebar-shell[data-sidebar-theme="dark"] .sidebar-brand,
        #sidebar-shell[data-sidebar-theme="dark"] .sidebar-menu-toggle,
        #sidebar-shell[data-sidebar-theme="dark"] .sidebar-theme-toggle {
            background: #0f172a !important;
            border-color: #1e293b !important;
            color: #cbd5e1 !important;
        }

        #sidebar-shell[data-sidebar-theme="light"] .sidebar-brand,
        #sidebar-shell[data-sidebar-theme="light"] .sidebar-menu-toggle,
        #sidebar-shell[data-sidebar-theme="light"] .sidebar-theme-toggle {
            background: #f8fafc !important;
            border-color: #e2e8f0 !important;
            color: #334155 !important;
        }

        #sidebar-shell[data-sidebar-theme="dark"] .sidebar-rail-link {
            color: #cbd5e1 !important;
        }

        #sidebar-shell[data-sidebar-theme="light"] .sidebar-rail-link {
            color: #475569 !important;
        }

        #sidebar-shell[data-sidebar-theme="dark"] .sidebar-rail-link:hover,
        #sidebar-shell[data-sidebar-theme="dark"] .sidebar-rail-link.is-active {
            background: #1e293b !important;
            color: #ffffff !important;
        }

        #sidebar-shell[data-sidebar-theme="light"] .sidebar-rail-link:hover,
        #sidebar-shell[data-sidebar-theme="light"] .sidebar-rail-link.is-active {
            background: #dbeafe !important;
            color: #2563eb !important;
        }

        #sidebar-shell[data-sidebar-theme="dark"] .sidebar-avatar {
            background: #1e293b !important;
            color: #ffffff !important;
        }

        #sidebar-shell[data-sidebar-theme="light"] .sidebar-avatar {
            background: #e2e8f0 !important;
            color: #0f172a !important;
        }

        /*
        |--------------------------------------------------------------------------
        | Expanded Sidebar Panel Theme
        |--------------------------------------------------------------------------
        */

        #sidebar-shell[data-sidebar-theme="dark"] .sidebar-panel {
            background: #020617 !important;
            color: #f8fafc !important;
            border-color: #1e293b !important;
        }

        #sidebar-shell[data-sidebar-theme="light"] .sidebar-panel {
            background: #ffffff !important;
            color: #0f172a !important;
            border-color: #e2e8f0 !important;
        }

        #sidebar-shell[data-sidebar-theme="dark"] .sidebar-panel-header,
        #sidebar-shell[data-sidebar-theme="dark"] .sidebar-panel-footer {
            background: #020617 !important;
            border-color: #1e293b !important;
        }

        #sidebar-shell[data-sidebar-theme="light"] .sidebar-panel-header,
        #sidebar-shell[data-sidebar-theme="light"] .sidebar-panel-footer {
            background: #ffffff !important;
            border-color: #e2e8f0 !important;
        }

        #sidebar-shell[data-sidebar-theme="dark"] .sidebar-panel .text-slate-950,
        #sidebar-shell[data-sidebar-theme="dark"] .sidebar-panel .text-slate-900,
        #sidebar-shell[data-sidebar-theme="dark"] .sidebar-panel .text-slate-800,
        #sidebar-shell[data-sidebar-theme="dark"] .sidebar-panel .text-slate-700 {
            color: #f8fafc !important;
        }

        #sidebar-shell[data-sidebar-theme="dark"] .sidebar-panel .text-slate-600,
        #sidebar-shell[data-sidebar-theme="dark"] .sidebar-panel .text-slate-500,
        #sidebar-shell[data-sidebar-theme="dark"] .sidebar-panel .text-slate-400 {
            color: #94a3b8 !important;
        }

        #sidebar-shell[data-sidebar-theme="dark"] .sidebar-panel .bg-white,
        #sidebar-shell[data-sidebar-theme="dark"] .sidebar-panel .bg-slate-50,
        #sidebar-shell[data-sidebar-theme="dark"] .sidebar-panel .bg-slate-100 {
            background: #0f172a !important;
        }

        #sidebar-shell[data-sidebar-theme="dark"] .sidebar-panel .hover\:bg-slate-50:hover,
        #sidebar-shell[data-sidebar-theme="dark"] .sidebar-panel .hover\:bg-slate-100:hover {
            background: #1e293b !important;
        }

        #sidebar-shell[data-sidebar-theme="dark"] .sidebar-panel .border-slate-200,
        #sidebar-shell[data-sidebar-theme="dark"] .sidebar-panel .border-blue-100 {
            border-color: #1e293b !important;
        }

        #sidebar-shell[data-sidebar-theme="dark"] .sidebar-panel .bg-blue-50 {
            background: rgba(37, 99, 235, 0.18) !important;
        }

        #sidebar-shell[data-sidebar-theme="dark"] .sidebar-panel .text-blue-700,
        #sidebar-shell[data-sidebar-theme="dark"] .sidebar-panel .text-blue-600,
        #sidebar-shell[data-sidebar-theme="dark"] .sidebar-panel .text-blue-500 {
            color: #93c5fd !important;
        }

        #sidebar-shell[data-sidebar-theme="dark"] .sidebar-panel .ring-blue-100 {
            --tw-ring-color: rgba(37, 99, 235, 0.35) !important;
        }

        #sidebar-shell[data-sidebar-theme="dark"] .sidebar-panel .bg-emerald-50 {
            background: rgba(16, 185, 129, 0.14) !important;
        }

        #sidebar-shell[data-sidebar-theme="dark"] .sidebar-panel .text-emerald-700 {
            color: #6ee7b7 !important;
        }

        #sidebar-shell[data-sidebar-theme="dark"] .sidebar-theme-switcher {
            background: #0f172a !important;
        }

        #sidebar-shell[data-sidebar-theme="light"] .sidebar-theme-switcher {
            background: #f1f5f9 !important;
        }

        #sidebar-shell[data-sidebar-theme="dark"] .sidebar-panel .bg-red-50 {
            background: rgba(239, 68, 68, 0.13) !important;
        }

        #sidebar-shell[data-sidebar-theme="dark"] .sidebar-panel .hover\:bg-red-100:hover {
            background: rgba(239, 68, 68, 0.2) !important;
        }

        #sidebar-shell[data-sidebar-theme="dark"] .sidebar-panel .text-red-600 {
            color: #f87171 !important;
        }
    </style>
</head>

<body class="font-sans antialiased bg-slate-100 text-slate-900">

@php
    $user = auth()->user();
    $role = $user?->role;
    $userInitial = strtoupper(substr($user->name ?? 'U', 0, 1));

    $roleLabel = match ($role) {
        'admin' => 'Admin',
        'ketua' => 'Ketua Tim',
        'anggota' => 'Anggota',
        'kepala' => 'Kepala',
        default => 'User',
    };

    /*
    |--------------------------------------------------------------------------
    | Notification Data
    |--------------------------------------------------------------------------
    | Popup topbar memakai data ini.
    | Sidebar kiri tetap menjadi shortcut ke halaman semua notifikasi.
    |--------------------------------------------------------------------------
    */
    $notificationCount = 0;
    $recentNotifications = collect();

    if ($user) {
        $notificationCount = \App\Models\Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        $recentNotifications = \App\Models\Notification::where('user_id', $user->id)
            ->latest()
            ->limit(8)
            ->get();
    }

    $notificationTypeMeta = function ($type) {
        return match ($type) {
            'iki_submitted' => [
                'label' => 'Review IKI',
                'icon' => 'file-clock',
                'class' => 'bg-blue-50 text-blue-700 border-blue-100',
            ],
            'iki_approved' => [
                'label' => 'IKI Disetujui',
                'icon' => 'badge-check',
                'class' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
            ],
            'iki_rejected' => [
                'label' => 'IKI Revisi',
                'icon' => 'circle-alert',
                'class' => 'bg-red-50 text-red-700 border-red-100',
            ],
            'project_assigned' => [
                'label' => 'Project Baru',
                'icon' => 'folder-plus',
                'class' => 'bg-violet-50 text-violet-700 border-violet-100',
            ],
            'success' => [
                'label' => 'Berhasil',
                'icon' => 'check-circle-2',
                'class' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
            ],
            'warning' => [
                'label' => 'Perhatian',
                'icon' => 'alert-triangle',
                'class' => 'bg-amber-50 text-amber-700 border-amber-100',
            ],
            'danger' => [
                'label' => 'Penting',
                'icon' => 'circle-alert',
                'class' => 'bg-red-50 text-red-700 border-red-100',
            ],
            default => [
                'label' => 'Informasi',
                'icon' => 'bell',
                'class' => 'bg-slate-50 text-slate-700 border-slate-100',
            ],
        };
    };

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

    $pageTitle = match (true) {
        request()->is('admin') => 'Dashboard Admin',
        request()->is('admin/users*') => 'Kelola Users',
        request()->is('admin/team*') => 'Kelola Team',
        request()->is('admin/iku*') => 'Kelola IKU',
        request()->is('admin/rk-ketua*') => 'RK Ketua',
        request()->is('admin/project*') => 'Project',
        request()->is('admin/rk-anggota*') => 'RK Anggota',
        request()->is('admin/iki*') => 'IKI',
        request()->is('admin/daily-task*') => 'Daily Task',

        request()->is('ketua') => 'Dashboard Ketua Tim',
        request()->is('ketua/rk-ketua*') => 'RK Ketua',
        request()->is('ketua/project*') => 'Project Tim',
        request()->is('ketua/rk-anggota*') && request('mode') === 'mine' => 'RK Pribadi Saya',
        request()->is('ketua/rk-anggota*') => 'RK Anggota Project',
        request()->is('ketua/iki*') && request('mode') === 'mine' => 'IKI Pribadi Saya',
        request()->is('ketua/iki*') => 'Review IKI',
        request()->is('ketua/daily-task*') && request('mode') === 'mine' => 'Daily Task Saya',
        request()->is('ketua/daily-task*') => 'Monitoring Daily Task',

        request()->is('anggota') => 'Dashboard Anggota',
        request()->is('anggota/project*') => 'Project Saya',
        request()->is('anggota/rk-anggota*') => 'RK Pribadi Saya',
        request()->is('anggota/iki*') => 'IKI Saya',
        request()->is('anggota/daily-task*') => 'Daily Task Saya',

        request()->is('kepala') => 'Dashboard Kepala',
        request()->is('kepala/iku*') => 'Monitoring IKU',
        request()->is('kepala/rk-ketua*') => 'Monitoring RK Ketua',
        request()->is('kepala/project*') => 'Monitoring Project',
        request()->is('kepala/rk-anggota*') => 'Monitoring RK Anggota',
        request()->is('kepala/iki*') => 'Monitoring IKI',
        request()->is('kepala/daily-task*') => 'Monitoring Daily Task',

        request()->is('notification') || request()->is('notification/*') => 'Notifikasi',

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
                        'description' => 'Ringkasan sistem monitoring.',
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
                        'description' => 'Kelola akun, role, dan akses pengguna.',
                        'url' => url('/admin/users'),
                        'path' => '/admin/users',
                        'icon' => 'users',
                    ],
                    [
                        'label' => 'Team',
                        'description' => 'Kelola tim dan ketua tim.',
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
                        'description' => 'Indikator utama level organisasi.',
                        'url' => url('/admin/iku'),
                        'path' => '/admin/iku',
                        'icon' => 'bar-chart-3',
                    ],
                    [
                        'label' => 'RK Ketua',
                        'description' => 'Rencana kerja ketua per IKU dan tim.',
                        'url' => url('/admin/rk-ketua'),
                        'path' => '/admin/rk-ketua',
                        'icon' => 'target',
                    ],
                    [
                        'label' => 'Project',
                        'description' => 'Kelola project dan anggota project.',
                        'url' => url('/admin/project'),
                        'path' => '/admin/project',
                        'icon' => 'folder-kanban',
                    ],
                    [
                        'label' => 'RK Anggota',
                        'description' => 'Wadah rencana kerja pelaksana.',
                        'url' => url('/admin/rk-anggota'),
                        'path' => '/admin/rk-anggota',
                        'icon' => 'clipboard-check',
                    ],
                    [
                        'label' => 'IKI',
                        'description' => 'Unit approval dan capaian individu.',
                        'url' => url('/admin/iki'),
                        'path' => '/admin/iki',
                        'icon' => 'badge-check',
                    ],
                    [
                        'label' => 'Daily Task',
                        'description' => 'Aktivitas harian pendukung IKI.',
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
                        'description' => 'Ringkasan pekerjaan ketua tim.',
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
                        'description' => 'Kelola rencana kerja tim.',
                        'url' => url('/ketua/rk-ketua'),
                        'path' => '/ketua/rk-ketua',
                        'icon' => 'target',
                    ],
                    [
                        'label' => 'Project Tim',
                        'description' => 'Project yang dipimpin atau diikuti.',
                        'url' => url('/ketua/project'),
                        'path' => '/ketua/project',
                        'icon' => 'folder-kanban',
                    ],
                    [
                        'label' => 'RK Anggota Project',
                        'description' => 'Pantau RK anggota pada project tim.',
                        'url' => url('/ketua/rk-anggota'),
                        'path' => '/ketua/rk-anggota',
                        'icon' => 'clipboard-check',
                        'custom_active' => request()->is('ketua/rk-anggota*') && request('mode') !== 'mine',
                    ],
                    [
                        'label' => 'Review IKI',
                        'description' => 'Approve/reject IKI dari anggota project.',
                        'url' => url('/ketua/iki'),
                        'path' => '/ketua/iki',
                        'icon' => 'badge-check',
                        'custom_active' => request()->is('ketua/iki*') && request('mode') !== 'mine',
                    ],
                    [
                        'label' => 'Monitoring Daily Task',
                        'description' => 'Pantau aktivitas harian di bawah IKI.',
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
                        'description' => 'RK pribadi sebagai pelaksana project lain.',
                        'url' => url('/ketua/rk-anggota?mode=mine'),
                        'path' => '/ketua/rk-anggota',
                        'mode' => 'mine',
                        'icon' => 'user-check',
                    ],
                    [
                        'label' => 'IKI Pribadi Saya',
                        'description' => 'Kelola IKI pribadi untuk RK milik saya.',
                        'url' => url('/ketua/iki?mode=mine'),
                        'path' => '/ketua/iki',
                        'mode' => 'mine',
                        'icon' => 'badge-check',
                    ],
                    [
                        'label' => 'Daily Task Saya',
                        'description' => 'Daily task pribadi di bawah IKI.',
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
                        'description' => 'Ringkasan pekerjaan saya.',
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
                        'description' => 'Project yang saya ikuti.',
                        'url' => url('/anggota/project'),
                        'path' => '/anggota/project',
                        'icon' => 'folder-kanban',
                    ],
                    [
                        'label' => 'RK Pribadi Saya',
                        'description' => 'Wadah rencana kerja pribadi saya.',
                        'url' => url('/anggota/rk-anggota'),
                        'path' => '/anggota/rk-anggota',
                        'icon' => 'file-pen-line',
                    ],
                    [
                        'label' => 'IKI Saya',
                        'description' => 'Kelola IKI dan submit bukti final.',
                        'url' => url('/anggota/iki'),
                        'path' => '/anggota/iki',
                        'icon' => 'badge-check',
                    ],
                    [
                        'label' => 'Daily Task Saya',
                        'description' => 'Aktivitas harian di bawah IKI.',
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
                'label' => 'Utama',
                'items' => [
                    [
                        'label' => 'Dashboard',
                        'description' => 'Ringkasan kinerja organisasi.',
                        'url' => url('/kepala'),
                        'path' => '/kepala',
                        'icon' => 'layout-dashboard',
                        'exact' => true,
                    ],
                ],
            ],
            [
                'label' => 'Monitoring Data',
                'items' => [
                    [
                        'label' => 'IKU',
                        'description' => 'Pantau capaian indikator utama.',
                        'url' => url('/kepala/iku'),
                        'path' => '/kepala/iku',
                        'icon' => 'bar-chart-3',
                    ],
                    [
                        'label' => 'RK Ketua',
                        'description' => 'Pantau RK Ketua per IKU dan tim.',
                        'url' => url('/kepala/rk-ketua'),
                        'path' => '/kepala/rk-ketua',
                        'icon' => 'target',
                    ],
                    [
                        'label' => 'Project',
                        'description' => 'Pantau project lintas tim.',
                        'url' => url('/kepala/project'),
                        'path' => '/kepala/project',
                        'icon' => 'folder-kanban',
                    ],
                    [
                        'label' => 'RK Anggota',
                        'description' => 'Pantau RK anggota sebagai wadah kerja.',
                        'url' => url('/kepala/rk-anggota'),
                        'path' => '/kepala/rk-anggota',
                        'icon' => 'clipboard-check',
                    ],
                    [
                        'label' => 'IKI',
                        'description' => 'Pantau IKI, bukti final, dan status review.',
                        'url' => url('/kepala/iki'),
                        'path' => '/kepala/iki',
                        'icon' => 'badge-check',
                    ],
                    [
                        'label' => 'Daily Task',
                        'description' => 'Pantau aktivitas harian di bawah IKI.',
                        'url' => url('/kepala/daily-task'),
                        'path' => '/kepala/daily-task',
                        'icon' => 'list-checks',
                    ],
                ],
            ],
        ];
    }

    $notificationActive = request()->is('notification') || request()->is('notification/*');

    $utilityMenu = [
        [
            'label' => 'Notifikasi',
            'description' => 'Lihat semua pembaruan sistem.',
            'url' => url('/notification'),
            'icon' => 'bell',
            'active' => $notificationActive,
            'badge' => $notificationCount,
        ],
    ];

    $flatMenu = collect($menuGroups)
        ->flatMap(fn ($group) => $group['items'])
        ->values()
        ->all();

    $activeItem = collect($flatMenu)->first(function ($item) use ($isActive) {
        return $item['custom_active'] ?? $isActive(
            $item['path'],
            $item['mode'] ?? null,
            $item['exact'] ?? false
        );
    });

    $activeDescription = $activeItem['description'] ?? (
        $notificationActive
            ? 'Lihat pembaruan penting untuk akun kamu.'
            : 'Monitoring kinerja dari IKU sampai IKI dan Daily Task.'
    );
@endphp

<div class="min-h-screen">

    <div id="sidebar-shell" v-bind:data-sidebar-theme="sidebarTheme">

        <!-- Mobile Overlay -->
        <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-show="sidebarExpanded && isMobile()"
                v-on:click="closeSidebar"
                class="fixed inset-0 z-40 bg-slate-950/50 backdrop-blur-[2px] lg:hidden"
                aria-hidden="true">
            </div>
        </transition>

        <!-- Compact Sidebar Rail -->
        <aside class="sidebar-rail fixed inset-y-0 left-0 z-50 w-20 lg:w-24 bg-slate-950 text-white border-r border-slate-800 shadow-2xl transition-colors duration-200">
            <div class="h-full flex flex-col items-center px-3 py-4">

                <button
                    type="button"
                    v-on:click="toggleSidebar"
                    class="sidebar-brand w-full rounded-3xl bg-slate-900 border border-slate-800 p-3 flex flex-col items-center gap-2 hover:bg-slate-800 transition"
                    title="BPS Monitoring"
                    aria-label="BPS Monitoring">
                    <div class="w-11 h-11 rounded-2xl bg-blue-600 flex items-center justify-center shadow-lg shadow-blue-950/40">
                        <i data-lucide="bar-chart-3" class="w-5 h-5"></i>
                    </div>

                    <div class="hidden lg:block text-[10px] leading-tight text-center text-slate-400">
                        BPS<br>Monitoring
                    </div>
                </button>

                <button
                    type="button"
                    v-on:click="toggleSidebar"
                    v-bind:class="sidebarExpanded ? 'bg-blue-600 text-white shadow-lg shadow-blue-950/30' : 'text-slate-300 hover:bg-slate-900 hover:text-white'"
                    class="sidebar-menu-toggle mt-4 w-full rounded-2xl px-3 py-3 flex flex-col items-center gap-1 transition"
                    title="Buka/Tutup Menu"
                    aria-label="Buka/Tutup Menu">
                    <i data-lucide="panel-left" class="w-5 h-5"></i>
                    <span class="text-[11px] font-semibold">Menu</span>
                </button>

                <nav class="sidebar-scroll mt-5 w-full flex-1 overflow-y-auto space-y-2">
                    @foreach($flatMenu as $item)
                        @php
                            $active = $item['custom_active'] ?? $isActive(
                                $item['path'],
                                $item['mode'] ?? null,
                                $item['exact'] ?? false
                            );
                        @endphp

                        <a
                            href="{{ $item['url'] }}"
                            v-on:click="handleMenuClick"
                            class="sidebar-rail-link group relative h-14 rounded-2xl flex items-center justify-center transition {{ $active ? 'is-active bg-slate-800 text-white ring-1 ring-slate-700' : 'text-slate-400 hover:bg-slate-900 hover:text-white' }}"
                            title="{{ $item['label'] }}">
                            <i data-lucide="{{ $item['icon'] }}" class="w-5 h-5"></i>

                            <span class="pointer-events-none absolute left-full ml-3 hidden group-hover:block whitespace-nowrap rounded-xl bg-slate-900 px-3 py-2 text-xs font-semibold text-white shadow-xl ring-1 ring-slate-700">
                                {{ $item['label'] }}
                            </span>
                        </a>
                    @endforeach
                </nav>

                <div class="w-full border-t border-slate-800 pt-4 space-y-2">
                    <a
                        href="{{ url('/notification') }}"
                        v-on:click="handleMenuClick"
                        class="sidebar-rail-link relative w-full h-14 rounded-2xl flex items-center justify-center transition {{ $notificationActive ? 'is-active bg-slate-800 text-white ring-1 ring-slate-700' : 'text-slate-400 hover:bg-slate-900 hover:text-white' }}"
                        title="Notifikasi">
                        <i data-lucide="bell" class="w-5 h-5"></i>

                        @if($notificationCount > 0)
                            <span class="absolute top-2 right-2 min-w-5 h-5 rounded-full bg-red-500 px-1 text-[10px] font-bold text-white flex items-center justify-center">
                                {{ $notificationCount > 99 ? '99+' : $notificationCount }}
                            </span>
                        @endif
                    </a>

                    <button
                        type="button"
                        v-on:click="toggleSidebarTheme"
                        class="sidebar-theme-toggle w-full h-14 rounded-2xl border border-slate-800 flex flex-col items-center justify-center gap-1 transition"
                        title="Ganti Dark/Light Sidebar"
                        aria-label="Ganti Dark/Light Sidebar">
                        <i v-show="sidebarTheme === 'dark'" data-lucide="sun" class="w-5 h-5"></i>
                        <i v-show="sidebarTheme === 'light'" data-lucide="moon" class="w-5 h-5"></i>
                        <span class="text-[10px] font-semibold" v-text="sidebarTheme === 'dark' ? 'Light' : 'Dark'"></span>
                    </button>

                    <div class="w-full flex justify-center">
                        <div class="sidebar-avatar w-11 h-11 rounded-2xl bg-slate-800 flex items-center justify-center font-bold text-white">
                            {{ $userInitial }}
                        </div>
                    </div>
                </div>

            </div>
        </aside>

        <!-- Expanded Sidebar Panel -->
        <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 -translate-x-4"
            enter-to-class="opacity-100 translate-x-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-x-0"
            leave-to-class="opacity-0 -translate-x-4"
        >
            <aside
                v-show="sidebarExpanded"
                class="sidebar-panel fixed inset-y-0 left-20 lg:left-24 z-50 w-[calc(100vw-5rem)] max-w-[360px] lg:w-[360px] bg-white border-r border-slate-200 shadow-2xl transition-colors duration-200">

                <div class="h-full flex flex-col">

                    <div class="sidebar-panel-header p-5 border-b border-slate-200">
                        <div class="flex items-start gap-4">
                            <div class="w-14 h-14 rounded-2xl bg-blue-50 text-blue-700 flex items-center justify-center font-extrabold text-lg shrink-0">
                                {{ $userInitial }}
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="font-bold text-slate-950 truncate">
                                    {{ $user->name ?? 'User' }}
                                </div>

                                <div class="text-sm text-slate-500 mt-0.5">
                                    {{ $roleLabel }}
                                </div>

                                <div class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Sistem aktif
                                </div>
                            </div>

                            <button
                                type="button"
                                v-on:click="closeSidebar"
                                class="w-10 h-10 rounded-2xl border border-slate-200 text-slate-500 hover:bg-slate-100 hover:text-slate-900 transition flex items-center justify-center shrink-0"
                                title="Tutup sidebar"
                                aria-label="Tutup sidebar">
                                <i data-lucide="x" class="w-5 h-5"></i>
                            </button>
                        </div>
                    </div>

                    <nav class="sidebar-scroll flex-1 overflow-y-auto px-4 py-4">
                        @foreach($menuGroups as $group)
                            <div class="mb-6">
                                <div class="px-2 mb-2 text-[11px] font-bold uppercase tracking-[0.16em] text-slate-400">
                                    {{ $group['label'] }}
                                </div>

                                <div class="space-y-1.5">
                                    @foreach($group['items'] as $item)
                                        @php
                                            $active = $item['custom_active'] ?? $isActive(
                                                $item['path'],
                                                $item['mode'] ?? null,
                                                $item['exact'] ?? false
                                            );
                                        @endphp

                                        <a
                                            href="{{ $item['url'] }}"
                                            v-on:click="handleMenuClick"
                                            class="group flex items-center gap-3 rounded-2xl px-3 py-3 transition {{ $active ? 'bg-blue-50 text-blue-700 ring-1 ring-blue-100' : 'text-slate-700 hover:bg-slate-50' }}">

                                            <span class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 {{ $active ? 'bg-blue-600 text-white shadow-sm' : 'bg-slate-100 text-slate-500 group-hover:bg-white group-hover:text-slate-900' }}">
                                                <i data-lucide="{{ $item['icon'] }}" class="w-5 h-5"></i>
                                            </span>

                                            <span class="min-w-0 flex-1">
                                                <span class="block text-sm font-bold truncate">
                                                    {{ $item['label'] }}
                                                </span>
                                                <span class="block text-xs {{ $active ? 'text-blue-500' : 'text-slate-500' }} truncate">
                                                    {{ $item['description'] ?? 'Buka halaman ini' }}
                                                </span>
                                            </span>

                                            @if($active)
                                                <i data-lucide="chevron-right" class="w-4 h-4 text-blue-600 shrink-0"></i>
                                            @endif
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        <div class="mb-6">
                            <div class="px-2 mb-2 text-[11px] font-bold uppercase tracking-[0.16em] text-slate-400">
                                Lainnya
                            </div>

                            <div class="space-y-1.5">
                                @foreach($utilityMenu as $item)
                                    <a
                                        href="{{ $item['url'] }}"
                                        v-on:click="handleMenuClick"
                                        class="group flex items-center gap-3 rounded-2xl px-3 py-3 transition {{ $item['active'] ? 'bg-blue-50 text-blue-700 ring-1 ring-blue-100' : 'text-slate-700 hover:bg-slate-50' }}">

                                        <span class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 {{ $item['active'] ? 'bg-blue-600 text-white shadow-sm' : 'bg-slate-100 text-slate-500 group-hover:bg-white group-hover:text-slate-900' }}">
                                            <i data-lucide="{{ $item['icon'] }}" class="w-5 h-5"></i>
                                        </span>

                                        <span class="min-w-0 flex-1">
                                            <span class="block text-sm font-bold truncate">
                                                {{ $item['label'] }}
                                            </span>
                                            <span class="block text-xs {{ $item['active'] ? 'text-blue-500' : 'text-slate-500' }} truncate">
                                                {{ $item['description'] }}
                                            </span>
                                        </span>

                                        @if(($item['badge'] ?? 0) > 0)
                                            <span class="min-w-6 h-6 rounded-full bg-red-500 px-2 text-xs font-bold text-white flex items-center justify-center">
                                                {{ $item['badge'] > 99 ? '99+' : $item['badge'] }}
                                            </span>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>

                        @if(in_array($role, ['admin', 'kepala', 'ketua', 'anggota'], true))
                            <div class="rounded-3xl bg-blue-50 border border-blue-100 p-4">
                                <div class="flex items-center gap-2 text-blue-700 font-bold text-sm">
                                    <i data-lucide="route" class="w-4 h-4"></i>
                                    <span>Alur Sistem</span>
                                </div>

                                @if($role === 'admin')
                                    <p class="text-xs text-blue-700/80 mt-2 leading-relaxed">
                                        IKU → RK Ketua → Project → RK Anggota → IKI → Daily Task. Approval utama dilakukan di IKI.
                                    </p>
                                @elseif($role === 'kepala')
                                    <p class="text-xs text-blue-700/80 mt-2 leading-relaxed">
                                        Pantau capaian dari IKU sampai IKI. Daily Task menjadi bukti aktivitas di bawah IKI.
                                    </p>
                                @elseif($role === 'ketua')
                                    <p class="text-xs text-blue-700/80 mt-2 leading-relaxed">
                                        Kelola RK Ketua dan Project, lalu review IKI anggota. Untuk pekerjaan pribadi, gunakan mode IKI Pribadi Saya.
                                    </p>
                                @else
                                    <p class="text-xs text-blue-700/80 mt-2 leading-relaxed">
                                        Project Saya → RK Pribadi Saya → IKI Saya → Daily Task Saya → Submit IKI.
                                    </p>
                                @endif
                            </div>
                        @endif
                    </nav>

                    <div class="sidebar-panel-footer p-4 border-t border-slate-200">
                        <div class="sidebar-theme-switcher mb-3 grid grid-cols-2 gap-2 rounded-2xl bg-slate-100 p-1">
                            <button
                                type="button"
                                v-on:click="setSidebarTheme('dark')"
                                v-bind:class="sidebarTheme === 'dark' ? 'bg-slate-950 text-white shadow-sm' : 'text-slate-500 hover:text-slate-900'"
                                class="flex items-center justify-center gap-2 rounded-xl px-3 py-2 text-xs font-bold transition">
                                <i data-lucide="moon" class="w-4 h-4"></i>
                                Dark
                            </button>

                            <button
                                type="button"
                                v-on:click="setSidebarTheme('light')"
                                v-bind:class="sidebarTheme === 'light' ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-500 hover:text-slate-900'"
                                class="flex items-center justify-center gap-2 rounded-xl px-3 py-2 text-xs font-bold transition">
                                <i data-lucide="sun" class="w-4 h-4"></i>
                                Light
                            </button>
                        </div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <button
                                type="submit"
                                class="w-full rounded-2xl bg-red-50 px-4 py-3 text-sm font-bold text-red-600 hover:bg-red-100 transition flex items-center justify-center gap-2">
                                <i data-lucide="log-out" class="w-4 h-4"></i>
                                Logout
                            </button>
                        </form>

                        <div class="mt-3 text-center text-[11px] text-slate-400">
                            BPS Monitoring © {{ date('Y') }}
                        </div>
                    </div>

                </div>
            </aside>
        </transition>

        <div class="pl-20 lg:pl-24">
            <header class="sticky top-0 z-30 bg-white/90 backdrop-blur border-b border-slate-200 shadow-sm">
                <div class="h-16 px-4 sm:px-6 lg:px-8 flex items-center justify-between gap-4">

                    <div class="min-w-0">
                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                v-on:click="toggleSidebar"
                                class="lg:hidden w-10 h-10 rounded-2xl bg-slate-950 text-white flex items-center justify-center"
                                aria-label="Buka menu">
                                <i data-lucide="menu" class="w-5 h-5"></i>
                            </button>

                            <div class="min-w-0">
                                <h1 class="font-extrabold text-base sm:text-lg text-slate-950 truncate">
                                    {{ $pageTitle }}
                                </h1>

                                <p class="hidden sm:block text-xs text-slate-500 truncate">
                                    {{ $activeDescription }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 sm:gap-3">

                        <!-- Notification Popup: native <details>, tidak bergantung ke JS toggle -->
                        <details class="relative group" data-notification-root>
                            <summary
                                class="list-none relative w-11 h-11 rounded-2xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 hover:text-slate-950 transition flex items-center justify-center cursor-pointer select-none"
                                title="Notifikasi"
                                aria-label="Buka notifikasi">
                                <i data-lucide="bell" class="w-5 h-5"></i>

                                @if($notificationCount > 0)
                                    <span class="absolute -top-1 -right-1 min-w-5 h-5 rounded-full bg-red-500 px-1 text-[10px] font-bold text-white flex items-center justify-center ring-2 ring-white">
                                        {{ $notificationCount > 99 ? '99+' : $notificationCount }}
                                    </span>
                                @endif
                            </summary>

                            <div
                                class="absolute right-0 mt-3 w-[390px] max-w-[calc(100vw-2rem)] overflow-hidden rounded-3xl bg-white shadow-2xl ring-1 ring-slate-200 z-[9999]">

                                <div class="border-b border-slate-200 px-5 py-4 bg-white">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <h3 class="text-base font-extrabold text-slate-950">
                                                Notifikasi
                                            </h3>

                                            <p class="mt-1 text-xs text-slate-500">
                                                Informasi terbaru untuk akun kamu.
                                            </p>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            @if($notificationCount > 0)
                                                <span class="shrink-0 rounded-full bg-red-50 px-3 py-1 text-xs font-bold text-red-600">
                                                    {{ $notificationCount }} baru
                                                </span>
                                            @else
                                                <span class="shrink-0 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-600">
                                                    Aman
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="notification-scroll max-h-[430px] overflow-y-auto divide-y divide-slate-100 bg-white">
                                    @forelse($recentNotifications as $notification)
                                        @php
                                            $meta = $notificationTypeMeta($notification->type ?? 'info');
                                        @endphp

                                        <form method="POST" action="{{ route('notification.read', $notification->id) }}">
                                            @csrf

                                            <button
                                                type="submit"
                                                class="w-full text-left px-5 py-4 transition hover:bg-slate-50 {{ !$notification->is_read ? 'bg-blue-50/50' : 'bg-white' }}">

                                                <div class="flex gap-3">
                                                    <div class="relative shrink-0">
                                                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl border {{ $meta['class'] }}">
                                                            <i data-lucide="{{ $meta['icon'] }}" class="h-5 w-5"></i>
                                                        </div>

                                                        @if(!$notification->is_read)
                                                            <span class="absolute -right-0.5 -top-0.5 h-3 w-3 rounded-full bg-red-500 ring-2 ring-white"></span>
                                                        @endif
                                                    </div>

                                                    <div class="min-w-0 flex-1">
                                                        <div class="flex flex-wrap items-center gap-2">
                                                            <p class="truncate text-sm font-extrabold text-slate-950">
                                                                {{ $notification->title }}
                                                            </p>

                                                            <span class="rounded-full border px-2 py-0.5 text-[10px] font-bold {{ $meta['class'] }}">
                                                                {{ $meta['label'] }}
                                                            </span>

                                                            @if(!$notification->is_read)
                                                                <span class="rounded-full bg-red-500 px-2 py-0.5 text-[10px] font-bold text-white">
                                                                    Baru
                                                                </span>
                                                            @endif
                                                        </div>

                                                        <p class="mt-1 line-clamp-2 text-xs leading-relaxed text-slate-600">
                                                            {{ $notification->message }}
                                                        </p>

                                                        <div class="mt-2 flex flex-wrap items-center gap-2 text-[11px] text-slate-400">
                                                            <span class="inline-flex items-center gap-1">
                                                                <i data-lucide="clock-3" class="h-3 w-3"></i>
                                                                {{ $notification->created_at?->diffForHumans() ?? '-' }}
                                                            </span>

                                                            @if(!empty($notification->url))
                                                                <span class="inline-flex items-center gap-1 font-semibold text-blue-600">
                                                                    <i data-lucide="mouse-pointer-click" class="h-3 w-3"></i>
                                                                    Buka
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </button>
                                        </form>
                                    @empty
                                        <div class="px-5 py-10 text-center">
                                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-3xl bg-slate-100 text-slate-400">
                                                <i data-lucide="bell-off" class="h-6 w-6"></i>
                                            </div>

                                            <h4 class="mt-4 text-sm font-extrabold text-slate-900">
                                                Belum ada notifikasi
                                            </h4>

                                            <p class="mt-1 text-xs leading-relaxed text-slate-500">
                                                Info seperti IKI masuk, IKI disetujui, IKI perlu revisi, atau project baru akan muncul di sini.
                                            </p>
                                        </div>
                                    @endforelse
                                </div>

                                <div class="border-t border-slate-200 bg-slate-50 px-5 py-3">
                                    <div class="flex items-center justify-between gap-3">
                                        @if($notificationCount > 0)
                                            <form method="POST" action="{{ route('notification.readAll') }}">
                                                @csrf

                                                <button
                                                    type="submit"
                                                    class="text-xs font-bold text-emerald-600 hover:text-emerald-700">
                                                    Tandai semua dibaca
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-xs text-slate-400">
                                                Semua sudah dibaca
                                            </span>
                                        @endif

                                        <a href="{{ route('notification.index') }}"
                                            class="text-xs font-bold text-blue-600 hover:text-blue-700">
                                            Lihat semua
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </details>

                        <div class="hidden md:flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-3 py-2">
                            <div class="text-right min-w-0">
                                <div class="max-w-[180px] truncate text-sm font-bold text-slate-950">
                                    {{ $user->name ?? 'User' }}
                                </div>

                                <div class="text-xs text-slate-500">
                                    {{ $roleLabel }}
                                </div>
                            </div>

                            <div class="w-10 h-10 rounded-2xl bg-slate-950 text-white flex items-center justify-center font-bold">
                                {{ $userInitial }}
                            </div>
                        </div>
                    </div>

                </div>
            </header>
        </div>

    </div>

    <div class="pl-20 lg:pl-24 min-h-screen">
        <main class="px-4 py-6 sm:px-6 lg:px-8">
            {{ $slot }}
        </main>
    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.addEventListener('click', (event) => {
            document.querySelectorAll('[data-notification-root][open]').forEach((dropdown) => {
                if (!dropdown.contains(event.target)) {
                    dropdown.removeAttribute('open');
                }
            });
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                document.querySelectorAll('[data-notification-root][open]').forEach((dropdown) => {
                    dropdown.removeAttribute('open');
                });
            }
        });

        if (window.lucide) {
            window.lucide.createIcons();
        }
    });
</script>

</body>
</html>