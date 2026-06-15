<?php

namespace App\Http\Controllers;

use App\Models\Iki;
use App\Models\RkAnggota;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;

class IkiController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['admin', 'kepala', 'ketua', 'anggota'], true)) {
            abort(403, 'Role tidak diizinkan mengakses IKI.');
        }

        /*
        |--------------------------------------------------------------------------
        | Mode Context
        |--------------------------------------------------------------------------
        | /ketua/iki
        | - mode ketua/reviewer
        | - melihat IKI dari project yang dia pimpin.
        |
        | /ketua/iki?mode=mine
        | - mode pekerjaan saya
        | - melihat IKI miliknya sendiri sebagai pelaksana di project lain.
        |--------------------------------------------------------------------------
        */
        $isMineMode = $user->role === 'ketua' && $request->mode === 'mine';

        $query = Iki::with([
            'rkAnggota.project.team',
            'rkAnggota.project.rkKetua.iku',
            'rkAnggota.project.leader',
            'rkAnggota.user',
            'dailyTasks',
            'approver',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Role Scope
        |--------------------------------------------------------------------------
        | Admin:
        | - melihat semua IKI.
        |
        | Kepala:
        | - monitoring semua IKI.
        |
        | Anggota:
        | - hanya melihat IKI miliknya sendiri.
        |
        | Ketua mode normal:
        | - melihat IKI dari project yang dia pimpin.
        |
        | Ketua mode mine:
        | - melihat IKI miliknya sendiri.
        |--------------------------------------------------------------------------
        */
        if ($user->role === 'anggota' || $isMineMode) {
            $query->whereHas('rkAnggota', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        } elseif ($user->role === 'ketua') {
            $query->whereHas('rkAnggota.project', function ($q) use ($user) {
                $q->where('leader_id', $user->id);
            });
        }

        if ($request->filled('project_id')) {
            $query->whereHas('rkAnggota', function ($q) use ($request) {
                $q->where('project_id', $request->project_id);
            });
        }

        if ($request->filled('rk_anggota_id')) {
            $query->where('rk_anggota_id', $request->rk_anggota_id);
        }

        if (
            $request->filled('user_id')
            && !($user->role === 'anggota' || $isMineMode)
        ) {
            $query->whereHas('rkAnggota', function ($q) use ($request) {
                $q->where('user_id', $request->user_id);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('year')) {
            $query->whereHas('rkAnggota.project.rkKetua.iku', function ($q) use ($request) {
                $q->where('year', $request->year);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', '%' . $search . '%')
                    ->orWhere('final_evidence', 'like', '%' . $search . '%')
                    ->orWhereHas('rkAnggota', function ($sub) use ($search) {
                        $sub->where('description', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('rkAnggota.project', function ($sub) use ($search) {
                        $sub->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('rkAnggota.project.team', function ($sub) use ($search) {
                        $sub->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('rkAnggota.user', function ($sub) use ($search) {
                        $sub->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        $ikis = $query
            ->latest()
            ->paginate(10)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Dropdown RK Anggota
        |--------------------------------------------------------------------------
        | Dipakai untuk form tambah/filter IKI.
        |--------------------------------------------------------------------------
        */
        $rkQuery = RkAnggota::with([
            'project.team',
            'project.rkKetua.iku',
            'project.leader',
            'user',
            'ikis',
        ]);

        if ($user->role === 'anggota' || $isMineMode) {
            $rkQuery->where('user_id', $user->id);
        } elseif ($user->role === 'ketua') {
            $rkQuery->whereHas('project', function ($q) use ($user) {
                $q->where('leader_id', $user->id);
            });
        }

        $rkAnggotas = $rkQuery
            ->latest()
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Dropdown Project
        |--------------------------------------------------------------------------
        */
        $projectQuery = Project::with(['team', 'leader', 'rkKetua.iku']);

        if ($user->role === 'anggota' || $isMineMode) {
            $projectQuery->whereHas('members', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        } elseif ($user->role === 'ketua') {
            $projectQuery->where('leader_id', $user->id);
        }

        $projects = $projectQuery
            ->latest()
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Dropdown User
        |--------------------------------------------------------------------------
        */
        if ($user->role === 'anggota' || $isMineMode) {
            $users = User::where('id', $user->id)->get();
        } else {
            $users = User::whereIn('role', ['anggota', 'ketua'])
                ->orderBy('name')
                ->get();
        }

        return view('iki.index', compact(
            'ikis',
            'rkAnggotas',
            'projects',
            'users',
            'isMineMode'
        ));
    }

    public function store(Request $request)
    {
        $this->abortIfMonitoringOnly();

        $request->validate([
            'rk_anggota_id' => 'required|exists:rk_anggotas,id',
            'description' => 'required|string',
            'target' => 'nullable|string|max:255',
            'unit' => 'nullable|string|max:255',
        ]);

        $rk = RkAnggota::with([
            'project',
            'user',
        ])->findOrFail($request->rk_anggota_id);

        $this->authorizeCreateIki($rk);

        Iki::create([
            'rk_anggota_id' => $rk->id,
            'description' => $request->description,
            'target' => $request->target,
            'unit' => $request->unit,
            'status' => Iki::STATUS_DRAFT,
        ]);

        return back()->with('success', 'IKI berhasil dibuat.');
    }

    public function show($id)
    {
        $iki = Iki::with([
            'rkAnggota.project.team',
            'rkAnggota.project.rkKetua.iku',
            'rkAnggota.project.leader',
            'rkAnggota.user',
            'dailyTasks',
            'approver',
        ])->findOrFail($id);

        $this->authorizeViewIki($iki);

        return response()->json($iki);
    }

    public function update(Request $request, $id)
    {
        $this->abortIfMonitoringOnly();

        $iki = Iki::with([
            'rkAnggota.project',
            'rkAnggota.user',
        ])->findOrFail($id);

        $this->authorizeManageIki($iki);

        if (!$iki->isEditable()) {
            return back()->with('error', 'IKI yang sudah disubmit atau disetujui tidak bisa diubah.');
        }

        $request->validate([
            'rk_anggota_id' => 'required|exists:rk_anggotas,id',
            'description' => 'required|string',
            'target' => 'nullable|string|max:255',
            'unit' => 'nullable|string|max:255',
        ]);

        $rk = RkAnggota::with([
            'project',
            'user',
        ])->findOrFail($request->rk_anggota_id);

        $this->authorizeCreateIki($rk);

        $iki->update([
            'rk_anggota_id' => $rk->id,
            'description' => $request->description,
            'target' => $request->target,
            'unit' => $request->unit,
        ]);

        return back()->with('success', 'IKI berhasil diupdate.');
    }

    public function destroy($id)
    {
        $this->abortIfMonitoringOnly();

        $iki = Iki::with([
            'rkAnggota.project',
            'rkAnggota.user',
        ])->findOrFail($id);

        $this->authorizeManageIki($iki);

        if (!$iki->isEditable()) {
            return back()->with('error', 'IKI yang sudah disubmit atau disetujui tidak bisa dihapus.');
        }

        $iki->delete();

        return back()->with('success', 'IKI berhasil dihapus.');
    }

    public function submit(Request $request, $id)
    {
        $this->abortIfMonitoringOnly();

        $request->validate([
            'final_evidence' => 'required|string|max:2000',
        ], [
            'final_evidence.required' => 'Link bukti final wajib diisi sebelum submit IKI.',
            'final_evidence.max' => 'Link bukti final maksimal 2000 karakter.',
        ]);

        $iki = Iki::with([
            'rkAnggota.project',
            'rkAnggota.user',
            'dailyTasks',
        ])->findOrFail($id);

        $this->authorizeSubmitIki($iki);

        if (!$iki->canSubmit()) {
            return back()->with('error', 'IKI ini tidak bisa disubmit.');
        }

        $iki->update([
            'status' => Iki::STATUS_SUBMITTED,
            'submitted_at' => now(),
            'approved_at' => null,
            'approved_by' => null,
            'final_evidence' => $request->final_evidence,
            'rejection_note' => null,
        ]);

        return back()->with('success', 'IKI berhasil disubmit untuk review Ketua.');
    }

    public function approve($id)
    {
        $this->abortIfMonitoringOnly();

        $iki = Iki::with([
            'rkAnggota.project',
            'rkAnggota.user',
        ])->findOrFail($id);

        $this->authorizeReviewIki($iki);

        if (!$iki->canBeReviewed()) {
            return back()->with('error', 'Hanya IKI berstatus submitted yang bisa disetujui.');
        }

        $iki->update([
            'status' => Iki::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => auth()->id(),
            'rejection_note' => null,
        ]);

        return back()->with('success', 'IKI berhasil disetujui.');
    }

    public function bulkApprove(Request $request)
{
    $this->abortIfMonitoringOnly();

    $request->validate([
        'iki_ids' => 'required|array|min:1',
        'iki_ids.*' => 'integer|exists:ikis,id',
    ], [
        'iki_ids.required' => 'Pilih minimal satu IKI untuk disetujui.',
        'iki_ids.array' => 'Format pilihan IKI tidak valid.',
        'iki_ids.min' => 'Pilih minimal satu IKI untuk disetujui.',
        'iki_ids.*.exists' => 'Ada IKI yang tidak ditemukan.',
    ]);

    $ikis = Iki::with([
        'rkAnggota.project',
        'rkAnggota.user',
    ])
        ->whereIn('id', $request->iki_ids)
        ->get();

    if ($ikis->isEmpty()) {
        return back()->with('error', 'Tidak ada IKI yang ditemukan untuk disetujui.');
    }

    /*
    |--------------------------------------------------------------------------
    | Authorization Per IKI
    |--------------------------------------------------------------------------
    | Tetap dicek satu per satu agar Ketua hanya bisa approve IKI dari project
    | yang dia pimpin dan tidak bisa approve IKI miliknya sendiri.
    |--------------------------------------------------------------------------
    */
    foreach ($ikis as $iki) {
        $this->authorizeReviewIki($iki);
    }

    /*
    |--------------------------------------------------------------------------
    | Hanya IKI Submitted Yang Diproses
    |--------------------------------------------------------------------------
    | Jika user mencentang IKI yang bukan submitted, data tersebut dilewati.
    |--------------------------------------------------------------------------
    */
    $submittedIkis = $ikis
        ->filter(function ($iki) {
            return $iki->canBeReviewed();
        })
        ->values();

    $skippedCount = $ikis->count() - $submittedIkis->count();

    if ($submittedIkis->isEmpty()) {
        return back()->with('error', 'Tidak ada IKI berstatus submitted yang bisa disetujui.');
    }

    \Illuminate\Support\Facades\DB::transaction(function () use ($submittedIkis) {
        foreach ($submittedIkis as $iki) {
            $iki->update([
                'status' => Iki::STATUS_APPROVED,
                'approved_at' => now(),
                'approved_by' => auth()->id(),
                'rejection_note' => null,
            ]);
        }
    });

    $message = $submittedIkis->count() . ' IKI berhasil disetujui.';

    if ($skippedCount > 0) {
        $message .= ' ' . $skippedCount . ' IKI dilewati karena bukan berstatus submitted.';
    }

   $redirectQuery = [
    'project_id' => $request->project_id,
    'rk_anggota_id' => $request->rk_anggota_id,
    'user_id' => $request->user_id,
    'search' => $request->search,
    'year' => $request->year,
    'status' => Iki::STATUS_APPROVED,
];

$redirectQuery = array_filter($redirectQuery, function ($value) {
    return filled($value);
});

$routeName = auth()->user()->role === 'admin'
    ? 'admin.iki.index'
    : 'ketua.iki.index';

return redirect()
    ->route($routeName, $redirectQuery)
    ->with('success', $message);
}

    public function reject(Request $request, $id)
    {
        $this->abortIfMonitoringOnly();

        $request->validate([
            'rejection_note' => 'required|string|max:1000',
        ], [
            'rejection_note.required' => 'Alasan penolakan wajib diisi.',
        ]);

        $iki = Iki::with([
            'rkAnggota.project',
            'rkAnggota.user',
        ])->findOrFail($id);

        $this->authorizeReviewIki($iki);

        if (!$iki->canBeReviewed()) {
            return back()->with('error', 'Hanya IKI berstatus submitted yang bisa ditolak.');
        }

        $iki->update([
            'status' => Iki::STATUS_REJECTED,
            'approved_at' => null,
            'approved_by' => null,
            'rejection_note' => $request->rejection_note,
        ]);

        return back()->with('success', 'IKI berhasil ditolak.');
    }

    /*
    |--------------------------------------------------------------------------
    | Authorization Helpers
    |--------------------------------------------------------------------------
    */

    private function authorizeViewIki(Iki $iki): void
    {
        $user = auth()->user();

        if (in_array($user->role, ['admin', 'kepala'], true)) {
            return;
        }

        if ((int) $iki->rkAnggota->user_id === (int) $user->id) {
            return;
        }

        if (
            $user->role === 'ketua'
            && $iki->rkAnggota->project
            && (int) $iki->rkAnggota->project->leader_id === (int) $user->id
        ) {
            return;
        }

        abort(403, 'Akses ditolak. Kamu tidak berhak melihat IKI ini.');
    }

    private function authorizeCreateIki(RkAnggota $rk): void
    {
        $user = auth()->user();
        $isMineMode = $user->role === 'ketua' && request('mode') === 'mine';

        if ($user->role === 'admin') {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Anggota / Ketua mode mine
        |--------------------------------------------------------------------------
        | Hanya boleh membuat IKI untuk RK miliknya sendiri.
        |--------------------------------------------------------------------------
        */
        if ($user->role === 'anggota' || $isMineMode) {
            if ((int) $rk->user_id === (int) $user->id) {
                return;
            }

            abort(403, 'Kamu hanya boleh membuat IKI untuk RK milikmu sendiri.');
        }

        /*
        |--------------------------------------------------------------------------
        | Ketua mode normal
        |--------------------------------------------------------------------------
        | Ketua boleh membuat IKI untuk RK Anggota dari project yang dia pimpin,
        | tetapi tidak boleh membuat IKI untuk dirinya sendiri.
        |--------------------------------------------------------------------------
        */
        if (
            $user->role === 'ketua'
            && $rk->project
            && (int) $rk->project->leader_id === (int) $user->id
        ) {
            if ((int) $rk->user_id === (int) $user->id) {
                abort(403, 'Ketua tidak boleh membuat IKI untuk dirinya sendiri pada project yang dia pimpin.');
            }

            return;
        }

        abort(403, 'Akses ditolak. Kamu tidak berhak membuat IKI untuk RK ini.');
    }

    private function authorizeManageIki(Iki $iki): void
    {
        $user = auth()->user();
        $isMineMode = $user->role === 'ketua' && request('mode') === 'mine';

        if ($user->role === 'admin') {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Pemilik IKI
        |--------------------------------------------------------------------------
        */
        if (
            ($user->role === 'anggota' || $isMineMode)
            && (int) $iki->rkAnggota->user_id === (int) $user->id
        ) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Ketua project
        |--------------------------------------------------------------------------
        */
        if (
            $user->role === 'ketua'
            && !$isMineMode
            && $iki->rkAnggota->project
            && (int) $iki->rkAnggota->project->leader_id === (int) $user->id
        ) {
            if ((int) $iki->rkAnggota->user_id === (int) $user->id) {
                abort(403, 'Ketua tidak boleh mengelola IKI miliknya sendiri pada project yang dia pimpin.');
            }

            return;
        }

        abort(403, 'Akses ditolak. Kamu tidak berhak mengelola IKI ini.');
    }

    private function authorizeSubmitIki(Iki $iki): void
    {
        $user = auth()->user();

        if ($user->role === 'admin') {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Submit adalah aksi pemilik IKI.
        | Ketua reviewer tidak submit IKI anggota.
        |--------------------------------------------------------------------------
        */
        if ((int) $iki->rkAnggota->user_id === (int) $user->id) {
            return;
        }

        abort(403, 'Akses ditolak. Kamu hanya boleh submit IKI milikmu sendiri.');
    }

    private function authorizeReviewIki(Iki $iki): void
    {
        $user = auth()->user();

        if ($user->role === 'admin') {
            return;
        }

        if ((int) $iki->rkAnggota->user_id === (int) $user->id) {
            abort(403, 'Kamu tidak boleh review IKI milikmu sendiri.');
        }

        if (
            $user->role === 'ketua'
            && $iki->rkAnggota->project
            && (int) $iki->rkAnggota->project->leader_id === (int) $user->id
        ) {
            return;
        }

        abort(403, 'Akses ditolak. Kamu hanya boleh review IKI dari project yang kamu pimpin.');
    }

    private function abortIfMonitoringOnly(): void
    {
        if (auth()->user()?->role === 'kepala') {
            abort(403, 'Kepala hanya dapat melakukan monitoring.');
        }
    }
}