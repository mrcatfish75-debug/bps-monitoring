<?php

namespace App\Http\Controllers;

use App\Models\RkKetua;
use App\Models\RkKetuaTemplate;
use App\Models\Iku;
use App\Models\Team;
use App\Models\Iki;
use Illuminate\Http\Request;

class RkKetuaController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $year = $request->year ?? date('Y');

        $ikus = Iku::where('year', $year)->get();

        if ($user->role === 'ketua') {
            $teams = Team::with('leader')
                ->where('leader_id', $user->id)
                ->get();
        } else {
            $teams = Team::with('leader')->get();
        }

        /*
        |--------------------------------------------------------------------------
        | RK Ketua Index
        |--------------------------------------------------------------------------
        | Progress tidak dihitung manual di controller.
        | Progress RK Ketua berasal dari Model RkKetua:
        |
        | RK Ketua -> Project -> RK Anggota -> IKI Approved
        |
        | Daily Task tidak masuk progress utama secara langsung.
        | Daily Task berada di bawah IKI sebagai bukti/proses kerja.
        |--------------------------------------------------------------------------
        */
        $query = RkKetua::with([
                'iku',
                'team.leader',
                'projects.members',
                'projects.rkAnggotas.user',
                'projects.rkAnggotas.ikis.dailyTasks',
                'projects.rkAnggotas.ikis.approver',
            ])
            ->whereHas('iku', fn ($q) => $q->where('year', $year));

        if ($user->role === 'ketua') {
            $query->where('user_id', $user->id);
        }

        if ($request->iku_id) {
            $query->where('iku_id', $request->iku_id);
        }

        if ($request->team_id) {
            if ($user->role === 'ketua') {
                $allowedTeamIds = $teams->pluck('id')->toArray();

                if (!in_array((int) $request->team_id, $allowedTeamIds, true)) {
                    abort(403, 'Kamu hanya boleh melihat RK Ketua dari tim yang kamu pimpin.');
                }
            }

            $query->where('team_id', $request->team_id);
        }

        if ($request->search) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhereHas('iku', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('team', function ($q3) use ($search) {
                        $q3->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('projects', function ($q4) use ($search) {
                        $q4->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('projects.rkAnggotas', function ($q5) use ($search) {
                        $q5->where('description', 'like', "%{$search}%");
                    })
                    ->orWhereHas('projects.rkAnggotas.ikis', function ($q6) use ($search) {
                        $q6->where('description', 'like', "%{$search}%")
                            ->orWhere('final_evidence', 'like', "%{$search}%");
                    });
            });
        }

        $rkKetuas = $query
            ->latest()
            ->paginate(10)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Compatibility untuk Blade lama
        |--------------------------------------------------------------------------
        | Tetap isi property ringkasan agar view lama tidak langsung rusak.
        | Nilainya sekarang berbasis IKI, bukan status approved RK lama.
        |--------------------------------------------------------------------------
        */
        $rkKetuas->getCollection()->transform(function ($rk) {
            $summary = $this->buildRkKetuaSummary($rk);

            $rk->project_count = $summary['project_count'];
            $rk->rk_anggota_count = $summary['rk_anggota_count'];
            $rk->completed_rk_anggota_count = $summary['completed_rk_anggota_count'];
            $rk->total_iki_count = $summary['total_iki_count'];
            $rk->approved_iki_count = $summary['approved_iki_count'];
            $rk->daily_task_count = $summary['daily_task_count'];

            /*
            |--------------------------------------------------------------------------
            | Legacy alias
            |--------------------------------------------------------------------------
            | Untuk Blade lama yang mungkin masih membaca approved_rk_anggota_count.
            | Maknanya sekarang RK selesai dari IKI, bukan status RK approved.
            |--------------------------------------------------------------------------
            */
            $rk->approved_rk_anggota_count = $summary['completed_rk_anggota_count'];

            return $rk;
        });

        $search = $request->search;

        /*
        |--------------------------------------------------------------------------
        | RK Ketua Template Picker
        |--------------------------------------------------------------------------
        | Template berasal dari tabel rk_ketua_templates yang sudah diimport
        | dari Excel. Data ini hanya dipakai untuk membantu mengisi textarea
        | description di form Add/Edit RK Ketua, bukan untuk membuat RK Ketua
        | otomatis.
        |--------------------------------------------------------------------------
        */
        $rkKetuaTemplates = RkKetuaTemplate::active()
            ->orderBy('description')
            ->get();

        return view('rk_ketua.index', compact(
            'rkKetuas',
            'ikus',
            'teams',
            'year',
            'search',
            'rkKetuaTemplates'
        ));
    }

    public function store(Request $request)
    {
        $this->abortIfMonitoringOnly();

        $user = auth()->user();

        $request->validate([
            'iku_id' => 'required|exists:ikus,id',
            'team_id' => 'required|exists:teams,id',
            'description' => 'required|string',
        ]);

        if ($user->role === 'ketua') {
            $team = Team::where('leader_id', $user->id)
                ->where('id', $request->team_id)
                ->firstOrFail();

            $ownerId = $user->id;
        } else {
            $team = Team::findOrFail($request->team_id);
            $ownerId = $team->leader_id;
        }

        RkKetua::create([
            'iku_id' => $request->iku_id,
            'team_id' => $team->id,
            'user_id' => $ownerId,
            'description' => $request->description,
        ]);

        return back()->with('success', 'RK Ketua berhasil dibuat');
    }

    public function update(Request $request, $id)
    {
        $this->abortIfMonitoringOnly();

        $user = auth()->user();

        $rk = RkKetua::findOrFail($id);

        if ($user->role === 'ketua' && (int) $rk->user_id !== (int) $user->id) {
            abort(403, 'Kamu hanya boleh mengubah RK Ketua milikmu sendiri.');
        }

        $request->validate([
            'iku_id' => 'required|exists:ikus,id',
            'team_id' => 'required|exists:teams,id',
            'description' => 'required|string',
        ]);

        if ($user->role === 'ketua') {
            $team = Team::where('leader_id', $user->id)
                ->where('id', $request->team_id)
                ->firstOrFail();

            $ownerId = $user->id;
        } else {
            $team = Team::findOrFail($request->team_id);
            $ownerId = $team->leader_id;
        }

        $rk->update([
            'iku_id' => $request->iku_id,
            'team_id' => $team->id,
            'user_id' => $ownerId,
            'description' => $request->description,
        ]);

        return back()->with('success', 'RK Ketua berhasil diupdate');
    }

    public function destroy($id)
    {
        $this->abortIfMonitoringOnly();

        $user = auth()->user();

        $rk = RkKetua::findOrFail($id);

        if ($user->role === 'ketua' && (int) $rk->user_id !== (int) $user->id) {
            abort(403, 'Kamu hanya boleh menghapus RK Ketua milikmu sendiri.');
        }

        if ($rk->projects()->exists()) {
            return back()->with('error', 'Masih ada project');
        }

        $rk->delete();

        return back()->with('success', 'RK Ketua dihapus');
    }

    public function show($id)
    {
        $user = auth()->user();

        if (!$user) {
            abort(403);
        }

        $rk = RkKetua::with([
            'iku',
            'team.leader',
            'projects.members',
            'projects.leader',
            'projects.rkAnggotas.user',
            'projects.rkAnggotas.ikis.dailyTasks',
            'projects.rkAnggotas.ikis.approver',
        ])->findOrFail($id);

        /*
        |--------------------------------------------------------------------------
        | Authorization
        |--------------------------------------------------------------------------
        | Admin:
        | - boleh melihat semua RK Ketua.
        |
        | Kepala:
        | - monitoring semua RK Ketua.
        |
        | Ketua:
        | - hanya boleh melihat RK Ketua miliknya sendiri.
        |--------------------------------------------------------------------------
        */
        if ($user->role === 'ketua' && (int) $rk->user_id !== (int) $user->id) {
            abort(403, 'Kamu hanya boleh melihat RK Ketua milikmu sendiri.');
        }

        return response()->json($this->formatRkKetuaForJson($rk));
    }

    public function search(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            abort(403);
        }

        $year = $request->year ?? date('Y');

        $query = RkKetua::with([
                'iku',
                'team.leader',
                'projects.members',
                'projects.rkAnggotas.user',
                'projects.rkAnggotas.ikis.dailyTasks',
                'projects.rkAnggotas.ikis.approver',
            ])
            ->whereHas('iku', function ($q) use ($year) {
                $q->where('year', $year);
            });

        /*
        |--------------------------------------------------------------------------
        | Role Scope
        |--------------------------------------------------------------------------
        | Ketua hanya boleh mencari RK Ketua miliknya sendiri.
        |--------------------------------------------------------------------------
        */
        if ($user->role === 'ketua') {
            $query->where('user_id', $user->id);
        }

        /*
        |--------------------------------------------------------------------------
        | Filter IKU
        |--------------------------------------------------------------------------
        */
        if ($request->filled('iku_id')) {
            $query->where('iku_id', $request->iku_id);
        }

        /*
        |--------------------------------------------------------------------------
        | Filter Team
        |--------------------------------------------------------------------------
        | Ketua hanya boleh filter tim yang dia pimpin.
        |--------------------------------------------------------------------------
        */
        if ($request->filled('team_id')) {
            if ($user->role === 'ketua') {
                $isAllowedTeam = Team::where('leader_id', $user->id)
                    ->where('id', $request->team_id)
                    ->exists();

                if (!$isAllowedTeam) {
                    abort(403, 'Kamu hanya boleh mencari RK Ketua dari tim yang kamu pimpin.');
                }
            }

            $query->where('team_id', $request->team_id);
        }

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhereHas('iku', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('team', function ($q3) use ($search) {
                        $q3->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('projects', function ($q4) use ($search) {
                        $q4->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('projects.rkAnggotas', function ($q5) use ($search) {
                        $q5->where('description', 'like', "%{$search}%");
                    })
                    ->orWhereHas('projects.rkAnggotas.ikis', function ($q6) use ($search) {
                        $q6->where('description', 'like', "%{$search}%")
                            ->orWhere('final_evidence', 'like', "%{$search}%");
                    });
            });
        }

        $rkKetuas = $query
            ->latest()
            ->limit(20)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Search Response
        |--------------------------------------------------------------------------
        | Progress RK Ketua berasal dari Model RkKetua.
        | Ringkasan sekarang berbasis IKI.
        |--------------------------------------------------------------------------
        */
        $data = $rkKetuas->map(function ($rk) {
            $summary = $this->buildRkKetuaSummary($rk);

            return [
                'id' => $rk->id,
                'iku_id' => $rk->iku_id,
                'team_id' => $rk->team_id,
                'user_id' => $rk->user_id,
                'description' => $rk->description,

                'project_count' => $summary['project_count'],
                'rk_anggota_count' => $summary['rk_anggota_count'],
                'completed_rk_anggota_count' => $summary['completed_rk_anggota_count'],
                'total_iki_count' => $summary['total_iki_count'],
                'approved_iki_count' => $summary['approved_iki_count'],
                'submitted_iki_count' => $summary['submitted_iki_count'],
                'rejected_iki_count' => $summary['rejected_iki_count'],
                'daily_task_count' => $summary['daily_task_count'],

                /*
                |--------------------------------------------------------------------------
                | Legacy alias
                |--------------------------------------------------------------------------
                */
                'approved_rk_anggota_count' => $summary['completed_rk_anggota_count'],

                'progress' => $rk->progress,
                'progress_label' => $rk->progress_label ?? null,

                'iku' => $rk->iku ? [
                    'id' => $rk->iku->id,
                    'name' => $rk->iku->name,
                    'year' => $rk->iku->year,
                ] : null,

                'team' => $rk->team ? [
                    'id' => $rk->team->id,
                    'name' => $rk->team->name,
                    'leader' => $rk->team->leader ? [
                        'id' => $rk->team->leader->id,
                        'name' => $rk->team->leader->name,
                    ] : null,
                ] : null,
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
    | IKI-aware RK Ketua Helpers
    |--------------------------------------------------------------------------
    */

    private function buildRkKetuaSummary(RkKetua $rk): array
    {
        $rk->loadMissing([
            'projects.members',
            'projects.rkAnggotas.ikis.dailyTasks',
        ]);

        $projects = $rk->projects;

        $rkAnggotas = $projects
            ->flatMap(fn ($project) => $project->rkAnggotas)
            ->values();

        $ikis = $rkAnggotas
            ->flatMap(fn ($rkAnggota) => $rkAnggota->ikis)
            ->values();

        $dailyTasks = $ikis
            ->flatMap(fn ($iki) => $iki->dailyTasks)
            ->values();

        $completedRkAnggotas = $rkAnggotas
            ->filter(fn ($rkAnggota) => $rkAnggota->is_completed)
            ->count();

        return [
            'project_count' => $projects->count(),

            'rk_anggota_count' => $rkAnggotas->count(),
            'completed_rk_anggota_count' => $completedRkAnggotas,

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

    private function buildProjectSummary($project): array
    {
        $project->loadMissing([
            'members',
            'rkAnggotas.ikis.dailyTasks',
        ]);

        $rkAnggotas = $project->rkAnggotas;

        $ikis = $rkAnggotas
            ->flatMap(fn ($rkAnggota) => $rkAnggota->ikis)
            ->values();

        $dailyTasks = $ikis
            ->flatMap(fn ($iki) => $iki->dailyTasks)
            ->values();

        $completedRkAnggotas = $rkAnggotas
            ->filter(fn ($rkAnggota) => $rkAnggota->is_completed)
            ->count();

        return [
            'members_count' => $project->members->count(),

            'rk_anggota_count' => $rkAnggotas->count(),
            'completed_rk_anggota_count' => $completedRkAnggotas,

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

    private function formatRkKetuaForJson(RkKetua $rk): array
    {
        $summary = $this->buildRkKetuaSummary($rk);

        $projects = $rk->projects
            ->map(function ($project) {
                $projectSummary = $this->buildProjectSummary($project);

                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'status' => $project->status ?? '-',
                    'progress' => $project->progress,
                    'progress_label' => $project->progress_label ?? null,

                    'members_count' => $projectSummary['members_count'],

                    'rk_anggota_count' => $projectSummary['rk_anggota_count'],
                    'completed_rk_anggota_count' => $projectSummary['completed_rk_anggota_count'],

                    'total_iki_count' => $projectSummary['total_iki_count'],
                    'approved_iki_count' => $projectSummary['approved_iki_count'],
                    'submitted_iki_count' => $projectSummary['submitted_iki_count'],
                    'rejected_iki_count' => $projectSummary['rejected_iki_count'],
                    'daily_task_count' => $projectSummary['daily_task_count'],

                    /*
                    |--------------------------------------------------------------------------
                    | Legacy alias
                    |--------------------------------------------------------------------------
                    | Supaya view lama tidak error.
                    |--------------------------------------------------------------------------
                    */
                    'approved_rk_count' => $projectSummary['completed_rk_anggota_count'],

                    'leader' => $project->leader ? [
                        'id' => $project->leader->id,
                        'name' => $project->leader->name,
                        'email' => $project->leader->email,
                    ] : null,
                ];
            })
            ->values();

        return [
            'id' => $rk->id,
            'iku_id' => $rk->iku_id,
            'team_id' => $rk->team_id,
            'user_id' => $rk->user_id,
            'description' => $rk->description,

            'progress' => $rk->progress,
            'progress_label' => $rk->progress_label ?? null,

            'project_count' => $summary['project_count'],

            'rk_anggota_count' => $summary['rk_anggota_count'],
            'completed_rk_anggota_count' => $summary['completed_rk_anggota_count'],

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
            'approved_rk_anggota_count' => $summary['completed_rk_anggota_count'],

            'iku' => $rk->iku ? [
                'id' => $rk->iku->id,
                'name' => $rk->iku->name,
                'year' => $rk->iku->year,
            ] : null,

            'team' => $rk->team ? [
                'id' => $rk->team->id,
                'name' => $rk->team->name,
                'leader' => $rk->team->leader ? [
                    'id' => $rk->team->leader->id,
                    'name' => $rk->team->leader->name,
                ] : null,
            ] : null,

            'projects' => $projects,
        ];
    }
}