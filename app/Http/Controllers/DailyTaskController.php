<?php

namespace App\Http\Controllers;

use App\Models\DailyTask;
use App\Models\Iki;
use App\Models\RkAnggota;
use Illuminate\Http\Request;

class DailyTaskController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'search' => 'nullable|string|max:255',
            'year' => 'nullable|integer|min:2020|max:' . ((int) date('Y') + 5),
            'status' => 'nullable|string|in:draft,submitted,approved,rejected,pending',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'mode' => 'nullable|string|in:mine',
        ]);

        $user = auth()->user();

        if (!in_array($user->role, ['admin', 'kepala', 'anggota', 'ketua'], true)) {
            abort(403, 'Role tidak diizinkan mengakses Daily Task.');
        }

        $selectedYear = $request->filled('year') ? (int) $request->year : (int) date('Y');
        $selectedStatus = $request->filled('status') ? strtolower((string) $request->status) : null;

        $isMineMode = $user->role === 'ketua' && $request->mode === 'mine';
        $isMonitoringOnly = $user->role === 'kepala';

        /*
        |--------------------------------------------------------------------------
        | IKI Dropdown
        |--------------------------------------------------------------------------
        | Daily Task sekarang dibuat di bawah IKI.
        | Hanya IKI draft/rejected yang boleh ditambahkan Daily Task.
        |--------------------------------------------------------------------------
        */
        if ($isMonitoringOnly) {
            $ikis = collect();
        } else {
            $ikiQuery = Iki::with([
                'rkAnggota.project.team',
                'rkAnggota.project.rkKetua.iku',
                'rkAnggota.user',
            ])->whereIn('status', [
                Iki::STATUS_DRAFT,
                Iki::STATUS_REJECTED,
            ]);

            if ($user->role === 'anggota' || $isMineMode) {
                $ikiQuery->whereHas('rkAnggota', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            } elseif ($user->role === 'ketua') {
                /*
                |--------------------------------------------------------------------------
                | Ketua mode normal adalah monitoring/reviewer.
                | Secara UI dia tidak create Daily Task, tapi query ini tetap aman.
                |--------------------------------------------------------------------------
                */
                $ikiQuery->whereHas('rkAnggota.project', function ($q) use ($user) {
                    $q->where('leader_id', $user->id);
                });
            }

            /*
            |--------------------------------------------------------------------------
            | Filter Tahun Untuk Dropdown IKI
            |--------------------------------------------------------------------------
            | Supaya pilihan IKI yang bisa dibuat Daily Task ikut menyesuaikan tahun
            | yang dipilih user. Tidak mempengaruhi flow lama.
            |--------------------------------------------------------------------------
            */
            if ($selectedYear !== null) {
                $ikiQuery->whereHas('rkAnggota.project.rkKetua.iku', function ($q) use ($selectedYear) {
                    $q->where('year', $selectedYear);
                });
            }

            if ($request->filled('search')) {
                $search = $request->search;

                $ikiQuery->where(function ($q) use ($search) {
                    $q->where('description', 'like', '%' . $search . '%')
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

            $ikis = $ikiQuery
                ->latest()
                ->limit(100)
                ->get();
        }

        /*
        |--------------------------------------------------------------------------
        | Legacy RK Anggota Dropdown
        |--------------------------------------------------------------------------
        | Masih dikirim ke Blade agar tidak merusak bagian lama yang mungkin
        | belum dibersihkan, tapi flow baru memakai $ikis.
        |--------------------------------------------------------------------------
        */
        $rkAnggotas = collect();

        /*
        |--------------------------------------------------------------------------
        | Daily Task List
        |--------------------------------------------------------------------------
        | Daily Task baru terhubung ke IKI dan tetap menyimpan rk_anggota_id
        | untuk transisi/kompatibilitas data lama.
        |--------------------------------------------------------------------------
        */
        $taskQuery = DailyTask::with([
            'iki',
            'iki.rkAnggota.project.team',
            'iki.rkAnggota.project.rkKetua.iku',
            'iki.rkAnggota.project.leader',
            'iki.rkAnggota.user',

            'rkAnggota.project.team',
            'rkAnggota.project.rkKetua.iku',
            'rkAnggota.project.leader',
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

        /*
        |--------------------------------------------------------------------------
        | Filter Tahun
        |--------------------------------------------------------------------------
        | Tahun diambil dari IKU.year.
        | Prioritas data baru:
        | daily_tasks -> iki -> rk_anggota -> project -> rk_ketua -> iku
        |
        | Fallback data lama:
        | daily_tasks -> rk_anggota -> project -> rk_ketua -> iku
        |--------------------------------------------------------------------------
        */
        if ($selectedYear !== null) {
            $taskQuery->where(function ($q) use ($selectedYear) {
                $q->whereHas('iki.rkAnggota.project.rkKetua.iku', function ($sub) use ($selectedYear) {
                    $sub->where('year', $selectedYear);
                })
                ->orWhere(function ($legacy) use ($selectedYear) {
                    $legacy->whereNull('iki_id')
                        ->whereHas('rkAnggota.project.rkKetua.iku', function ($sub) use ($selectedYear) {
                            $sub->where('year', $selectedYear);
                        });
                });
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Filter Status
        |--------------------------------------------------------------------------
        | Flow final approval ada di IKI, jadi status utama yang difilter adalah
        | ikis.status: draft, submitted, approved, rejected.
        |
        | Status pending tetap didukung untuk jaga-jaga kalau Blade lama masih
        | mengirim status Daily Task legacy.
        |--------------------------------------------------------------------------
        */
        if ($selectedStatus !== null) {
            if ($selectedStatus === DailyTask::STATUS_PENDING) {
                $taskQuery->where('status', DailyTask::STATUS_PENDING);
            } else {
                $taskQuery->where(function ($q) use ($selectedStatus) {
                    $q->whereHas('iki', function ($sub) use ($selectedStatus) {
                        $sub->where('status', $selectedStatus);
                    })
                    ->orWhere(function ($legacy) use ($selectedStatus) {
                        $legacy->whereNull('iki_id')
                            ->whereHas('rkAnggota', function ($sub) use ($selectedStatus) {
                                $sub->where('status', $selectedStatus);
                            });
                    })
                    ->orWhere(function ($legacyTask) use ($selectedStatus) {
                        $legacyTask->whereNull('iki_id')
                            ->where('status', $selectedStatus);
                    });
                });
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $taskQuery->where(function ($q) use ($search) {
                $q->where('activity', 'like', '%' . $search . '%')
                    ->orWhere('output', 'like', '%' . $search . '%')
                    ->orWhere('evidence_url', 'like', '%' . $search . '%')

                    ->orWhereHas('iki', function ($sub) use ($search) {
                        $sub->where('description', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('iki.rkAnggota', function ($sub) use ($search) {
                        $sub->where('description', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('iki.rkAnggota.project', function ($sub) use ($search) {
                        $sub->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('iki.rkAnggota.project.team', function ($sub) use ($search) {
                        $sub->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('iki.rkAnggota.project.rkKetua.iku', function ($sub) use ($search) {
                        $sub->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('iki.rkAnggota.user', function ($sub) use ($search) {
                        $sub->where('name', 'like', '%' . $search . '%');
                    })

                    /*
                    | Legacy fallback search.
                    */
                    ->orWhereHas('rkAnggota', function ($sub) use ($search) {
                        $sub->where('description', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('rkAnggota.project', function ($sub) use ($search) {
                        $sub->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('rkAnggota.project.team', function ($sub) use ($search) {
                        $sub->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('rkAnggota.project.rkKetua.iku', function ($sub) use ($search) {
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

        return view('daily_task.index', compact(
            'ikis',
            'rkAnggotas',
            'tasks',
            'selectedYear',
            'selectedStatus'
        ));
    }

    public function create(Request $request)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['admin', 'anggota', 'ketua'], true)) {
            abort(403, 'Role tidak diizinkan membuat Daily Task.');
        }

        $isMineMode = $user->role === 'ketua' && $request->mode === 'mine';

        if ($user->role === 'ketua' && !$isMineMode) {
            abort(403, 'Ketua hanya dapat membuat Daily Task melalui mode Pekerjaan Saya.');
        }

        $ikiQuery = Iki::with([
            'rkAnggota.project',
            'rkAnggota.user',
        ])->whereIn('status', [
            Iki::STATUS_DRAFT,
            Iki::STATUS_REJECTED,
        ]);

        if ($user->role === 'anggota' || $isMineMode) {
            $ikiQuery->whereHas('rkAnggota', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        $ikis = $ikiQuery
            ->latest()
            ->get();

        return view('daily_task.create', compact('ikis'));
    }

    public function store(Request $request)
    {
        $this->abortIfMonitoringOnly();

        $request->validate([
            'iki_id' => 'required|exists:ikis,id',
            'date' => 'required|date',
            'activity' => 'required|string',
            'output' => 'nullable|string',
            'evidence_url' => 'nullable|url',
        ]);

        $user = auth()->user();

        $iki = Iki::with([
            'rkAnggota.project',
            'rkAnggota.user',
        ])->findOrFail($request->iki_id);

        $rk = $iki->rkAnggota;

        if (!$rk) {
            return back()->with('error', 'IKI tidak memiliki RK Anggota yang valid.');
        }

        /*
        |--------------------------------------------------------------------------
        | Authorization Create
        |--------------------------------------------------------------------------
        | Admin boleh input semua.
        | Anggota dan ketua mode mine hanya boleh input Daily Task untuk IKI miliknya.
        |--------------------------------------------------------------------------
        */
        if ($user->role !== 'admin' && (int) $rk->user_id !== (int) $user->id) {
            abort(403, 'Akses ditolak. Kamu hanya boleh membuat Daily Task untuk IKI milikmu sendiri.');
        }

        /*
        |--------------------------------------------------------------------------
        | IKI Status Lock
        |--------------------------------------------------------------------------
        | Daily Task hanya boleh dibuat saat IKI masih draft/rejected.
        |--------------------------------------------------------------------------
        */
        if (!in_array($iki->status, [
            Iki::STATUS_DRAFT,
            Iki::STATUS_REJECTED,
        ], true)) {
            return back()->with('error', 'Daily Task tidak bisa ditambahkan karena IKI sudah disubmit atau disetujui.');
        }

        DailyTask::create([
            'rk_anggota_id' => $rk->id,
            'iki_id' => $iki->id,
            'date' => $request->date,
            'activity' => $request->activity,
            'output' => $request->output ?? '-',
            'evidence_url' => $request->evidence_url,
            'status' => DailyTask::STATUS_PENDING,
        ]);

        return back()->with('success', 'Daily Task berhasil dibuat.');
    }

    public function show($id)
    {
        $task = DailyTask::with([
            'iki.rkAnggota.project.team',
            'iki.rkAnggota.project.rkKetua.iku',
            'iki.rkAnggota.project.leader',
            'iki.rkAnggota.user',

            'rkAnggota.project.team',
            'rkAnggota.project.rkKetua.iku',
            'rkAnggota.project.leader',
            'rkAnggota.user',
        ])->findOrFail($id);

        $this->authorizeViewTask($task);

        return response()->json($this->formatTaskForJson($task));
    }

    public function update(Request $request, $id)
    {
        $this->abortIfMonitoringOnly();

        $request->validate([
            'date' => 'required|date',
            'activity' => 'required|string',
            'output' => 'nullable|string',
            'evidence_url' => 'nullable|url',
        ]);

        $task = DailyTask::with([
            'iki.rkAnggota.project',
            'iki.rkAnggota.user',
            'rkAnggota.project',
            'rkAnggota.user',
        ])->findOrFail($id);

        $this->authorizeManageTask($task);

        $iki = $task->iki;

        /*
        |--------------------------------------------------------------------------
        | IKI Status Lock
        |--------------------------------------------------------------------------
        | Jika task sudah punya IKI, lock berdasarkan status IKI.
        | Jika data lama belum punya IKI, fallback ke status RK Anggota.
        |--------------------------------------------------------------------------
        */
        if ($iki) {
            if (!in_array($iki->status, [
                Iki::STATUS_DRAFT,
                Iki::STATUS_REJECTED,
            ], true)) {
                return back()->with('error', 'Daily Task tidak bisa diubah karena IKI sudah disubmit atau disetujui.');
            }
        } else {
            if (!in_array($task->rkAnggota?->status, [
                RkAnggota::STATUS_DRAFT,
                RkAnggota::STATUS_REJECTED,
            ], true)) {
                return back()->with('error', 'Daily Task tidak bisa diubah karena RK Anggota sudah disubmit atau disetujui.');
            }
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
        $this->abortIfMonitoringOnly();

        $task = DailyTask::with([
            'iki.rkAnggota.project',
            'iki.rkAnggota.user',
            'rkAnggota.project',
            'rkAnggota.user',
        ])->findOrFail($id);

        $this->authorizeManageTask($task);

        $iki = $task->iki;

        if ($iki) {
            if (!in_array($iki->status, [
                Iki::STATUS_DRAFT,
                Iki::STATUS_REJECTED,
            ], true)) {
                return back()->with('error', 'Daily Task tidak bisa dihapus karena IKI sudah disubmit atau disetujui.');
            }
        } else {
            if (!in_array($task->rkAnggota?->status, [
                RkAnggota::STATUS_DRAFT,
                RkAnggota::STATUS_REJECTED,
            ], true)) {
                return back()->with('error', 'Daily Task tidak bisa dihapus karena RK Anggota sudah disubmit atau disetujui.');
            }
        }

        $task->delete();

        return back()->with('success', 'Daily Task berhasil dihapus.');
    }

    /*
    |--------------------------------------------------------------------------
    | Legacy Daily Task Approval
    |--------------------------------------------------------------------------
    | Flow final: approval dilakukan di IKI, bukan Daily Task.
    |--------------------------------------------------------------------------
    */
    public function approve($id)
    {
        return back()->with(
            'error',
            'Approval Daily Task sudah tidak digunakan. Silakan review dan approve melalui IKI.'
        );
    }

    public function reject(Request $request, $id)
    {
        return back()->with(
            'error',
            'Reject Daily Task sudah tidak digunakan. Silakan reject melalui IKI.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Authorization Helpers
    |--------------------------------------------------------------------------
    */

    private function getTaskRkAnggota(DailyTask $task): ?RkAnggota
    {
        return $task->iki?->rkAnggota ?? $task->rkAnggota;
    }

    private function authorizeViewTask(DailyTask $task): void
    {
        $user = auth()->user();
        $rk = $this->getTaskRkAnggota($task);

        if (in_array($user->role, ['admin', 'kepala'], true)) {
            return;
        }

        if ($rk && (int) $rk->user_id === (int) $user->id) {
            return;
        }

        if (
            $user->role === 'ketua'
            && $rk
            && $rk->project
            && (int) $rk->project->leader_id === (int) $user->id
        ) {
            return;
        }

        abort(403, 'Akses ditolak. Kamu tidak berhak melihat Daily Task ini.');
    }

    private function authorizeManageTask(DailyTask $task): void
    {
        $user = auth()->user();
        $rk = $this->getTaskRkAnggota($task);

        if ($user->role === 'admin') {
            return;
        }

        if ($rk && (int) $rk->user_id === (int) $user->id) {
            return;
        }

        abort(403, 'Akses ditolak. Kamu hanya boleh mengelola Daily Task milikmu sendiri.');
    }

    private function abortIfMonitoringOnly(): void
    {
        if (auth()->user()?->role === 'kepala') {
            abort(403, 'Kepala hanya dapat melakukan monitoring.');
        }
    }

    private function formatTaskForJson(DailyTask $task): array
    {
        $iki = $task->iki;
        $rk = $iki?->rkAnggota ?? $task->rkAnggota;

        $project = $rk?->project;
        $team = $project?->team;
        $rkKetua = $project?->rkKetua;
        $iku = $rkKetua?->iku;
        $user = $rk?->user;
        $leader = $project?->leader;

        $ikiPayload = $iki ? [
            'id' => $iki->id,
            'description' => $iki->description,
            'target' => $iki->target,
            'unit' => $iki->unit,
            'status' => $iki->status,
            'status_label' => $iki->status_label,
            'progress' => $iki->progress ?? 0,
            'final_evidence' => $iki->final_evidence,
            'submitted_at' => $iki->submitted_at,
            'approved_at' => $iki->approved_at,
            'approved_by' => $iki->approved_by,
            'rejection_note' => $iki->rejection_note,
        ] : null;

        $rkPayload = $rk ? [
            'id' => $rk->id,
            'description' => $rk->description,
            'status' => $rk->status,
            'progress' => $rk->progress ?? 0,
            'final_evidence' => $rk->final_evidence ?? null,

            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ] : null,

            'project' => $project ? [
                'id' => $project->id,
                'name' => $project->name,
                'status' => $project->status ?? null,
                'progress' => $project->progress ?? 0,
                'start_date' => $project->start_date,
                'end_date' => $project->end_date,

                'leader' => $leader ? [
                    'id' => $leader->id,
                    'name' => $leader->name,
                    'email' => $leader->email,
                ] : null,

                'team' => $team ? [
                    'id' => $team->id,
                    'name' => $team->name,
                ] : null,

                'rk_ketua' => $rkKetua ? [
                    'id' => $rkKetua->id,
                    'description' => $rkKetua->description,

                    'iku' => $iku ? [
                        'id' => $iku->id,
                        'name' => $iku->name,
                        'year' => $iku->year,
                    ] : null,
                ] : null,

                'rkKetua' => $rkKetua ? [
                    'id' => $rkKetua->id,
                    'description' => $rkKetua->description,

                    'iku' => $iku ? [
                        'id' => $iku->id,
                        'name' => $iku->name,
                        'year' => $iku->year,
                    ] : null,
                ] : null,
            ] : null,
        ] : null;

        return [
            'id' => $task->id,
            'rk_anggota_id' => $task->rk_anggota_id,
            'iki_id' => $task->iki_id,
            'date' => $task->date,
            'activity' => $task->activity,
            'output' => $task->output,
            'evidence_url' => $task->evidence_url,
            'status' => $task->status,
            'approved_by' => $task->approved_by,
            'approved_at' => $task->approved_at,
            'review_note' => $task->review_note,
            'created_at' => $task->created_at,
            'updated_at' => $task->updated_at,

            'iki' => $ikiPayload,
            'rk_anggota' => $rkPayload,
            'rkAnggota' => $rkPayload,
        ];
    }
}