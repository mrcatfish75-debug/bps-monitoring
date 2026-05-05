<x-app-layout>

<div class="bg-white p-6 rounded-xl shadow">

    <!-- HEADER -->
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold">Indikator Kinerja Utama (IKU)</h2>

        <div class="flex gap-2">
            <!-- REFRESH -->
            <a href="{{ route('iku.index') }}"
                class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">
                Refresh
            </a>
            
            <!-- ADD -->
            <button onclick="openCreateModal()"
                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow">
                + Add
            </button>
        </div>
    </div>

    <!-- FILTER + SEARCH -->
    <form method="GET" class="grid grid-cols-4 gap-3 mb-5">

        <!-- YEAR -->
        <select name="year" class="border px-3 py-2 rounded">
            @for($y = date('Y'); $y >= 2020; $y--)
                <option value="{{ $y }}" {{ request('year',$year) == $y ? 'selected' : '' }}>
                    {{ $y }}
                </option>
            @endfor
        </select>

        <!-- SEARCH -->
        <input type="text" id="searchInput"
    placeholder="Cari IKU..."
    class="border px-3 py-2 rounded">

        <!-- EMPTY -->
        <div></div>

        <!-- BUTTON -->
        <button type="button" class="bg-gray-800 text-white px-4 rounded">
            Filter
        </button>

    </form>

    <!-- TABLE -->
    <div class="overflow-x-auto">
    <table class="w-full text-sm border">

        <thead class="bg-gray-100">
            <tr class="text-left">
                <th class="p-2">IKU</th>
                <th class="p-2">Tahun</th>
                <th class="p-2">Satuan</th>
                <th class="p-2">Target</th>
                <th class="p-2">Progress</th>
                <th class="p-2 text-center">Aksi</th>
            </tr>
        </thead>

        <tbody id="ikuTableBody">
        @forelse($ikus as $iku)
        <tr class="border-b hover:bg-gray-50">

            <td class="p-2 font-medium">
                {{ $iku->name }}
            </td>

            <td class="p-2">
                {{ $iku->year }}
            </td>

            <td class="p-2">
                {{ $iku->satuan }}
            </td>

            <td class="p-2">
                {{ $iku->target }}
            </td>

            <!-- PROGRESS -->
            <td class="p-2">
                <div class="w-full bg-gray-200 rounded h-2">
                    <div class="bg-green-500 h-2 rounded"
                        style="width: {{ $iku->progress }}%">
                    </div>
                </div>
                <small class="text-gray-600">{{ $iku->progress }}%</small>
            </td>

            <!-- AKSI -->
            <td class="p-2 text-center space-x-2">

                <button
                    onclick='openEditModal(@json($iku))'
                    class="text-yellow-500 hover:underline">
                    Edit
                </button>

                <form method="POST"
                    action="{{ url('/admin/iku/'.$iku->id) }}"
                    class="inline"
                    onsubmit="return confirm('Yakin hapus IKU ini?')">
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
            <td colspan="6" class="text-center py-4 text-gray-400">
                Data IKU tidak ditemukan
            </td>
        </tr>
        @endforelse
        </tbody>

    </table>
    </div>
    
<!-- 🔥 PAGINATION -->
<div class="mt-4 flex justify-center">
    {{ $ikus->withQueryString()->links() }}
</div>

</div>


<!-- ================= CREATE MODAL ================= -->
<div id="modalCreate"
class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50">

<div class="bg-white p-6 rounded-xl w-[400px] shadow">

<h3 class="text-lg font-semibold mb-4">Tambah IKU</h3>

<form method="POST" action="{{ route('iku.store') }}">
@csrf

<input name="name" placeholder="Nama IKU"
class="border w-full mb-2 p-2 rounded" required>

<input name="year" placeholder="Tahun" value="{{ $year }}"
class="border w-full mb-2 p-2 rounded" required>

<input name="satuan" placeholder="Satuan (%, unit, dll)"
class="border w-full mb-2 p-2 rounded" required>

<input name="target" placeholder="Target"
class="border w-full mb-4 p-2 rounded" required>

<div class="flex justify-end gap-2">
<button type="button"
onclick="closeModal('modalCreate')"
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
class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50">

<div class="bg-white p-6 rounded-xl w-[400px] shadow">

<h3 class="text-lg font-semibold mb-4">Edit IKU</h3>

<form method="POST" id="formEdit">
@csrf
@method('PUT')

<input id="edit_name" name="name"
class="border w-full mb-2 p-2 rounded" required>

<input id="edit_year" name="year"
class="border w-full mb-2 p-2 rounded" required>

<input id="edit_satuan" name="satuan"
class="border w-full mb-2 p-2 rounded" required>

<input id="edit_target" name="target"
class="border w-full mb-4 p-2 rounded" required>

<div class="flex justify-end gap-2">
<button type="button"
onclick="closeModal('modalEdit')"
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

<!-- ================= IMPORT MODAL ================= -->
<div id="modalImport"
    class="hidden fixed inset-0 bg-black/40 flex items-center justify-center">

    <div class="bg-white p-6 rounded w-96 shadow">

        <h3 class="text-lg font-semibold mb-3">Import IKU</h3>

        <form method="POST" action="{{ route('iku.import') }}" enctype="multipart/form-data">
            @csrf

            <input type="file" name="file"
                class="border w-full mb-3 p-2 rounded" required>

            <p class="text-xs text-gray-500 mb-3">
                Format: name | year | satuan | target
            </p>

            <div class="flex justify-end gap-2">
                <button type="button"
                    onclick="closeModal('modalImport')"
                    class="px-3 py-1 bg-gray-300 rounded">
                    Cancel
                </button>

                <button class="bg-blue-600 text-white px-3 py-1 rounded">
                    Import
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

    document.getElementById('edit_name').value = data.name;
    document.getElementById('edit_year').value = data.year;
    document.getElementById('edit_satuan').value = data.satuan;
    document.getElementById('edit_target').value = data.target;

    document.getElementById('formEdit').action = `/admin/iku/${data.id}`;
}

function closeModal(id){
    document.getElementById(id).classList.add('hidden');
}

function openImportModal(){
    document.getElementById('modalImport').classList.remove('hidden');
}
</script>

<script>
let timer;

document.getElementById('searchInput').addEventListener('keyup', function() {

    clearTimeout(timer);

    let keyword = this.value;

    timer = setTimeout(() => {

        fetch(`/iku/search?search=${keyword}`)
        .then(res => res.json())
        .then(data => {

            let tbody = document.getElementById('ikuTableBody');
            tbody.innerHTML = '';

            if (data.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-4 text-gray-400">
                            Data IKU tidak ditemukan
                        </td>
                    </tr>
                `;
                return;
            }

            data.forEach(iku => {

                tbody.innerHTML += `
                    <tr class="border-b hover:bg-gray-50">
                        <td class="p-2 font-medium">${iku.name}</td>
                        <td class="p-2">${iku.year}</td>
                        <td class="p-2">${iku.satuan}</td>
                        <td class="p-2">${iku.target}</td>
                        <td class="p-2">-</td>
                        <td class="p-2 text-center">-</td>
                    </tr>
                `;

            });

        });

    }, 400);

});
</script>

</x-app-layout>