<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\RkKetua;
use App\Models\RkAnggota;
use App\Models\DailyTask;
use App\Models\User;
use App\Models\Team;
use App\Models\Iku;
use App\Models\Iki;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Exports\DailyTaskExport;
use Maatwebsite\Excel\Facades\Excel;

class DashboardController extends Controller
{
    /**
     * ADMIN DASHBOARD (GLOBAL VIEW)
     */
    public function admin(Request $request)
    {
        $year = $request->year ?? date('Y');

        /*
        |--------------------------------------------------------------------------
        | Admin Dashboard
        |--------------------------------------------------------------------------
        | Flow terbaru:
        | IKU -> RK Ketua -> Project -> RK Anggota -> IKI -> Daily Task
        | Progress utama berasal dari approval IKI, lalu naik ke RK Anggota,
        | Project, RK Ketua, dan IKU melalui accessor model.
        |--------------------------------------------------------------------------
        */
        $projects = Project::with([
                'rkKetua.iku',
                'team',
                'leader',
                'members',
                'rkAnggotas.user',
                'rkAnggotas.ikis.dailyTasks',
                'rkAnggotas.ikis.approver',
            ])
            ->whereHas('rkKetua.iku', function ($q) use ($year) {
                $q->where('year', $year);
            })
            ->latest()
            ->get();

        $ikus = Iku::with([
                'rkKetuas.projects.rkAnggotas.ikis.dailyTasks',
            ])
            ->where('year', $year)
            ->latest()
            ->get();

        $rkAnggotas = $projects
            ->flatMap(fn ($project) => $project->rkAnggotas)
            ->values();

        $ikis = $rkAnggotas
            ->flatMap(fn ($rk) => $rk->ikis)
            ->values();

        $avgProgress = $projects->count()
            ? round($projects->avg(fn ($project) => $project->progress))
            : 0;

        $avgIkuProgress = $ikus->count()
            ? round($ikus->avg(fn ($iku) => $iku->progress))
            : 0;

        /*
        |--------------------------------------------------------------------------
        | Recent Tasks
        |--------------------------------------------------------------------------
        | Daily Task tetap ditampilkan sebagai monitoring aktivitas.
        |--------------------------------------------------------------------------
        */
        $recentTasks = DailyTask::with([
                'rkAnggota.user',
                'rkAnggota.project.rkKetua.iku',
            ])
            ->whereHas('rkAnggota.project.rkKetua.iku', function ($q) use ($year) {
                $q->where('year', $year);
            })
            ->latest('date')
            ->latest()
            ->take(5)
            ->get();

        $projectByMonth = Project::selectRaw('MONTH(created_at) as month, COUNT(*) as total')
            ->whereHas('rkKetua.iku', function ($q) use ($year) {
                $q->where('year', $year);
            })
            ->whereYear('created_at', $year)
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        $taskByMonth = DailyTask::selectRaw('MONTH(date) as month, COUNT(*) as total')
            ->whereHas('rkAnggota.project.rkKetua.iku', function ($q) use ($year) {
                $q->where('year', $year);
            })
            ->whereYear('date', $year)
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        return view('dashboard.admin', [
            'year' => $year,
            'totalUser' => User::count(),
            'totalTeam' => Team::count(),
            'totalIku' => $ikus->count(),
            'totalProject' => $projects->count(),
            'totalRkAnggota' => $rkAnggotas->count(),

            /*
            |--------------------------------------------------------------------------
            | IKI Summary
            |--------------------------------------------------------------------------
            */
            'totalIki' => $ikis->count(),
            'draftIki' => $ikis->where('status', Iki::STATUS_DRAFT)->count(),
            'submittedIki' => $ikis->where('status', Iki::STATUS_SUBMITTED)->count(),
            'approvedIki' => $ikis->where('status', Iki::STATUS_APPROVED)->count(),
            'rejectedIki' => $ikis->where('status', Iki::STATUS_REJECTED)->count(),

            /*
            |--------------------------------------------------------------------------
            | Legacy aliases
            |--------------------------------------------------------------------------
            | Dipertahankan agar dashboard lama tidak langsung error.
            |--------------------------------------------------------------------------
            */
            'approvedRk' => $rkAnggotas->filter(fn ($rk) => $rk->is_completed)->count(),
            'totalTask' => DailyTask::whereHas('rkAnggota.project.rkKetua.iku', function ($q) use ($year) {
                $q->where('year', $year);
            })->count(),

            'avgProgress' => $avgProgress,
            'avgIkuProgress' => $avgIkuProgress,

            'ikus' => $ikus,
            'projects' => $projects,
            'recentTasks' => $recentTasks,
            'projectByMonth' => $projectByMonth,
            'taskByMonth' => $taskByMonth,
        ]);
    }

    /**
     * KEPALA DASHBOARD (MONITORING SEMUA)
     */
    public function kepala(Request $request)
{
    $year = $request->year ?? date('Y');
    $currentYear = now()->year;

    if (!is_numeric($year)) {
        $year = $currentYear;
    }

    $year = (int) $year;
    $today = now()->toDateString();

    /*
    |--------------------------------------------------------------------------
    | Kepala Dashboard
    |--------------------------------------------------------------------------
    | Fokus baru:
    | Kepala Kantor perlu cepat melihat aktivitas harian pegawai:
    | - Hari ini siapa mengerjakan apa
    | - Siapa belum isi Daily Task
    | - Task mana belum ada bukti
    | - IKI mana yang menunggu review / belum submit
    |
    | Variabel lama tetap dipertahankan agar Blade lama tidak rusak.
    |--------------------------------------------------------------------------
    */

    $ikus = Iku::with([
        'rkKetuas.team',
        'rkKetuas.ketua',
        'rkKetuas.projects',
        'rkKetuas.projects.rkAnggotas',
        'rkKetuas.projects.rkAnggotas.ikis',
    ])
        ->where('year', $year)
        ->latest()
        ->get();

    $projects = Project::with([
            'rkKetua.iku',
            'team',
            'leader',
            'rkAnggotas',
            'rkAnggotas.ikis',
        ])
        ->whereHas('rkKetua.iku', function ($q) use ($year) {
            $q->where('year', $year);
        })
        ->latest()
        ->get();

    $rkKetuas = RkKetua::with([
        'iku',
        'team',
        'ketua',
        'projects',
        'projects.rkAnggotas',
        'projects.rkAnggotas.ikis',
    ])
        ->whereHas('iku', function ($q) use ($year) {
            $q->where('year', $year);
        })
        ->latest()
        ->get();

    $rkAnggotas = RkAnggota::with([
        'project.rkKetua.iku',
        'project.leader',
        'project.team',
        'user',
        'ikis',
    ])
        ->whereHas('project.rkKetua.iku', function ($q) use ($year) {
            $q->where('year', $year);
        })
        ->latest()
        ->get();

    $ikis = Iki::with([
            'rkAnggota.user',
            'rkAnggota.project.rkKetua.iku',
            'rkAnggota.project.team',
        ])
        ->whereHas('rkAnggota.project.rkKetua.iku', function ($q) use ($year) {
            $q->where('year', $year);
        })
        ->latest()
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Base Query Daily Task Tahun Berjalan
    |--------------------------------------------------------------------------
    | Support data baru dan fallback data lama:
    | - Data baru: daily_tasks -> iki -> rkAnggota -> project -> rkKetua -> iku
    | - Data lama: daily_tasks -> rkAnggota -> project -> rkKetua -> iku
    |--------------------------------------------------------------------------
    */
    $dailyTaskBaseQuery = DailyTask::query()
        ->where(function ($q) use ($year) {
            $q->whereHas('rkAnggota.project.rkKetua.iku', function ($sub) use ($year) {
                $sub->where('year', $year);
            })
            ->orWhereHas('iki.rkAnggota.project.rkKetua.iku', function ($sub) use ($year) {
                $sub->where('year', $year);
            });
        });

    /*
    |--------------------------------------------------------------------------
    | Recent Tasks
    |--------------------------------------------------------------------------
    */
    $recentTasks = (clone $dailyTaskBaseQuery)
        ->select([
            'id',
            'rk_anggota_id',
            'iki_id',
            'activity',
            'date',
            'status',
            'evidence_url',
            'created_at',
            'updated_at',
        ])
        ->with([
            'iki.rkAnggota.user:id,name,email',
            'iki.rkAnggota.project:id,name,rk_ketua_id,team_id',
            'rkAnggota.user:id,name,email',
            'rkAnggota.project:id,name,rk_ketua_id,team_id',
        ])
        ->latest('date')
        ->latest()
        ->take(8)
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Daily Task Hari Ini
    |--------------------------------------------------------------------------
    */
    $dailyTaskToday = (clone $dailyTaskBaseQuery)
        ->with([
            'iki',
            'iki.rkAnggota.user',
            'iki.rkAnggota.project.team',
            'iki.rkAnggota.project.rkKetua.iku',

            'rkAnggota.user',
            'rkAnggota.project.team',
            'rkAnggota.project.rkKetua.iku',
        ])
        ->whereDate('date', $today)
        ->latest('updated_at')
        ->latest()
        ->get();

    $dailyTaskThisWeek = (clone $dailyTaskBaseQuery)
        ->with([
            'iki.rkAnggota.user',
            'iki.rkAnggota.project',
            'rkAnggota.user',
            'rkAnggota.project',
        ])
        ->whereBetween('date', [
            now()->startOfWeek()->toDateString(),
            now()->endOfWeek()->toDateString(),
        ])
        ->latest('date')
        ->latest()
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Legacy Variables
    |--------------------------------------------------------------------------
    */
    $totalIku = $ikus->count();
    $totalProject = $projects->count();
    $totalRkKetua = $rkKetuas->count();
    $totalRkAnggota = $rkAnggotas->count();
    $totalIki = $ikis->count();

    /*
|--------------------------------------------------------------------------
| Pegawai Aktif, Sudah Isi, dan Belum Isi Hari Ini
|--------------------------------------------------------------------------
| Pegawai aktif:
| - Punya RK Anggota tahun ini
| - Masih punya IKI draft/rejected
| - Atau belum punya IKI sama sekali
|--------------------------------------------------------------------------
*/
$userIdsWithActiveTasks = $rkAnggotas
    ->toBase()
    ->filter(function ($rk) {
        if (!$rk->user_id) {
            return false;
        }

        if ($rk->ikis->isEmpty()) {
            return true;
        }

        return $rk->ikis
            ->whereIn('status', [
                Iki::STATUS_DRAFT,
                Iki::STATUS_REJECTED,
            ])
            ->isNotEmpty();
    })
    ->pluck('user_id')
    ->filter()
    ->map(fn ($id) => (int) $id)
    ->unique()
    ->values();

$userIdsWithTaskToday = $dailyTaskToday
    ->toBase()
    ->map(function ($task) {
        $rk = $task->iki?->rkAnggota ?? $task->rkAnggota;

        return $rk?->user_id;
    })
    ->filter()
    ->map(fn ($id) => (int) $id)
    ->unique()
    ->values();

/*
|--------------------------------------------------------------------------
| FIX ERROR:
| Jangan pakai intersect() langsung pada Eloquent Collection berisi integer.
| Gunakan filter + contains agar Laravel tidak memanggil getKey() pada angka.
|--------------------------------------------------------------------------
*/
$userIdsAlreadyReportedToday = $userIdsWithTaskToday
    ->filter(fn ($userId) => $userIdsWithActiveTasks->contains((int) $userId))
    ->map(fn ($id) => (int) $id)
    ->unique()
    ->values();

$pegawaiBelumIsiTask = $rkAnggotas
    ->toBase()
    ->filter(function ($rk) use ($userIdsWithActiveTasks, $userIdsAlreadyReportedToday) {
        $userId = (int) $rk->user_id;

        return $userId
            && $userIdsWithActiveTasks->contains($userId)
            && !$userIdsAlreadyReportedToday->contains($userId);
    })
    ->unique('user_id')
    ->values();

$pegawaiSudahIsiTask = $userIdsAlreadyReportedToday->isEmpty()
    ? collect()
    : User::whereIn('id', $userIdsAlreadyReportedToday->all())
        ->orderBy('name')
        ->get(['id', 'name', 'email']);

$totalPegawaiAktif = $userIdsWithActiveTasks->count();
$totalSudahIsiTask = $userIdsAlreadyReportedToday->count();
$totalBelumIsiTask = $pegawaiBelumIsiTask->count();

$persentaseKepatuhan = $totalPegawaiAktif > 0
    ? round(($totalSudahIsiTask / $totalPegawaiAktif) * 100)
    : 0;

$persentaseBelumIsi = $totalPegawaiAktif > 0
    ? round(($totalBelumIsiTask / $totalPegawaiAktif) * 100)
    : 0;

    /*
    |--------------------------------------------------------------------------
    | Aktivitas Hari Ini Dikelompokkan Per Pegawai
    |--------------------------------------------------------------------------
    | Ini variabel penting untuk dashboard Kepala yang sederhana:
    | "Hari ini siapa mengerjakan apa?"
    |--------------------------------------------------------------------------
    */
    $aktivitasHariIniByPegawai = $dailyTaskToday
        ->groupBy(function ($task) {
            $rk = $task->iki?->rkAnggota ?? $task->rkAnggota;
            return $rk?->user_id ?? 'unknown';
        })
        ->map(function ($tasks) {
            $firstTask = $tasks->first();
            $firstRk = $firstTask->iki?->rkAnggota ?? $firstTask->rkAnggota;
            $user = $firstRk?->user;

            return [
                'user_id' => $user?->id,
                'name' => $user?->name ?? 'Pegawai',
                'email' => $user?->email,
                'total_task' => $tasks->count(),
                'items' => $tasks
                    ->sortByDesc('updated_at')
                    ->map(function ($task) {
                        $rk = $task->iki?->rkAnggota ?? $task->rkAnggota;
                        $project = $rk?->project;
                        $iki = $task->iki;

                        return [
                            'id' => $task->id,
                            'date' => $task->date,
                            'activity' => $task->activity,
                            'project' => $project?->name,
                            'team' => $project?->team?->name,
                            'iki' => $iki?->description,
                            'iki_status' => $iki?->status,
                            'evidence_url' => $task->evidence_url,
                            'has_evidence' => filled($task->evidence_url),
                            'updated_at' => $task->updated_at,
                        ];
                    })
                    ->values(),
            ];
        })
        ->sortBy('name')
        ->values();

    $topPegawaiHariIni = $dailyTaskToday
        ->groupBy(function ($task) {
            $rk = $task->iki?->rkAnggota ?? $task->rkAnggota;
            return $rk?->user?->name ?? 'Unknown';
        })
        ->map(fn ($tasks) => $tasks->count())
        ->sortDesc()
        ->take(5);

    /*
    |--------------------------------------------------------------------------
    | Daily Task Tanpa Bukti Dukung
    |--------------------------------------------------------------------------
    */
    $taskTanpaBuktiQuery = (clone $dailyTaskBaseQuery)
        ->with([
            'iki.rkAnggota.user',
            'iki.rkAnggota.project',
            'rkAnggota.user',
            'rkAnggota.project',
        ])
        ->where(function ($q) {
            $q->whereNull('evidence_url')
              ->orWhere('evidence_url', '');
        });

    $totalTaskTanpaBukti = (clone $taskTanpaBuktiQuery)->count();

    $taskTanpaBukti = $taskTanpaBuktiQuery
        ->latest('date')
        ->latest()
        ->take(20)
        ->get();

    $totalTask = (clone $dailyTaskBaseQuery)->count();

    /*
    |--------------------------------------------------------------------------
    | IKI Status Summary
    |--------------------------------------------------------------------------
    */
    $draftIki = $ikis->where('status', Iki::STATUS_DRAFT)->count();
    $submittedIki = $ikis->where('status', Iki::STATUS_SUBMITTED)->count();
    $approvedIki = $ikis->where('status', Iki::STATUS_APPROVED)->count();
    $rejectedIki = $ikis->where('status', Iki::STATUS_REJECTED)->count();

    /*
    |--------------------------------------------------------------------------
    | Legacy RK Status Summary
    |--------------------------------------------------------------------------
    */
    $draftRk = $rkAnggotas->where('status', RkAnggota::STATUS_DRAFT)->count();
    $submittedRk = $rkAnggotas->where('status', RkAnggota::STATUS_SUBMITTED)->count();
    $approvedRk = $rkAnggotas->filter(fn ($rk) => $rk->is_completed)->count();
    $rejectedRk = $rkAnggotas->where('status', RkAnggota::STATUS_REJECTED)->count();

    $avgIkuProgress = $ikus->count() ? round($ikus->avg(fn ($iku) => $iku->progress)) : 0;
    $avgProjectProgress = $projects->count() ? round($projects->avg(fn ($project) => $project->progress)) : 0;

    /*
    |--------------------------------------------------------------------------
    | Review Queue & IKI Belum Submit
    |--------------------------------------------------------------------------
    */
    $pendingIkis = Iki::with([
            'rkAnggota.user',
            'rkAnggota.project',
        ])
        ->where('status', Iki::STATUS_SUBMITTED)
        ->whereHas('rkAnggota.project.rkKetua.iku', function ($q) use ($year) {
            $q->where('year', $year);
        })
        ->latest()
        ->take(8)
        ->get();

    $pendingReviews = $pendingIkis;
    $pendingApprovalCount = $pendingIkis->count();

    $unsubmittedIkis = $ikis
        ->where('status', Iki::STATUS_DRAFT)
        ->values();

    $lowProgressIkus = $ikus
        ->sortBy('progress')
        ->take(5)
        ->values();

    $attentionProjects = $projects
        ->filter(function ($project) {
            $projectIkis = $project->rkAnggotas->flatMap(fn ($rk) => $rk->ikis);

            return (int) $project->progress < 50
                || $project->rkAnggotas->count() === 0
                || $projectIkis->count() === 0;
        })
        ->sortBy(fn ($project) => $project->progress)
        ->take(8)
        ->values();

    $rkKetuaProgress = $rkKetuas
        ->sortBy('progress')
        ->values();

    $projectByMonth = Project::selectRaw('MONTH(created_at) as month, COUNT(*) as total')
        ->whereHas('rkKetua.iku', function ($q) use ($year) {
            $q->where('year', $year);
        })
        ->whereYear('created_at', $year)
        ->groupBy('month')
        ->orderBy('month')
        ->pluck('total', 'month');

    $taskByMonth = DailyTask::selectRaw('MONTH(date) as month, COUNT(*) as total')
        ->where(function ($q) use ($year) {
            $q->whereHas('rkAnggota.project.rkKetua.iku', function ($sub) use ($year) {
                $sub->where('year', $year);
            })
            ->orWhereHas('iki.rkAnggota.project.rkKetua.iku', function ($sub) use ($year) {
                $sub->where('year', $year);
            });
        })
        ->whereYear('date', $year)
        ->groupBy('month')
        ->orderBy('month')
        ->pluck('total', 'month');

    /*
    |--------------------------------------------------------------------------
    | Variabel Numerik & Grafik Untuk Blade Kepala
    |--------------------------------------------------------------------------
    */
    $totalTaskHariIni = $dailyTaskToday->count();
    $pegawaiAktifHariIni = $userIdsAlreadyReportedToday->count();

    $totalIkuValue = $ikus->count();
    $avgIkuProgressValue = $ikus->count() ? round($ikus->avg(fn ($iku) => $iku->progress)) : 0;

    $totalRkKetuaValue = $rkKetuas->count();
    $totalProjectValue = $projects->count();

    $avgIkiProgressValue = $ikis->count() ? round($ikis->avg(fn ($iki) => $iki->progress)) : 0;

    $totalTaskValue = $totalTask;

    $approvedTaskStatus = defined(DailyTask::class . '::STATUS_APPROVED')
        ? DailyTask::STATUS_APPROVED
        : 'approved';

    $completedTaskValue = (clone $dailyTaskBaseQuery)
        ->where('status', $approvedTaskStatus)
        ->count();

    $completedProjectValue = $projects
        ->filter(fn ($p) => $p->progress >= 100)
        ->count();

    $lowIkiValue = $ikis
        ->filter(fn ($i) => $i->progress < 50)
        ->count();

    $lowProjectValue = $projects
        ->filter(fn ($p) => $p->progress < 50)
        ->count();

    $ikuChartData = $ikus
        ->map(fn ($iku) => [
            'name' => $iku->name,
            'progress' => $iku->progress,
        ])
        ->values()
        ->toArray();

    $rkKetuaChartData = $rkKetuas
        ->map(fn ($rk) => [
            'name' => Str::limit($rk->description ?? 'RK', 20),
            'progress' => $rk->progress,
        ])
        ->values()
        ->toArray();

    $projectChartData = $projects
        ->map(fn ($p) => [
            'name' => Str::limit($p->name, 20),
            'progress' => $p->progress,
        ])
        ->values()
        ->toArray();

    $ikiChartData = $ikis
        ->map(fn ($i) => [
            'name' => Str::limit($i->description ?? 'IKI', 20),
            'progress' => $i->progress,
        ])
        ->values()
        ->toArray();

    $projectByMonthArray = $projectByMonth->toArray();
    $taskByMonthArray = $taskByMonth->toArray();

    $ikiStatusSummary = [
        'draft' => $draftIki,
        'submitted' => $submittedIki,
        'approved' => $approvedIki,
        'rejected' => $rejectedIki,
    ];

    $lowProgressIkis = $ikis
        ->filter(fn ($i) => $i->progress < 50)
        ->sortBy('progress')
        ->take(5)
        ->values();

    /*
    |--------------------------------------------------------------------------
    | Label UI Sederhana Untuk Kepala
    |--------------------------------------------------------------------------
    */
    $tanggalHariIniLabel = now()->translatedFormat('l, d F Y');
    $lastUpdatedAt = now()->format('H:i');

    return view('dashboard.kepala', compact(
        'year',
        'ikus',
        'projects',
        'rkKetuas',
        'rkAnggotas',
        'ikis',
        'recentTasks',
        'dailyTaskToday',
        'dailyTaskThisWeek',

        'totalIku',
        'totalProject',
        'totalRkKetua',
        'totalRkAnggota',
        'totalIki',
        'totalTask',

        'totalBelumIsiTask',
        'pegawaiBelumIsiTask',
        'pegawaiSudahIsiTask',
        'totalSudahIsiTask',
        'totalPegawaiAktif',
        'persentaseKepatuhan',
        'persentaseBelumIsi',
        'topPegawaiHariIni',
        'aktivitasHariIniByPegawai',

        'taskTanpaBukti',
        'totalTaskTanpaBukti',

        'draftIki',
        'submittedIki',
        'approvedIki',
        'rejectedIki',

        'draftRk',
        'submittedRk',
        'approvedRk',
        'rejectedRk',

        'avgIkuProgress',
        'avgProjectProgress',

        'pendingIkis',
        'pendingReviews',
        'unsubmittedIkis',
        'lowProgressIkus',
        'attentionProjects',
        'rkKetuaProgress',
        'pendingApprovalCount',

        'projectByMonth',
        'taskByMonth',

        'totalTaskHariIni',
        'pegawaiAktifHariIni',
        'totalIkuValue',
        'avgIkuProgressValue',
        'totalRkKetuaValue',
        'totalProjectValue',
        'avgIkiProgressValue',
        'totalTaskValue',
        'completedTaskValue',
        'completedProjectValue',
        'lowIkiValue',
        'lowProjectValue',
        'ikuChartData',
        'rkKetuaChartData',
        'projectChartData',
        'ikiChartData',
        'projectByMonthArray',
        'taskByMonthArray',
        'ikiStatusSummary',
        'lowProgressIkis',

        'tanggalHariIniLabel',
        'lastUpdatedAt'
    ));
}


    /**
     * KETUA DASHBOARD
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
                'rkAnggotas.ikis.dailyTasks',
                'rkAnggotas.ikis.approver',
            ])
            ->where('leader_id', $user->id)
            ->whereHas('rkKetua.iku', function ($q) use ($year) {
                $q->where('year', $year);
            })
            ->latest()
            ->get();

        $projectIds = $projects->pluck('id');

        $rkKetuas = RkKetua::with([
                'iku',
                'team',
                'projects.rkAnggotas.ikis.dailyTasks',
            ])
            ->where('user_id', $user->id)
            ->whereHas('iku', function ($q) use ($year) {
                $q->where('year', $year);
            })
            ->latest()
            ->get();

        $rkAnggotas = RkAnggota::with([
                'project',
                'user',
                'ikis.dailyTasks',
                'ikis.approver',
                'dailyTasks',
                'approver',
            ])
            ->whereIn('project_id', $projectIds)
            ->latest()
            ->get();

        $ikis = Iki::with([
                'rkAnggota.user',
                'rkAnggota.project.rkKetua.iku',
                'dailyTasks',
                'approver',
            ])
            ->whereHas('rkAnggota.project', function ($q) use ($user) {
                $q->where('leader_id', $user->id);
            })
            ->whereHas('rkAnggota.project.rkKetua.iku', function ($q) use ($year) {
                $q->where('year', $year);
            })
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
        $totalIki = $ikis->count();

        $totalTask = $ikis->flatMap(fn ($iki) => $iki->dailyTasks)->count();

        if ($totalTask === 0) {
            $totalTask = $rkAnggotas->sum(function ($rk) {
                return $rk->dailyTasks->count();
            });
        }

        $draftIki = $ikis
            ->where('status', Iki::STATUS_DRAFT)
            ->count();

        $submittedIki = $ikis
            ->where('status', Iki::STATUS_SUBMITTED)
            ->count();

        $approvedIki = $ikis
            ->where('status', Iki::STATUS_APPROVED)
            ->count();

        $rejectedIki = $ikis
            ->where('status', Iki::STATUS_REJECTED)
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Legacy RK summary
        |--------------------------------------------------------------------------
        */
        $draftRk = $rkAnggotas
            ->where('status', RkAnggota::STATUS_DRAFT)
            ->count();

        $submittedRk = $rkAnggotas
            ->where('status', RkAnggota::STATUS_SUBMITTED)
            ->count();

        $approvedRk = $rkAnggotas
            ->filter(fn ($rk) => $rk->is_completed)
            ->count();

        $rejectedRk = $rkAnggotas
            ->where('status', RkAnggota::STATUS_REJECTED)
            ->count();

        $progress = $projects->count()
            ? round($projects->avg(fn ($project) => $project->progress))
            : 0;

        /*
        |--------------------------------------------------------------------------
        | IKI yang perlu direview
        |--------------------------------------------------------------------------
        | Tidak menampilkan IKI milik diri sendiri agar tidak mendorong self-review.
        |--------------------------------------------------------------------------
        */
        $pendingIkis = $ikis
            ->where('status', Iki::STATUS_SUBMITTED)
            ->filter(function ($iki) use ($user) {
                return (int) ($iki->rkAnggota->user_id ?? 0) !== (int) $user->id;
            })
            ->values();

        $pendingReviews = $pendingIkis;
        $pendingApprovalCount = $pendingIkis->count();

        /*
        |--------------------------------------------------------------------------
        | IKI Belum Submit
        |--------------------------------------------------------------------------
        */
        $unsubmittedIkis = $ikis
            ->where('status', Iki::STATUS_DRAFT)
            ->values();

        /*
        |--------------------------------------------------------------------------
        | PEKERJAAN SAYA SEBAGAI ANGGOTA / PELAKSANA
        |--------------------------------------------------------------------------
        | Data ketika user role ketua ikut project sebagai member.
        |--------------------------------------------------------------------------
        */
        $memberProjects = Project::with([
                'team',
                'leader',
                'rkKetua.iku',
                'members',
                'rkAnggotas.ikis.dailyTasks',
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
                'ikis.dailyTasks',
                'ikis.approver',
                'dailyTasks',
                'approver',
            ])
            ->where('user_id', $user->id)
            ->whereHas('project.rkKetua.iku', function ($q) use ($year) {
                $q->where('year', $year);
            })
            ->latest()
            ->get();

        $myIkis = Iki::with([
                'rkAnggota.project.rkKetua.iku',
                'dailyTasks',
                'approver',
            ])
            ->whereHas('rkAnggota', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->whereHas('rkAnggota.project.rkKetua.iku', function ($q) use ($year) {
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
        $myTotalIki = $myIkis->count();
        $myTotalTask = $myDailyTasks->count();

        $myDraftIki = $myIkis
            ->where('status', Iki::STATUS_DRAFT)
            ->count();

        $mySubmittedIki = $myIkis
            ->where('status', Iki::STATUS_SUBMITTED)
            ->count();

        $myApprovedIki = $myIkis
            ->where('status', Iki::STATUS_APPROVED)
            ->count();

        $myRejectedIki = $myIkis
            ->where('status', Iki::STATUS_REJECTED)
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Legacy aliases untuk view lama
        |--------------------------------------------------------------------------
        */
        $myDraftRk = $myRkAnggotas
            ->where('status', RkAnggota::STATUS_DRAFT)
            ->count();

        $mySubmittedRk = $myRkAnggotas
            ->where('status', RkAnggota::STATUS_SUBMITTED)
            ->count();

        $myApprovedRk = $myRkAnggotas
            ->filter(fn ($rk) => $rk->is_completed)
            ->count();

        $myRejectedRk = $myRkAnggotas
            ->where('status', RkAnggota::STATUS_REJECTED)
            ->count();
        
        /*
/*
|--------------------------------------------------------------------------
| Daily Task Hari Ini & Minggu Ini Untuk Dashboard Ketua
|--------------------------------------------------------------------------
| Fix variabel compact:
| - $dailyTaskToday
| - $dailyTaskThisWeek
| - $totalTaskHariIni
| - $pegawaiAktifHariIni
| - $aktivitasHariIniByPegawai
|
| Support:
| - Data lama: daily_tasks -> rkAnggota -> project
| - Data baru: daily_tasks -> iki -> rkAnggota -> project
|--------------------------------------------------------------------------
*/

$today = now()->toDateString();

$ketuaDailyTaskBaseQuery = DailyTask::with([
        'iki',
        'iki.rkAnggota.user',
        'iki.rkAnggota.project.team',
        'iki.rkAnggota.project.rkKetua.iku',

        'rkAnggota.user',
        'rkAnggota.project.team',
        'rkAnggota.project.rkKetua.iku',
    ])
    ->where(function ($q) use ($user, $year) {
        /*
        | Data lama:
        | daily_tasks -> rk_anggota -> project -> leader
        */
        $q->where(function ($legacy) use ($user, $year) {
            $legacy->whereHas('rkAnggota.project', function ($projectQuery) use ($user) {
                    $projectQuery->where('leader_id', $user->id);
                })
                ->whereHas('rkAnggota.project.rkKetua.iku', function ($ikuQuery) use ($year) {
                    $ikuQuery->where('year', $year);
                });
        })

        /*
        | Data baru:
        | daily_tasks -> iki -> rk_anggota -> project -> leader
        */
        ->orWhere(function ($new) use ($user, $year) {
            $new->whereHas('iki.rkAnggota.project', function ($projectQuery) use ($user) {
                    $projectQuery->where('leader_id', $user->id);
                })
                ->whereHas('iki.rkAnggota.project.rkKetua.iku', function ($ikuQuery) use ($year) {
                    $ikuQuery->where('year', $year);
                });
        });
    });

$dailyTaskToday = (clone $ketuaDailyTaskBaseQuery)
    ->whereDate('date', $today)
    ->latest('updated_at')
    ->latest()
    ->get();

$dailyTaskThisWeek = (clone $ketuaDailyTaskBaseQuery)
    ->whereBetween('date', [
        now()->startOfWeek()->toDateString(),
        now()->endOfWeek()->toDateString(),
    ])
    ->latest('date')
    ->latest()
    ->get();

$totalTaskHariIni = $dailyTaskToday->count();

$pegawaiAktifHariIni = $dailyTaskToday
    ->map(function ($task) {
        $rk = $task->iki?->rkAnggota ?? $task->rkAnggota;

        return $rk?->user_id;
    })
    ->filter()
    ->unique()
    ->count();

$aktivitasHariIniByPegawai = $dailyTaskToday
    ->groupBy(function ($task) {
        $rk = $task->iki?->rkAnggota ?? $task->rkAnggota;

        return $rk?->user_id ?? 'unknown';
    })
    ->map(function ($tasks) {
        $firstTask = $tasks->first();
        $firstRk = $firstTask->iki?->rkAnggota ?? $firstTask->rkAnggota;
        $user = $firstRk?->user;

        return [
            'user_id' => $user?->id,
            'name' => $user?->name ?? 'Pegawai',
            'email' => $user?->email,
            'total_task' => $tasks->count(),
            'items' => $tasks
                ->sortByDesc('updated_at')
                ->map(function ($task) {
                    $rk = $task->iki?->rkAnggota ?? $task->rkAnggota;
                    $project = $rk?->project;
                    $iki = $task->iki;

                    return [
                        'id' => $task->id,
                        'date' => $task->date,
                        'activity' => $task->activity,
                        'project' => $project?->name,
                        'team' => $project?->team?->name,
                        'iki' => $iki?->description,
                        'iki_status' => $iki?->status,
                        'evidence_url' => $task->evidence_url,
                        'has_evidence' => filled($task->evidence_url),
                        'updated_at' => $task->updated_at,
                    ];
                })
                ->values(),
        ];
    })
    ->sortBy('name')
    ->values();
    
        return view('dashboard.ketua', compact(
            'year',

            /*
            |--------------------------------------------------------------------------
            | Mode Ketua / Reviewer
            |--------------------------------------------------------------------------
            */
            'projects',
            'rkKetuas',
            'rkAnggotas',
            'ikis',
            'recentTasks',
            'dailyTaskThisWeek',
            'pendingApprovalCount',
            'dailyTaskToday',
            'pendingIkis',
            'pendingReviews',
            'unsubmittedIkis',
            'totalProject',
            'totalRkKetua',
            'totalRkAnggota',
            'totalIki',
            'totalTask',
            'draftIki',
            'submittedIki',
            'approvedIki',
            'rejectedIki',
            'draftRk',
            'submittedRk',
            'approvedRk',
            'rejectedRk',
            'progress',

            /*
            |--------------------------------------------------------------------------
            | Mode Pekerjaan Saya
            |--------------------------------------------------------------------------
            */
            'memberProjects',
            'myRkAnggotas',
            'myIkis',
            'myDailyTasks',
            'myTotalProject',
            'myTotalRk',
            'myTotalIki',
            'myTotalTask',
            'myDraftIki',
            'mySubmittedIki',
            'myApprovedIki',
            'myRejectedIki',
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
        | Anggota hanya melihat project tempat dia masuk sebagai member melalui
        | tabel project_members.
        |--------------------------------------------------------------------------
        */
        $projects = Project::with([
                'team',
                'leader',
                'rkKetua.iku',
                'members',
                'rkAnggotas.ikis.dailyTasks',
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
        */
        $rkAnggotas = RkAnggota::with([
                'project.team',
                'project.leader',
                'project.rkKetua.iku',
                'ikis.dailyTasks',
                'ikis.approver',
                'dailyTasks',
                'approver',
            ])
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        /*
        |--------------------------------------------------------------------------
        | IKI Saya
        |--------------------------------------------------------------------------
        | IKI adalah unit submit dan approval utama.
        |--------------------------------------------------------------------------
        */
        $ikis = Iki::with([
                'rkAnggota.project.rkKetua.iku',
                'rkAnggota.project.leader',
                'dailyTasks',
                'approver',
             ])
            ->whereHas('rkAnggota', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->latest()
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Daily Task Saya
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
        $totalIki = $ikis->count();
        $totalDailyTasks = $dailyTasks->count();

        $draftIki = $ikis
            ->where('status', Iki::STATUS_DRAFT)
            ->count();

        $submittedIki = $ikis
            ->where('status', Iki::STATUS_SUBMITTED)
            ->count();

        $approvedIki = $ikis
            ->where('status', Iki::STATUS_APPROVED)
            ->count();

        $rejectedIki = $ikis
            ->where('status', Iki::STATUS_REJECTED)
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Legacy RK summary
        |--------------------------------------------------------------------------
        */
        $draftRk = $rkAnggotas
            ->where('status', RkAnggota::STATUS_DRAFT)
            ->count();

        $submittedRk = $rkAnggotas
            ->where('status', RkAnggota::STATUS_SUBMITTED)
            ->count();

        $approvedRk = $rkAnggotas
            ->filter(fn ($rk) => $rk->is_completed)
            ->count();

        $rejectedRk = $rkAnggotas
            ->where('status', RkAnggota::STATUS_REJECTED)
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Progress Pribadi
        |--------------------------------------------------------------------------
        | Progress pribadi dihitung dari IKI approved dibanding total IKI.
        |--------------------------------------------------------------------------
        */
        $personalProgress = $totalIki === 0
            ? 0
            : round(($approvedIki / $totalIki) * 100);

        /*
        |--------------------------------------------------------------------------
        | RK yang masih bisa dikerjakan
        |--------------------------------------------------------------------------
        */
        $editableRkCount = $rkAnggotas
            ->filter(fn ($rk) => $rk->is_editable)
            ->count();

        /*
        |--------------------------------------------------------------------------
        | RK yang butuh IKI
        |--------------------------------------------------------------------------
        */
        $needIkiCount = $rkAnggotas
            ->filter(function ($rk) {
                return $rk->is_editable && $rk->ikis->count() === 0;
            })
            ->count();

        /*
        |--------------------------------------------------------------------------
        | IKI yang perlu disubmit
        |--------------------------------------------------------------------------
        */
        $needSubmitIkiCount = $ikis
            ->whereIn('status', [
                Iki::STATUS_DRAFT,
                Iki::STATUS_REJECTED,
            ])
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Legacy alias untuk view lama
        |--------------------------------------------------------------------------
        */
        $needDailyTaskCount = $rkAnggotas
            ->filter(function ($rk) {
                return $rk->is_editable
                    && $rk->dailyTasks->count() === 0;
            })
            ->count();

        $latestTasks = $dailyTasks->take(5);
        $latestRk = $rkAnggotas->take(5);
        $latestIkis = $ikis->take(5);
        $latestProjects = $projects->take(5);

        return view('dashboard.anggota', compact(
            'projects',
            'rkAnggotas',
            'ikis',
            'dailyTasks',
            'latestProjects',
            'latestRk',
            'latestIkis',
            'latestTasks',
            'totalProjects',
            'totalRk',
            'totalIki',
            'totalDailyTasks',
            'draftIki',
            'submittedIki',
            'approvedIki',
            'rejectedIki',
            'draftRk',
            'submittedRk',
            'approvedRk',
            'rejectedRk',
            'personalProgress',
            'editableRkCount',
            'needIkiCount',
            'needSubmitIkiCount',
            'needDailyTaskCount'
        ));
    }

    public function calendar(Request $request)
{
    $user = auth()->user();

    $year = $request->year ?? date('Y');

    if (!is_numeric($year)) {
        $year = date('Y');
    }

    $year = (int) $year;

    /*
    |--------------------------------------------------------------------------
    | Calendar Daily Task
    |--------------------------------------------------------------------------
    | Fokus utama calendar adalah aktivitas harian.
    | Project tetap bisa dimunculkan jika request membawa include_projects=1.
    |--------------------------------------------------------------------------
    */

    $taskQuery = DailyTask::with([
            'iki',
            'iki.rkAnggota.user',
            'iki.rkAnggota.project.team',
            'iki.rkAnggota.project.rkKetua.iku',

            'rkAnggota.user',
            'rkAnggota.project.team',
            'rkAnggota.project.rkKetua.iku',
        ])
        ->where(function ($q) use ($year) {
            $q->whereHas('rkAnggota.project.rkKetua.iku', function ($sub) use ($year) {
                $sub->where('year', $year);
            })
            ->orWhereHas('iki.rkAnggota.project.rkKetua.iku', function ($sub) use ($year) {
                $sub->where('year', $year);
            });
        });

    /*
    |--------------------------------------------------------------------------
    | Role Scope Calendar
    |--------------------------------------------------------------------------
    */
    if ($user->role === 'ketua') {
        $taskQuery->where(function ($q) use ($user) {
            $q->whereHas('rkAnggota.project', function ($sub) use ($user) {
                $sub->where('leader_id', $user->id);
            })
            ->orWhereHas('iki.rkAnggota.project', function ($sub) use ($user) {
                $sub->where('leader_id', $user->id);
            })
            ->orWhereHas('rkAnggota', function ($sub) use ($user) {
                $sub->where('user_id', $user->id);
            })
            ->orWhereHas('iki.rkAnggota', function ($sub) use ($user) {
                $sub->where('user_id', $user->id);
            });
        });
    }

    if ($user->role === 'anggota') {
        $taskQuery->where(function ($q) use ($user) {
            $q->whereHas('rkAnggota', function ($sub) use ($user) {
                $sub->where('user_id', $user->id);
            })
            ->orWhereHas('iki.rkAnggota', function ($sub) use ($user) {
                $sub->where('user_id', $user->id);
            });
        });
    }

    /*
    |--------------------------------------------------------------------------
    | FullCalendar biasanya mengirim start dan end.
    |--------------------------------------------------------------------------
    */
    if ($request->filled('start')) {
        $taskQuery->whereDate('date', '>=', $request->start);
    }

    if ($request->filled('end')) {
        $taskQuery->whereDate('date', '<=', $request->end);
    }

    $tasks = $taskQuery
        ->orderBy('date')
        ->get()
        ->map(function ($task) {
            $rk = $task->iki?->rkAnggota ?? $task->rkAnggota;
            $user = $rk?->user;
            $project = $rk?->project;
            $iki = $task->iki;

            $status = $iki?->status ?? $rk?->status ?? $task->status;

            return [
                'id' => 'task-' . $task->id,
                'title' => ($user?->name ?? 'Pegawai') . ' - ' . Str::limit($task->activity, 50),
                'start' => $task->date,
                'allDay' => true,
                'type' => 'task',
                'extendedProps' => [
                    'task_id' => $task->id,
                    'pegawai' => $user?->name ?? 'Pegawai',
                    'project' => $project?->name,
                    'team' => $project?->team?->name,
                    'activity' => $task->activity,
                    'evidence_url' => $task->evidence_url,
                    'has_evidence' => filled($task->evidence_url),
                    'iki' => $iki?->description,
                    'status' => $status,
                ],
            ];
        });

    /*
    |--------------------------------------------------------------------------
    | Optional Project Events
    |--------------------------------------------------------------------------
    | Default calendar kepala sebaiknya tidak penuh project.
    | Jika butuh project, panggil: /calendar/events?include_projects=1
    |--------------------------------------------------------------------------
    */
    $projects = collect();

    if ($request->boolean('include_projects')) {
        $projectQuery = Project::with([
                'rkKetua.iku',
                'team',
                'leader',
            ])
            ->whereNotNull('start_date')
            ->whereHas('rkKetua.iku', function ($q) use ($year) {
                $q->where('year', $year);
            });

        if ($user->role === 'ketua') {
            $projectQuery->where(function ($q) use ($user) {
                $q->where('leader_id', $user->id)
                    ->orWhereHas('members', function ($sub) use ($user) {
                        $sub->where('users.id', $user->id);
                    });
            });
        }

        if ($user->role === 'anggota') {
            $projectQuery->whereHas('rkAnggotas', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        $projects = $projectQuery
            ->get()
            ->map(function ($project) {
                return [
                    'id' => 'project-' . $project->id,
                    'title' => 'Project: ' . Str::limit($project->name, 50),
                    'start' => $project->start_date,
                    'end' => $project->end_date,
                    'allDay' => true,
                    'type' => 'project',
                    'extendedProps' => [
                        'project_id' => $project->id,
                        'project' => $project->name,
                        'team' => $project->team?->name,
                        'leader' => $project->leader?->name,
                    ],
                ];
            });
    }

    return response()->json(
        $projects
            ->merge($tasks)
            ->values()
    );
}

    public function stats(Request $request)
    {
        $year = $request->year ?? date('Y');

        /*
        |--------------------------------------------------------------------------
        | Stats API - Admin Dashboard
        |--------------------------------------------------------------------------
        | Flow progress terbaru:
        | IKI approved -> RK Anggota -> Project -> RK Ketua -> IKU
        |--------------------------------------------------------------------------
        */

        $ikus = Iku::with([
                'rkKetuas.projects.rkAnggotas.ikis.dailyTasks',
            ])
            ->where('year', $year)
            ->get();

        $rkKetuas = RkKetua::with([
                'iku',
                'team.leader',
                'projects.rkAnggotas.ikis.dailyTasks',
            ])
            ->whereHas('iku', function ($q) use ($year) {
                $q->where('year', $year);
            })
            ->get();

        $projects = Project::with([
                'team',
                'leader',
                'rkKetua.iku',
                'members',
                'rkAnggotas.ikis.dailyTasks',
            ])
            ->whereHas('rkKetua.iku', function ($q) use ($year) {
                $q->where('year', $year);
            })
            ->latest()
            ->get();

        $rkAnggotas = RkAnggota::with([
                'user',
                'project.rkKetua.iku',
                'ikis.dailyTasks',
            ])
            ->whereHas('project.rkKetua.iku', function ($q) use ($year) {
                $q->where('year', $year);
            })
            ->latest()
            ->get();

        $ikis = Iki::with([
                'rkAnggota.user',
                'rkAnggota.project.rkKetua.iku',
                'dailyTasks',
            ])
            ->whereHas('rkAnggota.project.rkKetua.iku', function ($q) use ($year) {
                $q->where('year', $year);
            })
            ->latest()
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Daily Task Monitoring
        |--------------------------------------------------------------------------
        */
        $dailyTaskQuery = DailyTask::whereHas('rkAnggota.project.rkKetua.iku', function ($q) use ($year) {
            $q->where('year', $year);
        });

        $totalTasks = (clone $dailyTaskQuery)->count();

        $pendingTasks = (clone $dailyTaskQuery)
            ->where('status', DailyTask::STATUS_PENDING)
            ->count();

        $approvedTasks = (clone $dailyTaskQuery)
            ->where('status', DailyTask::STATUS_APPROVED)
            ->count();

        $rejectedTasks = (clone $dailyTaskQuery)
            ->where('status', DailyTask::STATUS_REJECTED)
            ->count();

        /*
        |--------------------------------------------------------------------------
        | IKI Status Summary
        |--------------------------------------------------------------------------
        */
        $draftIki = $ikis
            ->where('status', Iki::STATUS_DRAFT)
            ->count();

        $submittedIki = $ikis
            ->where('status', Iki::STATUS_SUBMITTED)
            ->count();

        $approvedIki = $ikis
            ->where('status', Iki::STATUS_APPROVED)
            ->count();

        $rejectedIki = $ikis
            ->where('status', Iki::STATUS_REJECTED)
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Legacy RK Status Summary
        |--------------------------------------------------------------------------
        */
        $draftRk = $rkAnggotas
            ->where('status', RkAnggota::STATUS_DRAFT)
            ->count();

        $submittedRk = $rkAnggotas
            ->where('status', RkAnggota::STATUS_SUBMITTED)
            ->count();

        $approvedRk = $rkAnggotas
            ->filter(fn ($rk) => $rk->is_completed)
            ->count();

        $rejectedRk = $rkAnggotas
            ->where('status', RkAnggota::STATUS_REJECTED)
            ->count();

        $totalRk = $rkAnggotas->count();

        /*
        |--------------------------------------------------------------------------
        | Progress Averages
        |--------------------------------------------------------------------------
        */
        $avgIkuProgress = $ikus->count()
            ? round($ikus->avg(fn ($iku) => $iku->progress))
            : 0;

        $avgRkKetuaProgress = $rkKetuas->count()
            ? round($rkKetuas->avg(fn ($rkKetua) => $rkKetua->progress))
            : 0;

        $avgProjectProgress = $projects->count()
            ? round($projects->avg(fn ($project) => $project->progress))
            : 0;

        /*
        |--------------------------------------------------------------------------
        | Monthly Monitoring Data
        |--------------------------------------------------------------------------
        */
        $projectByMonth = Project::selectRaw('MONTH(created_at) as month, COUNT(*) as total')
            ->whereHas('rkKetua.iku', function ($q) use ($year) {
                $q->where('year', $year);
            })
            ->whereYear('created_at', $year)
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        $taskByMonth = DailyTask::selectRaw('MONTH(date) as month, COUNT(*) as total')
            ->whereHas('rkAnggota.project.rkKetua.iku', function ($q) use ($year) {
                $q->where('year', $year);
            })
            ->whereYear('date', $year)
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        /*
        |--------------------------------------------------------------------------
        | Pending Reviews
        |--------------------------------------------------------------------------
        | Review utama sekarang adalah IKI submitted.
        |--------------------------------------------------------------------------
        */
        $pendingReviews = $ikis
            ->where('status', Iki::STATUS_SUBMITTED)
            ->take(10)
            ->map(function ($iki) {
                return [
                    'id' => $iki->id,
                    'description' => $iki->description,
                    'status' => $iki->status,
                    'status_label' => $iki->status_label ?? null,
                    'user' => $iki->rkAnggota && $iki->rkAnggota->user ? [
                        'id' => $iki->rkAnggota->user->id,
                        'name' => $iki->rkAnggota->user->name,
                    ] : null,
                    'project' => $iki->rkAnggota && $iki->rkAnggota->project ? [
                        'id' => $iki->rkAnggota->project->id,
                        'name' => $iki->rkAnggota->project->name,
                    ] : null,
                ];
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | IKU Progress Data
        |--------------------------------------------------------------------------
        */
        $ikuProgress = $ikus
            ->map(function ($iku) {
                $rkKetuas = $iku->rkKetuas;
                $projects = $rkKetuas->flatMap(fn ($rkKetua) => $rkKetua->projects);
                $rkAnggotas = $projects->flatMap(fn ($project) => $project->rkAnggotas);
                $ikis = $rkAnggotas->flatMap(fn ($rk) => $rk->ikis);

                return [
                    'id' => $iku->id,
                    'name' => $iku->name,
                    'year' => $iku->year,
                    'target' => $iku->target ?? null,
                    'satuan' => $iku->satuan ?? null,
                    'progress' => $iku->progress,
                    'progress_label' => $iku->progress_label ?? null,
                    'rk_ketua_count' => $rkKetuas->count(),
                    'project_count' => $projects->count(),
                    'rk_anggota_count' => $rkAnggotas->count(),
                    'total_iki_count' => $ikis->count(),
                    'approved_iki_count' => $ikis
                        ->where('status', Iki::STATUS_APPROVED)
                        ->count(),

                    /*
                    |--------------------------------------------------------------------------
                    | Legacy alias
                    |--------------------------------------------------------------------------
                    */
                    'approved_rk_anggota_count' => $rkAnggotas
                        ->filter(fn ($rk) => $rk->is_completed)
                        ->count(),
                ];
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | RK Ketua Progress Data
        |--------------------------------------------------------------------------
        */
        $rkKetuaProgress = $rkKetuas
            ->map(function ($rkKetua) {
                $projects = $rkKetua->projects;
                $rkAnggotas = $projects->flatMap(fn ($project) => $project->rkAnggotas);
                $ikis = $rkAnggotas->flatMap(fn ($rk) => $rk->ikis);

                return [
                    'id' => $rkKetua->id,
                    'description' => $rkKetua->description,
                    'progress' => $rkKetua->progress,
                    'progress_label' => $rkKetua->progress_label ?? null,
                    'project_count' => $projects->count(),
                    'rk_anggota_count' => $rkAnggotas->count(),
                    'total_iki_count' => $ikis->count(),
                    'approved_iki_count' => $ikis
                        ->where('status', Iki::STATUS_APPROVED)
                        ->count(),

                    /*
                    |--------------------------------------------------------------------------
                    | Legacy alias
                    |--------------------------------------------------------------------------
                    */
                    'approved_rk_anggota_count' => $rkAnggotas
                        ->filter(fn ($rk) => $rk->is_completed)
                        ->count(),

                    'iku' => $rkKetua->iku ? [
                        'id' => $rkKetua->iku->id,
                        'name' => $rkKetua->iku->name,
                        'year' => $rkKetua->iku->year,
                    ] : null,
                    'team' => $rkKetua->team ? [
                        'id' => $rkKetua->team->id,
                        'name' => $rkKetua->team->name,
                    ] : null,
                ];
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Project Progress Data
        |--------------------------------------------------------------------------
        */
        $projectProgress = $projects
            ->map(function ($project) {
                $rkAnggotas = $project->rkAnggotas;
                $ikis = $rkAnggotas->flatMap(fn ($rk) => $rk->ikis);

                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'status' => $project->status,
                    'progress' => $project->progress,
                    'progress_label' => $project->progress_label ?? null,

                    'total_rk_count' => $rkAnggotas->count(),
                    'completed_rk_count' => $rkAnggotas
                        ->filter(fn ($rk) => $rk->is_completed)
                        ->count(),

                    'total_iki_count' => $ikis->count(),
                    'approved_iki_count' => $ikis
                        ->where('status', Iki::STATUS_APPROVED)
                        ->count(),

                    /*
                    |--------------------------------------------------------------------------
                    | Legacy alias
                    |--------------------------------------------------------------------------
                    */
                    'approved_rk_count' => $rkAnggotas
                        ->filter(fn ($rk) => $rk->is_completed)
                        ->count(),

                    'members_count' => $project->members->count(),
                    'team' => $project->team ? [
                        'id' => $project->team->id,
                        'name' => $project->team->name,
                    ] : null,
                    'leader' => $project->leader ? [
                        'id' => $project->leader->id,
                        'name' => $project->leader->name,
                    ] : null,
                    'rk_ketua' => $project->rkKetua ? [
                        'id' => $project->rkKetua->id,
                        'description' => $project->rkKetua->description,
                    ] : null,
                    'iku' => $project->rkKetua && $project->rkKetua->iku ? [
                        'id' => $project->rkKetua->iku->id,
                        'name' => $project->rkKetua->iku->name,
                    ] : null,
                ];
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Attention Lists
        |--------------------------------------------------------------------------
        */
        $lowProgressIkus = $ikuProgress
            ->sortBy('progress')
            ->take(5)
            ->values();

        $attentionProjects = $projectProgress
            ->filter(function ($project) {
                return (int) ($project['progress'] ?? 0) < 50
                    || (int) ($project['total_rk_count'] ?? 0) === 0
                    || (int) ($project['total_iki_count'] ?? 0) === 0;
            })
            ->sortBy('progress')
            ->take(10)
            ->values();

        $rkKetuaWithoutProject = $rkKetuaProgress
            ->filter(function ($rkKetua) {
                return (int) ($rkKetua['project_count'] ?? 0) === 0;
            })
            ->values();

        return response()->json([
            /*
            |--------------------------------------------------------------------------
            | Basic Counts
            |--------------------------------------------------------------------------
            */
            'year' => (int) $year,
            'users' => User::count(),
            'teams' => Team::count(),
            'ikus_count' => $ikus->count(),
            'rk_ketuas_count' => $rkKetuas->count(),
            'projects' => $projects->count(),
            'rk_anggotas_count' => $rkAnggotas->count(),
            'ikis_count' => $ikis->count(),
            'tasks' => $totalTasks,

            /*
            |--------------------------------------------------------------------------
            | Main Progress
            |--------------------------------------------------------------------------
            */
            'avg_iku_progress' => $avgIkuProgress,
            'avg_rk_ketua_progress' => $avgRkKetuaProgress,
            'avg_project_progress' => $avgProjectProgress,

            /*
            |--------------------------------------------------------------------------
            | IKI Status Summary
            |--------------------------------------------------------------------------
            */
            'iki_status_summary' => [
                'draft' => $draftIki,
                'submitted' => $submittedIki,
                'approved' => $approvedIki,
                'rejected' => $rejectedIki,
                'total' => $ikis->count(),
            ],

            /*
            |--------------------------------------------------------------------------
            | Legacy RK Status Summary
            |--------------------------------------------------------------------------
            */
            'rk_status_summary' => [
                'draft' => $draftRk,
                'submitted' => $submittedRk,
                'approved' => $approvedRk,
                'rejected' => $rejectedRk,
                'total' => $totalRk,
            ],

            /*
            |--------------------------------------------------------------------------
            | Daily Task Monitoring Summary
            |--------------------------------------------------------------------------
            */
            'task_status_summary' => [
                'pending' => $pendingTasks,
                'approved' => $approvedTasks,
                'rejected' => $rejectedTasks,
                'total' => $totalTasks,
            ],

            /*
            |--------------------------------------------------------------------------
            | Chart Data
            |--------------------------------------------------------------------------
            */
            'iku_progress' => $ikuProgress,
            'rk_ketua_progress' => $rkKetuaProgress,
            'project_progress' => $projectProgress,
            'project_by_month' => $projectByMonth,
            'task_by_month' => $taskByMonth,

            /*
            |--------------------------------------------------------------------------
            | Actionable Lists
            |--------------------------------------------------------------------------
            */
            'pending_reviews' => $pendingReviews,
            'low_progress_ikus' => $lowProgressIkus,
            'attention_projects' => $attentionProjects,
            'rk_ketua_without_project' => $rkKetuaWithoutProject,
        ]);
    }

   public function exportDailyTasks(Request $request)
{
    $user = auth()->user();
    $year = $request->year ?? date('Y');

    $query = DailyTask::with([
        'iki',
        'rkAnggota.user',
        'rkAnggota.project',
    ]);

    /*
    |--------------------------------------------------------------------------
    | Filter Role
    |--------------------------------------------------------------------------
    */

    if ($user->role === 'ketua') {

        $query->whereHas('rkAnggota.project', function ($q) use ($user) {
            $q->where('leader_id', $user->id);
        });

    } elseif ($user->role === 'anggota') {

        $query->whereHas('rkAnggota', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });

    }

    /*
    |--------------------------------------------------------------------------
    | Filter Tahun
    |--------------------------------------------------------------------------
    */

    $query->whereHas('rkAnggota.project.rkKetua.iku', function ($q) use ($year) {
        $q->where('year', $year);
    });

    /*
    |--------------------------------------------------------------------------
    | Filter Rentang Tanggal
    |--------------------------------------------------------------------------
    */

    if ($request->filled('start_date')) {
        $query->whereDate('date', '>=', $request->start_date);
    }

    if ($request->filled('end_date')) {
        $query->whereDate('date', '<=', $request->end_date);
    }

    $tasks = $query
        ->orderBy('date')
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Nama File Export
    |--------------------------------------------------------------------------
    */

    $fileName = 'Rekap_Kegiatan_Harian';

    if (
        $request->filled('start_date') &&
        $request->filled('end_date')
    ) {
        $fileName .= '_'
            . $request->start_date
            . '_sampai_'
            . $request->end_date;
    } else {
        $fileName .= '_' . $year;
    }

    $fileName .= '.xlsx';

    return \Maatwebsite\Excel\Facades\Excel::download(
        new \App\Exports\DailyTaskExport($tasks),
        $fileName
    );
}

}