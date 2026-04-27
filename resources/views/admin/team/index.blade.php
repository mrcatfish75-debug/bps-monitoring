<x-app-layout>

<div class="bg-white p-6 rounded-xl shadow">

    <!-- HEADER -->
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold">Manajemen Tim</h2>

        <div class="flex gap-2">
            <!-- REFRESH -->
            <a href="{{ route('admin.team.index') }}"
                class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">
                Refresh
            </a>

            <!-- ADD -->
            <button onclick="openCreateModal()"
                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg shadow">
                + Add
            </button>
        </div>
    </div>

    <!-- FILTER + SEARCH -->
    <form method="GET" class="grid grid-cols-3 gap-3 mb-5">

        <!-- SEARCH -->
      <input type="text" name="search"
    value="{{ request('search') }}"
    onkeyup="liveSearchHandler(event)"
    placeholder="Cari nama tim..."
    class="border px-3 py-2 rounded w-full">

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
                <th class="p-2">ID</th>
                <th class="p-2">Nama Tim</th>
                <th class="p-2">Ketua</th>
                <th class="p-2">Jumlah Anggota</th>
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
                    {{ $team->members->count() }} orang
                </span>
            </td>

            <td class="p-2 text-center space-x-2">

                <button onclick='openEditModal(@json($team))'
                    class="text-yellow-500 hover:underline">
                    Edit
                </button>

                <form method="POST"
                    action="{{ route('admin.team.destroy',$team->id) }}"
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

<input name="name"
placeholder="Nama Tim"
class="border w-full mb-3 p-2 rounded">

<select name="leader_id"
class="border w-full mb-3 p-2 rounded">
<option value="">Pilih Ketua Tim</option>

@foreach($users as $u)
@if($u->role != 'admin')
<option value="{{ $u->id }}">
{{ $u->name }} ({{ $u->nip ?? '-' }})
</option>
@endif
@endforeach

</select>

<label class="text-sm font-semibold">Anggota Tim</label>

<select name="members[]" multiple
class="border w-full mb-4 p-2 rounded h-32">

@foreach($users as $u)
@if($u->role != 'admin')
<option value="{{ $u->id }}">
{{ $u->name }} ({{ $u->nip ?? '-' }})
</option>
@endif
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

<input id="editName"
name="name"
class="border w-full mb-3 p-2 rounded">

<select id="editLeader"
name="leader_id"
class="border w-full mb-3 p-2 rounded">
@foreach($users as $u)
@if($u->role != 'admin')
<option value="{{ $u->id }}">
{{ $u->name }}
</option>
@endif
@endforeach
</select>

<label class="text-sm font-semibold">Anggota Tim</label>

<select id="editMembers" name="members[]" multiple
class="border w-full mb-4 p-2 rounded h-32">
@foreach($users as $u)
@if($u->role != 'admin')
<option value="{{ $u->id }}">
{{ $u->name }}
</option>
@endif
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

    document.getElementById('editName').value = data.name;
    document.getElementById('editLeader').value = data.leader_id;

    let memberSelect = document.getElementById('editMembers');

    Array.from(memberSelect.options).forEach(opt => opt.selected = false);

    if (data.members) {
        data.members.forEach(m => {
            let option = memberSelect.querySelector(`option[value="${m.id}"]`);
            if(option) option.selected = true;
        });
    }

    document.getElementById('formEdit').action = `/admin/team/${data.id}`;
}

function closeEditModal(){
    document.getElementById('modalEdit').classList.add('hidden');
}

</script>

<script>
let searchTimer = null;

function liveSearchHandler(e) {

    if (e.key === "Enter") return;

    // ❌ kalau input kosong → jangan submit
    if (e.target.value.trim() === "") return;

    // ✅ cek modal aktif
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