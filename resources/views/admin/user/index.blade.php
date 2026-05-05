<x-app-layout>

<div class="bg-white p-6 rounded-xl shadow">

    <!-- HEADER -->
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold">Manajemen User</h2>

        <div class="flex gap-2">
            <a href="{{ route('iku.index') }}"
                class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">
                Refresh
            </a>

            <button onclick="openCreateModal()"
                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg shadow">
                + Add
            </button>
        </div>
    </div>

    <!-- 🔥 ALERT -->
    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    <!-- FILTER -->
    <form method="GET" class="flex gap-3 mb-5">

        <input type="text" name="search"
    value="{{ request('search') }}"
    onkeyup="liveSearchHandler(event)"
    placeholder="Cari nama / email / NIP..."
    class="border px-3 py-2 rounded w-full">

        <select name="role"
    onchange="this.form.submit()"class="border px-3 py-2 rounded">
            <option value="">Role</option>
            <option value="admin">Admin</option>
            <option value="ketua_tim">Ketua</option>
            <option value="anggota">Anggota</option>
        </select>

        <button class="bg-gray-800 text-white px-4 rounded">
            Filter
        </button>

    </form>

    <!-- TABLE -->
    <div class="overflow-x-auto">
    <table class="w-full text-sm border">

       <thead class="bg-gray-100">
<tr class="text-left">

    <!-- NAMA -->
    <th class="p-2 text-center">
        <a href="{{ request()->fullUrlWithQuery([
            'sort' => 'name',
            'direction' => request('direction') == 'asc' ? 'desc' : 'asc'
        ]) }}">
            Nama
            @if(request('sort') == 'name')
                {{ request('direction') == 'asc' ? '↑' : '↓' }}
            @endif
        </a>
    </th>

    <!-- NIP -->
    <th class="p-2 text-center">
        <a href="{{ request()->fullUrlWithQuery([
            'sort' => 'nip',
            'direction' => request('direction') == 'asc' ? 'desc' : 'asc'
        ]) }}">
            NIP
            @if(request('sort') == 'nip')
                {{ request('direction') == 'asc' ? '↑' : '↓' }}
            @endif
        </a>
    </th>

    <!-- EMAIL -->
    <th class="p-2 text-center">
        <a href="{{ request()->fullUrlWithQuery([
            'sort' => 'email',
            'direction' => request('direction') == 'asc' ? 'desc' : 'asc'
        ]) }}">
            Email
            @if(request('sort') == 'email')
                {{ request('direction') == 'asc' ? '↑' : '↓' }}
            @endif
        </a>
    </th>

    <!-- ROLE -->
    <th class="p-2 text-center">
        <a href="{{ request()->fullUrlWithQuery([
            'sort' => 'role',
            'direction' => request('direction') == 'asc' ? 'desc' : 'asc'
        ]) }}">
            Role
            @if(request('sort') == 'role')
                {{ request('direction') == 'asc' ? '↑' : '↓' }}
            @endif
        </a>
    </th>

    <!-- TEAM -->
    <th class="p-2 text-center">
        Team
    </th>

    <!-- AKSI -->
    <th class="p-2 text-center">Aksi</th>

</tr>
</thead>

        <tbody>
        @forelse($users as $user)
        <tr class="border-b hover:bg-gray-50">

            <td class="p-2 font-medium">
                {{ $user->name }}
            </td>

            <td class="p-2 text-gray-600">
                {{ $user->nip ?? '-' }}
            </td>

            <td class="p-2">{{ $user->email }}</td>

            <td class="p-2">
                <span class="px-2 py-1 rounded text-xs
                    @if($user->role=='admin') bg-red-100 text-red-600
                    @elseif($user->role=='ketua_tim') bg-blue-100 text-blue-600
                    @else bg-green-100 text-green-600
                    @endif">
                    {{ $user->role }}
                </span>
            </td>

            <td class="p-2">{{ $user->team->name ?? '-' }}</td>

            <td class="p-2 text-center space-x-2">

                <button onclick='openEditModal(@json($user))'
                    class="text-yellow-500 hover:underline">
                    Edit
                </button>

                <form action="{{ route('admin.users.destroy', $user->id) }}"
                    method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button class="text-red-500 hover:underline"
                        onclick="return confirm('Hapus user?')">
                        Delete
                    </button>
                </form>

            </td>

        </tr>
        @empty
        <tr>
            <td colspan="7" class="text-center py-4 text-gray-400">
                Tidak ada user
            </td>
        </tr>
        @endforelse
        </tbody>

    </table>
    </div>

    <!-- 🔥 PAGINATION -->
<div class="mt-4 flex justify-center">
    {{ $users->withQueryString()->links() }}
</div>

</div>


<!-- ================= IMPORT MODAL ================= -->
<div id="modalImport" class="hidden fixed inset-0 bg-black/40 flex justify-center items-center z-50">

<div class="bg-white p-6 rounded-xl w-[400px] shadow">

<h2 class="font-bold mb-4 text-lg">Import User (Excel)</h2>

<form method="POST" action="{{ route('admin.users.import') }}" enctype="multipart/form-data">
@csrf

<input type="file" name="file"
class="border w-full mb-4 p-2 rounded" required>

<p class="text-xs text-gray-500 mb-4">
Format: name | nip | email
</p>

<div class="flex justify-end gap-2">
<button type="button"
onclick="closeImportModal()"
class="bg-gray-300 px-3 py-1 rounded">
Cancel
</button>

<button type="submit"
class="bg-blue-600 text-white px-3 py-1 rounded">
Import
</button>
</div>

</form>

</div>
</div>


<!-- ================= CREATE MODAL ================= -->
<div id="modalCreate" class="hidden fixed inset-0 bg-black/40 flex justify-center items-center z-50">

<div class="bg-white p-6 rounded-xl w-[400px] shadow">

<h2 class="font-bold mb-4 text-lg">Tambah User</h2>

<form method="POST" action="{{ route('admin.users.store') }}">
@csrf

<input name="name" placeholder="Nama"
class="border w-full mb-2 p-2 rounded">

<input name="nip" placeholder="NIP (18 digit)"
class="border w-full mb-2 p-2 rounded">

<input name="email" placeholder="Email"
class="border w-full mb-2 p-2 rounded">

<input type="password" name="password" placeholder="Password"
class="border w-full mb-2 p-2 rounded">

<select name="role" class="border w-full mb-2 p-2 rounded">
<option value="admin">Admin</option>
<option value="ketua_tim">Ketua</option>
<option value="anggota">Anggota</option>
</select>

<select name="team_id" class="border w-full mb-4 p-2 rounded">
<option value="">Pilih Team</option>
@foreach($teams as $team)
<option value="{{ $team->id }}">{{ $team->name }}</option>
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
<div id="modalEdit" class="hidden fixed inset-0 bg-black/40 flex justify-center items-center z-50">

<div class="bg-white p-6 rounded-xl w-[400px] shadow">

<h2 class="font-bold mb-4 text-lg">Edit User</h2>

<form method="POST" id="formEdit">
@csrf
@method('PUT')

<input id="editName" name="name" class="border w-full mb-2 p-2 rounded">

<input id="editNip" name="nip" class="border w-full mb-2 p-2 rounded">

<input id="editEmail" name="email" class="border w-full mb-2 p-2 rounded">

<select id="editRole" name="role" class="border w-full mb-2 p-2 rounded">
<option value="admin">Admin</option>
<option value="ketua">Ketua</option>
<option value="anggota">Anggota</option>
</select>

<select id="editTeam" name="team_id" class="border w-full mb-4 p-2 rounded">
@foreach($teams as $team)
<option value="{{ $team->id }}">{{ $team->name }}</option>
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

function openImportModal(){
    document.getElementById('modalImport').classList.remove('hidden');
}

function closeImportModal(){
    document.getElementById('modalImport').classList.add('hidden');
}

function openCreateModal(){
    document.getElementById('modalCreate').classList.remove('hidden');
}

function closeCreateModal(){
    document.getElementById('modalCreate').classList.add('hidden');
}

function openEditModal(data){
    document.getElementById('modalEdit').classList.remove('hidden');

    document.getElementById('editName').value = data.name;
    document.getElementById('editNip').value = data.nip ?? '';
    document.getElementById('editEmail').value = data.email;
    document.getElementById('editRole').value = data.role;
    document.getElementById('editTeam').value = data.team_id;

    document.getElementById('formEdit').action = `/admin/users/${data.id}`;
}

function closeEditModal(){
    document.getElementById('modalEdit').classList.add('hidden');
}

</script>

<script>
let searchTimer = null;

function liveSearchHandler(e) {

    // jangan trigger enter
    if (e.key === "Enter") return;

    // ✅ FIX: cek modal aktif (pakai hidden)
    if (!document.getElementById('modalCreate').classList.contains('hidden') ||
        !document.getElementById('modalEdit').classList.contains('hidden') ||
        !document.getElementById('modalImport').classList.contains('hidden')) {
        return;
    }

    clearTimeout(searchTimer);

    searchTimer = setTimeout(() => {
        e.target.form.submit();
    }, 500);
}
</script>

</x-app-layout>