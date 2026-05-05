<x-app-layout>

<div class="bg-white p-6 rounded-xl shadow">

    <!-- HEADER -->
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold">Manajemen Tim</h2>

        <div class="flex gap-2">
            <a href="{{ route('admin.team.index') }}"
                class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">
                Refresh
            </a>

            <button type="button"
                onclick="openCreateModal()"
                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg shadow">
                + Add
            </button>
        </div>
    </div>

    <!-- ALERT -->
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

    <!-- FILTER + SEARCH -->
    <form method="GET" class="grid grid-cols-3 gap-3 mb-5">

        <input type="text"
            name="search"
            value="{{ request('search') }}"
            onkeyup="liveSearchHandler(event)"
            placeholder="Cari nama tim atau ketua..."
            class="border px-3 py-2 rounded w-full">

        <div></div>

        <button type="submit"
            class="bg-gray-800 text-white px-4 rounded">
            Filter
        </button>

    </form>

    <!-- TABLE -->
    <div class="overflow-x-auto">
        <table class="w-full text-sm border">

            <thead class="bg-gray-100">
                <tr class="text-left">
                    <th class="p-2">ID</th>
                    <th class="p-2">Nama Tim</th>
                    <th class="p-2">Ketua</th>
                    <th class="p-2">Jumlah Project</th>
                    <th class="p-2 text-center">Aksi</th>
                </tr>
            </thead>

            <tbody>
            @forelse($teams as $team)

                <tr class="border-b hover:bg-gray-50">

                    <td class="p-2">{{ $team->id }}</td>

                    <td class="p-2 font-medium">
                        {{ $team->name }}
                    </td>

                    <td class="p-2">
                        {{ $team->leader->name ?? '-' }}
                    </td>

                  <td class="p-2">
                        <span class="bg-blue-100 text-blue-600 px-2 py-1 rounded text-xs">
                            {{ $team->projects_count ?? 0 }} project
                        </span>
                    </td>

                   <td class="p-2 text-center space-x-2">

                        <button type="button"
                            onclick="openViewModal({{ $team->id }})"
                            class="text-blue-500 hover:underline">
                            View
                        </button>

                        <button type="button"
                            onclick='openEditModal(@json($team))'
                            class="text-yellow-500 hover:underline">
                            Edit
                        </button>

                        <form method="POST"
                            action="{{ route('admin.team.destroy', $team->id) }}"
                            class="inline"
                            onsubmit="return confirm('Hapus tim ini?')">
                            @csrf
                            @method('DELETE')

                            <button class="text-red-500 hover:underline">
                                Delete
                            </button>
                        </form>

                    </td>

                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center py-4 text-gray-400">
                        Tidak ada tim
                    </td>
                </tr>
            @endforelse
            </tbody>

        </table>
    </div>

    <div class="mt-4 flex justify-center">
        {{ $teams->withQueryString()->links() }}
    </div>

</div>


<!-- ================= CREATE MODAL ================= -->
<div id="modalCreate"
    class="hidden fixed inset-0 bg-black/40 flex justify-center items-center z-50">

    <div class="bg-white p-6 rounded-xl w-[420px] shadow">

        <h2 class="font-bold mb-4 text-lg">Tambah Tim</h2>

        <form method="POST" action="{{ route('admin.team.store') }}">
            @csrf

            <label class="block text-sm font-medium mb-1">
                Nama Tim
            </label>
            <input name="name"
                value="{{ old('name') }}"
                placeholder="Nama Tim"
                class="border w-full mb-3 p-2 rounded"
                required>

            <label class="block text-sm font-medium mb-1">
                Ketua Tim
            </label>
            <select name="leader_id"
                class="border w-full mb-3 p-2 rounded"
                required>
                <option value="">Pilih Ketua Tim</option>

                @foreach($users->where('role', 'ketua') as $u)
                    <option value="{{ $u->id }}">
                        {{ $u->name }} ({{ $u->nip ?? '-' }})
                    </option>
                @endforeach
            </select>

            <div class="flex justify-end gap-2">
                <button type="button"
                    onclick="closeCreateModal()"
                    class="bg-gray-300 px-3 py-1 rounded">
                    Cancel
                </button>

                <button type="submit"
                    class="bg-green-600 text-white px-3 py-1 rounded">
                    Save
                </button>
            </div>

        </form>

    </div>
</div>


<!-- ================= EDIT MODAL ================= -->
<div id="modalEdit"
    class="hidden fixed inset-0 bg-black/40 flex justify-center items-center z-50">

    <div class="bg-white p-6 rounded-xl w-[420px] shadow">

        <h2 class="font-bold mb-4 text-lg">Edit Tim</h2>

        <form method="POST" id="formEdit">
            @csrf
            @method('PUT')

            <label class="block text-sm font-medium mb-1">
                Nama Tim
            </label>
            <input id="editName"
                name="name"
                class="border w-full mb-3 p-2 rounded"
                required>

            <label class="block text-sm font-medium mb-1">
                Ketua Tim
            </label>
            <select id="editLeader"
                name="leader_id"
                class="border w-full mb-3 p-2 rounded"
                required>
                <option value="">Pilih Ketua Tim</option>

                @foreach($users->where('role', 'ketua') as $u)
                    <option value="{{ $u->id }}">
                        {{ $u->name }} ({{ $u->nip ?? '-' }})
                    </option>
                @endforeach
            </select>


            <div class="flex justify-end gap-2">
                <button type="button"
                    onclick="closeEditModal()"
                    class="bg-gray-300 px-3 py-1 rounded">
                    Cancel
                </button>

                <button type="submit"
                    class="bg-yellow-500 text-white px-3 py-1 rounded">
                    Update
                </button>
            </div>

        </form>

    </div>
</div>
<!-- ================= VIEW MODAL ================= -->
<div id="modalView"
    class="hidden fixed inset-0 bg-black/40 flex justify-center items-center z-50">

    <div class="bg-white p-6 rounded-xl w-[760px] max-h-[85vh] overflow-y-auto shadow">

        <div class="flex justify-between items-start mb-4">
            <div>
                <h2 class="font-bold text-xl">Detail Tim</h2>
                <p class="text-sm text-gray-500">
                    IKU, RK Ketua, dan Project yang dikerjakan tim
                </p>
            </div>

            <button type="button"
                onclick="closeViewModal()"
                class="text-gray-500 hover:text-red-500 text-2xl leading-none">
                ×
            </button>
        </div>

        <div id="viewContent" class="space-y-5">
            <p class="text-gray-500">Loading...</p>
        </div>

        <div class="flex justify-end mt-5">
            <button type="button"
                onclick="closeViewModal()"
                class="bg-gray-300 px-4 py-2 rounded">
                Close
            </button>
        </div>

    </div>
</div>



<!-- ================= SCRIPT ================= -->
<script>

function openCreateModal(){
    document.getElementById('modalCreate').classList.remove('hidden');
}

function closeCreateModal(){
    document.getElementById('modalCreate').classList.add('hidden');
}

function openEditModal(data){
    document.getElementById('modalEdit').classList.remove('hidden');

    document.getElementById('editName').value = data.name ?? '';
    document.getElementById('editLeader').value = data.leader_id ?? '';


    document.getElementById('formEdit').action = `/admin/team/${data.id}`;
}

function closeEditModal(){
    document.getElementById('modalEdit').classList.add('hidden');
}

function openViewModal(id) {
    const modal = document.getElementById('modalView');
    const content = document.getElementById('viewContent');

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
                ? `<p class="text-sm text-gray-400">Belum ada RK Ketua untuk tim ini.</p>`
                : `
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm border">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="p-2 text-left">IKU</th>
                                    <th class="p-2 text-left">Tahun</th>
                                    <th class="p-2 text-left">RK Ketua</th>
                                    <th class="p-2 text-center">Project</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${rkKetuas.map(rk => `
                                    <tr class="border-b">
                                        <td class="p-2">${rk.iku?.name ?? '-'}</td>
                                        <td class="p-2">${rk.iku?.year ?? '-'}</td>
                                        <td class="p-2">${rk.description ?? '-'}</td>
                                        <td class="p-2 text-center">
                                            <span class="bg-blue-100 text-blue-600 px-2 py-1 rounded text-xs">
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
                ? `<p class="text-sm text-gray-400">Belum ada project untuk tim ini.</p>`
                : `
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm border">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="p-2 text-left">Project</th>
                                    <th class="p-2 text-left">IKU</th>
                                    <th class="p-2 text-left">RK Ketua</th>
                                    <th class="p-2 text-center">Anggota Project</th>
                                    <th class="p-2 text-center">RK Approved</th>
                                    <th class="p-2 text-center">Progress</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${projects.map(project => `
                                    <tr class="border-b">
                                        <td class="p-2 font-medium">${project.name ?? '-'}</td>
                                        <td class="p-2">${project.rk_ketua?.iku?.name ?? '-'}</td>
                                        <td class="p-2">${project.rk_ketua?.description ?? '-'}</td>
                                        <td class="p-2 text-center">${project.members_count ?? 0}</td>
                                        <td class="p-2 text-center">
                                            ${project.approved_rk_count ?? 0}/${project.rk_anggota_count ?? 0}
                                        </td>
                                        <td class="p-2 text-center">
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
                <div class="border rounded p-4 bg-gray-50">
                    <h3 class="font-semibold text-lg">${data.name ?? '-'}</h3>
                    <p class="text-sm text-gray-600">
                        Ketua Tim: <b>${data.leader?.name ?? '-'}</b>
                    </p>
                    <div class="mt-2 flex gap-2">
                        <span class="bg-purple-100 text-purple-600 px-2 py-1 rounded text-xs">
                            ${data.rk_ketuas_count ?? 0} RK Ketua
                        </span>
                        <span class="bg-blue-100 text-blue-600 px-2 py-1 rounded text-xs">
                            ${data.projects_count ?? 0} Project
                        </span>
                    </div>
                </div>

                <div>
                    <h3 class="font-semibold mb-2">RK Ketua & IKU</h3>
                    ${rkHtml}
                </div>

                <div>
                    <h3 class="font-semibold mb-2">Project Tim</h3>
                    ${projectHtml}
                </div>
            `;
        })
        .catch(err => {
            console.error('VIEW TEAM ERROR:', err);

            content.innerHTML = `
                <div class="p-3 bg-red-100 text-red-700 rounded">
                    Gagal memuat detail tim.
                </div>
            `;
        });
}

function closeViewModal() {
    document.getElementById('modalView').classList.add('hidden');
}

let searchTimer = null;

function liveSearchHandler(e) {
    if (e.key === "Enter") return;

    if (!document.getElementById('modalCreate').classList.contains('hidden') ||
        !document.getElementById('modalEdit').classList.contains('hidden')) {
        return;
    }

    clearTimeout(searchTimer);

    searchTimer = setTimeout(() => {
        e.target.form.submit();
    }, 500);
}

</script>

</x-app-layout>