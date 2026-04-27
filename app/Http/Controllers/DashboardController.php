<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\RkKetua;
use App\Models\RkAnggota;
use App\Models\DailyTask;
use App\Models\User;
use App\Models\Team;
use App\Models\Iku;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * ADMIN DASHBOARD (GLOBAL VIEW)
     */
    public function admin(Request $request)
    {
        $year = $request->year ?? date('Y');

        // ambil semua project + relasi
        $projects = Project::with([
            'rkAnggotas.dailyTasks:id,rk_anggota_id,status'
        ])->select('id','name','rk_ketua_id')->get();

        // progress rata-rata
        $avgProgress = $projects->count()
            ? round($projects->avg(fn($p) => $p->progress))
            : 0;

        // aktivitas terbaru
        $recentTasks = DailyTask::with('rkAnggota.user')
            ->latest()
            ->take(5)
            ->get();
        
        // chart project per bulan
        $projectByMonth = Project::selectRaw('MONTH(created_at) as month, COUNT(*) as total')
                            ->whereYear('created_at', $year)
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        // chart task per bulan
        $taskByMonth = DailyTask::selectRaw('MONTH(created_at) as month, COUNT(*) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        return view('dashboard.admin', [
            'totalUser' => User::count(),
            'totalTeam' => Team::count(),
            'totalIku' => Iku::where('year', $year)->count(),
            'totalProject' => $projects->count(),
            'totalTask' => DailyTask::count(),
            'avgProgress' => $avgProgress,
            'recentTasks' => $recentTasks,
            'year' => $year
        ]);
    }

    /**
     * KEPALA DASHBOARD (MONITORING SEMUA)
     */
    public function kepala()
    {
        $projects = Project::with([
            'rkKetua.iku',
            'team',
            'leader',
            'rkAnggotas.dailyTasks'
        ])->get();

        $totalProject = $projects->count();
        $totalRkKetua = RkKetua::count();
        $totalRkAnggota = RkAnggota::count();
        $totalTask = DailyTask::count();

        // progress rata-rata
        $avgProgress = $projects->count()
            ? round($projects->avg(fn($p) => $p->progress))
            : 0;

        // chart project per bulan
        $projectByMonth = Project::selectRaw('MONTH(created_at) as month, COUNT(*) as total')
                            ->whereYear('created_at', $year)
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        // chart task per bulan
        $taskByMonth = DailyTask::selectRaw('MONTH(created_at) as month, COUNT(*) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        return view('dashboard.kepala', compact(
            'totalProject',
            'totalRkKetua',
            'totalRkAnggota',
            'totalTask',
            'projects',
            'avgProgress'
        ));
    }

    /**
     * KETUA DASHBOARD (PROJECT SENDIRI)
     */
    public function ketua(Request $request)
    {
        $year = $request->year ?? date('Y');

        $projects = Project::where('leader_id', auth()->id())
            ->whereHas('rkKetua.iku', function ($q) use ($year) {
                $q->where('year', $year);
            })
            ->with('rkAnggotas.dailyTasks')
            ->get();

        $totalProject = $projects->count();

        // total task dari semua project
        $totalTask = $projects->sum(function ($p) {
            return $p->rkAnggotas->sum(fn($rk) => $rk->dailyTasks->count());
        });

        $totalRkKetua = RkKetua::where('user_id', auth()->id())
            ->whereHas('iku', fn($q) => $q->where('year', $year))
            ->count();

        // progress REAL (bukan fake)
        $progress = $projects->count()
            ? round($projects->avg(fn($p) => $p->progress))
            : 0;

        return view('dashboard.ketua', compact(
            'totalProject',
            'totalTask',
            'totalRkKetua',
            'progress',
            'year'
        ));
    }

    /**
     * ANGGOTA DASHBOARD
     */
    public function anggota()
    {
        $tasks = DailyTask::whereHas('rkAnggota', function ($q) {
            $q->where('user_id', auth()->id());
        })->count();

        return view('dashboard.anggota', [
            'totalTask' => $tasks
        ]);
    }

    public function calendar()
    {
        $projects = \App\Models\Project::whereNotNull('start_date')
            ->get()
            ->map(function ($p) {
                return [
                    'title' => 'Project: ' . $p->name,
                    'start' => $p->start_date,
                    'end' => $p->end_date,
                    'type' => 'project'
                ];
            });

        $tasks = \App\Models\DailyTask::with('rkAnggota.project')
            ->get()
            ->map(function ($t) {
                return [
                    'title' => 'Task: ' . $t->activity,
                    'start' => $t->date,
                    'type' => 'task'
                ];
            });

        $events = $projects->merge($tasks);
        

        return response()->json($events);
    }

    public function stats()
    {
        $projects = \App\Models\Project::with('rkAnggotas.dailyTasks')->get();

        return response()->json([
            'users' => \App\Models\User::count(),
            'teams' => \App\Models\Team::count(),
            'projects' => $projects->count(),
            'tasks' => \App\Models\DailyTask::count(),

            'project_progress' => $projects->map(function($p){


                return [
                    'name' => $p->name,
                    'progress' => $progress
                ];
            })
        ]);
    }
}