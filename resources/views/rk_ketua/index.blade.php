<x-app-layout>

<div class="bg-white p-6 rounded-xl shadow">

    <!-- HEADER -->
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">Rencana Kinerja - Ketua</h2>

        <div class="flex gap-2">
            <a href="" class="px-4 py-2 bg-gray-200 rounded">Refresh</a>

            <button onclick="openCreateModal()"
                class="bg-green-600 text-white px-4 py-2 rounded">
                + Add
            </button>
        </div>
    </div>

    <!-- FILTER -->
    <form method="GET" class="grid grid-cols-4 gap-3 mb-4">

        <!-- YEAR -->
        <select name="year" class="border px-3 py-2 rounded">
            @for($y = date('Y'); $y >= 2020; $y--)
                <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>
                    {{ $y }}
                </option>
            @endfor
        </select>

        <!-- IKU -->
        <select name="iku_id" class="border px-3 py-2 rounded">
            <option value="">Semua IKU</option>
            @foreach($ikus as $iku)
                <option value="{{ $iku->id }}"
                    {{ request('iku_id') == $iku->id ? 'selected' : '' }}>
                    {{ $iku->name }}
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

    <!-- TABLE -->
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th><a href="?sort=iku_id">IKU</a></th>
                <th><a href="?sort=description">Rencana Kinerja</a></th>
                <th>Progress</th>
                <th>Aksi</th>
            </tr>
        </thead>

        <tbody id="rkKetuaTableBody">
        @foreach($rkKetuas as $rk)
        <tr class="border-b hover:bg-gray-50" data-id="{{ $rk->id }}">

            <td>{{ $rk->iku->name ?? '-' }}</td>

            <td>{{ $rk->description }}</td>

            <!-- PROGRESS -->
            <td>
                <div class="w-full bg-gray-200 rounded h-2">
                    <div class="bg-green-500 h-2 rounded"
                        style="width: {{ $rk->progress }}%">
                    </div>
                </div>
                <small>{{ $rk->progress }}%</small>
            </td>

            <!-- AKSI -->
            <td class="space-x-2">

                <!-- 🔥 VIEW BUTTON -->
                <a href="{{ url('/admin/project?rk_ketua_id='.$rk->id) }}"
                    class="text-blue-500">
                    View
                </a>

                <button onclick='openEditModal(@json($rk))'
                    class="text-yellow-500">
                    Edit
                </button>

                <form method="POST"
                    action="{{ url('/admin/rk-ketua/'.$rk->id) }}"
                    class="inline"
                    onsubmit="return confirm('Yakin hapus?')">
                    @csrf
                    @method('DELETE')
                    <button class="text-red-500">Delete</button>
                </form>

            </td>

        </tr>
        @endforeach
        </tbody>
    </table>

</div>


    <!-- 🔥 PAGINATION -->
<div class="mt-4 flex justify-center">
    {{ $rkKetuas->withQueryString()->links() }}
</div>

</div>


<!-- CREATE MODAL -->
<div id="modalCreate" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center">
<div class="bg-white p-6 rounded w-96">

<h3 class="text-lg font-semibold mb-3">Tambah RK Ketua</h3>

<form method="POST" action="{{ route('rk-ketua.store') }}">
@csrf

<!-- IKU -->
<select name="iku_id" required class="border w-full mb-2 p-2 rounded">
    <option value="">-- Pilih IKU --</option>
    @foreach($ikus as $iku)
        <option value="{{ $iku->id }}">{{ $iku->name }}</option>
    @endforeach
</select>

<!-- TEAM -->
<select name="team_id" id="teamSelect" required class="border w-full mb-2 p-2 rounded">
    <option value="">-- Pilih Tim Kerja --</option>
    @foreach($teams as $team)
        <option value="{{ $team->id }}"
            data-leader="{{ $team->leader->name ?? '' }}">
            {{ $team->name }}
        </option>
    @endforeach
</select>

<!-- KETUA AUTO -->
<input type="text" id="leaderName"
    placeholder="Ketua Tim"
    class="border w-full mb-2 p-2 rounded bg-gray-100"
    readonly>

<!-- RK KETUA -->
<textarea name="description"
    required
    placeholder="Rencana Kinerja Ketua"
    class="border w-full mb-4 p-2 rounded"></textarea>

<div class="flex justify-end gap-2">
    <button type="button" onclick="closeModal('modalCreate')"
        class="bg-gray-300 px-3 py-1 rounded">
        Cancel
    </button>

    <button class="bg-green-600 text-white px-3 py-1 rounded">
        Save
    </button>
</div>

</form>

</div>
</div>


<!-- ================= EDIT MODAL ================= -->
<div id="modalEdit" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center">
<div class="bg-white p-6 rounded w-96">

<h3 class="text-lg font-semibold mb-3">Edit RK Ketua</h3>

<form method="POST" id="formEdit">
@csrf
@method('PUT')

<!-- IKU (READONLY) -->
<input type="text" id="edit_iku"
    class="border w-full mb-2 p-2 rounded bg-gray-100"
    readonly>

<!-- TEAM (EDITABLE) -->
<select name="team_id" id="edit_teamSelect"
    class="border w-full mb-2 p-2 rounded">

    @foreach($teams as $team)
        <option value="{{ $team->id }}"
            data-leader="{{ $team->leader->name ?? '' }}">
            {{ $team->name }}
        </option>
    @endforeach
</select>

<!-- KETUA AUTO -->
<input type="text" id="edit_leader"
    class="border w-full mb-2 p-2 rounded bg-gray-100"
    readonly>

<!-- DESCRIPTION -->
<textarea id="edit_description"
    name="description"
    required
    class="border w-full mb-4 p-2 rounded"></textarea>

<div class="flex justify-end gap-2">
    <button type="button"
        onclick="closeModal('modalEdit')"
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


<!-- SCRIPT -->
<script>
function openCreateModal(){
    document.getElementById('modalCreate').classList.remove('hidden');
}

function openEditModal(data){

    document.getElementById('modalEdit').classList.remove('hidden');

    // IKU
    document.getElementById('edit_iku').value =
        data.iku?.name ?? '-';

    // TEAM SELECT
    let teamSelect = document.getElementById('edit_teamSelect');
    teamSelect.value = data.team_id;

    // SET KETUA
    let selected = teamSelect.options[teamSelect.selectedIndex];
    document.getElementById('edit_leader').value =
        selected.getAttribute('data-leader');

    // DESCRIPTION
    document.getElementById('edit_description').value =
        data.description;

    // ACTION
    document.getElementById('formEdit').action =
        `/admin/rk-ketua/${data.id}`;
}

// 🔥 AUTO UPDATE KETUA SAAT TEAM DIGANTI
document.getElementById('edit_teamSelect').addEventListener('change', function(){
    let selected = this.options[this.selectedIndex];
    let leader = selected.getAttribute('data-leader');

    document.getElementById('edit_leader').value = leader ?? '';
});

function closeModal(id){
    document.getElementById(id).classList.add('hidden');
}
function openCreateModal(){
    document.getElementById('modalCreate').classList.remove('hidden');
}

function closeModal(id){
    document.getElementById(id).classList.add('hidden');
}

document.getElementById('teamSelect').addEventListener('change', function(){
    let leader = this.options[this.selectedIndex].getAttribute('data-leader');
    document.getElementById('leaderName').value = leader ?? '';
});
</script>

<script>
let timer;

document.getElementById('searchInput').addEventListener('keyup', function() {

    clearTimeout(timer);

    let keyword = this.value;
    let year = document.querySelector('[name="year"]').value;
    let iku_id = document.querySelector('[name="iku_id"]').value;

    timer = setTimeout(() => {

        fetch(`/rk-ketua/search?search=${keyword}&year=${year}&iku_id=${iku_id}`)
        .then(res => res.json())
        .then(data => {

            let tbody = document.getElementById('rkKetuaTableBody');
            tbody.innerHTML = '';

            if (data.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center py-4 text-gray-400">
                            Data tidak ditemukan
                        </td>
                    </tr>
                `;
                return;
            }

            data.forEach(rk => {

                tbody.innerHTML += `
                    <tr class="border-b hover:bg-gray-50">

                        <td>${rk.iku?.name ?? '-'}</td>

                        <td>${rk.description}</td>

                        <td>
                            <span class="bg-blue-100 text-blue-600 px-2 py-1 rounded text-xs">
                                ${rk.project_count} project
                            </span>
                        </td>

                        <td class="space-x-2">
                            <a href="/admin/project?rk_ketua_id=${rk.id}" class="text-blue-500">View</a>
                            <button class="text-yellow-500">Edit</button>
                            <button class="text-red-500">Delete</button>
                        </td>

                    </tr>
                `;
            });

        });

    }, 400);

});
</script>

</x-app-layout>