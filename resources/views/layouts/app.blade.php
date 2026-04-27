<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>BPS Monitoring</title>

    <!-- FONTS -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- VITE (WAJIB BANGET) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased bg-gray-100">

<div class="flex min-h-screen">

    <!-- SIDEBAR -->
    <aside class="w-64 bg-slate-900 text-white p-5">

        <h1 class="text-xl font-bold mb-6">BPS Monitoring</h1>

        <nav class="space-y-2 text-sm">

            <a href="/admin" class="block p-2 rounded hover:bg-slate-700">Dashboard</a>

            <div class="text-gray-400 mt-4">Pengguna</div>

            <a href="/admin/users" class="block p-2 rounded hover:bg-slate-700">Users</a>
            <a href="/admin/team" class="block p-2 rounded hover:bg-slate-700">Team</a>

            <div class="text-gray-400 mt-4">Kelola</div>

            <a href="/admin/iku" class="block p-2 rounded hover:bg-slate-700">IKU</a>
            <a href="/admin/rk-ketua" class="block p-2 rounded hover:bg-slate-700">RK Ketua</a>
            <a href="/admin/project" class="block p-2 rounded hover:bg-slate-700">Project</a>
            <a href="/admin/rk-anggota" class="block p-2 rounded hover:bg-slate-700">RK Anggota</a>
            <a href="/admin/daily-task" class="block p-2 rounded hover:bg-slate-700">Daily Task</a>

        </nav>
    </aside>

    <!-- MAIN -->
    <div class="flex-1 flex flex-col">

        <!-- TOPBAR -->
        <header class="bg-white shadow p-4 flex justify-between items-center">

            <h2 class="font-semibold text-lg">
                {{ $header ?? 'Dashboard' }}
            </h2>

            <div class="flex items-center gap-4">

                <a href="/notifications">
                    🔔
                    <span class="bg-red-500 text-white px-2 rounded-full text-xs">
                        {{ \App\Models\Notification::where('user_id', auth()->id())->where('is_read', false)->count() }}
                    </span>
                </a>

                <span>{{ auth()->user()->name }}</span>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="text-red-500">Logout</button>
                </form>

            </div>

        </header>

        <!-- CONTENT -->
        <main class="p-6">
            {{ $slot }}
        </main>

    </div>

</div>

<script>
let debounceTimer;

function debounceSubmit(form) {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        form.submit();
    }, 500); // delay 500ms
}
</script>

</body>
</html>