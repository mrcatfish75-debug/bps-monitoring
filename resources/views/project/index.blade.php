<x-app-layout>

@php
    $authUser = auth()->user();
    $role = $authUser->role;

    $projectBasePath = match ($role) {
    'admin' => '/admin/project',
    'ketua' => '/ketua/project',
    'anggota' => '/anggota/project',
    default => '/project',
};

    /*
    |--------------------------------------------------------------------------
    | Permission utama halaman Project
    |--------------------------------------------------------------------------
    | Admin:
    | - bisa add/edit/delete semua project.
    |
    | Ketua:
    | - bisa add project dari RK Ketua miliknya.
    | - bisa edit/delete hanya project yang dia pimpin.
    | - bisa view project yang dia pimpin atau dia ikuti sebagai member.
    |
    | Anggota:
    | - hanya view project yang dia ikuti.
    */
    $canCreateProject = in_array($role, ['admin', 'ketua']);
@endphp

<div class="bg-white p-6 rounded-xl shadow">

    <!-- ================= HEADER ================= -->
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">Proyek</h2>

        <div class="flex gap-2">
            <a href="{{ url($projectBasePath) }}"
                class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">
                Refresh
            </a>

            @if($canCreateProject)
                <button type="button"
                    onclick="openCreateModal()"
                    class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                    + Add
                </button>
            @endif
        </div>
    </div>

    <!-- ================= FILTER ================= -->
<form id="projectFilterForm"
    method="GET"
    class="grid grid-cols-1 md:grid-cols-5 gap-2 mb-4"
    onsubmit="return false;">

    <!-- TAHUN -->
    <select name="year"
        id="yearFilter"
        class="project-filter border px-3 py-2 rounded">
        @for($y = date('Y'); $y >= 2020; $y--)
            <option value="{{ $y }}" {{ request('year', date('Y')) == $y ? 'selected' : '' }}>
                {{ $y }}
            </option>
        @endfor
    </select>

    <!-- TEAM -->
    <select name="team_id"
        id="teamFilter"
        class="project-filter border px-3 py-2 rounded">
        <option value="">Semua Tim Kerja</option>

        @foreach($teams as $t)
            <option value="{{ $t->id }}"
                {{ request('team_id') == $t->id ? 'selected' : '' }}>
                {{ $t->name }}
            </option>
        @endforeach
    </select>

    <!-- RK KETUA -->
    <select name="rk_ketua_id"
        id="rkKetuaFilter"
        class="project-filter border px-3 py-2 rounded">
        <option value="">Semua RK Ketua</option>

        @foreach($rkKetuas as $rk)
            <option value="{{ $rk->id }}"
                {{ request('rk_ketua_id') == $rk->id ? 'selected' : '' }}>
                {{ \Illuminate\Support\Str::limit($rk->description, 60) }}
            </option>
        @endforeach
    </select>

    <!-- SEARCH -->
    <input type="text"
        id="searchInput"
        name="search"
        value="{{ request('search') }}"
        placeholder="Cari proyek, RK Ketua, atau tim..."
        autocomplete="off"
        class="border px-3 py-2 rounded">

    <!-- RESET -->
    <a href="{{ url($projectBasePath) }}"
        class="bg-gray-200 text-gray-700 text-center px-4 py-2 rounded hover:bg-gray-300">
        Reset
    </a>
</form>

<div id="projectSearchInfo"
    class="hidden mb-4 p-3 rounded bg-blue-50 text-blue-700 text-sm">
</div>


    <!-- ================= TABLE ================= -->
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">

            <thead class="bg-gray-50 border-b">
                <tr>
                    <!-- NAMA PROJECT -->
                    <th class="py-2 px-3">
                        <a href="{{ request()->fullUrlWithQuery([
                            'sort' => 'name',
                            'direction' => request('direction') == 'asc' ? 'desc' : 'asc'
                        ]) }}">
                            Proyek
                            @if(request('sort') == 'name')
                                {{ request('direction') == 'asc' ? '↑' : '↓' }}
                            @endif
                        </a>
                    </th>

                    <!-- ANGGOTA -->
                    <th class="py-2 px-3 text-center">
                        Anggota
                    </th>

                    <!-- RK -->
                    <th class="py-2 px-3">
                        Rencana Ketua
                    </th>

                    <!-- PROGRESS -->
                    <th class="py-2 px-3 text-center">
                        Progress
                    </th>

                    <!-- AKSI -->
                    <th class="py-2 px-3 text-center">
                        Aksi
                    </th>
                </tr>
            </thead>

            <tbody id="projectTableBody">
            @forelse($projects as $p)

                @php
                    $canManageProject = $role === 'admin'
                        || (
                            $role === 'ketua'
                            && (int) $p->leader_id === (int) auth()->id()
                        );
                @endphp

                <tr class="border-b hover:bg-gray-50">

                    <!-- NAMA -->
                    <td class="py-2 px-3 font-medium">
                        {{ $p->name }}
                    </td>

                    <!-- JUMLAH ANGGOTA -->
                    <td class="py-2 px-3 text-center">
                        {{ $p->members->count() }}
                    </td>

                    <!-- RK -->
                    <td class="py-2 px-3">
                        {{ $p->rkKetua->description ?? '-' }}
                    </td>

                    <!-- PROGRESS -->
                    <td class="py-2 px-3">
                        <div class="w-full bg-gray-200 rounded h-2">
                            <div class="bg-green-500 h-2 rounded"
                                style="width: {{ $p->progress }}%">
                            </div>
                        </div>
                        <small>{{ $p->progress }}%</small>
                    </td>

                    <!-- AKSI -->
                    <td class="py-2 px-3 text-center space-x-2">

                        <button type="button"
                            onclick="openViewModal({{ $p->id }})"
                            class="text-blue-500 hover:underline">
                            View
                        </button>

                        @if($canManageProject)
                            <button type="button"
                                onclick="openEditModal({{ $p->id }})"
                                class="text-yellow-500 hover:underline">
                                Edit
                            </button>

                            <form method="POST"
                                action="{{ url($projectBasePath . '/' . $p->id) }}"
                                class="inline"
                                onsubmit="return confirm('Yakin hapus project ini?')">
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
                    <td colspan="5" class="text-center py-4 text-gray-500">
                        Tidak ada data
                    </td>
                </tr>
            @endforelse
            </tbody>

        </table>
    </div>

    <div id="projectPagination" class="mt-4 flex justify-center">
        {{ $projects->withQueryString()->links() }}
    </div>

</div>

<!-- ================= MODAL CREATE ================= -->
@if($canCreateProject)
<div id="modalCreate"
    class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50">

    <div class="bg-white rounded-2xl w-[720px] max-h-[90vh] overflow-y-auto shadow-xl">

        <!-- HEADER -->
        <div class="px-6 py-4 border-b flex justify-between items-start">
            <div>
                <h3 class="text-xl font-bold text-gray-900">
                    Tambah Proyek
                </h3>

                <p class="text-sm text-gray-500 mt-1">
                    Pilih RK Ketua, isi nama proyek, lalu pilih anggota project.
                </p>
            </div>

            <button type="button"
                onclick="closeCreateModal()"
                class="text-gray-400 hover:text-red-500 text-2xl leading-none">
                ×
            </button>
        </div>

        <form method="POST" action="{{ url($projectBasePath) }}" class="p-6">
            @csrf

            <!-- RK -->
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    RK Ketua
                </label>

                <select id="rk_ketua_select"
                    name="rk_ketua_id"
                    required
                    class="border w-full p-3 rounded-lg focus:ring focus:ring-green-100 focus:border-green-500">
                    <option value="">Pilih RK Ketua</option>

                    @foreach($createRkKetuas ?? $rkKetuas as $rk)
                        <option value="{{ $rk->id }}">
                            {{ $rk->description }}
                        </option>
                    @endforeach
                </select>

                <p class="text-xs text-gray-400 mt-1">
                    Anggota akan dimuat setelah RK Ketua dipilih.
                </p>
            </div>

            <!-- NAMA -->
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Nama Proyek
                </label>

                <input name="name"
                    required
                    placeholder="Contoh: Publikasi Statistik Kesejahteraan Rakyat"
                    class="border w-full p-3 rounded-lg focus:ring focus:ring-green-100 focus:border-green-500">
            </div>

            <!-- MEMBERS -->
            <div class="mb-5">
                <div class="flex justify-between items-center mb-2">
                    <label class="block text-sm font-semibold text-gray-700">
                        Anggota Project
                    </label>

                    <span id="selectedMemberCount"
                        class="text-xs bg-blue-100 text-blue-600 px-2 py-1 rounded">
                        0 dipilih
                    </span>
                </div>

                <input type="text"
                    id="memberSearchInput"
                    placeholder="Cari nama anggota..."
                    class="border w-full p-2 rounded-lg mb-2 focus:ring focus:ring-green-100 focus:border-green-500">

                <div class="flex justify-between items-center mb-2">
                    <div class="flex gap-2">
                        <button type="button"
                            onclick="selectAllVisibleMembers()"
                            class="text-xs px-3 py-1 rounded bg-gray-100 hover:bg-gray-200">
                            Pilih yang tampil
                        </button>

                        <button type="button"
                            onclick="clearSelectedMembers()"
                            class="text-xs px-3 py-1 rounded bg-gray-100 hover:bg-gray-200">
                            Hapus pilihan
                        </button>
                    </div>

                    <p class="text-xs text-gray-400">
                        Klik checkbox untuk memilih anggota.
                    </p>
                </div>

                <div id="members_checkbox_list"
                    class="border rounded-lg max-h-64 overflow-y-auto divide-y bg-white">
                    <div class="p-4 text-sm text-gray-400 text-center">
                        Pilih RK Ketua terlebih dahulu.
                    </div>
                </div>

                <p class="text-xs text-gray-400 mt-2">
                    Role ketua dan anggota bisa dipilih sebagai anggota project.
                </p>
            </div>

            <!-- ACTION -->
            <div class="flex justify-end gap-2 pt-4 border-t">
                <button type="button"
                    onclick="closeCreateModal()"
                    class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg">
                    Cancel
                </button>

                <button class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded-lg shadow">
                    Save
                </button>
            </div>

        </form>

    </div>
</div>
@endif

<!-- ================= MODAL EDIT ================= -->
<div id="modalEdit"
    class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50">

    <div class="bg-white rounded-2xl w-[720px] max-h-[90vh] overflow-y-auto shadow-xl">

        <!-- HEADER -->
        <div class="px-6 py-4 border-b flex justify-between items-start">
            <div>
                <h3 class="text-xl font-bold text-gray-900">
                    Edit Proyek
                </h3>

                <p class="text-sm text-gray-500 mt-1">
                    Perbarui nama proyek, RK Ketua, dan anggota project.
                </p>
            </div>

            <button type="button"
                onclick="closeEditModal()"
                class="text-gray-400 hover:text-red-500 text-2xl leading-none">
                ×
            </button>
        </div>

        <form method="POST" id="formEdit" class="p-6">
            @csrf
            @method('PUT')

            <!-- NAMA PROYEK -->
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Nama Proyek
                </label>

                <input id="edit_name"
                    name="name"
                    required
                    class="border w-full p-3 rounded-lg focus:ring focus:ring-yellow-100 focus:border-yellow-500">
            </div>

            <!-- RK KETUA -->
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    RK Ketua
                </label>

                <select id="edit_rk_ketua_id"
                    name="rk_ketua_id"
                    required
                    class="border w-full p-3 rounded-lg bg-white focus:ring focus:ring-yellow-100 focus:border-yellow-500">

                    <option value="">Pilih RK Ketua</option>

                    @foreach($createRkKetuas ?? $rkKetuas as $rk)
                        <option value="{{ $rk->id }}">
                            {{ $rk->description }}
                        </option>
                    @endforeach
                </select>

                <p class="text-xs text-gray-400 mt-1">
                    Untuk Ketua Tim, RK Ketua yang bisa dipakai hanya RK miliknya sendiri.
                </p>
            </div>

            <!-- ANGGOTA PROJECT -->
            <div class="mb-5">
                <div class="flex justify-between items-center mb-2">
                    <label class="block text-sm font-semibold text-gray-700">
                        Anggota Project
                    </label>

                    <span id="editSelectedMemberCount"
                        class="text-xs bg-blue-100 text-blue-600 px-2 py-1 rounded">
                        0 dipilih
                    </span>
                </div>

                <input type="text"
                    id="editMemberSearchInput"
                    placeholder="Cari nama anggota..."
                    class="border w-full p-2 rounded-lg mb-2 focus:ring focus:ring-yellow-100 focus:border-yellow-500">

                <div class="flex justify-between items-center mb-2">
                    <div class="flex gap-2">
                        <button type="button"
                            onclick="selectAllVisibleEditMembers()"
                            class="text-xs px-3 py-1 rounded bg-gray-100 hover:bg-gray-200">
                            Pilih yang tampil
                        </button>

                        <button type="button"
                            onclick="clearSelectedEditMembers()"
                            class="text-xs px-3 py-1 rounded bg-gray-100 hover:bg-gray-200">
                            Hapus pilihan
                        </button>
                    </div>

                    <p class="text-xs text-gray-400">
                        Klik checkbox untuk memilih anggota.
                    </p>
                </div>

                <div id="edit_members_checkbox_list"
                    class="border rounded-lg max-h-64 overflow-y-auto divide-y bg-white">
                    <div class="p-4 text-sm text-gray-400 text-center">
                        Loading anggota...
                    </div>
                </div>

                <p class="text-xs text-gray-400 mt-2">
                    Role ketua dan anggota bisa dipilih sebagai anggota project.
                </p>
            </div>

            <!-- INFO -->
            <div class="mb-5 p-4 rounded-xl bg-yellow-50 border border-yellow-100 text-sm text-yellow-700">
                Perubahan anggota project akan memengaruhi siapa saja yang bisa membuat RK pribadi pada project ini.
            </div>

            <!-- ACTION -->
            <div class="flex justify-end gap-2 pt-4 border-t">
                <button type="button"
                    onclick="closeEditModal()"
                    class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg">
                    Cancel
                </button>

                <button class="bg-yellow-500 hover:bg-yellow-600 text-white px-5 py-2 rounded-lg shadow">
                    Update
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ================= MODAL VIEW ================= -->
<div id="modalView"
    class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50">

    <div class="bg-white rounded-2xl w-[720px] max-h-[85vh] overflow-y-auto shadow-xl">

        <div class="px-6 py-4 border-b flex justify-between items-start">
            <div>
                <h3 class="text-xl font-bold text-gray-900">
                    Detail Proyek
                </h3>

                <p class="text-sm text-gray-500 mt-1">
                    Detail RK Ketua, tim, ketua project, anggota project, dan progress.
                </p>
            </div>

            <button type="button"
                onclick="closeViewModal()"
                class="text-gray-400 hover:text-red-500 text-2xl leading-none">
                ×
            </button>
        </div>

        <div id="viewContent" class="p-6"></div>

        <div class="flex justify-end px-6 py-4 border-t">
            <button type="button"
                onclick="closeViewModal()"
                class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg">
                Close
            </button>
        </div>

    </div>
</div>

<!-- ================= SCRIPT ================= -->
<script>
const PROJECT_BASE_PATH = @json($projectBasePath);
const CURRENT_ROLE = @json($role);

/*
|--------------------------------------------------------------------------
| Modal Helpers
|--------------------------------------------------------------------------
*/
function openCreateModal(){
    const modal = document.getElementById('modalCreate');

    if (modal) {
        modal.classList.remove('hidden');
    }
}

function closeCreateModal(){
    const modal = document.getElementById('modalCreate');

    if (modal) {
        modal.classList.add('hidden');
    }
}

function closeEditModal(){
    document.getElementById('modalEdit').classList.add('hidden');
}

function closeViewModal(){
    document.getElementById('modalView').classList.add('hidden');
}

/*
|--------------------------------------------------------------------------
| Create Members Checkbox
|--------------------------------------------------------------------------
*/
let createMembersData = [];

document.addEventListener('DOMContentLoaded', function(){

    const rkSelect = document.getElementById('rk_ketua_select');
    const searchInput = document.getElementById('memberSearchInput');

    if (rkSelect) {
        rkSelect.addEventListener('change', function(){
            const rkId = this.value;
            const container = document.getElementById('members_checkbox_list');

            createMembersData = [];
            updateSelectedMemberCount();

            if (!rkId) {
                container.innerHTML = `
                    <div class="p-4 text-sm text-gray-400 text-center">
                        Pilih RK Ketua terlebih dahulu.
                    </div>
                `;
                return;
            }

            container.innerHTML = `
                <div class="p-4 text-sm text-gray-400 text-center">
                    Loading anggota...
                </div>
            `;

            fetch(`/rk-ketua/${rkId}/members`)
                .then(res => {
                    if (!res.ok) {
                        throw new Error('Gagal memuat anggota');
                    }

                    return res.json();
                })
                .then(data => {
                    createMembersData = data || [];
                    renderCreateMemberCheckboxes(createMembersData);
                })
                .catch(err => {
                    console.error('LOAD MEMBERS ERROR:', err);

                    container.innerHTML = `
                        <div class="p-4 text-sm text-red-500 text-center">
                            Gagal memuat anggota.
                        </div>
                    `;
                });
        });
    }

    if (searchInput) {
        searchInput.addEventListener('keyup', function(){
            const keyword = this.value.toLowerCase();

            const filtered = createMembersData.filter(user => {
                const name = String(user.name ?? '').toLowerCase();
                const role = String(user.role ?? '').toLowerCase();

                return name.includes(keyword) || role.includes(keyword);
            });

            renderCreateMemberCheckboxes(filtered);
        });
    }

    const editSearchInput = document.getElementById('editMemberSearchInput');

    if (editSearchInput) {
        editSearchInput.addEventListener('keyup', function(){
            const keyword = this.value.toLowerCase();

            const filtered = editMembersData.filter(user => {
                const name = String(user.name ?? '').toLowerCase();
                const role = String(user.role ?? '').toLowerCase();

                return name.includes(keyword) || role.includes(keyword);
            });

            renderEditMemberCheckboxes(filtered);
        });
    }
});

function renderCreateMemberCheckboxes(users) {
    const container = document.getElementById('members_checkbox_list');

    if (!container) return;

    if (!users || users.length === 0) {
        container.innerHTML = `
            <div class="p-4 text-sm text-gray-400 text-center">
                Tidak ada anggota tersedia.
            </div>
        `;
        updateSelectedMemberCount();
        return;
    }

    container.innerHTML = users.map(user => {
        const roleLabel = user.role === 'ketua' ? 'Ketua' : 'Anggota';
        const roleClass = user.role === 'ketua'
            ? 'bg-purple-100 text-purple-600'
            : 'bg-blue-100 text-blue-600';

        return `
            <label class="member-row flex items-center gap-3 p-3 hover:bg-gray-50 cursor-pointer"
                data-name="${String(user.name ?? '').toLowerCase()}"
                data-role="${String(user.role ?? '').toLowerCase()}">

                <input type="checkbox"
                    name="members[]"
                    value="${user.id}"
                    class="member-checkbox rounded border-gray-300 text-green-600 focus:ring-green-500"
                    onchange="updateSelectedMemberCount()">

                <div class="flex-1">
                    <div class="font-medium text-gray-800">
                        ${user.name ?? '-'}
                    </div>
                    <div class="text-xs text-gray-400">
                        ${user.nip ?? '-'}
                    </div>
                </div>

                <span class="text-xs px-2 py-1 rounded ${roleClass}">
                    ${roleLabel}
                </span>
            </label>
        `;
    }).join('');

    updateSelectedMemberCount();
}

function updateSelectedMemberCount() {
    const checked = document.querySelectorAll('#members_checkbox_list .member-checkbox:checked').length;
    const badge = document.getElementById('selectedMemberCount');

    if (badge) {
        badge.textContent = `${checked} dipilih`;
    }
}

function selectAllVisibleMembers() {
    document
        .querySelectorAll('#members_checkbox_list .member-checkbox')
        .forEach(checkbox => {
            checkbox.checked = true;
        });

    updateSelectedMemberCount();
}

function clearSelectedMembers() {
    document
        .querySelectorAll('#members_checkbox_list .member-checkbox')
        .forEach(checkbox => {
            checkbox.checked = false;
        });

    updateSelectedMemberCount();
}

/*
|--------------------------------------------------------------------------
| Edit Project
|--------------------------------------------------------------------------
*/
let editMembersData = [];
let editSelectedMemberIds = [];

function openEditModal(id){

    fetch(`${PROJECT_BASE_PATH}/${id}`)
        .then(res => {
            if (!res.ok) {
                throw new Error('Gagal mengambil data project');
            }

            return res.json();
        })
        .then(data => {
            const modal = document.getElementById('modalEdit');
            const form = document.getElementById('formEdit');
            const container = document.getElementById('edit_members_checkbox_list');

            if (!modal || !form || !container) {
                console.error('Element edit modal tidak lengkap.');
                return;
            }

            modal.classList.remove('hidden');

            document.getElementById('edit_name').value = data.name ?? '';
            document.getElementById('edit_rk_ketua_id').value = data.rk_ketua_id ?? '';

            form.action = `${PROJECT_BASE_PATH}/${data.id}`;

            editSelectedMemberIds = (data.members ?? []).map(member => Number(member.id));

            container.innerHTML = `
                <div class="p-4 text-sm text-gray-400 text-center">
                    Loading anggota...
                </div>
            `;

            return fetch(`/rk-ketua/${data.rk_ketua_id}/members`);
        })
        .then(res => {
            if (!res.ok) {
                throw new Error('Gagal mengambil kandidat anggota');
            }

            return res.json();
        })
        .then(users => {
            editMembersData = users ?? [];
            renderEditMemberCheckboxes(editMembersData);
        })
        .catch(err => {
            console.error('EDIT ERROR:', err);

            const container = document.getElementById('edit_members_checkbox_list');

            if (container) {
                container.innerHTML = `
                    <div class="p-4 text-sm text-red-500 text-center">
                        Gagal memuat anggota project.
                    </div>
                `;
            }

            alert('Gagal buka edit project');
        });
}

function renderEditMemberCheckboxes(users) {
    const container = document.getElementById('edit_members_checkbox_list');

    if (!container) return;

    if (!users || users.length === 0) {
        container.innerHTML = `
            <div class="p-4 text-sm text-gray-400 text-center">
                Tidak ada anggota tersedia.
            </div>
        `;
        updateEditSelectedMemberCount();
        return;
    }

    container.innerHTML = users.map(user => {
        const isChecked = editSelectedMemberIds.includes(Number(user.id)) ? 'checked' : '';

        const roleLabel = user.role === 'ketua' ? 'Ketua' : 'Anggota';
        const roleClass = user.role === 'ketua'
            ? 'bg-purple-100 text-purple-600'
            : 'bg-blue-100 text-blue-600';

        return `
            <label class="edit-member-row flex items-center gap-3 p-3 hover:bg-gray-50 cursor-pointer"
                data-name="${String(user.name ?? '').toLowerCase()}"
                data-role="${String(user.role ?? '').toLowerCase()}">

                <input type="checkbox"
                    name="members[]"
                    value="${user.id}"
                    ${isChecked}
                    class="edit-member-checkbox rounded border-gray-300 text-yellow-600 focus:ring-yellow-500"
                    onchange="updateEditSelectedMemberCount()">

                <div class="flex-1">
                    <div class="font-medium text-gray-800">
                        ${user.name ?? '-'}
                    </div>
                    <div class="text-xs text-gray-400">
                        ${user.nip ?? '-'}
                    </div>
                </div>

                <span class="text-xs px-2 py-1 rounded ${roleClass}">
                    ${roleLabel}
                </span>
            </label>
        `;
    }).join('');

    updateEditSelectedMemberCount();
}

function updateEditSelectedMemberCount() {
    const checked = document.querySelectorAll('#edit_members_checkbox_list .edit-member-checkbox:checked').length;
    const badge = document.getElementById('editSelectedMemberCount');

    if (badge) {
        badge.textContent = `${checked} dipilih`;
    }
}

function selectAllVisibleEditMembers() {
    document
        .querySelectorAll('#edit_members_checkbox_list .edit-member-checkbox')
        .forEach(checkbox => {
            checkbox.checked = true;
        });

    updateEditSelectedMemberCount();
}

function clearSelectedEditMembers() {
    document
        .querySelectorAll('#edit_members_checkbox_list .edit-member-checkbox')
        .forEach(checkbox => {
            checkbox.checked = false;
        });

    updateEditSelectedMemberCount();
}

/*
|--------------------------------------------------------------------------
| View Project
|--------------------------------------------------------------------------
*/
function openViewModal(id){
    fetch(`${PROJECT_BASE_PATH}/${id}`)
        .then(res => {
            if (!res.ok) {
                throw new Error('Gagal mengambil detail project');
            }

            return res.json();
        })
        .then(data => {
            const members = data.members ?? [];

            const membersHtml = members.length
                ? members.map(m => `
                    <div class="p-3 border-b last:border-b-0 flex justify-between gap-3">
                        <div>
                            <div class="font-medium text-gray-800">${m.name ?? '-'}</div>
                            <div class="text-xs text-gray-400">${m.nip ?? '-'}</div>
                        </div>
                        <span class="text-xs px-2 py-1 rounded h-fit ${m.role === 'ketua' ? 'bg-purple-100 text-purple-600' : 'bg-blue-100 text-blue-600'}">
                            ${m.role === 'ketua' ? 'Ketua' : 'Anggota'}
                        </span>
                    </div>
                `).join('')
                : `
                    <div class="p-4 text-sm text-gray-400 text-center">
                        Belum ada anggota project.
                    </div>
                `;

            document.getElementById('viewContent').innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-4 rounded-xl border bg-gray-50">
                        <div class="text-xs text-gray-500 mb-1">Nama Proyek</div>
                        <div class="font-semibold">${data.name ?? '-'}</div>
                    </div>

                    <div class="p-4 rounded-xl border bg-gray-50">
                        <div class="text-xs text-gray-500 mb-1">Tim</div>
                        <div class="font-semibold">${data.team?.name ?? '-'}</div>
                    </div>

                    <div class="p-4 rounded-xl border bg-gray-50">
                        <div class="text-xs text-gray-500 mb-1">RK Ketua</div>
                        <div class="font-semibold">${data.rk_ketua?.description ?? '-'}</div>
                    </div>

                    <div class="p-4 rounded-xl border bg-gray-50">
                        <div class="text-xs text-gray-500 mb-1">Ketua Project</div>
                        <div class="font-semibold">${data.leader?.name ?? '-'}</div>
                    </div>
                </div>

                <div class="mt-5">
                    <h4 class="font-semibold mb-2">Anggota Project</h4>
                    <div class="border rounded-xl overflow-hidden">
                        ${membersHtml}
                    </div>
                </div>
            `;

            document.getElementById('modalView').classList.remove('hidden');
        })
        .catch(err => {
            console.error('VIEW ERROR:', err);
            alert('Gagal membuka detail project.');
        });
}

/*
|--------------------------------------------------------------------------
| Search AJAX
|--------------------------------------------------------------------------
*/
let timer;

function triggerSearch() {
    const searchInput = document.getElementById('searchInput');

    if (searchInput) {
        searchInput.dispatchEvent(new Event('keyup'));
    }
}

const searchInput = document.getElementById('searchInput');

if (searchInput) {
    searchInput.addEventListener('keyup', function() {

        clearTimeout(timer);

        const keyword = this.value;
        const year = document.querySelector('[name="year"]')?.value ?? '';
        const team = document.querySelector('[name="team_id"]')?.value ?? '';
        const rk = document.querySelector('[name="rk_ketua_id"]')?.value ?? '';

        timer = setTimeout(() => {

            fetch(`/project/search?search=${encodeURIComponent(keyword)}&year=${year}&team_id=${team}&rk_ketua_id=${rk}`)
                .then(res => res.json())
                .then(data => {

                    const tbody = document.getElementById('projectTableBody');
                    tbody.innerHTML = '';

                    if (!data || data.length === 0) {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="5" class="text-center py-4 text-gray-500">
                                    Tidak ada data
                                </td>
                            </tr>
                        `;
                        return;
                    }

                    data.forEach(p => {
                        const canManage = Boolean(p.can_manage);

                        const manageButtons = canManage ? `
                            <button type="button"
                                onclick="openEditModal(${p.id})"
                                class="text-yellow-500 hover:underline">
                                Edit
                            </button>

                            <form method="POST"
                                action="${PROJECT_BASE_PATH}/${p.id}"
                                class="inline"
                                onsubmit="return confirm('Yakin hapus project ini?')">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                <input type="hidden" name="_method" value="DELETE">

                                <button class="text-red-500 hover:underline">
                                    Delete
                                </button>
                            </form>
                        ` : '';

                        tbody.innerHTML += `
                            <tr class="border-b hover:bg-gray-50">

                                <td class="py-2 px-3 font-medium">
                                    ${p.name ?? '-'}
                                </td>

                                <td class="py-2 px-3 text-center">
                                    ${p.members_count ?? 0}
                                </td>

                                <td class="py-2 px-3">
                                    ${p.rk_ketua?.description ?? '-'}
                                </td>

                                <td class="py-2 px-3">
                                    <div class="w-full bg-gray-200 rounded h-2">
                                        <div class="bg-green-500 h-2 rounded"
                                            style="width: ${p.progress ?? 0}%">
                                        </div>
                                    </div>
                                    <small>${p.progress ?? 0}%</small>
                                </td>

                                <td class="py-2 px-3 text-center space-x-2">
                                    <button type="button"
                                        onclick="openViewModal(${p.id})"
                                        class="text-blue-500 hover:underline">
                                        View
                                    </button>

                                    ${manageButtons}
                                </td>

                            </tr>
                        `;
                    });

                });

        }, 400);

    });
}

/*
|--------------------------------------------------------------------------
| Global Functions
|--------------------------------------------------------------------------
*/
window.openCreateModal = openCreateModal;
window.openEditModal = openEditModal;
window.openViewModal = openViewModal;
window.closeCreateModal = closeCreateModal;
window.closeEditModal = closeEditModal;
window.closeViewModal = closeViewModal;
window.selectAllVisibleMembers = selectAllVisibleMembers;
window.clearSelectedMembers = clearSelectedMembers;
window.selectAllVisibleEditMembers = selectAllVisibleEditMembers;
window.clearSelectedEditMembers = clearSelectedEditMembers;
window.triggerSearch = triggerSearch;
</script>

</x-app-layout>