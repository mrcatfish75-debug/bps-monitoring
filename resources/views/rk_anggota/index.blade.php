<x-app-layout>

@php
    $authUser = auth()->user();
    $role = $authUser->role;

    $basePath = match($role) {
        'admin' => '/admin/rk-anggota',
        'anggota' => '/anggota/rk-anggota',
        'ketua' => '/ketua/rk-anggota',
        default => '/rk-anggota',
    };

    $projectBasePath = match($role) {
        'admin' => '/admin/project',
        'ketua' => '/ketua/project',
        'anggota' => '/anggota/project',
        default => '/project',
    };

    /*
    |--------------------------------------------------------------------------
    | Mode Context
    |--------------------------------------------------------------------------
    | /ketua/rk-anggota
    |   = mode ketua untuk review RK anggota dari project yang dia pimpin.
    |
    | /ketua/rk-anggota?mode=mine
    |   = mode pekerjaan saya, hanya RK milik ketua sendiri.
    */

    $isMineMode = $role === 'ketua' && request('mode') === 'mine';

    /*
    |--------------------------------------------------------------------------
    | Personal Mode
    |--------------------------------------------------------------------------
    | Anggota dan ketua mode mine adalah konteks pekerjaan pribadi.
    */

    $isPersonalMode = $role === 'anggota' || $isMineMode;

    /*
    |--------------------------------------------------------------------------
    | Manage Permission
    |--------------------------------------------------------------------------
    | Admin:
    | - bisa mengelola semua RK Anggota.
    |
    | Anggota:
    | - bisa mengelola RK miliknya sendiri.
    |
    | Ketua mode mine:
    | - bisa mengelola RK miliknya sendiri.
    |
    | Ketua mode normal:
    | - hanya review/approve/reject RK anggota project yang dia pimpin.
    */

    $canManageRkAnggota = $role === 'admin' || $isPersonalMode;

    $resetUrl = $isMineMode
        ? url()->current() . '?mode=mine'
        : url()->current();
@endphp

<div class="bg-white p-6 rounded-xl shadow">

    <!-- ================= HEADER ================= -->
    <div class="flex justify-between items-center mb-4">
    <div>
        <h2 class="text-xl font-bold">
            {{ $isPersonalMode ? 'RK Pribadi Saya' : 'Rencana Kinerja - Anggota' }}
        </h2>

        @if($isPersonalMode)
            <p class="text-sm text-gray-500 mt-1">
                Menampilkan RK Anggota milikmu sendiri sebagai pelaksana project.
            </p>
        @elseif($role === 'ketua')
            <p class="text-sm text-gray-500 mt-1">
                Menampilkan RK Anggota dari project yang kamu pimpin.
            </p>
        @endif
    </div>

    <div class="flex gap-2">
        <a href="{{ $resetUrl }}" class="px-4 py-2 bg-gray-200 rounded">
            Refresh
        </a>

       @if($canManageRkAnggota && (!$isPersonalMode || $projects->count() > 0))
            <button type="button"
                onclick="openCreateModal()"
                class="bg-green-600 text-white px-4 py-2 rounded">
                + Add
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
<form id="rkAnggotaFilterForm"
    method="GET"
    onsubmit="return false;"
    class="grid grid-cols-1 md:grid-cols-{{ ($role === 'admin' || ($role === 'ketua' && !$isMineMode)) ? '4' : '3' }} gap-2 mb-4">

    @if($isMineMode)
        <input type="hidden" name="mode" value="mine">
    @endif

    <!-- PROJECT -->
    <select name="project_id"
        id="projectFilter"
        class="rk-filter border px-3 py-2 rounded">
        <option value="">Semua Project</option>

        @foreach($projects as $p)
            <option value="{{ $p->id }}"
                {{ request('project_id') == $p->id ? 'selected' : '' }}>
                {{ $p->name }}
            </option>
        @endforeach
    </select>

    <!-- USER -->
    @if($role === 'admin' || ($role === 'ketua' && !$isMineMode))
        <select name="user_id"
            id="userFilter"
            class="rk-filter border px-3 py-2 rounded">
            <option value="">Semua Anggota</option>

            @foreach($users as $u)
                <option value="{{ $u->id }}"
                    {{ request('user_id') == $u->id ? 'selected' : '' }}>
                    {{ $u->name }}
                </option>
            @endforeach
        </select>
    @endif

    <!-- SEARCH -->
    <input type="text"
        id="rkSearchInput"
        name="search"
        value="{{ request('search') }}"
        placeholder="Cari project, anggota, atau rencana kinerja..."
        autocomplete="off"
        class="border px-3 py-2 rounded">

    <!-- RESET -->
    <a href="{{ $resetUrl }}"
        class="bg-gray-200 text-gray-700 text-center px-4 py-2 rounded hover:bg-gray-300">
        Reset
    </a>

</form>

<div id="rkSearchInfo"
    class="hidden mb-4 p-3 rounded bg-blue-50 text-blue-700 text-sm">
</div>

   <!-- ================= TABLE ================= -->
<div id="rkTableWrapper" class="transition-opacity duration-150">
<table class="w-full text-sm">

    <thead class="bg-gray-50 border-b">
        <tr>
            <th class="text-left p-2">Project</th>
            <th class="text-left p-2">Tim</th>

            @if(!$isPersonalMode)
                <th class="text-left p-2">Anggota</th>
            @endif

            <th class="text-left p-2">Rencana Kinerja</th>
            <th class="text-left p-2">Status</th>
            <th class="text-left p-2">Progress</th>
            <th class="text-left p-2">Aksi</th>
        </tr>
    </thead>

    <tbody id="rkAnggotaTableBody">
    @forelse($rkAnggotas as $rk)

        @php
            $statusClass = match($rk->status) {
                'draft' => 'bg-gray-100 text-gray-700',
                'submitted' => 'bg-blue-100 text-blue-700',
                'approved' => 'bg-green-100 text-green-700',
                'rejected' => 'bg-red-100 text-red-700',
                default => 'bg-gray-100 text-gray-700',
            };

            $statusLabel = match($rk->status) {
                'draft' => 'Draft',
                'submitted' => 'Submitted',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
                default => ucfirst($rk->status),
            };

            $isOwner = (int) $rk->user_id === (int) auth()->id();
            $isProjectLeader = optional($rk->project)->leader_id === auth()->id();

            $canEditDelete = $rk->isEditable()
                && (
                    $role === 'admin'
                    || ($canManageRkAnggota && $isOwner)
                );

            $canSubmit = $rk->canSubmit()
                && $rk->dailyTasks->count() > 0
                && (
                    $role === 'admin'
                    || $isOwner
                );

            $canReview = $rk->canBeReviewed()
                && !$isOwner
                && (
                    $role === 'admin'
                    || ($role === 'ketua' && !$isMineMode && $isProjectLeader)
                );

            $submitAction = match($role) {
                'admin' => route('admin.rk-anggota.submit', $rk->id),
                'ketua' => route('ketua.rk-anggota.submit', $rk->id),
                default => route('anggota.rk-anggota.submit', $rk->id),
            };

            $approveAction = $role === 'admin'
                ? route('admin.rk-anggota.approve', $rk->id)
                : route('ketua.rk-anggota.approve', $rk->id);

            $rejectAction = $role === 'admin'
                ? route('admin.rk-anggota.reject', $rk->id)
                : route('ketua.rk-anggota.reject', $rk->id);
        @endphp

        <tr class="border-b hover:bg-gray-50">

            <td class="p-2">{{ $rk->project->name ?? '-' }}</td>

            <td class="p-2">{{ $rk->project->team->name ?? '-' }}</td>

            @if(!$isPersonalMode)
                <td class="p-2">{{ $rk->user->name ?? '-' }}</td>
            @endif

            <td class="p-2">
                {{ $rk->description }}

                @if($rk->status === 'rejected' && $rk->rejection_note)
                    <div class="mt-1 text-xs text-red-600">
                        <b>Catatan:</b> {{ $rk->rejection_note }}
                    </div>
                @endif
            </td>

            <!-- STATUS -->
            <td class="p-2">
                <span class="px-2 py-1 rounded text-xs font-semibold {{ $statusClass }}">
                    {{ $statusLabel }}
                </span>

                @if($rk->submitted_at)
                    <div class="text-xs text-gray-500 mt-1">
                        Submit: {{ $rk->submitted_at->format('d M Y H:i') }}
                    </div>
                @endif

                @if($rk->approved_at)
                    <div class="text-xs text-gray-500 mt-1">
                        Approved: {{ $rk->approved_at->format('d M Y H:i') }}
                    </div>
                @endif
            </td>

            <!-- PROGRESS -->
            <td class="p-2">
                <div class="w-full bg-gray-200 h-2 rounded">
                    <div class="bg-green-500 h-2 rounded"
                        style="width: {{ $rk->progress }}%">
                    </div>
                </div>
                <small>{{ $rk->progress }}%</small>
            </td>

            <!-- AKSI -->
            <td class="p-2">
                <div class="flex flex-wrap gap-2">

                    <!-- VIEW -->
                    <button type="button"
                        onclick="openViewModal({{ $rk->id }})"
                        class="text-blue-500">
                        View
                    </button>

                    <!-- EDIT -->
                    @if($canEditDelete)
                        <button type="button"
                            onclick="openEditModal({{ $rk->id }})"
                            class="text-yellow-500">
                            Edit
                        </button>
                    @endif

                    <!-- DELETE -->
                    @if($canEditDelete)
                        <form method="POST"
                            action="{{ url($basePath . '/' . $rk->id) }}"
                            class="inline"
                            onsubmit="return confirm('Yakin ingin menghapus RK Anggota ini?')">
                            @csrf
                            @method('DELETE')

                            <button class="text-red-500">
                                Delete
                            </button>
                        </form>
                    @endif

                    <!-- SUBMIT -->
                    @if($canSubmit)
                        <form method="POST"
                            action="{{ $submitAction }}"
                            class="inline"
                            onsubmit="return confirm('Submit RK Anggota ini untuk review ketua?')">
                            @csrf
                            @method('PATCH')

                            <button class="text-purple-600 font-semibold">
                                Submit
                            </button>
                        </form>
                    @endif

                    @if($rk->canSubmit() && $isOwner && $rk->dailyTasks->count() === 0)
                        <span class="text-xs text-gray-400">
                            Tambahkan Daily Task dulu
                        </span>
                    @endif

                    <!-- APPROVE -->
                    @if($canReview)
                        <form method="POST"
                            action="{{ $approveAction }}"
                            class="inline"
                            onsubmit="return confirm('Setujui RK Anggota ini?')">
                            @csrf
                            @method('PATCH')

                            <button class="text-green-600 font-semibold">
                                Approve
                            </button>
                        </form>
                    @endif

                    <!-- REJECT -->
                    @if($canReview)
                        <button type="button"
                            onclick="openRejectModal({{ $rk->id }}, '{{ $rejectAction }}')"
                            class="text-red-600 font-semibold">
                            Reject
                        </button>
                    @endif

                </div>
            </td>

        </tr>
    @empty
        <tr>
            <td colspan="{{ $isPersonalMode ? 6 : 7 }}" class="text-center py-4">
                Tidak ada data
            </td>
        </tr>
    @endforelse
    </tbody>

</table>
</div>

<div id="rkPagination" class="mt-4">
    {{ $rkAnggotas->withQueryString()->links() }}
</div>

<!-- ================= VIEW MODAL ================= -->
<div id="modalView"
    class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50">

    <div class="bg-white rounded-2xl w-[760px] max-h-[85vh] overflow-y-auto shadow-xl">

        <div class="px-6 py-4 border-b flex justify-between items-start">
            <div>
                <h3 class="text-xl font-bold text-gray-900">
                    Detail RK Anggota
                </h3>
                <p class="text-sm text-gray-500 mt-1">
                    Detail rencana kerja, status review, dan Daily Task pendukung.
                </p>
            </div>

            <button type="button"
                onclick="closeModal('modalView')"
                class="text-gray-400 hover:text-red-500 text-2xl leading-none">
                ×
            </button>
        </div>

        <div id="viewContent" class="p-6"></div>

        <div class="flex justify-end px-6 py-4 border-t">
            <button type="button"
                onclick="closeModal('modalView')"
                class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg">
                Close
            </button>
        </div>

    </div>
</div>


<!-- ================= CREATE MODAL ================= -->
@if($canManageRkAnggota)
<div id="modalCreate"
    class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50">

    <div class="bg-white rounded-2xl w-[680px] max-h-[90vh] overflow-y-auto shadow-xl">

        <!-- HEADER -->
        <div class="px-6 py-4 border-b flex justify-between items-start">
            <div>
                <h3 class="text-xl font-bold text-gray-900">
                    {{ $isMineMode || $role === 'anggota' ? 'Tambah RK Pribadi' : 'Tambah RK Anggota' }}
                </h3>

                <p class="text-sm text-gray-500 mt-1">
                    @if($isMineMode || $role === 'anggota')
                        Buat rencana kerja pribadi berdasarkan project yang kamu ikuti.
                    @else
                        Buat rencana kerja anggota untuk project tertentu.
                    @endif
                </p>
            </div>

            <button type="button"
                onclick="closeModal('modalCreate')"
                class="text-gray-400 hover:text-red-500 text-2xl leading-none">
                ×
            </button>
        </div>

        <form method="POST" action="{{ url($basePath) }}" class="p-6">
            @csrf

            @if($isMineMode)
                <input type="hidden" name="mode" value="mine">
            @endif

            <!-- PROJECT -->
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Project
                </label>

                <select id="create_project"
                    name="project_id"
                    required
                    class="border w-full p-3 rounded-lg bg-white focus:ring focus:ring-green-100 focus:border-green-500">
                    <option value="">Pilih Project</option>

                    @foreach($projects as $p)
                        <option value="{{ $p->id }}">
                            {{ $p->name }}
                            @if($p->rkKetua && $p->rkKetua->iku)
                                - {{ $p->rkKetua->iku->name }}
                            @endif
                        </option>
                    @endforeach
                </select>

                <p class="text-xs text-gray-400 mt-1">
                    @if($isMineMode || $role === 'anggota')
                        Project yang muncul adalah project tempat kamu menjadi anggota.
                    @elseif($role === 'admin')
                        Pilih project, lalu pilih anggota dari daftar member project.
                    @else
                        Pilih project yang sesuai dengan RK anggota.
                    @endif
                </p>
            </div>

            <!-- USER -->
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Anggota
                </label>

                @if($role === 'admin')
                    <select id="create_user"
                        name="user_id"
                        required
                        class="border w-full p-3 rounded-lg bg-white focus:ring focus:ring-green-100 focus:border-green-500">
                        <option value="">Pilih project terlebih dahulu</option>
                    </select>

                    <p class="text-xs text-gray-400 mt-1">
                        Anggota diambil dari daftar member project yang dipilih.
                    </p>
                @else
                    <input type="hidden" name="user_id" value="{{ auth()->id() }}">

                    <div class="border w-full p-3 rounded-lg bg-gray-100 text-gray-700">
                        <div class="font-medium">
                            {{ auth()->user()->name }}
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                            {{ $isPersonalMode ? 'Pelaksana' : ucfirst(auth()->user()->role) }} - otomatis sebagai pemilik RK
                        </div>
                    </div>
                @endif
            </div>

            <!-- DESKRIPSI -->
            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Rencana Kinerja
                </label>

                <textarea name="description"
                    required
                    rows="5"
                    placeholder="Tulis rencana kerja yang akan dikerjakan..."
                    class="border w-full p-3 rounded-lg focus:ring focus:ring-green-100 focus:border-green-500"></textarea>

                <p class="text-xs text-gray-400 mt-1">
                    Contoh: Menyusun bahan, mengolah data, melakukan validasi, atau menyiapkan output pekerjaan.
                </p>
            </div>

            <!-- INFO -->
            <div class="mb-5 p-4 rounded-xl bg-blue-50 border border-blue-100 text-sm text-blue-700">
                @if($isMineMode || $role === 'anggota')
                    Kamu bisa membuat lebih dari satu RK pribadi dalam project yang sama. Daily Task nantinya dibuat untuk menyelesaikan RK ini.
                @elseif($role === 'admin')
                    Pastikan anggota yang dipilih memang termasuk dalam project tersebut.
                @else
                    RK Anggota akan masuk sebagai draft dan bisa disubmit setelah memiliki minimal satu Daily Task.
                @endif
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
@if($canManageRkAnggota)
<div id="modalEdit"
    class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50">

    <div class="bg-white rounded-2xl w-[680px] max-h-[90vh] overflow-y-auto shadow-xl">

        <!-- HEADER -->
        <div class="px-6 py-4 border-b flex justify-between items-start">
            <div>
                <h3 class="text-xl font-bold text-gray-900">
                    Edit RK Anggota
                </h3>
                <p class="text-sm text-gray-500 mt-1">
                    Perubahan hanya bisa dilakukan selama RK masih Draft atau Rejected.
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

            @if($isMineMode)
                <input type="hidden" name="mode" value="mine">
            @endif

            <!-- PROJECT -->
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Project
                </label>

                <select id="edit_project"
                    name="project_id"
                    required
                    class="border w-full p-3 rounded-lg bg-white focus:ring focus:ring-yellow-100 focus:border-yellow-500">
                    @foreach($projects as $p)
                        <option value="{{ $p->id }}">
                            {{ $p->name }}
                            @if($p->rkKetua && $p->rkKetua->iku)
                                - {{ $p->rkKetua->iku->name }}
                            @endif
                        </option>
                    @endforeach
                </select>

                <p class="text-xs text-gray-400 mt-1">
                    Project harus tetap sesuai dengan daftar project yang bisa kamu akses.
                </p>
            </div>

            <!-- USER -->
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Anggota
                </label>

                @if($role === 'admin')
                    <select id="edit_user"
                        name="user_id"
                        required
                        class="border w-full p-3 rounded-lg bg-white focus:ring focus:ring-yellow-100 focus:border-yellow-500">
                    </select>

                    <p class="text-xs text-gray-400 mt-1">
                        Anggota diambil dari daftar member project.
                    </p>
                @else
                    <input type="hidden" id="edit_user_hidden" name="user_id" value="{{ auth()->id() }}">

                    <div class="border w-full p-3 rounded-lg bg-gray-100 text-gray-700">
                        <div class="font-medium">
                            {{ auth()->user()->name }}
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                            {{ ucfirst(auth()->user()->role) }} - otomatis sebagai pemilik RK
                        </div>
                    </div>
                @endif
            </div>

            <!-- DESKRIPSI -->
            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Rencana Kinerja
                </label>

                <textarea id="edit_description"
                    name="description"
                    required
                    rows="5"
                    placeholder="Tulis rencana kerja yang akan dikerjakan..."
                    class="border w-full p-3 rounded-lg focus:ring focus:ring-yellow-100 focus:border-yellow-500"></textarea>
            </div>

            <!-- INFO -->
            <div class="mb-5 p-4 rounded-xl bg-yellow-50 border border-yellow-100 text-sm text-yellow-700">
                RK Anggota yang sudah Submitted atau Approved tidak bisa diedit. Jika RK ditolak, kamu bisa memperbaiki lalu submit ulang.
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

<!-- ================= REJECT MODAL ================= -->
@if($role === 'admin' || ($role === 'ketua' && !$isMineMode))
<div id="modalReject"
    class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50">

    <div class="bg-white p-6 rounded w-[420px] shadow">

        <h3 class="text-lg font-semibold mb-3">Tolak RK Anggota</h3>

        <form method="POST" id="formReject">
            @csrf
            @method('PATCH')

            <label class="block text-sm font-medium mb-1">
                Alasan Penolakan
            </label>

            <textarea name="rejection_note"
                required
                maxlength="1000"
                placeholder="Tulis alasan penolakan..."
                class="border w-full mb-4 p-2 rounded"></textarea>

            <div class="flex justify-end gap-2">
                <button type="button"
                    onclick="closeModal('modalReject')"
                    class="px-3 py-1 bg-gray-300 rounded">
                    Cancel
                </button>

                <button class="bg-red-600 text-white px-4 py-1 rounded">
                    Reject
                </button>
            </div>

        </form>

    </div>
</div>
@endif


<!-- ================= SCRIPT ================= -->
<script>
const RK_ANGGOTA_BASE_PATH = @json($basePath);
const PROJECT_BASE_PATH = @json($projectBasePath);
const IS_ADMIN = @json($role === 'admin');

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

function closeModal(id){
    const modal = document.getElementById(id);

    if (modal) {
        modal.classList.add('hidden');
    }
}

function openRejectModal(id, actionUrl){
    const form = document.getElementById('formReject');
    const modal = document.getElementById('modalReject');

    if (!form || !modal) {
        return;
    }

    form.action = actionUrl;
    modal.classList.remove('hidden');
}

/*
|--------------------------------------------------------------------------
| Project Members Loader
|--------------------------------------------------------------------------
| RK Anggota harus dibuat untuk user yang masuk project_members.
| Jadi dropdown user harus diisi dari members project yang dipilih.
|--------------------------------------------------------------------------
*/

function loadProjectMembers(projectId, selectId, selectedUserId = null) {
    const select = document.getElementById(selectId);

    if (!select) {
        return;
    }

    select.innerHTML = '<option value="">Loading...</option>';

    if (!projectId) {
        select.innerHTML = '<option value="">Pilih Anggota</option>';
        return;
    }

    fetch(`${PROJECT_BASE_PATH}/${projectId}`)
        .then(res => {
            if (!res.ok) {
                throw new Error('Gagal mengambil data project');
            }

            return res.json();
        })
        .then(data => {
            select.innerHTML = '<option value="">Pilih Anggota</option>';

            if (!data.members || data.members.length === 0) {
                select.innerHTML = '<option value="">Tidak ada anggota project</option>';
                return;
            }

            data.members.forEach(member => {
                const selected = selectedUserId && member.id == selectedUserId
                    ? 'selected'
                    : '';

                select.innerHTML += `
                    <option value="${member.id}" ${selected}>
                        ${member.name}
                    </option>
                `;
            });
        })
        .catch(error => {
            console.error(error);
            select.innerHTML = '<option value="">Gagal mengambil anggota project</option>';
        });
}

/*
|--------------------------------------------------------------------------
| Edit Modal
|--------------------------------------------------------------------------
*/

function openEditModal(id){
    fetch(`${RK_ANGGOTA_BASE_PATH}/${id}`)
        .then(res => {
            if (!res.ok) {
                throw new Error('Gagal mengambil detail RK Anggota');
            }

            return res.json();
        })
        .then(data => {
            const modal = document.getElementById('modalEdit');
            const form = document.getElementById('formEdit');
            const editProject = document.getElementById('edit_project');
            const editDescription = document.getElementById('edit_description');

            if (!modal || !form || !editProject || !editDescription) {
                return;
            }

            modal.classList.remove('hidden');

            form.action = `${RK_ANGGOTA_BASE_PATH}/${data.id}`;

            editProject.value = data.project_id;
            editDescription.value = data.description ?? '';

            if (IS_ADMIN) {
    loadProjectMembers(data.project_id, 'edit_user', data.user_id);
}
        })
        .catch(error => {
            console.error(error);
            alert('Gagal membuka edit RK Anggota.');
        });
}

/*
|--------------------------------------------------------------------------
| View Modal
|--------------------------------------------------------------------------
*/

function openViewModal(id){
    fetch(`${RK_ANGGOTA_BASE_PATH}/${id}`)
        .then(res => {
            if (!res.ok) {
                throw new Error('Gagal mengambil detail RK Anggota');
            }

            return res.json();
        })
        .then(data => {
            let dailyTasksHtml = '';

            if (data.daily_tasks && data.daily_tasks.length > 0) {
                dailyTasksHtml = `
                    <div class="mt-5">
                        <h4 class="font-semibold mb-2">Daily Task / Bukti Proses</h4>

                        <div class="border rounded-xl overflow-hidden">
                            ${data.daily_tasks.map(task => {
                                const evidenceHtml = task.evidence_url
                                    ? `<a class="text-blue-600 underline" href="${task.evidence_url}" target="_blank">Buka Link</a>`
                                    : '<span class="text-gray-400">-</span>';

                                return `
                                    <div class="p-4 border-b last:border-b-0 bg-white">
                                        <div class="flex justify-between gap-4 mb-2">
                                            <div class="font-medium text-gray-800">
                                                ${task.activity ?? '-'}
                                            </div>
                                            <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded shrink-0">
                                                ${task.date ?? '-'}
                                            </span>
                                        </div>

                                        <div class="text-sm text-gray-600">
                                            <b>Link Bukti:</b> ${evidenceHtml}
                                        </div>

                                        ${task.created_at ? `
                                            <div class="text-xs text-gray-400 mt-2">
                                                Dibuat: ${formatDate(task.created_at)}
                                            </div>
                                        ` : ''}
                                    </div>
                                `;
                            }).join('')}
                        </div>
                    </div>
                `;
            } else {
                dailyTasksHtml = `
                    <div class="mt-5 p-4 bg-yellow-50 text-yellow-700 rounded-xl border border-yellow-100">
                        Belum ada Daily Task untuk RK Anggota ini.
                    </div>
                `;
            }

            let rejectionHtml = '';

            if (data.status === 'rejected' && data.rejection_note) {
                rejectionHtml = `
                    <div class="p-3 rounded-xl bg-red-50 text-red-700 border border-red-100">
                        <b>Catatan Penolakan:</b> ${data.rejection_note}
                    </div>
                `;
            }

            let approverHtml = '';

            if (data.approver) {
                approverHtml = `
                    <p><b>Approved By:</b> ${data.approver.name}</p>
                `;
            }

            const viewContent = document.getElementById('viewContent');
            const modalView = document.getElementById('modalView');

            if (!viewContent || !modalView) {
                return;
            }

            viewContent.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <div class="p-4 rounded-xl bg-gray-50 border">
                        <div class="text-xs text-gray-500 mb-1">IKU</div>
                        <div class="font-semibold">
                            ${data.project?.rk_ketua?.iku?.name ?? '-'}
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-gray-50 border">
                        <div class="text-xs text-gray-500 mb-1">Project</div>
                        <div class="font-semibold">
                            ${data.project?.name ?? '-'}
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-gray-50 border">
                        <div class="text-xs text-gray-500 mb-1">Tim</div>
                        <div class="font-semibold">
                            ${data.project?.team?.name ?? '-'}
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-gray-50 border">
                        <div class="text-xs text-gray-500 mb-1">Anggota</div>
                        <div class="font-semibold">
                            ${data.user?.name ?? '-'}
                        </div>
                    </div>

                </div>

                <div class="mt-4 p-4 rounded-xl border">
                    <div class="text-xs text-gray-500 mb-1">Rencana Kinerja</div>
                    <div class="font-medium text-gray-800">
                        ${data.description ?? '-'}
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-4 rounded-xl border">
                        <p><b>Status:</b> ${data.status ?? '-'}</p>
                        ${data.submitted_at ? `<p><b>Submitted At:</b> ${formatDate(data.submitted_at)}</p>` : ''}
                        ${data.approved_at ? `<p><b>Approved At:</b> ${formatDate(data.approved_at)}</p>` : ''}
                        ${approverHtml}
                    </div>

                    <div class="p-4 rounded-xl border">
                        <p><b>Total Daily Task:</b> ${data.daily_tasks ? data.daily_tasks.length : 0}</p>
                        <p><b>Progress:</b> ${data.progress ?? 0}%</p>
                    </div>
                </div>

                <div class="mt-4">
                    ${rejectionHtml}
                </div>

                ${dailyTasksHtml}
            `;

            modalView.classList.remove('hidden');
        })
        .catch(error => {
            console.error(error);
            alert('Gagal mengambil detail RK Anggota.');
        });
}

/*
|--------------------------------------------------------------------------
| Date Formatter
|--------------------------------------------------------------------------
*/

function formatDate(dateString){
    let date = new Date(dateString);

    if (isNaN(date)) {
        return dateString;
    }

    return date.toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}


/*
|--------------------------------------------------------------------------
| Instant AJAX Filter
|--------------------------------------------------------------------------
| Tidak butuh route search baru.
| Fetch halaman yang sama, lalu ambil ulang tbody dan pagination dari HTML hasil.
|--------------------------------------------------------------------------
*/
let rkSearchTimer = null;
let lastRkSearchUrl = '';

function getRkFilterParams() {
    const form = document.getElementById('rkAnggotaFilterForm');
    const params = new URLSearchParams();

    if (!form) {
        return params;
    }

    const formData = new FormData(form);

    formData.forEach((value, key) => {
        if (value !== null && String(value).trim() !== '') {
            params.append(key, value);
        }
    });

    return params;
}

function setRkSearchInfo(message = '', type = 'info') {
    const info = document.getElementById('rkSearchInfo');

    if (!info) {
        return;
    }

    if (!message) {
        info.classList.add('hidden');
        info.innerHTML = '';
        return;
    }

    const classMap = {
        info: 'mb-4 p-3 rounded bg-blue-50 text-blue-700 text-sm',
        success: 'mb-4 p-3 rounded bg-green-50 text-green-700 text-sm',
        warning: 'mb-4 p-3 rounded bg-yellow-50 text-yellow-700 text-sm',
        error: 'mb-4 p-3 rounded bg-red-50 text-red-700 text-sm',
    };

    info.className = classMap[type] ?? classMap.info;
    info.innerHTML = message;
}

function countRkRows() {
    const tbody = document.getElementById('rkAnggotaTableBody');

    if (!tbody) {
        return 0;
    }

    return tbody.querySelectorAll('tr.border-b').length;
}

function updateBrowserUrl(url) {
    if (window.history && window.history.replaceState) {
        window.history.replaceState({}, '', url);
    }
}

function fetchRkAnggotaList(url) {
    const tableWrapper = document.getElementById('rkTableWrapper');

    if (tableWrapper) {
        tableWrapper.classList.add('opacity-60');
    }

    fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'text/html',
        },
    })
        .then(res => {
            if (!res.ok) {
                throw new Error('Gagal memuat data RK Anggota.');
            }

            return res.text();
        })
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            const newTbody = doc.querySelector('#rkAnggotaTableBody');
            const currentTbody = document.getElementById('rkAnggotaTableBody');

            const newPagination = doc.querySelector('#rkPagination');
            const currentPagination = document.getElementById('rkPagination');

            if (!newTbody || !currentTbody) {
                throw new Error('Target tabel RK Anggota tidak ditemukan.');
            }

            currentTbody.innerHTML = newTbody.innerHTML;

            if (newPagination && currentPagination) {
                currentPagination.innerHTML = newPagination.innerHTML;
            }

            updateBrowserUrl(url);

            const totalRows = countRkRows();

            if (totalRows === 0) {
                setRkSearchInfo('Tidak ada RK Anggota yang sesuai dengan filter/search.', 'warning');
            } else {
                setRkSearchInfo(`Menampilkan ${totalRows} data RK Anggota sesuai filter/search.`, 'success');
            }

            bindRkPaginationLinks();
        })
        .catch(error => {
            console.error('RK ANGGOTA AJAX FILTER ERROR:', error);
            setRkSearchInfo('Gagal memuat data RK Anggota. Cek filter atau controller index.', 'error');
        })
        .finally(() => {
            if (tableWrapper) {
                tableWrapper.classList.remove('opacity-60');
            }
        });
}

function runRkInstantSearch() {
    const params = getRkFilterParams();
    const queryString = params.toString();

    const url = queryString
        ? `${window.location.pathname}?${queryString}`
        : window.location.pathname;

    if (url === lastRkSearchUrl) {
        return;
    }

    lastRkSearchUrl = url;

    fetchRkAnggotaList(url);
}

function triggerRkSearch() {
    clearTimeout(rkSearchTimer);

    rkSearchTimer = setTimeout(() => {
        runRkInstantSearch();
    }, 300);
}

function bindRkPaginationLinks() {
    const pagination = document.getElementById('rkPagination');

    if (!pagination) {
        return;
    }

    pagination.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', function (event) {
            event.preventDefault();

            const url = this.getAttribute('href');

            if (!url) {
                return;
            }

            fetchRkAnggotaList(url);
        });
    });
}

document.addEventListener('DOMContentLoaded', function () {
    const filterForm = document.getElementById('rkAnggotaFilterForm');
    const searchInput = document.getElementById('rkSearchInput');
    const filterInputs = document.querySelectorAll('.rk-filter');

    if (filterForm) {
        filterForm.addEventListener('submit', function (event) {
            event.preventDefault();
            triggerRkSearch();
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', triggerRkSearch);
    }

    filterInputs.forEach(input => {
        input.addEventListener('change', triggerRkSearch);
    });

    bindRkPaginationLinks();
});


/*
|--------------------------------------------------------------------------
| Event Binding
|--------------------------------------------------------------------------
*/

document.addEventListener('DOMContentLoaded', function(){

    const createProject = document.getElementById('create_project');

   if (createProject && IS_ADMIN) {
    createProject.addEventListener('change', function(){
        loadProjectMembers(this.value, 'create_user');
    });
}

    const editProject = document.getElementById('edit_project');

   if (editProject && IS_ADMIN) {
    editProject.addEventListener('change', function(){
        loadProjectMembers(this.value, 'edit_user');
    });
}

});

</script>

</x-app-layout>