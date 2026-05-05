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
    /**
 * ADMIN DASHBOARD (GLOBAL VIEW)
 */
public function admin(Request $request)
{
    $year = $request->year ?? date('Y');

    $projects = Project::with([
            'rkAnggotas',
        ])
        ->whereHas('rkKetua.iku', function ($q) use ($year) {
            $q->where('year', $year);
        })
        ->latest()
        ->get();

    $avgProgress = $projects->count()
        ? round($projects->avg(fn ($project) => $project->progress))
        : 0;

    $recentTasks = DailyTask::with([
            'rkAnggota.user',
            'rkAnggota.project',
        ])
        ->whereHas('rkAnggota.project.rkKetua.iku', function ($q) use ($year) {
            $q->where('year', $year);
        })
        ->latest('date')
        ->latest()
        ->take(5)
        ->get();

    $projectByMonth = Project::selectRaw('MONTH(created_at) as month, COUNT(*) as total')
        ->whereYear('created_at', $year)
        ->groupBy('month')
        ->orderBy('month')
        ->pluck('total', 'month');

    $taskByMonth = DailyTask::selectRaw('MONTH(date) as month, COUNT(*) as total')
        ->whereYear('date', $year)
        ->groupBy('month')
        ->orderBy('month')
        ->pluck('total', 'month');

    return view('dashboard.admin', [
        'totalUser' => User::count(),
        'totalTeam' => Team::count(),
        'totalIku' => Iku::where('year', $year)->count(),
        'totalProject' => $projects->count(),
        'totalTask' => DailyTask::whereYear('date', $year)->count(),
        'avgProgress' => $avgProgress,
        'recentTasks' => $recentTasks,
        'projectByMonth' => $projectByMonth,
        'taskByMonth' => $taskByMonth,
        'year' => $year,
    ]);
}

    /**
     * KEPALA DASHBOARD (MONITORING SEMUA)
     */
    /**
 * KEPALA DASHBOARD (MONITORING SEMUA)
 */
public function kepala(Request $request)
{
    $year = $request->year ?? date('Y');

    $projects = Project::with([
            'rkKetua.iku',
            'team',
            'leader',
            'rkAnggotas.dailyTasks',
        ])
        ->whereHas('rkKetua.iku', function ($q) use ($year) {
            $q->where('year', $year);
        })
        ->latest()
        ->get();

    $totalProject = $projects->count();

    $totalRkKetua = RkKetua::whereHas('iku', function ($q) use ($year) {
            $q->where('year', $year);
        })
        ->count();

    $totalRkAnggota = RkAnggota::whereHas('project.rkKetua.iku', function ($q) use ($year) {
            $q->where('year', $year);
        })
        ->count();

    $totalTask = DailyTask::whereHas('rkAnggota.project.rkKetua.iku', function ($q) use ($year) {
            $q->where('year', $year);
        })
        ->count();

    $avgProgress = $projects->count()
        ? round($projects->avg(fn ($project) => $project->progress))
        : 0;

    $projectByMonth = Project::selectRaw('MONTH(created_at) as month, COUNT(*) as total')
        ->whereYear('created_at', $year)
        ->groupBy('month')
        ->orderBy('month')
        ->pluck('total', 'month');

    $taskByMonth = DailyTask::selectRaw('MONTH(date) as month, COUNT(*) as total')
        ->whereYear('date', $year)
        ->groupBy('month')
        ->orderBy('month')
        ->pluck('total', 'month');

    return view('dashboard.kepala', compact(
        'year',
        'totalProject',
        'totalRkKetua',
        'totalRkAnggota',
        'totalTask',
        'projects',
        'avgProgress',
        'projectByMonth',
        'taskByMonth'
    ));
}

   /**
 * KETUA DASHBOARD (PROJECT YANG DIA PIMPIN)
 */
/**
 * KETUA DASHBOARD (PROJECT YANG DIA PIMPIN)
 */
public function ketua(Request $request)
{
    $user = auth()->user();
    $year = $request->year ?? date('Y');

    /*
    |--------------------------------------------------------------------------
    | MODE KETUA / REVIEWER
    |--------------------------------------------------------------------------
    | Data ketika user role ketua bertindak sebagai leader project.
    | Sumber kebenaran: projects.leader_id = auth()->id()
    |--------------------------------------------------------------------------
    */
    $projects = Project::with([
            'team',
            'leader',
            'rkKetua.iku',
            'members',
            'rkAnggotas.user',
            'rkAnggotas.dailyTasks',
            'rkAnggotas.approver',
        ])
        ->where('leader_id', $user->id)
        ->whereHas('rkKetua.iku', function ($q) use ($year) {
            $q->where('year', $year);
        })
        ->latest()
        ->get();

    $projectIds = $projects->pluck('id');

    $rkKetuas = RkKetua::with(['iku', 'team'])
        ->where('user_id', $user->id)
        ->whereHas('iku', function ($q) use ($year) {
            $q->where('year', $year);
        })
        ->latest()
        ->get();

    $rkAnggotas = RkAnggota::with([
            'project',
            'user',
            'dailyTasks',
            'approver',
        ])
        ->whereIn('project_id', $projectIds)
        ->latest()
        ->get();

    $recentTasks = DailyTask::with([
            'rkAnggota.project',
            'rkAnggota.user',
        ])
        ->whereHas('rkAnggota.project', function ($q) use ($user) {
            $q->where('leader_id', $user->id);
        })
        ->whereHas('rkAnggota.project.rkKetua.iku', function ($q) use ($year) {
            $q->where('year', $year);
        })
        ->latest('date')
        ->latest()
        ->take(10)
        ->get();

    $totalProject = $projects->count();
    $totalRkKetua = $rkKetuas->count();
    $totalRkAnggota = $rkAnggotas->count();

    $totalTask = $rkAnggotas->sum(function ($rk) {
        return $rk->dailyTasks->count();
    });

    $draftRk = $rkAnggotas
        ->where('status', RkAnggota::STATUS_DRAFT)
        ->count();

    $submittedRk = $rkAnggotas
        ->where('status', RkAnggota::STATUS_SUBMITTED)
        ->count();

    $approvedRk = $rkAnggotas
        ->where('status', RkAnggota::STATUS_APPROVED)
        ->count();

    $rejectedRk = $rkAnggotas
        ->where('status', RkAnggota::STATUS_REJECTED)
        ->count();

    $progress = $projects->count()
        ? round($projects->avg(fn ($project) => $project->progress))
        : 0;

    /*
    |--------------------------------------------------------------------------
    | RK Anggota yang perlu direview
    |--------------------------------------------------------------------------
    | Default: tidak menampilkan RK milik diri sendiri agar tidak mendorong
    | self-review/self-approval.
    |--------------------------------------------------------------------------
    */
    $pendingReviews = $rkAnggotas
        ->where('status', RkAnggota::STATUS_SUBMITTED)
        ->where('user_id', '!=', $user->id)
        ->values();

    /*
    |--------------------------------------------------------------------------
    | PEKERJAAN SAYA SEBAGAI ANGGOTA / PELAKSANA
    |--------------------------------------------------------------------------
    | Data ketika user role ketua ikut project sebagai member.
    | Sumber kebenaran: project_members.
    |--------------------------------------------------------------------------
    */
    $memberProjects = Project::with([
            'team',
            'leader',
            'rkKetua.iku',
            'members',
            'rkAnggotas',
        ])
        ->whereHas('members', function ($q) use ($user) {
            $q->where('users.id', $user->id);
        })
        ->whereHas('rkKetua.iku', function ($q) use ($year) {
            $q->where('year', $year);
        })
        ->latest()
        ->get();

    $myRkAnggotas = RkAnggota::with([
            'project.team',
            'project.leader',
            'project.rkKetua.iku',
            'dailyTasks',
            'approver',
        ])
        ->where('user_id', $user->id)
        ->whereHas('project.rkKetua.iku', function ($q) use ($year) {
            $q->where('year', $year);
        })
        ->latest()
        ->get();

    $myDailyTasks = DailyTask::with([
            'rkAnggota.project',
            'rkAnggota.user',
        ])
        ->whereHas('rkAnggota', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->whereHas('rkAnggota.project.rkKetua.iku', function ($q) use ($year) {
            $q->where('year', $year);
        })
        ->latest('date')
        ->latest()
        ->take(10)
        ->get();

    $myTotalProject = $memberProjects->count();
    $myTotalRk = $myRkAnggotas->count();
    $myTotalTask = $myDailyTasks->count();

    $myDraftRk = $myRkAnggotas
        ->where('status', RkAnggota::STATUS_DRAFT)
        ->count();

    $mySubmittedRk = $myRkAnggotas
        ->where('status', RkAnggota::STATUS_SUBMITTED)
        ->count();

    $myApprovedRk = $myRkAnggotas
        ->where('status', RkAnggota::STATUS_APPROVED)
        ->count();

    $myRejectedRk = $myRkAnggotas
        ->where('status', RkAnggota::STATUS_REJECTED)
        ->count();

    return view('dashboard.ketua', compact(
        'year',

        /*
        |--------------------------------------------------------------------------
        | Variable lama
        |--------------------------------------------------------------------------
        | Dipertahankan supaya Blade dashboard ketua yang sekarang tidak langsung rusak.
        |--------------------------------------------------------------------------
        */
        'projects',
        'rkKetuas',
        'rkAnggotas',
        'recentTasks',
        'pendingReviews',
        'totalProject',
        'totalRkKetua',
        'totalRkAnggota',
        'totalTask',
        'draftRk',
        'submittedRk',
        'approvedRk',
        'rejectedRk',
        'progress',

        /*
        |--------------------------------------------------------------------------
        | Variable baru untuk Pekerjaan Saya
        |--------------------------------------------------------------------------
        */
        'memberProjects',
        'myRkAnggotas',
        'myDailyTasks',
        'myTotalProject',
        'myTotalRk',
        'myTotalTask',
        'myDraftRk',
        'mySubmittedRk',
        'myApprovedRk',
        'myRejectedRk'
    ));
}


    /**
     * ANGGOTA DASHBOARD
     */
    public function anggota()
{
    $user = auth()->user();

    /*
    |--------------------------------------------------------------------------
    | Project Saya
    |--------------------------------------------------------------------------
    | Anggota hanya melihat project tempat dia masuk sebagai member
    | melalui tabel project_members.
    |--------------------------------------------------------------------------
    */
    $projects = Project::with([
            'team',
            'leader',
            'rkKetua.iku',
            'members',
            'rkAnggotas.dailyTasks',
        ])
        ->whereHas('members', function ($q) use ($user) {
            $q->where('users.id', $user->id);
        })
        ->latest()
        ->get();

    /*
    |--------------------------------------------------------------------------
    | RK Pribadi Saya
    |--------------------------------------------------------------------------
    | Semua RK Anggota milik user login.
    |--------------------------------------------------------------------------
    */
    $rkAnggotas = RkAnggota::with([
            'project.team',
            'project.leader',
            'project.rkKetua.iku',
            'dailyTasks',
            'approver',
        ])
        ->where('user_id', $user->id)
        ->latest()
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Daily Task Saya
    |--------------------------------------------------------------------------
    | Semua Daily Task dari RK milik user login.
    |--------------------------------------------------------------------------
    */
    $dailyTasks = DailyTask::with([
            'rkAnggota.project',
            'rkAnggota.user',
        ])
        ->whereHas('rkAnggota', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->latest('date')
        ->latest()
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Summary Counts
    |--------------------------------------------------------------------------
    */
    $totalProjects = $projects->count();
    $totalRk = $rkAnggotas->count();
    $totalDailyTasks = $dailyTasks->count();

    $draftRk = $rkAnggotas
        ->where('status', RkAnggota::STATUS_DRAFT)
        ->count();

    $submittedRk = $rkAnggotas
        ->where('status', RkAnggota::STATUS_SUBMITTED)
        ->count();

    $approvedRk = $rkAnggotas
        ->where('status', RkAnggota::STATUS_APPROVED)
        ->count();

    $rejectedRk = $rkAnggotas
        ->where('status', RkAnggota::STATUS_REJECTED)
        ->count();

    /*
    |--------------------------------------------------------------------------
    | Progress Pribadi
    |--------------------------------------------------------------------------
    | Progress dihitung dari RK yang sudah approved dibanding total RK pribadi.
    |--------------------------------------------------------------------------
    */
    $personalProgress = $totalRk === 0
        ? 0
        : round(($approvedRk / $totalRk) * 100);

    /*
    |--------------------------------------------------------------------------
    | RK yang masih bisa dikerjakan
    |--------------------------------------------------------------------------
    | Daily Task hanya bisa dibuat kalau RK masih draft/rejected.
    |--------------------------------------------------------------------------
    */
    $editableRkCount = $rkAnggotas
        ->whereIn('status', [
            RkAnggota::STATUS_DRAFT,
            RkAnggota::STATUS_REJECTED,
        ])
        ->count();

    /*
    |--------------------------------------------------------------------------
    | RK yang butuh Daily Task
    |--------------------------------------------------------------------------
    | RK draft/rejected yang belum punya Daily Task.
    |--------------------------------------------------------------------------
    */
    $needDailyTaskCount = $rkAnggotas
        ->filter(function ($rk) {
            return in_array($rk->status, [
                    RkAnggota::STATUS_DRAFT,
                    RkAnggota::STATUS_REJECTED,
                ])
                && $rk->dailyTasks->count() === 0;
        })
        ->count();

    /*
    |--------------------------------------------------------------------------
    | Latest Data
    |--------------------------------------------------------------------------
    */
    $latestTasks = $dailyTasks->take(5);
    $latestRk = $rkAnggotas->take(5);
    $latestProjects = $projects->take(5);

    return view('dashboard.anggota', compact(
        'projects',
        'rkAnggotas',
        'dailyTasks',
        'latestProjects',
        'latestRk',
        'latestTasks',
        'totalProjects',
        'totalRk',
        'totalDailyTasks',
        'draftRk',
        'submittedRk',
        'approvedRk',
        'rejectedRk',
        'personalProgress',
        'editableRkCount',
        'needDailyTaskCount'
    ));
}

    public function calendar()
{
    $user = auth()->user();

    $projectQuery = Project::query()
        ->whereNotNull('start_date');

    $taskQuery = DailyTask::with('rkAnggota.project');

    /*
    |--------------------------------------------------------------------------
    | Role Scope Calendar
    |--------------------------------------------------------------------------
    | Admin/Kepala:
    | - semua project dan task.
    |
    | Ketua:
    | - project yang dia pimpin.
    | - project yang dia ikuti sebagai member.
    | - task dari project yang dia pimpin.
    | - task pribadi miliknya sendiri.
    |
    | Anggota:
    | - project yang berkaitan dengan RK miliknya.
    | - task miliknya sendiri.
    |--------------------------------------------------------------------------
    */
    if ($user->role === 'ketua') {
        $projectQuery->where(function ($q) use ($user) {
            $q->where('leader_id', $user->id)
                ->orWhereHas('members', function ($sub) use ($user) {
                    $sub->where('users.id', $user->id);
                });
        });

        $taskQuery->where(function ($q) use ($user) {
            $q->whereHas('rkAnggota.project', function ($sub) use ($user) {
                $sub->where('leader_id', $user->id);
            })->orWhereHas('rkAnggota', function ($sub) use ($user) {
                $sub->where('user_id', $user->id);
            });
        });
    }

    if ($user->role === 'anggota') {
        $projectQuery->whereHas('rkAnggotas', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });

        $taskQuery->whereHas('rkAnggota', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });
    }

    $projects = $projectQuery
        ->get()
        ->map(function ($project) {
            return [
                'title' => 'Project: ' . $project->name,
                'start' => $project->start_date,
                'end' => $project->end_date,
                'type' => 'project',
            ];
        });

    $tasks = $taskQuery
        ->get()
        ->map(function ($task) {
            return [
                'title' => 'Task: ' . $task->activity,
                'start' => $task->date,
                'type' => 'task',
            ];
        });

    $events = $projects->merge($tasks);

    return response()->json($events);
}



    public function stats()
{
    $projects = Project::with('rkAnggotas.dailyTasks')->get();

    return response()->json([
        'users' => User::count(),
        'teams' => Team::count(),
        'projects' => $projects->count(),
        'tasks' => DailyTask::count(),

        'project_progress' => $projects->map(function ($project) {
            return [
                'name' => $project->name,
                'progress' => $project->progress,
            ];
        }),
    ]);
}

}