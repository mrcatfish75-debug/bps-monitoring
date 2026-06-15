<x-app-layout>

@php
    $yearValue = $year ?? date('Y');

    $ikusCollection = collect($ikus ?? []);
    $projectsCollection = collect($projects ?? []);
    $recentTasksCollection = collect($recentTasks ?? []);

    $allRkKetuas = $ikusCollection
        ->flatMap(fn ($iku) => $iku->rkKetuas ?? collect())
        ->values();

    $allProjectsFromIku = $allRkKetuas
        ->flatMap(fn ($rkKetua) => $rkKetua->projects ?? collect())
        ->values();

    $allProjects = $projectsCollection->isNotEmpty()
        ? $projectsCollection
        : $allProjectsFromIku;

    $allRkAnggotas = $allProjects
        ->flatMap(fn ($project) => $project->rkAnggotas ?? collect())
        ->values();

    $allIkis = $allRkAnggotas
        ->flatMap(fn ($rkAnggota) => $rkAnggota->ikis ?? collect())
        ->values();

    $allDailyTasksFromIki = $allIkis
        ->flatMap(fn ($iki) => $iki->dailyTasks ?? collect())
        ->values();

    $totalUserValue = (int) ($totalUser ?? 0);
    $totalTeamValue = (int) ($totalTeam ?? 0);
    $totalIkuValue = (int) ($totalIku ?? $ikusCollection->count());
    $totalProjectValue = (int) ($totalProject ?? $allProjects->count());
    $totalRkAnggotaValue = (int) ($totalRkAnggota ?? $allRkAnggotas->count());
    $totalIkiValue = (int) ($totalIki ?? $allIkis->count());
    $totalTaskValue = (int) ($totalTask ?? $allDailyTasksFromIki->count());

    $draftIkiValue = (int) ($draftIki ?? $allIkis->where('status', \App\Models\Iki::STATUS_DRAFT)->count());
    $submittedIkiValue = (int) ($submittedIki ?? $allIkis->where('status', \App\Models\Iki::STATUS_SUBMITTED)->count());
    $approvedIkiValue = (int) ($approvedIki ?? $allIkis->where('status', \App\Models\Iki::STATUS_APPROVED)->count());
    $rejectedIkiValue = (int) ($rejectedIki ?? $allIkis->where('status', \App\Models\Iki::STATUS_REJECTED)->count());

    $avgIkuProgressValue = (int) ($avgIkuProgress ?? round($ikusCollection->avg(fn ($iku) => (int) ($iku->progress ?? 0)) ?? 0));
    $avgProjectProgressValue = (int) ($avgProgress ?? round($allProjects->avg(fn ($project) => (int) ($project->progress ?? 0)) ?? 0));

    $approvedIkiRate = $totalIkiValue > 0
        ? (int) round(($approvedIkiValue / $totalIkiValue) * 100)
        : 0;

    $completedProjectValue = $allProjects
        ->filter(fn ($project) => (int) ($project->progress ?? 0) >= 100)
        ->count();

    $lowProjectValue = $allProjects
        ->filter(fn ($project) => (int) ($project->progress ?? 0) < 50)
        ->count();

    $projectWithoutIkiValue = $allProjects
        ->filter(function ($project) {
            $rkAnggotas = $project->rkAnggotas ?? collect();
            $ikis = $rkAnggotas->flatMap(fn ($rk) => $rk->ikis ?? collect());

            return $ikis->count() === 0;
        })
        ->count();

    $pendingIkis = $allIkis
        ->where('status', \App\Models\Iki::STATUS_SUBMITTED)
        ->take(8)
        ->values();

    $attentionProjects = $allProjects
        ->filter(function ($project) {
            $rkAnggotas = $project->rkAnggotas ?? collect();
            $ikis = $rkAnggotas->flatMap(fn ($rk) => $rk->ikis ?? collect());

            return (int) ($project->progress ?? 0) < 50
                || $rkAnggotas->count() === 0
                || $ikis->count() === 0;
        })
        ->sortBy(fn ($project) => (int) ($project->progress ?? 0))
        ->take(8)
        ->values();

    $lowProgressIkus = $ikusCollection
        ->sortBy(fn ($iku) => (int) ($iku->progress ?? 0))
        ->take(6)
        ->values();

    $ikuChartData = $ikusCollection
        ->map(function ($iku) {
            return [
                'name' => \Illuminate\Support\Str::limit($iku->name ?? 'IKU', 30),
                'progress' => (int) ($iku->progress ?? 0),
            ];
        })
        ->values();

    $projectChartData = $allProjects
        ->take(12)
        ->map(function ($project) {
            return [
                'name' => \Illuminate\Support\Str::limit($project->name ?? 'Project', 30),
                'progress' => (int) ($project->progress ?? 0),
            ];
        })
        ->values();

    $ikiStatusData = [
        'draft' => $draftIkiValue,
        'submitted' => $submittedIkiValue,
        'approved' => $approvedIkiValue,
        'rejected' => $rejectedIkiValue,
    ];

    $projectByMonthArray = [];
    $taskByMonthArray = [];

    foreach (($projectByMonth ?? collect()) as $month => $total) {
        $projectByMonthArray[(int) $month] = (int) $total;
    }

    foreach (($taskByMonth ?? collect()) as $month => $total) {
        $taskByMonthArray[(int) $month] = (int) $total;
    }

    $adminAction = null;

    if ($submittedIkiValue > 0) {
        $adminAction = [
            'type' => 'warning',
            'title' => 'Ada IKI menunggu review',
            'message' => 'Beberapa IKI sudah disubmit dan perlu ditangani oleh Ketua Tim.',
            'url' => url('/admin/iki'),
            'label' => 'Cek IKI',
        ];
    } elseif ($projectWithoutIkiValue > 0) {
        $adminAction = [
            'type' => 'info',
            'title' => 'Ada project belum punya IKI',
            'message' => 'Project yang belum punya IKI belum bisa menghasilkan progress yang jelas.',
            'url' => url('/admin/project'),
            'label' => 'Cek Project',
        ];
    } elseif ($totalIkuValue === 0) {
        $adminAction = [
            'type' => 'info',
            'title' => 'Belum ada IKU',
            'message' => 'Buat IKU terlebih dahulu agar monitoring tahunan bisa dimulai.',
            'url' => url('/admin/iku'),
            'label' => 'Buat IKU',
        ];
    } else {
        $adminAction = [
            'type' => 'success',
            'title' => 'Sistem terlihat aktif',
            'message' => 'Data utama sudah tersedia. Lanjut pantau progress dan IKI secara berkala.',
            'url' => url('/admin/iku'),
            'label' => 'Lihat IKU',
        ];
    }

    $adminActionClass = match($adminAction['type']) {
        'success' => 'bg-emerald-50 border-emerald-100 text-emerald-800',
        'warning' => 'bg-amber-50 border-amber-100 text-amber-800',
        default => 'bg-blue-50 border-blue-100 text-blue-800',
    };

    $adminActionButtonClass = match($adminAction['type']) {
        'success' => 'bg-emerald-600 hover:bg-emerald-700',
        'warning' => 'bg-amber-600 hover:bg-amber-700',
        default => 'bg-blue-600 hover:bg-blue-700',
    };
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
                        Admin · Monitoring Tahun {{ $yearValue }}
                    </div>

                    <h1 class="mt-3 text-2xl font-extrabold tracking-tight sm:text-3xl">
                        Dashboard Admin
                    </h1>

                    <p class="mt-2 max-w-3xl text-sm leading-relaxed text-slate-300">
                        Pantau kondisi sistem, data pengguna, IKU, project, IKI, dan aktivitas kerja. Progress utama naik dari IKI yang disetujui.
                    </p>
                </div>

                <form method="GET" action="{{ route('admin.dashboard') }}"
                    class="flex w-full flex-col gap-2 rounded-2xl bg-white/10 p-3 ring-1 ring-white/10 sm:w-auto sm:flex-row sm:items-center">

                    <label for="year" class="px-1 text-xs font-bold uppercase tracking-wide text-slate-300">
                        Tahun
                    </label>

                    <input
                        id="year"
                        name="year"
                        type="number"
                        value="{{ $yearValue }}"
                        class="w-full rounded-xl border-0 bg-white px-3 py-2 text-sm font-bold text-slate-950 focus:ring-2 focus:ring-emerald-400 sm:w-28">

                    <button
                        type="submit"
                        class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-emerald-500">
                        Terapkan
                    </button>
                </form>
            </div>
        </div>
    </section>

    <!-- ================= EXPLANATION + ACTION ================= -->
    <section class="grid gap-4 xl:grid-cols-3">

        <div class="rounded-3xl border border-blue-100 bg-blue-50 p-5 xl:col-span-2">
            <div class="flex items-start gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-white">
                    <i data-lucide="route" class="h-5 w-5"></i>
                </div>

                <div>
                    <h2 class="font-extrabold text-blue-900">
                        Alur Sistem Monitoring
                    </h2>

                    <p class="mt-2 text-sm leading-relaxed text-blue-800">
                        Admin menyiapkan data dasar. IKU diturunkan menjadi RK Ketua, lalu Project, RK Anggota, IKI, dan Daily Task.
                        Approval utama dilakukan pada IKI.
                    </p>

                    <div class="mt-4 grid grid-cols-2 gap-2 text-center text-xs font-bold text-blue-700 sm:grid-cols-6">
                        <div class="rounded-2xl bg-white px-3 py-2 ring-1 ring-blue-100">IKU</div>
                        <div class="rounded-2xl bg-white px-3 py-2 ring-1 ring-blue-100">RK Ketua</div>
                        <div class="rounded-2xl bg-white px-3 py-2 ring-1 ring-blue-100">Project</div>
                        <div class="rounded-2xl bg-white px-3 py-2 ring-1 ring-blue-100">RK Anggota</div>
                        <div class="rounded-2xl bg-white px-3 py-2 ring-1 ring-blue-100">IKI</div>
                        <div class="rounded-2xl bg-white px-3 py-2 ring-1 ring-blue-100">Daily Task</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-3xl border {{ $adminActionClass }} p-5">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-white/70">
                    @if($adminAction['type'] === 'success')
                        <i data-lucide="check-circle-2" class="h-5 w-5"></i>
                    @elseif($adminAction['type'] === 'warning')
                        <i data-lucide="alert-triangle" class="h-5 w-5"></i>
                    @else
                        <i data-lucide="info" class="h-5 w-5"></i>
                    @endif
                </div>

                <div>
                    <h2 class="font-extrabold">
                        {{ $adminAction['title'] }}
                    </h2>

                    <p class="mt-2 text-sm leading-relaxed opacity-90">
                        {{ $adminAction['message'] }}
                    </p>

                    <a href="{{ $adminAction['url'] }}"
                        class="mt-4 inline-flex items-center gap-2 rounded-2xl px-4 py-2 text-sm font-bold text-white {{ $adminActionButtonClass }}">
                        {{ $adminAction['label'] }}
                        <i data-lucide="arrow-right" class="h-4 w-4"></i>
                    </a>
                </div>
            </div>
        </div>

    </section>

    <!-- ================= TOP SUMMARY ================= -->
    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">

        <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center justify-between">
                <div class="text-sm font-semibold text-slate-500">Users</div>
                <div class="rounded-2xl bg-slate-50 p-2 text-slate-600">
                    <i data-lucide="users" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-4 text-3xl font-extrabold text-slate-950">{{ $totalUserValue }}</div>
            <div class="mt-1 text-xs text-slate-500">Akun pengguna sistem</div>
        </div>

        <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center justify-between">
                <div class="text-sm font-semibold text-slate-500">Team</div>
                <div class="rounded-2xl bg-blue-50 p-2 text-blue-600">
                    <i data-lucide="building-2" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-4 text-3xl font-extrabold text-slate-950">{{ $totalTeamValue }}</div>
            <div class="mt-1 text-xs text-slate-500">Tim kerja terdaftar</div>
        </div>

        <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center justify-between">
                <div class="text-sm font-semibold text-slate-500">Total IKU</div>
                <div class="rounded-2xl bg-violet-50 p-2 text-violet-600">
                    <i data-lucide="target" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-4 text-3xl font-extrabold text-slate-950">{{ $totalIkuValue }}</div>
            <div class="mt-1 text-xs text-slate-500">Sasaran utama tahun ini</div>
        </div>

        <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center justify-between">
                <div class="text-sm font-semibold text-slate-500">Project</div>
                <div class="rounded-2xl bg-emerald-50 p-2 text-emerald-600">
                    <i data-lucide="folder-kanban" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-4 text-3xl font-extrabold text-slate-950">{{ $totalProjectValue }}</div>
            <div class="mt-1 text-xs text-slate-500">{{ $completedProjectValue }} project selesai</div>
        </div>

        <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center justify-between">
                <div class="text-sm font-semibold text-slate-500">IKI Approved</div>
                <div class="rounded-2xl bg-green-50 p-2 text-green-600">
                    <i data-lucide="badge-check" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-4 text-3xl font-extrabold text-slate-950">{{ $approvedIkiValue }}/{{ $totalIkiValue }}</div>
            <div class="mt-1 text-xs text-slate-500">{{ $approvedIkiRate }}% IKI disetujui</div>
        </div>

        <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center justify-between">
                <div class="text-sm font-semibold text-slate-500">Daily Task</div>
                <div class="rounded-2xl bg-amber-50 p-2 text-amber-600">
                    <i data-lucide="list-checks" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-4 text-3xl font-extrabold text-slate-950">{{ $totalTaskValue }}</div>
            <div class="mt-1 text-xs text-slate-500">Aktivitas pendukung IKI</div>
        </div>

    </section>

    <!-- ================= PROGRESS HEALTH ================= -->
    <section class="grid gap-4 xl:grid-cols-3">

        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-extrabold text-slate-950">Progress IKU</h2>
                    <p class="mt-1 text-sm text-slate-500">Rata-rata capaian IKU.</p>
                </div>
                <div class="rounded-2xl bg-blue-50 p-3 text-blue-600">
                    <i data-lucide="trending-up" class="h-6 w-6"></i>
                </div>
            </div>

            <div class="mt-6 flex items-end gap-2">
                <div class="text-5xl font-extrabold text-blue-600">{{ $avgIkuProgressValue }}%</div>
                <div class="pb-2 text-sm font-semibold text-slate-500">rata-rata</div>
            </div>

            <div class="mt-5 h-4 rounded-full bg-slate-100">
                <div class="h-4 rounded-full bg-blue-500" style="width: {{ min($avgIkuProgressValue, 100) }}%"></div>
            </div>
        </div>

        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-extrabold text-slate-950">Progress Project</h2>
                    <p class="mt-1 text-sm text-slate-500">Rata-rata project aktif.</p>
                </div>
                <div class="rounded-2xl bg-emerald-50 p-3 text-emerald-600">
                    <i data-lucide="activity" class="h-6 w-6"></i>
                </div>
            </div>

            <div class="mt-6 flex items-end gap-2">
                <div class="text-5xl font-extrabold text-emerald-600">{{ $avgProjectProgressValue }}%</div>
                <div class="pb-2 text-sm font-semibold text-slate-500">rata-rata</div>
            </div>

            <div class="mt-5 h-4 rounded-full bg-slate-100">
                <div class="h-4 rounded-full bg-emerald-500" style="width: {{ min($avgProjectProgressValue, 100) }}%"></div>
            </div>
        </div>

        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-extrabold text-slate-950">Perlu Perhatian</h2>
                    <p class="mt-1 text-sm text-slate-500">Project rendah atau belum punya IKI.</p>
                </div>
                <div class="rounded-2xl bg-red-50 p-3 text-red-600">
                    <i data-lucide="alert-triangle" class="h-6 w-6"></i>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-2 gap-3">
                <div class="rounded-2xl bg-red-50 p-4">
                    <div class="text-xs font-bold text-red-600">Project Rendah</div>
                    <div class="mt-2 text-3xl font-extrabold text-red-700">{{ $lowProjectValue }}</div>
                </div>

                <div class="rounded-2xl bg-amber-50 p-4">
                    <div class="text-xs font-bold text-amber-600">Belum Ada IKI</div>
                    <div class="mt-2 text-3xl font-extrabold text-amber-700">{{ $projectWithoutIkiValue }}</div>
                </div>
            </div>
        </div>

    </section>

    <!-- ================= QUICK LINKS ================= -->
    <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-6">
        <a href="{{ url('/admin/users') }}" class="rounded-2xl bg-white p-4 text-sm font-bold text-slate-700 shadow-sm ring-1 ring-slate-200 transition hover:bg-slate-50">
            <i data-lucide="users" class="mb-2 h-5 w-5 text-slate-500"></i>
            Users
        </a>

        <a href="{{ url('/admin/team') }}" class="rounded-2xl bg-white p-4 text-sm font-bold text-slate-700 shadow-sm ring-1 ring-slate-200 transition hover:bg-slate-50">
            <i data-lucide="building-2" class="mb-2 h-5 w-5 text-blue-500"></i>
            Team
        </a>

        <a href="{{ url('/admin/iku') }}" class="rounded-2xl bg-white p-4 text-sm font-bold text-slate-700 shadow-sm ring-1 ring-slate-200 transition hover:bg-slate-50">
            <i data-lucide="target" class="mb-2 h-5 w-5 text-violet-500"></i>
            IKU
        </a>

        <a href="{{ url('/admin/rk-ketua') }}" class="rounded-2xl bg-white p-4 text-sm font-bold text-slate-700 shadow-sm ring-1 ring-slate-200 transition hover:bg-slate-50">
            <i data-lucide="clipboard-list" class="mb-2 h-5 w-5 text-blue-500"></i>
            RK Ketua
        </a>

        <a href="{{ url('/admin/project') }}" class="rounded-2xl bg-white p-4 text-sm font-bold text-slate-700 shadow-sm ring-1 ring-slate-200 transition hover:bg-slate-50">
            <i data-lucide="folder-kanban" class="mb-2 h-5 w-5 text-emerald-500"></i>
            Project
        </a>

        <a href="{{ url('/admin/iki') }}" class="rounded-2xl bg-white p-4 text-sm font-bold text-slate-700 shadow-sm ring-1 ring-slate-200 transition hover:bg-slate-50">
            <i data-lucide="badge-check" class="mb-2 h-5 w-5 text-green-500"></i>
            IKI
        </a>
    </section>

    <!-- ================= MAIN CHARTS ================= -->
    <section class="grid gap-6 xl:grid-cols-3">

        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 xl:col-span-2">
            <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-base font-extrabold text-slate-950">
                        Progress Setiap IKU
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Grafik ini menunjukkan capaian tiap IKU pada tahun terpilih.
                    </p>
                </div>

                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">
                    Skala 0–100%
                </span>
            </div>

            <div class="h-[360px]">
                <canvas id="ikuProgressChart"></canvas>
            </div>
        </div>

        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="mb-5">
                <h2 class="text-base font-extrabold text-slate-950">
                    Status IKI
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    IKI adalah unit approval utama.
                </p>
            </div>

            <div class="mx-auto h-[260px] max-w-[280px]">
                <canvas id="ikiStatusChart"></canvas>
            </div>

            <div class="mt-5 grid grid-cols-2 gap-3 text-sm">
                <div class="rounded-2xl bg-slate-50 p-3">
                    <div class="text-xs font-bold text-slate-500">Draft</div>
                    <div class="mt-1 text-xl font-extrabold text-slate-700">{{ $draftIkiValue }}</div>
                </div>

                <div class="rounded-2xl bg-blue-50 p-3">
                    <div class="text-xs font-bold text-blue-600">Review</div>
                    <div class="mt-1 text-xl font-extrabold text-blue-700">{{ $submittedIkiValue }}</div>
                </div>

                <div class="rounded-2xl bg-emerald-50 p-3">
                    <div class="text-xs font-bold text-emerald-600">Approved</div>
                    <div class="mt-1 text-xl font-extrabold text-emerald-700">{{ $approvedIkiValue }}</div>
                </div>

                <div class="rounded-2xl bg-red-50 p-3">
                    <div class="text-xs font-bold text-red-600">Rejected</div>
                    <div class="mt-1 text-xl font-extrabold text-red-700">{{ $rejectedIkiValue }}</div>
                </div>
            </div>
        </div>

    </section>

    <!-- ================= SECONDARY CHARTS ================= -->
    <section class="grid gap-6 xl:grid-cols-2">

        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="mb-5">
                <h2 class="text-base font-extrabold text-slate-950">
                    Progress Project
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    Progress project berdasarkan RK Anggota dan IKI yang disetujui.
                </p>
            </div>

            <div class="h-[340px]">
                <canvas id="projectProgressChart"></canvas>
            </div>
        </div>

        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="mb-5">
                <h2 class="text-base font-extrabold text-slate-950">
                    Aktivitas Bulanan
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    Project dibuat dan Daily Task tercatat per bulan.
                </p>
            </div>

            <div class="h-[340px]">
                <canvas id="monthlyChart"></canvas>
            </div>
        </div>

    </section>

    <!-- ================= ATTENTION LISTS ================= -->
    <section class="grid gap-6 xl:grid-cols-3">

        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-extrabold text-slate-950">
                        IKU Perlu Perhatian
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        IKU dengan progress paling rendah.
                    </p>
                </div>

                <i data-lucide="alert-triangle" class="h-5 w-5 text-amber-500"></i>
            </div>

            <div class="space-y-3">
                @forelse($lowProgressIkus as $iku)
                    <div class="rounded-2xl border border-slate-200 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="truncate text-sm font-extrabold text-slate-950">
                                    {{ $iku->name }}
                                </div>

                                <div class="mt-1 text-xs text-slate-500">
                                    RK Ketua: {{ ($iku->rkKetuas ?? collect())->count() }}
                                </div>
                            </div>

                            <span class="shrink-0 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700">
                                {{ $iku->progress ?? 0 }}%
                            </span>
                        </div>

                        <div class="mt-3 h-2 rounded-full bg-slate-100">
                            <div class="h-2 rounded-full bg-blue-600" style="width: {{ min((int) ($iku->progress ?? 0), 100) }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-500">
                        Belum ada IKU yang perlu perhatian.
                    </div>
                @endforelse
            </div>
        </div>

        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-extrabold text-slate-950">
                        Project Perlu Perhatian
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Project progress rendah atau belum punya IKI.
                    </p>
                </div>

                <i data-lucide="folder-warning" class="h-5 w-5 text-red-500"></i>
            </div>

            <div class="space-y-3">
                @forelse($attentionProjects as $project)
                    @php
                        $projectRks = $project->rkAnggotas ?? collect();
                        $projectIkis = $projectRks->flatMap(fn ($rk) => $rk->ikis ?? collect());
                        $projectApprovedIkis = $projectIkis->where('status', \App\Models\Iki::STATUS_APPROVED)->count();
                    @endphp

                    <div class="rounded-2xl border border-slate-200 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="truncate text-sm font-extrabold text-slate-950">
                                    {{ $project->name }}
                                </div>

                                <div class="mt-1 text-xs text-slate-500">
                                    IKU: {{ $project->rkKetua->iku->name ?? '-' }}
                                </div>
                            </div>

                            <span class="shrink-0 rounded-full bg-red-50 px-2.5 py-1 text-xs font-bold text-red-700">
                                {{ $project->progress ?? 0 }}%
                            </span>
                        </div>

                        <div class="mt-3 text-xs text-slate-500">
                            IKI Approved: {{ $projectApprovedIkis }}/{{ $projectIkis->count() }}
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-500">
                        Tidak ada project yang perlu perhatian.
                    </div>
                @endforelse
            </div>
        </div>

        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-extrabold text-slate-950">
                        IKI Menunggu Review
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        IKI yang sudah disubmit dan menunggu Ketua.
                    </p>
                </div>

                <i data-lucide="hourglass" class="h-5 w-5 text-blue-500"></i>
            </div>

            <div class="space-y-3">
                @forelse($pendingIkis as $iki)
                    <div class="rounded-2xl border border-slate-200 p-4">
                        <div class="text-sm font-extrabold text-slate-950 line-clamp-2">
                            {{ $iki->description ?? '-' }}
                        </div>

                        <div class="mt-2 text-xs text-slate-500">
                            {{ $iki->rkAnggota->user->name ?? '-' }}
                            · {{ $iki->rkAnggota->project->name ?? '-' }}
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-500">
                        Tidak ada IKI yang menunggu review.
                    </div>
                @endforelse
            </div>
        </div>

    </section>

    <!-- ================= PROJECT TABLE ================= -->
    <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-base font-extrabold text-slate-950">
                    Monitoring Project
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Daftar project tahun {{ $yearValue }} beserta IKU, ketua, RK Anggota, IKI, Daily Task, dan progress.
                </p>
            </div>

            <a href="{{ url('/admin/project') }}"
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
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">IKU</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Ketua</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">RK Anggota</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">IKI</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Daily Task</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Progress</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($allProjects->take(10) as $project)
                        @php
                            $projectRks = $project->rkAnggotas ?? collect();
                            $projectIkis = $projectRks->flatMap(fn ($rk) => $rk->ikis ?? collect());
                            $projectApprovedIkis = $projectIkis->where('status', \App\Models\Iki::STATUS_APPROVED)->count();
                            $projectDailyTasks = $projectIkis->flatMap(fn ($iki) => $iki->dailyTasks ?? collect())->count();
                        @endphp

                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 text-sm font-extrabold text-slate-800">
                                {{ $project->name }}
                            </td>

                            <td class="px-4 py-3 text-sm text-slate-600">
                                {{ $project->rkKetua->iku->name ?? '-' }}
                            </td>

                            <td class="px-4 py-3 text-sm text-slate-600">
                                {{ $project->leader->name ?? '-' }}
                            </td>

                            <td class="px-4 py-3 text-sm text-slate-600">
                                {{ $projectRks->count() }}
                            </td>

                            <td class="px-4 py-3 text-sm text-slate-600">
                                {{ $projectApprovedIkis }}/{{ $projectIkis->count() }} approved
                            </td>

                            <td class="px-4 py-3 text-sm text-slate-600">
                                {{ $projectDailyTasks }}
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
                            <td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500">
                                Belum ada project pada tahun ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($allProjects->count() > 10)
            <div class="mt-3 text-xs text-slate-500">
                Menampilkan 10 dari {{ $allProjects->count() }} project.
            </div>
        @endif
    </section>

    <!-- ================= RECENT TASKS ================= -->
    <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-base font-extrabold text-slate-950">
                    Daily Task Terbaru
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Aktivitas terbaru dari anggota. Daily Task menjadi catatan proses, bukan penentu progress utama.
                </p>
            </div>

            <a href="{{ url('/admin/daily-task') }}"
                class="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700">
                Lihat Daily Task
                <i data-lucide="arrow-right" class="h-4 w-4"></i>
            </a>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Tanggal</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Aktivitas</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Pegawai</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Project</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">RK</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($recentTasksCollection as $task)
                        <tr class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-4 py-3 text-sm font-semibold text-slate-700">
                                {{ $task->date ? \Carbon\Carbon::parse($task->date)->format('d M Y') : '-' }}
                            </td>

                            <td class="px-4 py-3 text-sm text-slate-700">
                                {{ \Illuminate\Support\Str::limit($task->activity ?? '-', 90) }}
                            </td>

                            <td class="px-4 py-3 text-sm text-slate-600">
                                {{ $task->rkAnggota->user->name ?? '-' }}
                            </td>

                            <td class="px-4 py-3 text-sm text-slate-600">
                                {{ $task->rkAnggota->project->name ?? '-' }}
                            </td>

                            <td class="px-4 py-3 text-sm text-slate-600">
                                {{ \Illuminate\Support\Str::limit($task->rkAnggota->description ?? '-', 70) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">
                                Belum ada daily task terbaru.
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
    const ikuProgressData = @json($ikuChartData);
    const projectProgressData = @json($projectChartData);
    const ikiStatusData = @json($ikiStatusData);
    const projectByMonth = @json($projectByMonthArray);
    const taskByMonth = @json($taskByMonthArray);

    Chart.defaults.font.family = "'Figtree', system-ui, sans-serif";
    Chart.defaults.color = '#64748b';
    Chart.defaults.borderColor = 'rgba(148, 163, 184, 0.18)';

    const monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    function emptyPlugin(id, message) {
        return {
            id,
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
        };
    }

    const ikuProgressChart = document.getElementById('ikuProgressChart');

    if (ikuProgressChart && typeof Chart !== 'undefined') {
        new Chart(ikuProgressChart, {
            type: 'bar',
            data: {
                labels: ikuProgressData.map(item => item.name),
                datasets: [{
                    label: 'Progress IKU (%)',
                    data: ikuProgressData.map(item => item.progress),
                    borderRadius: 12,
                    borderSkipped: false,
                    backgroundColor: 'rgba(37, 99, 235, 0.72)',
                    borderColor: 'rgba(37, 99, 235, 1)',
                    borderWidth: 1,
                    maxBarThickness: 44,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: ikuProgressData.length > 6 ? 'y' : 'x',
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
                        max: ikuProgressData.length > 6 ? 100 : undefined,
                        ticks: {
                            callback: value => ikuProgressData.length > 6 ? `${value}%` : value
                        }
                    },
                    y: {
                        beginAtZero: true,
                        max: ikuProgressData.length > 6 ? undefined : 100,
                        ticks: {
                            callback: value => ikuProgressData.length > 6 ? value : `${value}%`
                        }
                    }
                }
            },
            plugins: [emptyPlugin('emptyIkuProgressAdmin', 'Belum ada data IKU')]
        });
    }

    const ikiStatusChart = document.getElementById('ikiStatusChart');

    if (ikiStatusChart && typeof Chart !== 'undefined') {
        new Chart(ikiStatusChart, {
            type: 'doughnut',
            data: {
                labels: ['Draft', 'Menunggu Review', 'Approved', 'Rejected'],
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
            plugins: [emptyPlugin('emptyIkiStatusAdmin', 'Belum ada data IKI')]
        });
    }

    const projectProgressChart = document.getElementById('projectProgressChart');

    if (projectProgressChart && typeof Chart !== 'undefined') {
        new Chart(projectProgressChart, {
            type: 'bar',
            data: {
                labels: projectProgressData.map(item => item.name),
                datasets: [{
                    label: 'Progress Project (%)',
                    data: projectProgressData.map(item => item.progress),
                    borderRadius: 10,
                    borderSkipped: false,
                    backgroundColor: 'rgba(14, 165, 233, 0.70)',
                    borderColor: 'rgba(14, 165, 233, 1)',
                    borderWidth: 1,
                    maxBarThickness: 36,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: projectProgressData.length > 5 ? 'y' : 'x',
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
                        max: projectProgressData.length > 5 ? 100 : undefined,
                        ticks: {
                            callback: value => projectProgressData.length > 5 ? `${value}%` : value
                        }
                    },
                    y: {
                        beginAtZero: true,
                        max: projectProgressData.length > 5 ? undefined : 100,
                        ticks: {
                            callback: value => projectProgressData.length > 5 ? value : `${value}%`
                        }
                    }
                }
            },
            plugins: [emptyPlugin('emptyProjectProgressAdmin', 'Belum ada data Project')]
        });
    }

    const monthlyChart = document.getElementById('monthlyChart');

    if (monthlyChart && typeof Chart !== 'undefined') {
        new Chart(monthlyChart, {
            type: 'line',
            data: {
                labels: monthLabels,
                datasets: [
                    {
                        label: 'Project',
                        data: monthLabels.map((_, index) => Number(projectByMonth[index + 1] || 0)),
                        tension: 0.35,
                        borderColor: 'rgba(37, 99, 235, 1)',
                        backgroundColor: 'rgba(37, 99, 235, 0.12)',
                        fill: true,
                        pointRadius: 3,
                    },
                    {
                        label: 'Daily Task',
                        data: monthLabels.map((_, index) => Number(taskByMonth[index + 1] || 0)),
                        tension: 0.35,
                        borderColor: 'rgba(245, 158, 11, 1)',
                        backgroundColor: 'rgba(245, 158, 11, 0.10)',
                        fill: true,
                        pointRadius: 3,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    tooltip: {
                        callbacks: {
                            label: context => `${context.dataset.label}: ${context.raw}`
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            },
            plugins: [emptyPlugin('emptyMonthlyAdmin', 'Belum ada aktivitas bulanan')]
        });
    }

    if (window.lucide) {
        window.lucide.createIcons();
    }
});
</script>

</x-app-layout>