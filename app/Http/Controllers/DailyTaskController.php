<?php

namespace App\Http\Controllers;

use App\Models\DailyTask;
use App\Models\RkAnggota;
use Illuminate\Http\Request;

class DailyTaskController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        if (!in_array($user->role, ['admin', 'anggota', 'ketua'])) {
    abort(403, 'Role tidak diizinkan mengakses Daily Task.');
}

        /*
        |--------------------------------------------------------------------------
        | RK Anggota list untuk dropdown create
        |--------------------------------------------------------------------------
        | Admin melihat semua RK yang masih bisa diisi task.
        | Anggota hanya melihat RK miliknya.
        | Ketua tidak perlu create task, tapi tetap aman kalau view butuh variable.
        */
        $rkQuery = RkAnggota::with(['project', 'user'])
            ->whereIn('status', [
                RkAnggota::STATUS_DRAFT,
                RkAnggota::STATUS_REJECTED,
            ]);

        if ($user->role === 'anggota') {
            $rkQuery->where('user_id', $user->id);
        }

        if ($user->role === 'ketua') {
            $rkQuery->whereHas('project', function ($q) use ($user) {
                $q->where('leader_id', $user->id);
            });
        }

        if ($request->search) {
            $rkQuery->where('description', 'like', '%' . $request->search . '%');
        }

        $rkAnggotas = $rkQuery->limit(50)->get();

        /*
        |--------------------------------------------------------------------------
        | Daily Task list
        |--------------------------------------------------------------------------
        | Admin melihat semua.
        | Anggota hanya melihat task miliknya.
        | Ketua melihat task dari project yang dia pimpin.
        */
        $taskQuery = DailyTask::with([
            'rkAnggota.project',
            'rkAnggota.user',
        ]);

        if ($user->role === 'anggota') {
            $taskQuery->whereHas('rkAnggota', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        if ($user->role === 'ketua') {
            $taskQuery->whereHas('rkAnggota.project', function ($q) use ($user) {
                $q->where('leader_id', $user->id);
            });
        }

        if ($request->search) {
            $taskQuery->where(function ($q) use ($request) {
                $q->where('activity', 'like', '%' . $request->search . '%')
                    ->orWhere('output', 'like', '%' . $request->search . '%')
                    ->orWhereHas('rkAnggota', function ($sub) use ($request) {
                        $sub->where('description', 'like', '%' . $request->search . '%');
                    });
            });
        }

        $tasks = $taskQuery
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('daily_task.index', compact('rkAnggotas', 'tasks'));
    }

    public function create()
    {
        $user = auth()->user();
        if ($user->role === 'ketua') {
    abort(403, 'Ketua hanya dapat memonitor Daily Task.');
}

        $query = RkAnggota::with(['project', 'user'])
            ->whereIn('status', [
                RkAnggota::STATUS_DRAFT,
                RkAnggota::STATUS_REJECTED,
            ]);

        if ($user->role === 'anggota') {
            $query->where('user_id', $user->id);
        }

        $rkAnggotas = $query->get();

        return view('daily_task.create', compact('rkAnggotas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'rk_anggota_id' => 'required|exists:rk_anggotas,id',
            'date' => 'nullable|date',
            'activity' => 'required|string',
            'output' => 'nullable|string',
            'evidence_url' => 'nullable|url',
        ]);

        $user = auth()->user();

        $rk = RkAnggota::with('project')->findOrFail($request->rk_anggota_id);

        /*
        |--------------------------------------------------------------------------
        | Authorization
        |--------------------------------------------------------------------------
        | Admin boleh input untuk siapa pun.
        | Anggota hanya boleh input untuk RK miliknya.
        | Ketua tidak boleh input Daily Task.
        */
        if ($user->role === 'anggota' && $rk->user_id !== $user->id) {
            abort(403, 'Akses ditolak.');
        }

        if ($user->role === 'ketua') {
            abort(403, 'Ketua hanya dapat memonitor Daily Task.');
        }

        /*
        |--------------------------------------------------------------------------
        | Status lock
        |--------------------------------------------------------------------------
        | Daily Task hanya boleh dibuat saat RK masih draft/rejected.
        */
        if (!in_array($rk->status, [
            RkAnggota::STATUS_DRAFT,
            RkAnggota::STATUS_REJECTED,
        ])) {
            return back()->with('error', 'Daily Task tidak bisa ditambahkan karena RK Anggota sudah disubmit atau disetujui.');
        }

        DailyTask::create([
            'rk_anggota_id' => $rk->id,
            'date' => $request->date ?? now()->toDateString(),
            'activity' => $request->activity,
            'output' => $request->output,
            'evidence_url' => $request->evidence_url,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Daily Task berhasil dibuat.');
    }

   public function show($id)
{
    $task = DailyTask::with([
        'rkAnggota.project.team',
        'rkAnggota.project.rkKetua.iku',
        'rkAnggota.user',
    ])->findOrFail($id);

    $this->authorizeViewTask($task);

    return response()->json($task);
}

    public function update(Request $request, $id)
    {
        $request->validate([
            'date' => 'nullable|date',
            'activity' => 'required|string',
            'output' => 'nullable|string',
            'evidence_url' => 'nullable|url',
        ]);

        $task = DailyTask::with('rkAnggota')->findOrFail($id);

        $this->authorizeManageTask($task);

        if (!in_array($task->rkAnggota->status, [
            RkAnggota::STATUS_DRAFT,
            RkAnggota::STATUS_REJECTED,
        ])) {
            return back()->with('error', 'Daily Task tidak bisa diubah karena RK Anggota sudah disubmit atau disetujui.');
        }

        $task->update([
            'date' => $request->date,
            'activity' => $request->activity,
            'output' => $request->output,
            'evidence_url' => $request->evidence_url,
        ]);

        return back()->with('success', 'Daily Task berhasil diupdate.');
    }

    public function destroy($id)
    {
        $task = DailyTask::with('rkAnggota')->findOrFail($id);

        $this->authorizeManageTask($task);

        if (!in_array($task->rkAnggota->status, [
            RkAnggota::STATUS_DRAFT,
            RkAnggota::STATUS_REJECTED,
        ])) {
            return back()->with('error', 'Daily Task tidak bisa dihapus karena RK Anggota sudah disubmit atau disetujui.');
        }

        $task->delete();

        return back()->with('success', 'Daily Task berhasil dihapus.');
    }

    /*
    |--------------------------------------------------------------------------
    | Legacy Daily Task Approval
    |--------------------------------------------------------------------------
    | Secara flow final, progress tidak lagi bergantung pada approval Daily Task.
    | Method ini dibiarkan dulu agar route lama tidak rusak.
    | UI utama tidak perlu lagi menampilkan tombol approve/reject task.
    */
    public function approve($id)
{
    return back()->with(
        'error',
        'Approval Daily Task sudah tidak digunakan. Silakan review dan approve melalui RK Anggota.'
    );
}

public function reject(Request $request, $id)
{
    return back()->with(
        'error',
        'Reject Daily Task sudah tidak digunakan. Silakan reject melalui RK Anggota.'
    );
}
    private function authorizeViewTask(DailyTask $task): void
    {
        $user = auth()->user();

        if ($user->role === 'admin') {
            return;
        }

        if ($user->role === 'anggota' && $task->rkAnggota->user_id === $user->id) {
            return;
        }

        if ($user->role === 'ketua' && $task->rkAnggota->project->leader_id === $user->id) {
            return;
        }

        abort(403, 'Akses ditolak.');
    }

    private function authorizeManageTask(DailyTask $task): void
    {
        $user = auth()->user();

        if ($user->role === 'admin') {
            return;
        }

        if ($user->role === 'anggota' && $task->rkAnggota->user_id === $user->id) {
            return;
        }

        abort(403, 'Akses ditolak.');
    }
}