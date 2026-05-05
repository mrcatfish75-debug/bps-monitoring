<x-app-layout>

@php
    $statusBadge = function ($status) {
        return match($status) {
            'draft' => 'bg-gray-100 text-gray-700 border-gray-200',
            'submitted' => 'bg-blue-100 text-blue-700 border-blue-200',
            'approved' => 'bg-green-100 text-green-700 border-green-200',
            'rejected' => 'bg-red-100 text-red-700 border-red-200',
            default => 'bg-gray-100 text-gray-700 border-gray-200',
        };
    };

    $statusLabel = function ($status) {
        return match($status) {
            'draft' => 'Draft',
            'submitted' => 'Submitted',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default => ucfirst($status ?? '-'),
        };
    };

    $progress = $personalProgress ?? 0;
@endphp

<div class="space-y-6">

    <!-- ================= HEADER ================= -->
    <div class="bg-white rounded-2xl shadow-sm border p-6">
        <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    Dashboard Anggota
                </h1>

                <p class="text-gray-500 mt-1">
                    Ringkasan project, RK pribadi, Daily Task, dan status pekerjaanmu.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ url('/anggota/project') }}"
                    class="px-4 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700">
                    Project Saya
                </a>

                <a href="{{ url('/anggota/rk-anggota') }}"
                    class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white">
                    RK Pribadi Saya
                </a>

                <a href="{{ url('/anggota/daily-task') }}"
                    class="px-4 py-2 rounded-lg bg-green-600 hover:bg-green-700 text-white">
                    Daily Task Saya
                </a>
            </div>
        </div>
    </div>

    <!-- ================= RULE + PROGRESS ================= -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">

        <div class="xl:col-span-2 bg-blue-50 border border-blue-100 rounded-2xl p-5">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center font-bold">
                    i
                </div>

                <div>
                    <h2 class="font-semibold text-blue-800">
                        Mode Anggota
                    </h2>

                    <p class="text-sm text-blue-700 mt-2 leading-relaxed">
                        Kamu hanya dapat melihat project yang kamu ikuti, membuat RK pribadi, dan mengisi Daily Task milikmu sendiri. Daily Task hanya bisa ditambah atau diubah selama RK masih berstatus Draft atau Rejected. Setelah RK disubmit atau disetujui, data menjadi read-only.
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white border rounded-2xl p-5 shadow-sm">
            <div class="flex justify-between items-center mb-2">
                <h2 class="font-semibold text-gray-800">
                    Progress Pribadi
                </h2>

                <span class="font-bold text-green-600">
                    {{ $progress }}%
                </span>
            </div>

            <div class="w-full bg-gray-200 rounded-full h-3">
                <div class="bg-green-500 h-3 rounded-full"
                    style="width: {{ $progress }}%">
                </div>
            </div>

            <p class="text-xs text-gray-500 mt-2">
                Dihitung dari RK pribadi yang sudah Approved dibanding total RK pribadi.
            </p>
        </div>

    </div>

    <!-- ================= STAT CARDS ================= -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">

        <div class="bg-white rounded-2xl border shadow-sm p-5">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-500">
                        Project Saya
                    </div>

                    <div class="text-3xl font-bold text-gray-900 mt-2">
                        {{ $totalProjects ?? 0 }}
                    </div>
                </div>

                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl font-bold">
                    P
                </div>
            </div>

            <p class="text-xs text-gray-400 mt-3">
                Project yang kamu ikuti sebagai anggota.
            </p>
        </div>

        <div class="bg-white rounded-2xl border shadow-sm p-5">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-500">
                        RK Pribadi
                    </div>

                    <div class="text-3xl font-bold text-gray-900 mt-2">
                        {{ $totalRk ?? 0 }}
                    </div>
                </div>

                <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl font-bold">
                    R
                </div>
            </div>

            <p class="text-xs text-gray-400 mt-3">
                Total rencana kerja milikmu.
            </p>
        </div>

        <div class="bg-white rounded-2xl border shadow-sm p-5">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-500">
                        Daily Task
                    </div>

                    <div class="text-3xl font-bold text-gray-900 mt-2">
                        {{ $totalDailyTasks ?? 0 }}
                    </div>
                </div>

                <div class="w-12 h-12 rounded-xl bg-green-50 text-green-600 flex items-center justify-center text-xl font-bold">
                    D
                </div>
            </div>

            <p class="text-xs text-gray-400 mt-3">
                Total catatan progres harian.
            </p>
        </div>

        <div class="bg-white rounded-2xl border shadow-sm p-5">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-500">
                        Perlu Daily Task
                    </div>

                    <div class="text-3xl font-bold text-orange-600 mt-2">
                        {{ $needDailyTaskCount ?? 0 }}
                    </div>
                </div>

                <div class="w-12 h-12 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center text-xl font-bold">
                    !
                </div>
            </div>

            <p class="text-xs text-gray-400 mt-3">
                RK Draft/Rejected yang belum punya Daily Task.
            </p>
        </div>

    </div>

    <!-- ================= STATUS RK ================= -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">

        <div class="rounded-2xl border bg-gray-50 p-5">
            <div class="text-sm text-gray-500">
                RK Draft
            </div>

            <div class="text-2xl font-bold text-gray-700 mt-2">
                {{ $draftRk ?? 0 }}
            </div>

            <p class="text-xs text-gray-400 mt-2">
                Masih bisa diedit dan ditambah Daily Task.
            </p>
        </div>

        <div class="rounded-2xl border bg-blue-50 p-5">
            <div class="text-sm text-blue-600">
                RK Submitted
            </div>

            <div class="text-2xl font-bold text-blue-700 mt-2">
                {{ $submittedRk ?? 0 }}
            </div>

            <p class="text-xs text-blue-500 mt-2">
                Menunggu review Ketua Tim.
            </p>
        </div>

        <div class="rounded-2xl border bg-green-50 p-5">
            <div class="text-sm text-green-600">
                RK Approved
            </div>

            <div class="text-2xl font-bold text-green-700 mt-2">
                {{ $approvedRk ?? 0 }}
            </div>

            <p class="text-xs text-green-500 mt-2">
                Sudah disetujui dan terkunci.
            </p>
        </div>

        <div class="rounded-2xl border bg-red-50 p-5">
            <div class="text-sm text-red-600">
                RK Rejected
            </div>

            <div class="text-2xl font-bold text-red-700 mt-2">
                {{ $rejectedRk ?? 0 }}
            </div>

            <p class="text-xs text-red-500 mt-2">
                Perlu diperbaiki dan disubmit ulang.
            </p>
        </div>

    </div>

    <!-- ================= QUICK GUIDANCE ================= -->
    @if(($totalProjects ?? 0) === 0)
        <div class="p-5 rounded-2xl bg-yellow-50 border border-yellow-100 text-yellow-700">
            Kamu belum terdaftar di project mana pun. Hubungi Ketua Tim atau Admin agar kamu ditambahkan sebagai anggota project.
        </div>
    @elseif(($totalRk ?? 0) === 0)
        <div class="p-5 rounded-2xl bg-blue-50 border border-blue-100 text-blue-700">
            Kamu sudah terdaftar di project. Langkah berikutnya: buat RK Pribadi di menu <b>RK Pribadi Saya</b>.
        </div>
    @elseif(($needDailyTaskCount ?? 0) > 0)
        <div class="p-5 rounded-2xl bg-orange-50 border border-orange-100 text-orange-700">
            Ada {{ $needDailyTaskCount }} RK yang belum memiliki Daily Task. Tambahkan Daily Task agar RK bisa disubmit untuk review.
        </div>
    @endif

    <!-- ================= MAIN CONTENT ================= -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        <!-- PROJECT SAYA -->
        <div class="xl:col-span-1 bg-white rounded-2xl border shadow-sm p-5">
            <div class="flex justify-between items-center mb-4">
                <h2 class="font-bold text-gray-900">
                    Project Saya
                </h2>

                <a href="{{ url('/anggota/project') }}"
                    class="text-sm text-blue-600 hover:underline">
                    Lihat semua
                </a>
            </div>

            @forelse(($latestProjects ?? collect())->take(5) as $project)
                <div class="border rounded-xl p-4 mb-3 last:mb-0 hover:bg-gray-50">
                    <div class="font-semibold text-gray-900">
                        {{ $project->name }}
                    </div>

                    <div class="text-sm text-gray-500 mt-1">
                        Tim: {{ $project->team->name ?? '-' }}
                    </div>

                    <div class="text-xs text-gray-400 mt-1">
                        IKU: {{ $project->rkKetua->iku->name ?? '-' }}
                    </div>

                    <div class="text-xs text-gray-400 mt-1">
                        Ketua: {{ $project->leader->name ?? '-' }}
                    </div>
                </div>
            @empty
                <div class="p-4 rounded-xl bg-yellow-50 text-yellow-700 text-sm border border-yellow-100">
                    Belum ada project yang bisa ditampilkan.
                </div>
            @endforelse
        </div>

        <!-- RK TERBARU -->
        <div class="xl:col-span-2 bg-white rounded-2xl border shadow-sm p-5">
            <div class="flex justify-between items-center mb-4">
                <h2 class="font-bold text-gray-900">
                    RK Pribadi Terbaru
                </h2>

                <a href="{{ url('/anggota/rk-anggota') }}"
                    class="text-sm text-blue-600 hover:underline">
                    Lihat semua
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="text-left p-3">Project</th>
                            <th class="text-left p-3">Rencana Kinerja</th>
                            <th class="text-left p-3">Status</th>
                            <th class="text-center p-3">Daily Task</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($latestRk ?? [] as $rk)
                            <tr class="border-b last:border-b-0 hover:bg-gray-50">
                                <td class="p-3">
                                    {{ $rk->project->name ?? '-' }}
                                </td>

                                <td class="p-3">
                                    {{ \Illuminate\Support\Str::limit($rk->description, 80) }}
                                </td>

                                <td class="p-3">
                                    <span class="px-2 py-1 rounded border text-xs font-semibold {{ $statusBadge($rk->status) }}">
                                        {{ $statusLabel($rk->status) }}
                                    </span>
                                </td>

                                <td class="p-3 text-center">
                                    {{ $rk->dailyTasks->count() }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-gray-500">
                                    Belum ada RK Pribadi.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- ================= DAILY TASK TERBARU ================= -->
    <div class="bg-white rounded-2xl border shadow-sm p-5">
        <div class="flex justify-between items-center mb-4">
            <h2 class="font-bold text-gray-900">
                Daily Task Terbaru
            </h2>

            <a href="{{ url('/anggota/daily-task') }}"
                class="text-sm text-blue-600 hover:underline">
                Lihat semua
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="text-left p-3">Tanggal</th>
                        <th class="text-left p-3">Project</th>
                        <th class="text-left p-3">RK</th>
                        <th class="text-left p-3">Aktivitas</th>
                        <th class="text-left p-3">Link Bukti</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($latestTasks ?? [] as $task)
                        <tr class="border-b last:border-b-0 hover:bg-gray-50">
                            <td class="p-3">
                                {{ $task->date ? \Carbon\Carbon::parse($task->date)->format('d M Y') : '-' }}
                            </td>

                            <td class="p-3">
                                {{ $task->rkAnggota->project->name ?? '-' }}
                            </td>

                            <td class="p-3">
                                {{ \Illuminate\Support\Str::limit($task->rkAnggota->description ?? '-', 60) }}
                            </td>

                            <td class="p-3">
                                {{ \Illuminate\Support\Str::limit($task->activity, 80) }}
                            </td>

                            <td class="p-3">
                                @if($task->evidence_url)
                                    <a href="{{ $task->evidence_url }}"
                                        target="_blank"
                                        class="text-blue-600 underline">
                                        Buka Link
                                    </a>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-gray-500">
                                Belum ada Daily Task.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

</x-app-layout>