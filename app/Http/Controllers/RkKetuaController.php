<?php

namespace App\Http\Controllers;

use App\Models\RkKetua;
use App\Models\Iku;
use App\Models\Team;
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

    $query = RkKetua::with(['iku', 'team.leader', 'projects'])
        ->whereHas('iku', fn($q) => $q->where('year', $year));

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
                });
        });
    }

    $rkKetuas = $query->latest()->paginate(10);

    $rkKetuas->getCollection()->transform(function ($rk) {
        $rk->project_count = $rk->projects->count();
        return $rk;
    });

    $search = $request->search;

    return view('rk_ketua.index', compact(
        'rkKetuas',
        'ikus',
        'teams',
        'year',
        'search'
    ));
}

    public function store(Request $request)
{
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
        'projects.rkAnggotas',
    ])->findOrFail($id);

    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    | Admin:
    | - boleh melihat semua RK Ketua.
    |
    | Ketua:
    | - hanya boleh melihat RK Ketua miliknya sendiri.
    |--------------------------------------------------------------------------
    */
    if ($user->role === 'ketua' && (int) $rk->user_id !== (int) $user->id) {
        abort(403, 'Kamu hanya boleh melihat RK Ketua milikmu sendiri.');
    }

    $projects = $rk->projects->map(function ($project) {
        $totalRk = $project->rkAnggotas->count();

        $approvedRk = $project->rkAnggotas
            ->where('status', 'approved')
            ->count();

        $progress = $totalRk === 0
            ? 0
            : round(($approvedRk / $totalRk) * 100);

        return [
            'id' => $project->id,
            'name' => $project->name,
            'status' => $project->status ?? '-',
            'members_count' => $project->members->count(),
            'rk_anggota_count' => $totalRk,
            'approved_rk_count' => $approvedRk,
            'progress' => $progress,
        ];
    })->values();

    $totalProject = $projects->count();

    $averageProgress = $totalProject === 0
        ? 0
        : round($projects->avg('progress'));

    return response()->json([
        'id' => $rk->id,
        'iku_id' => $rk->iku_id,
        'team_id' => $rk->team_id,
        'user_id' => $rk->user_id,
        'description' => $rk->description,
        'progress' => $averageProgress,
        'project_count' => $totalProject,

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
    ]);
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
            'projects.rkAnggotas',
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
                });
        });
    }

    $rkKetuas = $query
        ->latest()
        ->limit(20)
        ->get();

    $data = $rkKetuas->map(function ($rk) {
        $projects = $rk->projects ?? collect();

        $projectCount = $projects->count();

        $projectProgress = $projects->map(function ($project) {
            $totalRk = $project->rkAnggotas->count();

            if ($totalRk === 0) {
                return 0;
            }

            $approvedRk = $project->rkAnggotas
                ->where('status', 'approved')
                ->count();

            return round(($approvedRk / $totalRk) * 100);
        });

        $progress = $projectCount === 0
            ? 0
            : round($projectProgress->avg());

        return [
            'id' => $rk->id,
            'iku_id' => $rk->iku_id,
            'team_id' => $rk->team_id,
            'user_id' => $rk->user_id,
            'description' => $rk->description,
            'project_count' => $projectCount,
            'progress' => $progress,

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
}