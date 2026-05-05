<x-app-layout>

@php
    $pendingReviews = $pendingReviews ?? collect();
    $projects = $projects ?? collect();
    $recentTasks = $recentTasks ?? collect();

    $memberProjects = $memberProjects ?? collect();
    $myRkAnggotas = $myRkAnggotas ?? collect();
    $myDailyTasks = $myDailyTasks ?? collect();

    $safeProgress = min(100, max(0, $progress ?? 0));
@endphp

<div class="space-y-6">

    <!-- ================= HEADER ================= -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

            <div>
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-blue-100 text-blue-600 flex items-center justify-center">
                        <i data-lucide="layout-dashboard" class="w-6 h-6"></i>
                    </div>

                    <div>
                        <h1 class="text-2xl font-bold">
                            Dashboard Ketua Tim
                        </h1>

                        <p class="text-gray-600 mt-1">
                            Ringkasan pekerjaan, progres project, RK yang perlu direview, dan aktivitas terbaru.
                        </p>
                    </div>
                </div>
            </div>

            <form method="GET" class="flex flex-col sm:flex-row gap-2">
                <select name="year" class="border px-3 py-2 rounded-xl bg-white">
                    @for($y = date('Y'); $y >= 2020; $y--)
                        <option value="{{ $y }}" {{ ($year ?? date('Y')) == $y ? 'selected' : '' }}>
                            {{ $y }}
                        </option>
                    @endfor
                </select>

                <button class="bg-gray-900 text-white px-5 py-2 rounded-xl hover:bg-gray-800">
                    Filter
                </button>
            </form>

        </div>
    </div>


    <!-- ================= ATTENTION STRIP ================= -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        <div class="lg:col-span-2 bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
            <div class="flex items-start justify-between gap-4">

                <div>
                    <div class="text-sm text-gray-500">
                        Perlu Review
                    </div>

                    <div class="mt-2 flex items-end gap-2">
                        <div class="text-4xl font-bold text-blue-600">
                            {{ $pendingReviews->count() }}
                        </div>
                        <div class="text-gray-500 mb-1">
                            RK Anggota submitted
                        </div>
                    </div>

                    <p class="text-sm text-gray-500 mt-2">
                        RK dengan status submitted perlu dicek proses kerjanya melalui Daily Task sebelum disetujui atau ditolak.
                    </p>
                </div>

                <div class="w-12 h-12 rounded-2xl bg-blue-100 text-blue-600 flex items-center justify-center shrink-0">
                    <i data-lucide="file-clock" class="w-6 h-6"></i>
                </div>

            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
            <div class="flex items-start justify-between gap-4">

                <div class="w-full">
                    <div class="text-sm text-gray-500">
                        Progress Rata-rata
                    </div>

                    <div class="text-4xl font-bold mt-2 text-green-600">
                        {{ $safeProgress }}%
                    </div>

                    <div class="w-full bg-gray-200 h-2 rounded mt-4">
                        <div class="bg-green-500 h-2 rounded"
                             style="width: {{ $safeProgress }}%">
                        </div>
                    </div>
                </div>

                <div class="w-12 h-12 rounded-2xl bg-green-100 text-green-600 flex items-center justify-center shrink-0">
                    <i data-lucide="trending-up" class="w-6 h-6"></i>
                </div>

            </div>
        </div>

    </div>


    <!-- ================= SUMMARY CARDS ================= -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-500">RK Ketua</div>
                    <div class="text-3xl font-bold mt-2">
                        {{ $totalRkKetua ?? 0 }}
                    </div>
                </div>

                <div class="w-12 h-12 rounded-2xl bg-blue-100 text-blue-600 flex items-center justify-center">
                    <i data-lucide="target" class="w-6 h-6"></i>
                </div>
            </div>

            <div class="text-xs text-gray-500 mt-3">
                Target kerja ketua pada tahun {{ $year ?? date('Y') }}.
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-500">Project Dipimpin</div>
                    <div class="text-3xl font-bold mt-2">
                        {{ $totalProject ?? 0 }}
                    </div>
                </div>

                <div class="w-12 h-12 rounded-2xl bg-green-100 text-green-600 flex items-center justify-center">
                    <i data-lucide="folder" class="w-6 h-6"></i>
                </div>
            </div>

            <div class="text-xs text-gray-500 mt-3">
                Project turunan dari RK Ketua.
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-500">RK Anggota</div>
                    <div class="text-3xl font-bold mt-2">
                        {{ $totalRkAnggota ?? 0 }}
                    </div>
                </div>

                <div class="w-12 h-12 rounded-2xl bg-purple-100 text-purple-600 flex items-center justify-center">
                    <i data-lucide="clipboard-check" class="w-6 h-6"></i>
                </div>
            </div>

            <div class="text-xs text-gray-500 mt-3">
                Unit kerja anggota di project yang dipimpin.
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-500">Daily Task</div>
                    <div class="text-3xl font-bold mt-2">
                        {{ $totalTask ?? 0 }}
                    </div>
                </div>

                <div class="w-12 h-12 rounded-2xl bg-orange-100 text-orange-600 flex items-center justify-center">
                    <i data-lucide="list-checks" class="w-6 h-6"></i>
                </div>
            </div>

            <div class="text-xs text-gray-500 mt-3">
                Catatan proses kerja dari anggota.
            </div>
        </div>

    </div>


    <!-- ================= STATUS CARDS ================= -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">

        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gray-100 text-gray-600 flex items-center justify-center">
                    <i data-lucide="circle" class="w-5 h-5"></i>
                </div>

                <div>
                    <div class="text-sm text-gray-500">Draft</div>
                    <div class="text-2xl font-bold text-gray-700">
                        {{ $draftRk ?? 0 }}
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center">
                    <i data-lucide="clock" class="w-5 h-5"></i>
                </div>

                <div>
                    <div class="text-sm text-gray-500">Submitted</div>
                    <div class="text-2xl font-bold text-blue-600">
                        {{ $pendingReviews->count() }}
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-green-100 text-green-600 flex items-center justify-center">
                    <i data-lucide="check-circle" class="w-5 h-5"></i>
                </div>

                <div>
                    <div class="text-sm text-gray-500">Approved</div>
                    <div class="text-2xl font-bold text-green-600">
                        {{ $approvedRk ?? 0 }}
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-red-100 text-red-600 flex items-center justify-center">
                    <i data-lucide="x-circle" class="w-5 h-5"></i>
                </div>

                <div>
                    <div class="text-sm text-gray-500">Rejected</div>
                    <div class="text-2xl font-bold text-red-600">
                        {{ $rejectedRk ?? 0 }}
                    </div>
                </div>
            </div>
        </div>

    </div>



    <!-- ================= PEKERJAAN SAYA SEBAGAI ANGGOTA ================= -->
<div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">

    <div class="flex items-center justify-between gap-4 mb-4">
        <div>
            <h2 class="text-lg font-bold">
                Pekerjaan Saya sebagai Anggota
            </h2>
            <p class="text-sm text-gray-500">
                Ringkasan pekerjaan pribadi ketika kamu menjadi anggota/pelaksana di project.
            </p>
        </div>

        <div class="flex gap-2">
            <a href="{{ url('/ketua/rk-anggota?mode=mine') }}"
                class="px-3 py-2 rounded-xl bg-blue-600 text-white text-sm hover:bg-blue-700">
                RK Pribadi Saya
            </a>

            <a href="{{ url('/ketua/daily-task?mode=mine') }}"
                class="px-3 py-2 rounded-xl bg-orange-600 text-white text-sm hover:bg-orange-700">
                Daily Task Saya
            </a>
        </div>
    </div>

    <!-- MINI CARDS PEKERJAAN SAYA -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">

        <div class="p-4 rounded-2xl bg-blue-50 border border-blue-100">
            <div class="text-sm text-blue-600">Project Saya Ikuti</div>
            <div class="text-3xl font-bold mt-2 text-blue-700">
                {{ $myTotalProject ?? 0 }}
            </div>
        </div>

        <div class="p-4 rounded-2xl bg-purple-50 border border-purple-100">
            <div class="text-sm text-purple-600">RK Pribadi Saya</div>
            <div class="text-3xl font-bold mt-2 text-purple-700">
                {{ $myTotalRk ?? 0 }}
            </div>
        </div>

        <div class="p-4 rounded-2xl bg-orange-50 border border-orange-100">
            <div class="text-sm text-orange-600">Daily Task Saya</div>
            <div class="text-3xl font-bold mt-2 text-orange-700">
                {{ $myTotalTask ?? 0 }}
            </div>
        </div>

    </div>

    <!-- STATUS RK PRIBADI -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">

        <div class="p-3 rounded-xl border bg-gray-50">
            <div class="text-xs text-gray-500">Draft</div>
            <div class="text-xl font-bold text-gray-700">
                {{ $myDraftRk ?? 0 }}
            </div>
        </div>

        <div class="p-3 rounded-xl border bg-blue-50">
            <div class="text-xs text-blue-500">Submitted</div>
            <div class="text-xl font-bold text-blue-600">
                {{ $mySubmittedRk ?? 0 }}
            </div>
        </div>

        <div class="p-3 rounded-xl border bg-green-50">
            <div class="text-xs text-green-500">Approved</div>
            <div class="text-xl font-bold text-green-600">
                {{ $myApprovedRk ?? 0 }}
            </div>
        </div>

        <div class="p-3 rounded-xl border bg-red-50">
            <div class="text-xs text-red-500">Rejected</div>
            <div class="text-xl font-bold text-red-600">
                {{ $myRejectedRk ?? 0 }}
            </div>
        </div>

    </div>

    <!-- RK PRIBADI TERBARU -->
    <div class="overflow-x-auto">
        <table class="w-full text-sm">

            <thead class="bg-gray-50 border-y">
                <tr>
                    <th class="text-left p-3">Project</th>
                    <th class="text-left p-3">Ketua Project</th>
                    <th class="text-left p-3">RK Pribadi</th>
                    <th class="text-left p-3">Daily Task</th>
                    <th class="text-left p-3">Status</th>
                </tr>
            </thead>

            <tbody>
            @forelse($myRkAnggotas->take(6) as $rk)
                <tr class="border-b hover:bg-gray-50">

                    <td class="p-3">
                        {{ $rk->project->name ?? '-' }}
                    </td>

                    <td class="p-3">
                        {{ $rk->project->leader->name ?? '-' }}
                    </td>

                    <td class="p-3">
                        {{ $rk->description }}
                    </td>

                    <td class="p-3">
                        <span class="px-2 py-1 rounded bg-gray-100 text-gray-700 text-xs">
                            {{ $rk->dailyTasks->count() }} task
                        </span>
                    </td>

                    <td class="p-3">
                        @php
                            $statusClass = match($rk->status) {
                                'approved' => 'bg-green-100 text-green-700',
                                'submitted' => 'bg-blue-100 text-blue-700',
                                'rejected' => 'bg-red-100 text-red-700',
                                default => 'bg-gray-100 text-gray-700',
                            };
                        @endphp

                        <span class="px-2 py-1 rounded text-xs {{ $statusClass }}">
                            {{ ucfirst($rk->status) }}
                        </span>
                    </td>

                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center py-6 text-gray-500">
                        Belum ada RK pribadi pada tahun ini.
                    </td>
                </tr>
            @endforelse
            </tbody>

        </table>
    </div>

    @if($myRkAnggotas->count() > 6)
        <div class="text-xs text-gray-500 mt-3">
            Menampilkan 6 dari {{ $myRkAnggotas->count() }} RK pribadi.
        </div>
    @endif

</div>


    <!-- ================= RK MENUNGGU REVIEW ================= -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">

        <div class="flex items-center justify-between gap-4 mb-4">
            <div>
                <h2 class="text-lg font-bold">
                    RK Anggota Menunggu Review
                </h2>
                <p class="text-sm text-gray-500">
                    RK yang sudah disubmit anggota dan perlu keputusan ketua.
                </p>
            </div>

            <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center">
                <i data-lucide="file-clock" class="w-5 h-5"></i>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">

                <thead class="bg-gray-50 border-y">
                    <tr>
                        <th class="text-left p-3">Project</th>
                        <th class="text-left p-3">Anggota</th>
                        <th class="text-left p-3">RK Anggota</th>
                        <th class="text-left p-3">Daily Task</th>
                    </tr>
                </thead>

                <tbody>
                @forelse($pendingReviews->take(6) as $rk)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="p-3">
                            {{ $rk->project->name ?? '-' }}
                        </td>

                        <td class="p-3">
                            {{ $rk->user->name ?? '-' }}
                        </td>

                        <td class="p-3">
                            {{ $rk->description }}
                        </td>

                        <td class="p-3">
                            <span class="px-2 py-1 rounded bg-gray-100 text-gray-700 text-xs">
                                {{ $rk->dailyTasks->count() }} task
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-6 text-gray-500">
                            Tidak ada RK Anggota yang menunggu review.
                        </td>
                    </tr>
                @endforelse
                </tbody>

            </table>
        </div>

        @if($pendingReviews->count() > 6)
            <div class="text-xs text-gray-500 mt-3">
                Menampilkan 6 dari {{ $pendingReviews->count() }} RK yang menunggu review.
            </div>
        @endif

    </div>


    <!-- ================= PROJECT SAYA ================= -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">

        <div class="flex items-center justify-between gap-4 mb-4">
            <div>
                <h2 class="text-lg font-bold">
                    Project yang Saya Pimpin
                </h2>
                <p class="text-sm text-gray-500">
                    Ringkasan project aktif berdasarkan RK Ketua pada tahun terpilih.
                </p>
            </div>

            <div class="w-10 h-10 rounded-xl bg-green-100 text-green-600 flex items-center justify-center">
                <i data-lucide="folder" class="w-5 h-5"></i>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">

                <thead class="bg-gray-50 border-y">
                    <tr>
                        <th class="text-left p-3">Project</th>
                        <th class="text-left p-3">Tim</th>
                        <th class="text-left p-3">RK Ketua</th>
                        <th class="text-left p-3">Anggota Project</th>
                        <th class="text-left p-3">Progress</th>
                    </tr>
                </thead>

                <tbody>
                @forelse($projects->take(6) as $project)
                    <tr class="border-b hover:bg-gray-50">

                        <td class="p-3 font-medium">
                            {{ $project->name }}
                        </td>

                        <td class="p-3">
                            {{ $project->team->name ?? '-' }}
                        </td>

                        <td class="p-3">
                            {{ $project->rkKetua->description ?? '-' }}
                        </td>

                        <td class="p-3">
                            <span class="px-2 py-1 rounded bg-gray-100 text-gray-700 text-xs">
                                {{ $project->members->count() }} orang
                            </span>

                            @if($project->members->count() > 0)
                                <div class="text-xs text-gray-500 mt-1">
                                    {{ $project->members->pluck('name')->take(3)->implode(', ') }}
                                    @if($project->members->count() > 3)
                                        ...
                                    @endif
                                </div>
                            @endif
                        </td>

                        <td class="p-3 min-w-[140px]">
                            <div class="w-full bg-gray-200 h-2 rounded">
                                <div class="bg-green-500 h-2 rounded"
                                     style="width: {{ $project->progress }}%">
                                </div>
                            </div>

                            <small>{{ $project->progress }}%</small>
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-6 text-gray-500">
                            Belum ada project yang kamu pimpin pada tahun ini.
                        </td>
                    </tr>
                @endforelse
                </tbody>

            </table>
        </div>

        @if($projects->count() > 6)
            <div class="text-xs text-gray-500 mt-3">
                Menampilkan 6 dari {{ $projects->count() }} project.
            </div>
        @endif

    </div>


    <!-- ================= DAILY TASK TERBARU ================= -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">

        <div class="flex items-center justify-between gap-4 mb-4">
            <div>
                <h2 class="text-lg font-bold">
                     Daily Task Terbaru dari Project yang Saya Pimpin
                </h2>
                <p class="text-sm text-gray-500">
                    Aktivitas terbaru dari RK Anggota di project yang kamu pimpin.
                </p>
            </div>

            <div class="w-10 h-10 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center">
                <i data-lucide="list-checks" class="w-5 h-5"></i>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">

                <thead class="bg-gray-50 border-y">
                    <tr>
                        <th class="text-left p-3">Tanggal</th>
                        <th class="text-left p-3">Project</th>
                        <th class="text-left p-3">Anggota</th>
                        <th class="text-left p-3">Activity</th>
                        <th class="text-left p-3">Output</th>
                    </tr>
                </thead>

                <tbody>
                @forelse($recentTasks->take(8) as $task)
                    <tr class="border-b hover:bg-gray-50">

                        <td class="p-3">
                            {{ $task->date ? \Carbon\Carbon::parse($task->date)->format('d M Y') : '-' }}
                        </td>

                        <td class="p-3">
                            {{ $task->rkAnggota->project->name ?? '-' }}
                        </td>

                        <td class="p-3">
                            {{ $task->rkAnggota->user->name ?? '-' }}
                        </td>

                        <td class="p-3">
                            {{ $task->activity ?? '-' }}
                        </td>

                        <td class="p-3">
                            {{ $task->output ?? '-' }}
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-6 text-gray-500">
                            Belum ada Daily Task terbaru.
                        </td>
                    </tr>
                @endforelse
                </tbody>

            </table>
        </div>

    </div>

</div>

</x-app-layout>