<?php

namespace App\Http\Controllers;

use App\Models\Iku;
use App\Models\DailyTask;
use Illuminate\Http\Request;

class IkuController extends Controller
{
    public function index(Request $request)
{
    $year = $request->year;
    $search = $request->search;

    $query = Iku::with([
        'rkKetuas.projects.rkAnggotas.dailyTasks'
    ]);

    // ✅ FILTER YEAR (opsional)
    if ($year) {
        $query->where('year', $year);
    }

    // ✅ FILTER SEARCH (INI YANG KURANG!)
    if ($search) {
        $query->where('name', 'like', '%' . $search . '%');
    }

    $ikus = $query->paginate(10);

$ikus->getCollection()->transform(function ($iku) {

        $total = 0;
        $approved = 0;

        foreach ($iku->rkKetuas as $rkKetua) {
            foreach ($rkKetua->projects as $project) {
                foreach ($project->rkAnggotas as $rkAnggota) {

                    $tasks = $rkAnggota->dailyTasks;

                    $total += $tasks->count();
                    $approved += $tasks
                        ->where('status', DailyTask::STATUS_APPROVED)
                        ->count();
                }
            }
        }

        $iku->progress = $total > 0 ? round(($approved / $total) * 100) : 0;

        return $iku;
    });

    return view('iku.index', compact('ikus', 'year', 'search'));
}

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'year' => 'required|numeric',
            'satuan' => 'required',
            'target' => 'required|numeric'
        ]);

        Iku::create([
            'name' => $request->name,
            'year' => $request->year,
            'satuan' => $request->satuan,
            'target' => $request->target,
        ]);

        return back()->with('success', 'IKU berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $iku = Iku::findOrFail($id);

        $request->validate([
            'name' => 'required',
            'year' => 'required|numeric',
            'satuan' => 'required',
            'target' => 'required|numeric'
        ]);

        $iku->update([
            'name' => $request->name,
            'year' => $request->year,
            'satuan' => $request->satuan,
            'target' => $request->target,
        ]);

        return back()->with('success', 'IKU berhasil diupdate');
    }

    public function destroy($id)
    {
        $iku = Iku::findOrFail($id);

        if ($iku->rkKetuas()->exists()) {
            return back()->with('error', 'IKU tidak bisa dihapus (sudah dipakai)');
        }

        $iku->delete();

        return back()->with('success', 'IKU berhasil dihapus');
    }

public function search(Request $request)
{
    $query = Iku::query();

    if ($request->year) {
        $query->where('year', $request->year);
    }

    if ($request->search) {
        $query->where('name', 'like', '%' . $request->search . '%');
    }

    $ikus = $query->limit(20)->get();

    return response()->json($ikus);
}

}