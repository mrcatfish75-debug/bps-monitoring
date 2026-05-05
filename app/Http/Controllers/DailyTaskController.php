<?php

namespace App\Http\Controllers;

use App\Models\DailyTask;
use App\Models\RkAnggota;
use Illuminate\Http\Request;

class DailyTaskController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'search' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'mode' => 'nullable|string|in:mine',
        ]);

        $user = auth()->user();

        if (!in_array($user->role, ['admin', 'anggota', 'ketua'])) {
            abort(403, 'Role tidak diizinkan mengakses Daily Task.');
        }

        $isMineMode = $user->role === 'ketua' && $request->mode === 'mine';

        /*
        |--------------------------------------------------------------------------
        | RK Anggota Dropdown
        |--------------------------------------------------------------------------
        | Hanya RK status draft/rejected yang boleh ditambah Daily Task.
        */
        $rkQuery = RkAnggota::with(['project', 'user'])
            ->whereIn('status', [
                RkAnggota::STATUS_DRAFT,
                RkAnggota::STATUS_REJECTED,
            ]);

        if ($user->role === 'anggota' || $isMineMode) {
            $rkQuery->where('user_id', $user->id);
        } elseif ($user->role === 'ketua') {
            $rkQuery->whereHas('project', function ($q) use ($user) {
                $q->where('leader_id', $user->id);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $rkQuery->where(function ($q) use ($search) {
                $q->where('description', 'like', '%' . $search . '%')
                    ->orWhereHas('project', function ($sub) use ($search) {
                        $sub->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('user', function ($sub) use ($search) {
                        $sub->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        $rkAnggotas = $rkQuery
            ->latest()
            ->limit(50)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Daily Task List
        |--------------------------------------------------------------------------
        | Admin:
        | - melihat semua.
        |
        | Anggota:
        | - hanya task dari RK miliknya sendiri.
        |
        | Ketua mode normal:
        | - task dari project yang dia pimpin.
        |
        | Ketua mode mine:
        | - task dari RK miliknya sendiri.
        */
        $taskQuery = DailyTask::with([
            'rkAnggota.project',
            'rkAnggota.user',
        ]);

        if ($user->role === 'anggota' || $isMineMode) {
            $taskQuery->whereHas('rkAnggota', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        } elseif ($user->role === 'ketua') {
            $taskQuery->whereHas('rkAnggota.project', function ($q) use ($user) {
                $q->where('leader_id', $user->id);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $taskQuery->where(function ($q) use ($search) {
                $q->where('activity', 'like', '%' . $search . '%')
                    ->orWhere('output', 'like', '%' . $search . '%')
                    ->orWhere('evidence_url', 'like', '%' . $search . '%')
                    ->orWhereHas('rkAnggota', function ($sub) use ($search) {
                        $sub->where('description', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('rkAnggota.project', function ($sub) use ($search) {
                        $sub->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('rkAnggota.user', function ($sub) use ($search) {
                        $sub->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        if ($request->filled('start_date')) {
            $taskQuery->whereDate('date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $taskQuery->whereDate('date', '<=', $request->end_date);
        }

        $tasks = $taskQuery
            ->latest('date')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('daily_task.index', compact('rkAnggotas', 'tasks'));
    }

    public function create(Request $request)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['admin', 'anggota', 'ketua'])) {
            abort(403, 'Role tidak diizinkan membuat Daily Task.');
        }

        $isMineMode = $user->role === 'ketua' && $request->mode === 'mine';

        if ($user->role === 'ketua' && !$isMineMode) {
            abort(403, 'Ketua hanya dapat membuat Daily Task melalui mode Pekerjaan Saya.');
        }

        $query = RkAnggota::with(['project', 'user'])
            ->whereIn('status', [
                RkAnggota::STATUS_DRAFT,
                RkAnggota::STATUS_REJECTED,
            ]);

        if ($user->role === 'anggota' || $isMineMode) {
            $query->where('user_id', $user->id);
        }

        $rkAnggotas = $query
            ->latest()
            ->get();

        return view('daily_task.create', compact('rkAnggotas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'rk_anggota_id' => 'required|exists:rk_anggotas,id',
            'date' => 'required|date|after_or_equal:today',
            'activity' => 'required|string',
            'output' => 'nullable|string',
            'evidence_url' => 'nullable|url',
        ]);

        $user = auth()->user();

        $rk = RkAnggota::with('project')->findOrFail($request->rk_anggota_id);

        /*
        |--------------------------------------------------------------------------
        | Authorization Create
        |--------------------------------------------------------------------------
        | Admin boleh input semua.
        | Anggota dan ketua mode pribadi hanya boleh input untuk RK miliknya.
        */
        if ($user->role !== 'admin' && (int) $rk->user_id !== (int) $user->id) {
            abort(403, 'Akses ditolak. Kamu hanya boleh membuat Daily Task untuk RK milikmu sendiri.');
        }

        /*
        |--------------------------------------------------------------------------
        | Status Lock
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
            'date' => $request->date,
            'activity' => $request->activity,
            'output' => $request->output ?? '-',
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
            'date' => 'required|date|after_or_equal:today',
            'activity' => 'required|string',
            'output' => 'nullable|string',
            'evidence_url' => 'nullable|url',
        ]);

        $task = DailyTask::with([
            'rkAnggota.project',
            'rkAnggota.user',
        ])->findOrFail($id);

        $this->authorizeManageTask($task);

        /*
        |--------------------------------------------------------------------------
        | Status Lock
        |--------------------------------------------------------------------------
        | Daily Task hanya boleh diubah saat RK masih draft/rejected.
        */
        if (!in_array($task->rkAnggota->status, [
            RkAnggota::STATUS_DRAFT,
            RkAnggota::STATUS_REJECTED,
        ])) {
            return back()->with('error', 'Daily Task tidak bisa diubah karena RK Anggota sudah disubmit atau disetujui.');
        }

        $task->update([
            'date' => $request->date,
            'activity' => $request->activity,
            'output' => $request->output ?? '-',
            'evidence_url' => $request->evidence_url,
        ]);

        return back()->with('success', 'Daily Task berhasil diupdate.');
    }

    public function destroy($id)
    {
        $task = DailyTask::with([
            'rkAnggota.project',
            'rkAnggota.user',
        ])->findOrFail($id);

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
    | Flow final: approval dilakukan di RK Anggota, bukan Daily Task.
    |--------------------------------------------------------------------------
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

    /*
    |--------------------------------------------------------------------------
    | Authorization Helpers
    |--------------------------------------------------------------------------
    */

    private function authorizeViewTask(DailyTask $task): void
    {
        $user = auth()->user();

        if ($user->role === 'admin') {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Pemilik RK boleh melihat Daily Task miliknya sendiri.
        |--------------------------------------------------------------------------
        */
        if ($task->rkAnggota && (int) $task->rkAnggota->user_id === (int) $user->id) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Ketua project boleh melihat Daily Task dari project yang dia pimpin.
        |--------------------------------------------------------------------------
        */
        if (
            $user->role === 'ketua' &&
            $task->rkAnggota &&
            $task->rkAnggota->project &&
            (int) $task->rkAnggota->project->leader_id === (int) $user->id
        ) {
            return;
        }

        abort(403, 'Akses ditolak. Kamu tidak berhak melihat Daily Task ini.');
    }

    private function authorizeManageTask(DailyTask $task): void
    {
        $user = auth()->user();

        if ($user->role === 'admin') {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Manage = create/update/delete.
        | Pemilik RK boleh manage Daily Task miliknya sendiri.
        | Ketua project tidak otomatis boleh edit/delete Daily Task anggota.
        |--------------------------------------------------------------------------
        */
        if ($task->rkAnggota && (int) $task->rkAnggota->user_id === (int) $user->id) {
            return;
        }

        abort(403, 'Akses ditolak. Kamu hanya boleh mengelola Daily Task milikmu sendiri.');
    }
}