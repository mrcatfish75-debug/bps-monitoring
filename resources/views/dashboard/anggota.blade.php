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
            'submitted' => 'Menunggu Review',
            'approved' => 'Disetujui',
            'rejected' => 'Perlu Revisi',
            default => ucfirst($status ?? '-'),
        };
    };

    $statusDescription = function ($status) {
        return match($status) {
            'draft' => 'Masih bisa diedit.',
            'submitted' => 'Sedang menunggu keputusan Ketua.',
            'approved' => 'Sudah disetujui dan dihitung selesai.',
            'rejected' => 'Perlu diperbaiki lalu submit ulang.',
            default => '-',
        };
    };

    $progress = (int) ($personalProgress ?? 0);

    $totalProjectsValue = (int) ($totalProjects ?? 0);
    $totalRkValue = (int) ($totalRk ?? 0);
    $totalIkiValue = (int) ($totalIki ?? 0);
    $totalDailyTasksValue = (int) ($totalDailyTasks ?? 0);

    $draftIkiValue = (int) ($draftIki ?? 0);
    $submittedIkiValue = (int) ($submittedIki ?? 0);
    $approvedIkiValue = (int) ($approvedIki ?? 0);
    $rejectedIkiValue = (int) ($rejectedIki ?? 0);

    $needIkiValue = (int) ($needIkiCount ?? 0);
    $needSubmitIkiValue = (int) ($needSubmitIkiCount ?? 0);

    $approvalRate = $totalIkiValue > 0
        ? (int) round(($approvedIkiValue / $totalIkiValue) * 100)
        : 0;

    $latestProjectsCollection = collect($latestProjects ?? []);
    $latestRkCollection = collect($latestRk ?? []);
    $latestIkisCollection = collect($latestIkis ?? []);
    $latestTasksCollection = collect($latestTasks ?? []);

    $ikiStatusData = [
        'draft' => $draftIkiValue,
        'submitted' => $submittedIkiValue,
        'approved' => $approvedIkiValue,
        'rejected' => $rejectedIkiValue,
    ];

    $quickAction = null;

    if ($totalProjectsValue === 0) {
        $quickAction = [
            'type' => 'warning',
            'title' => 'Kamu belum masuk project',
            'message' => 'Hubungi Ketua Tim atau Admin agar kamu ditambahkan ke project.',
            'url' => url('/anggota/project'),
            'label' => 'Cek Project Saya',
        ];
    } elseif ($totalRkValue === 0) {
        $quickAction = [
            'type' => 'info',
            'title' => 'Buat RK Pribadi dulu',
            'message' => 'RK Pribadi adalah wadah pekerjaanmu di dalam project.',
            'url' => url('/anggota/rk-anggota'),
            'label' => 'Buat RK Pribadi',
        ];
    } elseif ($needIkiValue > 0) {
        $quickAction = [
            'type' => 'info',
            'title' => 'Ada RK yang belum punya IKI',
            'message' => 'Buat IKI di bawah RK agar pekerjaan bisa dinilai dan dihitung progress-nya.',
            'url' => url('/anggota/iki'),
            'label' => 'Kelola IKI Saya',
        ];
    } elseif ($needSubmitIkiValue > 0) {
        $quickAction = [
            'type' => 'warning',
            'title' => 'Ada IKI yang perlu disubmit',
            'message' => 'Submit IKI setelah bukti final dan aktivitas pendukung sudah siap.',
            'url' => url('/anggota/iki'),
            'label' => 'Submit IKI',
        ];
    } elseif ($submittedIkiValue > 0) {
        $quickAction = [
            'type' => 'info',
            'title' => 'IKI sedang menunggu review',
            'message' => 'Ketua Tim akan mengecek dan memberi keputusan pada IKI yang kamu submit.',
            'url' => url('/anggota/iki'),
            'label' => 'Lihat Status IKI',
        ];
    } else {
        $quickAction = [
            'type' => 'success',
            'title' => 'Pekerjaanmu terlihat rapi',
            'message' => 'Lanjutkan isi Daily Task dan kelola IKI bila ada pekerjaan baru.',
            'url' => url('/anggota/daily-task'),
            'label' => 'Isi Daily Task',
        ];
    }

    $quickActionClass = match($quickAction['type']) {
        'success' => 'bg-emerald-50 border-emerald-100 text-emerald-800',
        'warning' => 'bg-amber-50 border-amber-100 text-amber-800',
        default => 'bg-blue-50 border-blue-100 text-blue-800',
    };

    $quickActionButtonClass = match($quickAction['type']) {
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
                        Mode Anggota
                    </div>

                    <h1 class="mt-3 text-2xl font-extrabold tracking-tight sm:text-3xl">
                        Dashboard Anggota
                    </h1>

                    <p class="mt-2 max-w-3xl text-sm leading-relaxed text-slate-300">
                        Pantau project, RK Pribadi, IKI, dan Daily Task milikmu. Progress utama dihitung dari IKI yang sudah disetujui Ketua.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                    <a href="{{ url('/anggota/rk-anggota') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-white/10 px-4 py-3 text-sm font-bold text-white ring-1 ring-white/10 transition hover:bg-white/15">
                        <i data-lucide="file-pen-line" class="h-4 w-4"></i>
                        RK Saya
                    </a>

                    <a href="{{ url('/anggota/iki') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-blue-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-blue-500">
                        <i data-lucide="badge-check" class="h-4 w-4"></i>
                        IKI Saya
                    </a>

                    <a href="{{ url('/anggota/daily-task') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-emerald-500">
                        <i data-lucide="list-checks" class="h-4 w-4"></i>
                        Daily Task
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- ================= HOW TO READ ================= -->
    <section class="grid gap-4 xl:grid-cols-3">

        <div class="rounded-3xl border border-blue-100 bg-blue-50 p-5 xl:col-span-2">
            <div class="flex items-start gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-white">
                    <i data-lucide="route" class="h-5 w-5"></i>
                </div>

                <div>
                    <h2 class="font-extrabold text-blue-900">
                        Alur Kerja Kamu
                    </h2>

                    <p class="mt-2 text-sm leading-relaxed text-blue-800">
                        Mulai dari project yang kamu ikuti, buat RK Pribadi, lalu buat IKI. Daily Task dipakai sebagai catatan aktivitas. Setelah siap, submit IKI untuk direview Ketua.
                    </p>

                    <div class="mt-4 grid grid-cols-2 gap-2 text-center text-xs font-bold text-blue-700 sm:grid-cols-5">
                        <div class="rounded-2xl bg-white px-3 py-2 ring-1 ring-blue-100">Project</div>
                        <div class="rounded-2xl bg-white px-3 py-2 ring-1 ring-blue-100">RK Pribadi</div>
                        <div class="rounded-2xl bg-white px-3 py-2 ring-1 ring-blue-100">IKI</div>
                        <div class="rounded-2xl bg-white px-3 py-2 ring-1 ring-blue-100">Daily Task</div>
                        <div class="rounded-2xl bg-white px-3 py-2 ring-1 ring-blue-100">Submit IKI</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-3xl border {{ $quickActionClass }} p-5">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-white/70">
                    @if($quickAction['type'] === 'success')
                        <i data-lucide="check-circle-2" class="h-5 w-5"></i>
                    @elseif($quickAction['type'] === 'warning')
                        <i data-lucide="alert-triangle" class="h-5 w-5"></i>
                    @else
                        <i data-lucide="info" class="h-5 w-5"></i>
                    @endif
                </div>

                <div>
                    <h2 class="font-extrabold">
                        {{ $quickAction['title'] }}
                    </h2>

                    <p class="mt-2 text-sm leading-relaxed opacity-90">
                        {{ $quickAction['message'] }}
                    </p>

                    <a href="{{ $quickAction['url'] }}"
                        class="mt-4 inline-flex items-center gap-2 rounded-2xl px-4 py-2 text-sm font-bold text-white {{ $quickActionButtonClass }}">
                        {{ $quickAction['label'] }}
                        <i data-lucide="arrow-right" class="h-4 w-4"></i>
                    </a>
                </div>
            </div>
        </div>

    </section>

    <!-- ================= PROGRESS + STATUS ================= -->
    <section class="grid gap-4 xl:grid-cols-3">

        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 xl:col-span-1">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-extrabold text-slate-950">
                        Progress Pribadi
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Dihitung dari IKI yang disetujui.
                    </p>
                </div>

                <div class="rounded-2xl bg-emerald-50 p-3 text-emerald-600">
                    <i data-lucide="trending-up" class="h-6 w-6"></i>
                </div>
            </div>

            <div class="mt-6 flex items-end gap-2">
                <div class="text-5xl font-extrabold text-slate-950">
                    {{ $progress }}%
                </div>

                <div class="pb-2 text-sm font-semibold text-slate-500">
                    selesai
                </div>
            </div>

            <div class="mt-5 h-4 rounded-full bg-slate-100">
                <div class="h-4 rounded-full bg-emerald-500"
                    style="width: {{ min($progress, 100) }}%">
                </div>
            </div>

            <p class="mt-3 text-xs leading-relaxed text-slate-500">
                Rumus sederhana: IKI Disetujui ÷ Total IKI × 100%.
            </p>
        </div>

        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 xl:col-span-2">
            <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-base font-extrabold text-slate-950">
                        Status IKI Saya
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        IKI adalah bagian yang dinilai dan disetujui oleh Ketua.
                    </p>
                </div>

                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">
                    {{ $approvedIkiValue }}/{{ $totalIkiValue }} disetujui
                </span>
            </div>

            <div class="grid gap-3 sm:grid-cols-4">
                <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                    <div class="text-xs font-bold text-slate-500">Draft</div>
                    <div class="mt-2 text-3xl font-extrabold text-slate-700">{{ $draftIkiValue }}</div>
                    <div class="mt-1 text-xs text-slate-500">Masih dikerjakan</div>
                </div>

                <div class="rounded-2xl bg-blue-50 p-4 ring-1 ring-blue-100">
                    <div class="text-xs font-bold text-blue-600">Menunggu Review</div>
                    <div class="mt-2 text-3xl font-extrabold text-blue-700">{{ $submittedIkiValue }}</div>
                    <div class="mt-1 text-xs text-blue-600">Sudah disubmit</div>
                </div>

                <div class="rounded-2xl bg-emerald-50 p-4 ring-1 ring-emerald-100">
                    <div class="text-xs font-bold text-emerald-600">Disetujui</div>
                    <div class="mt-2 text-3xl font-extrabold text-emerald-700">{{ $approvedIkiValue }}</div>
                    <div class="mt-1 text-xs text-emerald-600">Masuk progress</div>
                </div>

                <div class="rounded-2xl bg-red-50 p-4 ring-1 ring-red-100">
                    <div class="text-xs font-bold text-red-600">Perlu Revisi</div>
                    <div class="mt-2 text-3xl font-extrabold text-red-700">{{ $rejectedIkiValue }}</div>
                    <div class="mt-1 text-xs text-red-600">Perbaiki lagi</div>
                </div>
            </div>
        </div>

    </section>

    <!-- ================= STAT CARDS ================= -->
    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">

        <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center justify-between">
                <div class="text-sm font-semibold text-slate-500">Project Saya</div>
                <div class="rounded-2xl bg-blue-50 p-2 text-blue-600">
                    <i data-lucide="folder-kanban" class="h-5 w-5"></i>
                </div>
            </div>

            <div class="mt-4 text-3xl font-extrabold text-slate-950">
                {{ $totalProjectsValue }}
            </div>

            <div class="mt-1 text-xs text-slate-500">
                Project yang kamu ikuti.
            </div>
        </div>

        <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center justify-between">
                <div class="text-sm font-semibold text-slate-500">RK Pribadi</div>
                <div class="rounded-2xl bg-violet-50 p-2 text-violet-600">
                    <i data-lucide="file-pen-line" class="h-5 w-5"></i>
                </div>
            </div>

            <div class="mt-4 text-3xl font-extrabold text-slate-950">
                {{ $totalRkValue }}
            </div>

            <div class="mt-1 text-xs text-slate-500">
                Wadah pekerjaanmu.
            </div>
        </div>

        <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center justify-between">
                <div class="text-sm font-semibold text-slate-500">Total IKI</div>
                <div class="rounded-2xl bg-emerald-50 p-2 text-emerald-600">
                    <i data-lucide="badge-check" class="h-5 w-5"></i>
                </div>
            </div>

            <div class="mt-4 text-3xl font-extrabold text-slate-950">
                {{ $totalIkiValue }}
            </div>

            <div class="mt-1 text-xs text-slate-500">
                Target kerja yang dinilai.
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
                {{ $totalDailyTasksValue }}
            </div>

            <div class="mt-1 text-xs text-slate-500">
                Catatan aktivitas harian.
            </div>
        </div>

        <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center justify-between">
                <div class="text-sm font-semibold text-slate-500">Perlu Aksi</div>
                <div class="rounded-2xl bg-red-50 p-2 text-red-600">
                    <i data-lucide="alert-circle" class="h-5 w-5"></i>
                </div>
            </div>

            <div class="mt-4 text-3xl font-extrabold text-red-600">
                {{ $needIkiValue + $needSubmitIkiValue + $rejectedIkiValue }}
            </div>

            <div class="mt-1 text-xs text-slate-500">
                IKI/RK yang perlu dilanjutkan.
            </div>
        </div>

    </section>

    <!-- ================= CHARTS ================= -->
    <section class="grid gap-6 xl:grid-cols-3">

        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 xl:col-span-1">
            <div class="mb-5">
                <h2 class="text-base font-extrabold text-slate-950">
                    Ringkasan IKI
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    Komposisi status IKI milikmu.
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
                        IKI Terbaru
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Cek pekerjaan terbaru dan status review-nya.
                    </p>
                </div>

                <a href="{{ url('/anggota/iki') }}"
                    class="inline-flex items-center gap-2 rounded-2xl bg-blue-600 px-4 py-2 text-sm font-bold text-white hover:bg-blue-700">
                    Lihat Semua IKI
                    <i data-lucide="arrow-right" class="h-4 w-4"></i>
                </a>
            </div>

            <div class="space-y-3">
                @forelse($latestIkisCollection->take(5) as $iki)
                    <div class="rounded-2xl border border-slate-200 p-4 transition hover:bg-slate-50">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="font-extrabold text-slate-950">
                                    {{ \Illuminate\Support\Str::limit($iki->description ?? '-', 100) }}
                                </div>

                                <div class="mt-1 text-sm text-slate-500">
                                    Project: {{ $iki->rkAnggota->project->name ?? '-' }}
                                </div>

                                <div class="mt-1 text-xs text-slate-400">
                                    RK: {{ \Illuminate\Support\Str::limit($iki->rkAnggota->description ?? '-', 80) }}
                                </div>
                            </div>

                            <div class="shrink-0 text-left sm:text-right">
                                <span class="inline-flex rounded-full border px-3 py-1 text-xs font-bold {{ $statusBadge($iki->status) }}">
                                    {{ $statusLabel($iki->status) }}
                                </span>

                                <div class="mt-2 text-xs text-slate-500">
                                    Daily Task: {{ $iki->dailyTasks->count() }}
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl bg-slate-50 p-5 text-sm text-slate-500">
                        Belum ada IKI. Buat IKI dari RK Pribadi agar pekerjaan bisa dinilai.
                    </div>
                @endforelse
            </div>
        </div>

    </section>

    <!-- ================= MAIN CONTENT ================= -->
    <section class="grid gap-6 xl:grid-cols-3">

        <!-- PROJECT SAYA -->
        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-extrabold text-slate-950">
                        Project Saya
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Project yang kamu ikuti.
                    </p>
                </div>

                <a href="{{ url('/anggota/project') }}"
                    class="text-sm font-bold text-blue-600 hover:underline">
                    Semua
                </a>
            </div>

            <div class="space-y-3">
                @forelse($latestProjectsCollection->take(5) as $project)
                    <div class="rounded-2xl border border-slate-200 p-4 transition hover:bg-slate-50">
                        <div class="font-extrabold text-slate-950">
                            {{ $project->name }}
                        </div>

                        <div class="mt-2 grid gap-1 text-sm text-slate-500">
                            <div>Tim: {{ $project->team->name ?? '-' }}</div>
                            <div>Ketua: {{ $project->leader->name ?? '-' }}</div>
                            <div>IKU: {{ $project->rkKetua->iku->name ?? '-' }}</div>
                        </div>

                        <div class="mt-3">
                            <div class="mb-1 flex justify-between text-xs font-bold text-slate-500">
                                <span>Progress Project</span>
                                <span>{{ $project->progress ?? 0 }}%</span>
                            </div>

                            <div class="h-2 rounded-full bg-slate-100">
                                <div class="h-2 rounded-full bg-blue-500"
                                    style="width: {{ min((int) ($project->progress ?? 0), 100) }}%">
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl bg-amber-50 p-5 text-sm text-amber-700 ring-1 ring-amber-100">
                        Belum ada project yang bisa ditampilkan.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- RK PRIBADI -->
        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 xl:col-span-2">
            <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-base font-extrabold text-slate-950">
                        RK Pribadi Terbaru
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        RK adalah wadah kerja. Progressnya berasal dari IKI di bawahnya.
                    </p>
                </div>

                <a href="{{ url('/anggota/rk-anggota') }}"
                    class="inline-flex items-center gap-2 rounded-2xl bg-slate-950 px-4 py-2 text-sm font-bold text-white hover:bg-slate-800">
                    Kelola RK
                    <i data-lucide="arrow-right" class="h-4 w-4"></i>
                </a>
            </div>

            <div class="overflow-x-auto rounded-2xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Project</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">RK Pribadi</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">IKI</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Progress</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($latestRkCollection as $rk)
                            @php
                                $rkIkis = $rk->ikis ?? collect();
                                $rkApprovedIkis = $rkIkis->where('status', \App\Models\Iki::STATUS_APPROVED)->count();
                                $rkTotalIkis = $rkIkis->count();
                            @endphp

                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 text-sm font-semibold text-slate-700">
                                    {{ $rk->project->name ?? '-' }}
                                </td>

                                <td class="px-4 py-3 text-sm text-slate-700">
                                    {{ \Illuminate\Support\Str::limit($rk->description ?? '-', 80) }}
                                </td>

                                <td class="px-4 py-3 text-sm text-slate-600">
                                    {{ $rkApprovedIkis }}/{{ $rkTotalIkis }} disetujui
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
                                <td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500">
                                    Belum ada RK Pribadi.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </section>

    <!-- ================= DAILY TASK TERBARU ================= -->
    <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-base font-extrabold text-slate-950">
                    Daily Task Terbaru
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Daily Task adalah catatan aktivitas harian. Progress utama tetap dihitung dari IKI yang disetujui.
                </p>
            </div>

            <a href="{{ url('/anggota/daily-task') }}"
                class="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700">
                Isi Daily Task
                <i data-lucide="arrow-right" class="h-4 w-4"></i>
            </a>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Tanggal</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Project</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">RK/IKI</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Aktivitas</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Bukti</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($latestTasksCollection as $task)
                        <tr class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-4 py-3 text-sm font-semibold text-slate-700">
                                {{ $task->date ? \Carbon\Carbon::parse($task->date)->format('d M Y') : '-' }}
                            </td>

                            <td class="px-4 py-3 text-sm text-slate-600">
                                {{ $task->rkAnggota->project->name ?? '-' }}
                            </td>

                            <td class="px-4 py-3 text-sm text-slate-600">
                                {{ \Illuminate\Support\Str::limit($task->iki->description ?? $task->rkAnggota->description ?? '-', 60) }}
                            </td>

                            <td class="px-4 py-3 text-sm text-slate-700">
                                {{ \Illuminate\Support\Str::limit($task->activity ?? '-', 80) }}
                            </td>

                            <td class="px-4 py-3 text-sm">
                                @if($task->evidence_url)
                                    <a href="{{ $task->evidence_url }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="font-bold text-blue-600 underline">
                                        Buka
                                    </a>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">
                                Belum ada Daily Task.
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

    const chartElement = document.getElementById('ikiStatusChart');

    if (!chartElement || typeof Chart === 'undefined') {
        return;
    }

    Chart.defaults.font.family = "'Figtree', system-ui, sans-serif";
    Chart.defaults.color = '#64748b';
    Chart.defaults.borderColor = 'rgba(148, 163, 184, 0.18)';

    const emptyPlugin = {
        id: 'emptyIkiStatus',
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
            ctx.fillText('Belum ada data IKI', (chartArea.left + chartArea.right) / 2, (chartArea.top + chartArea.bottom) / 2);
            ctx.restore();
        }
    };

    new Chart(chartElement, {
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
        plugins: [emptyPlugin]
    });

    if (window.lucide) {
        window.lucide.createIcons();
    }
});
</script>

</x-app-layout>