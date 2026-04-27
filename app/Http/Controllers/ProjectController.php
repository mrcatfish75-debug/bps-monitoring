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
        $query = Project::with([
            'team',
            'leader',
            'rkKetua.iku',
            'members',
            'rkAnggotas.dailyTasks'
        ]);

        // FILTER TAHUN
        if ($request->year) {
            $query->whereHas('rkKetua.iku', function ($q) use ($request) {
                $q->where('year', $request->year);
            });
        }

        // FILTER TEAM
        if ($request->team_id) {
            $query->where('team_id', $request->team_id);
        }

        // FILTER RK
        if ($request->rk_ketua_id) {
            $query->where('rk_ketua_id', $request->rk_ketua_id);
        }

        // SEARCH
        if ($request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

       // 🔥 SORTING
        if ($request->sort) {
            $query->orderBy($request->sort, $request->direction ?? 'asc');
        } else {
            $query->latest();
        }

        $projects = $query->paginate(10);

        $teams = Team::all();
        $rkKetuas = RkKetua::with('iku')->get();

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
            'name' => 'required',
            'rk_ketua_id' => 'required|exists:rk_ketuas,id',
            'members' => 'required|array',
            'members.*' => 'exists:users,id',
        ]);

        $rk = RkKetua::with('ketua')->findOrFail($request->rk_ketua_id);

            if (!$rk->ketua || $rk->ketua->role !== 'ketua') {
                return back()->with('error', 'RK Ketua harus memiliki user dengan role ketua.');
            }

            $project = Project::create([
                'name' => $request->name,
                'rk_ketua_id' => $rk->id,
                'team_id' => $rk->team_id,
                'leader_id' => $rk->user_id,
            ]);

        $project->members()->sync($request->members);

        return back()->with('success', 'Project berhasil dibuat');
    }

    /**
     * SHOW (AJAX VIEW)
     */
    public function show($id)
{
    return response()->json(
        Project::with([
            'team',
            'leader',
            'rkKetua.iku',
            'rkKetua.ketua',
            'members' => function ($q) {
                $q->where('role', 'anggota');
            }
        ])->findOrFail($id)
    );
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
        'members' => 'required|array',
        'members.*' => 'exists:users,id',
    ]);

    $rk = RkKetua::with('ketua')->findOrFail($request->rk_ketua_id);

    if (!$rk->ketua || $rk->ketua->role !== 'ketua') {
        return back()->with('error', 'RK Ketua harus memiliki user dengan role ketua.');
    }

    $project->update([
        'name' => $request->name,
        'rk_ketua_id' => $rk->id,
        'team_id' => $rk->team_id,
        'leader_id' => $rk->user_id,
    ]);

    $project->members()->sync($request->members);

    return back()->with('success', 'Project berhasil diupdate');
}

    /**
     * DELETE
     */
    public function destroy($id)
    {
        Project::findOrFail($id)->delete();
        return back()->with('success','Project dihapus');
    }

    /**
     * AJAX MEMBER
     */
   public function getMembers($id)
{
    $rk = RkKetua::findOrFail($id);

    $members = User::where('team_id', $rk->team_id)
        ->where('role', 'anggota')
        ->get();

    return response()->json($members);
}

public function search(Request $request)
{
    $query = Project::with([
        'team',
        'leader',
        'rkKetua.iku'
    ]);

    if ($request->year) {
        $query->whereHas('rkKetua.iku', function ($q) use ($request) {
            $q->where('year', $request->year);
        });
    }

    if ($request->team_id) {
        $query->where('team_id', $request->team_id);
    }

    if ($request->rk_ketua_id) {
        $query->where('rk_ketua_id', $request->rk_ketua_id);
    }

    if ($request->search) {
        $query->where('name', 'like', '%' . $request->search . '%');
    }

    $projects = $query->latest()->limit(20)->get();

    return response()->json($projects);
}

}