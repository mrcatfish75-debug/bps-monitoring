<?php

namespace App\Http\Controllers;

use App\Models\RkAnggota;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;

class RkAnggotaController extends Controller
{
   public function index(Request $request)
{
    $user = auth()->user();

    /*
    |--------------------------------------------------------------------------
    | Mode Context
    |--------------------------------------------------------------------------
    | Role ketua punya dua konteks:
    |
    | /ketua/rk-anggota
    | - mode ketua
    | - melihat RK Anggota dari project yang dia pimpin
    |
    | /ketua/rk-anggota?mode=mine
    | - mode pekerjaan saya
    | - melihat RK Anggota miliknya sendiri sebagai anggota project lain
    */
    $isMineMode = $user->role === 'ketua' && $request->mode === 'mine';

    $query = RkAnggota::with([
        'project.team',
        'project.rkKetua.iku',
        'user',
        'dailyTasks',
        'approver',
    ]);

    /*
    |--------------------------------------------------------------------------
    | Role Scope
    |--------------------------------------------------------------------------
    | Admin:
    | - melihat semua RK Anggota.
    |
    | Anggota:
    | - hanya melihat RK miliknya sendiri.
    |
    | Ketua mode normal:
    | - melihat RK Anggota dari project yang dia pimpin.
    |
    | Ketua mode mine:
    | - melihat RK Anggota miliknya sendiri, walaupun project dipimpin orang lain.
    */
    if ($user->role === 'anggota' || $isMineMode) {
        $query->where('user_id', $user->id);
    } elseif ($user->role === 'ketua') {
        $query->whereHas('project', function ($q) use ($user) {
            $q->where('leader_id', $user->id);
        });
    }

    // FILTER PROJECT
    if ($request->filled('project_id')) {
        $query->where('project_id', $request->project_id);
    }

    // FILTER USER
    // Hanya admin dan ketua mode normal yang boleh filter berdasarkan user.
    // Anggota dan ketua mode mine selalu dipaksa melihat RK miliknya sendiri.
    if (
        $request->filled('user_id')
        && !($user->role === 'anggota' || $isMineMode)
    ) {
        $query->where('user_id', $request->user_id);
    }

    // FILTER TAHUN
    if ($request->filled('year')) {
        $query->whereHas('project.rkKetua.iku', function ($q) use ($request) {
            $q->where('year', $request->year);
        });
    }

    // SEARCH
    if ($request->filled('search')) {
        $search = $request->search;

        $query->where(function ($q) use ($search) {
            $q->where('description', 'like', '%' . $search . '%')
                ->orWhereHas('project', function ($sub) use ($search) {
                    $sub->where('name', 'like', '%' . $search . '%');
                })
                ->orWhereHas('user', function ($sub) use ($search) {
                    $sub->where('name', 'like', '%' . $search . '%');
                });
        });
    }

    $rkAnggotas = $query
        ->latest()
        ->paginate(10)
        ->withQueryString();

    /*
    |--------------------------------------------------------------------------
    | Dropdown User
    |--------------------------------------------------------------------------
    | Anggota dan ketua mode mine hanya boleh memilih dirinya sendiri.
    | Admin dan ketua mode normal dapat melihat pegawai operasional.
    */
    if ($user->role === 'anggota' || $isMineMode) {
        $users = User::where('id', $user->id)->get();
    } else {
        $users = User::whereIn('role', ['anggota', 'ketua'])
            ->orderBy('name')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Dropdown Project
    |--------------------------------------------------------------------------
    | Admin:
    | - semua project.
    |
    | Ketua mode normal:
    | - project yang dia pimpin.
    |
    | Anggota / Ketua mode mine:
    | - project tempat user menjadi member di project_members.
    |
    | Catatan penting:
    | Jangan pakai whereHas('rkAnggotas') di mode mine,
    | karena user belum bisa membuat RK pertama kalau project hanya muncul
    | setelah RK sudah ada.
    */
    $projectQuery = Project::with(['team', 'members', 'rkKetua.iku']);

    if ($user->role === 'ketua' && !$isMineMode) {
        $projectQuery->where('leader_id', $user->id);
    }

    if ($user->role === 'anggota' || $isMineMode) {
        $projectQuery->whereHas('members', function ($q) use ($user) {
            $q->where('users.id', $user->id);
        });
    }

    $projects = $projectQuery
        ->latest()
        ->get();

    return view('rk_anggota.index', compact(
        'rkAnggotas',
        'projects',
        'users'
    ));
}


    public function store(Request $request)
{
    $request->validate([
        'project_id' => 'required|exists:projects,id',
        'user_id' => 'required|exists:users,id',
        'description' => 'required|string',
    ]);

    $user = auth()->user();

    $project = Project::with('members')->findOrFail($request->project_id);
    $assignee = User::findOrFail($request->user_id);

    /*
    |--------------------------------------------------------------------------
    | Authorization Create RK Anggota
    |--------------------------------------------------------------------------
    | Admin:
    | - boleh membuat RK Anggota untuk siapa pun selama user valid dan
    |   menjadi member project.
    |
    | Anggota / Ketua mode pribadi:
    | - hanya boleh membuat RK untuk dirinya sendiri.
    |
    | Catatan:
    | - Tidak ada duplicate check project_id + user_id.
    | - Artinya 1 user boleh punya banyak RK Anggota pada project yang sama.
    */
    if ($user->role !== 'admin') {
        if ((int) $request->user_id !== (int) $user->id) {
            abort(403, 'Kamu hanya boleh membuat RK Anggota untuk dirimu sendiri.');
        }
    }

    $this->ensureValidAssigneeRole($assignee);
    $this->ensureUserIsProjectMember($project, $assignee->id);

    RkAnggota::create([
        'project_id' => $project->id,
        'user_id' => $assignee->id,
        'description' => $request->description,
        'status' => RkAnggota::STATUS_DRAFT,
    ]);

    return back()->with('success', 'RK Anggota dibuat.');
}



public function update(Request $request, $id)
{
    $rk = RkAnggota::with('project')->findOrFail($id);

    $this->authorizeManageRk($rk);

    if (!$rk->isEditable()) {
        return back()->with('error', 'RK Anggota yang sudah disubmit atau disetujui tidak bisa diubah.');
    }

    $request->validate([
        'project_id' => 'required|exists:projects,id',
        'user_id' => 'required|exists:users,id',
        'description' => 'required|string',
    ]);

    $user = auth()->user();

    $project = Project::with('members')->findOrFail($request->project_id);
    $assignee = User::findOrFail($request->user_id);

    /*
    |--------------------------------------------------------------------------
    | Authorization Update RK Anggota
    |--------------------------------------------------------------------------
    | Admin:
    | - boleh mengubah semua RK.
    |
    | Non-admin:
    | - hanya boleh mengubah RK miliknya sendiri.
    | - tidak boleh mengganti user_id menjadi user lain.
    |
    | Catatan:
    | - Tidak ada duplicate check project_id + user_id.
    | - Artinya 1 user boleh punya banyak RK Anggota pada project yang sama.
    */
    if ($user->role !== 'admin') {
        if ((int) $request->user_id !== (int) $user->id) {
            abort(403, 'Kamu hanya boleh mengubah RK Anggota milikmu sendiri.');
        }

        if ((int) $rk->user_id !== (int) $user->id) {
            abort(403, 'Kamu hanya boleh mengubah RK Anggota milikmu sendiri.');
        }
    }

    $this->ensureValidAssigneeRole($assignee);
    $this->ensureUserIsProjectMember($project, $assignee->id);

    $rk->update([
        'project_id' => $project->id,
        'user_id' => $assignee->id,
        'description' => $request->description,
    ]);

    return back()->with('success', 'RK Anggota diupdate.');
}



    public function submit($id)
    {
        $rk = RkAnggota::with(['dailyTasks', 'project'])->findOrFail($id);

        $this->authorizeSubmitRk($rk);

        if (!$rk->canSubmit()) {
            return back()->with('error', 'RK Anggota ini tidak bisa disubmit.');
        }

        if ($rk->dailyTasks->count() === 0) {
            return back()->with('error', 'Isi minimal satu Daily Task sebelum submit RK Anggota.');
        }

        $rk->update([
            'status' => RkAnggota::STATUS_SUBMITTED,
            'submitted_at' => now(),
            'approved_at' => null,
            'approved_by' => null,
            'rejection_note' => null,
        ]);

        return back()->with('success', 'RK Anggota berhasil disubmit untuk review ketua.');
    }

    public function approve($id)
    {
        $rk = RkAnggota::with('project')->findOrFail($id);

        $this->authorizeReviewRk($rk);

        if (!$rk->canBeReviewed()) {
            return back()->with('error', 'Hanya RK Anggota berstatus submitted yang bisa disetujui.');
        }

        $rk->update([
            'status' => RkAnggota::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => auth()->id(),
            'rejection_note' => null,
        ]);

        return back()->with('success', 'RK Anggota berhasil disetujui.');
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejection_note' => 'required|string|max:1000',
        ]);

        $rk = RkAnggota::with('project')->findOrFail($id);

        $this->authorizeReviewRk($rk);

        if (!$rk->canBeReviewed()) {
            return back()->with('error', 'Hanya RK Anggota berstatus submitted yang bisa ditolak.');
        }

        $rk->update([
            'status' => RkAnggota::STATUS_REJECTED,
            'approved_at' => null,
            'approved_by' => null,
            'rejection_note' => $request->rejection_note,
        ]);

        return back()->with('success', 'RK Anggota berhasil ditolak.');
    }

    public function destroy($id)
    {
        $rk = RkAnggota::with('project')->findOrFail($id);

        $this->authorizeManageRk($rk);

        if (!$rk->isEditable()) {
            return back()->with('error', 'RK Anggota yang sudah disubmit atau disetujui tidak bisa dihapus.');
        }

        $rk->delete();

        return back()->with('success', 'RK Anggota dihapus.');
    }

    public function show($id)
    {
        $rk = RkAnggota::with([
            'project.team',
            'project.rkKetua.iku',
            'project.leader',
            'user',
            'dailyTasks',
            'approver',
        ])->findOrFail($id);

        $this->authorizeViewRk($rk);

        return response()->json($rk);
    }

    /*
    |--------------------------------------------------------------------------
    | Authorization Helpers
    |--------------------------------------------------------------------------
    | Helper ini sengaja dibuat eksplisit supaya bug akses gampang dilacak.
    |--------------------------------------------------------------------------
    */

    private function authorizeViewRk(RkAnggota $rk): void
    {
        $user = auth()->user();

        if ($user->role === 'admin') {
            return;
        }

        // Pemilik RK boleh melihat RK miliknya.
        if ($rk->user_id === $user->id) {
            return;
        }

        // Ketua project boleh melihat RK dari project yang dia pimpin.
        if ($rk->project && $rk->project->leader_id === $user->id) {
            return;
        }

        abort(403, 'Akses ditolak. Kamu tidak berhak melihat RK Anggota ini.');
    }

    private function authorizeManageRk(RkAnggota $rk): void
{
    $user = auth()->user();

    if ($user->role === 'admin') {
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Manage RK = create/update/delete data RK.
    |
    | Admin:
    | - boleh manage semua.
    |
    | Pemilik RK:
    | - boleh manage RK miliknya sendiri selama status masih editable.
    |
    | Ketua leader project:
    | - tidak otomatis boleh edit/delete RK milik anggota.
    | - tugas ketua di mode normal adalah review/approve/reject.
    |--------------------------------------------------------------------------
    */
    if ((int) $rk->user_id === (int) $user->id) {
        return;
    }

    abort(403, 'Akses ditolak. Kamu hanya boleh mengelola RK Anggota milikmu sendiri.');
}


    private function authorizeSubmitRk(RkAnggota $rk): void
    {
        $user = auth()->user();

        if ($user->role === 'admin') {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Submit adalah aksi milik pemilik RK.
        | Ini mendukung kasus user role ketua yang menjadi anggota di project lain,
        | karena yang dicek adalah rk.user_id, bukan role.
        |--------------------------------------------------------------------------
        */
        if ($rk->user_id === $user->id) {
            return;
        }

        abort(403, 'Akses ditolak. Kamu hanya boleh submit RK Anggota milikmu sendiri.');
    }

   private function authorizeReviewRk(RkAnggota $rk): void
{
    $user = auth()->user();

    if ($user->role === 'admin') {
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Cegah Self Approval
    |--------------------------------------------------------------------------
    | Ketua tidak boleh approve/reject RK miliknya sendiri, walaupun dia leader
    | project tersebut. Ini mencegah konflik kepentingan.
    |--------------------------------------------------------------------------
    */
    if ((int) $rk->user_id === (int) $user->id) {
        abort(403, 'Kamu tidak boleh review RK Anggota milikmu sendiri.');
    }

    /*
    |--------------------------------------------------------------------------
    | Review = approve/reject.
    | Hanya leader project yang boleh approve/reject RK Anggota.
    |--------------------------------------------------------------------------
    */
    if (
        $user->role === 'ketua'
        && $rk->project
        && (int) $rk->project->leader_id === (int) $user->id
    ) {
        return;
    }

    abort(403, 'Akses ditolak. Kamu hanya boleh review RK Anggota dari project yang kamu pimpin.');
}

    private function ensureUserIsProjectMember(Project $project, int $userId): void
    {
        if (!$project->members->contains('id', $userId)) {
            abort(422, 'User bukan anggota project. RK Anggota hanya bisa dibuat untuk user yang ada di project_members.');
        }
    }

    private function ensureValidAssigneeRole(User $user): void
    {
        if (!in_array($user->role, ['anggota', 'ketua'])) {
            abort(422, 'User tidak valid sebagai anggota project. Hanya role anggota atau ketua yang boleh diberi RK Anggota.');
        }
    }
}