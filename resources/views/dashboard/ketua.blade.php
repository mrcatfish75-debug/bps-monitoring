<x-app-layout>

@php
    $pendingReviews = collect($pendingReviews ?? []);
    $pendingIkis = collect($pendingIkis ?? $pendingReviews);

    $projects = collect($projects ?? []);
    $rkKetuas = collect($rkKetuas ?? []);
    $rkAnggotas = collect($rkAnggotas ?? []);
    $ikis = collect($ikis ?? []);
    $recentTasks = collect($recentTasks ?? []);

    $dailyTaskToday = collect($dailyTaskToday ?? []);
        $dailyTaskThisWeek = collect($dailyTaskThisWeek ?? []);
        $aktivitasHariIniByPegawai = collect($aktivitasHariIniByPegawai ?? []);

        $totalTaskHariIniValue = (int) ($totalTaskHariIni ?? $dailyTaskToday->count());
        $pegawaiAktifHariIniValue = (int) ($pegawaiAktifHariIni ?? 0);

        $taskTanpaBukti = collect($taskTanpaBukti ?? []);

        if ($taskTanpaBukti->isEmpty()) {
            $taskTanpaBukti = $dailyTaskThisWeek
                ->filter(function ($task) {
                    return blank($task->evidence_url ?? null);
                })
                ->values();
        }

        $totalTaskTanpaBuktiValue = (int) ($totalTaskTanpaBukti ?? $taskTanpaBukti->count());

        $calendarEventsUrl = url('/calendar/events');

        $getTaskRk = function ($task) {
            return $task->iki?->rkAnggota ?? $task->rkAnggota;
        };

        $getTaskUserName = function ($task) use ($getTaskRk) {
            return $getTaskRk($task)?->user?->name ?? '-';
        };

        $getTaskProjectName = function ($task) use ($getTaskRk) {
            return $getTaskRk($task)?->project?->name ?? '-';
        };

    $memberProjects = collect($memberProjects ?? []);
    $myRkAnggotas = collect($myRkAnggotas ?? []);
    $myIkis = collect($myIkis ?? []);
    $myDailyTasks = collect($myDailyTasks ?? []);

    $safeProgress = min(100, max(0, (int) ($progress ?? 0)));

    $totalRkKetuaValue = (int) ($totalRkKetua ?? $rkKetuas->count());
    $totalProjectValue = (int) ($totalProject ?? $projects->count());
    $totalRkAnggotaValue = (int) ($totalRkAnggota ?? $rkAnggotas->count());
    $totalIkiValue = (int) ($totalIki ?? $ikis->count());
    $totalTaskValue = (int) ($totalTask ?? $recentTasks->count());

    $draftIkiValue = (int) ($draftIki ?? $ikis->where('status', \App\Models\Iki::STATUS_DRAFT)->count());
    $submittedIkiValue = (int) ($submittedIki ?? $ikis->where('status', \App\Models\Iki::STATUS_SUBMITTED)->count());
    $approvedIkiValue = (int) ($approvedIki ?? $ikis->where('status', \App\Models\Iki::STATUS_APPROVED)->count());
    $rejectedIkiValue = (int) ($rejectedIki ?? $ikis->where('status', \App\Models\Iki::STATUS_REJECTED)->count());

    $myTotalProjectValue = (int) ($myTotalProject ?? $memberProjects->count());
    $myTotalRkValue = (int) ($myTotalRk ?? $myRkAnggotas->count());
    $myTotalIkiValue = (int) ($myTotalIki ?? $myIkis->count());
    $myTotalTaskValue = (int) ($myTotalTask ?? $myDailyTasks->count());

    $myDraftIkiValue = (int) ($myDraftIki ?? $myIkis->where('status', \App\Models\Iki::STATUS_DRAFT)->count());
    $mySubmittedIkiValue = (int) ($mySubmittedIki ?? $myIkis->where('status', \App\Models\Iki::STATUS_SUBMITTED)->count());
    $myApprovedIkiValue = (int) ($myApprovedIki ?? $myIkis->where('status', \App\Models\Iki::STATUS_APPROVED)->count());
    $myRejectedIkiValue = (int) ($myRejectedIki ?? $myIkis->where('status', \App\Models\Iki::STATUS_REJECTED)->count());

    $reviewCount = $pendingIkis->count();

    $reviewRate = $totalIkiValue > 0
        ? (int) round(($approvedIkiValue / $totalIkiValue) * 100)
        : 0;

    $myProgress = $myTotalIkiValue > 0
        ? (int) round(($myApprovedIkiValue / $myTotalIkiValue) * 100)
        : 0;

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
            'submitted' => 'Menunggu Review',
            'approved' => 'Disetujui',
            'rejected' => 'Perlu Revisi',
            default => ucfirst($status ?? '-'),
        };
    };

    $reviewAction = null;

    if ($reviewCount > 0) {
        $reviewAction = [
            'type' => 'warning',
            'title' => 'Ada IKI menunggu review',
            'message' => 'Buka halaman Review IKI untuk menyetujui atau menolak IKI anggota project.',
            'url' => url('/ketua/iki'),
            'label' => 'Review IKI',
        ];
    } elseif ($totalProjectValue === 0) {
        $reviewAction = [
            'type' => 'info',
            'title' => 'Belum ada project yang dipimpin',
            'message' => 'Buat project dari RK Ketua agar anggota bisa mulai membuat RK dan IKI.',
            'url' => url('/ketua/project'),
            'label' => 'Buka Project',
        ];
    } else {
        $reviewAction = [
            'type' => 'success',
            'title' => 'Tidak ada review tertunda',
            'message' => 'Semua IKI yang masuk sudah ditangani atau belum ada pengajuan baru.',
            'url' => url('/ketua/iki'),
            'label' => 'Cek Review IKI',
        ];
    }

    $reviewActionClass = match($reviewAction['type']) {
        'success' => 'bg-emerald-50 border-emerald-100 text-emerald-800',
        'warning' => 'bg-amber-50 border-amber-100 text-amber-800',
        default => 'bg-blue-50 border-blue-100 text-blue-800',
    };

    $reviewActionButtonClass = match($reviewAction['type']) {
        'success' => 'bg-emerald-600 hover:bg-emerald-700',
        'warning' => 'bg-amber-600 hover:bg-amber-700',
        default => 'bg-blue-600 hover:bg-blue-700',
    };

    $ikiStatusData = [
        'draft' => $draftIkiValue,
        'submitted' => $submittedIkiValue,
        'approved' => $approvedIkiValue,
        'rejected' => $rejectedIkiValue,
    ];

    $myIkiStatusData = [
        'draft' => $myDraftIkiValue,
        'submitted' => $mySubmittedIkiValue,
        'approved' => $myApprovedIkiValue,
        'rejected' => $myRejectedIkiValue,
    ];

    $projectChartData = $projects->take(10)->map(function ($project) {
        return [
            'name' => \Illuminate\Support\Str::limit($project->name ?? 'Project', 28),
            'progress' => (int) ($project->progress ?? 0),
        ];
    })->values();
@endphp

<div class="space-y-6">

    <!-- ================= HEADER ================= -->
    <section class="overflow-hidden rounded-3xl bg-slate-950 text-white shadow-sm">
        <div class="relative px-6 py-7">
            <div class="absolute inset-y-0 right-0 hidden w-1/2 bg-gradient-to-l from-blue-600/20 to-transparent lg:block"></div>

            <div class="relative flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full bg-emerald-500/15 px-3 py-1 text-xs font-bold text-emerald-200 ring-1 ring-emerald-400/20">
                        <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                        Ketua Tim · Tahun {{ $year ?? date('Y') }}
                    </div>

                    <h1 class="mt-3 text-2xl font-extrabold tracking-tight sm:text-3xl">
                        Dashboard Ketua Tim
                    </h1>

                    <p class="mt-2 max-w-3xl text-sm leading-relaxed text-slate-300">
                        Pantau aktivitas harian anggota pada project yang kamu pimpin, cek IKI yang menunggu review, serta lihat bukti dukung sebelum melakukan approve atau reject.
                    </p>
                </div>

                <form method="GET"
                    class="flex w-full flex-col gap-2 rounded-2xl bg-white/10 p-3 ring-1 ring-white/10 sm:w-auto sm:flex-row sm:items-center">

                    <label for="year" class="px-1 text-xs font-bold uppercase tracking-wide text-slate-300">
                        Tahun
                    </label>

                    <select id="year" name="year"
                        class="w-full rounded-xl border-0 bg-white px-3 py-2 text-sm font-bold text-slate-950 focus:ring-2 focus:ring-emerald-400 sm:w-32">
                        @for($y = date('Y'); $y >= 2020; $y--)
                            <option value="{{ $y }}" {{ ($year ?? date('Y')) == $y ? 'selected' : '' }}>
                                {{ $y }}
                            </option>
                        @endfor
                    </select>

                    <button
                        class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-emerald-500">
                        Terapkan
                    </button>
                </form>
            </div>
        </div>
    </section>

    <!-- ================= ROLE EXPLANATION ================= -->
    <section class="grid gap-4 xl:grid-cols-3">

        <div class="rounded-3xl border border-blue-100 bg-blue-50 p-5 xl:col-span-2">
            <div class="flex items-start gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-white">
                    <i data-lucide="route" class="h-5 w-5"></i>
                </div>

                <div>
                    <h2 class="font-extrabold text-blue-900">
                        Alur Kerja Ketua
                    </h2>

                    <p class="mt-2 text-sm leading-relaxed text-blue-800">
                        Ketua membuat project dari RK Ketua, memantau RK Anggota, lalu melakukan review IKI. Progress utama naik saat IKI anggota disetujui.
                    </p>

                    <div class="mt-4 grid grid-cols-2 gap-2 text-center text-xs font-bold text-blue-700 sm:grid-cols-5">
                        <div class="rounded-2xl bg-white px-3 py-2 ring-1 ring-blue-100">RK Ketua</div>
                        <div class="rounded-2xl bg-white px-3 py-2 ring-1 ring-blue-100">Project</div>
                        <div class="rounded-2xl bg-white px-3 py-2 ring-1 ring-blue-100">RK Anggota</div>
                        <div class="rounded-2xl bg-white px-3 py-2 ring-1 ring-blue-100">Review IKI</div>
                        <div class="rounded-2xl bg-white px-3 py-2 ring-1 ring-blue-100">Progress Naik</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-3xl border {{ $reviewActionClass }} p-5">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-white/70">
                    @if($reviewAction['type'] === 'success')
                        <i data-lucide="check-circle-2" class="h-5 w-5"></i>
                    @elseif($reviewAction['type'] === 'warning')
                        <i data-lucide="alert-triangle" class="h-5 w-5"></i>
                    @else
                        <i data-lucide="info" class="h-5 w-5"></i>
                    @endif
                </div>

                <div>
                    <h2 class="font-extrabold">
                        {{ $reviewAction['title'] }}
                    </h2>

                    <p class="mt-2 text-sm leading-relaxed opacity-90">
                        {{ $reviewAction['message'] }}
                    </p>

                    <a href="{{ $reviewAction['url'] }}"
                        class="mt-4 inline-flex items-center gap-2 rounded-2xl px-4 py-2 text-sm font-bold text-white {{ $reviewActionButtonClass }}">
                        {{ $reviewAction['label'] }}
                        <i data-lucide="arrow-right" class="h-4 w-4"></i>
                    </a>
                </div>
            </div>
        </div>

    </section>

    <!-- ================= DAILY TASK FOCUS SUMMARY ================= -->
<section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">

    <a href="{{ url('/ketua/daily-task') }}"
        class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 hover:ring-emerald-300 transition">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-sm font-extrabold text-slate-500">
                    Aktivitas Hari Ini
                </h2>
                <p class="mt-1 text-xs text-slate-400">
                    Daily Task anggota pada project yang dipimpin.
                </p>
            </div>

            <div class="rounded-2xl bg-emerald-50 p-3 text-emerald-600">
                <i data-lucide="list-checks" class="h-6 w-6"></i>
            </div>
        </div>

        <div class="mt-5 text-5xl font-extrabold text-emerald-600">
            {{ $totalTaskHariIniValue }}
        </div>

        <div class="mt-2 text-sm text-slate-500">
            task tercatat hari ini
        </div>
    </a>

    <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-sm font-extrabold text-slate-500">
                    Anggota Aktif Hari Ini
                </h2>
                <p class="mt-1 text-xs text-slate-400">
                    Anggota yang sudah mengisi Daily Task.
                </p>
            </div>

            <div class="rounded-2xl bg-blue-50 p-3 text-blue-600">
                <i data-lucide="users" class="h-6 w-6"></i>
            </div>
        </div>

        <div class="mt-5 text-5xl font-extrabold text-blue-600">
            {{ $pegawaiAktifHariIniValue }}
        </div>

        <div class="mt-2 text-sm text-slate-500">
            anggota sudah tercatat
        </div>
    </div>

    <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-sm font-extrabold text-slate-500">
                    Task Tanpa Bukti
                </h2>
                <p class="mt-1 text-xs text-slate-400">
                    Aktivitas anggota yang belum melampirkan bukti.
                </p>
            </div>

            <div class="rounded-2xl bg-red-50 p-3 text-red-600">
                <i data-lucide="alert-circle" class="h-6 w-6"></i>
            </div>
        </div>

        <div class="mt-5 text-5xl font-extrabold text-red-600">
            {{ $totalTaskTanpaBuktiValue }}
        </div>

        <div class="mt-2 text-sm text-slate-500">
            perlu dilengkapi
        </div>
    </div>

    <a href="{{ url('/ketua/iki?status=submitted') }}"
        class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 hover:ring-amber-300 transition">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-sm font-extrabold text-slate-500">
                    IKI Menunggu Review
                </h2>
                <p class="mt-1 text-xs text-slate-400">
                    IKI submitted yang perlu dicek Ketua Tim.
                </p>
            </div>

            <div class="rounded-2xl bg-amber-50 p-3 text-amber-600">
                <i data-lucide="file-clock" class="h-6 w-6"></i>
            </div>
        </div>

        <div class="mt-5 text-5xl font-extrabold text-amber-600">
            {{ $reviewCount }}
        </div>

        <div class="mt-2 text-sm text-slate-500">
            perlu keputusan
        </div>
    </a>

</section>

    <!-- ================= AKTIVITAS ANGGOTA HARI INI ================= -->
<section class="grid gap-6 xl:grid-cols-3">

    <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 xl:col-span-2">
        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-lg font-extrabold text-slate-950">
                    Aktivitas Anggota Hari Ini
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    Daftar pekerjaan harian anggota pada project yang kamu pimpin.
                </p>
            </div>

            <a href="{{ url('/ketua/daily-task') }}"
                class="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700">
                Monitoring Daily Task
                <i data-lucide="arrow-right" class="h-4 w-4"></i>
            </a>
        </div>

        <div class="space-y-4">
            @forelse($aktivitasHariIniByPegawai as $group)
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="text-base font-extrabold text-slate-950">
                                {{ data_get($group, 'name', 'Pegawai') }}
                            </div>

                            <div class="text-xs text-slate-500">
                                {{ data_get($group, 'total_task', collect(data_get($group, 'items', []))->count()) }}
                                aktivitas hari ini
                            </div>
                        </div>

                        <span class="inline-flex w-fit rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">
                            Sudah Mengisi
                        </span>
                    </div>

                    <div class="space-y-2">
                        @foreach(collect(data_get($group, 'items', []))->take(4) as $item)
                            <div class="rounded-xl bg-white p-4 ring-1 ring-slate-200">
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                    <div class="min-w-0">
                                        <div class="text-sm font-bold leading-relaxed text-slate-900">
                                            {{ data_get($item, 'activity', '-') }}
                                        </div>

                                        <div class="mt-2 flex flex-wrap gap-2 text-xs">
                                            @if(data_get($item, 'project'))
                                                <span class="rounded-full bg-slate-100 px-3 py-1 font-semibold text-slate-600">
                                                    Project: {{ \Illuminate\Support\Str::limit(data_get($item, 'project'), 45) }}
                                                </span>
                                            @endif

                                            @if(data_get($item, 'iki_status'))
                                                <span class="rounded-full bg-blue-50 px-3 py-1 font-semibold text-blue-700">
                                                    IKI: {{ ucfirst(data_get($item, 'iki_status')) }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="shrink-0">
                                        @if(data_get($item, 'has_evidence'))
                                            <a href="{{ data_get($item, 'evidence_url') }}"
                                                target="_blank"
                                                class="inline-flex rounded-xl bg-emerald-50 px-4 py-2 text-xs font-bold text-emerald-700 ring-1 ring-emerald-100 hover:bg-emerald-100">
                                                Ada Bukti
                                            </a>
                                        @else
                                            <span class="inline-flex rounded-xl bg-red-50 px-4 py-2 text-xs font-bold text-red-700 ring-1 ring-red-100">
                                                Belum Ada Bukti
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-8 text-center">
                    <div class="text-lg font-extrabold text-amber-800">
                        Belum ada aktivitas hari ini
                    </div>
                    <p class="mt-2 text-sm text-amber-700">
                        Belum ada Daily Task anggota pada project yang kamu pimpin hari ini.
                    </p>
                </div>
            @endforelse
        </div>
    </div>

    <div class="rounded-3xl border border-red-100 bg-red-50 p-6 shadow-sm">
        <div class="mb-5">
            <h2 class="text-lg font-extrabold text-red-950">
                Task Tanpa Bukti
            </h2>
            <p class="mt-1 text-sm text-red-700">
                Perlu dicek sebelum IKI anggota disetujui.
            </p>
        </div>

        <div class="max-h-[520px] space-y-3 overflow-y-auto pr-1">
            @forelse($taskTanpaBukti->take(10) as $task)
                <div class="rounded-2xl bg-white p-4 ring-1 ring-red-100">
                    <div class="text-sm font-extrabold text-slate-950">
                        {{ $getTaskUserName($task) }}
                    </div>

                    <div class="mt-2 text-sm leading-relaxed text-slate-700">
                        {{ \Illuminate\Support\Str::limit($task->activity ?? '-', 100) }}
                    </div>

                    <div class="mt-2 text-xs text-slate-500">
                        {{ $task->date ? \Carbon\Carbon::parse($task->date)->format('d M Y') : '-' }}
                        ·
                        {{ $getTaskProjectName($task) }}
                    </div>
                </div>
            @empty
                <div class="rounded-2xl bg-white p-6 text-center ring-1 ring-emerald-100">
                    <div class="font-extrabold text-emerald-700">
                        Aman
                    </div>
                    <p class="mt-1 text-sm text-slate-500">
                        Tidak ada Daily Task tanpa bukti pada data yang ditampilkan.
                    </p>
                </div>
            @endforelse
        </div>
    </div>

</section>

    <!-- ================= SUMMARY CARDS ================= -->
    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">

        <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center justify-between">
                <div class="text-sm font-semibold text-slate-500">RK Ketua</div>
                <div class="rounded-2xl bg-blue-50 p-2 text-blue-600">
                    <i data-lucide="target" class="h-5 w-5"></i>
                </div>
            </div>

            <div class="mt-4 text-3xl font-extrabold text-slate-950">
                {{ $totalRkKetuaValue }}
            </div>

            <div class="mt-1 text-xs text-slate-500">
                RK yang kamu pegang.
            </div>
        </div>

        <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center justify-between">
                <div class="text-sm font-semibold text-slate-500">Project Dipimpin</div>
                <div class="rounded-2xl bg-emerald-50 p-2 text-emerald-600">
                    <i data-lucide="folder-kanban" class="h-5 w-5"></i>
                </div>
            </div>

            <div class="mt-4 text-3xl font-extrabold text-slate-950">
                {{ $totalProjectValue }}
            </div>

            <div class="mt-1 text-xs text-slate-500">
                Project sebagai leader.
            </div>
        </div>

        <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center justify-between">
                <div class="text-sm font-semibold text-slate-500">RK Anggota</div>
                <div class="rounded-2xl bg-violet-50 p-2 text-violet-600">
                    <i data-lucide="clipboard-check" class="h-5 w-5"></i>
                </div>
            </div>

            <div class="mt-4 text-3xl font-extrabold text-slate-950">
                {{ $totalRkAnggotaValue }}
            </div>

            <div class="mt-1 text-xs text-slate-500">
                Wadah kerja anggota.
            </div>
        </div>

        <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center justify-between">
                <div class="text-sm font-semibold text-slate-500">Total IKI</div>
                <div class="rounded-2xl bg-sky-50 p-2 text-sky-600">
                    <i data-lucide="badge-check" class="h-5 w-5"></i>
                </div>
            </div>

            <div class="mt-4 text-3xl font-extrabold text-slate-950">
                {{ $totalIkiValue }}
            </div>

            <div class="mt-1 text-xs text-slate-500">
                Unit approval anggota.
            </div>
        </div>

        <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center justify-between">
                <div class="text-sm font-semibold text-slate-500">Daily Task</div>
                <div class="rounded-2xl bg-amber-50 p-2 text-amber-600">
                    <i data-lucide="list-checks" class="h-5 w-5"></i>
                </div>
            </div>

            <div class="mt-4 text-3xl font-extrabold text-slate-950">
                {{ $totalTaskValue }}
            </div>

            <div class="mt-1 text-xs text-slate-500">
                Aktivitas pendukung IKI.
            </div>
        </div>

    </section>

    <!-- ================= CHARTS ================= -->
    <section class="grid gap-6 xl:grid-cols-3">

        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="mb-5">
                <h2 class="text-base font-extrabold text-slate-950">
                    Status IKI Anggota Project
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    IKI dari project yang kamu pimpin.
                </p>
            </div>

            <div class="mx-auto h-[260px] max-w-[280px]">
                <canvas id="ikiStatusChart"></canvas>
            </div>
        </div>

        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 xl:col-span-2">
            <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-base font-extrabold text-slate-950">
                        Progress Project yang Dipimpin
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Grafik ini menunjukkan progress setiap project yang kamu pimpin.
                    </p>
                </div>

                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">
                    Skala 0–100%
                </span>
            </div>

            <div class="h-[320px]">
                <canvas id="projectProgressChart"></canvas>
            </div>
        </div>

    </section>

    <!-- ================= KALENDER AKTIVITAS TIM ================= -->
<section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
    <div class="mb-6 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h2 class="text-lg font-extrabold text-slate-950">
                Kalender Aktivitas Tim
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                Kalender ini menampilkan Daily Task anggota pada project yang kamu pimpin.
            </p>
        </div>

        <div class="rounded-xl bg-blue-50 px-4 py-3 text-sm font-semibold text-blue-700 ring-1 ring-blue-100">
            Klik tanggal atau aktivitas untuk melihat detail.
        </div>
    </div>

    <div class="mb-5 grid gap-3 md:grid-cols-3">
        <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200">
            <div class="text-sm font-extrabold text-slate-900">
                1. Pilih Tanggal
            </div>
            <p class="mt-1 text-sm text-slate-600">
                Klik tanggal untuk melihat daftar aktivitas anggota.
            </p>
        </div>

        <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200">
            <div class="text-sm font-extrabold text-slate-900">
                2. Cek Aktivitas
            </div>
            <p class="mt-1 text-sm text-slate-600">
                Klik aktivitas untuk melihat detail pekerjaan.
            </p>
        </div>

        <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200">
            <div class="text-sm font-extrabold text-slate-900">
                3. Periksa Bukti
            </div>
            <p class="mt-1 text-sm text-slate-600">
                Pastikan aktivitas memiliki bukti sebelum review IKI.
            </p>
        </div>
    </div>

    <div class="mb-4 flex flex-wrap gap-2 text-sm">
        <span class="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1 font-bold text-emerald-700">
            <span class="h-3 w-3 rounded-full bg-emerald-500"></span>
            Ada bukti
        </span>

        <span class="inline-flex items-center gap-2 rounded-full bg-red-100 px-3 py-1 font-bold text-red-700">
            <span class="h-3 w-3 rounded-full bg-red-500"></span>
            Belum ada bukti
        </span>
    </div>

    <div class="grid gap-6 xl:grid-cols-12">
        <div class="xl:col-span-8">
            <div id="ketuaActivityCalendar"
                class="min-h-[560px] rounded-2xl border border-slate-200 bg-white p-3">
            </div>
        </div>

        <div class="xl:col-span-4">
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <h3 class="text-lg font-extrabold text-slate-950">
                    Detail Kalender
                </h3>

                <p class="mt-1 text-sm text-slate-500">
                    Informasi aktivitas akan tampil setelah tanggal atau aktivitas diklik.
                </p>

                <div class="mt-4 rounded-2xl bg-white p-4 ring-1 ring-slate-200">
                    <div class="text-xs font-bold uppercase tracking-wide text-slate-400">
                        Tanggal Dipilih
                    </div>

                    <div id="ketuaCalendarSelectedDateTitle" class="mt-1 text-base font-extrabold text-slate-950">
                        -
                    </div>

                    <div id="ketuaCalendarSelectedDateCount" class="mt-2 inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">
                        Pilih tanggal
                    </div>
                </div>

                <div class="mt-4 rounded-2xl bg-white p-4 ring-1 ring-slate-200">
                    <div class="mb-3 text-sm font-extrabold text-slate-950">
                        Aktivitas pada Tanggal Ini
                    </div>

                    <div id="ketuaCalendarSelectedDateList" class="space-y-2">
                        <div class="rounded-xl bg-slate-50 p-4 text-sm text-slate-500">
                            Klik salah satu tanggal pada kalender.
                        </div>
                    </div>
                </div>

                <div class="mt-4 rounded-2xl bg-white p-4 ring-1 ring-slate-200">
                    <div class="mb-3 text-sm font-extrabold text-slate-950">
                        Detail Aktivitas
                    </div>

                    <div id="ketuaCalendarEmptyState" class="rounded-xl bg-slate-50 p-4 text-sm text-slate-500">
                        Belum ada aktivitas yang dipilih.
                    </div>

                    <div id="ketuaCalendarDetail" class="hidden space-y-3">
                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="text-xs font-bold uppercase tracking-wide text-slate-400">
                                Anggota
                            </div>
                            <div id="ketuaCalendarPegawai" class="mt-1 text-base font-extrabold text-slate-950">
                                -
                            </div>
                        </div>

                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="text-xs font-bold uppercase tracking-wide text-slate-400">
                                Aktivitas
                            </div>
                            <div id="ketuaCalendarActivity" class="mt-1 text-sm leading-relaxed text-slate-700">
                                -
                            </div>
                        </div>

                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="text-xs font-bold uppercase tracking-wide text-slate-400">
                                Project
                            </div>
                            <div id="ketuaCalendarProject" class="mt-1 text-sm font-semibold text-slate-700">
                                -
                            </div>
                        </div>

                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="text-xs font-bold uppercase tracking-wide text-slate-400">
                                Bukti Dukung
                            </div>
                            <div id="ketuaCalendarEvidence" class="mt-2">
                                -
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

    <!-- ================= IKI MENUNGGU REVIEW ================= -->
    <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-base font-extrabold text-slate-950">
                    IKI Menunggu Review
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Daftar IKI yang sudah disubmit anggota dan perlu keputusanmu.
                </p>
            </div>

            <a href="{{ url('/ketua/iki') }}"
                class="inline-flex items-center gap-2 rounded-2xl bg-blue-600 px-4 py-2 text-sm font-bold text-white hover:bg-blue-700">
                Buka Review IKI
                <i data-lucide="arrow-right" class="h-4 w-4"></i>
            </a>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Project</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Anggota</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">IKI</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Daily Task</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Status</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($pendingIkis->take(8) as $iki)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 text-sm font-semibold text-slate-700">
                                {{ $iki->rkAnggota->project->name ?? '-' }}
                            </td>

                            <td class="px-4 py-3 text-sm text-slate-600">
                                {{ $iki->rkAnggota->user->name ?? '-' }}
                            </td>

                            <td class="px-4 py-3 text-sm text-slate-700">
                                {{ \Illuminate\Support\Str::limit($iki->description ?? '-', 90) }}
                            </td>

                            <td class="px-4 py-3 text-sm text-slate-600">
                                {{ $iki->dailyTasks->count() }} task
                            </td>

                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full border px-3 py-1 text-xs font-bold {{ $statusBadge($iki->status) }}">
                                    {{ $statusLabel($iki->status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">
                                Tidak ada IKI yang menunggu review.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($pendingIkis->count() > 8)
            <div class="mt-3 text-xs text-slate-500">
                Menampilkan 8 dari {{ $pendingIkis->count() }} IKI yang menunggu review.
            </div>
        @endif
    </section>

    <!-- ================= PROJECT DIPIMPIN ================= -->
    <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-base font-extrabold text-slate-950">
                    Project yang Saya Pimpin
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Progress project berasal dari RK Anggota dan IKI yang disetujui.
                </p>
            </div>

            <a href="{{ url('/ketua/project') }}"
                class="inline-flex items-center gap-2 rounded-2xl bg-slate-950 px-4 py-2 text-sm font-bold text-white hover:bg-slate-800">
                Kelola Project
                <i data-lucide="arrow-right" class="h-4 w-4"></i>
            </a>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Project</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">RK Ketua</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Anggota</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">IKI</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Progress</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($projects->take(8) as $project)
                        @php
                            $projectRks = $project->rkAnggotas ?? collect();
                            $projectIkis = $projectRks->flatMap(fn ($rk) => $rk->ikis ?? collect());
                            $projectApprovedIkis = $projectIkis->where('status', \App\Models\Iki::STATUS_APPROVED)->count();
                        @endphp

                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <div class="text-sm font-extrabold text-slate-800">
                                    {{ $project->name }}
                                </div>
                                <div class="mt-1 text-xs text-slate-500">
                                    Tim: {{ $project->team->name ?? '-' }}
                                </div>
                            </td>

                            <td class="px-4 py-3 text-sm text-slate-600">
                                {{ \Illuminate\Support\Str::limit($project->rkKetua->description ?? '-', 70) }}
                            </td>

                            <td class="px-4 py-3 text-sm text-slate-600">
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">
                                    {{ $project->members->count() }} orang
                                </span>

                                @if($project->members->count() > 0)
                                    <div class="mt-1 text-xs text-slate-400">
                                        {{ $project->members->pluck('name')->take(3)->implode(', ') }}
                                        @if($project->members->count() > 3)
                                            ...
                                        @endif
                                    </div>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-sm text-slate-600">
                                {{ $projectApprovedIkis }}/{{ $projectIkis->count() }} disetujui
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex min-w-[150px] items-center gap-3">
                                    <div class="h-2 flex-1 rounded-full bg-slate-100">
                                        <div class="h-2 rounded-full bg-emerald-500"
                                            style="width: {{ min((int) ($project->progress ?? 0), 100) }}%">
                                        </div>
                                    </div>

                                    <div class="w-10 text-right text-xs font-extrabold text-slate-700">
                                        {{ $project->progress ?? 0 }}%
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">
                                Belum ada project yang kamu pimpin pada tahun ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($projects->count() > 8)
            <div class="mt-3 text-xs text-slate-500">
                Menampilkan 8 dari {{ $projects->count() }} project.
            </div>
        @endif
    </section>

    <!-- ================= PEKERJAAN SAYA SEBAGAI ANGGOTA ================= -->
    <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-base font-extrabold text-slate-950">
                    Pekerjaan Saya sebagai Anggota
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Bagian ini dipakai jika kamu menjadi anggota/pelaksana di project lain.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ url('/ketua/rk-anggota?mode=mine') }}"
                    class="inline-flex items-center gap-2 rounded-2xl bg-slate-950 px-4 py-2 text-sm font-bold text-white hover:bg-slate-800">
                    RK Pribadi
                </a>

                <a href="{{ url('/ketua/iki?mode=mine') }}"
                    class="inline-flex items-center gap-2 rounded-2xl bg-blue-600 px-4 py-2 text-sm font-bold text-white hover:bg-blue-700">
                    IKI Pribadi
                </a>

                <a href="{{ url('/ketua/daily-task?mode=mine') }}"
                    class="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700">
                    Daily Task
                </a>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-4">
            <div class="rounded-2xl bg-blue-50 p-4 ring-1 ring-blue-100">
                <div class="text-xs font-bold text-blue-600">Project Saya Ikuti</div>
                <div class="mt-2 text-3xl font-extrabold text-blue-700">{{ $myTotalProjectValue }}</div>
            </div>

            <div class="rounded-2xl bg-violet-50 p-4 ring-1 ring-violet-100">
                <div class="text-xs font-bold text-violet-600">RK Pribadi</div>
                <div class="mt-2 text-3xl font-extrabold text-violet-700">{{ $myTotalRkValue }}</div>
            </div>

            <div class="rounded-2xl bg-emerald-50 p-4 ring-1 ring-emerald-100">
                <div class="text-xs font-bold text-emerald-600">IKI Disetujui</div>
                <div class="mt-2 text-3xl font-extrabold text-emerald-700">{{ $myApprovedIkiValue }}/{{ $myTotalIkiValue }}</div>
            </div>

            <div class="rounded-2xl bg-amber-50 p-4 ring-1 ring-amber-100">
                <div class="text-xs font-bold text-amber-600">Daily Task Saya</div>
                <div class="mt-2 text-3xl font-extrabold text-amber-700">{{ $myTotalTaskValue }}</div>
            </div>
        </div>

        <div class="mt-5 grid gap-6 xl:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm font-bold text-slate-800">Progress Pribadi</div>
                        <div class="mt-1 text-xs text-slate-500">Berdasarkan IKI pribadi yang disetujui.</div>
                    </div>

                    <div class="text-3xl font-extrabold text-emerald-600">
                        {{ $myProgress }}%
                    </div>
                </div>

                <div class="mt-4 h-3 rounded-full bg-slate-100">
                    <div class="h-3 rounded-full bg-emerald-500"
                        style="width: {{ min($myProgress, 100) }}%">
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 p-5 xl:col-span-2">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <div class="text-sm font-bold text-slate-800">Status IKI Pribadi</div>
                        <div class="mt-1 text-xs text-slate-500">Ringkasan pekerjaanmu sebagai pelaksana.</div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                    <div class="rounded-xl bg-slate-50 p-3">
                        <div class="text-xs font-bold text-slate-500">Draft</div>
                        <div class="text-2xl font-extrabold text-slate-700">{{ $myDraftIkiValue }}</div>
                    </div>

                    <div class="rounded-xl bg-blue-50 p-3">
                        <div class="text-xs font-bold text-blue-600">Review</div>
                        <div class="text-2xl font-extrabold text-blue-700">{{ $mySubmittedIkiValue }}</div>
                    </div>

                    <div class="rounded-xl bg-emerald-50 p-3">
                        <div class="text-xs font-bold text-emerald-600">Disetujui</div>
                        <div class="text-2xl font-extrabold text-emerald-700">{{ $myApprovedIkiValue }}</div>
                    </div>

                    <div class="rounded-xl bg-red-50 p-3">
                        <div class="text-xs font-bold text-red-600">Revisi</div>
                        <div class="text-2xl font-extrabold text-red-700">{{ $myRejectedIkiValue }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-5 overflow-x-auto rounded-2xl border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Project</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Ketua Project</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">RK Pribadi</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">IKI</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Progress</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($myRkAnggotas->take(6) as $rk)
                        @php
                            $rkIkis = $rk->ikis ?? collect();
                            $rkApprovedIkis = $rkIkis->where('status', \App\Models\Iki::STATUS_APPROVED)->count();
                        @endphp

                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 text-sm font-semibold text-slate-700">
                                {{ $rk->project->name ?? '-' }}
                            </td>

                            <td class="px-4 py-3 text-sm text-slate-600">
                                {{ $rk->project->leader->name ?? '-' }}
                            </td>

                            <td class="px-4 py-3 text-sm text-slate-700">
                                {{ \Illuminate\Support\Str::limit($rk->description ?? '-', 80) }}
                            </td>

                            <td class="px-4 py-3 text-sm text-slate-600">
                                {{ $rkApprovedIkis }}/{{ $rkIkis->count() }} disetujui
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex min-w-[130px] items-center gap-3">
                                    <div class="h-2 flex-1 rounded-full bg-slate-100">
                                        <div class="h-2 rounded-full bg-emerald-500"
                                            style="width: {{ min((int) ($rk->progress ?? 0), 100) }}%">
                                        </div>
                                    </div>

                                    <div class="w-10 text-right text-xs font-extrabold text-slate-700">
                                        {{ $rk->progress ?? 0 }}%
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">
                                Belum ada RK pribadi pada tahun ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <!-- ================= DAILY TASK TERBARU ================= -->
    <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-base font-extrabold text-slate-950">
                    Daily Task Terbaru dari Project yang Saya Pimpin
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Daily Task adalah catatan aktivitas anggota. Progress utama tetap dari IKI yang disetujui.
                </p>
            </div>

            <a href="{{ url('/ketua/daily-task') }}"
                class="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700">
                Monitoring Daily Task
                <i data-lucide="arrow-right" class="h-4 w-4"></i>
            </a>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Tanggal</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Project</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Anggota</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Aktivitas</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Output</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($recentTasks->take(8) as $task)
                        <tr class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-4 py-3 text-sm font-semibold text-slate-700">
                                {{ $task->date ? \Carbon\Carbon::parse($task->date)->format('d M Y') : '-' }}
                            </td>

                            <td class="px-4 py-3 text-sm text-slate-600">
                                {{ $task->rkAnggota->project->name ?? '-' }}
                            </td>

                            <td class="px-4 py-3 text-sm text-slate-600">
                                {{ $task->rkAnggota->user->name ?? '-' }}
                            </td>

                            <td class="px-4 py-3 text-sm text-slate-700">
                                {{ \Illuminate\Support\Str::limit($task->activity ?? '-', 90) }}
                            </td>

                            <td class="px-4 py-3 text-sm text-slate-600">
                                {{ \Illuminate\Support\Str::limit($task->output ?? '-', 80) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">
                                Belum ada Daily Task terbaru.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const ikiStatusData = @json($ikiStatusData);
    const projectChartData = @json($projectChartData);

    Chart.defaults.font.family = "'Figtree', system-ui, sans-serif";
    Chart.defaults.color = '#64748b';
    Chart.defaults.borderColor = 'rgba(148, 163, 184, 0.18)';

    function emptyPlugin(id, message) {
        return {
            id: id,
            afterDraw(chart) {
                const hasData = chart.data.datasets.some(dataset => {
                    return dataset.data.some(value => Number(value) > 0);
                });

                if (hasData) {
                    return;
                }

                const { ctx, chartArea } = chart;

                if (!chartArea) {
                    return;
                }

                ctx.save();
                ctx.fillStyle = '#94a3b8';
                ctx.font = '600 13px Figtree, sans-serif';
                ctx.textAlign = 'center';
                ctx.fillText(message, (chartArea.left + chartArea.right) / 2, (chartArea.top + chartArea.bottom) / 2);
                ctx.restore();
            }
        }
    }

    const ikiStatusChart = document.getElementById('ikiStatusChart');

    if (ikiStatusChart && typeof Chart !== 'undefined') {
        new Chart(ikiStatusChart, {
            type: 'doughnut',
            data: {
                labels: ['Draft', 'Menunggu Review', 'Disetujui', 'Perlu Revisi'],
                datasets: [{
                    data: [
                        ikiStatusData.draft || 0,
                        ikiStatusData.submitted || 0,
                        ikiStatusData.approved || 0,
                        ikiStatusData.rejected || 0,
                    ],
                    backgroundColor: [
                        'rgba(100, 116, 139, 0.75)',
                        'rgba(37, 99, 235, 0.80)',
                        'rgba(16, 185, 129, 0.80)',
                        'rgba(239, 68, 68, 0.80)',
                    ],
                    borderColor: '#ffffff',
                    borderWidth: 4,
                    hoverOffset: 8,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 10,
                            usePointStyle: true,
                            pointStyle: 'circle',
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: context => `${context.label}: ${context.raw}`
                        }
                    }
                }
            },
            plugins: [emptyPlugin('emptyIkiStatusKetua', 'Belum ada data IKI')]
        });
    }

    const projectProgressChart = document.getElementById('projectProgressChart');

    if (projectProgressChart && typeof Chart !== 'undefined') {
        new Chart(projectProgressChart, {
            type: 'bar',
            data: {
                labels: projectChartData.map(item => item.name),
                datasets: [{
                    label: 'Progress Project (%)',
                    data: projectChartData.map(item => item.progress),
                    borderRadius: 10,
                    borderSkipped: false,
                    backgroundColor: 'rgba(37, 99, 235, 0.70)',
                    borderColor: 'rgba(37, 99, 235, 1)',
                    borderWidth: 1,
                    maxBarThickness: 36,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: projectChartData.length > 5 ? 'y' : 'x',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: context => `Progress: ${context.raw}%`
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        max: projectChartData.length > 5 ? 100 : undefined,
                        ticks: {
                            callback: value => projectChartData.length > 5 ? `${value}%` : value
                        }
                    },
                    y: {
                        beginAtZero: true,
                        max: projectChartData.length > 5 ? undefined : 100,
                        ticks: {
                            callback: value => projectChartData.length > 5 ? value : `${value}%`
                        }
                    }
                }
            },
            plugins: [emptyPlugin('emptyProjectProgressKetua', 'Belum ada data project')]
        });
    }

    if (window.lucide) {
        window.lucide.createIcons();
    }
});
</script>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css">

<style>
    #ketuaActivityCalendar .fc-toolbar-title {
        font-size: 1.35rem;
        font-weight: 800;
        color: #0f172a;
    }

    #ketuaActivityCalendar .fc-button {
        border-radius: 0.75rem;
        font-weight: 700;
        padding: 0.5rem 0.8rem;
    }

    #ketuaActivityCalendar .fc-daygrid-day-number {
        font-weight: 700;
        color: #0f172a;
        padding: 0.5rem;
    }

    #ketuaActivityCalendar .fc-event {
        border-radius: 0.55rem;
        padding: 0.12rem 0.35rem;
        font-weight: 700;
        cursor: pointer;
    }

    #ketuaActivityCalendar .fc-day-today {
        background: #fff7ed !important;
    }

    #ketuaActivityCalendar .fc-daygrid-day:hover {
        background: #f8fafc;
        cursor: pointer;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/locales/id.global.min.js"></script>

<script>
window.addEventListener('load', function () {
    const calendarEl = document.getElementById('ketuaActivityCalendar');
    const calendarEventsUrl = @json($calendarEventsUrl ?? url('/calendar/events'));
    const selectedYear = @json((int) ($year ?? date('Y')));

    let allCalendarEvents = [];
    let calendarEventMap = {};
    let selectedDate = null;

    function escapeHtml(value) {
        if (value === null || value === undefined || value === '') {
            return '-';
        }

        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function formatDateReadable(dateString) {
        if (!dateString) {
            return '-';
        }

        const date = new Date(dateString + 'T00:00:00');

        if (isNaN(date)) {
            return dateString;
        }

        return date.toLocaleDateString('id-ID', {
            weekday: 'long',
            day: '2-digit',
            month: 'long',
            year: 'numeric'
        });
    }

    function getEventDate(eventData) {
        if (!eventData || !eventData.start) {
            return null;
        }

        return String(eventData.start).slice(0, 10);
    }

    function getEvidenceBadge(props) {
        if (props && props.evidence_url) {
            return `
                <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">
                    Ada Bukti
                </span>
            `;
        }

        return `
            <span class="inline-flex rounded-full bg-red-100 px-3 py-1 text-xs font-bold text-red-700">
                Belum Ada Bukti
            </span>
        `;
    }

    function showDetailData(title, props) {
        const emptyState = document.getElementById('ketuaCalendarEmptyState');
        const detail = document.getElementById('ketuaCalendarDetail');

        if (emptyState) {
            emptyState.classList.add('hidden');
        }

        if (detail) {
            detail.classList.remove('hidden');
        }

        document.getElementById('ketuaCalendarPegawai').innerHTML = escapeHtml(props.pegawai || '-');
        document.getElementById('ketuaCalendarActivity').innerHTML = escapeHtml(props.activity || title || '-');
        document.getElementById('ketuaCalendarProject').innerHTML = escapeHtml(props.project || '-');

        const evidenceEl = document.getElementById('ketuaCalendarEvidence');

        if (evidenceEl) {
            if (props.evidence_url) {
                evidenceEl.innerHTML = `
                    <a href="${escapeHtml(props.evidence_url)}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex rounded-xl bg-emerald-50 px-4 py-2 text-sm font-bold text-emerald-700 ring-1 ring-emerald-100 hover:bg-emerald-100">
                        Buka Bukti
                    </a>
                `;
            } else {
                evidenceEl.innerHTML = `
                    <span class="inline-flex rounded-xl bg-red-50 px-4 py-2 text-sm font-bold text-red-700 ring-1 ring-red-100">
                        Belum Ada Bukti
                    </span>
                `;
            }
        }
    }

    function renderSelectedDateList(dateString) {
        selectedDate = dateString;

        const titleEl = document.getElementById('ketuaCalendarSelectedDateTitle');
        const countEl = document.getElementById('ketuaCalendarSelectedDateCount');
        const listEl = document.getElementById('ketuaCalendarSelectedDateList');

        if (titleEl) {
            titleEl.innerHTML = escapeHtml(formatDateReadable(dateString));
        }

        const eventsOnDate = allCalendarEvents.filter(item => getEventDate(item) === dateString);

        if (countEl) {
            countEl.innerHTML = `${eventsOnDate.length} aktivitas tercatat`;
            countEl.className = eventsOnDate.length > 0
                ? 'mt-2 inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700'
                : 'mt-2 inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600';
        }

        if (!listEl) {
            return;
        }

        if (eventsOnDate.length === 0) {
            listEl.innerHTML = `
                <div class="rounded-xl bg-slate-50 p-4 text-sm text-slate-500">
                    Tidak ada aktivitas anggota pada tanggal ini.
                </div>
            `;
            return;
        }

        listEl.innerHTML = eventsOnDate.map(item => {
            const props = item.extendedProps || {};
            const eventId = escapeHtml(item.id);

            return `
                <button type="button"
                    data-ketua-calendar-event-id="${eventId}"
                    class="w-full rounded-xl border border-slate-200 bg-white p-3 text-left hover:border-blue-300 hover:bg-blue-50">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="truncate text-sm font-extrabold text-slate-950">
                                ${escapeHtml(props.pegawai || 'Anggota')}
                            </div>
                            <div class="mt-1 line-clamp-2 text-xs leading-relaxed text-slate-600">
                                ${escapeHtml(props.activity || item.title || '-')}
                            </div>
                        </div>

                        <div class="shrink-0">
                            ${getEvidenceBadge(props)}
                        </div>
                    </div>
                </button>
            `;
        }).join('');

        listEl.querySelectorAll('[data-ketua-calendar-event-id]').forEach(button => {
            button.addEventListener('click', function () {
                const eventId = this.getAttribute('data-ketua-calendar-event-id');
                const eventData = calendarEventMap[eventId];

                if (eventData) {
                    showDetailData(eventData.title, eventData.extendedProps || {});
                }
            });
        });
    }

    if (!calendarEl || !window.FullCalendar) {
        return;
    }

    const today = new Date();
    const currentYear = today.getFullYear();

    const initialDate = selectedYear === currentYear
        ? today.toISOString().slice(0, 10)
        : `${selectedYear}-01-01`;

    selectedDate = initialDate;

    const calendar = new FullCalendar.Calendar(calendarEl, {
        locale: 'id',
        initialView: 'dayGridMonth',
        initialDate: initialDate,
        height: 560,
        firstDay: 1,
        selectable: true,
        dayMaxEvents: 4,
        eventDisplay: 'block',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,listWeek'
        },
        buttonText: {
            today: 'Hari ini',
            month: 'Bulan',
            list: 'List'
        },
        events: function(info, successCallback, failureCallback) {
            const params = new URLSearchParams({
                year: selectedYear,
                start: info.startStr,
                end: info.endStr
            });

            fetch(`${calendarEventsUrl}?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json'
                }
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Endpoint kalender gagal. Status ${response.status}`);
                    }

                    return response.json();
                })
                .then(data => {
                    const normalized = data.map(item => {
                        const props = item.extendedProps || {};
                        const hasEvidence = props.has_evidence === true;

                        return {
                            ...item,
                            backgroundColor: hasEvidence ? '#dcfce7' : '#fee2e2',
                            borderColor: hasEvidence ? '#16a34a' : '#dc2626',
                            textColor: hasEvidence ? '#166534' : '#991b1b',
                        };
                    });

                    allCalendarEvents = normalized;
                    calendarEventMap = {};

                    normalized.forEach(item => {
                        calendarEventMap[item.id] = item;
                    });

                    successCallback(normalized);
                    renderSelectedDateList(selectedDate || initialDate);
                })
                .catch(error => {
                    console.error('Ketua calendar error:', error);
                    failureCallback(error);
                });
        },
        dateClick: function(info) {
            calendar.select(info.dateStr);
            renderSelectedDateList(info.dateStr);
        },
        eventClick: function(info) {
            info.jsEvent.preventDefault();

            const eventDate = info.event.startStr
                ? info.event.startStr.slice(0, 10)
                : selectedDate;

            if (eventDate) {
                calendar.select(eventDate);
                renderSelectedDateList(eventDate);
            }

            showDetailData(info.event.title, info.event.extendedProps || {});
        }
    });

    calendar.render();
    calendar.select(initialDate);
});
</script>

</x-app-layout>