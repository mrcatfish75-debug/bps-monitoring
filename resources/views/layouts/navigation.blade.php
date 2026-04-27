<div class="flex">

    <!-- SIDEBAR -->
    <div class="w-64 bg-slate-900 text-white min-h-screen p-5">

        <h1 class="text-xl font-bold mb-6">BPS Monitoring</h1>

        <nav class="space-y-2 text-sm">

            <!-- DASHBOARD -->
            <a href="/admin"
               class="block p-2 rounded hover:bg-slate-700">
               Dashboard
            </a>

            <!-- USER -->
            <div class="text-gray-400 mt-4">Pengguna</div>

            <a href="/admin/users"
               class="block p-2 rounded hover:bg-slate-700">
               Users
            </a>

            <a href="/admin/team"
               class="block p-2 rounded hover:bg-slate-700">
               Team
            </a>

            <!-- KELOLA -->
            <div class="text-gray-400 mt-4">Kelola</div>

            <a href="/admin/iku"
               class="block p-2 rounded hover:bg-slate-700">
               IKU
            </a>

            <a href="/admin/rk-ketua"
               class="block p-2 rounded hover:bg-slate-700">
               RK Ketua
            </a>

            <a href="/admin/project"
               class="block p-2 rounded hover:bg-slate-700">
               Project
            </a>

            <a href="/admin/rk-anggota"
               class="block p-2 rounded hover:bg-slate-700">
               RK Anggota
            </a>

            <a href="/admin/daily-task"
               class="block p-2 rounded hover:bg-slate-700">
               Daily Task
            </a>

            <!-- NOTIF -->
            <div class="text-gray-400 mt-4">Lainnya</div>

            <a href="/notifications"
               class="block p-2 rounded hover:bg-slate-700">
               Notifications
            </a>

        </nav>
    </div>

    <!-- MAIN CONTENT -->
    <div class="flex-1">

        <!-- TOPBAR -->
        <div class="bg-white shadow p-4 flex justify-between">

            <div class="font-semibold">
                Dashboard
            </div>

            <div class="flex items-center gap-4">

                <!-- NOTIF -->
                <a href="/notifications">
                    🔔
                    <span class="bg-red-500 text-white px-2 rounded-full text-xs">
                        {{ \App\Models\Notification::where('user_id', auth()->id())->where('is_read', false)->count() }}
                    </span>
                </a>

                <!-- USER -->
                <span>{{ auth()->user()->name }}</span>

                <!-- LOGOUT -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="text-red-500">Logout</button>
                </form>

            </div>
        </div>

        <!-- CONTENT -->
        <div class="p-6">
            {{ $slot }}
        </div>

    </div>

</div>