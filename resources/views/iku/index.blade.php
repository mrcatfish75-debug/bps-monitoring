<x-app-layout>

@php
    $role = auth()->user()->role;
    $isAdmin = $role === 'admin';
    $isKepala = $role === 'kepala';

    $indexUrl = $isKepala
        ? url('/kepala/iku')
        : route('iku.index');

    $ikuBasePath = $isKepala
        ? '/kepala/iku'
        : ($isAdmin ? '/admin/iku' : '/iku');

    /*
    |--------------------------------------------------------------------------
    | Safe data untuk JS Template Picker
    |--------------------------------------------------------------------------
    */
    $ikuTemplateOptions = ($ikuTemplates ?? collect())
        ->map(function ($iku) {
            return [
                'name' => $iku->name,
                'year' => $iku->year,
                'satuan' => $iku->satuan,
                'target' => $iku->target,
            ];
        })
        ->values();
@endphp

<div class="bg-white p-4 sm:p-6 rounded-xl shadow">

    <!-- ================= HEADER ================= -->
    <div class="flex flex-col gap-3 md:flex-row md:justify-between md:items-start mb-6">
        <div>
            <h2 class="text-xl font-bold text-gray-900">
                Indikator Kinerja Utama (IKU)
            </h2>

            <p class="text-sm text-gray-500 mt-1">
                Progress IKU dihitung dari RK Ketua → Project → RK Anggota → IKI. Approval dilakukan pada level IKI.
            </p>
        </div>

        <div class="flex flex-col sm:flex-row gap-2 w-full md:w-auto">
            <a href="{{ $indexUrl }}"
                class="w-full sm:w-auto text-center px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300 text-sm font-medium">
                Refresh
            </a>

            @if($isAdmin)
                <button type="button"
                    onclick="openImportModal()"
                    class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow text-sm font-semibold">
                    Import Excel
                </button>

                <button type="button"
                    onclick="openCreateModal()"
                    class="w-full sm:w-auto bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg shadow text-sm font-semibold">
                    + Add IKU
                </button>
            @endif
        </div>
    </div>

    <!-- ================= FILTER + SEARCH ================= -->
    <form method="GET"
        class="mb-5"
        onsubmit="return false;">

        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">

            <div class="md:col-span-3">
                <select id="yearFilter"
                    name="year"
                    class="border px-3 py-2 rounded-lg w-full bg-white focus:ring focus:ring-green-100 focus:border-green-500">
                    @for($y = date('Y'); $y >= 2020; $y--)
                        <option value="{{ $y }}" {{ request('year', $year) == $y ? 'selected' : '' }}>
                            {{ $y }}
                        </option>
                    @endfor
                </select>
            </div>

            <div class="md:col-span-6">
                <input type="text"
                    id="searchInput"
                    placeholder="Cari IKU, RK Ketua, Project, RK Anggota, atau IKI..."
                    value="{{ request('search') }}"
                    autocomplete="off"
                    class="border px-3 py-2 rounded-lg w-full focus:ring focus:ring-green-100 focus:border-green-500">
            </div>

            <a href="{{ $indexUrl }}"
                class="md:col-span-1 bg-gray-200 text-center px-4 py-2 rounded-lg hover:bg-gray-300">
                Reset
            </a>

            <button type="button"
                onclick="searchIkuNow()"
                class="md:col-span-2 bg-gray-800 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">
                Filter
            </button>

        </div>
    </form>

    <!-- ================= DESKTOP TABLE ================= -->
    <div class="hidden md:block overflow-x-auto rounded-xl border">
        <table class="w-full text-sm min-w-[1100px]">

            <thead class="bg-gray-50 border-b">
                <tr class="text-left">
                    <th class="p-3">IKU</th>
                    <th class="p-3">Tahun</th>
                    <th class="p-3">Target</th>
                    <th class="p-3 text-center">RK Ketua</th>
                    <th class="p-3 text-center">Project</th>
                    <th class="p-3 text-center">IKI</th>
                    <th class="p-3 text-center">Daily Task</th>
                    <th class="p-3">Progress</th>
                    <th class="p-3">Status</th>
                    <th class="p-3 text-center w-[120px]">Aksi</th>
                </tr>
            </thead>

            <tbody id="ikuTableBody">
                @forelse($ikus as $iku)
                    @php
                        $progress = (int) ($iku->progress ?? 0);

                        $statusLabel = match (true) {
                            $progress >= 100 => 'Selesai',
                            $progress > 0 => 'Berjalan',
                            default => 'Belum Berjalan',
                        };

                        $statusClass = match (true) {
                            $progress >= 100 => 'bg-green-100 text-green-700',
                            $progress > 0 => 'bg-blue-100 text-blue-700',
                            default => 'bg-gray-100 text-gray-600',
                        };

                        $rkKetuaCount = $iku->rk_ketua_count ?? $iku->rkKetuas->count();
                        $projectCount = $iku->project_count ?? 0;
                        $totalIki = $iku->total_iki_count ?? 0;
                        $approvedIki = $iku->approved_iki_count ?? 0;
                        $dailyTaskCount = $iku->daily_task_count ?? 0;
                    @endphp

                    <tr class="border-b last:border-b-0 hover:bg-gray-50">

                        <td class="p-3 font-medium">
                            <div class="font-semibold text-gray-900">
                                {{ $iku->name }}
                            </div>

                            @if($iku->code ?? null)
                                <div class="text-xs text-gray-400 mt-1">
                                    Kode: {{ $iku->code }}
                                </div>
                            @endif
                        </td>

                        <td class="p-3">
                            {{ $iku->year }}
                        </td>

                        <td class="p-3">
                            <span class="font-semibold">
                                {{ $iku->target ?? 100 }} {{ $iku->satuan ?? '%' }}
                            </span>
                        </td>

                        <td class="p-3 text-center">
                            <div class="font-semibold text-gray-900">
                                {{ $rkKetuaCount }}
                            </div>
                            <div class="text-xs text-gray-400">
                                RK Ketua
                            </div>
                        </td>

                        <td class="p-3 text-center">
                            <div class="font-semibold text-gray-900">
                                {{ $projectCount }}
                            </div>
                            <div class="text-xs text-gray-400">
                                Project
                            </div>
                        </td>

                        <td class="p-3 text-center">
                            <div class="font-semibold text-gray-900">
                                {{ $approvedIki }}/{{ $totalIki }}
                            </div>
                            <div class="text-xs text-gray-400">
                                IKI approved
                            </div>

                            @if(($iku->submitted_iki_count ?? 0) > 0)
                                <div class="text-xs text-blue-600 mt-1">
                                    {{ $iku->submitted_iki_count }} review
                                </div>
                            @endif

                            @if(($iku->rejected_iki_count ?? 0) > 0)
                                <div class="text-xs text-red-600 mt-1">
                                    {{ $iku->rejected_iki_count }} revisi
                                </div>
                            @endif
                        </td>

                        <td class="p-3 text-center">
                            <div class="font-semibold text-gray-900">
                                {{ $dailyTaskCount }}
                            </div>
                            <div class="text-xs text-gray-400">
                                task
                            </div>
                        </td>

                        <td class="p-3">
                            <div class="w-full bg-gray-200 rounded h-2">
                                <div class="bg-green-500 h-2 rounded"
                                    style="width: {{ min($progress, 100) }}%">
                                </div>
                            </div>
                            <small class="text-gray-600">{{ $progress }}%</small>
                        </td>

                        <td class="p-3">
                            <span class="px-2 py-1 rounded text-xs font-semibold {{ $statusClass }}">
                                {{ $statusLabel }}
                            </span>
                        </td>

                        <td class="p-3 text-center relative">
                            <div class="relative inline-block text-left">

                                <button type="button"
                                    data-iku-action-button
                                    onclick="toggleIkuActionMenu('iku-action-menu-desktop-{{ $iku->id }}', event)"
                                    class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                                    Aksi
                                    <span class="text-xs">▾</span>
                                </button>

                                <div id="iku-action-menu-desktop-{{ $iku->id }}"
                                    data-iku-action-menu
                                    class="hidden absolute right-0 top-full z-50 mt-2 w-44 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl">

                                    <button type="button"
                                        onclick="closeAllIkuActionMenus(); openViewModalById({{ $iku->id }})"
                                        class="block w-full px-4 py-2 text-left text-sm text-blue-600 hover:bg-blue-50">
                                        View
                                    </button>

                                    @if($isAdmin)
                                        <button type="button"
                                            data-iku-id="{{ $iku->id }}"
                                            data-iku-name="{{ $iku->name }}"
                                            data-iku-year="{{ $iku->year }}"
                                            data-iku-satuan="{{ $iku->satuan }}"
                                            data-iku-target="{{ $iku->target }}"
                                            onclick="openEditModalFromButton(this)"
                                            class="block w-full px-4 py-2 text-left text-sm text-yellow-600 hover:bg-yellow-50">
                                            Edit
                                        </button>

                                        <form method="POST"
                                            action="{{ url('/admin/iku/'.$iku->id) }}"
                                            onsubmit="return confirm('Yakin hapus IKU ini?')">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit"
                                                class="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50">
                                                Delete
                                            </button>
                                        </form>
                                    @endif

                                </div>
                            </div>
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center py-5 text-gray-400">
                            Data IKU tidak ditemukan
                        </td>
                    </tr>
                @endforelse
            </tbody>

        </table>
    </div>

    <!-- ================= MOBILE CARDS ================= -->
    <div id="ikuMobileCards" class="md:hidden space-y-3">
        @forelse($ikus as $iku)
            @php
                $progress = (int) ($iku->progress ?? 0);

                $statusLabel = match (true) {
                    $progress >= 100 => 'Selesai',
                    $progress > 0 => 'Berjalan',
                    default => 'Belum Berjalan',
                };

                $statusClass = match (true) {
                    $progress >= 100 => 'bg-green-100 text-green-700',
                    $progress > 0 => 'bg-blue-100 text-blue-700',
                    default => 'bg-gray-100 text-gray-600',
                };

                $rkKetuaCount = $iku->rk_ketua_count ?? $iku->rkKetuas->count();
                $projectCount = $iku->project_count ?? 0;
                $totalIki = $iku->total_iki_count ?? 0;
                $approvedIki = $iku->approved_iki_count ?? 0;
                $dailyTaskCount = $iku->daily_task_count ?? 0;
            @endphp

            <div class="rounded-xl border bg-white p-4 shadow-sm">

                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="font-semibold text-gray-900 break-words">
                            {{ $iku->name }}
                        </h3>

                        @if($iku->code ?? null)
                            <p class="text-xs text-gray-400 mt-1">
                                Kode: {{ $iku->code }}
                            </p>
                        @endif

                        <p class="text-sm text-gray-500 mt-1">
                            Tahun {{ $iku->year }} · Target {{ $iku->target ?? 100 }} {{ $iku->satuan ?? '%' }}
                        </p>
                    </div>

                    <div class="relative shrink-0">
                        <button type="button"
                            data-iku-action-button
                            onclick="toggleIkuActionMenu('iku-action-menu-mobile-{{ $iku->id }}', event)"
                            class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                            Aksi
                            <span class="text-xs">▾</span>
                        </button>

                        <div id="iku-action-menu-mobile-{{ $iku->id }}"
                            data-iku-action-menu
                            class="hidden absolute right-0 top-full z-50 mt-2 w-44 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl">

                            <button type="button"
                                onclick="closeAllIkuActionMenus(); openViewModalById({{ $iku->id }})"
                                class="block w-full px-4 py-2 text-left text-sm text-blue-600 hover:bg-blue-50">
                                View
                            </button>

                            @if($isAdmin)
                                <button type="button"
                                    data-iku-id="{{ $iku->id }}"
                                    data-iku-name="{{ $iku->name }}"
                                    data-iku-year="{{ $iku->year }}"
                                    data-iku-satuan="{{ $iku->satuan }}"
                                    data-iku-target="{{ $iku->target }}"
                                    onclick="openEditModalFromButton(this)"
                                    class="block w-full px-4 py-2 text-left text-sm text-yellow-600 hover:bg-yellow-50">
                                    Edit
                                </button>

                                <form method="POST"
                                    action="{{ url('/admin/iku/'.$iku->id) }}"
                                    onsubmit="return confirm('Yakin hapus IKU ini?')">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit"
                                        class="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50">
                                        Delete
                                    </button>
                                </form>
                            @endif

                        </div>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-2 text-xs">
                    <div class="rounded-lg bg-gray-50 border p-2">
                        <div class="text-gray-400">RK Ketua</div>
                        <div class="font-semibold text-gray-900">{{ $rkKetuaCount }}</div>
                    </div>

                    <div class="rounded-lg bg-gray-50 border p-2">
                        <div class="text-gray-400">Project</div>
                        <div class="font-semibold text-gray-900">{{ $projectCount }}</div>
                    </div>

                    <div class="rounded-lg bg-gray-50 border p-2">
                        <div class="text-gray-400">IKI Approved</div>
                        <div class="font-semibold text-gray-900">{{ $approvedIki }}/{{ $totalIki }}</div>
                    </div>

                    <div class="rounded-lg bg-gray-50 border p-2">
                        <div class="text-gray-400">Daily Task</div>
                        <div class="font-semibold text-gray-900">{{ $dailyTaskCount }}</div>
                    </div>
                </div>

                <div class="mt-4">
                    <div class="flex justify-between mb-1">
                        <span class="text-xs text-gray-500">Progress</span>
                        <span class="text-xs font-semibold text-gray-700">{{ $progress }}%</span>
                    </div>

                    <div class="w-full bg-gray-200 rounded h-2">
                        <div class="bg-green-500 h-2 rounded"
                            style="width: {{ min($progress, 100) }}%">
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <span class="px-2 py-1 rounded text-xs font-semibold {{ $statusClass }}">
                        {{ $statusLabel }}
                    </span>
                </div>

            </div>
        @empty
            <div class="rounded-xl border p-5 text-center text-gray-400">
                Data IKU tidak ditemukan
            </div>
        @endforelse
    </div>

    <div class="mt-4 flex justify-center">
        {{ $ikus->withQueryString()->links() }}
    </div>

</div>

<!-- ================= CREATE MODAL ================= -->
@if($isAdmin)
<div id="modalCreate"
    class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4">

    <div class="bg-white rounded-2xl w-full sm:max-w-[760px] max-h-[90vh] overflow-y-auto shadow-xl">

        <div class="flex items-start justify-between px-5 sm:px-6 py-5 border-b">
            <div>
                <h3 class="text-xl font-bold text-gray-900">
                    Tambah IKU
                </h3>
                <p class="text-sm text-gray-500 mt-1">
                    Buat Indikator Kinerja Utama sebagai target kerja tahunan.
                </p>
            </div>

            <button type="button"
                onclick="closeModal('modalCreate')"
                class="text-gray-400 hover:text-red-500 text-2xl leading-none">
                ×
            </button>
        </div>

        <form method="POST" action="{{ route('iku.store') }}" class="px-5 sm:px-6 py-6">
            @csrf

            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Nama IKU
                </label>

                <input
                    id="create_name"
                    name="name"
                    type="text"
                    placeholder="Tulis nama IKU atau pilih dari template..."
                    autocomplete="off"
                    class="border w-full p-3 rounded-lg focus:ring focus:ring-green-100 focus:border-green-500"
                    required>

                <div id="createIkuTemplateDropdown"
                    class="hidden mt-2 max-h-48 overflow-y-auto rounded-xl border bg-white shadow-lg">
                </div>

                <p class="text-xs text-gray-400 mt-2">
                    Klik kolom ini untuk memilih template dari data IKU yang sudah ada/import, atau ketik manual.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        Tahun
                    </label>

                    <input
                        id="create_year"
                        name="year"
                        type="number"
                        min="2020"
                        max="2100"
                        value="{{ $year ?? date('Y') }}"
                        placeholder="Tahun IKU"
                        class="border w-full p-3 rounded-lg focus:ring focus:ring-green-100 focus:border-green-500"
                        required>

                    <p class="text-xs text-gray-400 mt-2">
                        Tahun periode IKU.
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        Satuan
                    </label>

                    <input
                        id="create_satuan"
                        name="satuan"
                        type="text"
                        value="%"
                        placeholder="Contoh: %, Dokumen, Kegiatan"
                        class="border w-full p-3 rounded-lg focus:ring focus:ring-green-100 focus:border-green-500"
                        required>

                    <p class="text-xs text-gray-400 mt-2">
                        Sesuaikan dengan satuan target.
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        Target Capaian
                    </label>

                    <input
                        id="create_target"
                        name="target"
                        type="number"
                        min="0"
                        step="0.01"
                        value="100"
                        placeholder="Target capaian"
                        class="border w-full p-3 rounded-lg focus:ring focus:ring-green-100 focus:border-green-500"
                        required>

                    <p class="text-xs text-gray-400 mt-2">
                        Contoh: 100, 12, 95.5.
                    </p>
                </div>
            </div>

            <div class="mb-6 rounded-xl bg-blue-50 border border-blue-100 px-4 py-3">
                <p class="text-sm text-blue-700">
                    Progress IKU dihitung otomatis dari RK Ketua, Project, RK Anggota, dan IKI yang disetujui. Daily Task digunakan sebagai monitoring aktivitas di bawah IKI.
                </p>
            </div>

            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-4 border-t">
                <button type="button"
                    onclick="closeModal('modalCreate')"
                    class="w-full sm:w-auto bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg">
                    Cancel
                </button>

                <button type="submit"
                    class="w-full sm:w-auto bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded-lg shadow">
                    Save
                </button>
            </div>

        </form>

    </div>
</div>
@endif

<!-- ================= EDIT MODAL ================= -->
@if($isAdmin)
<div id="modalEdit"
    class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4">

    <div class="bg-white rounded-2xl w-full sm:max-w-[760px] max-h-[90vh] overflow-y-auto shadow-xl">

        <div class="flex items-start justify-between px-5 sm:px-6 py-5 border-b">
            <div>
                <h3 class="text-xl font-bold text-gray-900">
                    Edit IKU
                </h3>
                <p class="text-sm text-gray-500 mt-1">
                    Perbarui data IKU tanpa mengubah flow monitoring utama.
                </p>
            </div>

            <button type="button"
                onclick="closeModal('modalEdit')"
                class="text-gray-400 hover:text-red-500 text-2xl leading-none">
                ×
            </button>
        </div>

        <form method="POST" id="formEdit" class="px-5 sm:px-6 py-6">
            @csrf
            @method('PUT')

            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Nama IKU
                </label>

                <input
                    id="edit_name"
                    name="name"
                    type="text"
                    placeholder="Tulis nama IKU..."
                    class="border w-full p-3 rounded-lg focus:ring focus:ring-yellow-100 focus:border-yellow-500"
                    required>

                <p class="text-xs text-gray-400 mt-2">
                    Gunakan nama IKU yang jelas dan konsisten dengan indikator yang dimonitor.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        Tahun
                    </label>

                    <input
                        id="edit_year"
                        name="year"
                        type="number"
                        min="2020"
                        max="2100"
                        placeholder="Tahun IKU"
                        class="border w-full p-3 rounded-lg focus:ring focus:ring-yellow-100 focus:border-yellow-500"
                        required>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        Satuan
                    </label>

                    <input
                        id="edit_satuan"
                        name="satuan"
                        type="text"
                        placeholder="Contoh: %, Dokumen, Kegiatan"
                        class="border w-full p-3 rounded-lg focus:ring focus:ring-yellow-100 focus:border-yellow-500"
                        required>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        Target Capaian
                    </label>

                    <input
                        id="edit_target"
                        name="target"
                        type="number"
                        min="0"
                        step="0.01"
                        placeholder="Target capaian"
                        class="border w-full p-3 rounded-lg focus:ring focus:ring-yellow-100 focus:border-yellow-500"
                        required>
                </div>
            </div>

            <div class="mb-6 rounded-xl bg-yellow-50 border border-yellow-100 px-4 py-3">
                <p class="text-sm text-yellow-700">
                    Perubahan target atau nama IKU dapat memengaruhi pembacaan monitoring pada RK Ketua, Project, RK Anggota, dan IKI.
                </p>
            </div>

            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-4 border-t">
                <button type="button"
                    onclick="closeModal('modalEdit')"
                    class="w-full sm:w-auto bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg">
                    Cancel
                </button>

                <button type="submit"
                    class="w-full sm:w-auto bg-yellow-500 hover:bg-yellow-600 text-white px-5 py-2 rounded-lg shadow">
                    Update
                </button>
            </div>

        </form>

    </div>
</div>
@endif

<!-- ================= VIEW MODAL ================= -->
<div id="modalView"
    class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4">

    <div class="bg-white rounded-2xl w-full sm:max-w-[1100px] max-h-[90vh] overflow-y-auto shadow-xl">

        <div class="p-5 sm:p-6 border-b flex justify-between items-start">
            <div>
                <h3 id="view_iku_name" class="text-xl font-bold text-gray-900">
                    Detail IKU
                </h3>
                <p id="view_iku_meta" class="text-sm text-gray-500 mt-1"></p>
            </div>

            <button type="button"
                onclick="closeModal('modalView')"
                class="text-gray-400 hover:text-red-500 text-2xl leading-none">
                ×
            </button>
        </div>

        <div id="viewContent" class="p-5 sm:p-6 space-y-5">

            <div>
                <div class="flex justify-between mb-1">
                    <span class="text-sm font-semibold text-gray-700">Progress IKU</span>
                    <span id="view_iku_progress_text" class="text-sm font-bold text-gray-700">0%</span>
                </div>

                <div class="w-full bg-gray-200 rounded h-3">
                    <div id="view_iku_progress_bar" class="bg-green-500 h-3 rounded" style="width: 0%"></div>
                </div>

                <p class="text-xs text-gray-500 mt-2">
                    Progress IKU dihitung dari rata-rata progress RK Ketua. Approval utama dilakukan pada level IKI.
                </p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-6 gap-3">
                <div class="bg-blue-50 border border-blue-100 rounded-lg p-4">
                    <div class="text-xs text-blue-600 font-semibold">Team</div>
                    <div id="view_team_count" class="text-2xl font-bold text-blue-700">0</div>
                </div>

                <div class="bg-purple-50 border border-purple-100 rounded-lg p-4">
                    <div class="text-xs text-purple-600 font-semibold">RK Ketua</div>
                    <div id="view_rk_ketua_count" class="text-2xl font-bold text-purple-700">0</div>
                </div>

                <div class="bg-indigo-50 border border-indigo-100 rounded-lg p-4">
                    <div class="text-xs text-indigo-600 font-semibold">Project</div>
                    <div id="view_project_count" class="text-2xl font-bold text-indigo-700">0</div>
                </div>

                <div class="bg-sky-50 border border-sky-100 rounded-lg p-4">
                    <div class="text-xs text-sky-600 font-semibold">RK Anggota</div>
                    <div id="view_rk_anggota_count" class="text-2xl font-bold text-sky-700">0</div>
                </div>

                <div class="bg-emerald-50 border border-emerald-100 rounded-lg p-4">
                    <div class="text-xs text-emerald-600 font-semibold">IKI Approved</div>
                    <div id="view_iki_approved_count" class="text-2xl font-bold text-emerald-700">0/0</div>
                </div>

                <div class="bg-gray-50 border rounded-lg p-4">
                    <div class="text-xs text-gray-600 font-semibold">Daily Task</div>
                    <div id="view_daily_task_count" class="text-2xl font-bold text-gray-700">0</div>
                </div>
            </div>

            <div class="rounded-lg bg-blue-50 border border-blue-100 px-4 py-3 text-sm text-blue-700">
                IKU memantau RK Ketua. RK Ketua memantau Project. Project memantau RK Anggota. RK Anggota memantau IKI dan Daily Task.
            </div>

            <div class="border rounded-lg">
                <div class="bg-gray-50 p-3 border-b font-semibold">
                    Team yang mengerjakan IKU
                </div>
                <div id="view_teams" class="p-3 text-sm text-gray-700">
                    -
                </div>
            </div>

            <div class="border rounded-lg">
                <div class="bg-gray-50 p-3 border-b font-semibold">
                    RK Ketua, Project, IKI, dan Daily Task
                </div>
                <div id="view_rk_ketuas" class="p-3 space-y-3 text-sm">
                    -
                </div>
            </div>

        </div>

    </div>
</div>

<!-- ================= IMPORT MODAL ================= -->
@if($isAdmin)
<div id="modalImport"
    class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4">

    <div class="bg-white rounded-2xl w-full sm:max-w-[520px] max-h-[90vh] overflow-y-auto shadow-xl">

        <div class="px-5 sm:px-6 py-4 border-b flex justify-between items-start">
            <div>
                <h3 class="text-xl font-bold text-gray-900">
                    Import IKU dari Excel
                </h3>
                <p class="text-sm text-gray-500 mt-1">
                    Upload file Excel untuk memasukkan data IKU ke database.
                </p>
            </div>

            <button type="button"
                onclick="closeModal('modalImport')"
                class="text-gray-400 hover:text-red-500 text-2xl leading-none">
                ×
            </button>
        </div>

        <form method="POST"
            action="{{ route('iku.import') }}"
            enctype="multipart/form-data"
            class="p-5 sm:p-6">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    File Excel
                </label>

                <input type="file"
                    name="file"
                    accept=".xlsx,.xls,.csv"
                    class="border w-full p-3 rounded-lg bg-white"
                    required>

                <div class="mt-2 rounded-lg bg-yellow-50 border border-yellow-100 p-3 text-xs text-yellow-700">
                    <div class="font-semibold mb-1">
                        Format heading Excel wajib:
                    </div>

                    <div class="font-mono bg-white border border-yellow-100 rounded px-2 py-1 text-yellow-900">
                        name | year | satuan | target
                    </div>

                    <p class="mt-2">
                        Contoh satuan: %, Dokumen, Kegiatan, Publikasi.
                    </p>
                </div>
            </div>

            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-4 border-t">
                <button type="button"
                    onclick="closeModal('modalImport')"
                    class="w-full sm:w-auto bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg">
                    Cancel
                </button>

                <button type="submit"
                    class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg shadow">
                    Import
                </button>
            </div>
        </form>

    </div>
</div>
@endif

<!-- ================= SCRIPT ================= -->
<script>
const csrfToken = @json(csrf_token());
const deleteBaseUrl = @json(url('/admin/iku'));
const isAdmin = @json($isAdmin);
const IKU_BASE_PATH = @json($ikuBasePath);
const IKU_TEMPLATE_OPTIONS = @json($ikuTemplateOptions);

let ikuSearchCache = {};
let timer;

/*
|--------------------------------------------------------------------------
| Utilities
|--------------------------------------------------------------------------
*/

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function escapeAttribute(value) {
    return escapeHtml(value).replaceAll('\n', '&#10;');
}

/*
|--------------------------------------------------------------------------
| Row Action Dropdown
|--------------------------------------------------------------------------
*/

function closeAllIkuActionMenus() {
    document.querySelectorAll('[data-iku-action-menu]').forEach(menu => {
        menu.classList.add('hidden');
    });
}

function toggleIkuActionMenu(menuId, event = null) {
    if (event) {
        event.stopPropagation();
    }

    const targetMenu = document.getElementById(menuId);

    if (!targetMenu) {
        return;
    }

    const isCurrentlyHidden = targetMenu.classList.contains('hidden');

    closeAllIkuActionMenus();

    if (isCurrentlyHidden) {
        targetMenu.classList.remove('hidden');
    }
}

document.addEventListener('click', function (event) {
    const clickedButton = event.target.closest('[data-iku-action-button]');
    const clickedMenu = event.target.closest('[data-iku-action-menu]');

    if (!clickedButton && !clickedMenu) {
        closeAllIkuActionMenus();
    }
});

/*
|--------------------------------------------------------------------------
| Modal Helpers
|--------------------------------------------------------------------------
*/

function closeModal(id){
    const modal = document.getElementById(id);

    if (modal) {
        modal.classList.add('hidden');
    }
}

function openImportModal(){
    if (!isAdmin) {
        return;
    }

    const modal = document.getElementById('modalImport');

    if (modal) {
        modal.classList.remove('hidden');
    }
}

function openCreateModal(){
    if (!isAdmin) {
        return;
    }

    const modal = document.getElementById('modalCreate');

    if (!modal) {
        return;
    }

    modal.classList.remove('hidden');

    setTimeout(() => {
        const input = document.getElementById('create_name');
        if (input) input.focus();
    }, 50);
}

function openEditModalFromButton(button) {
    closeAllIkuActionMenus();

    openEditModal({
        id: button.dataset.ikuId,
        name: button.dataset.ikuName,
        year: button.dataset.ikuYear,
        satuan: button.dataset.ikuSatuan,
        target: button.dataset.ikuTarget,
    });
}

function openEditModal(data){
    if (!isAdmin) {
        return;
    }

    if (!data || !data.id) {
        console.error('Data IKU tidak valid:', data);
        return;
    }

    const modal = document.getElementById('modalEdit');

    if (!modal) {
        return;
    }

    modal.classList.remove('hidden');

    document.getElementById('edit_name').value = data.name ?? '';
    document.getElementById('edit_year').value = data.year ?? new Date().getFullYear();
    document.getElementById('edit_satuan').value = data.satuan ?? '%';
    document.getElementById('edit_target').value = data.target ?? 100;

    document.getElementById('formEdit').action = `/admin/iku/${data.id}`;

    setTimeout(() => {
        document.getElementById('edit_name')?.focus();
    }, 50);
}

function openEditModalFromCache(id) {
    if (ikuSearchCache[id]) {
        openEditModal(ikuSearchCache[id]);
    }
}

/*
|--------------------------------------------------------------------------
| View Detail
|--------------------------------------------------------------------------
*/

function openViewModalById(id) {
    closeAllIkuActionMenus();

    const modal = document.getElementById('modalView');

    if (modal) {
        modal.classList.remove('hidden');
    }

    setViewLoading();

    fetch(`${IKU_BASE_PATH}/${id}`, {
        headers: {
            'Accept': 'application/json',
        },
    })
        .then(async res => {
            if (!res.ok) {
                const body = await res.text();

                console.error('IKU DETAIL ERROR RESPONSE:', {
                    status: res.status,
                    url: res.url,
                    body,
                });

                throw new Error(`Gagal mengambil detail IKU. Status: ${res.status}`);
            }

            return res.json();
        })
        .then(data => {
            renderIkuView(data);
        })
        .catch(error => {
            console.error('IKU detail error:', error);

            document.getElementById('view_rk_ketuas').innerHTML = `
                <div class="p-4 rounded bg-red-50 text-red-700 border border-red-100">
                    Gagal memuat detail IKU.
                </div>
            `;
        });
}

function openViewModal(data) {
    document.getElementById('modalView').classList.remove('hidden');
    renderIkuView(data);
}

function openViewModalFromCache(id) {
    closeAllIkuActionMenus();

    if (ikuSearchCache[id]) {
        openViewModal(ikuSearchCache[id]);
    } else {
        openViewModalById(id);
    }
}

function setViewLoading() {
    document.getElementById('view_iku_name').innerText = 'Detail IKU';
    document.getElementById('view_iku_meta').innerText = 'Memuat data...';
    document.getElementById('view_iku_progress_text').innerText = '0%';
    document.getElementById('view_iku_progress_bar').style.width = '0%';

    document.getElementById('view_team_count').innerText = '0';
    document.getElementById('view_rk_ketua_count').innerText = '0';
    document.getElementById('view_project_count').innerText = '0';
    document.getElementById('view_rk_anggota_count').innerText = '0';
    document.getElementById('view_iki_approved_count').innerText = '0/0';
    document.getElementById('view_daily_task_count').innerText = '0';

    document.getElementById('view_teams').innerHTML = `<span class="text-gray-400">Memuat...</span>`;
    document.getElementById('view_rk_ketuas').innerHTML = `<div class="text-gray-400">Memuat...</div>`;
}

function renderIkuView(data) {
    const progress = Number(data.progress ?? 0);
    const rkKetuas = data.rk_ketuas ?? data.rkKetuas ?? [];

    const teams = collectUniqueTeams(rkKetuas);

    const projectCount = Number(data.project_count ?? 0);
    const rkAnggotaCount = Number(data.rk_anggota_count ?? 0);
    const approvedIki = Number(data.approved_iki_count ?? 0);
    const totalIki = Number(data.total_iki_count ?? 0);
    const dailyTaskCount = Number(data.daily_task_count ?? 0);

    document.getElementById('view_iku_name').innerText = data.name ?? 'Detail IKU';
    document.getElementById('view_iku_meta').innerText = `Tahun ${data.year ?? '-'} · Target ${data.target ?? 100} ${data.satuan ?? '%'}`;
    document.getElementById('view_iku_progress_text').innerText = `${progress}%`;
    document.getElementById('view_iku_progress_bar').style.width = `${Math.min(progress, 100)}%`;

    document.getElementById('view_team_count').innerText = teams.length;
    document.getElementById('view_rk_ketua_count').innerText = data.rk_ketua_count ?? rkKetuas.length;
    document.getElementById('view_project_count').innerText = projectCount;
    document.getElementById('view_rk_anggota_count').innerText = rkAnggotaCount;
    document.getElementById('view_iki_approved_count').innerText = `${approvedIki}/${totalIki}`;
    document.getElementById('view_daily_task_count').innerText = dailyTaskCount;

    document.getElementById('view_teams').innerHTML = teams.length
        ? teams.map(team => `
            <span class="inline-block bg-blue-50 text-blue-700 px-3 py-1 rounded-full mr-2 mb-2 font-semibold">
                ${escapeHtml(team.name)}
            </span>
        `).join('')
        : `<span class="text-gray-400">Belum ada team/RK Ketua yang terhubung ke IKU ini.</span>`;

    document.getElementById('view_rk_ketuas').innerHTML = rkKetuas.length
        ? rkKetuas.map(rk => renderRkKetuaItem(rk)).join('')
        : `<div class="text-gray-400">Belum ada RK Ketua untuk IKU ini.</div>`;
}

function collectUniqueTeams(rkKetuas) {
    const teamMap = {};

    rkKetuas.forEach(rk => {
        if (rk.team && rk.team.id) {
            teamMap[rk.team.id] = {
                id: rk.team.id,
                name: rk.team.name ?? '-',
            };
        }
    });

    return Object.values(teamMap);
}

function renderRkKetuaItem(rk) {
    const projects = rk.projects ?? [];
    const progress = Number(rk.progress ?? 0);

    const totalIki = Number(rk.total_iki_count ?? 0);
    const approvedIki = Number(rk.approved_iki_count ?? 0);
    const dailyTaskCount = Number(rk.daily_task_count ?? 0);
    const rkAnggotaCount = Number(rk.rk_anggota_count ?? 0);

    return `
        <div class="border rounded-lg p-4">
            <div class="flex flex-col md:flex-row md:justify-between gap-3">
                <div>
                    <div class="font-semibold text-gray-900">
                        ${escapeHtml(rk.description ?? '-')}
                    </div>

                    <div class="text-xs text-gray-500 mt-1">
                        Team: ${escapeHtml(rk.team?.name ?? '-')}
                        · Ketua: ${escapeHtml(rk.ketua?.name ?? rk.team?.leader?.name ?? '-')}
                    </div>

                    <div class="text-xs text-gray-500 mt-1">
                        Project: ${rk.project_count ?? projects.length}
                        · RK Anggota: ${rkAnggotaCount}
                        · IKI: ${approvedIki}/${totalIki} approved
                        · Daily Task: ${dailyTaskCount}
                    </div>
                </div>

                <div class="md:w-44">
                    <div class="w-full bg-gray-200 rounded h-2">
                        <div class="bg-blue-500 h-2 rounded" style="width:${Math.min(progress, 100)}%"></div>
                    </div>
                    <div class="text-xs text-right mt-1 font-semibold text-gray-700">
                        ${progress}%
                    </div>
                </div>
            </div>

            <div class="mt-3 space-y-2">
                ${projects.length ? projects.map(project => renderProjectItem(project)).join('') : '<div class="text-xs text-gray-400">Belum ada project.</div>'}
            </div>
        </div>
    `;
}

function renderProjectItem(project) {
    const progress = Number(project.progress ?? 0);

    const totalIki = Number(project.total_iki_count ?? 0);
    const approvedIki = Number(project.approved_iki_count ?? 0);
    const dailyTaskCount = Number(project.daily_task_count ?? 0);
    const rkAnggotaCount = Number(project.rk_anggota_count ?? 0);
    const completedRk = Number(project.completed_rk_anggota_count ?? 0);

    return `
        <div class="bg-gray-50 rounded p-3 border">
            <div class="flex flex-col md:flex-row md:justify-between gap-3">
                <div>
                    <div class="font-semibold text-gray-800">
                        ${escapeHtml(project.name ?? '-')}
                    </div>

                    <div class="text-xs text-gray-500 mt-1">
                        Status: ${escapeHtml(project.status ?? '-')}
                    </div>

                    <div class="text-xs text-gray-500 mt-1">
                        RK selesai: ${completedRk}/${rkAnggotaCount}
                        · IKI: ${approvedIki}/${totalIki} approved
                        · Daily Task: ${dailyTaskCount}
                    </div>

                    ${project.submitted_iki_count > 0 ? `
                        <div class="text-xs text-blue-600 mt-1">
                            ${project.submitted_iki_count} IKI menunggu review
                        </div>
                    ` : ''}

                    ${project.rejected_iki_count > 0 ? `
                        <div class="text-xs text-red-600 mt-1">
                            ${project.rejected_iki_count} IKI perlu revisi
                        </div>
                    ` : ''}
                </div>

                <div class="md:w-40">
                    <div class="w-full bg-gray-200 rounded h-1.5">
                        <div class="bg-green-500 h-1.5 rounded" style="width:${Math.min(progress, 100)}%"></div>
                    </div>
                    <div class="text-xs text-right mt-1 font-bold text-gray-700">
                        ${progress}%
                    </div>
                </div>
            </div>
        </div>
    `;
}

/*
|--------------------------------------------------------------------------
| IKU Template Picker
|--------------------------------------------------------------------------
*/

function setupIkuTemplatePicker() {
    const nameInput = document.getElementById('create_name');
    const yearInput = document.getElementById('create_year');
    const satuanInput = document.getElementById('create_satuan');
    const targetInput = document.getElementById('create_target');
    const dropdown = document.getElementById('createIkuTemplateDropdown');

    if (!nameInput || !yearInput || !satuanInput || !targetInput || !dropdown) {
        return;
    }

    function normalizeText(value) {
        return String(value ?? '').toLowerCase();
    }

    function hideDropdown() {
        dropdown.classList.add('hidden');
        dropdown.innerHTML = '';
    }

    function showDropdown() {
        dropdown.classList.remove('hidden');
    }

    function renderTemplates(keyword = '') {
        const search = normalizeText(keyword);

        let templates = IKU_TEMPLATE_OPTIONS.filter(item => {
            const name = normalizeText(item.name);
            const year = normalizeText(item.year);
            const satuan = normalizeText(item.satuan);
            const target = normalizeText(item.target);

            if (!search) {
                return true;
            }

            return name.includes(search)
                || year.includes(search)
                || satuan.includes(search)
                || target.includes(search);
        });

        templates = templates.slice(0, 50);

        if (templates.length === 0) {
            dropdown.innerHTML = `
                <div class="p-3 text-sm text-gray-400">
                    Template IKU tidak ditemukan. Kamu tetap bisa mengetik manual.
                </div>
            `;
            showDropdown();
            return;
        }

        dropdown.innerHTML = templates.map(item => {
            return `
                <button type="button"
                    class="iku-template-option block w-full border-b px-4 py-3 text-left text-sm hover:bg-blue-50 last:border-b-0"
                    data-name="${escapeAttribute(item.name)}"
                    data-year="${escapeAttribute(item.year)}"
                    data-satuan="${escapeAttribute(item.satuan)}"
                    data-target="${escapeAttribute(item.target)}">

                    <div class="font-semibold text-gray-900 leading-5">
                        ${escapeHtml(item.name)}
                    </div>

                    <div class="mt-1 text-xs text-gray-500">
                        Tahun: ${escapeHtml(item.year ?? '-')}
                        · Target: ${escapeHtml(item.target ?? '-')} ${escapeHtml(item.satuan ?? '')}
                    </div>
                </button>
            `;
        }).join('');

        showDropdown();
    }

    nameInput.addEventListener('focus', function () {
        renderTemplates(this.value);
    });

    nameInput.addEventListener('input', function () {
        renderTemplates(this.value);
    });

    dropdown.addEventListener('mousedown', function (event) {
        event.preventDefault();

        const option = event.target.closest('.iku-template-option');

        if (!option) {
            return;
        }

        nameInput.value = option.dataset.name || '';
        yearInput.value = option.dataset.year || new Date().getFullYear();
        satuanInput.value = option.dataset.satuan || '%';
        targetInput.value = option.dataset.target || 100;

        hideDropdown();

        setTimeout(() => {
            nameInput.focus();
        }, 0);
    });

    [yearInput, satuanInput, targetInput].forEach(input => {
        input.addEventListener('focus', hideDropdown);
        input.addEventListener('click', hideDropdown);
    });

    document.addEventListener('click', function (event) {
        if (event.target === nameInput || dropdown.contains(event.target)) {
            return;
        }

        hideDropdown();
    });
}

/*
|--------------------------------------------------------------------------
| Search Render
|--------------------------------------------------------------------------
*/

function getProgressStatus(progress) {
    progress = Number(progress || 0);

    if (progress >= 100) {
        return {
            label: 'Selesai',
            className: 'bg-green-100 text-green-700'
        };
    }

    if (progress > 0) {
        return {
            label: 'Berjalan',
            className: 'bg-blue-100 text-blue-700'
        };
    }

    return {
        label: 'Belum Berjalan',
        className: 'bg-gray-100 text-gray-600'
    };
}

function renderIkuActionMenu(iku, mode = 'desktop') {
    const menuId = `iku-action-menu-${mode}-${iku.id}`;

    return `
        <div class="relative inline-block text-left">
            <button type="button"
                data-iku-action-button
                onclick="toggleIkuActionMenu('${menuId}', event)"
                class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                Aksi
                <span class="text-xs">▾</span>
            </button>

            <div id="${menuId}"
                data-iku-action-menu
                class="hidden absolute right-0 top-full z-50 mt-2 w-44 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl">
                <button type="button"
                    onclick="closeAllIkuActionMenus(); openViewModalFromCache(${iku.id})"
                    class="block w-full px-4 py-2 text-left text-sm text-blue-600 hover:bg-blue-50">
                    View
                </button>

                ${isAdmin ? `
                    <button type="button"
                        onclick="closeAllIkuActionMenus(); openEditModalFromCache(${iku.id})"
                        class="block w-full px-4 py-2 text-left text-sm text-yellow-600 hover:bg-yellow-50">
                        Edit
                    </button>

                    <form method="POST"
                        action="${deleteBaseUrl}/${iku.id}"
                        onsubmit="return confirm('Yakin hapus IKU ini?')">
                        <input type="hidden" name="_token" value="${csrfToken}">
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit"
                            class="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50">
                            Delete
                        </button>
                    </form>
                ` : ''}
            </div>
        </div>
    `;
}

function renderIkuTableRow(iku) {
    const progress = Number(iku.progress ?? 0);
    const status = getProgressStatus(progress);

    const rkKetuaCount = Number(iku.rk_ketua_count ?? 0);
    const projectCount = Number(iku.project_count ?? 0);
    const approvedIki = Number(iku.approved_iki_count ?? 0);
    const totalIki = Number(iku.total_iki_count ?? 0);
    const dailyTaskCount = Number(iku.daily_task_count ?? 0);

    return `
        <tr class="border-b last:border-b-0 hover:bg-gray-50">
            <td class="p-3 font-medium">
                <div class="font-semibold text-gray-900">
                    ${escapeHtml(iku.name)}
                </div>
                ${iku.code ? `
                    <div class="text-xs text-gray-400 mt-1">
                        Kode: ${escapeHtml(iku.code)}
                    </div>
                ` : ''}
            </td>

            <td class="p-3">${iku.year ?? '-'}</td>

            <td class="p-3">
                <span class="font-semibold">${iku.target ?? 100} ${escapeHtml(iku.satuan ?? '%')}</span>
            </td>

            <td class="p-3 text-center">
                <div class="font-semibold text-gray-900">${rkKetuaCount}</div>
                <div class="text-xs text-gray-400">RK Ketua</div>
            </td>

            <td class="p-3 text-center">
                <div class="font-semibold text-gray-900">${projectCount}</div>
                <div class="text-xs text-gray-400">Project</div>
            </td>

            <td class="p-3 text-center">
                <div class="font-semibold text-gray-900">${approvedIki}/${totalIki}</div>
                <div class="text-xs text-gray-400">IKI approved</div>

                ${iku.submitted_iki_count > 0 ? `
                    <div class="text-xs text-blue-600 mt-1">
                        ${iku.submitted_iki_count} review
                    </div>
                ` : ''}

                ${iku.rejected_iki_count > 0 ? `
                    <div class="text-xs text-red-600 mt-1">
                        ${iku.rejected_iki_count} revisi
                    </div>
                ` : ''}
            </td>

            <td class="p-3 text-center">
                <div class="font-semibold text-gray-900">${dailyTaskCount}</div>
                <div class="text-xs text-gray-400">task</div>
            </td>

            <td class="p-3">
                <div class="w-full bg-gray-200 rounded h-2">
                    <div class="bg-green-500 h-2 rounded" style="width: ${Math.min(progress, 100)}%"></div>
                </div>
                <small class="text-gray-600">${progress}%</small>
            </td>

            <td class="p-3">
                <span class="px-2 py-1 rounded text-xs font-semibold ${status.className}">
                    ${status.label}
                </span>
            </td>

            <td class="p-3 text-center relative">
                ${renderIkuActionMenu(iku, 'desktop-search')}
            </td>
        </tr>
    `;
}

function renderIkuMobileCard(iku) {
    const progress = Number(iku.progress ?? 0);
    const status = getProgressStatus(progress);

    const rkKetuaCount = Number(iku.rk_ketua_count ?? 0);
    const projectCount = Number(iku.project_count ?? 0);
    const approvedIki = Number(iku.approved_iki_count ?? 0);
    const totalIki = Number(iku.total_iki_count ?? 0);
    const dailyTaskCount = Number(iku.daily_task_count ?? 0);

    return `
        <div class="rounded-xl border bg-white p-4 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <h3 class="font-semibold text-gray-900 break-words">
                        ${escapeHtml(iku.name ?? '-')}
                    </h3>

                    ${iku.code ? `
                        <p class="text-xs text-gray-400 mt-1">
                            Kode: ${escapeHtml(iku.code)}
                        </p>
                    ` : ''}

                    <p class="text-sm text-gray-500 mt-1">
                        Tahun ${escapeHtml(iku.year ?? '-')} · Target ${escapeHtml(iku.target ?? 100)} ${escapeHtml(iku.satuan ?? '%')}
                    </p>
                </div>

                <div class="relative shrink-0">
                    ${renderIkuActionMenu(iku, 'mobile-search')}
                </div>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-2 text-xs">
                <div class="rounded-lg bg-gray-50 border p-2">
                    <div class="text-gray-400">RK Ketua</div>
                    <div class="font-semibold text-gray-900">${rkKetuaCount}</div>
                </div>

                <div class="rounded-lg bg-gray-50 border p-2">
                    <div class="text-gray-400">Project</div>
                    <div class="font-semibold text-gray-900">${projectCount}</div>
                </div>

                <div class="rounded-lg bg-gray-50 border p-2">
                    <div class="text-gray-400">IKI Approved</div>
                    <div class="font-semibold text-gray-900">${approvedIki}/${totalIki}</div>
                </div>

                <div class="rounded-lg bg-gray-50 border p-2">
                    <div class="text-gray-400">Daily Task</div>
                    <div class="font-semibold text-gray-900">${dailyTaskCount}</div>
                </div>
            </div>

            <div class="mt-4">
                <div class="flex justify-between mb-1">
                    <span class="text-xs text-gray-500">Progress</span>
                    <span class="text-xs font-semibold text-gray-700">${progress}%</span>
                </div>

                <div class="w-full bg-gray-200 rounded h-2">
                    <div class="bg-green-500 h-2 rounded" style="width: ${Math.min(progress, 100)}%"></div>
                </div>
            </div>

            <div class="mt-3">
                <span class="px-2 py-1 rounded text-xs font-semibold ${status.className}">
                    ${status.label}
                </span>
            </div>
        </div>
    `;
}

function searchIkuNow() {
    clearTimeout(timer);

    const keyword = document.getElementById('searchInput').value;
    const year = document.getElementById('yearFilter').value;

    const url = `/iku/search?search=${encodeURIComponent(keyword)}&year=${encodeURIComponent(year)}`;

    fetch(url)
        .then(res => res.json())
        .then(data => {
            const tbody = document.getElementById('ikuTableBody');
            const mobileCards = document.getElementById('ikuMobileCards');

            tbody.innerHTML = '';
            mobileCards.innerHTML = '';
            ikuSearchCache = {};

            if (data.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="10" class="text-center py-5 text-gray-400">
                            Data IKU tidak ditemukan
                        </td>
                    </tr>
                `;

                mobileCards.innerHTML = `
                    <div class="rounded-xl border p-5 text-center text-gray-400">
                        Data IKU tidak ditemukan
                    </div>
                `;
                return;
            }

            data.forEach(iku => {
                ikuSearchCache[iku.id] = iku;

                tbody.innerHTML += renderIkuTableRow(iku);
                mobileCards.innerHTML += renderIkuMobileCard(iku);
            });
        })
        .catch(error => {
            console.error('IKU search error:', error);
        });
}

/*
|--------------------------------------------------------------------------
| Event Binding
|--------------------------------------------------------------------------
*/

document.getElementById('searchInput')?.addEventListener('keyup', function() {
    clearTimeout(timer);
    timer = setTimeout(searchIkuNow, 400);
});

document.getElementById('yearFilter')?.addEventListener('change', function() {
    searchIkuNow();
});

['modalCreate', 'modalEdit', 'modalView', 'modalImport'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', function(event) {
        if (event.target === this) {
            closeModal(id);
        }
    });
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeAllIkuActionMenus();
        closeModal('modalCreate');
        closeModal('modalEdit');
        closeModal('modalView');
        closeModal('modalImport');
    }
});

document.addEventListener('DOMContentLoaded', function () {
    setupIkuTemplatePicker();
});
</script>

</x-app-layout>
