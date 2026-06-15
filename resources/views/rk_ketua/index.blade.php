<x-app-layout>

@php
    $role = auth()->user()->role;

    $rkKetuaBasePath = match ($role) {
        'admin' => '/admin/rk-ketua',
        'ketua' => '/ketua/rk-ketua',
        'kepala' => '/kepala/rk-ketua',
        default => '/rk-ketua',
    };

    $projectBasePath = match ($role) {
        'admin' => '/admin/project',
        'ketua' => '/ketua/project',
        'kepala' => '/kepala/project',
        default => '/project',
    };

    $canManageRkKetua = in_array($role, ['admin', 'ketua'], true);
    $isKepala = $role === 'kepala';

    /*
    |--------------------------------------------------------------------------
    | RK Ketua Template Picker Options
    |--------------------------------------------------------------------------
    | Data ini berasal dari controller ($rkKetuaTemplates) yang mengambil isi
    | tabel rk_ketua_templates. Disiapkan di @php agar @json di script tetap
    | sederhana dan tidak menyebabkan parse error Blade.
    |--------------------------------------------------------------------------
    */
    $rkKetuaTemplateOptions = ($rkKetuaTemplates ?? collect())
        ->map(function ($template) {
            return [
                'description' => $template->description,
                'category' => $template->category,
            ];
        })
        ->values();
@endphp

<div class="bg-white p-6 rounded-xl shadow">

    <!-- ================= HEADER ================= -->
    <div class="flex flex-col gap-3 md:flex-row md:justify-between md:items-center mb-4">
        <div>
            <h2 class="text-xl font-bold">
                Rencana Kinerja - Ketua
            </h2>

            @if($role === 'ketua')
                <p class="text-sm text-gray-500 mt-1">
                    Menampilkan RK Ketua berdasarkan tim kerja yang kamu pimpin.
                </p>
            @elseif($role === 'kepala')
                <p class="text-sm text-gray-500 mt-1">
                    Mode monitoring. Menampilkan seluruh RK Ketua, project, IKI, dan progress turunannya.
                </p>
            @else
                <p class="text-sm text-gray-500 mt-1">
                    Monitoring RK Ketua berdasarkan IKU, project, RK Anggota, IKI, dan Daily Task.
                </p>
            @endif
        </div>

        <div class="flex gap-2">
            <a href="{{ url($rkKetuaBasePath) }}"
                class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">
                Refresh
            </a>

            @if($canManageRkKetua)
                <button type="button"
                    onclick="openCreateModal()"
                    class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                    + Add RK Ketua
                </button>
            @endif
        </div>
    </div>

    <!-- ================= ALERT ================= -->
    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-700 rounded">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">
            <ul class="list-disc ml-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- ================= FILTER ================= -->
    <form id="rkKetuaFilterForm"
        method="GET"
        class="grid grid-cols-1 md:grid-cols-6 gap-3 mb-4">

        <!-- YEAR -->
        <select name="year"
            id="yearFilter"
            class="border px-3 py-2 rounded">
            @for($y = date('Y'); $y >= 2020; $y--)
                <option value="{{ $y }}" {{ (int) $y === (int) $year ? 'selected' : '' }}>
                    {{ $y }}
                </option>
            @endfor
        </select>

        <!-- IKU -->
        <select name="iku_id"
            id="ikuFilter"
            class="border px-3 py-2 rounded">
            <option value="">Semua IKU</option>

            @foreach($ikus as $iku)
                <option value="{{ $iku->id }}"
                    {{ request('iku_id') == $iku->id ? 'selected' : '' }}>
                    {{ $iku->name }}
                </option>
            @endforeach
        </select>

        <!-- TEAM -->
        <select name="team_id"
            id="teamFilter"
            class="border px-3 py-2 rounded">
            <option value="">Semua Tim</option>

            @foreach($teams as $team)
                <option value="{{ $team->id }}"
                    {{ request('team_id') == $team->id ? 'selected' : '' }}>
                    {{ $team->name }}
                </option>
            @endforeach
        </select>

        <!-- SEARCH -->
        <input type="text"
            id="searchInput"
            name="search"
            value="{{ request('search') }}"
            placeholder="Cari RK Ketua, IKU, tim, project, atau IKI..."
            class="border px-3 py-2 rounded">

        <button type="submit"
            class="bg-gray-800 text-white px-4 rounded">
            Filter
        </button>

        <a href="{{ url($rkKetuaBasePath) }}"
            class="bg-gray-200 text-center px-4 py-2 rounded hover:bg-gray-300">
            Reset
        </a>
    </form>

    <!-- ================= TABLE ================= -->
    <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[1100px]">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="text-left p-3">
                        IKU
                    </th>

                    <th class="text-left p-3">
                        Rencana Kinerja
                    </th>

                    <th class="text-left p-3">
                        Tim
                    </th>

                    <th class="text-center p-3">
                        Project
                    </th>

                    <th class="text-center p-3">
                        IKI
                    </th>

                    <th class="text-center p-3">
                        Daily Task
                    </th>

                    <th class="text-left p-3">
                        Progress
                    </th>

                    <th class="text-left p-3">
                        Aksi
                    </th>
                </tr>
            </thead>

            <tbody id="rkKetuaTableBody">
            @forelse($rkKetuas as $rk)
                @php
                    $projectCount = $rk->project_count ?? $rk->projects->count();
                    $totalIki = $rk->total_iki_count ?? 0;
                    $approvedIki = $rk->approved_iki_count ?? 0;
                    $dailyTaskCount = $rk->daily_task_count ?? 0;
                    $completedRk = $rk->completed_rk_anggota_count ?? $rk->approved_rk_anggota_count ?? 0;
                    $totalRkAnggota = $rk->rk_anggota_count ?? 0;
                @endphp

                <tr class="border-b hover:bg-gray-50" data-id="{{ $rk->id }}">

                    <!-- IKU -->
                    <td class="p-3">
                        <div class="font-medium text-gray-900">
                            {{ $rk->iku->name ?? '-' }}
                        </div>

                        @if($rk->iku?->year)
                            <div class="text-xs text-gray-400 mt-1">
                                Tahun: {{ $rk->iku->year }}
                            </div>
                        @endif
                    </td>

                    <!-- DESCRIPTION -->
                    <td class="p-3">
                        {{ $rk->description }}
                    </td>

                    <!-- TEAM -->
                    <td class="p-3">
                        <div class="font-medium text-gray-900">
                            {{ $rk->team->name ?? '-' }}
                        </div>

                        <div class="text-xs text-gray-400 mt-1">
                            Ketua: {{ $rk->team->leader->name ?? '-' }}
                        </div>
                    </td>

                    <!-- PROJECT -->
                    <td class="p-3 text-center">
                        <div class="font-semibold text-gray-900">
                            {{ $projectCount }}
                        </div>
                        <div class="text-xs text-gray-400">
                            project
                        </div>
                    </td>

                    <!-- IKI -->
                    <td class="p-3 text-center">
                        <div class="font-semibold text-gray-900">
                            {{ $approvedIki }}/{{ $totalIki }}
                        </div>
                        <div class="text-xs text-gray-400">
                            IKI approved
                        </div>

                        @if($totalRkAnggota > 0)
                            <div class="text-xs text-gray-400 mt-1">
                                RK selesai: {{ $completedRk }}/{{ $totalRkAnggota }}
                            </div>
                        @endif
                    </td>

                    <!-- DAILY TASK -->
                    <td class="p-3 text-center">
                        <div class="font-semibold text-gray-900">
                            {{ $dailyTaskCount }}
                        </div>
                        <div class="text-xs text-gray-400">
                            task
                        </div>
                    </td>

                    <!-- PROGRESS -->
                    <td class="p-3">
                        <div class="w-full bg-gray-200 rounded h-2">
                            <div class="bg-green-500 h-2 rounded"
                                style="width: {{ $rk->progress ?? 0 }}%">
                            </div>
                        </div>
                        <small>{{ $rk->progress ?? 0 }}%</small>
                    </td>

                    <!-- AKSI -->
                    <td class="p-3 space-x-2">

                        <button type="button"
                            onclick="openViewModal({{ $rk->id }})"
                            class="text-blue-500 hover:underline">
                            View
                        </button>

                        <a href="{{ url($projectBasePath . '?rk_ketua_id=' . $rk->id) }}"
                            class="text-purple-600 hover:underline">
                            Project
                        </a>

                        @if($canManageRkKetua)
                            <button type="button"
                                onclick='openEditModal(@json($rk))'
                                class="text-yellow-500 hover:underline">
                                Edit
                            </button>

                            <form method="POST"
                                action="{{ url($rkKetuaBasePath . '/' . $rk->id) }}"
                                class="inline"
                                onsubmit="return confirm('Yakin hapus RK Ketua ini?')">
                                @csrf
                                @method('DELETE')

                                <button class="text-red-500 hover:underline">
                                    Delete
                                </button>
                            </form>
                        @endif

                    </td>

                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center py-4 text-gray-500">
                        Tidak ada data RK Ketua
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <!-- ================= PAGINATION ================= -->
    <div class="mt-4 flex justify-center">
        {{ $rkKetuas->withQueryString()->links() }}
    </div>

</div>

<!-- ================= CREATE MODAL ================= -->
@if($canManageRkKetua)
<div id="modalCreate"
    class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4">

    <div class="bg-white rounded-2xl w-[680px] max-h-[90vh] overflow-y-auto shadow-xl">

        <!-- HEADER -->
        <div class="px-6 py-4 border-b flex justify-between items-start">
            <div>
                <h3 class="text-xl font-bold text-gray-900">
                    Tambah RK Ketua
                </h3>

                <p class="text-sm text-gray-500 mt-1">
                    Buat rencana kinerja ketua berdasarkan IKU dan tim kerja yang dipimpin.
                </p>
            </div>

            <button type="button"
                onclick="closeModal('modalCreate')"
                class="text-gray-400 hover:text-red-500 text-2xl leading-none">
                ×
            </button>
        </div>

        <form method="POST" action="{{ url($rkKetuaBasePath) }}" class="p-6">
            @csrf

            <!-- IKU -->
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    IKU
                </label>

                <select name="iku_id"
                    required
                    class="border w-full p-3 rounded-lg bg-white focus:ring focus:ring-green-100 focus:border-green-500">
                    <option value="">-- Pilih IKU --</option>

                    @foreach($ikus as $iku)
                        <option value="{{ $iku->id }}">
                            {{ $iku->name }}
                        </option>
                    @endforeach
                </select>

                <p class="text-xs text-gray-400 mt-1">
                    Pilih IKU yang akan diturunkan menjadi RK Ketua.
                </p>
            </div>

            <!-- TEAM -->
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Tim Kerja
                </label>

                <select name="team_id"
                    id="teamSelect"
                    required
                    class="border w-full p-3 rounded-lg bg-white focus:ring focus:ring-green-100 focus:border-green-500">
                    <option value="">-- Pilih Tim Kerja --</option>

                    @foreach($teams as $team)
                        <option value="{{ $team->id }}"
                            data-leader="{{ $team->leader->name ?? '' }}">
                            {{ $team->name }}
                        </option>
                    @endforeach
                </select>

                <p class="text-xs text-gray-400 mt-1">
                    Untuk Ketua Tim, pilihan tim hanya tim yang kamu pimpin.
                </p>
            </div>

            <!-- KETUA AUTO -->
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Ketua Tim
                </label>

                <input type="text"
                    id="leaderName"
                    placeholder="Ketua Tim akan terisi otomatis"
                    class="border w-full p-3 rounded-lg bg-gray-100 text-gray-600 cursor-not-allowed"
                    readonly>
            </div>

            <!-- RK KETUA -->
            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Rencana Kinerja Ketua
                </label>

                <div class="relative">
                    <textarea id="create_description"
                        name="description"
                        required
                        rows="5"
                        placeholder="Tulis rencana kinerja ketua atau pilih dari template..."
                        autocomplete="off"
                        class="border w-full p-3 rounded-lg focus:ring focus:ring-green-100 focus:border-green-500"></textarea>

                    <div id="createRkKetuaTemplateDropdown"
                        class="hidden absolute left-0 right-0 z-50 mt-1 max-h-48 overflow-y-auto rounded-xl border bg-white shadow-xl">
                    </div>
                </div>

                <p class="text-xs text-gray-400 mt-1">
                    Klik kolom ini untuk memilih template RK Ketua dari data Excel yang sudah diimport, atau tetap ketik manual.
                </p>
            </div>

            <!-- INFO -->
            <div class="mb-5 p-4 rounded-xl bg-blue-50 border border-blue-100 text-sm text-blue-700">
                RK Ketua menjadi dasar pembuatan project. Progress RK Ketua dihitung dari progress project, yang bersumber dari RK Anggota dan IKI.
            </div>

            <!-- ACTION -->
            <div class="flex justify-end gap-2 pt-4 border-t">
                <button type="button"
                    onclick="closeModal('modalCreate')"
                    class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg">
                    Cancel
                </button>

                <button type="submit"
                    class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded-lg shadow">
                    Save
                </button>
            </div>

        </form>

    </div>
</div>
@endif

<!-- ================= EDIT MODAL ================= -->
@if($canManageRkKetua)
<div id="modalEdit"
    class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4">

    <div class="bg-white rounded-2xl w-[680px] max-h-[90vh] overflow-y-auto shadow-xl">

        <!-- HEADER -->
        <div class="px-6 py-4 border-b flex justify-between items-start">
            <div>
                <h3 class="text-xl font-bold text-gray-900">
                    Edit RK Ketua
                </h3>

                <p class="text-sm text-gray-500 mt-1">
                    Perbarui rencana kinerja ketua berdasarkan IKU dan tim kerja yang dipimpin.
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

            <!-- IKU -->
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    IKU
                </label>

                <input type="text"
                    id="edit_iku"
                    class="border w-full p-3 rounded-lg bg-gray-100 text-gray-700 cursor-not-allowed"
                    readonly>

                <input type="hidden"
                    id="edit_iku_id"
                    name="iku_id">

                <p class="text-xs text-gray-400 mt-1">
                    IKU mengikuti data RK Ketua yang sedang diedit.
                </p>
            </div>

            <!-- TEAM -->
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Tim Kerja
                </label>

                <select id="edit_teamSelect"
                    name="team_id"
                    required
                    class="border w-full p-3 rounded-lg bg-white focus:ring focus:ring-yellow-100 focus:border-yellow-500">
                    <option value="">-- Pilih Tim Kerja --</option>

                    @foreach($teams as $team)
                        <option value="{{ $team->id }}"
                            data-leader="{{ $team->leader->name ?? '' }}">
                            {{ $team->name }}
                        </option>
                    @endforeach
                </select>

                <p class="text-xs text-gray-400 mt-1">
                    Untuk Ketua Tim, pilihan tim hanya tim yang kamu pimpin.
                </p>
            </div>

            <!-- KETUA AUTO -->
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Ketua Tim
                </label>

                <input type="text"
                    id="edit_leader"
                    placeholder="Ketua Tim akan terisi otomatis"
                    class="border w-full p-3 rounded-lg bg-gray-100 text-gray-700 cursor-not-allowed"
                    readonly>

                <p class="text-xs text-gray-400 mt-1">
                    Ketua Tim otomatis mengikuti Tim Kerja yang dipilih.
                </p>
            </div>

            <!-- DESCRIPTION -->
            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Rencana Kinerja Ketua
                </label>

                <textarea id="edit_description"
                    name="description"
                    required
                    rows="5"
                    placeholder="Tulis rencana kinerja ketua..."
                    class="border w-full p-3 rounded-lg focus:ring focus:ring-yellow-100 focus:border-yellow-500"></textarea>

                <p class="text-xs text-gray-400 mt-1">
                    Contoh: Menyusun, mengawal, dan mengevaluasi pelaksanaan pekerjaan tim.
                </p>
            </div>

            <!-- INFO -->
            <div class="mb-5 p-4 rounded-xl bg-yellow-50 border border-yellow-100 text-sm text-yellow-700">
                Jika Tim Kerja diganti, pemilik RK Ketua otomatis mengikuti Ketua Tim dari tim yang dipilih.
            </div>

            <!-- ACTION -->
            <div class="flex justify-end gap-2 pt-4 border-t">
                <button type="button"
                    onclick="closeModal('modalEdit')"
                    class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg">
                    Cancel
                </button>

                <button type="submit"
                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-5 py-2 rounded-lg shadow">
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

    <div class="bg-white rounded-2xl w-[980px] max-h-[85vh] overflow-y-auto shadow-xl">

        <!-- HEADER -->
        <div class="px-6 py-4 border-b flex justify-between items-start">
            <div>
                <h3 class="text-xl font-bold text-gray-900">
                    Detail RK Ketua
                </h3>

                <p class="text-sm text-gray-500 mt-1">
                    Detail IKU, tim kerja, project, RK Anggota, IKI, Daily Task, dan progress.
                </p>
            </div>

            <button type="button"
                onclick="closeModal('modalView')"
                class="text-gray-400 hover:text-red-500 text-2xl leading-none">
                ×
            </button>
        </div>

        <div id="viewContent" class="p-6">
            <p class="text-gray-500">Loading...</p>
        </div>

        <div class="flex justify-end px-6 py-4 border-t">
            <button type="button"
                onclick="closeModal('modalView')"
                class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg">
                Close
            </button>
        </div>

    </div>
</div>

<!-- ================= SCRIPT ================= -->
<script>
const RK_KETUA_BASE_PATH = @json($rkKetuaBasePath);
const PROJECT_BASE_PATH = @json($projectBasePath);
const CAN_MANAGE_RK_KETUA = @json($canManageRkKetua);
const RK_KETUA_TEMPLATE_OPTIONS = @json($rkKetuaTemplateOptions);

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
| RK Ketua Template Picker - Create Modal Only
|--------------------------------------------------------------------------
| Picker hanya membantu mengisi textarea Create RK Ketua.
| Tidak mengubah flow edit, view, filter, table, ajax search, atau tombol lain.
|--------------------------------------------------------------------------
*/
function setupRkKetuaCreateTemplatePicker() {
    const textarea = document.getElementById('create_description');
    const dropdown = document.getElementById('createRkKetuaTemplateDropdown');

    if (!textarea || !dropdown) {
        return;
    }

    function normalizeText(value) {
        return String(value ?? '').toLowerCase();
    }

    function hideDropdown() {
        dropdown.classList.add('hidden');
        dropdown.innerHTML = '';
    }

    function renderTemplates(keyword = '') {
        const search = normalizeText(keyword);

        let templates = RK_KETUA_TEMPLATE_OPTIONS.filter(item => {
            const description = normalizeText(item.description);
            const category = normalizeText(item.category);

            if (!search) {
                return true;
            }

            return description.includes(search) || category.includes(search);
        });

        templates = templates.slice(0, 12);

        if (templates.length === 0) {
            dropdown.innerHTML = `
                <div class="p-3 text-sm text-gray-400">
                    Template RK Ketua tidak ditemukan. Kamu tetap bisa mengetik manual.
                </div>
            `;
            dropdown.classList.remove('hidden');
            return;
        }

        dropdown.innerHTML = templates.map(item => {
            return `
                <button type="button"
                    class="rk-ketua-template-option block w-full border-b px-4 py-3 text-left text-sm hover:bg-green-50 last:border-b-0"
                    data-description="${escapeAttribute(item.description)}">

                    <div class="font-semibold text-gray-900 leading-5">
                        ${escapeHtml(item.description)}
                    </div>

                    ${item.category ? `
                        <div class="mt-1 text-xs text-gray-500">
                            Kategori: ${escapeHtml(item.category)}
                        </div>
                    ` : ''}
                </button>
            `;
        }).join('');

        dropdown.classList.remove('hidden');
    }

    textarea.addEventListener('focus', function () {
        renderTemplates(this.value);
    });

    textarea.addEventListener('input', function () {
        renderTemplates(this.value);
    });

    dropdown.addEventListener('mousedown', function (event) {
        const option = event.target.closest('.rk-ketua-template-option');

        if (!option) {
            return;
        }

        event.preventDefault();

        textarea.value = option.dataset.description || '';
        hideDropdown();

        setTimeout(() => {
            textarea.focus();
        }, 0);
    });

    document.addEventListener('click', function (event) {
        if (event.target === textarea || dropdown.contains(event.target)) {
            return;
        }

        hideDropdown();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            hideDropdown();
        }
    });
}

/*
|--------------------------------------------------------------------------
| View RK Ketua
|--------------------------------------------------------------------------
*/
function openViewModal(id){
    const modal = document.getElementById('modalView');
    const content = document.getElementById('viewContent');

    if (!modal || !content) {
        console.error('modalView atau viewContent tidak ditemukan.');
        return;
    }

    modal.classList.remove('hidden');

    content.innerHTML = `
        <div class="p-4 text-sm text-gray-500">
            Loading detail RK Ketua...
        </div>
    `;

    fetch(`${RK_KETUA_BASE_PATH}/${id}`)
        .then(res => {
            if (!res.ok) {
                throw new Error('Gagal mengambil detail RK Ketua');
            }

            return res.json();
        })
        .then(data => {
            const projects = data.projects ?? [];
            const progress = data.progress ?? 0;

            const projectHtml = projects.length === 0
                ? `
                    <div class="p-4 rounded-xl bg-yellow-50 text-yellow-700 border border-yellow-100">
                        Belum ada project yang dibuat dari RK Ketua ini.
                    </div>
                `
                : `
                    <div class="overflow-x-auto border rounded-xl">
                        <table class="w-full text-sm min-w-[900px]">
                            <thead class="bg-gray-50 border-b">
                                <tr>
                                    <th class="text-left p-3">Project</th>
                                    <th class="text-center p-3">Anggota</th>
                                    <th class="text-center p-3">RK Anggota</th>
                                    <th class="text-center p-3">IKI Approved</th>
                                    <th class="text-center p-3">Daily Task</th>
                                    <th class="text-center p-3">Progress</th>
                                    <th class="text-center p-3">Aksi</th>
                                </tr>
                            </thead>

                            <tbody>
                                ${projects.map(project => `
                                    <tr class="border-b last:border-b-0 hover:bg-gray-50">
                                        <td class="p-3">
                                            <div class="font-medium text-gray-900">
                                                ${escapeHtml(project.name ?? '-')}
                                            </div>

                                            <div class="text-xs text-gray-400 mt-1">
                                                Status: ${escapeHtml(project.status ?? '-')}
                                            </div>
                                        </td>

                                        <td class="p-3 text-center">
                                            <span class="bg-blue-100 text-blue-600 px-2 py-1 rounded text-xs">
                                                ${project.members_count ?? 0} orang
                                            </span>
                                        </td>

                                        <td class="p-3 text-center">
                                            <div class="font-semibold text-gray-900">
                                                ${project.completed_rk_anggota_count ?? 0}/${project.rk_anggota_count ?? 0}
                                            </div>
                                            <div class="text-xs text-gray-400">
                                                RK selesai
                                            </div>
                                        </td>

                                        <td class="p-3 text-center">
                                            <div class="font-semibold text-gray-900">
                                                ${project.approved_iki_count ?? 0}/${project.total_iki_count ?? 0}
                                            </div>
                                            <div class="text-xs text-gray-400">
                                                IKI approved
                                            </div>

                                            ${project.submitted_iki_count > 0 ? `
                                                <div class="text-xs text-blue-600 mt-1">
                                                    ${project.submitted_iki_count} menunggu review
                                                </div>
                                            ` : ''}

                                            ${project.rejected_iki_count > 0 ? `
                                                <div class="text-xs text-red-600 mt-1">
                                                    ${project.rejected_iki_count} perlu revisi
                                                </div>
                                            ` : ''}
                                        </td>

                                        <td class="p-3 text-center">
                                            <div class="font-semibold text-gray-900">
                                                ${project.daily_task_count ?? 0}
                                            </div>
                                            <div class="text-xs text-gray-400">
                                                task
                                            </div>
                                        </td>

                                        <td class="p-3">
                                            <div class="w-full bg-gray-200 rounded h-2">
                                                <div class="bg-green-500 h-2 rounded"
                                                    style="width: ${project.progress ?? 0}%">
                                                </div>
                                            </div>
                                            <div class="text-xs text-center mt-1">
                                                ${project.progress ?? 0}%
                                            </div>
                                        </td>

                                        <td class="p-3 text-center">
                                            <a href="${PROJECT_BASE_PATH}?rk_ketua_id=${data.id}"
                                                class="text-blue-600 hover:underline">
                                                Lihat Project
                                            </a>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;

            content.innerHTML = `
                <!-- SUMMARY -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <div class="p-4 rounded-xl bg-gray-50 border">
                        <div class="text-xs text-gray-500 mb-1">
                            IKU
                        </div>
                        <div class="font-semibold text-gray-900">
                            ${escapeHtml(data.iku?.name ?? '-')}
                        </div>
                        <div class="text-xs text-gray-400 mt-1">
                            Tahun: ${escapeHtml(data.iku?.year ?? '-')}
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-gray-50 border">
                        <div class="text-xs text-gray-500 mb-1">
                            Tim Kerja
                        </div>
                        <div class="font-semibold text-gray-900">
                            ${escapeHtml(data.team?.name ?? '-')}
                        </div>
                        <div class="text-xs text-gray-400 mt-1">
                            Ketua: ${escapeHtml(data.team?.leader?.name ?? '-')}
                        </div>
                    </div>

                </div>

                <!-- DESCRIPTION -->
                <div class="mt-4 p-4 rounded-xl border bg-white">
                    <div class="text-xs text-gray-500 mb-1">
                        Rencana Kinerja Ketua
                    </div>
                    <div class="font-medium text-gray-900">
                        ${escapeHtml(data.description ?? '-')}
                    </div>
                </div>

                <!-- PROGRESS -->
                <div class="mt-4 p-4 rounded-xl bg-green-50 border border-green-100">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <div>
                            <div class="text-sm font-semibold text-green-700">
                                Progress RK Ketua
                            </div>
                            <div class="text-xs text-green-700 mt-1">
                                Progress dihitung dari rata-rata progress project di bawah RK Ketua ini.
                            </div>
                        </div>

                        <div class="md:w-72">
                            <div class="w-full bg-green-100 rounded h-3">
                                <div class="bg-green-500 h-3 rounded"
                                    style="width: ${progress}%">
                                </div>
                            </div>
                            <div class="text-right text-2xl font-bold text-green-700 mt-1">
                                ${progress}%
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STATS -->
                <div class="mt-4 grid grid-cols-2 md:grid-cols-5 gap-3">

                    <div class="p-4 rounded-xl bg-blue-50 border border-blue-100">
                        <div class="text-xs text-blue-600 font-semibold">
                            Project
                        </div>
                        <div class="text-3xl font-bold text-blue-700 mt-2">
                            ${data.project_count ?? 0}
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-purple-50 border border-purple-100">
                        <div class="text-xs text-purple-600 font-semibold">
                            RK Anggota
                        </div>
                        <div class="text-3xl font-bold text-purple-700 mt-2">
                            ${data.rk_anggota_count ?? 0}
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-indigo-50 border border-indigo-100">
                        <div class="text-xs text-indigo-600 font-semibold">
                            Total IKI
                        </div>
                        <div class="text-3xl font-bold text-indigo-700 mt-2">
                            ${data.total_iki_count ?? 0}
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-100">
                        <div class="text-xs text-emerald-600 font-semibold">
                            IKI Approved
                        </div>
                        <div class="text-3xl font-bold text-emerald-700 mt-2">
                            ${data.approved_iki_count ?? 0}
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-gray-50 border">
                        <div class="text-xs text-gray-600 font-semibold">
                            Daily Task
                        </div>
                        <div class="text-3xl font-bold text-gray-700 mt-2">
                            ${data.daily_task_count ?? 0}
                        </div>
                    </div>

                </div>

                <!-- INFO -->
                <div class="mt-4 p-4 rounded-xl bg-blue-50 border border-blue-100 text-sm text-blue-700">
                    Approval dilakukan pada level IKI. RK Ketua memantau progress dari Project,
                    Project memantau RK Anggota, dan RK Anggota memantau IKI serta Daily Task.
                </div>

                <!-- PROJECT LIST -->
                <div class="mt-6">
                    <div class="flex justify-between items-center mb-3">
                        <h4 class="font-semibold text-gray-900">
                            Project dari RK Ketua Ini
                        </h4>

                        <a href="${PROJECT_BASE_PATH}?rk_ketua_id=${data.id}"
                            class="text-sm text-blue-600 hover:underline">
                            Buka Laman Project
                        </a>
                    </div>

                    ${projectHtml}
                </div>
            `;
        })
        .catch(err => {
            console.error('VIEW RK KETUA ERROR:', err);

            content.innerHTML = `
                <div class="p-4 rounded-xl bg-red-50 text-red-700 border border-red-100">
                    Gagal memuat detail RK Ketua.
                </div>
            `;
        });
}

/*
|--------------------------------------------------------------------------
| Modal Helpers
|--------------------------------------------------------------------------
*/
function openCreateModal(){
    const modal = document.getElementById('modalCreate');

    if (!modal) {
        console.error('modalCreate tidak ditemukan');
        return;
    }

    modal.classList.remove('hidden');
}

function closeModal(id){
    const modal = document.getElementById(id);

    if (!modal) {
        console.error(`${id} tidak ditemukan`);
        return;
    }

    modal.classList.add('hidden');
}

/*
|--------------------------------------------------------------------------
| Edit RK Ketua
|--------------------------------------------------------------------------
*/
function openEditModal(data){
    const modal = document.getElementById('modalEdit');

    if (!modal) {
        console.error('modalEdit tidak ditemukan');
        return;
    }

    modal.classList.remove('hidden');

    document.getElementById('edit_iku').value =
        data.iku?.name ?? '-';

    document.getElementById('edit_iku_id').value =
        data.iku_id ?? '';

    const teamSelect = document.getElementById('edit_teamSelect');

    if (teamSelect) {
        teamSelect.value = data.team_id ?? '';

        const selected = teamSelect.options[teamSelect.selectedIndex];

        document.getElementById('edit_leader').value =
            selected ? selected.getAttribute('data-leader') : '';
    }

    document.getElementById('edit_description').value =
        data.description ?? '';

    document.getElementById('formEdit').action =
        `${RK_KETUA_BASE_PATH}/${data.id}`;
}

/*
|--------------------------------------------------------------------------
| Team Leader Auto Fill
|--------------------------------------------------------------------------
*/
document.addEventListener('DOMContentLoaded', function () {
    setupRkKetuaCreateTemplatePicker();

    const teamSelect = document.getElementById('teamSelect');

    if (teamSelect) {
        teamSelect.addEventListener('change', function(){
            const selected = this.options[this.selectedIndex];
            const leader = selected ? selected.getAttribute('data-leader') : '';

            const leaderName = document.getElementById('leaderName');

            if (leaderName) {
                leaderName.value = leader ?? '';
            }
        });
    }

    const editTeamSelect = document.getElementById('edit_teamSelect');

    if (editTeamSelect) {
        editTeamSelect.addEventListener('change', function(){
            const selected = this.options[this.selectedIndex];
            const leader = selected ? selected.getAttribute('data-leader') : '';

            const editLeader = document.getElementById('edit_leader');

            if (editLeader) {
                editLeader.value = leader ?? '';
            }
        });
    }
});
</script>

<script>
let rkKetuaSearchTimer = null;
let rkKetuaSearchData = {};

const rkKetuaFilterForm = document.getElementById('rkKetuaFilterForm');
const searchInput = document.getElementById('searchInput');
const yearFilter = document.getElementById('yearFilter');
const ikuFilter = document.getElementById('ikuFilter');
const teamFilter = document.getElementById('teamFilter');

function getRkKetuaFilterParams() {
    const params = new URLSearchParams();

    const keyword = searchInput?.value ?? '';
    const year = yearFilter?.value ?? '';
    const ikuId = ikuFilter?.value ?? '';
    const teamId = teamFilter?.value ?? '';

    if (keyword) {
        params.set('search', keyword);
    }

    if (year) {
        params.set('year', year);
    }

    if (ikuId) {
        params.set('iku_id', ikuId);
    }

    if (teamId) {
        params.set('team_id', teamId);
    }

    return params;
}

function renderRkKetuaTable(data) {
    const tbody = document.getElementById('rkKetuaTableBody');

    if (!tbody) {
        return;
    }

    tbody.innerHTML = '';
    rkKetuaSearchData = {};

    if (!data || data.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-4 text-gray-400">
                    Data tidak ditemukan
                </td>
            </tr>
        `;
        return;
    }

    data.forEach(rk => {
        rkKetuaSearchData[rk.id] = rk;

        const progress = rk.progress ?? 0;
        const projectCount = rk.project_count ?? 0;
        const totalIki = rk.total_iki_count ?? 0;
        const approvedIki = rk.approved_iki_count ?? 0;
        const dailyTaskCount = rk.daily_task_count ?? 0;
        const completedRk = rk.completed_rk_anggota_count ?? rk.approved_rk_anggota_count ?? 0;
        const totalRkAnggota = rk.rk_anggota_count ?? 0;

        const manageButtons = CAN_MANAGE_RK_KETUA ? `
            <button type="button"
                onclick="openEditModalFromSearch(${rk.id})"
                class="text-yellow-500 hover:underline">
                Edit
            </button>

            <form method="POST"
                action="${RK_KETUA_BASE_PATH}/${rk.id}"
                class="inline"
                onsubmit="return confirm('Yakin hapus RK Ketua ini?')">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="hidden" name="_method" value="DELETE">

                <button class="text-red-500 hover:underline">
                    Delete
                </button>
            </form>
        ` : '';

        tbody.innerHTML += `
            <tr class="border-b hover:bg-gray-50" data-id="${rk.id}">
                <td class="p-3">
                    <div class="font-medium text-gray-900">
                        ${escapeHtml(rk.iku?.name ?? '-')}
                    </div>
                    <div class="text-xs text-gray-400 mt-1">
                        Tahun: ${escapeHtml(rk.iku?.year ?? '-')}
                    </div>
                </td>

                <td class="p-3">
                    ${escapeHtml(rk.description ?? '-')}
                </td>

                <td class="p-3">
                    <div class="font-medium text-gray-900">
                        ${escapeHtml(rk.team?.name ?? '-')}
                    </div>
                    <div class="text-xs text-gray-400 mt-1">
                        Ketua: ${escapeHtml(rk.team?.leader?.name ?? '-')}
                    </div>
                </td>

                <td class="p-3 text-center">
                    <div class="font-semibold text-gray-900">
                        ${projectCount}
                    </div>
                    <div class="text-xs text-gray-400">
                        project
                    </div>
                </td>

                <td class="p-3 text-center">
                    <div class="font-semibold text-gray-900">
                        ${approvedIki}/${totalIki}
                    </div>
                    <div class="text-xs text-gray-400">
                        IKI approved
                    </div>

                    ${totalRkAnggota > 0 ? `
                        <div class="text-xs text-gray-400 mt-1">
                            RK selesai: ${completedRk}/${totalRkAnggota}
                        </div>
                    ` : ''}
                </td>

                <td class="p-3 text-center">
                    <div class="font-semibold text-gray-900">
                        ${dailyTaskCount}
                    </div>
                    <div class="text-xs text-gray-400">
                        task
                    </div>
                </td>

                <td class="p-3">
                    <div class="w-full bg-gray-200 rounded h-2">
                        <div class="bg-green-500 h-2 rounded"
                            style="width: ${progress}%">
                        </div>
                    </div>
                    <small>${progress}%</small>
                </td>

                <td class="p-3 space-x-2">
                    <button type="button"
                        onclick="openViewModal(${rk.id})"
                        class="text-blue-500 hover:underline">
                        View
                    </button>

                    <a href="${PROJECT_BASE_PATH}?rk_ketua_id=${rk.id}"
                        class="text-purple-600 hover:underline">
                        Project
                    </a>

                    ${manageButtons}
                </td>
            </tr>
        `;
    });
}

function fetchRkKetuaAjax({ pushUrl = false } = {}) {
    const params = getRkKetuaFilterParams();
    const queryString = params.toString();

    /*
    |--------------------------------------------------------------------------
    | Search URL
    |--------------------------------------------------------------------------
    | Tetap memakai endpoint lama /rk-ketua/search agar tidak mengganggu route
    | yang sudah berjalan.
    |--------------------------------------------------------------------------
    */
    const ajaxUrl = `/rk-ketua/search${queryString ? `?${queryString}` : ''}`;

    const tbody = document.getElementById('rkKetuaTableBody');

    if (tbody) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-4 text-gray-400">
                    Memuat data...
                </td>
            </tr>
        `;
    }

    fetch(ajaxUrl, {
        headers: {
            'Accept': 'application/json',
        },
    })
        .then(async res => {
            if (!res.ok) {
                const body = await res.text();

                console.error('SEARCH RK KETUA ERROR RESPONSE:', {
                    status: res.status,
                    url: res.url,
                    body: body,
                });

                throw new Error(`Gagal mengambil data RK Ketua. Status: ${res.status}`);
            }

            return res.json();
        })
        .then(data => {
            renderRkKetuaTable(data);

            if (pushUrl) {
                const pageUrl = `${window.location.pathname}${queryString ? `?${queryString}` : ''}`;
                window.history.replaceState({}, '', pageUrl);
            }
        })
        .catch(err => {
            console.error('SEARCH RK KETUA ERROR:', err);

            if (tbody) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center py-4 text-red-500">
                            Gagal memuat data RK Ketua.
                        </td>
                    </tr>
                `;
            }
        });
}

if (searchInput) {
    searchInput.addEventListener('keyup', function () {
        clearTimeout(rkKetuaSearchTimer);

        rkKetuaSearchTimer = setTimeout(() => {
            fetchRkKetuaAjax({ pushUrl: true });
        }, 400);
    });
}

[yearFilter, ikuFilter, teamFilter].forEach(filter => {
    if (!filter) {
        return;
    }

    filter.addEventListener('change', function () {
        fetchRkKetuaAjax({ pushUrl: true });
    });
});

if (rkKetuaFilterForm) {
    rkKetuaFilterForm.addEventListener('submit', function (event) {
        event.preventDefault();
        fetchRkKetuaAjax({ pushUrl: true });
    });
}

function openEditModalFromSearch(id) {
    const data = rkKetuaSearchData[id];

    if (!data) {
        alert('Data RK Ketua tidak ditemukan.');
        return;
    }

    openEditModal(data);
}
</script>

</x-app-layout>