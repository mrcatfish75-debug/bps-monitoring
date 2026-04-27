<x-app-layout>

<div class="bg-white p-6 rounded-xl shadow">

    <!-- ================= HEADER ================= -->
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">Proyek</h2>

        <div class="flex gap-2">
            <a href="" class="px-4 py-2 bg-gray-200 rounded">Refresh</a>

            <button onclick="openCreateModal()"
                class="bg-green-600 text-white px-4 py-2 rounded">
                + Add
            </button>
        </div>
    </div>

    <!-- ================= FILTER ================= -->
    <form method="GET" class="grid grid-cols-5 gap-2 mb-4">

        <!-- TAHUN -->
        <select name="year" onchange="triggerSearch()" class="border px-3 py-2 rounded">
            @for($y = date('Y'); $y >= 2020; $y--)
                <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>
                    {{ $y }}
                </option>
            @endfor
        </select>

        <!-- TEAM -->
       <select name="team_id" onchange="triggerSearch()" class="border px-3 py-2 rounded">
            <option value="">Tim Kerja</option>
            @foreach($teams as $t)
                <option value="{{ $t->id }}" {{ request('team_id') == $t->id ? 'selected' : '' }}>
                    {{ $t->name }}
                </option>
            @endforeach
        </select>

        <!-- RK KETUA -->
        <select name="rk_ketua_id" onchange="triggerSearch()" class="border px-3 py-2 rounded">
            <option value="">RK Ketua</option>
            @foreach($rkKetuas as $rk)
                <option value="{{ $rk->id }}" {{ request('rk_ketua_id') == $rk->id ? 'selected' : '' }}>
                    {{ $rk->description }}
                </option>
            @endforeach
        </select>

        <!-- SEARCH -->
        <input type="text" id="searchInput"
            placeholder="Pencarian..."
            class="border px-3 py-2 rounded">

        <button class="bg-gray-800 text-white px-4 rounded">
            Filter
        </button>
    </form>

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
        <a href="{{ request()->fullUrlWithQuery([
            'sort' => 'members_count',
            'direction' => request('direction') == 'asc' ? 'desc' : 'asc'
        ]) }}">
            Anggota
        </a>
    </th>

    <!-- RK -->
    <th class="py-2 px-3">
        Rencana Ketua
    </th>

    <!-- PROGRESS -->
    <th class="py-2 px-3 text-center">
        Progress
    </th>

    <!-- CREATED -->
    <th class="py-2 px-3 text-center">
        <a href="{{ request()->fullUrlWithQuery([
            'sort' => 'created_at',
            'direction' => request('direction') == 'asc' ? 'desc' : 'asc'
        ]) }}">
            Dibuat
            @if(request('sort') == 'created_at')
                {{ request('direction') == 'asc' ? '↑' : '↓' }}
            @endif
        </a>
    </th>

    <!-- AKSI -->
    <th class="py-2 px-3 text-center">Aksi</th>

</tr>
</thead>

            <tbody id="projectTableBody">
            @forelse($projects as $p)
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

                        <button onclick="openViewModal({{ $p->id }})"
                            class="text-blue-500 hover:underline">
                            View
                        </button>

                        <button onclick="openEditModal({{ $p->id }})"
                            class="text-yellow-500 hover:underline">
                            Edit
                        </button>

                        <form method="POST"
                            action="{{ route('project.destroy',$p->id) }}"
                            class="inline">
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
                    <td colspan="5" class="text-center py-4 text-gray-500">
                        Tidak ada data
                    </td>
                </tr>
            @endforelse
            </tbody>

        </table>
    </div>

    <div class="mt-4 flex justify-center">
    {{ $projects->withQueryString()->links() }}
</div>

</div>

<!-- ================= MODAL CREATE ================= -->
<div id="modalCreate"
    class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50">

    <div class="bg-white p-6 rounded w-[500px] shadow">

        <h3 class="text-lg font-semibold mb-3">Tambah Proyek</h3>

        <form method="POST" action="{{ route('project.store') }}">
            @csrf

            <!-- RK -->
            <select id="rk_ketua_select" name="rk_ketua_id"
                class="border w-full mb-2 p-2 rounded">
                <option value="">Pilih RK Ketua</option>
                @foreach($rkKetuas as $rk)
                    <option value="{{ $rk->id }}">
                        {{ $rk->description }}
                    </option>
                @endforeach
            </select>

            <!-- NAMA -->
            <input name="name"
                placeholder="Nama Proyek"
                class="border w-full mb-2 p-2 rounded">

            <!-- MEMBERS -->
            <select id="members_select" name="members[]"
                multiple size="6"
                class="border w-full p-2 mb-4 rounded">
            </select>

            <div class="flex justify-end gap-2">
                <button type="button"
                    onclick="closeModal()"
                    class="bg-gray-300 px-4 py-1 rounded">
                    Cancel
                </button>

                <button class="bg-green-600 text-white px-4 py-1 rounded">
                    Save
                </button>
            </div>

        </form>

    </div>
</div>


<!-- ================= MODAL EDIT ================= -->
<div id="modalEdit"
    class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50">

    <div class="bg-white p-6 w-[500px] rounded shadow">

        <h3 class="text-lg font-semibold mb-3">Edit Proyek</h3>

        <form method="POST" id="formEdit">
            @csrf
            @method('PUT')

            <!-- NAME -->
            <input id="edit_name" name="name"
                class="border w-full mb-2 p-2 rounded">

            <!-- MEMBERS -->
            <select id="edit_members" name="members[]"
                multiple size="6"
                class="border w-full mb-4 p-2 rounded">
            </select>

            <div class="flex justify-end gap-2">
                <button type="button"
                    onclick="closeEditModal()"
                    class="bg-gray-300 px-3 py-1 rounded">
                    Cancel
                </button>

                <button class="bg-yellow-500 text-white px-3 py-1 rounded">
                    Update
                </button>
            </div>

        </form>

    </div>
</div>


<!-- ================= MODAL VIEW ================= -->
<div id="modalView"
    class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50">

    <div class="bg-white p-6 w-96 rounded shadow">
        <div id="viewContent"></div>

        <button onclick="closeViewModal()"
            class="mt-4 bg-gray-300 px-4 py-2 rounded">
            Close
        </button>
    </div>
</div>


<!-- ================= SCRIPT ================= -->
<script>

// ================= CREATE MODAL =================
function openCreateModal(){
    document.getElementById('modalCreate').classList.remove('hidden');
}

function closeModal(){
    document.getElementById('modalCreate').classList.add('hidden');
}


// ================= EDIT =================
function openEditModal(id){

    fetch(`/admin/project/${id}`)
    .then(res => res.json())
    .then(data => {

        let modal = document.getElementById('modalEdit');

        if (!modal) {
            console.error('modalEdit tidak ditemukan');
            return;
        }

        modal.classList.remove('hidden');

        document.getElementById('edit_name').value = data.name;

        document.getElementById('formEdit').action =
            `/admin/project/${data.id}`;

        // LOAD MEMBERS
        fetch(`/rk-ketua/${data.rk_ketua_id}/members`)
        .then(res => res.json())
        .then(users => {

            let select = document.getElementById('edit_members');
            select.innerHTML = '';

            users.forEach(u => {

                let selected = data.members.some(m => m.id === u.id)
                    ? 'selected'
                    : '';

                select.innerHTML += `
                    <option value="${u.id}" ${selected}>
                        ${u.name}
                    </option>
                `;
            });

        });

    })
    .catch(err => {
        console.error('EDIT ERROR:', err);
        alert('Gagal buka edit');
    });
}

function closeEditModal(){
    document.getElementById('modalEdit').classList.add('hidden');
}


// ================= VIEW =================
function openViewModal(id){
    fetch(`/admin/project/${id}`)
    .then(res => res.json())
    .then(data => {

        document.getElementById('viewContent').innerHTML = `
            <p><b>Nama:</b> ${data.name}</p>
            <p><b>RK Ketua:</b> ${data.rk_ketua?.description ?? '-'}</p>
            <p><b>Tim:</b> ${data.team?.name ?? '-'}</p>
            <p><b>Ketua:</b> ${data.leader?.name ?? '-'}</p>
            <p class="mt-2 font-bold">Anggota:</p>
            <ul>
                ${data.members.map(m => `<li>- ${m.name}</li>`).join('')}
            </ul>
        `;

        document.getElementById('modalView').classList.remove('hidden');
    });
}

function closeViewModal(){
    document.getElementById('modalView').classList.add('hidden');
}


// ================= AUTO MEMBER CREATE =================
document.addEventListener('DOMContentLoaded', function(){

    let el = document.getElementById('rk_ketua_select');
    if (!el) return;

    el.addEventListener('change', function(){

        let rkId = this.value;
        let memberSelect = document.getElementById('members_select');

        memberSelect.innerHTML = '<option>Loading...</option>';

        if (!rkId) return;

        fetch(`/rk-ketua/${rkId}/members`)
        .then(res => res.json())
        .then(data => {

            memberSelect.innerHTML = '';

            if (data.length === 0) {
                memberSelect.innerHTML = '<option>Tidak ada anggota</option>';
                return;
            }

            data.forEach(user => {
                let option = document.createElement('option');
                option.value = user.id;
                option.textContent = user.name;
                memberSelect.appendChild(option);
            });

        });

    });

});


// GLOBAL (BIAR BUTTON BISA CLICK)
window.openEditModal = openEditModal;
window.openViewModal = openViewModal;

</script>

<script>
let timer;

function triggerSearch() {
    document.getElementById('searchInput')
        .dispatchEvent(new Event('keyup'));
}

document.getElementById('searchInput').addEventListener('keyup', function() {

    clearTimeout(timer);

    let keyword = this.value;
    let year = document.querySelector('[name="year"]')?.value ?? '';
    let team = document.querySelector('[name="team_id"]')?.value ?? '';
    let rk = document.querySelector('[name="rk_ketua_id"]')?.value ?? '';

    timer = setTimeout(() => {

        fetch(`/project/search?search=${keyword}&year=${year}&team_id=${team}&rk_ketua_id=${rk}`)
        .then(res => res.json())
        .then(data => {

            let tbody = document.getElementById('projectTableBody');
            tbody.innerHTML = '';

            if (data.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-4 text-gray-500">
                            Tidak ada data
                        </td>
                    </tr>
                `;
                return;
            }

            data.forEach(p => {

                tbody.innerHTML += `
                    <tr class="border-b hover:bg-gray-50">

                        <td class="py-2 px-3 font-medium">
                            ${p.name}
                        </td>

                        <td class="py-2 px-3 text-center">
                            -
                        </td>

                        <td class="py-2 px-3">
                            ${p.rk_ketua?.description ?? '-'}
                        </td>

                        <td class="py-2 px-3">
                            -
                        </td>

                        <td class="py-2 px-3 text-center">
                            -
                        </td>

                      <td class="py-2 px-3 text-center space-x-2">

    <button onclick="openViewModal(${p.id})"
        class="text-blue-500 hover:underline">
        View
    </button>

    <button onclick="openEditModal(${p.id})"
        class="text-yellow-500 hover:underline">
        Edit
    </button>

    <form method="POST" action="/admin/project/${p.id}" class="inline">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <input type="hidden" name="_method" value="DELETE">
        <button class="text-red-500 hover:underline">
            Delete
        </button>
    </form>

</td>

                    </tr>
                `;
            });

        });

    }, 400);

});

</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    triggerSearch();
});
</script>

</x-app-layout>