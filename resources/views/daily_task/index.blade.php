<x-app-layout>

@php
    $role = auth()->user()->role;

    $basePath = match($role) {
        'admin' => '/admin/daily-task',
        'anggota' => '/anggota/daily-task',
        'ketua' => '/ketua/daily-task',
        default => '/daily-task',
    };

    /*
    |--------------------------------------------------------------------------
    | Mode Context
    |--------------------------------------------------------------------------
    | /ketua/daily-task
    |   = mode ketua untuk monitoring Daily Task anggota dari project yang dipimpin.
    |
    | /ketua/daily-task?mode=mine
    |   = mode pekerjaan saya, hanya Daily Task milik ketua sendiri.
    */
    $isMineMode = $role === 'ketua' && request('mode') === 'mine';

    $isPersonalMode = $role === 'anggota' || $isMineMode;

    /*
    |--------------------------------------------------------------------------
    | Manage Permission
    |--------------------------------------------------------------------------
    | Admin:
    | - bisa mengelola semua Daily Task.
    |
    | Anggota:
    | - bisa mengelola Daily Task miliknya sendiri.
    |
    | Ketua mode mine:
    | - bisa mengelola Daily Task miliknya sendiri sebagai pelaksana project.
    |
    | Ketua mode normal:
    | - hanya monitoring Daily Task anggota project yang dia pimpin.
    */
    $canManageDailyTask = $role === 'admin' || $isPersonalMode;

    /*
    |--------------------------------------------------------------------------
    | URL yang mempertahankan mode
    |--------------------------------------------------------------------------
    */
    $cleanUrl = url()->current();
    $resetUrl = $isMineMode ? $cleanUrl . '?mode=mine' : $cleanUrl;
@endphp



<div class="bg-white p-6 rounded-xl shadow">

    <!-- ================= HEADER ================= -->
    <div class="flex justify-between items-center mb-4">
        <div>
    <h2 class="text-xl font-bold">
        {{ $isPersonalMode ? 'Daily Task Saya' : 'Daily Task' }}
    </h2>

    @if($isPersonalMode)
    <p class="text-sm text-gray-500 mt-1">
        Menampilkan Daily Task milikmu sendiri.
    </p>
@endif
</div>

        <div class="flex gap-2">
            <a href="{{ $resetUrl }}"
                class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">
                Refresh
            </a>

            @if($canManageDailyTask && $rkAnggotas->count() > 0)
                <button onclick="openModal('modalCreate')"
                    class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                    + Add
                </button>
            @endif

            @if($isPersonalMode && $rkAnggotas->count() === 0)
                <div class="mb-4 p-4 rounded-xl bg-yellow-50 text-yellow-700 border border-yellow-100 text-sm">
                    Belum ada RK Pribadi yang bisa diisi Daily Task. Buat RK Pribadi terlebih dahulu atau pastikan RK masih berstatus Draft/Rejected.
                </div>
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
<form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-2 mb-4">

    @if($isMineMode)
        <input type="hidden" name="mode" value="mine">
    @endif

    <!-- SEARCH -->
    <input type="text"
        name="search"
        value="{{ request('search') }}"
        placeholder="Cari activity, link bukti, atau RK Anggota..."
        class="border px-3 py-2 rounded md:col-span-2">

    <!-- START DATE -->
<input type="date"
    name="start_date"
    value="{{ request('start_date') }}"
    class="border px-3 py-2 rounded">

<!-- END DATE -->
<input type="date"
    name="end_date"
    value="{{ request('end_date') }}"
    class="border px-3 py-2 rounded">

    <!-- FILTER -->
    <button class="bg-gray-800 text-white px-4 rounded">
        Filter
    </button>

    <!-- RESET -->
    <a href="{{ $resetUrl }}"
        class="bg-gray-200 text-center px-4 py-2 rounded hover:bg-gray-300">
        Reset
    </a>

</form>

@if(request('start_date') || request('end_date'))
    <div class="mb-4 p-3 bg-yellow-50 text-yellow-700 rounded text-sm">
        Menampilkan Daily Task

        @if(request('start_date'))
            dari <b>{{ request('start_date') }}</b>
        @endif

        @if(request('end_date'))
            sampai <b>{{ request('end_date') }}</b>
        @endif
    </div>
@endif

    <!-- ================= ROLE INFO ================= -->
<div class="mb-4 p-3 bg-blue-50 text-blue-700 rounded text-sm">
    @if($role === 'admin')
        Mode Admin: kamu dapat mengelola semua Daily Task untuk kebutuhan administrasi.
    @elseif($role === 'anggota')
        Mode Anggota: kamu hanya dapat mengelola Daily Task milikmu selama RK Anggota masih Draft atau Rejected.
    @elseif($role === 'ketua' && $isMineMode)
        Mode Pekerjaan Saya: kamu dapat mengelola Daily Task milikmu selama RK Anggota masih Draft atau Rejected.
    @elseif($role === 'ketua')
        Mode Ketua: halaman ini hanya untuk monitoring proses kerja anggota. Approval dilakukan dari RK Anggota.
    @endif
</div>

    <!-- ================= TABLE ================= -->
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="text-left p-2">Project</th>
                    <th class="text-left p-2">RK Anggota</th>
                    @if(!$isPersonalMode)
                        <th class="text-left p-2">Anggota</th>
                    @endif
                    <th class="text-left p-2">Tanggal</th>
                    <th class="text-left p-2">Activity</th>
                    <th class="text-left p-2">Link Bukti</th>
                    <th class="text-left p-2">Status RK</th>
                    <th class="text-left p-2">Aksi</th>
                </tr>
            </thead>

            <tbody>
            @forelse($tasks as $t)
                @php
                    $rk = $t->rkAnggota;
                    $rkStatus = $rk->status ?? 'draft';

                    $statusClass = match($rkStatus) {
                        'draft' => 'bg-gray-100 text-gray-700',
                        'submitted' => 'bg-blue-100 text-blue-700',
                        'approved' => 'bg-green-100 text-green-700',
                        'rejected' => 'bg-red-100 text-red-700',
                        default => 'bg-gray-100 text-gray-700',
                    };

                    $statusLabel = match($rkStatus) {
                        'draft' => 'Draft',
                        'submitted' => 'Submitted',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        default => ucfirst($rkStatus),
                    };

                    $isOwner = (int) optional($rk)->user_id === (int) auth()->id();

                $canEditThisTask = in_array($rkStatus, ['draft', 'rejected'])
                    && (
                        $role === 'admin'
                        || ($isPersonalMode && $isOwner)
                    );
                @endphp

                <tr class="border-b hover:bg-gray-50">
                    <td class="p-2">
                        {{ $rk->project->name ?? '-' }}
                    </td>

                    <td class="p-2">
                        {{ $rk->description ?? '-' }}
                    </td>

                    @if(!$isPersonalMode)
                        <td class="p-2">
                            {{ $rk->user->name ?? '-' }}
                        </td>
                    @endif

                    <td class="p-2">
                        {{ $t->date ? \Carbon\Carbon::parse($t->date)->format('d M Y') : '-' }}
                    </td>

                    <td class="p-2">
                        {{ $t->activity }}
                    </td>

                    <td class="p-2">
    @if($t->evidence_url)
        <a href="{{ $t->evidence_url }}"
            target="_blank"
            class="text-blue-600 underline">
            Buka Link
        </a>
    @else
        <span class="text-gray-400">-</span>
    @endif
</td>
                    <td class="p-2">
                        <span class="px-2 py-1 rounded text-xs font-semibold {{ $statusClass }}">
                            {{ $statusLabel }}
                        </span>

                        @if($rkStatus === 'submitted')
                            <div class="text-xs text-gray-500 mt-1">
                                Menunggu review ketua
                            </div>
                        @elseif($rkStatus === 'approved')
                            <div class="text-xs text-gray-500 mt-1">
                                Sudah dikunci
                            </div>
                        @elseif($rkStatus === 'rejected')
                            <div class="text-xs text-gray-500 mt-1">
                                Bisa direvisi
                            </div>
                        @endif
                    </td>

                    <td class="p-2">
                        <div class="flex flex-wrap gap-2">

                            <button type="button"
                                onclick="openViewModal({{ $t->id }})"
                                class="text-blue-500">
                                View
                            </button>

                            @if($canEditThisTask)
                                <button type="button"
                                    onclick="openEditModal({{ $t->id }})"
                                    class="text-yellow-500">
                                    Edit
                                </button>

                                <form method="POST"
                                    action="{{ $basePath }}/{{ $t->id }}"
                                    class="inline"
                                    onsubmit="return confirm('Yakin ingin menghapus Daily Task ini?')">
                                    @csrf
                                    @method('DELETE')

                                    <button class="text-red-500">
                                        Delete
                                    </button>
                                </form>
                            @else
                                <span class="text-gray-400 text-xs">
                                    Read only
                                </span>
                            @endif

                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $isPersonalMode ? 7 : 8 }}" class="text-center py-4 text-gray-500">
                        Belum ada Daily Task
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($tasks, 'links'))
        <div class="mt-4">
            {{ $tasks->withQueryString()->links() }}
        </div>
    @endif

</div>


<!-- ================= CREATE MODAL ================= -->
<!-- ================= CREATE MODAL ================= -->
@if($canManageDailyTask)
<div id="modalCreate"
    class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50">

    <div class="bg-white rounded-2xl w-[720px] max-h-[90vh] overflow-y-auto shadow-xl">

        <!-- HEADER -->
        <div class="px-6 py-4 border-b flex justify-between items-start">
            <div>
                <h3 class="text-xl font-bold text-gray-900">
                    {{ $isPersonalMode ? 'Tambah Daily Task Saya' : 'Tambah Daily Task' }}
                </h3>

                <p class="text-sm text-gray-500 mt-1">
                    @if($isPersonalMode)
                        Catat progres harian untuk RK pribadi yang masih Draft atau Rejected.
                    @elseif($role === 'admin')
                        Tambahkan catatan proses kerja untuk RK Anggota tertentu.
                    @else
                        Tambahkan catatan proses kerja.
                    @endif
                </p>
            </div>

            <button type="button"
                onclick="closeModal('modalCreate')"
                class="text-gray-400 hover:text-red-500 text-2xl leading-none">
                ×
            </button>
        </div>

        <form method="POST" action="{{ $basePath }}" class="p-6">
            @csrf

            @if($isMineMode)
                <input type="hidden" name="mode" value="mine">
            @endif

            <!-- RK ANGGOTA -->
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    RK Anggota
                </label>

                <select name="rk_anggota_id"
                    required
                    class="border w-full p-3 rounded-lg bg-white focus:ring focus:ring-green-100 focus:border-green-500">
                    <option value="">Pilih RK Anggota</option>

                    @foreach($rkAnggotas as $rk)
                        @if(in_array($rk->status, ['draft', 'rejected']))
                            <option value="{{ $rk->id }}">
                                {{ $rk->project->name ?? '-' }}
                                — {{ \Illuminate\Support\Str::limit($rk->description, 70) }}
                                @if($role === 'admin')
                                    — {{ $rk->user->name ?? '-' }}
                                @endif
                            </option>
                        @endif
                    @endforeach
                </select>

                <p class="text-xs text-gray-400 mt-1">
                    Daily Task hanya bisa ditambahkan untuk RK Anggota yang masih Draft atau Rejected.
                </p>
            </div>

            <!-- TANGGAL -->
            
<div class="mb-4">
    <label class="block text-sm font-semibold text-gray-700 mb-1">
        Tanggal
    </label>

    <input type="date"
    name="date"
    value="{{ old('date', now()->toDateString()) }}"
    min="{{ now()->toDateString() }}"
    required
    class="border w-full p-3 rounded-lg bg-white focus:ring focus:ring-green-100 focus:border-green-500">

    <p class="text-xs text-gray-400 mt-1">
        Tanggal pelaksanaan minimal hari ini. Tidak bisa memilih tanggal sebelum hari ini.
    </p>
</div>

            <!-- AKTIVITAS -->
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Aktivitas
                </label>

                <textarea name="activity"
                    required
                    rows="5"
                    placeholder="Tulis aktivitas/progres yang dikerjakan hari ini..."
                    class="border w-full p-3 rounded-lg focus:ring focus:ring-green-100 focus:border-green-500"></textarea>

                <p class="text-xs text-gray-400 mt-1">
                    Contoh: membersihkan data, membuat tabel, validasi output, koordinasi, atau revisi dokumen.
                </p>
            </div>

            <!-- OUTPUT HIDDEN LEGACY -->
            <input type="hidden" name="output" value="-">

            <!-- EVIDENCE URL -->
            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Link Bukti Kerja
                </label>

                <input name="evidence_url"
                    type="url"
                    placeholder="https://drive.google.com/..."
                    class="border w-full p-3 rounded-lg focus:ring focus:ring-green-100 focus:border-green-500">

                <p class="text-xs text-gray-400 mt-1">
                    Isi dengan link dokumen, spreadsheet, drive, foto bukti, atau file pendukung pekerjaan.
                </p>
            </div>

            <!-- INFO -->
            <div class="mb-5 p-4 rounded-xl bg-blue-50 border border-blue-100 text-sm text-blue-700">
                Daily Task menjadi bukti proses untuk RK Anggota. Setelah minimal satu Daily Task dibuat, RK Anggota bisa disubmit untuk review.
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

<!-- ================= VIEW MODAL ================= -->
<!-- ================= VIEW MODAL ================= -->
<div id="modalView"
    class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50">

    <div class="bg-white rounded-2xl w-[760px] max-h-[85vh] overflow-y-auto shadow-xl">

        <!-- HEADER -->
        <div class="px-6 py-4 border-b flex justify-between items-start">
            <div>
                <h3 class="text-xl font-bold text-gray-900">
                    Detail Daily Task
                </h3>
                <p class="text-sm text-gray-500 mt-1">
                    Detail progres harian, RK terkait, link bukti kerja, dan waktu pencatatan.
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


<!-- ================= EDIT MODAL ================= -->
@if($canManageDailyTask)
<div id="modalEdit"
    class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50">

    <div class="bg-white rounded-2xl w-[720px] max-h-[90vh] overflow-y-auto shadow-xl">

        <!-- HEADER -->
        <div class="px-6 py-4 border-b flex justify-between items-start">
            <div>
                <h3 class="text-xl font-bold text-gray-900">
                    Edit Daily Task
                </h3>

                <p class="text-sm text-gray-500 mt-1">
                    Perbarui aktivitas, tanggal pelaksanaan, dan link bukti kerja. Tanggal tidak boleh sebelum hari ini.
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

            <!-- TANGGAL -->
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Tanggal Pelaksanaan
                </label>

                <input id="edit_date"
                    type="date"
                    name="date"
                    min="{{ now()->toDateString() }}"
                    required
                    class="border w-full p-3 rounded-lg bg-white focus:ring focus:ring-yellow-100 focus:border-yellow-500">

                <p class="text-xs text-gray-400 mt-1">
                    Tanggal pelaksanaan minimal hari ini. Tidak bisa memilih tanggal sebelum hari ini.
                </p>
            </div>

            <!-- AKTIVITAS -->
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Aktivitas
                </label>

                <textarea id="edit_activity"
                    name="activity"
                    required
                    rows="5"
                    placeholder="Tulis aktivitas/progres yang dikerjakan..."
                    class="border w-full p-3 rounded-lg focus:ring focus:ring-yellow-100 focus:border-yellow-500"></textarea>

                <p class="text-xs text-gray-400 mt-1">
                    Contoh: analisis data, validasi tabel, revisi dokumen, koordinasi, atau penyusunan output.
                </p>
            </div>

            <!-- OUTPUT HIDDEN LEGACY -->
            <input type="hidden" id="edit_output" name="output" value="-">

            <!-- LINK BUKTI -->
            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Link Bukti Kerja
                </label>

                <input id="edit_evidence"
                    name="evidence_url"
                    type="url"
                    placeholder="https://drive.google.com/..."
                    class="border w-full p-3 rounded-lg focus:ring focus:ring-yellow-100 focus:border-yellow-500">

                <p class="text-xs text-gray-400 mt-1">
                    Gunakan link Google Drive, Spreadsheet, dokumen, foto bukti, atau file pendukung pekerjaan.
                </p>
            </div>

            <!-- INFO -->
            <div class="mb-5 p-4 rounded-xl bg-yellow-50 border border-yellow-100 text-sm text-yellow-700">
                Daily Task hanya bisa diedit selama RK Anggota masih berstatus Draft atau Rejected. Jika RK sudah Submitted atau Approved, task akan terkunci.
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


<!-- ================= SCRIPT ================= -->
<script>

const DAILY_TASK_BASE_PATH = @json($basePath);

function openModal(id){
    document.getElementById(id).classList.remove('hidden');
}

function closeModal(id){
    document.getElementById(id).classList.add('hidden');
}

function formatDate(dateString){
    if (!dateString) return '-';

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

function openViewModal(id){

    fetch(`${DAILY_TASK_BASE_PATH}/${id}`)
    .then(res => {
        if (!res.ok) {
            throw new Error('Gagal mengambil detail Daily Task.');
        }

        return res.json();
    })
    .then(data => {

        const status = data.rk_anggota?.status ?? '-';

        const statusClass = {
            draft: 'bg-gray-100 text-gray-700',
            submitted: 'bg-blue-100 text-blue-700',
            approved: 'bg-green-100 text-green-700',
            rejected: 'bg-red-100 text-red-700',
        }[status] ?? 'bg-gray-100 text-gray-700';

        const evidenceHtml = data.evidence_url
            ? `
                <a href="${data.evidence_url}"
                    target="_blank"
                    class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                    Buka Link Bukti
                </a>
            `
            : `
                <span class="text-gray-400">
                    Tidak ada link bukti
                </span>
            `;

        document.getElementById('viewContent').innerHTML = `
            <!-- TOP SUMMARY -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div class="p-4 rounded-xl bg-gray-50 border">
                    <div class="text-xs text-gray-500 mb-1">
                        Project
                    </div>
                    <div class="font-semibold text-gray-900">
                        ${data.rk_anggota?.project?.name ?? '-'}
                    </div>
                </div>

                <div class="p-4 rounded-xl bg-gray-50 border">
                    <div class="text-xs text-gray-500 mb-1">
                        Anggota
                    </div>
                    <div class="font-semibold text-gray-900">
                        ${data.rk_anggota?.user?.name ?? '-'}
                    </div>
                </div>

            </div>

            <!-- RK INFO -->
            <div class="mt-4 p-4 rounded-xl border bg-white">
                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3">
                    <div>
                        <div class="text-xs text-gray-500 mb-1">
                            RK Anggota
                        </div>
                        <div class="font-semibold text-gray-900">
                            ${data.rk_anggota?.description ?? '-'}
                        </div>
                    </div>

                    <span class="px-3 py-1 rounded-full text-xs font-semibold ${statusClass}">
                        ${status}
                    </span>
                </div>
            </div>

            <!-- DAILY TASK DETAIL -->
            <div class="mt-4 p-4 rounded-xl border bg-white">
                <div class="text-xs text-gray-500 mb-1">
                    Tanggal Pelaksanaan / Rencana Pelaksanaan
                </div>

                <div class="text-lg font-semibold text-gray-900 mb-4">
                    ${data.date ?? '-'}
                </div>

                <div class="text-xs text-gray-500 mb-1">
                    Aktivitas
                </div>

                <div class="text-gray-800 leading-relaxed whitespace-pre-line">
                    ${data.activity ?? '-'}
                </div>
            </div>

            <!-- EVIDENCE -->
            <div class="mt-4 p-4 rounded-xl bg-blue-50 border border-blue-100">
                <div class="text-xs text-blue-600 mb-2 font-semibold">
                    Link Bukti Kerja
                </div>

                ${evidenceHtml}
            </div>

            <!-- TIME AUDIT -->
            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">

                <div class="p-4 rounded-xl bg-gray-50 border">
                    <div class="text-xs text-gray-500 mb-1">
                        Jam Dibuat
                    </div>
                    <div class="font-semibold text-gray-800">
                        ${formatDate(data.created_at)}
                    </div>
                </div>

                <div class="p-4 rounded-xl bg-gray-50 border">
                    <div class="text-xs text-gray-500 mb-1">
                        Jam Update Terakhir
                    </div>
                    <div class="font-semibold text-gray-800">
                        ${formatDate(data.updated_at)}
                    </div>
                </div>

            </div>
        `;

        openModal('modalView');
    })
    .catch(() => {
        alert('Gagal mengambil detail Daily Task.');
    });
}


function openEditModal(id){
    fetch(`${DAILY_TASK_BASE_PATH}/${id}`)
    .then(res => {
        if (!res.ok) {
            throw new Error('Gagal mengambil data Daily Task.');
        }

        return res.json();
    })
    .then(data => {
        document.getElementById('formEdit').action =
            `${DAILY_TASK_BASE_PATH}/${id}`;

        document.getElementById('edit_date').value = data.date ?? '';
        document.getElementById('edit_activity').value = data.activity ?? '';
        document.getElementById('edit_output').value = data.output ?? '-';
        document.getElementById('edit_evidence').value = data.evidence_url ?? '';

        openModal('modalEdit');
    })
    .catch(() => {
        alert('Gagal mengambil data Daily Task.');
    });
}

</script>

</x-app-layout>