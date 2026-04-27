<x-app-layout>

@php
    $role = auth()->user()->role;

    $basePath = match($role) {
        'admin' => '/admin/daily-task',
        'anggota' => '/anggota/daily-task',
        'ketua' => '/ketua/daily-task',
        default => '/daily-task',
    };

    $canManageDailyTask = in_array($role, ['admin', 'anggota']);
@endphp

<div class="bg-white p-6 rounded-xl shadow">

    <!-- ================= HEADER ================= -->
    <div class="flex justify-between items-center mb-4">
        <div>
            <h2 class="text-xl font-bold">Daily Task</h2>
            <p class="text-sm text-gray-500 mt-1">
                Monitoring aktivitas harian. Daily Task adalah bukti proses, bukan penentu progress.
            </p>
        </div>

        <div class="flex gap-2">
            <a href="{{ url()->current() }}"
                class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">
                Refresh
            </a>

            @if($canManageDailyTask)
                <button onclick="openModal('modalCreate')"
                    class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
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
    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-2 mb-4">

        <input type="text"
            name="search"
            value="{{ request('search') }}"
            placeholder="Cari activity, output, atau RK Anggota..."
            class="border px-3 py-2 rounded md:col-span-3">

        <button class="bg-gray-800 text-white px-4 rounded">
            Filter
        </button>

    </form>

    <!-- ================= ROLE INFO ================= -->
    <div class="mb-4 p-3 bg-blue-50 text-blue-700 rounded text-sm">
        @if($role === 'admin')
            Mode Admin: kamu dapat mengelola semua Daily Task untuk kebutuhan administrasi.
        @elseif($role === 'anggota')
            Mode Anggota: kamu hanya dapat mengelola Daily Task milikmu selama RK Anggota masih Draft atau Rejected.
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
                    <th class="text-left p-2">Anggota</th>
                    <th class="text-left p-2">Tanggal</th>
                    <th class="text-left p-2">Activity</th>
                    <th class="text-left p-2">Output</th>
                    <th class="text-left p-2">Evidence</th>
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

                    $canEditThisTask = $canManageDailyTask
                        && in_array($rkStatus, ['draft', 'rejected']);
                @endphp

                <tr class="border-b hover:bg-gray-50">
                    <td class="p-2">
                        {{ $rk->project->name ?? '-' }}
                    </td>

                    <td class="p-2">
                        {{ $rk->description ?? '-' }}
                    </td>

                    <td class="p-2">
                        {{ $rk->user->name ?? '-' }}
                    </td>

                    <td class="p-2">
                        {{ $t->date ?? '-' }}
                    </td>

                    <td class="p-2">
                        {{ $t->activity }}
                    </td>

                    <td class="p-2">
                        {{ $t->output ?? '-' }}
                    </td>

                    <td class="p-2">
                        @if($t->evidence_url)
                            <a href="{{ $t->evidence_url }}"
                                target="_blank"
                                class="text-blue-600 underline">
                                Lihat Bukti
                            </a>
                        @else
                            -
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
                    <td colspan="9" class="text-center py-4 text-gray-500">
                        Belum ada Daily Task
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($tasks, 'links'))
        <div class="mt-4">
            {{ $tasks->links() }}
        </div>
    @endif

</div>


<!-- ================= CREATE MODAL ================= -->
@if($canManageDailyTask)
<div id="modalCreate"
    class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50">

    <div class="bg-white p-6 rounded w-[480px] shadow">

        <h3 class="font-bold mb-3">Tambah Daily Task</h3>

        <form method="POST" action="{{ $basePath }}">
            @csrf

            <label class="block text-sm font-medium mb-1">RK Anggota</label>
            <select name="rk_anggota_id"
                class="border w-full mb-2 p-2 rounded"
                required>
                <option value="">Pilih RK Anggota</option>

                @foreach($rkAnggotas as $rk)
                    @if(in_array($rk->status, ['draft', 'rejected']))
                        <option value="{{ $rk->id }}">
                            {{ $rk->description }}
                            - {{ $rk->user->name ?? '-' }}
                            - {{ $rk->project->name ?? '-' }}
                        </option>
                    @endif
                @endforeach
            </select>

            <label class="block text-sm font-medium mb-1">Tanggal</label>
            <input type="date"
                name="date"
                value="{{ now()->toDateString() }}"
                class="border w-full mb-2 p-2 rounded"
                required>

            <label class="block text-sm font-medium mb-1">Aktivitas</label>
            <textarea name="activity"
                placeholder="Aktivitas harian"
                class="border w-full mb-2 p-2 rounded"
                required></textarea>

            <label class="block text-sm font-medium mb-1">Output</label>
            <input name="output"
                placeholder="Output pekerjaan"
                class="border w-full mb-2 p-2 rounded">

            <label class="block text-sm font-medium mb-1">Evidence URL</label>
            <input name="evidence_url"
                placeholder="https://..."
                class="border w-full mb-3 p-2 rounded">

            <div class="flex justify-end gap-2">
                <button type="button"
                    onclick="closeModal('modalCreate')"
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
@endif


<!-- ================= VIEW MODAL ================= -->
<div id="modalView"
    class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50">

    <div class="bg-white p-6 rounded w-[560px] shadow max-h-[85vh] overflow-y-auto">

        <h3 class="font-bold mb-3">Detail Daily Task</h3>

        <div id="viewContent" class="space-y-2 text-sm"></div>

        <div class="flex justify-end mt-4">
            <button type="button"
                onclick="closeModal('modalView')"
                class="bg-gray-300 px-3 py-1 rounded">
                Close
            </button>
        </div>

    </div>
</div>


<!-- ================= EDIT MODAL ================= -->
@if($canManageDailyTask)
<div id="modalEdit"
    class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50">

    <div class="bg-white p-6 rounded w-[480px] shadow">

        <h3 class="font-bold mb-3">Edit Daily Task</h3>

        <form method="POST" id="formEdit">
            @csrf
            @method('PUT')

            <label class="block text-sm font-medium mb-1">Tanggal</label>
            <input id="edit_date"
                type="date"
                name="date"
                class="border w-full mb-2 p-2 rounded"
                required>

            <label class="block text-sm font-medium mb-1">Aktivitas</label>
            <textarea id="edit_activity"
                name="activity"
                class="border w-full mb-2 p-2 rounded"
                required></textarea>

            <label class="block text-sm font-medium mb-1">Output</label>
            <input id="edit_output"
                name="output"
                class="border w-full mb-2 p-2 rounded">

            <label class="block text-sm font-medium mb-1">Evidence URL</label>
            <input id="edit_evidence"
                name="evidence_url"
                class="border w-full mb-3 p-2 rounded">

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
    .then(res => res.json())
    .then(data => {

        let evidenceHtml = '-';

        if (data.evidence_url) {
            evidenceHtml = `
                <a href="${data.evidence_url}" target="_blank" class="text-blue-600 underline">
                    Lihat Bukti
                </a>
            `;
        }

        document.getElementById('viewContent').innerHTML = `
            <p><b>Project:</b> ${data.rk_anggota?.project?.name ?? '-'}</p>
            <p><b>RK Anggota:</b> ${data.rk_anggota?.description ?? '-'}</p>
            <p><b>Anggota:</b> ${data.rk_anggota?.user?.name ?? '-'}</p>
            <p><b>Status RK:</b> ${data.rk_anggota?.status ?? '-'}</p>
            <hr class="my-2">
            <p><b>Tanggal:</b> ${data.date ?? '-'}</p>
            <p><b>Activity:</b> ${data.activity ?? '-'}</p>
            <p><b>Output:</b> ${data.output ?? '-'}</p>
            <p><b>Evidence:</b> ${evidenceHtml}</p>
            <p><b>Dibuat:</b> ${formatDate(data.created_at)}</p>
            <p><b>Diupdate:</b> ${formatDate(data.updated_at)}</p>
        `;

        openModal('modalView');
    })
    .catch(() => {
        alert('Gagal mengambil detail Daily Task.');
    });
}

function openEditModal(id){

    fetch(`${DAILY_TASK_BASE_PATH}/${id}`)
    .then(res => res.json())
    .then(data => {

        document.getElementById('formEdit').action =
            `${DAILY_TASK_BASE_PATH}/${id}`;

        document.getElementById('edit_date').value = data.date ?? '';
        document.getElementById('edit_activity').value = data.activity ?? '';
        document.getElementById('edit_output').value = data.output ?? '';
        document.getElementById('edit_evidence').value = data.evidence_url ?? '';

        openModal('modalEdit');
    })
    .catch(() => {
        alert('Gagal mengambil data Daily Task.');
    });
}

</script>

</x-app-layout>