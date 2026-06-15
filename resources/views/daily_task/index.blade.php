<x-app-layout>

@php
    $role = auth()->user()->role;

    $basePath = match($role) {
        'admin' => '/admin/daily-task',
        'kepala' => '/kepala/daily-task',
        'anggota' => '/anggota/daily-task',
        'ketua' => '/ketua/daily-task',
        default => '/daily-task',
    };

    $isMineMode = $role === 'ketua' && request('mode') === 'mine';
    $isPersonalMode = $role === 'anggota' || $isMineMode;
    $canManageDailyTask = $role === 'admin' || $isPersonalMode;

    /*
    |--------------------------------------------------------------------------
    | URL yang mempertahankan mode
    |--------------------------------------------------------------------------
    */
    $cleanUrl = url()->current();
    $resetUrl = $isMineMode ? $cleanUrl . '?mode=mine' : $cleanUrl;

    /*
    |--------------------------------------------------------------------------
    | Selected Filter
    |--------------------------------------------------------------------------
    | selectedYear dan selectedStatus dikirim dari DailyTaskController.
    | Fallback tetap disediakan agar Blade aman jika controller lama masih dipakai.
    |--------------------------------------------------------------------------
    */
    $selectedYear = $selectedYear ?? request('year', date('Y'));
    $selectedStatus = $selectedStatus ?? request('status');

    $statusOptions = [
        '' => 'Semua Status IKI',
        'draft' => 'Draft',
        'submitted' => 'Submitted',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ];

    $hasActiveFilters = request()->filled('search')
        || request()->filled('year')
        || request()->filled('status')
        || request()->filled('start_date')
        || request()->filled('end_date');
@endphp

<div class="space-y-6">

    {{-- HEADER --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-5">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-green-50 text-green-700 text-xs font-semibold mb-3">
                    Monitoring Kegiatan Harian
                </div>

                <h2 class="text-2xl font-bold text-gray-900">
                    {{ $isPersonalMode ? 'Daily Task Saya' : 'Daily Task' }}
                </h2>

                <p class="text-sm text-gray-500 mt-2 max-w-3xl">
                    @if($isPersonalMode)
                        Catat aktivitas harian, progres pengerjaan, dan link bukti dukung untuk IKI milikmu.
                    @elseif($role === 'kepala')
                        Pantau aktivitas harian pegawai, status IKI, dan bukti dukung pekerjaan.
                    @elseif($role === 'ketua')
                        Pantau Daily Task anggota tim. Approval akhir tetap dilakukan melalui IKI.
                    @else
                        Kelola dan pantau Daily Task seluruh pegawai.
                    @endif
                </p>
            </div>

            <div class="flex flex-col sm:flex-row gap-2">
                <button type="button"
                    onclick="openModal('modalExport')"
                    class="h-11 px-5 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 flex items-center justify-center font-semibold shadow-sm">
                    Export Excel
                </button>

                <a href="{{ $resetUrl }}"
                    class="h-11 px-5 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 flex items-center justify-center font-semibold">
                    Refresh
                </a>

                @if($canManageDailyTask && isset($ikis) && $ikis->count() > 0)
                    <button type="button"
                        onclick="openModal('modalCreate')"
                        class="h-11 px-5 bg-green-600 text-white rounded-xl hover:bg-green-700 flex items-center justify-center font-semibold shadow-sm">
                        + Tambah Task
                    </button>
                @endif
            </div>
        </div>

        @if($isPersonalMode && isset($ikis) && $ikis->count() === 0)
            <div class="mt-5 p-4 rounded-xl bg-yellow-50 text-yellow-800 border border-yellow-100 text-sm">
                <div class="font-semibold mb-1">
                    Belum ada IKI yang bisa diisi Daily Task.
                </div>
                <div>
                    Buat IKI terlebih dahulu atau pastikan IKI masih berstatus Draft/Rejected.
                </div>
            </div>
        @endif
    </div>

    {{-- ALERT --}}
    @if(session('success'))
        <div class="p-4 bg-green-50 text-green-700 rounded-xl border border-green-100">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 bg-red-50 text-red-700 rounded-xl border border-red-100">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 bg-red-50 text-red-700 rounded-xl border border-red-100">
            <div class="font-semibold mb-2">
                Ada data yang perlu diperbaiki:
            </div>
            <ul class="list-disc ml-5 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- FILTER --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-start justify-between gap-4 mb-4">
            <div>
                <h3 class="font-bold text-gray-900">
                    Filter Daily Task
                </h3>
                <p class="text-sm text-gray-500 mt-1">
                    Gunakan filter untuk mengecek aktivitas harian berdasarkan tahun, status IKI, dan rentang tanggal.
                </p>
            </div>

            @if($hasActiveFilters)
                <a href="{{ $resetUrl }}"
                    class="text-sm px-3 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium">
                    Reset Filter
                </a>
            @endif
        </div>

        <form
            id="filterForm"
            method="GET"
            class="grid grid-cols-1 md:grid-cols-12 gap-4">

            @if($isMineMode)
                <input type="hidden" name="mode" value="mine">
            @endif

            {{-- SEARCH --}}
            <div class="md:col-span-4">
                <label for="searchInput" class="block text-xs font-semibold text-gray-500 mb-1">
                    Pencarian
                </label>
                <input
                    id="searchInput"
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Cari aktivitas, project, anggota, atau bukti..."
                    class="w-full h-11 border border-gray-200 rounded-xl px-4 focus:ring-2 focus:ring-green-100 focus:border-green-500">
            </div>

            {{-- YEAR --}}
            <div class="md:col-span-2">
                <label for="yearFilter" class="block text-xs font-semibold text-gray-500 mb-1">
                    Tahun IKU
                </label>
                <select
                    id="yearFilter"
                    name="year"
                    class="w-full h-11 border border-gray-200 rounded-xl px-3 bg-white focus:ring-2 focus:ring-green-100 focus:border-green-500">

                    @for($y = (int) date('Y') + 1; $y >= 2024; $y--)
                        <option
                            value="{{ $y }}"
                            {{ (string) $selectedYear === (string) $y ? 'selected' : '' }}>
                            {{ $y }}
                        </option>
                    @endfor

                </select>
            </div>

            {{-- STATUS --}}
            <div class="md:col-span-2">
                <label for="statusFilter" class="block text-xs font-semibold text-gray-500 mb-1">
                    Status IKI
                </label>
                <select
                    id="statusFilter"
                    name="status"
                    class="w-full h-11 border border-gray-200 rounded-xl px-3 bg-white focus:ring-2 focus:ring-green-100 focus:border-green-500">

                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}" {{ (string) $selectedStatus === (string) $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach

                </select>
            </div>

            {{-- START DATE --}}
            <div class="md:col-span-2">
                <label for="startDateFilter" class="block text-xs font-semibold text-gray-500 mb-1">
                    Tanggal Awal
                </label>
                <input
                    id="startDateFilter"
                    type="date"
                    name="start_date"
                    value="{{ request('start_date') }}"
                    class="w-full h-11 border border-gray-200 rounded-xl px-3 focus:ring-2 focus:ring-green-100 focus:border-green-500">
            </div>

            {{-- END DATE --}}
            <div class="md:col-span-2">
                <label for="endDateFilter" class="block text-xs font-semibold text-gray-500 mb-1">
                    Tanggal Akhir
                </label>
                <input
                    id="endDateFilter"
                    type="date"
                    name="end_date"
                    value="{{ request('end_date') }}"
                    class="w-full h-11 border border-gray-200 rounded-xl px-3 focus:ring-2 focus:ring-green-100 focus:border-green-500">
            </div>

            <div class="md:col-span-12 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-1">
                <div class="flex flex-wrap gap-2 text-xs">
                    <span class="px-3 py-1 rounded-full bg-gray-100 text-gray-600">
                        Tahun: {{ $selectedYear ?: 'Semua' }}
                    </span>

                    <span class="px-3 py-1 rounded-full bg-gray-100 text-gray-600">
                        Status: {{ $statusOptions[$selectedStatus] ?? 'Semua Status IKI' }}
                    </span>

                    @if(request('start_date') || request('end_date'))
                        <span class="px-3 py-1 rounded-full bg-gray-100 text-gray-600">
                            Tanggal:
                            {{ request('start_date') ?: 'Awal' }}
                            s/d
                            {{ request('end_date') ?: 'Akhir' }}
                        </span>
                    @endif
                </div>

                <div class="flex gap-2">
                    <button type="submit"
                        class="h-10 px-5 bg-green-600 hover:bg-green-700 text-white rounded-xl font-semibold">
                        Terapkan Filter
                    </button>

                    <a href="{{ $resetUrl }}"
                        class="h-10 px-5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-semibold flex items-center">
                        Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- EXPORT INFO --}}
    @if(request('start_date') || request('end_date') || request('year') || request('status'))
        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4">
            <div class="font-semibold text-emerald-800 mb-2">
                Filter Aktif
            </div>
            <div class="text-sm text-emerald-700 leading-6">
                • Tahun IKU: <b>{{ $selectedYear ?: 'Semua Tahun' }}</b><br>
                • Status IKI: <b>{{ $statusOptions[$selectedStatus] ?? 'Semua Status IKI' }}</b>

                @if(request('start_date'))
                    <br>• Tanggal Awal: <b>{{ request('start_date') }}</b>
                @endif

                @if(request('end_date'))
                    <br>• Tanggal Akhir: <b>{{ request('end_date') }}</b>
                @endif
            </div>
        </div>
    @endif

    {{-- ROLE INFO --}}
    <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-700">
        @if($role === 'admin')
            <b>Mode Admin:</b> kamu dapat mengelola semua Daily Task untuk kebutuhan administrasi.
        @elseif($role === 'anggota')
            <b>Mode Anggota:</b> kamu hanya dapat mengelola Daily Task milikmu selama IKI masih Draft atau Rejected.
        @elseif($role === 'ketua' && $isMineMode)
            <b>Mode Pekerjaan Saya:</b> kamu dapat mengelola Daily Task milikmu selama IKI masih Draft atau Rejected.
        @elseif($role === 'ketua')
            <b>Mode Ketua:</b> halaman ini untuk monitoring proses kerja anggota. Approval dilakukan dari IKI.
        @elseif($role === 'kepala')
            <b>Mode Kepala:</b> halaman ini untuk monitoring aktivitas harian pegawai. Kepala hanya dapat melihat data, bukan mengubah Daily Task.
        @endif
    </div>

    {{-- TABLE --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div>
            <h3 class="font-bold text-gray-900">
                Aktivitas Harian Pegawai
            </h3>
            <p class="text-sm text-gray-500 mt-1">
                Fokus utama halaman ini adalah melihat siapa mengerjakan apa setiap hari.
            </p>
        </div>

        <div class="text-sm text-gray-500">
            Total data halaman ini: <b>{{ $tasks->count() }}</b>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3 font-semibold w-[140px]">
                        Tanggal
                    </th>

                    @if(!$isPersonalMode)
                        <th class="text-left px-4 py-3 font-semibold w-[180px]">
                            Pegawai
                        </th>
                    @endif

                    <th class="text-left px-4 py-3 font-semibold min-w-[520px]">
                        Aktivitas Harian
                    </th>

                    <th class="text-left px-4 py-3 font-semibold w-[130px]">
                        Bukti
                    </th>

                    <th class="text-left px-4 py-3 font-semibold w-[160px]">
                        Status IKI
                    </th>

                    <th class="text-left px-4 py-3 font-semibold w-[150px]">
                        Aksi
                    </th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100">
            @forelse($tasks as $t)
                @php
                    $iki = $t->iki;
                    $rk = $iki?->rkAnggota ?? $t->rkAnggota;

                    $project = $rk?->project;
                    $team = $project?->team;
                    $iku = $project?->rkKetua?->iku;

                    $ikiStatus = $iki->status ?? $rk?->status ?? $t->status ?? 'draft';

                    $statusClass = match($ikiStatus) {
                        'draft' => 'bg-gray-100 text-gray-700',
                        'submitted' => 'bg-blue-100 text-blue-700',
                        'approved' => 'bg-green-100 text-green-700',
                        'rejected' => 'bg-red-100 text-red-700',
                        'pending' => 'bg-yellow-100 text-yellow-700',
                        default => 'bg-gray-100 text-gray-700',
                    };

                    $statusLabel = match($ikiStatus) {
                        'draft' => 'Draft',
                        'submitted' => 'Submitted',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'pending' => 'Pending',
                        default => ucfirst($ikiStatus),
                    };

                    $statusNote = match($ikiStatus) {
                        'draft' => 'Masih bisa diedit',
                        'submitted' => 'Menunggu review ketua',
                        'approved' => 'Sudah dikunci',
                        'rejected' => 'Perlu revisi',
                        'pending' => 'Menunggu proses',
                        default => null,
                    };

                    $isOwner = (int) optional($rk)->user_id === (int) auth()->id();

                    $canEditThisTask = in_array($ikiStatus, ['draft', 'rejected'])
                        && (
                            $role === 'admin'
                            || ($isPersonalMode && $isOwner)
                        );
                @endphp

                <tr class="hover:bg-gray-50 align-top">
                    {{-- TANGGAL --}}
                    <td class="px-4 py-4 whitespace-nowrap">
                        <div class="font-semibold text-gray-900">
                            {{ $t->date ? \Carbon\Carbon::parse($t->date)->format('d M Y') : '-' }}
                        </div>

                        @if($t->date && \Carbon\Carbon::parse($t->date)->isToday())
                            <div class="inline-flex mt-2 px-2 py-1 rounded-full bg-green-50 text-green-700 text-xs font-semibold">
                                Hari ini
                            </div>
                        @endif
                    </td>

                    {{-- PEGAWAI --}}
                    @if(!$isPersonalMode)
                        <td class="px-4 py-4">
                            <div class="font-semibold text-gray-900">
                                {{ $rk->user->name ?? '-' }}
                            </div>

                            @if($team)
                                <div class="text-xs text-gray-400 mt-1">
                                    {{ $team->name }}
                                </div>
                            @endif
                        </td>
                    @endif

                    {{-- AKTIVITAS HARIAN --}}
                    <td class="px-4 py-4">
                        <div class="text-base font-semibold text-gray-900 leading-relaxed">
                            {{ $t->activity ?: '-' }}
                        </div>

                        <div class="mt-3 flex flex-wrap gap-2 text-xs">
                            @if($project)
                                <span class="px-2.5 py-1 rounded-full bg-gray-100 text-gray-600">
                                    Project: {{ \Illuminate\Support\Str::limit($project->name, 45) }}
                                </span>
                            @endif

                            @if($iki)
                                <span class="px-2.5 py-1 rounded-full bg-blue-50 text-blue-700">
                                    IKI: {{ \Illuminate\Support\Str::limit($iki->description, 55) }}
                                </span>
                            @else
                                <span class="px-2.5 py-1 rounded-full bg-yellow-50 text-yellow-700">
                                    Belum terhubung IKI
                                </span>
                            @endif

                            @if($iku)
                                <span class="px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700">
                                    IKU {{ $iku->year }}
                                </span>
                            @endif
                        </div>

                        @if($rk?->description)
                            <div class="mt-2 text-xs text-gray-400">
                                RK Anggota: {{ \Illuminate\Support\Str::limit($rk->description, 100) }}
                            </div>
                        @endif
                    </td>

                    {{-- BUKTI --}}
                    <td class="px-4 py-4 whitespace-nowrap">
                        @if($t->evidence_url)
                            <a href="{{ $t->evidence_url }}"
                                target="_blank"
                                class="inline-flex items-center px-3 py-1.5 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 font-semibold text-xs">
                                Buka Bukti
                            </a>
                        @else
                            <span class="inline-flex items-center px-3 py-1.5 rounded-lg bg-red-50 text-red-600 font-semibold text-xs">
                                Belum Ada
                            </span>
                        @endif
                    </td>

                    {{-- STATUS --}}
                    <td class="px-4 py-4">
                        <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold {{ $statusClass }}">
                            {{ $statusLabel }}
                        </span>

                        @if($statusNote)
                            <div class="text-xs text-gray-500 mt-2">
                                {{ $statusNote }}
                            </div>
                        @endif
                    </td>

                   {{-- AKSI --}}
                    <td class="px-4 py-4">
                        <div class="relative inline-block text-left">
                            <button type="button"
                                onclick="toggleActionMenu(event, 'actionMenu-{{ $t->id }}')"
                                class="inline-flex items-center justify-center gap-1 min-w-[84px] px-4 py-2 rounded-lg border border-gray-900 bg-white text-gray-900 hover:bg-gray-50 text-sm font-semibold shadow-sm">
                                Aksi
                                <span class="text-[10px] leading-none">▼</span>
                            </button>

                            <div id="actionMenu-{{ $t->id }}"
                                data-action-menu
                                onclick="event.stopPropagation()"
                                class="hidden fixed z-[9999] w-52 rounded-xl bg-white border border-gray-200 shadow-lg overflow-hidden">

                                {{-- Semua role boleh View --}}
                                <button type="button"
                                    onclick="closeAllActionMenus(); openViewModal({{ $t->id }})"
                                    class="w-full text-left px-5 py-3 text-sm text-blue-600 hover:bg-blue-50">
                                    View
                                </button>

                                @if($canEditThisTask)
                                    <button type="button"
                                        onclick="closeAllActionMenus(); openEditModal({{ $t->id }})"
                                        class="w-full text-left px-5 py-3 text-sm text-yellow-600 hover:bg-yellow-50 border-t border-gray-100">
                                        Edit
                                    </button>

                                    <form method="POST"
                                        action="{{ $basePath }}/{{ $t->id }}"
                                        onsubmit="return confirm('Yakin ingin menghapus Daily Task ini?')">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                            class="w-full text-left px-5 py-3 text-sm text-red-600 hover:bg-red-50 border-t border-gray-100">
                                            Delete
                                        </button>
                                    </form>
                                @else
                                    <div class="px-5 py-3 text-xs text-gray-400 bg-gray-50 border-t border-gray-100 leading-relaxed">
                                        @if($role === 'kepala')
                                            Kepala hanya dapat monitoring.
                                        @elseif($role === 'ketua' && !$isMineMode)
                                            Ketua monitoring dari sini. Review dilakukan melalui IKI.
                                        @elseif(in_array($ikiStatus, ['submitted', 'approved']))
                                            Task terkunci karena IKI sudah {{ $statusLabel }}.
                                        @else
                                            Tidak ada aksi tambahan.
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $isPersonalMode ? 5 : 6 }}" class="text-center py-12 text-gray-500">
                        <div class="font-semibold text-gray-700 mb-1">
                            Belum ada aktivitas harian
                        </div>
                        <div class="text-sm">
                            Data Daily Task belum tersedia atau tidak sesuai dengan filter yang dipilih.
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($tasks, 'links'))
        <div class="px-5 py-4 border-t border-gray-100">
            {{ $tasks->withQueryString()->links() }}
        </div>
    @endif
</div>


{{-- CREATE MODAL --}}
@if($canManageDailyTask)
<div id="modalCreate"
    class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">

    <div class="bg-white rounded-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto shadow-xl">

        <div class="px-6 py-4 border-b flex justify-between items-start">
            <div>
                <h3 class="text-xl font-bold text-gray-900">
                    {{ $isPersonalMode ? 'Tambah Daily Task Saya' : 'Tambah Daily Task' }}
                </h3>

                <p class="text-sm text-gray-500 mt-1">
                    @if($isPersonalMode)
                        Catat progres harian untuk IKI pribadi yang masih Draft atau Rejected.
                    @elseif($role === 'admin')
                        Tambahkan catatan proses kerja untuk IKI anggota tertentu.
                    @else
                        Tambahkan catatan proses kerja.
                    @endif
                </p>
            </div>

            <button type="button"
                onclick="closeModal('modalCreate')"
                class="text-gray-400 hover:text-red-500 text-2xl leading-none">
                ×
            </button>
        </div>

        <form method="POST" action="{{ $basePath }}" class="p-6">
            @csrf

            @if($isMineMode)
                <input type="hidden" name="mode" value="mine">
            @endif

            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    IKI
                </label>

                <select name="iki_id"
                    required
                    class="border border-gray-200 w-full p-3 rounded-xl bg-white focus:ring-2 focus:ring-green-100 focus:border-green-500">
                    <option value="">Pilih IKI</option>

                    @foreach(($ikis ?? collect()) as $iki)
                        @if(in_array($iki->status, ['draft', 'rejected']))
                            <option value="{{ $iki->id }}">
                                {{ $iki->rkAnggota->project->name ?? '-' }}
                                — {{ \Illuminate\Support\Str::limit($iki->description, 80) }}
                                @if($role === 'admin')
                                    — {{ $iki->rkAnggota->user->name ?? '-' }}
                                @endif
                            </option>
                        @endif
                    @endforeach
                </select>

                <p class="text-xs text-gray-400 mt-1">
                    Daily Task dibuat di bawah IKI. Hanya IKI berstatus Draft atau Rejected yang bisa dipilih.
                </p>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Tanggal
                </label>

                <input type="date"
                    name="date"
                    value="{{ old('date', now()->toDateString()) }}"
                    required
                    class="border border-gray-200 w-full p-3 rounded-xl bg-white focus:ring-2 focus:ring-green-100 focus:border-green-500">

                <p class="text-xs text-gray-400 mt-1">
                    Tanggal pelaksanaan kegiatan. Boleh mengisi kegiatan yang belum sempat dicatat sebelumnya.
                </p>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Aktivitas
                </label>

                <textarea name="activity"
                    required
                    rows="5"
                    placeholder="Tulis aktivitas/progres yang dikerjakan hari ini..."
                    class="border border-gray-200 w-full p-3 rounded-xl focus:ring-2 focus:ring-green-100 focus:border-green-500"></textarea>

                <p class="text-xs text-gray-400 mt-1">
                    Contoh: membersihkan data, membuat tabel, validasi output, koordinasi, atau revisi dokumen.
                </p>
            </div>

            <input type="hidden" name="output" value="-">

            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Link Bukti Kerja
                </label>

                <input name="evidence_url"
                    type="url"
                    placeholder="https://drive.google.com/..."
                    class="border border-gray-200 w-full p-3 rounded-xl focus:ring-2 focus:ring-green-100 focus:border-green-500">

                <p class="text-xs text-gray-400 mt-1">
                    Isi dengan link dokumen, spreadsheet, drive, foto bukti, atau file pendukung pekerjaan.
                </p>
            </div>

            <div class="mb-5 p-4 rounded-xl bg-blue-50 border border-blue-100 text-sm text-blue-700">
                Daily Task menjadi bukti proses untuk IKI. Setelah proses kerja cukup, IKI bisa disubmit untuk review Ketua.
            </div>

            <div class="flex justify-end gap-2 pt-4 border-t">
                <button type="button"
                    onclick="closeModal('modalCreate')"
                    class="bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-xl font-semibold text-gray-700">
                    Cancel
                </button>

                <button type="submit"
                    class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded-xl font-semibold shadow-sm">
                    Save
                </button>
            </div>
        </form>

    </div>
</div>
@endif


{{-- VIEW MODAL --}}
<div id="modalView"
    class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">

    <div class="bg-white rounded-2xl w-full max-w-3xl max-h-[85vh] overflow-y-auto shadow-xl">

        <div class="px-6 py-4 border-b flex justify-between items-start">
            <div>
                <h3 class="text-xl font-bold text-gray-900">
                    Detail Daily Task
                </h3>
                <p class="text-sm text-gray-500 mt-1">
                    Detail progres harian, RK terkait, link bukti kerja, dan waktu pencatatan.
                </p>
            </div>

            <button type="button"
                onclick="closeModal('modalView')"
                class="text-gray-400 hover:text-red-500 text-2xl leading-none">
                ×
            </button>
        </div>

        <div id="viewContent" class="p-6"></div>

        <div class="flex justify-end px-6 py-4 border-t">
            <button type="button"
                onclick="closeModal('modalView')"
                class="bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-xl font-semibold text-gray-700">
                Close
            </button>
        </div>

    </div>
</div>


{{-- EDIT MODAL --}}
@if($canManageDailyTask)
<div id="modalEdit"
    class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">

    <div class="bg-white rounded-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto shadow-xl">

        <div class="px-6 py-4 border-b flex justify-between items-start">
            <div>
                <h3 class="text-xl font-bold text-gray-900">
                    Edit Daily Task
                </h3>

                <p class="text-sm text-gray-500 mt-1">
                    Perbarui tanggal, aktivitas, dan link bukti kerja selama IKI masih Draft atau Rejected.
                </p>
            </div>

            <button type="button"
                onclick="closeModal('modalEdit')"
                class="text-gray-400 hover:text-red-500 text-2xl leading-none">
                ×
            </button>
        </div>

        <form method="POST" id="formEdit" class="p-6">
            @csrf
            @method('PUT')

            @if($isMineMode)
                <input type="hidden" name="mode" value="mine">
            @endif

            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Tanggal Pelaksanaan
                </label>

                <input id="edit_date"
                    type="date"
                    name="date"
                    required
                    class="border border-gray-200 w-full p-3 rounded-xl bg-white focus:ring-2 focus:ring-yellow-100 focus:border-yellow-500">

                <p class="text-xs text-gray-400 mt-1">
                    Tanggal pelaksanaan kegiatan. Boleh mengisi kegiatan yang belum sempat dicatat sebelumnya.
                </p>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Aktivitas
                </label>

                <textarea id="edit_activity"
                    name="activity"
                    required
                    rows="5"
                    placeholder="Tulis aktivitas/progres yang dikerjakan..."
                    class="border border-gray-200 w-full p-3 rounded-xl focus:ring-2 focus:ring-yellow-100 focus:border-yellow-500"></textarea>

                <p class="text-xs text-gray-400 mt-1">
                    Contoh: analisis data, validasi tabel, revisi dokumen, koordinasi, atau penyusunan output.
                </p>
            </div>

            <input type="hidden" id="edit_output" name="output" value="-">

            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Link Bukti Kerja
                </label>

                <input id="edit_evidence"
                    name="evidence_url"
                    type="url"
                    placeholder="https://drive.google.com/..."
                    class="border border-gray-200 w-full p-3 rounded-xl focus:ring-2 focus:ring-yellow-100 focus:border-yellow-500">

                <p class="text-xs text-gray-400 mt-1">
                    Gunakan link Google Drive, Spreadsheet, dokumen, foto bukti, atau file pendukung pekerjaan.
                </p>
            </div>

            <div class="mb-5 p-4 rounded-xl bg-yellow-50 border border-yellow-100 text-sm text-yellow-700">
                Daily Task hanya bisa diedit selama IKI masih berstatus Draft atau Rejected.
                Jika IKI sudah Submitted atau Approved, task akan terkunci.
            </div>

            <div class="flex justify-end gap-2 pt-4 border-t">
                <button type="button"
                    onclick="closeModal('modalEdit')"
                    class="bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-xl font-semibold text-gray-700">
                    Cancel
                </button>

                <button type="submit"
                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-5 py-2 rounded-xl font-semibold shadow-sm">
                    Update
                </button>
            </div>
        </form>

    </div>
</div>
@endif


{{-- EXPORT MODAL --}}
<div id="modalExport"
    class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">

    <div class="bg-white rounded-2xl w-full max-w-lg shadow-xl">

        <div class="px-6 py-4 border-b">
            <h3 class="text-lg font-bold text-gray-900">
                Export Kegiatan Harian
            </h3>

            <p class="text-sm text-gray-500 mt-1">
                Pilih rentang tanggal kegiatan yang ingin diexport ke Excel.
            </p>
        </div>

        <form
            method="GET"
            action="{{ route('export.daily-tasks') }}"
            class="p-6">

            <input
                type="hidden"
                name="year"
                value="{{ $selectedYear ?: date('Y') }}">

            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Tanggal Awal
                </label>

                <input
                    type="date"
                    name="start_date"
                    required
                    value="{{ request('start_date') }}"
                    class="w-full border border-gray-200 rounded-xl p-3 focus:ring-2 focus:ring-emerald-100 focus:border-emerald-500">
            </div>

            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Tanggal Akhir
                </label>

                <input
                    type="date"
                    name="end_date"
                    required
                    value="{{ request('end_date') }}"
                    class="w-full border border-gray-200 rounded-xl p-3 focus:ring-2 focus:ring-emerald-100 focus:border-emerald-500">
            </div>

            <div class="mb-5 p-4 rounded-xl bg-emerald-50 border border-emerald-100 text-sm text-emerald-700">
                Export akan menggunakan tahun <b>{{ $selectedYear ?: date('Y') }}</b>.
            </div>

            <div class="flex justify-end gap-2">
                <button
                    type="button"
                    onclick="closeModal('modalExport')"
                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-xl font-semibold text-gray-700">
                    Batal
                </button>

                <button
                    type="submit"
                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-semibold">
                    Export Excel
                </button>
            </div>
        </form>

    </div>
</div>


<script>
const DAILY_TASK_BASE_PATH = @json($basePath);
const filterForm = document.getElementById('filterForm');

const searchInput = document.getElementById('searchInput');
const yearFilter = document.getElementById('yearFilter');
const statusFilter = document.getElementById('statusFilter');
const startDateFilter = document.getElementById('startDateFilter');
const endDateFilter = document.getElementById('endDateFilter');

let searchTimeout;

if (searchInput) {
    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            filterForm.submit();
        }, 600);
    });
}

if (yearFilter) {
    yearFilter.addEventListener('change', () => {
        filterForm.submit();
    });
}

if (statusFilter) {
    statusFilter.addEventListener('change', () => {
        filterForm.submit();
    });
}

if (startDateFilter) {
    startDateFilter.addEventListener('change', () => {
        filterForm.submit();
    });
}

if (endDateFilter) {
    endDateFilter.addEventListener('change', () => {
        filterForm.submit();
    });
}

function toggleActionMenu(event, menuId) {
    event.stopPropagation();

    const menu = document.getElementById(menuId);
    const button = event.currentTarget;

    if (!menu || !button) {
        return;
    }

    const wasHidden = menu.classList.contains('hidden');

    closeAllActionMenus();

    if (!wasHidden) {
        return;
    }

    const rect = button.getBoundingClientRect();
    const menuWidth = 224;
    const gap = 8;

    let left = rect.right - menuWidth;
    let top = rect.bottom + gap;

    if (left < gap) {
        left = gap;
    }

    if (left + menuWidth > window.innerWidth - gap) {
        left = window.innerWidth - menuWidth - gap;
    }

    if (top + 220 > window.innerHeight) {
        top = rect.top - 220 - gap;
    }

    if (top < gap) {
        top = gap;
    }

    menu.style.left = `${left}px`;
    menu.style.top = `${top}px`;
    menu.classList.remove('hidden');
}

function closeAllActionMenus() {
    document.querySelectorAll('[data-action-menu]').forEach(menu => {
        menu.classList.add('hidden');
    });
}

document.addEventListener('click', closeAllActionMenus);

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeAllActionMenus();
    }
});

window.addEventListener('resize', closeAllActionMenus);
window.addEventListener('scroll', closeAllActionMenus, true);

function openModal(id) {
    const modal = document.getElementById(id);

    if (modal) {
        modal.classList.remove('hidden');
    }
}

function closeModal(id) {
    const modal = document.getElementById(id);

    if (modal) {
        modal.classList.add('hidden');
    }
}

function escapeHtml(value) {
    if (value === null || value === undefined) {
        return '-';
    }

    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function formatDate(dateString) {
    if (!dateString) return '-';

    let date = new Date(dateString);

    if (isNaN(date)) {
        return escapeHtml(dateString);
    }

    return date.toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function getStatusClass(status) {
    return {
        draft: 'bg-gray-100 text-gray-700',
        submitted: 'bg-blue-100 text-blue-700',
        approved: 'bg-green-100 text-green-700',
        rejected: 'bg-red-100 text-red-700',
        pending: 'bg-yellow-100 text-yellow-700',
    }[status] ?? 'bg-gray-100 text-gray-700';
}

function getStatusLabel(status) {
    return {
        draft: 'Draft',
        submitted: 'Submitted',
        approved: 'Approved',
        rejected: 'Rejected',
        pending: 'Pending',
    }[status] ?? escapeHtml(status || '-');
}

function renderEvidenceButton(url) {
    if (!url) {
        return `
            <span class="inline-flex items-center px-3 py-2 rounded-xl bg-red-50 text-red-600 font-semibold text-sm">
                Tidak ada link bukti
            </span>
        `;
    }

    const safeUrl = escapeHtml(url);

    return `
        <a href="${safeUrl}"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl font-semibold">
            Buka Link Bukti
        </a>
    `;
}

function openViewModal(id) {
    fetch(`${DAILY_TASK_BASE_PATH}/${id}`)
        .then(async res => {
            if (!res.ok) {
                const body = await res.text();

                console.error('Daily Task detail error:', {
                    status: res.status,
                    url: res.url,
                    body: body,
                });

                throw new Error(`Gagal mengambil detail Daily Task. Status: ${res.status}`);
            }

            return res.json();
        })
        .then(data => {
            const status = data.iki?.status ?? data.rk_anggota?.status ?? data.status ?? '-';
            const statusClass = getStatusClass(status);
            const statusLabel = getStatusLabel(status);

            document.getElementById('viewContent').innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <div class="p-4 rounded-xl bg-gray-50 border border-gray-100">
                        <div class="text-xs text-gray-500 mb-1">
                            Project
                        </div>
                        <div class="font-semibold text-gray-900">
                            ${escapeHtml(data.rk_anggota?.project?.name)}
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-gray-50 border border-gray-100">
                        <div class="text-xs text-gray-500 mb-1">
                            Anggota
                        </div>
                        <div class="font-semibold text-gray-900">
                            ${escapeHtml(data.rk_anggota?.user?.name)}
                        </div>
                    </div>

                </div>

                <div class="mt-4 p-4 rounded-xl border border-gray-100 bg-white">
                    <div class="text-xs text-gray-500 mb-1">
                        RK Anggota
                    </div>
                    <div class="font-semibold text-gray-900 leading-relaxed">
                        ${escapeHtml(data.rk_anggota?.description)}
                    </div>
                </div>

                <div class="mt-4 p-4 rounded-xl border border-gray-100 bg-white">
                    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3">
                        <div>
                            <div class="text-xs text-gray-500 mb-1">
                                IKI
                            </div>
                            <div class="font-semibold text-gray-900 leading-relaxed">
                                ${escapeHtml(data.iki?.description)}
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                Target: ${escapeHtml(data.iki?.target)} ${escapeHtml(data.iki?.unit ?? '')}
                            </div>
                        </div>

                        <span class="px-3 py-1 rounded-full text-xs font-semibold ${statusClass}">
                            ${statusLabel}
                        </span>
                    </div>
                </div>

                <div class="mt-4 p-4 rounded-xl border border-gray-100 bg-white">
                    <div class="text-xs text-gray-500 mb-1">
                        Tanggal Pelaksanaan
                    </div>

                    <div class="text-lg font-semibold text-gray-900 mb-4">
                        ${escapeHtml(data.date)}
                    </div>

                    <div class="text-xs text-gray-500 mb-1">
                        Aktivitas
                    </div>

                    <div class="text-gray-800 leading-relaxed whitespace-pre-line">
                        ${escapeHtml(data.activity)}
                    </div>
                </div>

                <div class="mt-4 p-4 rounded-xl bg-blue-50 border border-blue-100">
                    <div class="text-xs text-blue-600 mb-2 font-semibold">
                        Link Bukti Kerja
                    </div>

                    ${renderEvidenceButton(data.evidence_url)}
                </div>

                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">

                    <div class="p-4 rounded-xl bg-gray-50 border border-gray-100">
                        <div class="text-xs text-gray-500 mb-1">
                            Jam Dibuat
                        </div>
                        <div class="font-semibold text-gray-800">
                            ${formatDate(data.created_at)}
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-gray-50 border border-gray-100">
                        <div class="text-xs text-gray-500 mb-1">
                            Jam Update Terakhir
                        </div>
                        <div class="font-semibold text-gray-800">
                            ${formatDate(data.updated_at)}
                        </div>
                    </div>

                </div>
            `;

            openModal('modalView');
        })
        .catch(error => {
            console.error(error);
            alert(error.message || 'Gagal mengambil detail Daily Task.');
        });
}

function openEditModal(id) {
    fetch(`${DAILY_TASK_BASE_PATH}/${id}`)
        .then(res => {
            if (!res.ok) {
                throw new Error('Gagal mengambil data Daily Task.');
            }

            return res.json();
        })
        .then(data => {
            document.getElementById('formEdit').action = `${DAILY_TASK_BASE_PATH}/${id}`;

            document.getElementById('edit_date').value = data.date ?? '';
            document.getElementById('edit_activity').value = data.activity ?? '';
            document.getElementById('edit_output').value = data.output ?? '-';
            document.getElementById('edit_evidence').value = data.evidence_url ?? '';

            openModal('modalEdit');
        })
        .catch(() => {
            alert('Gagal mengambil data Daily Task.');
        });
}
</script>

</x-app-layout>