<x-app-layout>

@php
    $ketuaUsers = $users->where('role', 'ketua');
@endphp

<div class="bg-white p-4 sm:p-6 rounded-xl shadow">

    <!-- ================= HEADER ================= -->
    <div class="flex flex-col gap-3 md:flex-row md:justify-between md:items-center mb-6">
        <div>
            <h2 class="text-xl font-bold text-gray-900">
                Manajemen Tim
            </h2>

            <p class="text-sm text-gray-500 mt-1">
                Kelola tim kerja, ketua tim, dan monitoring jumlah project pada setiap tim.
            </p>
        </div>

        <div class="flex flex-col sm:flex-row gap-2 w-full md:w-auto">
            <a href="{{ route('admin.team.index') }}"
                class="w-full sm:w-auto text-center px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300 text-sm font-medium">
                Refresh
            </a>

            <button type="button"
                onclick="openCreateModal()"
                class="w-full sm:w-auto bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg shadow text-sm font-semibold">
                + Add Tim
            </button>
        </div>
    </div>

    <!-- ================= ALERT ================= -->
    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg">
            <ul class="list-disc ml-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- ================= FILTER ================= -->
    <form method="GET"
        class="mb-5">

        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">

            <div class="md:col-span-8">
                <input type="text"
                    name="search"
                    value="{{ request('search') }}"
                    onkeyup="liveSearchHandler(event)"
                    placeholder="Cari nama tim atau ketua tim..."
                    autocomplete="off"
                    class="border px-3 py-2 rounded-lg w-full focus:ring focus:ring-green-100 focus:border-green-500">
            </div>

            <button type="submit"
                class="md:col-span-2 bg-gray-800 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">
                Filter
            </button>

            <a href="{{ route('admin.team.index') }}"
                class="md:col-span-2 bg-gray-200 hover:bg-gray-300 text-center px-4 py-2 rounded-lg">
                Reset
            </a>

        </div>
    </form>

    <!-- ================= DESKTOP TABLE ================= -->
    <div class="hidden md:block overflow-x-auto rounded-xl border">
        <table class="w-full text-sm min-w-[820px]">

            <thead class="bg-gray-50 border-b">
                <tr class="text-left">
                    <th class="p-3 w-[70px]">ID</th>
                    <th class="p-3">Nama Tim</th>
                    <th class="p-3">Ketua Tim</th>
                    <th class="p-3 text-center">Jumlah Project</th>
                    <th class="p-3 text-center w-[120px]">Aksi</th>
                </tr>
            </thead>

            <tbody>
            @forelse($teams as $team)

                <tr class="border-b last:border-b-0 hover:bg-gray-50">

                    <td class="p-3 text-gray-500">
                        #{{ $team->id }}
                    </td>

                    <td class="p-3">
                        <div class="font-semibold text-gray-900">
                            {{ $team->name }}
                        </div>
                    </td>

                    <td class="p-3">
                        <div class="font-medium text-gray-800">
                            {{ $team->leader->name ?? '-' }}
                        </div>

                        @if($team->leader?->nip)
                            <div class="text-xs text-gray-400 mt-1">
                                NIP: {{ $team->leader->nip }}
                            </div>
                        @endif
                    </td>

                    <td class="p-3 text-center">
                        <span class="bg-blue-100 text-blue-600 px-2 py-1 rounded text-xs font-semibold">
                            {{ $team->projects_count ?? 0 }} project
                        </span>
                    </td>

                    <td class="p-3 text-center relative">
                        <div class="relative inline-block text-left">

                            <button type="button"
                                data-team-action-button
                                onclick="toggleTeamActionMenu('team-action-menu-desktop-{{ $team->id }}', event)"
                                class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                                Aksi
                                <span class="text-xs">▾</span>
                            </button>

                            <div id="team-action-menu-desktop-{{ $team->id }}"
                                data-team-action-menu
                                class="hidden absolute right-0 top-full z-50 mt-2 w-44 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl">

                                <button type="button"
                                    onclick="closeAllTeamActionMenus(); openViewModal({{ $team->id }})"
                                    class="block w-full px-4 py-2 text-left text-sm text-blue-600 hover:bg-blue-50">
                                    View
                                </button>

                               <button type="button"
                                data-team-id="{{ $team->id }}"
                                data-team-name="{{ $team->name }}"
                                data-team-leader-id="{{ $team->leader_id }}"
                                onclick="openEditModalFromButton(this)"
                                class="block w-full px-4 py-2 text-left text-sm text-yellow-600 hover:bg-yellow-50">
                                Edit
                            </button>

                                <form method="POST"
                                    action="{{ route('admin.team.destroy', $team->id) }}"
                                    onsubmit="return confirm('Hapus tim ini?')">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit"
                                        class="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50">
                                        Delete
                                    </button>
                                </form>

                            </div>
                        </div>
                    </td>

                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center py-5 text-gray-400">
                        Tidak ada tim
                    </td>
                </tr>
            @endforelse
            </tbody>

        </table>
    </div>

    <!-- ================= MOBILE CARDS ================= -->
    <div class="md:hidden space-y-3">
        @forelse($teams as $team)
            <div class="rounded-xl border bg-white p-4 shadow-sm">

                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="text-xs text-gray-400 mb-1">
                            #{{ $team->id }}
                        </div>

                        <h3 class="font-semibold text-gray-900 break-words">
                            {{ $team->name }}
                        </h3>

                        <p class="text-sm text-gray-500 mt-1">
                            Ketua:
                            <span class="font-medium text-gray-700">
                                {{ $team->leader->name ?? '-' }}
                            </span>
                        </p>

                        @if($team->leader?->nip)
                            <p class="text-xs text-gray-400 mt-1">
                                NIP: {{ $team->leader->nip }}
                            </p>
                        @endif
                    </div>

                    <div class="relative shrink-0">
                        <button type="button"
                            data-team-action-button
                            onclick="toggleTeamActionMenu('team-action-menu-mobile-{{ $team->id }}', event)"
                            class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                            Aksi
                            <span class="text-xs">▾</span>
                        </button>

                        <div id="team-action-menu-mobile-{{ $team->id }}"
                            data-team-action-menu
                            class="hidden absolute right-0 top-full z-50 mt-2 w-44 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl">

                            <button type="button"
                                onclick="closeAllTeamActionMenus(); openViewModal({{ $team->id }})"
                                class="block w-full px-4 py-2 text-left text-sm text-blue-600 hover:bg-blue-50">
                                View
                            </button>

                            <button type="button"
                                data-team-id="{{ $team->id }}"
                                data-team-name="{{ $team->name }}"
                                data-team-leader-id="{{ $team->leader_id }}"
                                onclick="openEditModalFromButton(this)"
                                class="block w-full px-4 py-2 text-left text-sm text-yellow-600 hover:bg-yellow-50">
                                Edit
                            </button>

                            <form method="POST"
                                action="{{ route('admin.team.destroy', $team->id) }}"
                                onsubmit="return confirm('Hapus tim ini?')">
                                @csrf
                                @method('DELETE')

                                <button type="submit"
                                    class="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50">
                                    Delete
                                </button>
                            </form>

                        </div>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="bg-blue-100 text-blue-600 px-2 py-1 rounded text-xs font-semibold">
                        {{ $team->projects_count ?? 0 }} project
                    </span>
                </div>

            </div>
        @empty
            <div class="rounded-xl border p-5 text-center text-gray-400">
                Tidak ada tim
            </div>
        @endforelse
    </div>

    <div class="mt-4 flex justify-center">
        {{ $teams->withQueryString()->links() }}
    </div>

</div>

<!-- ================= CREATE MODAL ================= -->
<div id="modalCreate"
    class="hidden fixed inset-0 bg-black/40 flex justify-center items-center z-50 px-4">

    <div class="bg-white rounded-2xl w-full sm:w-[560px] max-h-[90vh] overflow-y-auto shadow-xl">

        <!-- HEADER -->
        <div class="px-5 sm:px-6 py-4 border-b flex justify-between items-start">
            <div>
                <h3 class="text-xl font-bold text-gray-900">
                    Tambah Tim
                </h3>

                <p class="text-sm text-gray-500 mt-1">
                    Buat tim kerja dan tentukan ketua tim yang bertanggung jawab.
                </p>
            </div>

            <button type="button"
                onclick="closeCreateModal()"
                class="text-gray-400 hover:text-red-500 text-2xl leading-none">
                ×
            </button>
        </div>

        <form method="POST" action="{{ route('admin.team.store') }}" class="p-5 sm:p-6">
            @csrf

            <!-- NAMA TIM -->
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Nama Tim
                </label>

                <input name="name"
                    value="{{ old('name') }}"
                    placeholder="Contoh: Tim Statistik Sosial"
                    class="border w-full p-3 rounded-lg focus:ring focus:ring-green-100 focus:border-green-500"
                    required>

                <p class="text-xs text-gray-400 mt-1">
                    Gunakan nama tim yang jelas agar mudah dipantau pada RK Ketua dan Project.
                </p>
            </div>

            <!-- KETUA TIM -->
            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Ketua Tim
                </label>

                <select name="leader_id"
                    class="border w-full p-3 rounded-lg bg-white focus:ring focus:ring-green-100 focus:border-green-500"
                    required>
                    <option value="">Pilih Ketua Tim</option>

                    @foreach($ketuaUsers as $u)
                        <option value="{{ $u->id }}"
                            {{ old('leader_id') == $u->id ? 'selected' : '' }}>
                            {{ $u->name }} ({{ $u->nip ?? '-' }})
                        </option>
                    @endforeach
                </select>

                <p class="text-xs text-gray-400 mt-1">
                    Hanya user dengan role Ketua yang dapat dipilih sebagai ketua tim.
                </p>
            </div>

            <!-- INFO -->
            <div class="mb-5 p-4 rounded-xl bg-blue-50 border border-blue-100 text-sm text-blue-700">
                Tim akan digunakan pada RK Ketua, Project, dan monitoring progress pekerjaan.
            </div>

            <!-- ACTION -->
            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-4 border-t">
                <button type="button"
                    onclick="closeCreateModal()"
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

<!-- ================= EDIT MODAL ================= -->
<div id="modalEdit"
    class="hidden fixed inset-0 bg-black/40 flex justify-center items-center z-50 px-4">

    <div class="bg-white rounded-2xl w-full sm:w-[560px] max-h-[90vh] overflow-y-auto shadow-xl">

        <!-- HEADER -->
        <div class="px-5 sm:px-6 py-4 border-b flex justify-between items-start">
            <div>
                <h3 class="text-xl font-bold text-gray-900">
                    Edit Tim
                </h3>

                <p class="text-sm text-gray-500 mt-1">
                    Perbarui nama tim atau ketua tim.
                </p>
            </div>

            <button type="button"
                onclick="closeEditModal()"
                class="text-gray-400 hover:text-red-500 text-2xl leading-none">
                ×
            </button>
        </div>

        <form method="POST" id="formEdit" class="p-5 sm:p-6">
            @csrf
            @method('PUT')

            <!-- NAMA TIM -->
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Nama Tim
                </label>

                <input id="editName"
                    name="name"
                    placeholder="Nama Tim"
                    class="border w-full p-3 rounded-lg focus:ring focus:ring-yellow-100 focus:border-yellow-500"
                    required>
            </div>

            <!-- KETUA TIM -->
            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Ketua Tim
                </label>

                <select id="editLeader"
                    name="leader_id"
                    class="border w-full p-3 rounded-lg bg-white focus:ring focus:ring-yellow-100 focus:border-yellow-500"
                    required>
                    <option value="">Pilih Ketua Tim</option>

                    @foreach($ketuaUsers as $u)
                        <option value="{{ $u->id }}">
                            {{ $u->name }} ({{ $u->nip ?? '-' }})
                        </option>
                    @endforeach
                </select>

                <p class="text-xs text-gray-400 mt-1">
                    Jika ketua tim diganti, pastikan perubahan ini sesuai struktur kerja.
                </p>
            </div>

            <!-- INFO -->
            <div class="mb-5 p-4 rounded-xl bg-yellow-50 border border-yellow-100 text-sm text-yellow-700">
                Perubahan ketua tim dapat memengaruhi akses Ketua pada RK Ketua dan Project terkait.
            </div>

            <!-- ACTION -->
            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-4 border-t">
                <button type="button"
                    onclick="closeEditModal()"
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

<!-- ================= VIEW MODAL ================= -->
<div id="modalView"
    class="hidden fixed inset-0 bg-black/40 flex justify-center items-center z-50 px-4">

    <div class="bg-white rounded-2xl w-full sm:w-[900px] max-h-[85vh] overflow-y-auto shadow-xl">

        <!-- HEADER -->
        <div class="px-5 sm:px-6 py-4 border-b flex justify-between items-start">
            <div>
                <h3 class="text-xl font-bold text-gray-900">
                    Detail Tim
                </h3>

                <p class="text-sm text-gray-500 mt-1">
                    Detail IKU, RK Ketua, dan Project yang dikerjakan tim.
                </p>
            </div>

            <button type="button"
                onclick="closeViewModal()"
                class="text-gray-400 hover:text-red-500 text-2xl leading-none">
                ×
            </button>
        </div>

        <div id="viewContent" class="p-5 sm:p-6 space-y-5">
            <p class="text-gray-500">Loading...</p>
        </div>

        <div class="flex justify-end px-5 sm:px-6 py-4 border-t">
            <button type="button"
                onclick="closeViewModal()"
                class="w-full sm:w-auto bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg">
                Close
            </button>
        </div>

    </div>
</div>

<!-- ================= SCRIPT ================= -->
<script>
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

/*
|--------------------------------------------------------------------------
| Row Action Dropdown
|--------------------------------------------------------------------------
*/

function closeAllTeamActionMenus() {
    document.querySelectorAll('[data-team-action-menu]').forEach(menu => {
        menu.classList.add('hidden');
    });
}

function toggleTeamActionMenu(menuId, event = null) {
    if (event) {
        event.stopPropagation();
    }

    const targetMenu = document.getElementById(menuId);

    if (!targetMenu) {
        return;
    }

    const isCurrentlyHidden = targetMenu.classList.contains('hidden');

    closeAllTeamActionMenus();

    if (isCurrentlyHidden) {
        targetMenu.classList.remove('hidden');
    }
}

document.addEventListener('click', function (event) {
    const clickedButton = event.target.closest('[data-team-action-button]');
    const clickedMenu = event.target.closest('[data-team-action-menu]');

    if (!clickedButton && !clickedMenu) {
        closeAllTeamActionMenus();
    }
});

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        closeAllTeamActionMenus();
    }
});

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

function openEditModalFromButton(button) {
    closeAllTeamActionMenus();

    openEditModal({
        id: button.dataset.teamId,
        name: button.dataset.teamName,
        leader_id: button.dataset.teamLeaderId,
    });
}

function openEditModal(data){
    const modal = document.getElementById('modalEdit');
    const editName = document.getElementById('editName');
    const editLeader = document.getElementById('editLeader');
    const formEdit = document.getElementById('formEdit');

    if (!modal || !editName || !editLeader || !formEdit) {
        return;
    }

    modal.classList.remove('hidden');

    editName.value = data.name ?? '';
    editLeader.value = data.leader_id ?? '';
    formEdit.action = `/admin/team/${data.id}`;
}

function closeEditModal(){
    const modal = document.getElementById('modalEdit');

    if (modal) {
        modal.classList.add('hidden');
    }
}

function closeViewModal() {
    const modal = document.getElementById('modalView');

    if (modal) {
        modal.classList.add('hidden');
    }
}

/*
|--------------------------------------------------------------------------
| View Team
|--------------------------------------------------------------------------
*/

function openViewModal(id) {
    const modal = document.getElementById('modalView');
    const content = document.getElementById('viewContent');

    if (!modal || !content) {
        return;
    }

    modal.classList.remove('hidden');
    content.innerHTML = '<p class="text-gray-500">Loading...</p>';

    fetch(`/admin/team/${id}`)
        .then(res => {
            if (!res.ok) {
                throw new Error('Gagal mengambil detail tim');
            }

            return res.json();
        })
        .then(data => {
            const rkKetuas = data.rk_ketuas ?? [];
            const projects = data.projects ?? [];

            const rkHtml = rkKetuas.length === 0
                ? `
                    <div class="p-4 rounded-xl bg-yellow-50 text-yellow-700 border border-yellow-100 text-sm">
                        Belum ada RK Ketua untuk tim ini.
                    </div>
                `
                : `
                    <div class="overflow-x-auto rounded-xl border">
                        <table class="w-full text-sm min-w-[780px]">
                            <thead class="bg-gray-50 border-b">
                                <tr>
                                    <th class="p-3 text-left">IKU</th>
                                    <th class="p-3 text-left">Tahun</th>
                                    <th class="p-3 text-left">RK Ketua</th>
                                    <th class="p-3 text-center">Project</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${rkKetuas.map(rk => `
                                    <tr class="border-b last:border-b-0 hover:bg-gray-50">
                                        <td class="p-3">${escapeHtml(rk.iku?.name ?? '-')}</td>
                                        <td class="p-3">${escapeHtml(rk.iku?.year ?? '-')}</td>
                                        <td class="p-3">${escapeHtml(rk.description ?? '-')}</td>
                                        <td class="p-3 text-center">
                                            <span class="bg-blue-100 text-blue-600 px-2 py-1 rounded text-xs font-semibold">
                                                ${rk.project_count ?? 0} project
                                            </span>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;

            const projectHtml = projects.length === 0
                ? `
                    <div class="p-4 rounded-xl bg-yellow-50 text-yellow-700 border border-yellow-100 text-sm">
                        Belum ada project untuk tim ini.
                    </div>
                `
                : `
                    <div class="overflow-x-auto rounded-xl border">
                        <table class="w-full text-sm min-w-[900px]">
                            <thead class="bg-gray-50 border-b">
                                <tr>
                                    <th class="p-3 text-left">Project</th>
                                    <th class="p-3 text-left">IKU</th>
                                    <th class="p-3 text-left">RK Ketua</th>
                                    <th class="p-3 text-center">Anggota</th>
                                    <th class="p-3 text-center">RK Selesai</th>
                                    <th class="p-3 text-center">Progress</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${projects.map(project => `
                                    <tr class="border-b last:border-b-0 hover:bg-gray-50">
                                        <td class="p-3 font-medium text-gray-900">
                                            ${escapeHtml(project.name ?? '-')}
                                        </td>
                                        <td class="p-3">
                                            ${escapeHtml(project.rk_ketua?.iku?.name ?? '-')}
                                        </td>
                                        <td class="p-3">
                                            ${escapeHtml(project.rk_ketua?.description ?? '-')}
                                        </td>
                                        <td class="p-3 text-center">
                                            ${project.members_count ?? 0}
                                        </td>
                                        <td class="p-3 text-center">
                                            ${project.approved_rk_count ?? 0}/${project.rk_anggota_count ?? 0}
                                        </td>
                                        <td class="p-3 text-center">
                                            <div class="w-full bg-gray-200 rounded h-2 mb-1">
                                                <div class="bg-green-500 h-2 rounded"
                                                    style="width: ${project.progress ?? 0}%">
                                                </div>
                                            </div>
                                            <span class="text-xs">${project.progress ?? 0}%</span>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;

            content.innerHTML = `
                <div class="p-4 rounded-xl bg-gray-50 border">
                    <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-3">
                        <div>
                            <div class="text-xs text-gray-500 mb-1">
                                Nama Tim
                            </div>
                            <h3 class="font-bold text-lg text-gray-900">
                                ${escapeHtml(data.name ?? '-')}
                            </h3>
                            <p class="text-sm text-gray-600 mt-1">
                                Ketua Tim:
                                <b>${escapeHtml(data.leader?.name ?? '-')}</b>
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <span class="bg-purple-100 text-purple-600 px-2 py-1 rounded text-xs font-semibold">
                                ${data.rk_ketuas_count ?? 0} RK Ketua
                            </span>

                            <span class="bg-blue-100 text-blue-600 px-2 py-1 rounded text-xs font-semibold">
                                ${data.projects_count ?? 0} Project
                            </span>
                        </div>
                    </div>
                </div>

                <div>
                    <h4 class="font-semibold mb-2 text-gray-900">
                        RK Ketua & IKU
                    </h4>
                    ${rkHtml}
                </div>

                <div>
                    <h4 class="font-semibold mb-2 text-gray-900">
                        Project Tim
                    </h4>
                    ${projectHtml}
                </div>
            `;
        })
        .catch(err => {
            console.error('VIEW TEAM ERROR:', err);

            content.innerHTML = `
                <div class="p-4 bg-red-100 text-red-700 rounded-xl">
                    Gagal memuat detail tim.
                </div>
            `;
        });
}

/*
|--------------------------------------------------------------------------
| Live Search
|--------------------------------------------------------------------------
*/

let searchTimer = null;

function liveSearchHandler(e) {
    if (e.key === "Enter") {
        return;
    }

    const createModal = document.getElementById('modalCreate');
    const editModal = document.getElementById('modalEdit');

    if (
        (createModal && !createModal.classList.contains('hidden')) ||
        (editModal && !editModal.classList.contains('hidden'))
    ) {
        return;
    }

    clearTimeout(searchTimer);

    searchTimer = setTimeout(() => {
        e.target.form.submit();
    }, 500);
}
</script>

</x-app-layout>
