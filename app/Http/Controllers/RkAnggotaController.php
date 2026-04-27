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

    $query = RkAnggota::with([
        'project.team',
        'project.rkKetua.iku',
        'user',
        'dailyTasks',
        'approver',
    ]);

    // ROLE: ANGGOTA hanya lihat RK miliknya sendiri
    if ($user->role === 'anggota') {
        $query->where('user_id', $user->id);
    }

    // ROLE: KETUA hanya lihat RK dari project yang dia pimpin
    if ($user->role === 'ketua') {
        $query->whereHas('project', function ($q) use ($user) {
            $q->where('leader_id', $user->id);
        });
    }

    // FILTER PROJECT
    if ($request->project_id) {
        $query->where('project_id', $request->project_id);
    }

    // FILTER USER
    if ($request->user_id) {
        $query->where('user_id', $request->user_id);
    }

    // FILTER TAHUN
    if ($request->year) {
        $query->whereHas('project.rkKetua.iku', function ($q) use ($request) {
            $q->where('year', $request->year);
        });
    }

    // SEARCH
    if ($request->search) {
        $query->where(function ($q) use ($request) {
            $q->where('description', 'like', '%' . $request->search . '%')
                ->orWhereHas('project', function ($sub) use ($request) {
                    $sub->where('name', 'like', '%' . $request->search . '%');
                })
                ->orWhereHas('user', function ($sub) use ($request) {
                    $sub->where('name', 'like', '%' . $request->search . '%');
                });
        });
    }

    $rkAnggotas = $query
        ->latest()
        ->paginate(10)
        ->withQueryString();

    // Dropdown user
    if ($user->role === 'anggota') {
        $users = User::where('id', $user->id)->get();
    } else {
        $users = User::where('role', 'anggota')->get();
    }

    // Dropdown project
    $projectQuery = Project::with('team');

    if ($user->role === 'ketua') {
        $projectQuery->where('leader_id', $user->id);
    }

    if ($user->role === 'anggota') {
        $projectQuery->whereHas('rkAnggotas', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });
    }

    $projects = $projectQuery->get();

    return view('rk_anggota.index', compact('rkAnggotas', 'projects', 'users'));
}

    public function store(Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'user_id' => 'required|exists:users,id',
            'description' => 'required'
        ]);

        $project = Project::with('members')->findOrFail($request->project_id);

        // 🔒 HARUS ANGGOTA PROJECT
        if (!$project->members->contains('id', $request->user_id)) {
            return back()->with('error','User bukan anggota project');
        }

        // 🔒 CEK DUPLIKAT
        $exists = RkAnggota::where('project_id',$request->project_id)
            ->where('user_id',$request->user_id)
            ->exists();

        if ($exists) {
            return back()->with('error','Sudah ada RK Anggota');
        }

        RkAnggota::create([
    'project_id' => $request->project_id,
    'user_id' => $request->user_id,
    'description' => $request->description,
    'status' => RkAnggota::STATUS_DRAFT,
]);

        return back()->with('success','RK Anggota dibuat');
    }


   public function update(Request $request, $id)
{
    $rk = RkAnggota::findOrFail($id);

    if (!$rk->isEditable()) {
        return back()->with('error', 'RK Anggota yang sudah disubmit atau disetujui tidak bisa diubah.');
    }

    $request->validate([
        'project_id' => 'required|exists:projects,id',
        'user_id' => 'required|exists:users,id',
        'description' => 'required'
    ]);

    $rk->update([
        'project_id' => $request->project_id,
        'user_id' => $request->user_id,
        'description' => $request->description
    ]);

    return back()->with('success','RK Anggota diupdate');
}

public function submit($id)
{
    $rk = RkAnggota::with('dailyTasks')->findOrFail($id);

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
    $rk = RkAnggota::findOrFail($id);

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

    $rk = RkAnggota::findOrFail($id);

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
    $rk = RkAnggota::findOrFail($id);

    if (!$rk->isEditable()) {
        return back()->with('error', 'RK Anggota yang sudah disubmit atau disetujui tidak bisa dihapus.');
    }

    $rk->delete();

    return back()->with('success','RK Anggota dihapus');
}


  public function show($id)
{
    return response()->json(
        RkAnggota::with([
            'project.team',
            'project.rkKetua.iku',
            'user',
            'dailyTasks',
            'approver'
        ])->findOrFail($id)
    );
}
}