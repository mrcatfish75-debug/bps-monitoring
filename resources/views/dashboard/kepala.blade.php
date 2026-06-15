<x-app-layout>

@php
    $year = $year ?? date('Y');

    $dailyTaskTodayCollection = collect($dailyTaskToday ?? []);
    $pegawaiBelumIsiCollection = collect($pegawaiBelumIsiTask ?? []);
    $pegawaiSudahIsiCollection = collect($pegawaiSudahIsiTask ?? []);
    $taskTanpaBuktiCollection = collect($taskTanpaBukti ?? []);
    $pendingIkisCollection = collect($pendingIkis ?? []);
    $unsubmittedIkisCollection = collect($unsubmittedIkis ?? []);
    $recentTasksCollection = collect($recentTasks ?? []);

    $totalTaskHariIniValue = $totalTaskHariIni ?? $dailyTaskTodayCollection->count();
    $totalSudahIsiTaskValue = $totalSudahIsiTask ?? ($pegawaiAktifHariIni ?? $pegawaiSudahIsiCollection->count());
    $totalBelumIsiTaskValue = $totalBelumIsiTask ?? $pegawaiBelumIsiCollection->count();
    $totalTaskTanpaBuktiValue = $totalTaskTanpaBukti ?? $taskTanpaBuktiCollection->count();
    $pendingApprovalCountValue = $pendingApprovalCount ?? $pendingIkisCollection->count();
    $totalPegawaiAktifValue = $totalPegawaiAktif ?? max(($totalSudahIsiTaskValue + $totalBelumIsiTaskValue), 0);

    $persentaseKepatuhanValue = $persentaseKepatuhan ?? (
        $totalPegawaiAktifValue > 0
            ? round(($totalSudahIsiTaskValue / $totalPegawaiAktifValue) * 100)
            : 0
    );

    $tanggalHariIniLabelValue = $tanggalHariIniLabel ?? \Carbon\Carbon::now()->translatedFormat('l, d F Y');
    $lastUpdatedAtValue = $lastUpdatedAt ?? \Carbon\Carbon::now()->format('H:i');

    $aktivitasGroups = collect($aktivitasHariIniByPegawai ?? []);

    if ($aktivitasGroups->isEmpty() && $dailyTaskTodayCollection->isNotEmpty()) {
        $aktivitasGroups = $dailyTaskTodayCollection
            ->groupBy(function ($task) {
                $rk = $task->iki?->rkAnggota ?? $task->rkAnggota;
                return $rk?->user_id ?? 'unknown';
            })
            ->map(function ($tasks) {
                $firstTask = $tasks->first();
                $firstRk = $firstTask->iki?->rkAnggota ?? $firstTask->rkAnggota;
                $user = $firstRk?->user;

                return [
                    'user_id' => $user?->id,
                    'name' => $user?->name ?? 'Pegawai',
                    'email' => $user?->email,
                    'total_task' => $tasks->count(),
                    'items' => $tasks->map(function ($task) {
                        $rk = $task->iki?->rkAnggota ?? $task->rkAnggota;
                        $project = $rk?->project;
                        $iki = $task->iki;

                        return [
                            'id' => $task->id,
                            'date' => $task->date,
                            'activity' => $task->activity,
                            'project' => $project?->name,
                            'team' => $project?->team?->name,
                            'iki' => $iki?->description,
                            'iki_status' => $iki?->status,
                            'evidence_url' => $task->evidence_url,
                            'has_evidence' => filled($task->evidence_url),
                            'updated_at' => $task->updated_at,
                        ];
                    })->values(),
                ];
            })
            ->sortBy('name')
            ->values();
    }

    $dailyTaskUrl = url('/kepala/daily-task') . '?year=' . $year;
    $dailyTaskSubmittedUrl = url('/kepala/daily-task') . '?year=' . $year . '&status=submitted';
    $dailyTaskNoEvidenceUrl = url('/kepala/daily-task') . '?year=' . $year;
    $calendarEventsUrl = url('/calendar/events');

    $healthColor = $persentaseKepatuhanValue >= 80
        ? 'emerald'
        : ($persentaseKepatuhanValue >= 50 ? 'amber' : 'red');

    $healthText = $persentaseKepatuhanValue >= 80
        ? 'Aman'
        : ($persentaseKepatuhanValue >= 50 ? 'Perlu dipantau' : 'Perlu perhatian');

    $getTaskRk = function ($task) {
        return $task->iki?->rkAnggota ?? $task->rkAnggota;
    };

    $getTaskUserName = function ($task) use ($getTaskRk) {
        return $getTaskRk($task)?->user?->name ?? '-';
    };

    $getTaskProjectName = function ($task) use ($getTaskRk) {
        return $getTaskRk($task)?->project?->name ?? '-';
    };
@endphp

<div class="space-y-6">

    {{-- ================= HEADER ================= --}}
    <section class="rounded-3xl bg-slate-950 text-white shadow-sm overflow-hidden">
        <div class="relative p-6 lg:p-8">
            <div class="absolute inset-y-0 right-0 hidden w-1/2 bg-gradient-to-l from-emerald-500/20 to-transparent lg:block"></div>

            <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full bg-emerald-500/15 px-4 py-1.5 text-sm font-bold text-emerald-200 ring-1 ring-emerald-400/20">
                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                        Monitoring Harian Tahun {{ $year }}
                    </div>

                    <h1 class="mt-4 text-3xl font-extrabold tracking-tight sm:text-4xl">
                        Dashboard Kepala Kantor
                    </h1>

                    <p class="mt-3 max-w-4xl text-base leading-relaxed text-slate-300">
                        Fokus utama halaman ini adalah memantau aktivitas harian pegawai:
                        <b>siapa sudah lapor, siapa belum lapor, siapa mengerjakan apa, dan mana yang belum ada bukti dukung.</b>
                    </p>

                    <div class="mt-4 flex flex-wrap gap-2 text-sm">
                        <span class="rounded-full bg-white/10 px-4 py-2 text-slate-200 ring-1 ring-white/10">
                            {{ $tanggalHariIniLabelValue }}
                        </span>

                        <span class="rounded-full bg-white/10 px-4 py-2 text-slate-200 ring-1 ring-white/10">
                            Update terakhir {{ $lastUpdatedAtValue }}
                        </span>

                        <span class="rounded-full bg-white/10 px-4 py-2 text-slate-200 ring-1 ring-white/10">
                            Auto refresh 60 detik
                        </span>
                    </div>
                </div>

                <form method="GET" action="{{ route('kepala.dashboard') }}"
                    class="w-full rounded-2xl bg-white/10 p-4 ring-1 ring-white/10 sm:w-auto">
                    <label for="year" class="block text-xs font-bold uppercase tracking-wide text-slate-300">
                        Pilih Tahun
                    </label>

                    <div class="mt-2 flex gap-2">
                        <input
                            id="year"
                            name="year"
                            type="number"
                            value="{{ $year }}"
                            class="w-full rounded-xl border-0 bg-white px-4 py-3 text-base font-bold text-slate-950 focus:ring-2 focus:ring-emerald-400 sm:w-32">

                        <button
                            type="submit"
                            class="rounded-xl bg-emerald-600 px-5 py-3 text-base font-bold text-white transition hover:bg-emerald-500">
                            Terapkan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    {{-- ================= STATUS BESAR UNTUK KEPALA ================= --}}
    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">

        <a href="{{ $dailyTaskUrl }}"
            class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200 hover:ring-emerald-300 transition">
            <div class="text-sm font-bold text-slate-500">
                Aktivitas Hari Ini
            </div>
            <div class="mt-3 text-4xl font-extrabold text-slate-950">
                {{ $totalTaskHariIniValue }}
            </div>
            <div class="mt-2 text-sm text-slate-500">
                Task tercatat hari ini
            </div>
        </a>

        <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="text-sm font-bold text-slate-500">
                Sudah Lapor
            </div>
            <div class="mt-3 text-4xl font-extrabold text-emerald-600">
                {{ $totalSudahIsiTaskValue }}
            </div>
            <div class="mt-2 text-sm text-slate-500">
                Pegawai sudah mengisi
            </div>
        </div>

        <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="text-sm font-bold text-slate-500">
                Belum Lapor
            </div>
            <div class="mt-3 text-4xl font-extrabold text-red-600">
                {{ $totalBelumIsiTaskValue }}
            </div>
            <div class="mt-2 text-sm text-slate-500">
                Pegawai belum isi hari ini
            </div>
        </div>

        <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="text-sm font-bold text-slate-500">
                Kepatuhan
            </div>
            <div class="mt-3 text-4xl font-extrabold
                @if($healthColor === 'emerald') text-emerald-600
                @elseif($healthColor === 'amber') text-amber-600
                @else text-red-600
                @endif">
                {{ $persentaseKepatuhanValue }}%
            </div>
            <div class="mt-2 text-sm text-slate-500">
                Status: {{ $healthText }}
            </div>
        </div>

        <a href="{{ $dailyTaskNoEvidenceUrl }}"
            class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200 hover:ring-red-300 transition">
            <div class="text-sm font-bold text-slate-500">
                Tanpa Bukti
            </div>
            <div class="mt-3 text-4xl font-extrabold text-red-600">
                {{ $totalTaskTanpaBuktiValue }}
            </div>
            <div class="mt-2 text-sm text-slate-500">
                Task belum ada bukti
            </div>
        </a>

        <a href="{{ $dailyTaskSubmittedUrl }}"
            class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200 hover:ring-blue-300 transition">
            <div class="text-sm font-bold text-slate-500">
                Menunggu Review
            </div>
            <div class="mt-3 text-4xl font-extrabold text-blue-600">
                {{ $pendingApprovalCountValue }}
            </div>
            <div class="mt-2 text-sm text-slate-500">
                IKI submitted
            </div>
        </a>

    </section>

    {{-- ================= FOKUS UTAMA: SIAPA MENGERJAKAN APA ================= --}}
    <section class="grid gap-6 xl:grid-cols-3">

        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 xl:col-span-2">
            <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h2 class="text-2xl font-extrabold text-slate-950">
                        Hari Ini Siapa Mengerjakan Apa?
                    </h2>
                    <p class="mt-1 text-base text-slate-500">
                        Daftar aktivitas harian pegawai. Dibuat besar dan sederhana agar mudah dipantau.
                    </p>
                </div>

                <a href="{{ $dailyTaskUrl }}"
                    class="inline-flex items-center justify-center rounded-xl bg-slate-950 px-5 py-3 text-sm font-bold text-white hover:bg-slate-800">
                    Buka Detail Daily Task
                </a>
            </div>

            <div class="space-y-4">
                @forelse($aktivitasGroups as $group)
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div class="text-lg font-extrabold text-slate-950">
                                    {{ data_get($group, 'name', 'Pegawai') }}
                                </div>

                                <div class="text-sm text-slate-500">
                                    {{ data_get($group, 'total_task', collect(data_get($group, 'items', []))->count()) }}
                                    aktivitas dicatat hari ini
                                </div>
                            </div>

                            <span class="inline-flex w-fit rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">
                                Sudah Lapor
                            </span>
                        </div>

                        <div class="space-y-3">
                            @foreach(collect(data_get($group, 'items', []))->take(5) as $item)
                                <div class="rounded-xl bg-white p-4 ring-1 ring-slate-200">
                                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                        <div class="min-w-0">
                                            <div class="text-base font-bold leading-relaxed text-slate-900">
                                                {{ data_get($item, 'activity', '-') }}
                                            </div>

                                            <div class="mt-2 flex flex-wrap gap-2 text-xs">
                                                @if(data_get($item, 'project'))
                                                    <span class="rounded-full bg-slate-100 px-3 py-1 font-semibold text-slate-600">
                                                        Project: {{ Str::limit(data_get($item, 'project'), 55) }}
                                                    </span>
                                                @endif

                                                @if(data_get($item, 'team'))
                                                    <span class="rounded-full bg-slate-100 px-3 py-1 font-semibold text-slate-600">
                                                        Tim: {{ Str::limit(data_get($item, 'team'), 35) }}
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
                                                    class="inline-flex rounded-xl bg-emerald-50 px-4 py-2 text-sm font-bold text-emerald-700 ring-1 ring-emerald-100 hover:bg-emerald-100">
                                                    Ada Bukti
                                                </a>
                                            @else
                                                <span class="inline-flex rounded-xl bg-red-50 px-4 py-2 text-sm font-bold text-red-700 ring-1 ring-red-100">
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
                        <div class="text-xl font-extrabold text-amber-800">
                            Belum ada aktivitas hari ini
                        </div>
                        <p class="mt-2 text-sm text-amber-700">
                            Pegawai belum mencatat Daily Task untuk hari ini.
                        </p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- BELUM LAPOR --}}
        <div class="rounded-3xl border border-red-100 bg-red-50 p-6 shadow-sm">
            <div class="mb-5 flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-xl font-extrabold text-red-950">
                        Belum Lapor Hari Ini
                    </h2>
                    <p class="mt-1 text-sm text-red-700">
                        Nama pegawai yang belum mengisi aktivitas harian.
                    </p>
                </div>

                <div class="rounded-2xl bg-white px-4 py-3 text-center ring-1 ring-red-100">
                    <div class="text-2xl font-extrabold text-red-600">
                        {{ $totalBelumIsiTaskValue }}
                    </div>
                    <div class="text-xs font-bold text-red-500">
                        Orang
                    </div>
                </div>
            </div>

            <div class="max-h-[520px] space-y-3 overflow-y-auto pr-1">
                @forelse($pegawaiBelumIsiCollection as $rk)
                    <div class="rounded-2xl bg-white p-4 ring-1 ring-red-100">
                        <div class="text-base font-extrabold text-slate-950">
                            {{ $rk->user->name ?? '-' }}
                        </div>

                        <div class="mt-1 text-sm text-slate-500">
                            Project: {{ $rk->project->name ?? '-' }}
                        </div>

                        @if($rk->project?->team)
                            <div class="mt-1 text-xs text-slate-400">
                                Tim: {{ $rk->project->team->name }}
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="rounded-2xl bg-white p-6 text-center ring-1 ring-emerald-100">
                        <div class="text-lg font-extrabold text-emerald-700">
                            Aman
                        </div>
                        <p class="mt-1 text-sm text-slate-500">
                            Semua pegawai aktif sudah melapor hari ini.
                        </p>
                    </div>
                @endforelse
            </div>
        </div>

    </section>

    {{-- ================= KALENDER AKTIVITAS ================= --}}
<section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h2 class="text-2xl font-extrabold text-slate-950">
                Kalender Aktivitas Harian
            </h2>
            <p class="mt-1 text-base text-slate-500">
                Gunakan kalender ini untuk melihat kegiatan pegawai berdasarkan tanggal.
            </p>
        </div>

        <a href="{{ $dailyTaskUrl }}"
            class="inline-flex items-center justify-center rounded-xl bg-slate-950 px-5 py-3 text-sm font-bold text-white hover:bg-slate-800">
            Buka Daftar Daily Task
        </a>
    </div>

    {{-- PETUNJUK PEMAKAIAN --}}
    <div class="mb-6 rounded-2xl border border-blue-100 bg-blue-50 p-5">
        <div class="mb-4 flex items-start justify-between gap-4">
            <div>
                <h3 class="text-lg font-extrabold text-blue-950">
                    Cara Membaca Kalender
                </h3>
                <p class="mt-1 text-sm text-blue-700">
                    Kalender ini menampilkan aktivitas harian pegawai. Kepala Kantor cukup klik tanggal atau nama aktivitas.
                </p>
            </div>

            <div class="hidden rounded-xl bg-white px-4 py-2 text-sm font-bold text-blue-700 ring-1 ring-blue-100 sm:block">
                Panduan Singkat
            </div>
        </div>

        <div class="grid gap-3 md:grid-cols-3">
            <div class="rounded-xl bg-white p-4 ring-1 ring-blue-100">
                <div class="text-sm font-extrabold text-blue-900">
                    1. Pilih Tanggal
                </div>
                <p class="mt-1 text-sm text-slate-600">
                    Klik kotak tanggal di kalender untuk melihat semua aktivitas pada tanggal tersebut.
                </p>
            </div>

            <div class="rounded-xl bg-white p-4 ring-1 ring-blue-100">
                <div class="text-sm font-extrabold text-blue-900">
                    2. Klik Aktivitas
                </div>
                <p class="mt-1 text-sm text-slate-600">
                    Klik nama pegawai/aktivitas berwarna di kalender untuk membuka detail.
                </p>
            </div>

            <div class="rounded-xl bg-white p-4 ring-1 ring-blue-100">
                <div class="text-sm font-extrabold text-blue-900">
                    3. Cek Bukti
                </div>
                <p class="mt-1 text-sm text-slate-600">
                    Detail aktivitas dan status bukti dukung akan muncul di panel sebelah kanan.
                </p>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-2 text-sm">
            <span class="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1 font-bold text-emerald-700">
                <span class="h-3 w-3 rounded-full bg-emerald-500"></span>
                Ada bukti dukung
            </span>

            <span class="inline-flex items-center gap-2 rounded-full bg-red-100 px-3 py-1 font-bold text-red-700">
                <span class="h-3 w-3 rounded-full bg-red-500"></span>
                Belum ada bukti
            </span>

            <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 font-bold text-slate-600">
                Klik tanggal untuk melihat daftar aktivitas
            </span>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-12">
        {{-- KALENDER --}}
        <div class="xl:col-span-8">
            <div class="mb-4 rounded-2xl border border-amber-100 bg-amber-50 p-4">
                <div class="text-sm font-extrabold text-amber-900">
                    Petunjuk:
                </div>
                <div class="mt-1 text-sm text-amber-700">
                    Klik <b>tanggal</b> untuk melihat daftar aktivitas pada hari itu.
                    Klik <b>nama aktivitas</b> untuk melihat detail pegawai, pekerjaan, project, dan bukti dukung.
                </div>
            </div>

            <div id="activityCalendar"
                class="min-h-[560px] rounded-2xl border border-slate-200 bg-white p-3">
            </div>
        </div>

        {{-- PANEL DETAIL --}}
        <div class="xl:col-span-4">
            <div class="sticky top-4 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <div class="mb-4">
                    <h3 class="text-xl font-extrabold text-slate-950">
                        Panel Informasi
                    </h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Detail aktivitas akan muncul di sini setelah kalender diklik.
                    </p>
                </div>

                {{-- RINGKASAN TANGGAL TERPILIH --}}
                <div class="mb-4 rounded-2xl bg-white p-4 ring-1 ring-slate-200">
                    <div class="text-xs font-bold uppercase tracking-wide text-slate-400">
                        Tanggal Dipilih
                    </div>

                    <div id="calendarSelectedDateTitle" class="mt-1 text-lg font-extrabold text-slate-950">
                        -
                    </div>

                    <div id="calendarSelectedDateCount" class="mt-2 inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">
                        Pilih tanggal di kalender
                    </div>
                </div>

                {{-- DAFTAR AKTIVITAS DI TANGGAL TERPILIH --}}
                <div class="mb-4 rounded-2xl bg-white p-4 ring-1 ring-slate-200">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <div>
                            <div class="text-sm font-extrabold text-slate-950">
                                Aktivitas pada Tanggal Ini
                            </div>
                            <div class="text-xs text-slate-500">
                                Klik salah satu item untuk melihat detail.
                            </div>
                        </div>
                    </div>

                    <div id="calendarSelectedDateList" class="space-y-2">
                        <div class="rounded-xl bg-slate-50 p-4 text-sm text-slate-500">
                            Klik salah satu tanggal pada kalender.
                        </div>
                    </div>
                </div>

                {{-- DETAIL AKTIVITAS --}}
                <div class="rounded-2xl bg-white p-4 ring-1 ring-slate-200">
                    <div class="mb-3 text-sm font-extrabold text-slate-950">
                        Detail Aktivitas
                    </div>

                    <div id="calendarEmptyState" class="rounded-xl bg-slate-50 p-4 text-sm text-slate-500">
                        Belum ada aktivitas yang dipilih.
                    </div>

                    <div id="calendarDetail" class="hidden space-y-3">
                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="text-xs font-bold uppercase tracking-wide text-slate-400">
                                Pegawai
                            </div>
                            <div id="calendarPegawai" class="mt-1 text-base font-extrabold text-slate-950">
                                -
                            </div>
                        </div>

                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="text-xs font-bold uppercase tracking-wide text-slate-400">
                                Aktivitas
                            </div>
                            <div id="calendarActivity" class="mt-1 text-sm leading-relaxed text-slate-700">
                                -
                            </div>
                        </div>

                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="text-xs font-bold uppercase tracking-wide text-slate-400">
                                Project
                            </div>
                            <div id="calendarProject" class="mt-1 text-sm font-semibold text-slate-700">
                                -
                            </div>
                        </div>

                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="text-xs font-bold uppercase tracking-wide text-slate-400">
                                Status Bukti Dukung
                            </div>
                            <div id="calendarEvidence" class="mt-2">
                                -
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

    {{-- ================= PERLU DITINDAKLANJUTI ================= --}}
    <section class="grid gap-6 xl:grid-cols-3">

        {{-- TANPA BUKTI --}}
        <div class="rounded-3xl border border-red-100 bg-red-50 p-6 shadow-sm">
            <div class="mb-5">
                <h2 class="text-xl font-extrabold text-red-950">
                    Task Tanpa Bukti
                </h2>
                <p class="mt-1 text-sm text-red-700">
                    Aktivitas yang sudah dicatat tetapi link bukti masih kosong.
                </p>
            </div>

            <div class="max-h-[420px] space-y-3 overflow-y-auto pr-1">
                @forelse($taskTanpaBuktiCollection as $task)
                    <div class="rounded-2xl bg-white p-4 ring-1 ring-red-100">
                        <div class="text-base font-extrabold text-slate-950">
                            {{ $getTaskUserName($task) }}
                        </div>

                        <div class="mt-2 text-sm leading-relaxed text-slate-700">
                            {{ Str::limit($task->activity ?? '-', 120) }}
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
                            Semua Daily Task sudah memiliki bukti dukung.
                        </p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- IKI BELUM SUBMIT --}}
        <div class="rounded-3xl border border-amber-100 bg-amber-50 p-6 shadow-sm">
            <div class="mb-5">
                <h2 class="text-xl font-extrabold text-amber-950">
                    IKI Belum Submit
                </h2>
                <p class="mt-1 text-sm text-amber-700">
                    IKI masih draft, sehingga belum masuk proses review Ketua.
                </p>
            </div>

            <div class="max-h-[420px] space-y-3 overflow-y-auto pr-1">
                @forelse($unsubmittedIkisCollection->take(20) as $iki)
                    <div class="rounded-2xl bg-white p-4 ring-1 ring-amber-100">
                        <div class="text-base font-extrabold text-slate-950">
                            {{ $iki->rkAnggota->user->name ?? '-' }}
                        </div>

                        <div class="mt-2 text-sm leading-relaxed text-slate-700">
                            {{ Str::limit($iki->description ?? '-', 120) }}
                        </div>

                        <div class="mt-2 flex flex-wrap gap-2 text-xs">
                            <span class="rounded-full bg-amber-100 px-3 py-1 font-bold text-amber-700">
                                Draft
                            </span>

                            <span class="rounded-full bg-slate-100 px-3 py-1 font-semibold text-slate-600">
                                Progress {{ $iki->progress ?? 0 }}%
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl bg-white p-6 text-center ring-1 ring-emerald-100">
                        <div class="font-extrabold text-emerald-700">
                            Aman
                        </div>
                        <p class="mt-1 text-sm text-slate-500">
                            Semua IKI sudah disubmit atau tidak ada IKI draft.
                        </p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- MENUNGGU REVIEW --}}
        <div class="rounded-3xl border border-blue-100 bg-blue-50 p-6 shadow-sm">
            <div class="mb-5">
                <h2 class="text-xl font-extrabold text-blue-950">
                    Menunggu Review Ketua
                </h2>
                <p class="mt-1 text-sm text-blue-700">
                    IKI yang sudah submitted dan menunggu keputusan Ketua Tim.
                </p>
            </div>

            <div class="max-h-[420px] space-y-3 overflow-y-auto pr-1">
                @forelse($pendingIkisCollection as $iki)
                    <div class="rounded-2xl bg-white p-4 ring-1 ring-blue-100">
                        <div class="text-base font-extrabold text-slate-950">
                            {{ $iki->rkAnggota->user->name ?? '-' }}
                        </div>

                        <div class="mt-2 text-sm leading-relaxed text-slate-700">
                            {{ Str::limit($iki->description ?? '-', 120) }}
                        </div>

                        <div class="mt-2 text-xs text-slate-500">
                            Project: {{ $iki->rkAnggota->project->name ?? '-' }}
                        </div>

                        <div class="mt-2">
                            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700">
                                Submitted
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl bg-white p-6 text-center ring-1 ring-emerald-100">
                        <div class="font-extrabold text-emerald-700">
                            Aman
                        </div>
                        <p class="mt-1 text-sm text-slate-500">
                            Tidak ada IKI yang sedang menunggu review.
                        </p>
                    </div>
                @endforelse
            </div>
        </div>

    </section>

    {{-- ================= RINGKASAN TAMBAHAN, TIDAK JADI FOKUS UTAMA ================= --}}
    <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <details>
            <summary class="cursor-pointer select-none text-xl font-extrabold text-slate-950">
                Ringkasan Kinerja Tambahan
                <span class="ml-2 text-sm font-semibold text-slate-400">
                    Klik untuk melihat progress IKU, IKI, Project
                </span>
            </summary>

            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl bg-slate-50 p-5 ring-1 ring-slate-200">
                    <div class="text-sm font-bold text-slate-500">
                        Progress IKU
                    </div>
                    <div class="mt-3 text-3xl font-extrabold text-slate-950">
                        {{ $avgIkuProgressValue ?? 0 }}%
                    </div>
                    <div class="mt-2 h-2 rounded-full bg-slate-200">
                        <div class="h-2 rounded-full bg-emerald-500" style="width: {{ min($avgIkuProgressValue ?? 0, 100) }}%"></div>
                    </div>
                </div>

                <div class="rounded-2xl bg-slate-50 p-5 ring-1 ring-slate-200">
                    <div class="text-sm font-bold text-slate-500">
                        Progress IKI
                    </div>
                    <div class="mt-3 text-3xl font-extrabold text-slate-950">
                        {{ $avgIkiProgressValue ?? 0 }}%
                    </div>
                    <div class="mt-2 h-2 rounded-full bg-slate-200">
                        <div class="h-2 rounded-full bg-blue-500" style="width: {{ min($avgIkiProgressValue ?? 0, 100) }}%"></div>
                    </div>
                </div>

                <div class="rounded-2xl bg-slate-50 p-5 ring-1 ring-slate-200">
                    <div class="text-sm font-bold text-slate-500">
                        Project Selesai
                    </div>
                    <div class="mt-3 text-3xl font-extrabold text-slate-950">
                        {{ $completedProjectValue ?? 0 }}
                    </div>
                    <div class="mt-2 text-sm text-slate-500">
                        Project progress 100%
                    </div>
                </div>

                <div class="rounded-2xl bg-slate-50 p-5 ring-1 ring-slate-200">
                    <div class="text-sm font-bold text-slate-500">
                        Project Rendah
                    </div>
                    <div class="mt-3 text-3xl font-extrabold text-red-600">
                        {{ $lowProjectValue ?? 0 }}
                    </div>
                    <div class="mt-2 text-sm text-slate-500">
                        Progress di bawah 50%
                    </div>
                </div>
            </div>

            <div class="mt-6 grid gap-6 xl:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 p-5">
                    <h3 class="text-base font-extrabold text-slate-950">
                        IKU Progress Terendah
                    </h3>

                    <div class="mt-4 space-y-3">
                        @forelse(($lowProgressIkus ?? collect()) as $iku)
                            <div class="rounded-xl bg-slate-50 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-extrabold text-slate-950">
                                            {{ $iku->name }}
                                        </div>
                                        <div class="mt-1 text-xs text-slate-500">
                                            Tahun {{ $iku->year ?? $year }}
                                        </div>
                                    </div>

                                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-700">
                                        {{ $iku->progress ?? 0 }}%
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-xl bg-slate-50 p-4 text-sm text-slate-500">
                                Belum ada data IKU.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 p-5">
                    <h3 class="text-base font-extrabold text-slate-950">
                        Aktivitas Terbaru
                    </h3>

                    <div class="mt-4 space-y-3">
                        @forelse($recentTasksCollection as $task)
                            <div class="rounded-xl bg-slate-50 p-4">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <div class="text-sm font-extrabold text-slate-950">
                                            {{ $getTaskUserName($task) }}
                                        </div>
                                        <div class="mt-1 text-sm text-slate-700">
                                            {{ Str::limit($task->activity ?? '-', 120) }}
                                        </div>
                                        <div class="mt-1 text-xs text-slate-500">
                                            {{ $task->date ? \Carbon\Carbon::parse($task->date)->format('d M Y') : '-' }}
                                            ·
                                            {{ $getTaskProjectName($task) }}
                                        </div>
                                    </div>

                                    @if($task->evidence_url)
                                        <span class="w-fit rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">
                                            Ada Bukti
                                        </span>
                                    @else
                                        <span class="w-fit rounded-full bg-red-100 px-3 py-1 text-xs font-bold text-red-700">
                                            Tanpa Bukti
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="rounded-xl bg-slate-50 p-4 text-sm text-slate-500">
                                Belum ada aktivitas terbaru.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </details>
    </section>

</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css">

<style>
    #activityCalendar .fc-toolbar-title {
        font-size: 1.6rem;
        font-weight: 800;
        color: #0f172a;
    }

    #activityCalendar .fc-button {
        border-radius: 0.75rem;
        font-weight: 700;
        padding: 0.55rem 0.9rem;
    }

    #activityCalendar .fc-daygrid-day-number {
        font-weight: 700;
        color: #0f172a;
        padding: 0.55rem;
    }

    #activityCalendar .fc-col-header-cell-cushion {
        font-weight: 800;
        color: #0f172a;
        padding: 0.7rem 0;
    }

    #activityCalendar .fc-event {
        border-radius: 0.55rem;
        padding: 0.12rem 0.35rem;
        font-weight: 700;
        cursor: pointer;
    }

    #activityCalendar .fc-day-today {
        background: #fff7ed !important;
    }

    #activityCalendar .fc-daygrid-day:hover {
        background: #f8fafc;
        cursor: pointer;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/locales/id.global.min.js"></script>

<script>
window.addEventListener('load', function () {
    const calendarEl = document.getElementById('activityCalendar');
    const calendarEventsUrl = @json($calendarEventsUrl ?? url('/calendar/events'));
    const selectedYear = @json((int) ($year ?? date('Y')));

    let allCalendarEvents = [];
    let calendarEventMap = {};
    let selectedDate = null;

    function showCalendarError(message) {
        if (!calendarEl) {
            return;
        }

        calendarEl.innerHTML = `
            <div class="flex min-h-[480px] items-center justify-center rounded-2xl bg-red-50 p-8 text-center">
                <div>
                    <div class="text-lg font-extrabold text-red-700">
                        Kalender belum bisa dimuat
                    </div>
                    <div class="mt-2 text-sm text-red-600">
                        ${message}
                    </div>
                    <div class="mt-4 text-xs text-red-500">
                        Cek koneksi internet/CDN FullCalendar atau route kalender.
                    </div>
                </div>
            </div>
        `;
    }

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

    function showCalendarDetailData(title, props) {
        const emptyState = document.getElementById('calendarEmptyState');
        const detail = document.getElementById('calendarDetail');

        if (emptyState) {
            emptyState.classList.add('hidden');
        }

        if (detail) {
            detail.classList.remove('hidden');
        }

        const pegawaiEl = document.getElementById('calendarPegawai');
        const activityEl = document.getElementById('calendarActivity');
        const projectEl = document.getElementById('calendarProject');
        const evidenceEl = document.getElementById('calendarEvidence');

        if (pegawaiEl) {
            pegawaiEl.innerHTML = escapeHtml(props.pegawai || '-');
        }

        if (activityEl) {
            activityEl.innerHTML = escapeHtml(props.activity || title || '-');
        }

        if (projectEl) {
            projectEl.innerHTML = escapeHtml(props.project || '-');
        }

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

    function showCalendarDetailFromRaw(eventData) {
        const props = eventData.extendedProps || {};
        showCalendarDetailData(eventData.title, props);
    }

    function showCalendarDetailFromFullCalendar(event) {
        const props = event.extendedProps || {};
        showCalendarDetailData(event.title, props);
    }

    function renderSelectedDateList(dateString) {
        selectedDate = dateString;

        const titleEl = document.getElementById('calendarSelectedDateTitle');
        const countEl = document.getElementById('calendarSelectedDateCount');
        const listEl = document.getElementById('calendarSelectedDateList');

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
                    Tidak ada aktivitas yang tercatat pada tanggal ini.
                </div>
            `;
            return;
        }

        listEl.innerHTML = eventsOnDate.map(item => {
            const props = item.extendedProps || {};
            const eventId = escapeHtml(item.id);

            return `
                <button type="button"
                    data-calendar-event-id="${eventId}"
                    class="w-full rounded-xl border border-slate-200 bg-white p-3 text-left hover:border-blue-300 hover:bg-blue-50">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="truncate text-sm font-extrabold text-slate-950">
                                ${escapeHtml(props.pegawai || 'Pegawai')}
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

        listEl.querySelectorAll('[data-calendar-event-id]').forEach(button => {
            button.addEventListener('click', function () {
                const eventId = this.getAttribute('data-calendar-event-id');
                const eventData = calendarEventMap[eventId];

                if (eventData) {
                    showCalendarDetailFromRaw(eventData);
                }
            });
        });
    }

    if (!calendarEl) {
        return;
    }

    if (!window.FullCalendar) {
        showCalendarError('Library FullCalendar tidak berhasil dimuat.');
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
        navLinks: true,
        selectable: true,
        selectMirror: true,
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

        loading: function(isLoading) {
            if (isLoading) {
                calendarEl.classList.add('opacity-60');
            } else {
                calendarEl.classList.remove('opacity-60');
            }
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
                    console.error('Calendar error:', error);
                    failureCallback(error);

                    showCalendarError(
                        'Endpoint kalender tidak bisa dibaca. Coba buka langsung: ' +
                        calendarEventsUrl +
                        '?year=' +
                        selectedYear
                    );
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

            showCalendarDetailFromFullCalendar(info.event);
        },

        eventDidMount: function(info) {
            const props = info.event.extendedProps || {};

            if (props.has_evidence === false) {
                info.el.title = 'Belum ada bukti dukung';
            } else {
                info.el.title = 'Sudah ada bukti dukung';
            }
        }
    });

    calendar.render();
    calendar.select(initialDate);

    if (window.lucide) {
        window.lucide.createIcons();
    }

    setTimeout(() => {
        if (!document.hidden) {
            window.location.reload();
        }
    }, 60000);
});
</script>

</x-app-layout>