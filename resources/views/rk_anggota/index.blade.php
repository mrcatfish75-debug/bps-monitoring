<x-app-layout>

@php
    $role = auth()->user()->role;

    $basePath = match($role) {
        'admin' => '/admin/rk-anggota',
        'anggota' => '/anggota/rk-anggota',
        'ketua' => '/ketua/rk-anggota',
        default => '/rk-anggota',
    };
@endphp

<div class="bg-white p-6 rounded-xl shadow">

    <!-- ================= HEADER ================= -->
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">Rencana Kinerja - Anggota</h2>

        <div class="flex gap-2">
            <a href="{{ url()->current() }}" class="px-4 py-2 bg-gray-200 rounded">
                Refresh
            </a>

            @if($role === 'admin')
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
    <form method="GET" class="grid grid-cols-4 gap-2 mb-4">

        <!-- PROJECT -->
        <select name="project_id" class="border px-3 py-2 rounded">
            <option value="">Project</option>
            @foreach($projects as $p)
                <option value="{{ $p->id }}"
                    {{ request('project_id') == $p->id ? 'selected' : '' }}>
                    {{ $p->name }}
                </option>
            @endforeach
        </select>

        <!-- USER -->
        <select name="user_id" class="border px-3 py-2 rounded">
            <option value="">Anggota</option>
            @foreach($users as $u)
                <option value="{{ $u->id }}"
                    {{ request('user_id') == $u->id ? 'selected' : '' }}>
                    {{ $u->name }}
                </option>
            @endforeach
        </select>

        <!-- SEARCH -->
        <input type="text" name="search"
            value="{{ request('search') }}"
            placeholder="Pencarian..."
            class="border px-3 py-2 rounded">

        <button class="bg-gray-800 text-white px-4 rounded">
            Filter
        </button>

    </form>

    <!-- ================= TABLE ================= -->
    <table class="w-full text-sm">

        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="text-left p-2">Project</th>
                <th class="text-left p-2">Tim</th>
                <th class="text-left p-2">Anggota</th>
                <th class="text-left p-2">Rencana Kinerja</th>
                <th class="text-left p-2">Status</th>
                <th class="text-left p-2">Progress</th>
                <th class="text-left p-2">Aksi</th>
            </tr>
        </thead>

        <tbody>
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

                /*
                |--------------------------------------------------------------------------
                | Role-aware action rules
                |--------------------------------------------------------------------------
                | Admin:
                | - boleh CRUD/testing workflow.
                |
                | Anggota:
                | - boleh submit RK miliknya sendiri saat draft/rejected.
                | - edit/delete tidak ditampilkan di sini karena route update/delete anggota
                |   belum aktif. Kalau nanti mau diaktifkan, kita tambah route + guard controller.
                |
                | Ketua:
                | - tidak boleh edit/delete/submit.
                | - hanya approve/reject saat status submitted.
                */
                $canEditDelete = $role === 'admin' && $rk->isEditable();

                $canSubmit = $rk->canSubmit()
                    && (
                        $role === 'admin'
                        || ($role === 'anggota' && $rk->user_id === auth()->id())
                    );

                $canReview = $rk->canBeReviewed()
                    && in_array($role, ['admin', 'ketua']);

                $submitAction = $role === 'admin'
                    ? route('admin.rk-anggota.submit', $rk->id)
                    : route('anggota.rk-anggota.submit', $rk->id);

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
                <td class="p-2">{{ $rk->user->name ?? '-' }}</td>

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

                <!-- ================= AKSI ================= -->
                <td class="p-2">
                    <div class="flex flex-wrap gap-2">

                        <!-- VIEW -->
                        <button type="button"
                            onclick="openViewModal({{ $rk->id }})"
                            class="text-blue-500">
                            View
                        </button>

                        <!-- EDIT: admin only, draft/rejected only -->
                        @if($canEditDelete)
                            <button type="button"
                                onclick="openEditModal({{ $rk->id }})"
                                class="text-yellow-500">
                                Edit
                            </button>
                        @endif

                        <!-- DELETE: admin only, draft/rejected only -->
                        @if($canEditDelete)
                            <form method="POST"
                                action="{{ route('rk-anggota.destroy', $rk->id) }}"
                                class="inline"
                                onsubmit="return confirm('Yakin ingin menghapus RK Anggota ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-500">
                                    Delete
                                </button>
                            </form>
                        @endif

                        <!-- SUBMIT: admin or anggota owner, draft/rejected only -->
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

                        <!-- APPROVE: admin/ketua, submitted only -->
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

                        <!-- REJECT: admin/ketua, submitted only -->
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
                <td colspan="7" class="text-center py-4">
                    Tidak ada data
                </td>
            </tr>
        @endforelse
        </tbody>

    </table>

    <div class="mt-4">
        {{ $rkAnggotas->links() }}
    </div>

</div>

<!-- ================= VIEW MODAL ================= -->
<div id="modalView"
    class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50">

    <div class="bg-white p-6 rounded w-[620px] max-h-[85vh] overflow-y-auto shadow">

        <h3 class="text-lg font-semibold mb-3">Detail RK Anggota</h3>

        <div id="viewContent"></div>

        <div class="flex justify-end mt-4">
            <button type="button"
                onclick="closeModal('modalView')"
                class="px-4 py-1 bg-gray-300 rounded">
                Close
            </button>
        </div>

    </div>
</div>

<!-- ================= CREATE MODAL ================= -->
@if($role === 'admin')
<div id="modalCreate"
    class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50">

    <div class="bg-white p-6 rounded w-[420px] shadow">

        <h3 class="text-lg font-semibold mb-3">Tambah RK Anggota</h3>

        <form method="POST" action="{{ route('rk-anggota.store') }}">
            @csrf

            <!-- PROJECT -->
            <select id="create_project" name="project_id"
                class="border w-full mb-2 p-2 rounded">
                <option value="">Pilih Project</option>
                @foreach($projects as $p)
                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                @endforeach
            </select>

            <!-- USER -->
            <select id="create_user" name="user_id"
                class="border w-full mb-2 p-2 rounded">
                <option value="">Pilih Anggota</option>
            </select>

            <!-- DESKRIPSI -->
            <textarea name="description"
                placeholder="Deskripsi tugas anggota"
                class="border w-full mb-4 p-2 rounded"></textarea>

            <!-- BUTTON -->
            <div class="flex justify-end gap-2">

                <button type="button"
                    onclick="closeModal('modalCreate')"
                    class="px-4 py-1 bg-gray-300 rounded hover:bg-gray-400">
                    Cancel
                </button>

                <button class="bg-green-600 text-white px-4 py-1 rounded">
                    Save
                </button>

            </div>

        </form>

    </div>
</div>
@endif

<!-- ================= EDIT MODAL ================= -->
@if($role === 'admin')
<div id="modalEdit"
    class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50">

    <div class="bg-white p-6 rounded w-[420px] shadow">

        <h3 class="text-lg font-semibold mb-3">Edit RK Anggota</h3>

        <form method="POST" id="formEdit">
            @csrf
            @method('PUT')

            <!-- PROJECT -->
            <select id="edit_project" name="project_id"
                class="border w-full mb-2 p-2 rounded">
                @foreach($projects as $p)
                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                @endforeach
            </select>

            <!-- USER -->
            <select id="edit_user" name="user_id"
                class="border w-full mb-2 p-2 rounded">
            </select>

            <!-- DESKRIPSI -->
            <textarea id="edit_description"
                name="description"
                class="border w-full mb-4 p-2 rounded"></textarea>

            <!-- BUTTON -->
            <div class="flex justify-end gap-2">
                <button type="button"
                    onclick="closeModal('modalEdit')"
                    class="px-3 py-1 bg-gray-300 rounded">
                    Cancel
                </button>

                <button class="bg-yellow-500 text-white px-4 py-1 rounded">
                    Update
                </button>
            </div>

        </form>

    </div>
</div>
@endif

<!-- ================= REJECT MODAL ================= -->
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

<!-- ================= SCRIPT ================= -->
<script>

const RK_ANGGOTA_BASE_PATH = @json($basePath);

function openCreateModal(){
    const modal = document.getElementById('modalCreate');

    if (modal) {
        modal.classList.remove('hidden');
    }
}

function openEditModal(id){

    fetch(`/admin/rk-anggota/${id}`)
    .then(res => res.json())
    .then(data => {

        const modal = document.getElementById('modalEdit');

        if (!modal) {
            return;
        }

        modal.classList.remove('hidden');

        // SET ACTION
        document.getElementById('formEdit').action =
            `/admin/rk-anggota/${data.id}`;

        // SET PROJECT
        document.getElementById('edit_project').value = data.project_id;

        // LOAD USER SESUAI PROJECT
        fetch(`/admin/project/${data.project_id}`)
        .then(res => res.json())
        .then(p => {

            let select = document.getElementById('edit_user');
            select.innerHTML = '';

            if (!p.members || p.members.length === 0) {
                select.innerHTML = '<option value="">Tidak ada anggota</option>';
                return;
            }

            p.members.forEach(m => {

                let selected = (m.id == data.user_id) ? 'selected' : '';

                select.innerHTML += `
                    <option value="${m.id}" ${selected}>
                        ${m.name}
                    </option>`;
            });

        });

        // SET DESKRIPSI
        document.getElementById('edit_description').value = data.description;

    });
}

function openViewModal(id){
    fetch(`${RK_ANGGOTA_BASE_PATH}/${id}`)
    .then(res => res.json())
    .then(data => {

        let dailyTasksHtml = '';

        if (data.daily_tasks && data.daily_tasks.length > 0) {
            dailyTasksHtml = `
                <div class="mt-4">
                    <h4 class="font-semibold mb-2">Daily Task / Bukti Proses</h4>
                    <div class="border rounded">
                        ${data.daily_tasks.map(task => `
                            <div class="p-3 border-b last:border-b-0">
                                <p><b>Activity:</b> ${task.activity ?? '-'}</p>
                                <p><b>Output:</b> ${task.output ?? '-'}</p>
                                <p><b>Status:</b> ${task.status ?? '-'}</p>
                                ${task.evidence_url ? `<p><b>Evidence:</b> <a class="text-blue-600 underline" href="${task.evidence_url}" target="_blank">Lihat Bukti</a></p>` : ''}
                                ${task.date ? `<p><b>Tanggal:</b> ${task.date}</p>` : ''}
                                ${task.created_at ? `<p><b>Dibuat:</b> ${formatDate(task.created_at)}</p>` : ''}
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        } else {
            dailyTasksHtml = `
                <div class="mt-4 p-3 bg-yellow-50 text-yellow-700 rounded">
                    Belum ada Daily Task untuk RK Anggota ini.
                </div>
            `;
        }

        let rejectionHtml = '';

        if (data.status === 'rejected' && data.rejection_note) {
            rejectionHtml = `
                <p class="text-red-600">
                    <b>Catatan Penolakan:</b> ${data.rejection_note}
                </p>
            `;
        }

        let approverHtml = '';

        if (data.approver) {
            approverHtml = `
                <p><b>Approved By:</b> ${data.approver.name}</p>
            `;
        }

        document.getElementById('viewContent').innerHTML = `
            <div class="space-y-2">
                <p><b>IKU:</b> ${data.project?.rk_ketua?.iku?.name ?? '-'}</p>
                <p><b>Project:</b> ${data.project?.name ?? '-'}</p>
                <p><b>Tim:</b> ${data.project?.team?.name ?? '-'}</p>
                <p><b>Anggota:</b> ${data.user?.name ?? '-'}</p>
                <p><b>Deskripsi:</b> ${data.description ?? '-'}</p>
                <p><b>Status:</b> ${data.status ?? '-'}</p>
                ${data.submitted_at ? `<p><b>Submitted At:</b> ${formatDate(data.submitted_at)}</p>` : ''}
                ${data.approved_at ? `<p><b>Approved At:</b> ${formatDate(data.approved_at)}</p>` : ''}
                ${approverHtml}
                ${rejectionHtml}
            </div>

            ${dailyTasksHtml}
        `;

        document.getElementById('modalView').classList.remove('hidden');
    })
    .catch(() => {
        alert('Gagal mengambil detail RK Anggota.');
    });
}

function openRejectModal(id, actionUrl){
    document.getElementById('formReject').action = actionUrl;
    document.getElementById('modalReject').classList.remove('hidden');
}

function closeModal(id){
    const modal = document.getElementById(id);

    if (modal) {
        modal.classList.add('hidden');
    }
}

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

document.addEventListener('DOMContentLoaded', function(){

    let createProject = document.getElementById('create_project');

    if (createProject) {
        createProject.addEventListener('change', function(){

            let projectId = this.value;

            let select = document.getElementById('create_user');
            select.innerHTML = '<option value="">Pilih Anggota</option>';

            if (!projectId) return;

            fetch(`/admin/project/${projectId}`)
            .then(res => res.json())
            .then(data => {

                // RESET
                select.innerHTML = '<option value="">Pilih Anggota</option>';

                if (!data.members || data.members.length === 0) {
                    select.innerHTML = '<option value="">Tidak ada anggota</option>';
                    return;
                }

                data.members.forEach(m => {
                    select.innerHTML += `
                        <option value="${m.id}">
                            ${m.name}
                        </option>
                    `;
                });

            });

        });
    }

});

</script>

</x-app-layout>