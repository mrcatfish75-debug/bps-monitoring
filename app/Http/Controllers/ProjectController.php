<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Team;
use App\Models\RkKetua;
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

    $query = Project::with([
        'team',
        'leader',
        'rkKetua.iku',
        'members',
        'rkAnggotas',
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
                ->orWhereHas('members', function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%");
                });
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Sorting
    |--------------------------------------------------------------------------
    */
    if ($request->filled('sort')) {
        $query->orderBy($request->sort, $request->direction ?? 'asc');
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
    | Admin:
    | - semua tim.
    |
    | Ketua:
    | - hanya tim dari project yang dia pimpin atau dia ikuti.
    |
    | Anggota:
    | - hanya tim dari project yang dia ikuti.
    |--------------------------------------------------------------------------
    */
    $teamQuery = Team::with('leader');

    if ($user->role === 'ketua') {
        $teamQuery->whereHas('projects', function ($q) use ($user, $year) {
            $q->whereHas('rkKetua.iku', function ($sub) use ($year) {
                $sub->where('year', $year);
            })
            ->where(function ($sub) use ($user) {
                $sub->where('leader_id', $user->id)
                    ->orWhereHas('members', function ($memberQ) use ($user) {
                        $memberQ->where('users.id', $user->id);
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
    | Untuk ketua, tetap RK Ketua miliknya sendiri agar Add Project tidak
    | menampilkan RK Ketua milik orang lain.
    |--------------------------------------------------------------------------
    */
    $rkKetuaQuery = RkKetua::with(['iku', 'team', 'projects'])
    ->whereHas('iku', function ($q) use ($year) {
        $q->where('year', $year);
    });

/*
|--------------------------------------------------------------------------
| RK Ketua Filter Options
|--------------------------------------------------------------------------
| Admin:
| - semua RK Ketua tahun berjalan.
|
| Ketua:
| - RK Ketua dari project yang dia pimpin.
| - RK Ketua dari project yang dia ikuti sebagai member.
|
| Anggota:
| - RK Ketua dari project yang dia ikuti sebagai member.
|--------------------------------------------------------------------------
*/
if ($user->role === 'ketua') {
    $rkKetuaQuery->whereHas('projects', function ($q) use ($user) {
        $q->where('leader_id', $user->id)
            ->orWhereHas('members', function ($memberQ) use ($user) {
                $memberQ->where('users.id', $user->id);
            });
    });
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
    $request->validate([
        'name' => 'required|string|max:255',
        'rk_ketua_id' => 'required|exists:rk_ketuas,id',
        'members' => 'required|array|min:1',
        'members.*' => 'exists:users,id',
    ]);

    $user = auth()->user();

    $rk = RkKetua::with('ketua')->findOrFail($request->rk_ketua_id);

    if (!$rk->ketua || $rk->ketua->role !== 'ketua') {
        return back()->with('error', 'RK Ketua harus memiliki user dengan role ketua.');
    }

    // Jika role ketua, hanya boleh membuat project dari RK Ketua miliknya
    if ($user->role === 'ketua' && $rk->user_id !== $user->id) {
        abort(403, 'Kamu hanya boleh membuat project dari RK Ketua milikmu sendiri.');
    }

    $selectedMembers = $request->members ?? [];

    // Anggota project bebas dari pegawai operasional, bukan dikunci team_id
    $validMembers = User::whereIn('id', $selectedMembers)
        ->whereIn('role', ['anggota', 'ketua'])
        ->pluck('id')
        ->toArray();

    if (count($validMembers) !== count($selectedMembers)) {
        return back()->with('error', 'Ada user yang tidak valid sebagai anggota project.');
    }

    $project = Project::create([
        'name' => $request->name,
        'rk_ketua_id' => $rk->id,
        'team_id' => $rk->team_id,
        'leader_id' => $rk->user_id,
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
        'rkAnggotas',
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

    return response()->json($project);
}

    /**
     * UPDATE
     */
   public function update(Request $request, $id)
{
    $project = Project::findOrFail($id);

    $request->validate([
        'name' => 'required|string|max:255',
        'rk_ketua_id' => 'required|exists:rk_ketuas,id',
        'members' => 'required|array|min:1',
        'members.*' => 'exists:users,id',
    ]);

    $user = auth()->user();

    if ($user->role === 'ketua' && $project->leader_id !== $user->id) {
        abort(403, 'Kamu hanya boleh mengubah project yang kamu pimpin.');
    }

    $rk = RkKetua::with('ketua')->findOrFail($request->rk_ketua_id);

    if (!$rk->ketua || $rk->ketua->role !== 'ketua') {
        return back()->with('error', 'RK Ketua harus memiliki user dengan role ketua.');
    }

    if ($user->role === 'ketua' && $rk->user_id !== $user->id) {
        abort(403, 'Kamu hanya boleh memakai RK Ketua milikmu sendiri.');
    }

    $selectedMembers = $request->members ?? [];

    // Anggota project bebas dari pegawai operasional, bukan dikunci team_id
    $validMembers = User::whereIn('id', $selectedMembers)
        ->whereIn('role', ['anggota', 'ketua'])
        ->pluck('id')
        ->toArray();

    if (count($validMembers) !== count($selectedMembers)) {
        return back()->with('error', 'Ada user yang tidak valid sebagai anggota project.');
    }

    $project->update([
        'name' => $request->name,
        'rk_ketua_id' => $rk->id,
        'team_id' => $rk->team_id,
        'leader_id' => $rk->user_id,
    ]);

    $project->members()->sync($validMembers);

    return back()->with('success', 'Project berhasil diupdate.');
}

    /**
     * DELETE
     */
    public function destroy($id)
{
    $project = Project::findOrFail($id);

    $user = auth()->user();

    if ($user->role === 'ketua' && $project->leader_id !== $user->id) {
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
    RkKetua::findOrFail($id);

    $members = User::whereIn('role', ['anggota', 'ketua'])
        ->orderBy('name')
        ->get();

    return response()->json($members);
}

public function search(Request $request)
{
    $user = auth()->user();
    $year = $request->year ?? date('Y');

    $query = Project::with([
        'team',
        'leader',
        'rkKetua.iku',
        'members',
        'rkAnggotas',
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
                ->orWhereHas('members', function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%");
                });
        });
    }

    $projects = $query
        ->latest()
        ->limit(20)
        ->get();

    $data = $projects->map(function ($project) {
        $members = $project->members
            ->whereIn('role', ['anggota', 'ketua'])
            ->values();

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
            'team' => [
                'id' => $project->team->id ?? null,
                'name' => $project->team->name ?? '-',
            ],
            'members_count' => $members->count(),
            'members_names' => $members->pluck('name')->take(3)->implode(', '),
            'rk_ketua' => [
                'id' => $project->rkKetua->id ?? null,
                'description' => $project->rkKetua->description ?? '-',
            ],
            'progress' => $progress,
            'approved_rk_count' => $approvedRk,
            'total_rk_count' => $totalRk,
        ];
    });

    return response()->json($data);
}

}