<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Team;
use App\Models\RkKetua;
use App\Models\RkAnggota;
use App\Models\Iki;
use App\Models\User;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    /**
     * INDEX
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $year = $request->year ?? date('Y');

        /*
        |--------------------------------------------------------------------------
        | Project Index
        |--------------------------------------------------------------------------
        | Progress project tidak dihitung manual di controller.
        | Progress berasal dari Model Project:
        |
        | Project -> RK Anggota -> IKI Approved
        |
        | Daily Task tidak masuk progress utama secara langsung.
        | Daily Task berada di bawah IKI sebagai bukti/proses kerja.
        |--------------------------------------------------------------------------
        */
        $query = Project::with([
                'team',
                'leader',
                'rkKetua.iku',
                'rkKetua.team',
                'rkKetua.ketua',
                'members',

                /*
                |--------------------------------------------------------------------------
                | IKI Flow
                |--------------------------------------------------------------------------
                | RK Anggota sekarang hanya wadah.
                | Progress RK Anggota dan Project dihitung dari IKI.
                |--------------------------------------------------------------------------
                */
                'rkAnggotas.user',
                'rkAnggotas.ikis.dailyTasks',
                'rkAnggotas.ikis.approver',
            ])
            ->whereHas('rkKetua.iku', function ($q) use ($year) {
                $q->where('year', $year);
            });

        /*
        |--------------------------------------------------------------------------
        | Role Scope Project
        |--------------------------------------------------------------------------
        | Admin:
        | - melihat semua project.
        |
        | Kepala:
        | - monitoring semua project.
        |
        | Ketua:
        | - melihat project yang dia pimpin.
        | - melihat project yang dia ikuti sebagai anggota project.
        |
        | Anggota:
        | - melihat project yang dia ikuti sebagai anggota project.
        |--------------------------------------------------------------------------
        */
        if ($user->role === 'ketua') {
            $query->where(function ($q) use ($user) {
                $q->where('leader_id', $user->id)
                    ->orWhereHas('members', function ($sub) use ($user) {
                        $sub->where('users.id', $user->id);
                    });
            });
        } elseif ($user->role === 'anggota') {
            $query->whereHas('members', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Filter Team
        |--------------------------------------------------------------------------
        */
        if ($request->filled('team_id')) {
            $query->where('team_id', $request->team_id);
        }

        /*
        |--------------------------------------------------------------------------
        | Filter RK Ketua
        |--------------------------------------------------------------------------
        */
        if ($request->filled('rk_ketua_id')) {
            $query->where('rk_ketua_id', $request->rk_ketua_id);
        }

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('rkKetua', function ($sub) use ($search) {
                        $sub->where('description', 'like', "%{$search}%");
                    })
                    ->orWhereHas('team', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('leader', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('members', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('rkAnggotas', function ($sub) use ($search) {
                        $sub->where('description', 'like', "%{$search}%");
                    })
                    ->orWhereHas('rkAnggotas.ikis', function ($sub) use ($search) {
                        $sub->where('description', 'like', "%{$search}%")
                            ->orWhere('final_evidence', 'like', "%{$search}%");
                    });
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Sorting
        |--------------------------------------------------------------------------
        */
        $allowedSorts = [
            'name',
            'created_at',
            'start_date',
            'end_date',
            'status',
        ];

        if (
            $request->filled('sort')
            && in_array($request->sort, $allowedSorts, true)
        ) {
            $direction = $request->direction === 'desc' ? 'desc' : 'asc';
            $query->orderBy($request->sort, $direction);
        } else {
            $query->latest();
        }

        $projects = $query
            ->paginate(10)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Team Filter Options
        |--------------------------------------------------------------------------
        | Admin/Kepala:
        | - semua tim.
        |
        | Ketua:
        | - tim dari project yang dia pimpin/ikuti
        | - ditambah tim yang dia pimpin sendiri agar bisa membuat project baru.
        |
        | Anggota:
        | - hanya tim dari project yang dia ikuti.
        |--------------------------------------------------------------------------
        */
        $teamQuery = Team::with('leader');

        if ($user->role === 'ketua') {
            $teamQuery->where(function ($q) use ($user, $year) {
                $q->where('leader_id', $user->id)
                    ->orWhereHas('projects', function ($projectQ) use ($user, $year) {
                        $projectQ->whereHas('rkKetua.iku', function ($sub) use ($year) {
                            $sub->where('year', $year);
                        })
                        ->where(function ($sub) use ($user) {
                            $sub->where('leader_id', $user->id)
                                ->orWhereHas('members', function ($memberQ) use ($user) {
                                    $memberQ->where('users.id', $user->id);
                                });
                        });
                    });
            });
        } elseif ($user->role === 'anggota') {
            $teamQuery->whereHas('projects', function ($q) use ($user, $year) {
                $q->whereHas('rkKetua.iku', function ($sub) use ($year) {
                    $sub->where('year', $year);
                })
                ->whereHas('members', function ($memberQ) use ($user) {
                    $memberQ->where('users.id', $user->id);
                });
            });
        }

        $teams = $teamQuery
            ->orderBy('name')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | RK Ketua Options
        |--------------------------------------------------------------------------
        | Untuk Ketua, dropdown Add Project harus menampilkan RK Ketua miliknya
        | sendiri, termasuk RK Ketua yang belum punya project.
        |--------------------------------------------------------------------------
        */
        $rkKetuaQuery = RkKetua::with([
                'iku',
                'team',
                'team.leader',
                'ketua',
                'projects.rkAnggotas.ikis',
            ])
            ->whereHas('iku', function ($q) use ($year) {
                $q->where('year', $year);
            });

        if ($user->role === 'ketua') {
            $rkKetuaQuery->where('user_id', $user->id);
        } elseif ($user->role === 'anggota') {
            $rkKetuaQuery->whereHas('projects.members', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }

        $rkKetuas = $rkKetuaQuery
            ->latest()
            ->get();

        return view('project.index', compact(
            'projects',
            'teams',
            'rkKetuas',
            'request'
        ));
    }

    /**
     * STORE
     */
    public function store(Request $request)
    {
        $this->abortIfMonitoringOnly();

        $request->validate([
            'name' => 'required|string|max:255',
            'rk_ketua_id' => 'required|exists:rk_ketuas,id',
            'members' => 'required|array|min:1',
            'members.*' => 'exists:users,id',
        ]);

        $user = auth()->user();

        $rk = RkKetua::with(['ketua', 'team'])->findOrFail($request->rk_ketua_id);

        if (!$rk->ketua || $rk->ketua->role !== 'ketua') {
            return back()->with('error', 'RK Ketua harus memiliki user dengan role ketua.');
        }

        /*
        |--------------------------------------------------------------------------
        | Ketua hanya boleh membuat project dari RK Ketua miliknya.
        |--------------------------------------------------------------------------
        */
        if ($user->role === 'ketua' && (int) $rk->user_id !== (int) $user->id) {
            abort(403, 'Kamu hanya boleh membuat project dari RK Ketua milikmu sendiri.');
        }

        $selectedMembers = array_map('intval', $request->members ?? []);
        $leaderId = (int) $rk->user_id;

        /*
        |--------------------------------------------------------------------------
        | Leader Tidak Boleh Menjadi Member Project Sendiri
        |--------------------------------------------------------------------------
        | Ketua boleh menjadi anggota project lain, tapi tidak boleh menjadi
        | anggota di project yang dia pimpin sendiri.
        |--------------------------------------------------------------------------
        */
        if (in_array($leaderId, $selectedMembers, true)) {
            return back()
                ->withInput()
                ->with('error', 'Ketua project tidak boleh ditambahkan sebagai anggota di project yang dia pimpin sendiri.');
        }

        /*
        |--------------------------------------------------------------------------
        | Anggota project bebas dari pegawai operasional.
        | Tidak dikunci team_id.
        |--------------------------------------------------------------------------
        */
        $validMembers = User::whereIn('id', $selectedMembers)
            ->whereIn('role', ['anggota', 'ketua'])
            ->where('id', '!=', $leaderId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        if (count($validMembers) !== count($selectedMembers)) {
            return back()
                ->withInput()
                ->with('error', 'Ada user yang tidak valid sebagai anggota project.');
        }

        $project = Project::create([
            'name' => $request->name,
            'rk_ketua_id' => $rk->id,
            'team_id' => $rk->team_id,
            'leader_id' => $leaderId,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => $request->status ?? 'active',
        ]);

        $project->members()->sync($validMembers);

        return back()->with('success', 'Project berhasil dibuat.');
    }

    /**
     * SHOW (AJAX VIEW)
     */
    public function show($id)
    {
        $project = Project::with([
            'team',
            'leader',
            'rkKetua.iku',
            'rkKetua.ketua',
            'members' => function ($q) {
                $q->whereIn('role', ['anggota', 'ketua']);
            },

            /*
            |--------------------------------------------------------------------------
            | IKI Monitoring Detail
            |--------------------------------------------------------------------------
            */
            'rkAnggotas.user',
            'rkAnggotas.ikis.dailyTasks',
            'rkAnggotas.ikis.approver',

            /*
            |--------------------------------------------------------------------------
            | Legacy fallback
            |--------------------------------------------------------------------------
            */
            'rkAnggotas.dailyTasks',
        ])->findOrFail($id);

        $user = auth()->user();

        if ($user->role === 'ketua') {
            $isLeader = (int) $project->leader_id === (int) $user->id;

            $isMember = $project->members()
                ->where('users.id', $user->id)
                ->exists();

            if (!$isLeader && !$isMember) {
                abort(403, 'Kamu hanya boleh melihat project yang kamu pimpin atau kamu ikuti.');
            }
        }

        if ($user->role === 'anggota') {
            $isMember = $project->members()
                ->where('users.id', $user->id)
                ->exists();

            if (!$isMember) {
                abort(403, 'Kamu hanya boleh melihat project yang kamu ikuti.');
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Kepala
        |--------------------------------------------------------------------------
        | Kepala boleh melihat semua project untuk monitoring.
        |--------------------------------------------------------------------------
        */

        return response()->json($this->formatProjectForJson($project));
    }

    /**
     * UPDATE
     */
    public function update(Request $request, $id)
    {
        $this->abortIfMonitoringOnly();

        $project = Project::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'rk_ketua_id' => 'required|exists:rk_ketuas,id',
            'members' => 'required|array|min:1',
            'members.*' => 'exists:users,id',
        ]);

        $user = auth()->user();

        if ($user->role === 'ketua' && (int) $project->leader_id !== (int) $user->id) {
            abort(403, 'Kamu hanya boleh mengubah project yang kamu pimpin.');
        }

        $rk = RkKetua::with(['ketua', 'team'])->findOrFail($request->rk_ketua_id);

        if (!$rk->ketua || $rk->ketua->role !== 'ketua') {
            return back()->with('error', 'RK Ketua harus memiliki user dengan role ketua.');
        }

        if ($user->role === 'ketua' && (int) $rk->user_id !== (int) $user->id) {
            abort(403, 'Kamu hanya boleh memakai RK Ketua milikmu sendiri.');
        }

        $selectedMembers = array_map('intval', $request->members ?? []);
        $leaderId = (int) $rk->user_id;

        /*
        |--------------------------------------------------------------------------
        | Leader Tidak Boleh Menjadi Member Project Sendiri
        |--------------------------------------------------------------------------
        */
        if (in_array($leaderId, $selectedMembers, true)) {
            return back()
                ->withInput()
                ->with('error', 'Ketua project tidak boleh ditambahkan sebagai anggota di project yang dia pimpin sendiri.');
        }

        /*
        |--------------------------------------------------------------------------
        | Anggota project bebas dari pegawai operasional.
        | Tidak dikunci team_id.
        |--------------------------------------------------------------------------
        */
        $validMembers = User::whereIn('id', $selectedMembers)
            ->whereIn('role', ['anggota', 'ketua'])
            ->where('id', '!=', $leaderId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        if (count($validMembers) !== count($selectedMembers)) {
            return back()
                ->withInput()
                ->with('error', 'Ada user yang tidak valid sebagai anggota project.');
        }

        $project->update([
            'name' => $request->name,
            'rk_ketua_id' => $rk->id,
            'team_id' => $rk->team_id,
            'leader_id' => $leaderId,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => $request->status ?? $project->status,
        ]);

        $project->members()->sync($validMembers);

        return back()->with('success', 'Project berhasil diupdate.');
    }

    /**
     * DELETE
     */
    public function destroy($id)
    {
        $this->abortIfMonitoringOnly();

        $project = Project::findOrFail($id);

        $user = auth()->user();

        if ($user->role === 'ketua' && (int) $project->leader_id !== (int) $user->id) {
            abort(403, 'Kamu hanya boleh menghapus project yang kamu pimpin.');
        }

        $project->members()->detach();

        $project->delete();

        return back()->with('success', 'Project dihapus.');
    }

    /**
     * AJAX MEMBER
     */
    public function getMembers($id)
    {
        $rk = RkKetua::with('ketua')->findOrFail($id);

        $leaderId = (int) $rk->user_id;

        /*
        |--------------------------------------------------------------------------
        | Ambil calon anggota project
        |--------------------------------------------------------------------------
        | Role ketua tetap boleh muncul sebagai anggota project lain.
        | Namun ketua dari RK Ketua ini dikeluarkan agar tidak bisa menjadi
        | anggota di project yang dia pimpin sendiri.
        |--------------------------------------------------------------------------
        */
        $members = User::whereIn('role', ['anggota', 'ketua'])
            ->where('id', '!=', $leaderId)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'email',
                'role',
                'team_id',
            ]);

        return response()->json($members);
    }

    /**
     * SEARCH
     */
    public function search(Request $request)
    {
        $user = auth()->user();
        $year = $request->year ?? date('Y');

        $query = Project::with([
                'team',
                'leader',
                'rkKetua.iku',
                'rkKetua.ketua',
                'members',
                'rkAnggotas.user',
                'rkAnggotas.ikis.dailyTasks',
            ])
            ->whereHas('rkKetua.iku', function ($q) use ($year) {
                $q->where('year', $year);
            });

        /*
        |--------------------------------------------------------------------------
        | Role Scope Search
        |--------------------------------------------------------------------------
        */
        if ($user && $user->role === 'ketua') {
            $query->where(function ($q) use ($user) {
                $q->where('leader_id', $user->id)
                    ->orWhereHas('members', function ($sub) use ($user) {
                        $sub->where('users.id', $user->id);
                    });
            });
        } elseif ($user && $user->role === 'anggota') {
            $query->whereHas('members', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }

        if ($request->filled('team_id')) {
            $query->where('team_id', $request->team_id);
        }

        if ($request->filled('rk_ketua_id')) {
            $query->where('rk_ketua_id', $request->rk_ketua_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('rkKetua', function ($sub) use ($search) {
                        $sub->where('description', 'like', "%{$search}%");
                    })
                    ->orWhereHas('team', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('leader', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('members', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('rkAnggotas', function ($sub) use ($search) {
                        $sub->where('description', 'like', "%{$search}%");
                    })
                    ->orWhereHas('rkAnggotas.ikis', function ($sub) use ($search) {
                        $sub->where('description', 'like', "%{$search}%")
                            ->orWhere('final_evidence', 'like', "%{$search}%");
                    });
            });
        }

        $projects = $query
            ->latest()
            ->limit(20)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Search Response
        |--------------------------------------------------------------------------
        | Progress project berasal dari Model Project.
        | Tidak dihitung manual dari status RK Anggota.
        |--------------------------------------------------------------------------
        */
        $data = $projects->map(function ($project) use ($user) {
            $members = $project->members
                ->whereIn('role', ['anggota', 'ketua'])
                ->values();

            $summary = $this->buildProjectSummary($project);

            $canManage = false;

            if ($user && $user->role === 'admin') {
                $canManage = true;
            }

            if (
                $user
                && $user->role === 'ketua'
                && (int) $project->leader_id === (int) $user->id
            ) {
                $canManage = true;
            }

            return [
                'id' => $project->id,
                'name' => $project->name,
                'can_manage' => $canManage,

                'team' => [
                    'id' => $project->team->id ?? null,
                    'name' => $project->team->name ?? '-',
                ],

                'leader' => [
                    'id' => $project->leader->id ?? null,
                    'name' => $project->leader->name ?? '-',
                ],

                'members_count' => $members->count(),
                'members_names' => $members->pluck('name')->take(3)->implode(', '),

                'rk_ketua' => [
                    'id' => $project->rkKetua->id ?? null,
                    'description' => $project->rkKetua->description ?? '-',
                    'iku' => [
                        'id' => $project->rkKetua->iku->id ?? null,
                        'name' => $project->rkKetua->iku->name ?? '-',
                    ],
                ],

                'progress' => $project->progress,
                'progress_label' => $project->progress_label ?? null,

                /*
                |--------------------------------------------------------------------------
                | New IKI-aware summary
                |--------------------------------------------------------------------------
                */
                'total_rk_count' => $summary['total_rk_count'],
                'completed_rk_count' => $summary['completed_rk_count'],
                'total_iki_count' => $summary['total_iki_count'],
                'approved_iki_count' => $summary['approved_iki_count'],
                'submitted_iki_count' => $summary['submitted_iki_count'],
                'rejected_iki_count' => $summary['rejected_iki_count'],
                'daily_task_count' => $summary['daily_task_count'],

                /*
                |--------------------------------------------------------------------------
                | Legacy aliases
                |--------------------------------------------------------------------------
                | Tetap dikirim agar JS lama tidak langsung rusak.
                | Nilainya sekarang bermakna "RK completed dari IKI", bukan status RK.
                |--------------------------------------------------------------------------
                */
                'approved_rk_count' => $summary['completed_rk_count'],
                'total_rk_count_legacy' => $summary['total_rk_count'],
            ];
        });

        return response()->json($data);
    }

    private function abortIfMonitoringOnly(): void
    {
        if (auth()->user()?->role === 'kepala') {
            abort(403, 'Kepala hanya dapat melakukan monitoring.');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | IKI-aware Project Helpers
    |--------------------------------------------------------------------------
    */

    private function buildProjectSummary(Project $project): array
    {
        $project->loadMissing([
            'rkAnggotas.ikis.dailyTasks',
        ]);

        $rkAnggotas = $project->rkAnggotas;

        $ikis = $rkAnggotas
            ->flatMap(fn ($rk) => $rk->ikis)
            ->values();

        $dailyTasks = $ikis
            ->flatMap(fn ($iki) => $iki->dailyTasks)
            ->values();

        $completedRk = $rkAnggotas
            ->filter(fn ($rk) => $rk->is_completed)
            ->count();

        return [
            'total_rk_count' => $rkAnggotas->count(),
            'completed_rk_count' => $completedRk,

            'total_iki_count' => $ikis->count(),
            'approved_iki_count' => $ikis
                ->where('status', Iki::STATUS_APPROVED)
                ->count(),

            'submitted_iki_count' => $ikis
                ->where('status', Iki::STATUS_SUBMITTED)
                ->count(),

            'rejected_iki_count' => $ikis
                ->where('status', Iki::STATUS_REJECTED)
                ->count(),

            'draft_iki_count' => $ikis
                ->where('status', Iki::STATUS_DRAFT)
                ->count(),

            'daily_task_count' => $dailyTasks->count(),
        ];
    }

    private function formatProjectForJson(Project $project): array
    {
        $summary = $this->buildProjectSummary($project);

        $members = $project->members
            ->whereIn('role', ['anggota', 'ketua'])
            ->values()
            ->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'nip' => $member->nip,
                    'email' => $member->email,
                    'role' => $member->role,
                    'team_id' => $member->team_id,
                ];
            });

        $rkAnggotas = $project->rkAnggotas
            ->map(function ($rk) {
                $ikis = $rk->ikis;

                return [
                    'id' => $rk->id,
                    'description' => $rk->description,
                    'target' => $rk->target,
                    'status' => $rk->status,
                    'status_label' => $rk->status_label,

                    'progress' => $rk->progress,
                    'is_completed' => $rk->is_completed,

                    'user' => $rk->user ? [
                        'id' => $rk->user->id,
                        'name' => $rk->user->name,
                        'email' => $rk->user->email,
                        'role' => $rk->user->role,
                    ] : null,

                    'iki_count' => $ikis->count(),
                    'approved_iki_count' => $ikis
                        ->where('status', Iki::STATUS_APPROVED)
                        ->count(),
                    'submitted_iki_count' => $ikis
                        ->where('status', Iki::STATUS_SUBMITTED)
                        ->count(),
                    'rejected_iki_count' => $ikis
                        ->where('status', Iki::STATUS_REJECTED)
                        ->count(),

                    'daily_task_count' => $ikis
                        ->flatMap(fn ($iki) => $iki->dailyTasks)
                        ->count(),

                    'ikis' => $ikis->map(function ($iki) {
                        return [
                            'id' => $iki->id,
                            'description' => $iki->description,
                            'target' => $iki->target,
                            'unit' => $iki->unit,
                            'status' => $iki->status,
                            'status_label' => $iki->status_label,
                            'progress' => $iki->progress,
                            'final_evidence' => $iki->final_evidence,
                            'submitted_at' => $iki->submitted_at,
                            'approved_at' => $iki->approved_at,
                            'rejection_note' => $iki->rejection_note,
                            'daily_task_count' => $iki->dailyTasks->count(),
                            'approver' => $iki->approver ? [
                                'id' => $iki->approver->id,
                                'name' => $iki->approver->name,
                                'email' => $iki->approver->email,
                            ] : null,
                        ];
                    })->values(),
                ];
            })
            ->values();

        return [
            'id' => $project->id,
            'name' => $project->name,
            'rk_ketua_id' => $project->rk_ketua_id,
            'team_id' => $project->team_id,
            'leader_id' => $project->leader_id,
            'start_date' => $project->start_date,
            'end_date' => $project->end_date,
            'status' => $project->status,
            'created_at' => $project->created_at,
            'updated_at' => $project->updated_at,

            'progress' => $project->progress,
            'progress_label' => $project->progress_label,

            'team' => $project->team ? [
                'id' => $project->team->id,
                'name' => $project->team->name,
                'leader_id' => $project->team->leader_id,
            ] : null,

            'leader' => $project->leader ? [
                'id' => $project->leader->id,
                'name' => $project->leader->name,
                'email' => $project->leader->email,
                'role' => $project->leader->role,
            ] : null,

            'rk_ketua' => $project->rkKetua ? [
                'id' => $project->rkKetua->id,
                'description' => $project->rkKetua->description,
                'target' => $project->rkKetua->target,
                'status' => $project->rkKetua->status,
                'ketua' => $project->rkKetua->ketua ? [
                    'id' => $project->rkKetua->ketua->id,
                    'name' => $project->rkKetua->ketua->name,
                    'email' => $project->rkKetua->ketua->email,
                ] : null,
                'iku' => $project->rkKetua->iku ? [
                    'id' => $project->rkKetua->iku->id,
                    'code' => $project->rkKetua->iku->code,
                    'name' => $project->rkKetua->iku->name,
                    'year' => $project->rkKetua->iku->year,
                    'target' => $project->rkKetua->iku->target,
                    'satuan' => $project->rkKetua->iku->satuan,
                ] : null,
            ] : null,

            /*
            |--------------------------------------------------------------------------
            | CamelCase alias
            |--------------------------------------------------------------------------
            | Tetap disediakan untuk JS lama yang mungkin membaca rkKetua.
            |--------------------------------------------------------------------------
            */
            'rkKetua' => $project->rkKetua ? [
                'id' => $project->rkKetua->id,
                'description' => $project->rkKetua->description,
                'target' => $project->rkKetua->target,
                'status' => $project->rkKetua->status,
                'ketua' => $project->rkKetua->ketua ? [
                    'id' => $project->rkKetua->ketua->id,
                    'name' => $project->rkKetua->ketua->name,
                    'email' => $project->rkKetua->ketua->email,
                ] : null,
                'iku' => $project->rkKetua->iku ? [
                    'id' => $project->rkKetua->iku->id,
                    'code' => $project->rkKetua->iku->code,
                    'name' => $project->rkKetua->iku->name,
                    'year' => $project->rkKetua->iku->year,
                    'target' => $project->rkKetua->iku->target,
                    'satuan' => $project->rkKetua->iku->satuan,
                ] : null,
            ] : null,

            'members' => $members,
            'rk_anggotas' => $rkAnggotas,
            'rkAnggotas' => $rkAnggotas,

            /*
            |--------------------------------------------------------------------------
            | IKI-aware summary
            |--------------------------------------------------------------------------
            */
            'total_rk_count' => $summary['total_rk_count'],
            'completed_rk_count' => $summary['completed_rk_count'],

            'total_iki_count' => $summary['total_iki_count'],
            'approved_iki_count' => $summary['approved_iki_count'],
            'submitted_iki_count' => $summary['submitted_iki_count'],
            'rejected_iki_count' => $summary['rejected_iki_count'],
            'draft_iki_count' => $summary['draft_iki_count'],
            'daily_task_count' => $summary['daily_task_count'],

            /*
            |--------------------------------------------------------------------------
            | Legacy aliases
            |--------------------------------------------------------------------------
            */
            'approved_rk_count' => $summary['completed_rk_count'],
            'total_rk_count_legacy' => $summary['total_rk_count'],
        ];
    }
}