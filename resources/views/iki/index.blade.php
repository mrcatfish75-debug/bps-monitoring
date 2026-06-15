<x-app-layout>

@php
    $authUser = auth()->user();
    $role = $authUser->role;

    $basePath = match($role) {
        'admin' => '/admin/iki',
        'ketua' => '/ketua/iki',
        'anggota' => '/anggota/iki',
        'kepala' => '/kepala/iki',
        default => '/iki',
    };

    $rkAnggotaBasePath = match($role) {
        'admin' => '/admin/rk-anggota',
        'ketua' => '/ketua/rk-anggota',
        'anggota' => '/anggota/rk-anggota',
        'kepala' => '/kepala/rk-anggota',
        default => '/rk-anggota',
    };

    $isMineMode = $role === 'ketua' && request('mode') === 'mine';
    $isPersonalMode = $role === 'anggota' || $isMineMode;
    $isKepala = $role === 'kepala';

    /*
    |--------------------------------------------------------------------------
    | Permission
    |--------------------------------------------------------------------------
    */
    $canManageIki = !$isKepala && ($role === 'admin' || $isPersonalMode);
    $canReviewIkiGlobal = $role === 'admin' || ($role === 'ketua' && !$isMineMode);
    $showBulkApprove = $canReviewIkiGlobal;

    $resetUrl = $isMineMode
        ? url()->current() . '?mode=mine'
        : url()->current();

    $formActionUrl = $isMineMode
        ? url($basePath) . '?mode=mine'
        : url($basePath);

    $bulkApproveAction = url($basePath . '/bulk-approve');

    $emptyColspan = ($isPersonalMode ? 8 : 9) + ($showBulkApprove ? 1 : 0);
@endphp

<div class="bg-white p-6 rounded-xl shadow">

    {{-- ================= HEADER ================= --}}
    <div class="flex flex-col gap-4 md:flex-row md:justify-between md:items-center mb-5">
        <div>
            <h2 class="text-xl font-bold text-gray-900">
                {{ $isPersonalMode ? 'IKI Pribadi Saya' : 'Indikator Kinerja Individu (IKI)' }}
            </h2>

            @if($isPersonalMode)
                <p class="text-sm text-gray-500 mt-1">
                    Menampilkan IKI milikmu sendiri sebagai pelaksana project.
                </p>
            @elseif($role === 'ketua')
                <p class="text-sm text-gray-500 mt-1">
                    Mode Ketua: review dan pantau IKI dari project yang kamu pimpin.
                    IKI berstatus Submitted bisa disetujui satu-satu atau sekaligus.
                </p>
            @elseif($role === 'kepala')
                <p class="text-sm text-gray-500 mt-1">
                    Mode monitoring. Kepala hanya dapat melihat seluruh IKI tanpa aksi tambah, edit, hapus, submit, approve, atau reject.
                </p>
            @else
                <p class="text-sm text-gray-500 mt-1">
                    IKI berada di bawah RK Anggota dan menjadi unit utama approval oleh Ketua Tim.
                </p>
            @endif
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ $resetUrl }}"
                class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300 font-semibold text-gray-700">
                Refresh
            </a>

            @if($canManageIki)
                <button type="button"
                    onclick="openCreateModal()"
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-semibold">
                    + Add IKI
                </button>
            @endif
        </div>
    </div>

    {{-- ================= ALERT ================= --}}
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

    @if($canManageIki && $rkAnggotas->count() === 0)
        <div class="mb-4 p-4 rounded-xl bg-yellow-50 border border-yellow-100 text-yellow-700 text-sm">
            <div class="font-semibold mb-1">
                Belum ada RK Anggota yang bisa dibuatkan IKI.
            </div>
            <p>
                Buat RK Anggota terlebih dahulu, lalu tambahkan IKI di bawah RK Anggota tersebut.
            </p>
        </div>
    @endif

    {{-- ================= FILTER ================= --}}
    <form id="ikiFilterForm"
        method="GET"
        class="mb-4">

        @if($isMineMode)
            <input type="hidden" name="mode" value="mine">
        @endif

        <div class="flex flex-col gap-2 xl:flex-row xl:items-center">

            <div class="grid flex-1 grid-cols-1 gap-2 md:grid-cols-{{ (in_array($role, ['admin', 'kepala'], true) || ($role === 'ketua' && !$isMineMode)) ? '5' : '4' }}">

                {{-- PROJECT --}}
                <select name="project_id"
                    id="projectFilter"
                    class="iki-filter w-full border px-3 py-2 rounded-lg">
                    <option value="">Semua Project</option>

                    @foreach($projects as $project)
                        <option value="{{ $project->id }}"
                            {{ request('project_id') == $project->id ? 'selected' : '' }}>
                            {{ $project->name }}
                        </option>
                    @endforeach
                </select>

                {{-- RK ANGGOTA --}}
                <select name="rk_anggota_id"
                    id="rkAnggotaFilter"
                    class="iki-filter w-full border px-3 py-2 rounded-lg">
                    <option value="">Semua RK Anggota</option>

                    @foreach($rkAnggotas as $rk)
                        <option value="{{ $rk->id }}"
                            {{ request('rk_anggota_id') == $rk->id ? 'selected' : '' }}>
                            {{ \Illuminate\Support\Str::limit($rk->description, 55) }}
                        </option>
                    @endforeach
                </select>

                {{-- USER --}}
                @if(in_array($role, ['admin', 'kepala'], true) || ($role === 'ketua' && !$isMineMode))
                    <select name="user_id"
                        id="userFilter"
                        class="iki-filter w-full border px-3 py-2 rounded-lg">
                        <option value="">Semua Anggota</option>

                        @foreach($users as $user)
                            <option value="{{ $user->id }}"
                                {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                @endif

                {{-- STATUS --}}
                <select name="status"
                    id="statusFilter"
                    class="iki-filter w-full border px-3 py-2 rounded-lg">
                    <option value="">Semua Status</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="submitted" {{ request('status') === 'submitted' ? 'selected' : '' }}>Submitted</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>

                {{-- SEARCH --}}
                <input type="text"
                    id="ikiSearchInput"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Cari IKI, RK, project, anggota..."
                    autocomplete="off"
                    class="w-full border px-3 py-2 rounded-lg">
            </div>

            <div class="flex shrink-0 gap-2">
                <button type="submit"
                    class="whitespace-nowrap bg-gray-800 text-white px-5 py-2 rounded-lg hover:bg-gray-700 font-semibold">
                    Filter
                </button>

                <a href="{{ $resetUrl }}"
                    class="whitespace-nowrap bg-gray-200 px-5 py-2 rounded-lg hover:bg-gray-300 font-semibold text-gray-700">
                    Reset
                </a>
            </div>
        </div>
    </form>

    <div id="ikiSearchInfo"
        class="hidden mb-4 p-3 rounded bg-blue-50 text-blue-700 text-sm">
    </div>

    {{-- ================= BULK APPROVE FORM ================= --}}
    @if($showBulkApprove)
        <form id="bulkApproveForm"
            method="POST"
            action="{{ $bulkApproveAction }}"
            onsubmit="return confirmBulkApprove()">
            @csrf

            @if(request()->filled('project_id'))
                <input type="hidden" name="project_id" value="{{ request('project_id') }}">
            @endif

            @if(request()->filled('rk_anggota_id'))
                <input type="hidden" name="rk_anggota_id" value="{{ request('rk_anggota_id') }}">
            @endif

            @if(request()->filled('user_id'))
                <input type="hidden" name="user_id" value="{{ request('user_id') }}">
            @endif

            @if(request()->filled('search'))
                <input type="hidden" name="search" value="{{ request('search') }}">
            @endif

            @if(request()->filled('year'))
                <input type="hidden" name="year" value="{{ request('year') }}">
            @endif
        </form>

        <div class="mb-4 rounded-xl border border-green-100 bg-green-50 p-4">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="font-bold text-green-800">
                        Bulk Approve IKI
                    </div>
                    <div class="text-sm text-green-700 mt-1">
                        Centang IKI berstatus <b>Submitted</b>, lalu klik <b>Approve Terpilih</b>.
                        IKI yang bukan Submitted tidak bisa dicentang.
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <span id="bulkSelectedInfo"
                        class="px-3 py-2 rounded-lg bg-white text-green-700 text-sm font-semibold border border-green-100">
                        0 IKI dipilih
                    </span>

                    <button id="bulkApproveButton"
                        type="submit"
                        form="bulkApproveForm"
                        disabled
                        class="px-4 py-2 rounded-lg bg-green-600 text-white font-semibold opacity-50 cursor-not-allowed">
                        Approve Terpilih
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ================= TABLE ================= --}}
    <div id="ikiTableWrapper" class="transition-opacity duration-150 overflow-x-auto">
        <table class="w-full text-sm min-w-[1180px]">

            <thead class="bg-gray-50 border-b">
                <tr>
                    @if($showBulkApprove)
                        <th class="text-center p-2 w-[48px]">
                            <input type="checkbox"
                                id="selectAllIki"
                                class="rounded border-gray-300 text-green-600 focus:ring-green-500"
                                title="Pilih semua IKI yang bisa diapprove">
                        </th>
                    @endif

                    <th class="text-left p-2">Project</th>
                    <th class="text-left p-2">RK Anggota</th>

                    @if(!$isPersonalMode)
                        <th class="text-left p-2">Anggota</th>
                    @endif

                    <th class="text-left p-2">IKI</th>
                    <th class="text-left p-2">Bukti Final</th>
                    <th class="text-left p-2">Status</th>
                    <th class="text-left p-2">Progress</th>
                    <th class="text-left p-2">Daily Task</th>
                    <th class="text-left p-2">Aksi</th>
                </tr>
            </thead>

            <tbody id="ikiTableBody">
            @forelse($ikis as $iki)

                @php
                    $statusClass = match($iki->status) {
                        'draft' => 'bg-gray-100 text-gray-700',
                        'submitted' => 'bg-blue-100 text-blue-700',
                        'approved' => 'bg-green-100 text-green-700',
                        'rejected' => 'bg-red-100 text-red-700',
                        default => 'bg-gray-100 text-gray-700',
                    };

                    $statusLabel = match($iki->status) {
                        'draft' => 'Draft',
                        'submitted' => 'Submitted',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        default => ucfirst($iki->status),
                    };

                    $rk = $iki->rkAnggota;
                    $project = $rk?->project;
                    $owner = $rk?->user;

                    $isOwner = $rk && (int) $rk->user_id === (int) auth()->id();
                    $isProjectLeader = $project && (int) $project->leader_id === (int) auth()->id();

                    $canEditDelete = $iki->isEditable()
                        && (
                            $role === 'admin'
                            || ($canManageIki && $isOwner)
                        );

                    $canSubmit = $iki->canSubmit()
                        && (
                            $role === 'admin'
                            || $isOwner
                        );

                    $canReview = $iki->canBeReviewed()
                        && !$isOwner
                        && (
                            $role === 'admin'
                            || ($role === 'ketua' && !$isMineMode && $isProjectLeader)
                        );

                    $submitAction = null;
                    if ($canSubmit) {
                        $submitAction = match($role) {
                            'admin' => route('admin.iki.submit', $iki->id),
                            'ketua' => route('ketua.iki.submit', $iki->id),
                            default => route('anggota.iki.submit', $iki->id),
                        };
                    }

                    $approveAction = null;
                    $rejectAction = null;

                    if ($canReview) {
                        $approveAction = $role === 'admin'
                            ? route('admin.iki.approve', $iki->id)
                            : route('ketua.iki.approve', $iki->id);

                        $rejectAction = $role === 'admin'
                            ? route('admin.iki.reject', $iki->id)
                            : route('ketua.iki.reject', $iki->id);
                    }
                @endphp

                <tr class="border-b hover:bg-gray-50">

                    @if($showBulkApprove)
                        <td class="p-2 text-center">
                            @if($canReview)
                                <input type="checkbox"
                                    name="iki_ids[]"
                                    value="{{ $iki->id }}"
                                    form="bulkApproveForm"
                                    class="iki-bulk-checkbox rounded border-gray-300 text-green-600 focus:ring-green-500">
                            @else
                                <span class="inline-block w-4 h-4 rounded border border-gray-200 bg-gray-100"
                                    title="Hanya IKI Submitted yang bisa dipilih"></span>
                            @endif
                        </td>
                    @endif

                    <td class="p-2 align-top">
                        <div class="font-semibold text-gray-800">
                            {{ $project->name ?? '-' }}
                        </div>
                        <div class="text-xs text-gray-400">
                            {{ $project->team->name ?? '-' }}
                        </div>
                    </td>

                    <td class="p-2 align-top">
                        {{ \Illuminate\Support\Str::limit($rk->description ?? '-', 70) }}
                    </td>

                    @if(!$isPersonalMode)
                        <td class="p-2 align-top">
                            {{ $owner->name ?? '-' }}
                        </td>
                    @endif

                    <td class="p-2 align-top">
                        <div class="font-medium text-gray-800">
                            {{ $iki->description }}
                        </div>

                        @if($iki->target || $iki->unit)
                            <div class="text-xs text-gray-500 mt-1">
                                Target: {{ $iki->target ?? '-' }} {{ $iki->unit ?? '' }}
                            </div>
                        @endif

                        @if($iki->status === 'rejected' && $iki->rejection_note)
                            <div class="mt-1 text-xs text-red-600">
                                <b>Catatan:</b> {{ $iki->rejection_note }}
                            </div>
                        @endif
                    </td>

                    <td class="p-2 align-top">
                        @if($iki->final_evidence)
                            <a href="{{ $iki->final_evidence }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex px-3 py-1 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 text-xs font-semibold">
                                Buka Bukti
                            </a>
                        @else
                            <span class="inline-flex px-3 py-1 rounded-lg bg-gray-100 text-gray-400 text-xs font-semibold">
                                Belum ada
                            </span>
                        @endif
                    </td>

                    <td class="p-2 align-top">
                        <span class="px-2 py-1 rounded text-xs font-semibold {{ $statusClass }}">
                            {{ $statusLabel }}
                        </span>

                        @if($iki->submitted_at)
                            <div class="text-xs text-gray-500 mt-1">
                                Submit: {{ $iki->submitted_at->format('d M Y H:i') }}
                            </div>
                        @endif

                        @if($iki->approved_at)
                            <div class="text-xs text-gray-500 mt-1">
                                Approved: {{ $iki->approved_at->format('d M Y H:i') }}
                            </div>
                        @endif
                    </td>

                    <td class="p-2 align-top">
                        <div class="w-full bg-gray-200 h-2 rounded">
                            <div class="bg-green-500 h-2 rounded"
                                style="width: {{ $iki->progress }}%">
                            </div>
                        </div>
                        <small>{{ $iki->progress }}%</small>
                    </td>

                    <td class="p-2 align-top">
                        <span class="inline-flex px-3 py-1 rounded-lg bg-gray-100 text-gray-700 text-xs font-semibold">
                            {{ $iki->dailyTasks->count() }} Task
                        </span>
                    </td>

                    <td class="p-2 align-top">
                        <div class="relative inline-block text-left">
                            <button type="button"
                                onclick="toggleActionMenu(event, 'ikiActionMenu-{{ $iki->id }}')"
                                class="inline-flex items-center justify-center gap-1 min-w-[84px] px-4 py-2 rounded-lg border border-gray-900 bg-white text-gray-900 hover:bg-gray-50 text-sm font-semibold shadow-sm">
                                Aksi
                                <span class="text-[10px] leading-none">▼</span>
                            </button>

                            <div id="ikiActionMenu-{{ $iki->id }}"
                                data-action-menu
                                onclick="event.stopPropagation()"
                                class="hidden fixed z-[9999] w-56 rounded-xl bg-white border border-gray-200 shadow-lg overflow-hidden">

                                <button type="button"
                                    onclick="closeAllActionMenus(); openViewModal({{ $iki->id }})"
                                    class="w-full text-left px-5 py-3 text-sm text-blue-600 hover:bg-blue-50">
                                    View
                                </button>

                                @if($canEditDelete)
                                    <button type="button"
                                        onclick="closeAllActionMenus(); openEditModal({{ $iki->id }})"
                                        class="w-full text-left px-5 py-3 text-sm text-yellow-600 hover:bg-yellow-50 border-t border-gray-100">
                                        Edit
                                    </button>

                                    <form method="POST"
                                        action="{{ url($basePath . '/' . $iki->id) }}{{ $isMineMode ? '?mode=mine' : '' }}"
                                        onsubmit="return confirm('Yakin ingin menghapus IKI ini?')">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                            class="w-full text-left px-5 py-3 text-sm text-red-600 hover:bg-red-50 border-t border-gray-100">
                                            Delete
                                        </button>
                                    </form>
                                @endif

                                @if($canSubmit)
                                    <button type="button"
                                        onclick="closeAllActionMenus(); openSubmitModal({{ $iki->id }}, '{{ $submitAction }}')"
                                        class="w-full text-left px-5 py-3 text-sm text-purple-600 hover:bg-purple-50 border-t border-gray-100">
                                        Submit
                                    </button>
                                @endif

                                @if($canReview)
                                    <form method="POST"
                                        action="{{ $approveAction }}"
                                        onsubmit="return confirm('Setujui IKI ini?')">
                                        @csrf
                                        @method('PATCH')

                                        <button type="submit"
                                            class="w-full text-left px-5 py-3 text-sm text-green-600 hover:bg-green-50 border-t border-gray-100">
                                            Approve
                                        </button>
                                    </form>

                                    <button type="button"
                                        onclick="closeAllActionMenus(); openRejectModal({{ $iki->id }}, '{{ $rejectAction }}')"
                                        class="w-full text-left px-5 py-3 text-sm text-red-600 hover:bg-red-50 border-t border-gray-100">
                                        Reject
                                    </button>
                                @endif

                                @if(!$canEditDelete && !$canSubmit && !$canReview)
                                    <div class="px-5 py-3 text-xs text-gray-400 bg-gray-50 border-t border-gray-100 leading-relaxed">
                                        @if($isKepala)
                                            Kepala hanya dapat monitoring.
                                        @elseif($iki->status === 'approved')
                                            IKI sudah approved.
                                        @elseif($iki->status === 'submitted')
                                            IKI sedang menunggu review.
                                        @else
                                            Tidak ada aksi tambahan.
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </td>

                </tr>
            @empty
                <tr>
                    <td colspan="{{ $emptyColspan }}" class="text-center py-4 text-gray-500">
                        Tidak ada data IKI
                    </td>
                </tr>
            @endforelse
            </tbody>

        </table>
    </div>

    <div id="ikiPagination" class="mt-4">
        {{ $ikis->withQueryString()->links() }}
    </div>

</div>


{{-- ================= VIEW MODAL ================= --}}
<div id="modalView"
    class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4">

    <div class="bg-white rounded-2xl w-[780px] max-h-[85vh] overflow-y-auto shadow-xl">

        <div class="px-6 py-4 border-b flex justify-between items-start">
            <div>
                <h3 class="text-xl font-bold text-gray-900">
                    Detail IKI
                </h3>
                <p class="text-sm text-gray-500 mt-1">
                    Detail IKI, status review, bukti final, dan Daily Task pendukung.
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


{{-- ================= CREATE MODAL ================= --}}
@if($canManageIki)
<div id="modalCreate"
    class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4">

    <div class="bg-white rounded-2xl w-[680px] max-h-[90vh] overflow-y-auto shadow-xl">

        <div class="px-6 py-4 border-b flex justify-between items-start">
            <div>
                <h3 class="text-xl font-bold text-gray-900">
                    Tambah IKI
                </h3>
                <p class="text-sm text-gray-500 mt-1">
                    Buat IKI di bawah RK Anggota. IKI inilah yang akan disubmit dan direview Ketua.
                </p>
            </div>

            <button type="button"
                onclick="closeModal('modalCreate')"
                class="text-gray-400 hover:text-red-500 text-2xl leading-none">
                ×
            </button>
        </div>

        <form method="POST" action="{{ $formActionUrl }}" class="p-6">
            @csrf

            @if($isMineMode)
                <input type="hidden" name="mode" value="mine">
            @endif

            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    RK Anggota
                </label>

                <select name="rk_anggota_id"
                    required
                    class="border w-full p-3 rounded-lg bg-white focus:ring focus:ring-green-100 focus:border-green-500">
                    <option value="">Pilih RK Anggota</option>

                    @foreach($rkAnggotas as $rk)
                        <option value="{{ $rk->id }}">
                            {{ $rk->project->name ?? '-' }} -
                            {{ \Illuminate\Support\Str::limit($rk->description, 90) }}

                            @if(!$isPersonalMode && $rk->user)
                                ({{ $rk->user->name }})
                            @endif
                        </option>
                    @endforeach
                </select>

                <p class="text-xs text-gray-400 mt-1">
                    IKI akan menjadi turunan dari RK Anggota yang dipilih.
                </p>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Uraian IKI
                </label>

                <textarea name="description"
                    required
                    rows="4"
                    placeholder="Contoh: Terlaksananya validasi data survei sesuai SOP"
                    class="border w-full p-3 rounded-lg focus:ring focus:ring-green-100 focus:border-green-500"></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        Target
                    </label>

                    <input type="text"
                        name="target"
                        placeholder="Contoh: 100"
                        class="border w-full p-3 rounded-lg focus:ring focus:ring-green-100 focus:border-green-500">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        Satuan
                    </label>

                    <input type="text"
                        name="unit"
                        placeholder="Contoh: persen, dokumen, kegiatan"
                        class="border w-full p-3 rounded-lg focus:ring focus:ring-green-100 focus:border-green-500">
                </div>
            </div>

            <div class="mb-5 p-4 rounded-xl bg-blue-50 border border-blue-100 text-sm text-blue-700">
                Setelah IKI dibuat, pelaksana dapat membuat Daily Task sebagai bukti proses dan submit IKI dengan bukti final.
            </div>

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


{{-- ================= EDIT MODAL ================= --}}
@if($canManageIki)
<div id="modalEdit"
    class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4">

    <div class="bg-white rounded-2xl w-[680px] max-h-[90vh] overflow-y-auto shadow-xl">

        <div class="px-6 py-4 border-b flex justify-between items-start">
            <div>
                <h3 class="text-xl font-bold text-gray-900">
                    Edit IKI
                </h3>
                <p class="text-sm text-gray-500 mt-1">
                    IKI hanya bisa diubah selama masih Draft atau Rejected.
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

            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    RK Anggota
                </label>

                <select id="edit_rk_anggota_id"
                    name="rk_anggota_id"
                    required
                    class="border w-full p-3 rounded-lg bg-white focus:ring focus:ring-yellow-100 focus:border-yellow-500">
                    @foreach($rkAnggotas as $rk)
                        <option value="{{ $rk->id }}">
                            {{ $rk->project->name ?? '-' }} -
                            {{ \Illuminate\Support\Str::limit($rk->description, 90) }}

                            @if(!$isPersonalMode && $rk->user)
                                ({{ $rk->user->name }})
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Uraian IKI
                </label>

                <textarea id="edit_description"
                    name="description"
                    required
                    rows="4"
                    class="border w-full p-3 rounded-lg focus:ring focus:ring-yellow-100 focus:border-yellow-500"></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        Target
                    </label>

                    <input id="edit_target"
                        type="text"
                        name="target"
                        class="border w-full p-3 rounded-lg focus:ring focus:ring-yellow-100 focus:border-yellow-500">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        Satuan
                    </label>

                    <input id="edit_unit"
                        type="text"
                        name="unit"
                        class="border w-full p-3 rounded-lg focus:ring focus:ring-yellow-100 focus:border-yellow-500">
                </div>
            </div>

            <div class="mb-5 p-4 rounded-xl bg-yellow-50 border border-yellow-100 text-sm text-yellow-700">
                IKI yang sudah Submitted atau Approved tidak bisa diedit.
            </div>

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


{{-- ================= SUBMIT MODAL ================= --}}
@if($role === 'admin' || $isPersonalMode)
<div id="modalSubmit"
    class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4">

    <div class="bg-white rounded-2xl w-[560px] max-h-[90vh] overflow-y-auto shadow-xl">

        <div class="px-6 py-4 border-b flex justify-between items-start">
            <div>
                <h3 class="text-xl font-bold text-gray-900">
                    Submit IKI
                </h3>
                <p class="text-sm text-gray-500 mt-1">
                    Kirim IKI untuk direview Ketua. Sertakan link bukti final.
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
                    placeholder="Contoh: link laporan, Drive, PDF, dashboard, spreadsheet, dokumen output"
                    class="border w-full p-3 rounded-lg focus:ring focus:ring-purple-100 focus:border-purple-500">

                <p class="text-xs text-gray-400 mt-1">
                    Bukti ini menjadi dasar Ketua melakukan review approve/reject IKI.
                </p>
            </div>

            <div class="mb-5 p-4 rounded-xl bg-purple-50 border border-purple-100 text-sm text-purple-700">
                Pastikan bukti final bisa diakses oleh Ketua.
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


{{-- ================= REJECT MODAL ================= --}}
@if($canReviewIkiGlobal)
<div id="modalReject"
    class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4">

    <div class="bg-white rounded-2xl w-[460px] shadow-xl">

        <div class="px-6 py-4 border-b">
            <h3 class="text-lg font-bold text-gray-900">
                Tolak IKI
            </h3>
            <p class="text-sm text-gray-500 mt-1">
                Tulis alasan penolakan agar pelaksana dapat memperbaiki IKI.
            </p>
        </div>

        <form method="POST" id="formReject" class="p-6">
            @csrf
            @method('PATCH')

            <textarea name="rejection_note"
                required
                maxlength="1000"
                rows="4"
                placeholder="Tulis alasan penolakan..."
                class="border w-full mb-4 p-3 rounded-lg focus:ring focus:ring-red-100 focus:border-red-500"></textarea>

            <div class="flex justify-end gap-2">
                <button type="button"
                    onclick="closeModal('modalReject')"
                    class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg">
                    Cancel
                </button>

                <button class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg">
                    Reject
                </button>
            </div>
        </form>

    </div>
</div>
@endif


{{-- ================= SCRIPT ================= --}}
<script>
const IKI_BASE_PATH = @json($basePath);
const IS_MINE_MODE = @json($isMineMode);

/*
|--------------------------------------------------------------------------
| Bulk Approve
|--------------------------------------------------------------------------
*/

function getSelectedBulkIkiCount() {
    return document.querySelectorAll('.iki-bulk-checkbox:checked').length;
}

function updateBulkApproveState() {
    const count = getSelectedBulkIkiCount();
    const info = document.getElementById('bulkSelectedInfo');
    const button = document.getElementById('bulkApproveButton');
    const selectAll = document.getElementById('selectAllIki');

    if (info) {
        info.textContent = `${count} IKI dipilih`;
    }

    if (button) {
        if (count > 0) {
            button.disabled = false;
            button.classList.remove('opacity-50', 'cursor-not-allowed');
            button.classList.add('hover:bg-green-700');
        } else {
            button.disabled = true;
            button.classList.add('opacity-50', 'cursor-not-allowed');
            button.classList.remove('hover:bg-green-700');
        }
    }

    if (selectAll) {
        const availableCheckboxes = document.querySelectorAll('.iki-bulk-checkbox');

        if (availableCheckboxes.length === 0) {
            selectAll.checked = false;
            selectAll.indeterminate = false;
            return;
        }

        const checkedCheckboxes = document.querySelectorAll('.iki-bulk-checkbox:checked');

        selectAll.checked = checkedCheckboxes.length === availableCheckboxes.length;
        selectAll.indeterminate = checkedCheckboxes.length > 0 && checkedCheckboxes.length < availableCheckboxes.length;
    }
}

function confirmBulkApprove() {
    const count = getSelectedBulkIkiCount();

    if (count < 1) {
        alert('Pilih minimal satu IKI untuk disetujui.');
        return false;
    }

    return confirm(`Setujui ${count} IKI terpilih?`);
}

function bindBulkApproveEvents() {
    const selectAll = document.getElementById('selectAllIki');

    if (selectAll) {
        selectAll.onclick = function () {
            document.querySelectorAll('.iki-bulk-checkbox').forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });

            updateBulkApproveState();
        };
    }

    document.querySelectorAll('.iki-bulk-checkbox').forEach(checkbox => {
        checkbox.onchange = updateBulkApproveState;
    });

    updateBulkApproveState();
}

/*
|--------------------------------------------------------------------------
| Action Dropdown
|--------------------------------------------------------------------------
*/

function toggleActionMenu(event, menuId) {
    event.stopPropagation();

    const menu = document.getElementById(menuId);
    const button = event.currentTarget;

    if (!menu || !button) {
        return;
    }

    const isHidden = menu.classList.contains('hidden');

    closeAllActionMenus();

    if (!isHidden) {
        return;
    }

    const rect = button.getBoundingClientRect();
    const menuWidth = 224;
    const gap = 8;

    let left = rect.left;
    let top = rect.bottom + gap;

    if (left + menuWidth > window.innerWidth - gap) {
        left = window.innerWidth - menuWidth - gap;
    }

    if (left < gap) {
        left = gap;
    }

    const estimatedMenuHeight = menu.scrollHeight || 180;

    if (top + estimatedMenuHeight > window.innerHeight - gap) {
        top = rect.top - estimatedMenuHeight - gap;
    }

    if (top < gap) {
        top = gap;
    }

    menu.style.left = `${left}px`;
    menu.style.top = `${top}px`;
    menu.classList.remove('hidden');
}

function closeAllActionMenus() {
    document.querySelectorAll('[data-action-menu]').forEach(menu => {
        menu.classList.add('hidden');
    });
}

document.addEventListener('click', closeAllActionMenus);

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeAllActionMenus();
    }
});

window.addEventListener('resize', closeAllActionMenus);
window.addEventListener('scroll', closeAllActionMenus, true);

/*
|--------------------------------------------------------------------------
| Modal Helpers
|--------------------------------------------------------------------------
*/

function openCreateModal() {
    const modal = document.getElementById('modalCreate');

    if (modal) {
        modal.classList.remove('hidden');
    }
}

function closeModal(id) {
    const modal = document.getElementById(id);

    if (modal) {
        modal.classList.add('hidden');
    }
}

function openSubmitModal(id, actionUrl) {
    const form = document.getElementById('formSubmit');
    const modal = document.getElementById('modalSubmit');

    if (!form || !modal) {
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

function openRejectModal(id, actionUrl) {
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
| Edit Modal
|--------------------------------------------------------------------------
*/

function openEditModal(id) {
    fetch(`${IKI_BASE_PATH}/${id}`)
        .then(async res => {
            if (!res.ok) {
                const body = await res.text();
                console.error('IKI edit fetch error:', res.status, body);
                throw new Error('Gagal mengambil detail IKI.');
            }

            return res.json();
        })
        .then(data => {
            const modal = document.getElementById('modalEdit');
            const form = document.getElementById('formEdit');

            const rkInput = document.getElementById('edit_rk_anggota_id');
            const descriptionInput = document.getElementById('edit_description');
            const targetInput = document.getElementById('edit_target');
            const unitInput = document.getElementById('edit_unit');

            if (!modal || !form || !rkInput || !descriptionInput) {
                return;
            }

            form.action = `${IKI_BASE_PATH}/${data.id}${IS_MINE_MODE ? '?mode=mine' : ''}`;

            rkInput.value = data.rk_anggota_id;
            descriptionInput.value = data.description ?? '';

            if (targetInput) {
                targetInput.value = data.target ?? '';
            }

            if (unitInput) {
                unitInput.value = data.unit ?? '';
            }

            modal.classList.remove('hidden');
        })
        .catch(error => {
            console.error(error);
            alert('Gagal membuka edit IKI.');
        });
}

/*
|--------------------------------------------------------------------------
| View Modal
|--------------------------------------------------------------------------
*/

function openViewModal(id) {
    fetch(`${IKI_BASE_PATH}/${id}`)
        .then(async res => {
            if (!res.ok) {
                const body = await res.text();
                console.error('IKI view fetch error:', res.status, body);
                throw new Error('Gagal mengambil detail IKI.');
            }

            return res.json();
        })
        .then(data => {
            const viewContent = document.getElementById('viewContent');
            const modalView = document.getElementById('modalView');

            if (!viewContent || !modalView) {
                return;
            }

            const dailyTasks = data.daily_tasks ?? [];

            let dailyTasksHtml = '';

            if (dailyTasks.length > 0) {
                dailyTasksHtml = `
                    <div class="mt-5">
                        <h4 class="font-semibold mb-2">Daily Task / Bukti Proses</h4>

                        <div class="border rounded-xl overflow-hidden">
                            ${dailyTasks.map(task => {
                                const evidenceHtml = task.evidence_url
                                    ? `<a class="text-blue-600 underline" href="${escapeHtml(task.evidence_url)}" target="_blank">Buka Link</a>`
                                    : '<span class="text-gray-400">-</span>';

                                return `
                                    <div class="p-4 border-b last:border-b-0 bg-white">
                                        <div class="flex justify-between gap-4 mb-2">
                                            <div class="font-medium text-gray-800">
                                                ${escapeHtml(task.activity ?? '-')}
                                            </div>
                                            <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded shrink-0">
                                                ${escapeHtml(task.date ?? '-')}
                                            </span>
                                        </div>

                                        <div class="text-sm text-gray-600">
                                            <b>Output:</b> ${escapeHtml(task.output ?? '-')}
                                        </div>

                                        <div class="text-sm text-gray-600 mt-1">
                                            <b>Link Bukti:</b> ${evidenceHtml}
                                        </div>
                                    </div>
                                `;
                            }).join('')}
                        </div>
                    </div>
                `;
            } else {
                dailyTasksHtml = `
                    <div class="mt-5 p-4 bg-yellow-50 text-yellow-700 rounded-xl border border-yellow-100">
                        Belum ada Daily Task untuk IKI ini.
                    </div>
                `;
            }

            const rejectionHtml = data.status === 'rejected' && data.rejection_note
                ? `
                    <div class="mt-4 p-3 rounded-xl bg-red-50 text-red-700 border border-red-100">
                        <b>Catatan Penolakan:</b> ${escapeHtml(data.rejection_note)}
                    </div>
                `
                : '';

            const finalEvidenceHtml = data.final_evidence
                ? `<a href="${escapeHtml(data.final_evidence)}" target="_blank" rel="noopener noreferrer" class="text-blue-700 underline">Buka Bukti Final</a>`
                : `<span class="text-gray-400">Belum ada bukti final.</span>`;

            viewContent.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <div class="p-4 rounded-xl bg-gray-50 border">
                        <div class="text-xs text-gray-500 mb-1">IKU</div>
                        <div class="font-semibold">
                            ${escapeHtml(data.rk_anggota?.project?.rk_ketua?.iku?.name ?? '-')}
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-gray-50 border">
                        <div class="text-xs text-gray-500 mb-1">Project</div>
                        <div class="font-semibold">
                            ${escapeHtml(data.rk_anggota?.project?.name ?? '-')}
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-gray-50 border">
                        <div class="text-xs text-gray-500 mb-1">Tim</div>
                        <div class="font-semibold">
                            ${escapeHtml(data.rk_anggota?.project?.team?.name ?? '-')}
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-gray-50 border">
                        <div class="text-xs text-gray-500 mb-1">Anggota</div>
                        <div class="font-semibold">
                            ${escapeHtml(data.rk_anggota?.user?.name ?? '-')}
                        </div>
                    </div>

                </div>

                <div class="mt-4 p-4 rounded-xl border">
                    <div class="text-xs text-gray-500 mb-1">RK Anggota</div>
                    <div class="font-medium text-gray-800">
                        ${escapeHtml(data.rk_anggota?.description ?? '-')}
                    </div>
                </div>

                <div class="mt-4 p-4 rounded-xl border">
                    <div class="text-xs text-gray-500 mb-1">Uraian IKI</div>
                    <div class="font-medium text-gray-800 whitespace-pre-line">
                        ${escapeHtml(data.description ?? '-')}
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-4 rounded-xl border">
                        <p><b>Target:</b> ${escapeHtml(data.target ?? '-')}</p>
                        <p><b>Satuan:</b> ${escapeHtml(data.unit ?? '-')}</p>
                        <p><b>Status:</b> ${escapeHtml(data.status_label ?? data.status ?? '-')}</p>
                        <p><b>Progress:</b> ${escapeHtml(data.progress ?? 0)}%</p>
                    </div>

                    <div class="p-4 rounded-xl border">
                        <p><b>Submitted At:</b> ${data.submitted_at ? formatDate(data.submitted_at) : '-'}</p>
                        <p><b>Approved At:</b> ${data.approved_at ? formatDate(data.approved_at) : '-'}</p>
                        <p><b>Approved By:</b> ${escapeHtml(data.approver?.name ?? '-')}</p>
                    </div>
                </div>

                <div class="mt-4 p-4 rounded-xl border bg-blue-50 border-blue-100">
                    <div class="text-xs text-blue-600 mb-1 font-semibold">Bukti Final IKI</div>
                    <div class="font-medium">
                        ${finalEvidenceHtml}
                    </div>
                </div>

                ${rejectionHtml}

                ${dailyTasksHtml}
            `;

            modalView.classList.remove('hidden');
        })
        .catch(error => {
            console.error(error);
            alert('Gagal mengambil detail IKI.');
        });
}

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function formatDate(dateString) {
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
| AJAX Filter
|--------------------------------------------------------------------------
*/

let ikiSearchTimer = null;
let lastIkiSearchUrl = '';

function getIkiFilterParams() {
    const form = document.getElementById('ikiFilterForm');
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

function setIkiSearchInfo(message = '', type = 'info') {
    const info = document.getElementById('ikiSearchInfo');

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

function countIkiRows() {
    const tbody = document.getElementById('ikiTableBody');

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

function fetchIkiList(url) {
    const tableWrapper = document.getElementById('ikiTableWrapper');

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
                throw new Error('Gagal memuat data IKI.');
            }

            return res.text();
        })
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            const newTbody = doc.querySelector('#ikiTableBody');
            const currentTbody = document.getElementById('ikiTableBody');

            const newPagination = doc.querySelector('#ikiPagination');
            const currentPagination = document.getElementById('ikiPagination');

            if (!newTbody || !currentTbody) {
                throw new Error('Target tabel IKI tidak ditemukan.');
            }

            currentTbody.innerHTML = newTbody.innerHTML;

            if (newPagination && currentPagination) {
                currentPagination.innerHTML = newPagination.innerHTML;
            }

            updateBrowserUrl(url);

            const totalRows = countIkiRows();

            if (totalRows === 0) {
                setIkiSearchInfo('Tidak ada IKI yang sesuai dengan filter/search.', 'warning');
            } else {
                setIkiSearchInfo(`Menampilkan ${totalRows} data IKI sesuai filter/search.`, 'success');
            }

            closeAllActionMenus();
            bindIkiPaginationLinks();
            bindBulkApproveEvents();
        })
        .catch(error => {
            console.error('IKI AJAX FILTER ERROR:', error);
            setIkiSearchInfo('Gagal memuat data IKI. Cek filter atau controller index.', 'error');
        })
        .finally(() => {
            if (tableWrapper) {
                tableWrapper.classList.remove('opacity-60');
            }
        });
}

function runIkiInstantSearch() {
    const params = getIkiFilterParams();
    const queryString = params.toString();

    const url = queryString
        ? `${window.location.pathname}?${queryString}`
        : window.location.pathname;

    if (url === lastIkiSearchUrl) {
        return;
    }

    lastIkiSearchUrl = url;

    fetchIkiList(url);
}

function triggerIkiSearch() {
    clearTimeout(ikiSearchTimer);

    ikiSearchTimer = setTimeout(() => {
        runIkiInstantSearch();
    }, 300);
}

function bindIkiPaginationLinks() {
    const pagination = document.getElementById('ikiPagination');

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

            fetchIkiList(url);
        });
    });
}

document.addEventListener('DOMContentLoaded', function () {
    const filterForm = document.getElementById('ikiFilterForm');
    const searchInput = document.getElementById('ikiSearchInput');
    const filterInputs = document.querySelectorAll('.iki-filter');

    if (filterForm) {
        filterForm.addEventListener('submit', function (event) {
            event.preventDefault();
            triggerIkiSearch();
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', triggerIkiSearch);
    }

    filterInputs.forEach(input => {
        input.addEventListener('change', triggerIkiSearch);
    });

    bindIkiPaginationLinks();
    bindBulkApproveEvents();
});
</script>

</x-app-layout>