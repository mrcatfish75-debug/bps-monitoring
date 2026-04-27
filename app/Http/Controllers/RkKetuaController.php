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
        $year = $request->year ?? date('Y');

        $ikus = Iku::where('year', $year)->get();
        $teams = Team::with('leader')->get();

        $query = RkKetua::with(['iku','team.leader','projects'])
            ->whereHas('iku', fn($q) => $q->where('year',$year));

        if ($request->iku_id) {
            $query->where('iku_id', $request->iku_id);
        }

        if ($request->team_id) {
            $query->where('team_id', $request->team_id);
        }

        // 🔥 FIX SEARCH (INI YANG DITAMBAHKAN)
        if ($request->search) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%$search%")
                  ->orWhereHas('iku', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%$search%");
                  });
            });
        }

        $rkKetuas = $query->latest()->paginate(10);

$rkKetuas->getCollection()->transform(function ($rk) {
    $rk->project_count = $rk->projects->count();
    return $rk;
});
        $search = $request->search;
        return view('rk_ketua.index', compact('rkKetuas','ikus','teams','year','search'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'iku_id' => 'required|exists:ikus,id',
            'team_id' => 'required|exists:teams,id',
            'description' => 'required|string'
        ]);

        $team = Team::findOrFail($request->team_id);

        RkKetua::create([
            'iku_id' => $request->iku_id,
            'team_id' => $team->id,
            'user_id' => $team->leader_id, // 🔥 AUTO KETUA
            'description' => $request->description,
        ]);

        return back()->with('success','RK Ketua berhasil dibuat');
    }

    public function update(Request $request, $id)
    {
        $rk = RkKetua::findOrFail($id);

        $request->validate([
            'team_id' => 'required|exists:teams,id',
            'description' => 'required|string'
        ]);

        $team = \App\Models\Team::findOrFail($request->team_id);

        // UPDATE SEMUA YANG TERKAIT
        $rk->update([
            'team_id' => $team->id,
            'user_id' => $team->leader_id, // auto update ketua
            'description' => $request->description
        ]);

        return back()->with('success','RK Ketua berhasil diupdate');
    }

    public function destroy($id)
    {
        $rk = RkKetua::findOrFail($id);

        if ($rk->projects()->exists()) {
            return back()->with('error','Masih ada project');
        }

        $rk->delete();

        return back()->with('success','RK Ketua dihapus');
    }

public function search(Request $request)
{
    $year = $request->year ?? date('Y');

    $query = RkKetua::with(['iku','team.leader','projects'])
        ->whereHas('iku', fn($q) => $q->where('year',$year));

    if ($request->iku_id) {
        $query->where('iku_id', $request->iku_id);
    }

    if ($request->team_id) {
        $query->where('team_id', $request->team_id);
    }

    if ($request->search) {
        $search = $request->search;

        $query->where(function ($q) use ($search) {
            $q->where('description', 'like', "%$search%")
              ->orWhereHas('iku', function ($q2) use ($search) {
                  $q2->where('name', 'like', "%$search%");
              });
        });
    }

    $rkKetuas = $query->latest()->limit(20)->get();

    // 🔥 TAMBAH project_count
    $rkKetuas->transform(function ($rk) {
        $rk->project_count = $rk->projects->count();
        return $rk;
    });

    return response()->json($rkKetuas);
}
}