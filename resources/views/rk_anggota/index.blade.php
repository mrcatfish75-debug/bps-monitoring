<x-app-layout>

@php
    $authUser = auth()->user();
    $role = $authUser->role;

    $basePath = match($role) {
        'admin' => '/admin/rk-anggota',
        'anggota' => '/anggota/rk-anggota',
        'ketua' => '/ketua/rk-anggota',
        'kepala' => '/kepala/rk-anggota',
        default => '/rk-anggota',
    };

    $projectBasePath = match($role) {
        'admin' => '/admin/project',
        'ketua' => '/ketua/project',
        'anggota' => '/anggota/project',
        'kepala' => '/kepala/project',
        default => '/project',
    };

    $isMineMode = $role === 'ketua' && request('mode') === 'mine';

    $isPersonalMode = $role === 'anggota' || $isMineMode;

    $isKepala = $role === 'kepala';

    $canManageRkAnggota = !$isKepala && ($role === 'admin' || $isPersonalMode);

   $resetUrl = $isMineMode
    ? url()->current() . '?mode=mine'
    : url()->current();

$rkTemplateOptions = ($rkTemplates ?? collect())
    ->map(function ($template) {
        return [
            'description' => $template->description ?? '',
            'category' => $template->category ?? null,
            'category_label' => $template->category_label ?? ($template->category ?? 'RK Anggota'),
        ];
    })
    ->filter(fn ($template) => trim((string) $template['description']) !== '')
    ->values();
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
            @elseif($role === 'kepala')
                <p class="text-sm text-gray-500 mt-1">
                    Mode monitoring. Menampilkan seluruh RK Anggota lintas project, tim, dan IKU tanpa aksi tambah, edit, hapus, submit, approve, atau reject.
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
                    + Add RK
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
        class="mb-4">

        @if($isMineMode)
            <input type="hidden" name="mode" value="mine">
        @endif

        <div class="flex flex-col gap-2 xl:flex-row xl:items-center">

            <!-- FILTER INPUTS -->
            <div class="grid flex-1 grid-cols-1 gap-2 md:grid-cols-{{ (in_array($role, ['admin', 'kepala'], true) || ($role === 'ketua' && !$isMineMode)) ? '3' : '2' }}">

                <!-- PROJECT -->
                <select name="project_id"
                    id="projectFilter"
                    class="rk-filter w-full border px-3 py-2 rounded">
                    <option value="">Semua Project</option>

                    @foreach($projects as $p)
                        <option value="{{ $p->id }}"
                            {{ request('project_id') == $p->id ? 'selected' : '' }}>
                            {{ $p->name }}
                        </option>
                    @endforeach
                </select>

                <!-- USER -->
                @if(in_array($role, ['admin', 'kepala'], true) || ($role === 'ketua' && !$isMineMode))
                    <select name="user_id"
                        id="userFilter"
                        class="rk-filter w-full border px-3 py-2 rounded">
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
                    class="w-full border px-3 py-2 rounded">
            </div>

            <!-- ACTION BUTTONS -->
            <div class="flex shrink-0 gap-2">
                <button type="submit"
                    class="whitespace-nowrap bg-gray-800 text-white px-5 py-2 rounded hover:bg-gray-700">
                    Filter
                </button>
            </div>

        </div>
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
                    <th class="text-left p-2">IKI</th>
                    <th class="text-left p-2">Status IKI</th>
                    <th class="text-left p-2">Progress</th>
                    <th class="text-left p-2">Aksi</th>
                </tr>
            </thead>

            <tbody id="rkAnggotaTableBody">
            @forelse($rkAnggotas as $rk)

                @php
    $ikiCount = $rk->ikis->count();
    $approvedIkiCount = $rk->ikis->where('status', 'approved')->count();
    $submittedIkiCount = $rk->ikis->where('status', 'submitted')->count();
    $rejectedIkiCount = $rk->ikis->where('status', 'rejected')->count();
    $draftIkiCount = $rk->ikis->where('status', 'draft')->count();

    if ($ikiCount === 0) {
        $statusClass = 'bg-gray-100 text-gray-700';
        $statusLabel = 'Belum Ada IKI';
    } elseif ($approvedIkiCount === $ikiCount) {
        $statusClass = 'bg-green-100 text-green-700';
        $statusLabel = 'Semua IKI Approved';
    } elseif ($submittedIkiCount > 0) {
        $statusClass = 'bg-blue-100 text-blue-700';
        $statusLabel = 'Ada IKI Submitted';
    } elseif ($rejectedIkiCount > 0) {
        $statusClass = 'bg-red-100 text-red-700';
        $statusLabel = 'Ada IKI Rejected';
    } else {
        $statusClass = 'bg-gray-100 text-gray-700';
        $statusLabel = 'Draft IKI';
    }

    $isOwner = (int) $rk->user_id === (int) auth()->id();

    $hasLockedIki = $rk->ikis
        ->whereIn('status', ['submitted', 'approved'])
        ->isNotEmpty();

    $canEditDelete = !$hasLockedIki
        && (
            $role === 'admin'
            || ($canManageRkAnggota && $isOwner)
        );

    $ikiUrl = match($role) {
        'admin' => '/admin/iki',
        'ketua' => $isMineMode ? '/ketua/iki?mode=mine&rk_anggota_id=' . $rk->id : '/ketua/iki?rk_anggota_id=' . $rk->id,
        'anggota' => '/anggota/iki?rk_anggota_id=' . $rk->id,
        'kepala' => '/kepala/iki?rk_anggota_id=' . $rk->id,
        default => '/iki?rk_anggota_id=' . $rk->id,
    };

    if ($role === 'admin') {
        $ikiUrl = '/admin/iki?rk_anggota_id=' . $rk->id;
    }
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

                    <!-- IKI -->
                    <td class="p-2">
                        <div class="font-semibold text-gray-800">
                            {{ $approvedIkiCount }}/{{ $ikiCount }} Approved
                        </div>

                        <div class="text-xs text-gray-400 mt-1">
                            {{ $ikiCount }} IKI total
                        </div>

                        @if($submittedIkiCount > 0)
                            <div class="text-xs text-blue-600 mt-1">
                                {{ $submittedIkiCount }} menunggu review
                            </div>
                        @endif

                        @if($rejectedIkiCount > 0)
                            <div class="text-xs text-red-600 mt-1">
                                {{ $rejectedIkiCount }} perlu revisi
                            </div>
                        @endif
                    </td>

                    <!-- STATUS IKI -->
                    <td class="p-2">
                        <span class="px-2 py-1 rounded text-xs font-semibold {{ $statusClass }}">
                            {{ $statusLabel }}
                        </span>

                        <div class="text-xs text-gray-500 mt-1">
                            Approval dilakukan di IKI
                        </div>
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
                    <td class="p-2 relative">
                        <div class="relative inline-block text-left">

                            <button type="button"
                                data-rk-action-button
                                onclick="toggleRkActionMenu('rk-action-menu-{{ $rk->id }}', event)"
                                class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                                Aksi
                                <span class="text-xs">▾</span>
                            </button>

                            <div id="rk-action-menu-{{ $rk->id }}"
                                data-rk-action-menu
                                class="hidden absolute right-0 top-full z-50 mt-2 w-44 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl">

                                <!-- VIEW -->
                                <button type="button"
                                    onclick="closeAllRkActionMenus(); openViewModal({{ $rk->id }})"
                                    class="block w-full px-4 py-2 text-left text-sm text-blue-600 hover:bg-blue-50">
                                    View
                                </button>

                                <!-- EDIT -->
                                @if($canEditDelete)
                                    <button type="button"
                                        onclick="closeAllRkActionMenus(); openEditModal({{ $rk->id }})"
                                        class="block w-full px-4 py-2 text-left text-sm text-yellow-600 hover:bg-yellow-50">
                                        Edit
                                    </button>
                                @endif

                                <!-- DELETE -->
                                @if($canEditDelete)
                                    <form method="POST"
                                        action="{{ url($basePath . '/' . $rk->id) }}"
                                        onsubmit="return confirm('Yakin ingin menghapus RK Anggota ini?')">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                            class="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50">
                                            Delete
                                        </button>
                                    </form>
                                @endif

                                <a href="{{ $ikiUrl }}"
                                    class="block px-4 py-2 text-sm font-semibold text-purple-600 hover:bg-purple-50">
                                    @if($canManageRkAnggota)
                                        {{ $ikiCount > 0 ? 'Kelola IKI' : 'Buat IKI' }}
                                    @else
                                        Lihat IKI
                                    @endif
                                </a>
                            </div>
                        </div>
                    </td>

                </tr>
            @empty
                <tr>
                    <td colspan="{{ $isPersonalMode ? 7 : 8 }}" class="text-center py-4">
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
                    RK Anggota adalah wadah kerja. Progress dan approval dihitung dari IKI.
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
            <div class="mb-5 relative">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Rencana Kinerja
                </label>

                <textarea id="create_description"
                    name="description"
                    required
                    rows="5"
                    placeholder="Ketik rencana kerja atau pilih dari template..."
                    autocomplete="off"
                    class="border w-full p-3 rounded-lg focus:ring focus:ring-green-100 focus:border-green-500"></textarea>

                <div id="createTemplateDropdown"
                    class="hidden mt-2 max-h-48 overflow-y-auto rounded-xl border bg-white shadow-lg">
                </div>

                <p class="text-xs text-gray-400 mt-1">
                    Ketik manual atau klik kolom ini untuk memilih template RK Anggota.
                </p>
            </div>

            <!-- INFO -->
            <div class="mb-5 p-4 rounded-xl bg-blue-50 border border-blue-100 text-sm text-blue-700">
                @if($isMineMode || $role === 'anggota')
                   Kamu bisa membuat lebih dari satu RK pribadi dalam project yang sama. Setelah RK dibuat, lanjutkan dengan membuat IKI. Daily Task nantinya dibuat di bawah IKI.
                @elseif($role === 'admin')
                    Pastikan anggota yang dipilih memang termasuk dalam project tersebut.
                @else
                    RK Anggota akan menjadi wadah kerja. Approval dilakukan melalui IKI, bukan langsung dari RK Anggota.
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

<!-- ================= SUBMIT MODAL ================= -->
@if($role === 'admin' || $isPersonalMode)
<div id="modalSubmit"
    class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4">

    <div class="bg-white rounded-2xl w-[560px] max-h-[90vh] overflow-y-auto shadow-xl">

        <!-- HEADER -->
        <div class="px-6 py-4 border-b flex justify-between items-start">
            <div>
                <h3 class="text-xl font-bold text-gray-900">
                    Submit RK Anggota
                </h3>

                <p class="text-sm text-gray-500 mt-1">
                    Kirim RK Anggota untuk direview Ketua. Sertakan link bukti final pekerjaan.
                </p>
            </div>

            <button type="button"
                onclick="closeModal('modalSubmit')"
                class="text-gray-400 hover:text-red-500 text-2xl leading-none">
                ×
            </button>
        </div>

        <form method="POST" id="formSubmit" class="p-6">
            @csrf
            @method('PATCH')

            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Link Bukti Final
                </label>

                <input type="text"
                    name="final_evidence"
                    required
                    maxlength="2000"
                    placeholder="Contoh: link laporan, folder Drive, PDF, spreadsheet, dashboard, atau dokumen output"
                    class="border w-full p-3 rounded-lg focus:ring focus:ring-purple-100 focus:border-purple-500">

                <p class="text-xs text-gray-400 mt-1">
                    Bukti ini menjadi dasar Ketua melakukan review approve/reject RK Anggota.
                </p>
            </div>

            <div class="mb-5 p-4 rounded-xl bg-purple-50 border border-purple-100 text-sm text-purple-700">
                Pastikan Daily Task sudah terisi dan bukti final dapat diakses oleh Ketua.
            </div>

            <div class="flex justify-end gap-2 pt-4 border-t">
                <button type="button"
                    onclick="closeModal('modalSubmit')"
                    class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg">
                    Cancel
                </button>

                <button type="submit"
                    class="bg-purple-600 hover:bg-purple-700 text-white px-5 py-2 rounded-lg shadow">
                    Submit
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
                    Perubahan hanya bisa dilakukan selama RK belum memiliki IKI yang Submitted atau Approved.
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
            <div class="mb-5 relative">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Rencana Kinerja
                </label>

                <textarea id="edit_description"
                    name="description"
                    required
                    rows="5"
                    placeholder="Ketik rencana kerja atau pilih dari template..."
                    autocomplete="off"
                    class="border w-full p-3 rounded-lg focus:ring focus:ring-yellow-100 focus:border-yellow-500"></textarea>

               <div id="editTemplateDropdown"
                    class="hidden mt-2 max-h-48 overflow-y-auto rounded-xl border bg-white shadow-lg">
                </div>

                <p class="text-xs text-gray-400 mt-1">
                    Ketik manual atau klik kolom ini untuk memilih template RK Anggota.
                </p>
            </div>

            <!-- INFO -->
            <div class="mb-5 p-4 rounded-xl bg-yellow-50 border border-yellow-100 text-sm text-yellow-700">
                RK Anggota tidak bisa diedit jika sudah memiliki IKI yang Submitted atau Approved.
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
const RK_TEMPLATE_OPTIONS = @json($rkTemplateOptions);

/*
|--------------------------------------------------------------------------
| Row Action Dropdown
|--------------------------------------------------------------------------
| Menu aksi ringkas untuk kolom Aksi.
|--------------------------------------------------------------------------
*/

function closeAllRkActionMenus() {
    document.querySelectorAll('[data-rk-action-menu]').forEach(menu => {
        menu.classList.add('hidden');
    });
}

function toggleRkActionMenu(menuId, event = null) {
    if (event) {
        event.stopPropagation();
    }

    const targetMenu = document.getElementById(menuId);

    if (!targetMenu) {
        return;
    }

    const isCurrentlyHidden = targetMenu.classList.contains('hidden');

    closeAllRkActionMenus();

    if (isCurrentlyHidden) {
        targetMenu.classList.remove('hidden');
    }
}

document.addEventListener('click', function (event) {
    const clickedButton = event.target.closest('[data-rk-action-button]');
    const clickedMenu = event.target.closest('[data-rk-action-menu]');

    if (!clickedButton && !clickedMenu) {
        closeAllRkActionMenus();
    }
});

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        closeAllRkActionMenus();
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

function openSubmitModal(id, actionUrl){
    const form = document.getElementById('formSubmit');
    const modal = document.getElementById('modalSubmit');

    if (!form || !modal) {
        console.error('Submit modal/form tidak ditemukan.');
        return;
    }

    form.action = actionUrl;
    modal.classList.remove('hidden');

    setTimeout(() => {
        const input = modal.querySelector('input[name="final_evidence"]');

        if (input) {
            input.focus();
        }
    }, 50);
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
            const viewContent = document.getElementById('viewContent');
            const modalView = document.getElementById('modalView');

            if (!viewContent || !modalView) {
                return;
            }

            const ikis = data.ikis ?? [];

            let ikisHtml = '';

            if (ikis.length > 0) {
                ikisHtml = `
                    <div class="mt-5">
                        <h4 class="font-semibold mb-2">Daftar IKI</h4>

                        <div class="border rounded-xl overflow-hidden">
                            ${ikis.map(iki => {
                                const statusClass = {
                                    draft: 'bg-gray-100 text-gray-700',
                                    submitted: 'bg-blue-100 text-blue-700',
                                    approved: 'bg-green-100 text-green-700',
                                    rejected: 'bg-red-100 text-red-700',
                                }[iki.status] ?? 'bg-gray-100 text-gray-700';

                                const evidenceHtml = iki.final_evidence
                                    ? `<a class="text-blue-600 underline" href="${escapeHtml(iki.final_evidence)}" target="_blank" rel="noopener noreferrer">Buka Bukti</a>`
                                    : '<span class="text-gray-400">Belum ada bukti final</span>';

                                const rejectionHtml = iki.status === 'rejected' && iki.rejection_note
                                    ? `<div class="mt-2 text-xs text-red-600"><b>Catatan:</b> ${escapeHtml(iki.rejection_note)}</div>`
                                    : '';

                                return `
                                    <div class="p-4 border-b last:border-b-0 bg-white">
                                        <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-3">
                                            <div>
                                                <div class="font-semibold text-gray-900">
                                                    ${escapeHtml(iki.description ?? '-')}
                                                </div>

                                                <div class="text-xs text-gray-500 mt-1">
                                                    Target: ${escapeHtml(iki.target ?? '-')} ${escapeHtml(iki.unit ?? '')}
                                                </div>

                                                <div class="text-xs text-gray-500 mt-1">
                                                    Daily Task: ${iki.daily_task_count ?? 0}
                                                </div>

                                                ${rejectionHtml}
                                            </div>

                                            <div class="text-right shrink-0">
                                                <span class="px-2 py-1 rounded text-xs font-semibold ${statusClass}">
                                                    ${escapeHtml(iki.status_label ?? iki.status ?? '-')}
                                                </span>

                                                <div class="text-xs text-gray-500 mt-2">
                                                    Progress: ${iki.progress ?? 0}%
                                                </div>

                                                <div class="text-xs mt-2">
                                                    ${evidenceHtml}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            }).join('')}
                        </div>
                    </div>
                `;
            } else {
                ikisHtml = `
                    <div class="mt-5 p-4 bg-yellow-50 text-yellow-700 rounded-xl border border-yellow-100">
                        Belum ada IKI untuk RK Anggota ini. Buat IKI terlebih dahulu agar progress dapat dihitung.
                    </div>
                `;
            }

            viewContent.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <div class="p-4 rounded-xl bg-gray-50 border">
                        <div class="text-xs text-gray-500 mb-1">IKU</div>
                        <div class="font-semibold">
                            ${escapeHtml(data.project?.rk_ketua?.iku?.name ?? '-')}
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-gray-50 border">
                        <div class="text-xs text-gray-500 mb-1">Project</div>
                        <div class="font-semibold">
                            ${escapeHtml(data.project?.name ?? '-')}
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-gray-50 border">
                        <div class="text-xs text-gray-500 mb-1">Tim</div>
                        <div class="font-semibold">
                            ${escapeHtml(data.project?.team?.name ?? '-')}
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-gray-50 border">
                        <div class="text-xs text-gray-500 mb-1">Anggota</div>
                        <div class="font-semibold">
                            ${escapeHtml(data.user?.name ?? '-')}
                        </div>
                    </div>

                </div>

                <div class="mt-4 p-4 rounded-xl border">
                    <div class="text-xs text-gray-500 mb-1">Rencana Kinerja</div>
                    <div class="font-medium text-gray-800">
                        ${escapeHtml(data.description ?? '-')}
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="p-4 rounded-xl border bg-purple-50 border-purple-100">
                        <div class="text-xs text-purple-600 font-semibold mb-1">Total IKI</div>
                        <div class="text-2xl font-bold text-purple-700">
                            ${data.iki_count ?? 0}
                        </div>
                    </div>

                    <div class="p-4 rounded-xl border bg-green-50 border-green-100">
                        <div class="text-xs text-green-600 font-semibold mb-1">IKI Approved</div>
                        <div class="text-2xl font-bold text-green-700">
                            ${data.approved_iki_count ?? 0}
                        </div>
                    </div>

                    <div class="p-4 rounded-xl border bg-blue-50 border-blue-100">
                        <div class="text-xs text-blue-600 font-semibold mb-1">Progress RK</div>
                        <div class="text-2xl font-bold text-blue-700">
                            ${data.progress ?? 0}%
                        </div>
                    </div>
                </div>

                <div class="mt-4 p-4 rounded-xl bg-blue-50 border border-blue-100 text-sm text-blue-700">
                   Approval dilakukan pada level IKI. RK Anggota hanya menjadi wadah rencana kerja.
                   Progress dan Daily Task dihitung dari IKI di bawah RK ini.

                    <div class="mt-2 font-semibold">
                        Total Daily Task: ${data.daily_task_count ?? 0}
                    </div>
                </div>

                ${ikisHtml}
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

/*
|--------------------------------------------------------------------------
| RK Template Picker - One Field Autocomplete
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

function escapeAttribute(value) {
    return escapeHtml(value).replaceAll('\n', '&#10;');
}

function splitNumberedTemplateDescription(description) {
    const text = String(description ?? '')
        .replace(/\s+/g, ' ')
        .trim();

    if (!text) {
        return [];
    }

    const matches = [...text.matchAll(/(?:^|\s)(\d+)\.\s*(.*?)(?=\s+\d+\.\s*|$)/g)];

    if (matches.length === 0) {
        return [text];
    }

    return matches
        .map(match => String(match[2] ?? '').trim())
        .filter(Boolean);
}

function getNormalizedRkTemplateOptions() {
    const normalized = [];

    RK_TEMPLATE_OPTIONS.forEach(item => {
        const descriptions = splitNumberedTemplateDescription(item.description);

        descriptions.forEach(description => {
            normalized.push({
                description: description,
                category: item.category ?? null,
                category_label: item.category_label ?? item.category ?? 'RK Anggota',
            });
        });
    });

    const unique = [];
    const seen = new Set();

    normalized.forEach(item => {
        const key = item.description.toLowerCase().trim();

        if (!key || seen.has(key)) {
            return;
        }

        seen.add(key);
        unique.push(item);
    });

    return unique;
}

function setupRkTemplateTextarea(textareaId, dropdownId) {
    const textarea = document.getElementById(textareaId);
    const dropdown = document.getElementById(dropdownId);

    if (!textarea || !dropdown) {
        return;
    }

    const templateOptions = getNormalizedRkTemplateOptions();

    function normalizeText(value) {
        return String(value ?? '').toLowerCase();
    }

    function hideDropdown() {
        dropdown.classList.add('hidden');
        dropdown.innerHTML = '';
    }

    function showDropdown() {
        dropdown.classList.remove('hidden');
    }

    function renderTemplates(keyword = '') {
        const search = normalizeText(keyword).trim();

        let templates = templateOptions.filter(item => {
            const description = normalizeText(item.description);
            const category = normalizeText(item.category_label ?? item.category);

            if (!search) {
                return true;
            }

            return description.includes(search) || category.includes(search);
        });

        templates = templates.slice(0, 50);

        if (templates.length === 0) {
            dropdown.innerHTML = `
                <div class="p-3 text-sm text-gray-400">
                    Template tidak ditemukan. Kamu tetap bisa mengetik manual.
                </div>
            `;
            showDropdown();
            return;
        }

        dropdown.innerHTML = templates.map(item => {
            const categoryLabel = item.category_label ?? item.category ?? 'RK Anggota';

            return `
                <button type="button"
                    class="rk-template-option block w-full border-b px-4 py-3 text-left text-sm last:border-b-0 hover:bg-green-50"
                    data-description="${escapeAttribute(item.description)}">
                    <div class="mb-1">
                        <span class="rounded bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-600">
                            ${escapeHtml(categoryLabel)}
                        </span>
                    </div>

                    <div class="leading-5 text-gray-800 break-words">
                        ${escapeHtml(item.description)}
                    </div>
                </button>
            `;
        }).join('');

        showDropdown();
    }

    textarea.addEventListener('focus', function () {
        renderTemplates(this.value);
    });

    textarea.addEventListener('input', function () {
        renderTemplates(this.value);
    });

    dropdown.addEventListener('mousedown', function (event) {
        event.preventDefault();

        const option = event.target.closest('.rk-template-option');

        if (!option) {
            return;
        }

        textarea.value = option.dataset.description || '';
        hideDropdown();

        setTimeout(() => {
            textarea.focus();
        }, 0);
    });

    document.addEventListener('click', function (event) {
        if (event.target === textarea || dropdown.contains(event.target)) {
            return;
        }

        hideDropdown();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            hideDropdown();
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {
    setupRkTemplateTextarea('create_description', 'createTemplateDropdown');
    setupRkTemplateTextarea('edit_description', 'editTemplateDropdown');
});

</script>

</x-app-layout>