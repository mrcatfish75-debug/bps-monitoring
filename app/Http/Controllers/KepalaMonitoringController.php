<?php

namespace App\Http\Controllers;

use App\Models\Iku;
use App\Models\RkAnggota;
use Illuminate\Http\Request;

class KepalaMonitoringController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Monitoring IKU Kepala
    |--------------------------------------------------------------------------
    | Read-only.
    | Kepala hanya melihat data, filter, search, dan detail.
    | Tidak ada create, update, delete, approve, reject.
    |--------------------------------------------------------------------------
    */
    public function iku(Request $request)
    {
        $year = $request->year ?? date('Y');
        $search = $request->search;

        $query = Iku::with([
            'rkKetuas.team.leader',
            'rkKetuas.ketua',
            'rkKetuas.projects.rkAnggotas',
        ]);

        if ($year) {
            $query->where('year', $year);
        }

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $ikus = $query
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('kepala.iku.index', compact(
            'ikus',
            'year',
            'search'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | AJAX Search Monitoring IKU Kepala
    |--------------------------------------------------------------------------
    */
    public function ikuSearch(Request $request)
    {
        $year = $request->year ?? date('Y');
        $search = $request->search;

        $query = Iku::with([
            'rkKetuas.team.leader',
            'rkKetuas.ketua',
            'rkKetuas.projects.rkAnggotas',
        ]);

        if ($year) {
            $query->where('year', $year);
        }

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $ikus = $query
            ->latest()
            ->limit(20)
            ->get()
            ->map(function ($iku) {
                return [
                    'id' => $iku->id,
                    'name' => $iku->name,
                    'year' => $iku->year,
                    'target' => $iku->target ?? 100,
                    'satuan' => '%',
                    'progress' => $iku->progress,
                    'progress_label' => $iku->progress_label ?? $this->progressLabel($iku->progress),

                    'rk_ketuas' => $iku->rkKetuas->map(function ($rk) {
                        return [
                            'id' => $rk->id,
                            'description' => $rk->description,
                            'progress' => $rk->progress,
                            'progress_label' => $rk->progress_label ?? $this->progressLabel($rk->progress),

                            'team' => $rk->team ? [
                                'id' => $rk->team->id,
                                'name' => $rk->team->name,
                                'leader' => $rk->team->leader ? [
                                    'id' => $rk->team->leader->id,
                                    'name' => $rk->team->leader->name,
                                ] : null,
                            ] : null,

                            'ketua' => $rk->ketua ? [
                                'id' => $rk->ketua->id,
                                'name' => $rk->ketua->name,
                            ] : null,

                            'projects' => $rk->projects->map(function ($project) {
                                $totalRk = $project->rkAnggotas->count();

                                $approvedRk = $project->rkAnggotas
                                    ->where('status', RkAnggota::STATUS_APPROVED)
                                    ->count();

                                return [
                                    'id' => $project->id,
                                    'name' => $project->name,
                                    'status' => $project->status,
                                    'progress' => $project->progress,
                                    'progress_label' => $project->progress_label ?? $this->progressLabel($project->progress),
                                    'total_rk_count' => $totalRk,
                                    'approved_rk_count' => $approvedRk,
                                    'rk_anggotas' => $project->rkAnggotas->map(function ($rkAnggota) {
                                        return [
                                            'id' => $rkAnggota->id,
                                            'description' => $rkAnggota->description,
                                            'status' => $rkAnggota->status,
                                            'status_label' => $rkAnggota->status_label ?? $rkAnggota->status,
                                            'progress' => $rkAnggota->progress,
                                        ];
                                    })->values(),
                                ];
                            })->values(),
                        ];
                    })->values(),
                ];
            })
            ->values();

        return response()->json($ikus);
    }

    private function progressLabel(int $progress): string
    {
        if ($progress >= 100) {
            return 'Selesai';
        }

        if ($progress > 0) {
            return 'Berjalan';
        }

        return 'Belum Berjalan';
    }
}