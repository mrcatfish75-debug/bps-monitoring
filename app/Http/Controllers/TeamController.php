<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        $query = Team::with(['leader'])
            ->withCount(['rkKetuas', 'projects']);

        if ($request->search) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('leader', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $teams = $query
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $users = User::where('role', 'ketua')
            ->orderBy('name')
            ->get();

        return view('admin.team.index', compact('teams', 'users'));
    }

    public function store(Request $request)
    {
        $leaderId = $request->leader_id ?? $request->ketua_id;

        if (!$leaderId) {
            return back()
                ->withInput()
                ->with('error', 'Ketua tim wajib dipilih.');
        }

        $request->merge([
            'leader_id' => $leaderId,
        ]);

        $request->validate([
            'name' => 'required|string|max:255',
            'leader_id' => 'required|exists:users,id',
        ]);

        $ketua = User::findOrFail($request->leader_id);

        if ($ketua->role !== 'ketua') {
            return back()
                ->withInput()
                ->with('error', 'Ketua tim harus user dengan role ketua.');
        }

        $team = Team::create([
            'name' => $request->name,
            'leader_id' => $ketua->id,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Catatan:
        | users.team_id hanya dipakai sebagai penanda ketua berada di team ini.
        | Anggota kerja tidak lagi disimpan di Team.
        | Anggota project dikelola lewat project_members.
        |--------------------------------------------------------------------------
        */
        $ketua->update([
            'team_id' => $team->id,
        ]);

        return back()->with('success', 'Team berhasil dibuat.');
    }

    public function show(Team $team)
{
    $team->load([
        'leader',
        'rkKetuas.iku',
        'rkKetuas.projects.members',
        'rkKetuas.projects.rkAnggotas',
        'projects.rkKetua.iku',
        'projects.members',
        'projects.rkAnggotas',
    ]);

    return response()->json([
        'id' => $team->id,
        'name' => $team->name,
        'leader_id' => $team->leader_id,
        'leader' => $team->leader,

        'rk_ketuas_count' => $team->rkKetuas->count(),
        'projects_count' => $team->projects->count(),

        'rk_ketuas' => $team->rkKetuas->map(function ($rk) {
            return [
                'id' => $rk->id,
                'description' => $rk->description,
                'iku' => $rk->iku ? [
                    'id' => $rk->iku->id,
                    'name' => $rk->iku->name,
                    'year' => $rk->iku->year,
                ] : null,
                'project_count' => $rk->projects->count(),
            ];
        })->values(),

        'projects' => $team->projects->map(function ($project) {
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
                'progress' => $progress,

                'rk_ketua' => $project->rkKetua ? [
                    'id' => $project->rkKetua->id,
                    'description' => $project->rkKetua->description,
                    'iku' => $project->rkKetua->iku ? [
                        'id' => $project->rkKetua->iku->id,
                        'name' => $project->rkKetua->iku->name,
                        'year' => $project->rkKetua->iku->year,
                    ] : null,
                ] : null,

                'members_count' => $project->members->count(),
                'members' => $project->members->map(function ($member) {
                    return [
                        'id' => $member->id,
                        'name' => $member->name,
                        'role' => $member->role,
                    ];
                })->values(),

                'rk_anggota_count' => $totalRk,
                'approved_rk_count' => $approvedRk,
            ];
        })->values(),
    ]);
}

    public function edit(Team $team)
    {
        $users = User::where('role', 'ketua')
            ->orderBy('name')
            ->get();

        return view('admin.team.edit', compact('team', 'users'));
    }

    public function update(Request $request, Team $team)
    {
        $leaderId = $request->leader_id ?? $request->ketua_id;

        if (!$leaderId) {
            return back()
                ->withInput()
                ->with('error', 'Ketua tim wajib dipilih.');
        }

        $request->merge([
            'leader_id' => $leaderId,
        ]);

        $request->validate([
            'name' => 'required|string|max:255',
            'leader_id' => 'required|exists:users,id',
        ]);

        $ketua = User::findOrFail($request->leader_id);

        if ($ketua->role !== 'ketua') {
            return back()
                ->withInput()
                ->with('error', 'Ketua tim harus user dengan role ketua.');
        }

        /*
        |--------------------------------------------------------------------------
        | Kalau ketua lama memakai team_id team ini, kosongkan dulu.
        | Ini hanya untuk ketua, bukan anggota.
        |--------------------------------------------------------------------------
        */
        User::where('team_id', $team->id)
            ->where('role', 'ketua')
            ->update([
                'team_id' => null,
            ]);

        $team->update([
            'name' => $request->name,
            'leader_id' => $ketua->id,
        ]);

        $ketua->update([
            'team_id' => $team->id,
        ]);

        return redirect()
            ->route('admin.team.index')
            ->with('success', 'Tim berhasil diupdate.');
    }

    public function destroy(Team $team)
    {
        if (method_exists($team, 'rkKetuas') && $team->rkKetuas()->exists()) {
            return back()->with('error', 'Tim tidak bisa dihapus karena masih memiliki RK Ketua.');
        }

        if (method_exists($team, 'projects') && $team->projects()->exists()) {
            return back()->with('error', 'Tim tidak bisa dihapus karena masih memiliki Project.');
        }

        User::where('team_id', $team->id)
            ->where('role', 'ketua')
            ->update([
                'team_id' => null,
            ]);

        $team->delete();

        return back()->with('success', 'Tim dihapus.');
    }
}