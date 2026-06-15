<x-app-layout>

@php
    $authUser = auth()->user();

    $roleOptions = [
        'admin' => 'Admin',
        'kepala' => 'Kepala',
        'ketua' => 'Ketua',
        'anggota' => 'Anggota',
    ];
@endphp

<div class="bg-white p-4 sm:p-6 rounded-xl shadow">

    <!-- ================= HEADER ================= -->
    <div class="flex flex-col gap-3 md:flex-row md:justify-between md:items-start mb-6">
        <div>
            <h2 class="text-xl font-bold text-gray-900">
                Manajemen User
            </h2>

            <p class="text-sm text-gray-500 mt-1">
                Kelola akun, role, tim, dan reset password sementara user.
            </p>
        </div>

        <div class="flex flex-col sm:flex-row gap-2 w-full md:w-auto">
            <a href="{{ route('admin.users.index') }}"
                class="w-full sm:w-auto text-center px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300 text-sm font-medium">
                Refresh
            </a>

            <button type="button"
                onclick="openImportModal()"
                class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow text-sm font-semibold">
                Import
            </button>

            <button type="button"
                onclick="openCreateModal()"
                class="w-full sm:w-auto bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg shadow text-sm font-semibold">
                + Add User
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

    @if(session('temporary_password'))
        <div class="mb-4 rounded-xl border border-yellow-200 bg-yellow-50 p-4">
            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div>
                    <h3 class="font-bold text-yellow-800">
                        Password Sementara
                    </h3>

                    <p class="text-sm text-yellow-700 mt-1">
                        Password ini hanya ditampilkan sekali. Berikan ke user, lalu user wajib mengganti password setelah login.
                    </p>

                    <div class="mt-3 text-sm text-yellow-900">
                        <div><b>User:</b> {{ session('temporary_password_user') }}</div>
                        <div><b>Email:</b> {{ session('temporary_password_email') }}</div>
                    </div>

                    <div class="mt-3 flex flex-col sm:flex-row sm:items-center gap-2">
                        <code id="temporaryPasswordValue"
                            class="rounded-lg bg-white border border-yellow-200 px-3 py-2 font-mono text-lg font-bold text-yellow-900 break-all">
                            {{ session('temporary_password') }}
                        </code>

                        <button type="button"
                            onclick="copyTemporaryPassword()"
                            class="rounded-lg bg-yellow-600 px-3 py-2 text-sm font-semibold text-white hover:bg-yellow-700">
                            Copy
                        </button>
                    </div>
                </div>

                <div class="rounded-lg bg-white/70 px-3 py-2 text-xs text-yellow-700">
                    Jika Admin lupa menyalin password ini, lakukan Reset Password ulang.
                </div>
            </div>
        </div>
    @endif

    @if(session('temporary_passwords') && count(session('temporary_passwords')) > 0)
        <div class="mb-4 rounded-xl border border-yellow-200 bg-yellow-50 p-4">
            <h3 class="font-bold text-yellow-800">
                Password Sementara Hasil Import
            </h3>

            <p class="text-sm text-yellow-700 mt-1">
                Password hanya ditampilkan sekali. Salin dan berikan ke masing-masing user.
            </p>

            @if(session('import_skipped'))
                <p class="text-xs text-yellow-700 mt-2">
                    Data dilewati: {{ session('import_skipped') }}
                </p>
            @endif

            <div class="mt-3 overflow-x-auto rounded-xl border border-yellow-200">
                <table class="w-full text-xs bg-white min-w-[620px]">
                    <thead class="bg-yellow-100 text-yellow-900">
                        <tr>
                            <th class="p-2 text-left">Nama</th>
                            <th class="p-2 text-left">Email</th>
                            <th class="p-2 text-left">Password Sementara</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(session('temporary_passwords') as $tempUser)
                            <tr class="border-t">
                                <td class="p-2">{{ $tempUser['name'] }}</td>
                                <td class="p-2">{{ $tempUser['email'] }}</td>
                                <td class="p-2 font-mono font-bold">{{ $tempUser['password'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- ================= FILTER ================= -->
    <form method="GET" class="mb-5">

        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">

            <div class="md:col-span-7">
                <input type="text"
                    name="search"
                    value="{{ request('search') }}"
                    onkeyup="liveSearchHandler(event)"
                    placeholder="Cari nama / email / NIP..."
                    autocomplete="off"
                    class="border px-3 py-2 rounded-lg w-full focus:ring focus:ring-green-100 focus:border-green-500">
            </div>

            <div class="md:col-span-3">
                <select name="role"
                    onchange="this.form.submit()"
                    class="border px-3 py-2 rounded-lg w-full bg-white focus:ring focus:ring-green-100 focus:border-green-500">
                    <option value="">Semua Role</option>

                    @foreach($roleOptions as $value => $label)
                        <option value="{{ $value }}" {{ request('role') === $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit"
                class="md:col-span-1 bg-gray-800 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">
                Filter
            </button>

            <a href="{{ route('admin.users.index') }}"
                class="md:col-span-1 bg-gray-200 hover:bg-gray-300 text-center px-4 py-2 rounded-lg">
                Reset
            </a>

        </div>
    </form>

    <!-- ================= DESKTOP TABLE ================= -->
    <div class="hidden md:block overflow-x-auto rounded-xl border">
        <table class="w-full text-sm min-w-[980px]">

            <thead class="bg-gray-50 border-b">
                <tr class="text-left">

                    <!-- NAMA -->
                    <th class="p-3">
                        <a href="{{ request()->fullUrlWithQuery([
                            'sort' => 'name',
                            'direction' => request('direction') == 'asc' ? 'desc' : 'asc'
                        ]) }}" class="hover:underline">
                            Nama
                            @if(request('sort') == 'name')
                                {{ request('direction') == 'asc' ? '↑' : '↓' }}
                            @endif
                        </a>
                    </th>

                    <!-- NIP -->
                    <th class="p-3">
                        <a href="{{ request()->fullUrlWithQuery([
                            'sort' => 'nip',
                            'direction' => request('direction') == 'asc' ? 'desc' : 'asc'
                        ]) }}" class="hover:underline">
                            NIP
                            @if(request('sort') == 'nip')
                                {{ request('direction') == 'asc' ? '↑' : '↓' }}
                            @endif
                        </a>
                    </th>

                    <!-- EMAIL -->
                    <th class="p-3">
                        <a href="{{ request()->fullUrlWithQuery([
                            'sort' => 'email',
                            'direction' => request('direction') == 'asc' ? 'desc' : 'asc'
                        ]) }}" class="hover:underline">
                            Email
                            @if(request('sort') == 'email')
                                {{ request('direction') == 'asc' ? '↑' : '↓' }}
                            @endif
                        </a>
                    </th>

                    <!-- ROLE -->
                    <th class="p-3">
                        <a href="{{ request()->fullUrlWithQuery([
                            'sort' => 'role',
                            'direction' => request('direction') == 'asc' ? 'desc' : 'asc'
                        ]) }}" class="hover:underline">
                            Role
                            @if(request('sort') == 'role')
                                {{ request('direction') == 'asc' ? '↑' : '↓' }}
                            @endif
                        </a>
                    </th>

                    <th class="p-3">Team</th>
                    <th class="p-3 text-center w-[120px]">Aksi</th>

                </tr>
            </thead>

            <tbody>
            @forelse($users as $user)
                @php
                    $roleLabel = match($user->role) {
                        'admin' => 'Admin',
                        'kepala' => 'Kepala',
                        'ketua', 'ketua_tim' => 'Ketua',
                        'anggota' => 'Anggota',
                        default => ucfirst($user->role),
                    };

                    $roleClass = match($user->role) {
                        'admin' => 'bg-red-100 text-red-700',
                        'kepala' => 'bg-purple-100 text-purple-700',
                        'ketua', 'ketua_tim' => 'bg-blue-100 text-blue-700',
                        'anggota' => 'bg-green-100 text-green-700',
                        default => 'bg-gray-100 text-gray-700',
                    };

                    $isSelf = (int) $authUser->id === (int) $user->id;
                @endphp

                <tr class="border-b last:border-b-0 hover:bg-gray-50">

                    <td class="p-3">
                        <div class="font-semibold text-gray-900">
                            {{ $user->name }}
                        </div>
                    </td>

                    <td class="p-3 text-gray-600">
                        {{ $user->nip ?? '-' }}
                    </td>

                    <td class="p-3">
                        <span class="break-all">
                            {{ $user->email }}
                        </span>
                    </td>

                    <td class="p-3">
                        <span class="px-2 py-1 rounded text-xs font-semibold {{ $roleClass }}">
                            {{ $roleLabel }}
                        </span>

                        @if($user->is_default_password)
                            <div class="mt-1">
                                <span class="px-2 py-0.5 rounded text-[11px] bg-yellow-100 text-yellow-700 font-semibold">
                                    Wajib Ganti Password
                                </span>
                            </div>
                        @endif
                    </td>

                    <td class="p-3">
                        {{ $user->team->name ?? '-' }}
                    </td>

                    <td class="p-3 text-center relative">
                        <div class="relative inline-block text-left">

                            <button type="button"
                                data-user-action-button
                                onclick="toggleUserActionMenu('user-action-menu-desktop-{{ $user->id }}', event)"
                                class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                                Aksi
                                <span class="text-xs">▾</span>
                            </button>

                            <div id="user-action-menu-desktop-{{ $user->id }}"
                                data-user-action-menu
                                class="hidden absolute right-0 top-full z-50 mt-2 w-48 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl">

                                <button type="button"
                                    data-user-id="{{ $user->id }}"
                                    data-user-name="{{ $user->name }}"
                                    data-user-nip="{{ $user->nip }}"
                                    data-user-email="{{ $user->email }}"
                                    data-user-role="{{ $user->role }}"
                                    data-user-team-id="{{ $user->team_id }}"
                                    onclick="openEditModalFromButton(this)"
                                    class="block w-full px-4 py-2 text-left text-sm text-yellow-600 hover:bg-yellow-50">
                                    Edit
                                </button>

                                @if(!$isSelf)
                                    <form action="{{ route('admin.users.reset', $user->id) }}"
                                        method="POST"
                                        onsubmit="return confirm('Reset password user ini? Password sementara baru akan dibuat dan user wajib mengganti password saat login.')">
                                        @csrf

                                        <button type="submit"
                                            class="block w-full px-4 py-2 text-left text-sm text-blue-600 hover:bg-blue-50">
                                            Reset Password
                                        </button>
                                    </form>

                                    <form action="{{ route('admin.users.destroy', $user->id) }}"
                                        method="POST"
                                        onsubmit="return confirm('Hapus user?')">
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
                    <td colspan="6" class="text-center py-5 text-gray-400">
                        Tidak ada user
                    </td>
                </tr>
            @endforelse
            </tbody>

        </table>
    </div>

    <!-- ================= MOBILE CARDS ================= -->
    <div class="md:hidden space-y-3">
        @forelse($users as $user)
            @php
                $roleLabel = match($user->role) {
                    'admin' => 'Admin',
                    'kepala' => 'Kepala',
                    'ketua', 'ketua_tim' => 'Ketua',
                    'anggota' => 'Anggota',
                    default => ucfirst($user->role),
                };

                $roleClass = match($user->role) {
                    'admin' => 'bg-red-100 text-red-700',
                    'kepala' => 'bg-purple-100 text-purple-700',
                    'ketua', 'ketua_tim' => 'bg-blue-100 text-blue-700',
                    'anggota' => 'bg-green-100 text-green-700',
                    default => 'bg-gray-100 text-gray-700',
                };

                $isSelf = (int) $authUser->id === (int) $user->id;
            @endphp

            <div class="rounded-xl border bg-white p-4 shadow-sm">

                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="font-semibold text-gray-900 break-words">
                            {{ $user->name }}
                        </h3>

                        <p class="text-sm text-gray-500 mt-1 break-all">
                            {{ $user->email }}
                        </p>

                        <p class="text-xs text-gray-400 mt-1">
                            NIP: {{ $user->nip ?? '-' }}
                        </p>
                    </div>

                    <div class="relative shrink-0">
                        <button type="button"
                            data-user-action-button
                            onclick="toggleUserActionMenu('user-action-menu-mobile-{{ $user->id }}', event)"
                            class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                            Aksi
                            <span class="text-xs">▾</span>
                        </button>

                        <div id="user-action-menu-mobile-{{ $user->id }}"
                            data-user-action-menu
                            class="hidden absolute right-0 top-full z-50 mt-2 w-48 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl">

                            <button type="button"
                                data-user-id="{{ $user->id }}"
                                data-user-name="{{ $user->name }}"
                                data-user-nip="{{ $user->nip }}"
                                data-user-email="{{ $user->email }}"
                                data-user-role="{{ $user->role }}"
                                data-user-team-id="{{ $user->team_id }}"
                                onclick="openEditModalFromButton(this)"
                                class="block w-full px-4 py-2 text-left text-sm text-yellow-600 hover:bg-yellow-50">
                                Edit
                            </button>

                            @if(!$isSelf)
                                <form action="{{ route('admin.users.reset', $user->id) }}"
                                    method="POST"
                                    onsubmit="return confirm('Reset password user ini? Password sementara baru akan dibuat dan user wajib mengganti password saat login.')">
                                    @csrf

                                    <button type="submit"
                                        class="block w-full px-4 py-2 text-left text-sm text-blue-600 hover:bg-blue-50">
                                        Reset Password
                                    </button>
                                </form>

                                <form action="{{ route('admin.users.destroy', $user->id) }}"
                                    method="POST"
                                    onsubmit="return confirm('Hapus user?')">
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

                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="px-2 py-1 rounded text-xs font-semibold {{ $roleClass }}">
                        {{ $roleLabel }}
                    </span>

                    @if($user->is_default_password)
                        <span class="px-2 py-1 rounded text-xs bg-yellow-100 text-yellow-700 font-semibold">
                            Wajib Ganti Password
                        </span>
                    @endif

                    <span class="px-2 py-1 rounded text-xs bg-gray-100 text-gray-700 font-semibold">
                        Team: {{ $user->team->name ?? '-' }}
                    </span>
                </div>

            </div>
        @empty
            <div class="rounded-xl border p-5 text-center text-gray-400">
                Tidak ada user
            </div>
        @endforelse
    </div>

    <!-- ================= PAGINATION ================= -->
    <div class="mt-4 flex justify-center">
        {{ $users->withQueryString()->links() }}
    </div>

</div>

<!-- ================= IMPORT MODAL ================= -->
<div id="modalImport"
    class="hidden fixed inset-0 bg-black/40 flex justify-center items-center z-50 px-4">

    <div class="bg-white rounded-2xl w-full sm:w-[520px] max-h-[90vh] overflow-y-auto shadow-xl">

        <!-- HEADER -->
        <div class="px-5 sm:px-6 py-4 border-b flex justify-between items-start">
            <div>
                <h3 class="text-xl font-bold text-gray-900">
                    Import User
                </h3>

                <p class="text-sm text-gray-500 mt-1">
                    Upload file Excel untuk membuat user secara massal.
                </p>
            </div>

            <button type="button"
                onclick="closeImportModal()"
                class="text-gray-400 hover:text-red-500 text-2xl leading-none">
                ×
            </button>
        </div>

        <form method="POST"
            action="{{ route('admin.users.import') }}"
            enctype="multipart/form-data"
            class="p-5 sm:p-6">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    File Excel
                </label>

                <input type="file"
                    name="file"
                    class="border w-full p-3 rounded-lg bg-white"
                    required>

                <div class="mt-2 rounded-lg bg-yellow-50 border border-yellow-100 p-3 text-xs text-yellow-700">
                <div class="font-semibold mb-1">
                    Format file Excel wajib:
                </div>

                <div class="font-mono bg-white border border-yellow-100 rounded px-2 py-1 text-yellow-900">
                    name | nip | email
                </div>

                <p class="mt-2">
                    Pastikan baris pertama/header menggunakan nama kolom tersebut. Kolom <b>name</b> dan <b>email</b> wajib diisi, sedangkan <b>nip</b> dapat dikosongkan jika belum tersedia.
                </p>
            </div>

            <div class="mb-5 p-4 rounded-xl bg-blue-50 border border-blue-100 text-sm text-blue-700">
                Password sementara akan dibuat otomatis untuk user hasil import dan ditampilkan sekali setelah proses selesai.
            </div>

            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-4 border-t">
                <button type="button"
                    onclick="closeImportModal()"
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

<!-- ================= CREATE MODAL ================= -->
<div id="modalCreate"
    class="hidden fixed inset-0 bg-black/40 flex justify-center items-center z-50 px-4">

    <div class="bg-white rounded-2xl w-full sm:w-[560px] max-h-[90vh] overflow-y-auto shadow-xl">

        <!-- HEADER -->
        <div class="px-5 sm:px-6 py-4 border-b flex justify-between items-start">
            <div>
                <h3 class="text-xl font-bold text-gray-900">
                    Tambah User
                </h3>

                <p class="text-sm text-gray-500 mt-1">
                    Buat akun baru dan tentukan role serta tim user.
                </p>
            </div>

            <button type="button"
                onclick="closeCreateModal()"
                class="text-gray-400 hover:text-red-500 text-2xl leading-none">
                ×
            </button>
        </div>

        <form method="POST" action="{{ route('admin.users.store') }}" class="p-5 sm:p-6">
            @csrf

            <!-- NAMA -->
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Nama
                </label>

                <input name="name"
                    value="{{ old('name') }}"
                    placeholder="Nama lengkap"
                    class="border w-full p-3 rounded-lg focus:ring focus:ring-green-100 focus:border-green-500"
                    required>
            </div>

            <!-- NIP -->
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    NIP
                </label>

                <input name="nip"
                    value="{{ old('nip') }}"
                    placeholder="NIP 18 digit"
                    class="border w-full p-3 rounded-lg focus:ring focus:ring-green-100 focus:border-green-500">
            </div>

            <!-- EMAIL -->
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Email
                </label>

                <input name="email"
                    value="{{ old('email') }}"
                    placeholder="Email"
                    class="border w-full p-3 rounded-lg focus:ring focus:ring-green-100 focus:border-green-500"
                    required>
            </div>

            <div class="mb-4 rounded-xl bg-blue-50 border border-blue-100 p-4 text-sm text-blue-700">
                Password sementara akan dibuat otomatis oleh sistem dan ditampilkan sekali setelah user dibuat.
            </div>

            <!-- ROLE -->
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Role
                </label>

                <select name="role"
                    class="border w-full p-3 rounded-lg bg-white focus:ring focus:ring-green-100 focus:border-green-500"
                    required>
                    @foreach($roleOptions as $value => $label)
                        <option value="{{ $value }}" {{ old('role', 'anggota') === $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- TEAM -->
            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Team
                </label>

                <select name="team_id"
                    class="border w-full p-3 rounded-lg bg-white focus:ring focus:ring-green-100 focus:border-green-500">
                    <option value="">Pilih Team</option>

                    @foreach($teams as $team)
                        <option value="{{ $team->id }}" {{ old('team_id') == $team->id ? 'selected' : '' }}>
                            {{ $team->name }}
                        </option>
                    @endforeach
                </select>
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
                    Edit User
                </h3>

                <p class="text-sm text-gray-500 mt-1">
                    Perbarui data akun, role, dan tim user.
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

            <!-- NAMA -->
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Nama
                </label>

                <input id="editName"
                    name="name"
                    placeholder="Nama lengkap"
                    class="border w-full p-3 rounded-lg focus:ring focus:ring-yellow-100 focus:border-yellow-500"
                    required>
            </div>

            <!-- NIP -->
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    NIP
                </label>

                <input id="editNip"
                    name="nip"
                    placeholder="NIP"
                    class="border w-full p-3 rounded-lg focus:ring focus:ring-yellow-100 focus:border-yellow-500">
            </div>

            <!-- EMAIL -->
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Email
                </label>

                <input id="editEmail"
                    name="email"
                    placeholder="Email"
                    class="border w-full p-3 rounded-lg focus:ring focus:ring-yellow-100 focus:border-yellow-500"
                    required>
            </div>

            <!-- ROLE -->
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Role
                </label>

                <select id="editRole"
                    name="role"
                    class="border w-full p-3 rounded-lg bg-white focus:ring focus:ring-yellow-100 focus:border-yellow-500"
                    required>
                    @foreach($roleOptions as $value => $label)
                        <option value="{{ $value }}">
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- TEAM -->
            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Team
                </label>

                <select id="editTeam"
                    name="team_id"
                    class="border w-full p-3 rounded-lg bg-white focus:ring focus:ring-yellow-100 focus:border-yellow-500">
                    <option value="">Pilih Team</option>

                    @foreach($teams as $team)
                        <option value="{{ $team->id }}">
                            {{ $team->name }}
                        </option>
                    @endforeach
                </select>
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

<!-- ================= SCRIPT ================= -->
<script>
/*
|--------------------------------------------------------------------------
| Temporary Password
|--------------------------------------------------------------------------
*/

function copyTemporaryPassword(){
    const passwordElement = document.getElementById('temporaryPasswordValue');

    if (!passwordElement) {
        return;
    }

    const password = passwordElement.innerText.trim();

    if (navigator.clipboard) {
        navigator.clipboard.writeText(password)
            .then(() => alert('Password sementara berhasil disalin.'))
            .catch(() => alert('Gagal menyalin password. Salin manual.'));
        return;
    }

    alert('Browser tidak mendukung copy otomatis. Salin password secara manual.');
}

/*
|--------------------------------------------------------------------------
| Row Action Dropdown
|--------------------------------------------------------------------------
*/

function closeAllUserActionMenus() {
    document.querySelectorAll('[data-user-action-menu]').forEach(menu => {
        menu.classList.add('hidden');
    });
}

function toggleUserActionMenu(menuId, event = null) {
    if (event) {
        event.stopPropagation();
    }

    const targetMenu = document.getElementById(menuId);

    if (!targetMenu) {
        return;
    }

    const isCurrentlyHidden = targetMenu.classList.contains('hidden');

    closeAllUserActionMenus();

    if (isCurrentlyHidden) {
        targetMenu.classList.remove('hidden');
    }
}

document.addEventListener('click', function (event) {
    const clickedButton = event.target.closest('[data-user-action-button]');
    const clickedMenu = event.target.closest('[data-user-action-menu]');

    if (!clickedButton && !clickedMenu) {
        closeAllUserActionMenus();
    }
});

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        closeAllUserActionMenus();
    }
});

/*
|--------------------------------------------------------------------------
| Modal Helpers
|--------------------------------------------------------------------------
*/

function openImportModal(){
    const modal = document.getElementById('modalImport');

    if (modal) {
        modal.classList.remove('hidden');
    }
}

function closeImportModal(){
    const modal = document.getElementById('modalImport');

    if (modal) {
        modal.classList.add('hidden');
    }
}

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
    closeAllUserActionMenus();

    openEditModal({
        id: button.dataset.userId,
        name: button.dataset.userName,
        nip: button.dataset.userNip,
        email: button.dataset.userEmail,
        role: button.dataset.userRole,
        team_id: button.dataset.userTeamId,
    });
}

function openEditModal(data){
    const modal = document.getElementById('modalEdit');
    const editName = document.getElementById('editName');
    const editNip = document.getElementById('editNip');
    const editEmail = document.getElementById('editEmail');
    const editRole = document.getElementById('editRole');
    const editTeam = document.getElementById('editTeam');
    const formEdit = document.getElementById('formEdit');

    if (!modal || !editName || !editNip || !editEmail || !editRole || !editTeam || !formEdit) {
        return;
    }

    modal.classList.remove('hidden');

    editName.value = data.name ?? '';
    editNip.value = data.nip ?? '';
    editEmail.value = data.email ?? '';
    editRole.value = data.role ?? 'anggota';
    editTeam.value = data.team_id ?? '';

    formEdit.action = `/admin/users/${data.id}`;
}

function closeEditModal(){
    const modal = document.getElementById('modalEdit');

    if (modal) {
        modal.classList.add('hidden');
    }
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
    const importModal = document.getElementById('modalImport');

    if (
        (createModal && !createModal.classList.contains('hidden')) ||
        (editModal && !editModal.classList.contains('hidden')) ||
        (importModal && !importModal.classList.contains('hidden'))
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
