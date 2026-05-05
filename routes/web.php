<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IkuController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RkKetuaController;
use App\Http\Controllers\RkAnggotaController;
use App\Http\Controllers\DailyTaskController;
use App\Http\Controllers\IkuImportController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\NotificationController;

/*
|--------------------------------------------------------------------------
| PUBLIC
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    if (!auth()->check()) {
        return redirect()->route('login');
    }

    $role = auth()->user()->role;

    return match ($role) {
        'admin' => redirect()->route('admin.dashboard'),
        'ketua' => redirect()->route('ketua.dashboard'),
        'anggota' => redirect()->route('anggota.dashboard'),
        'kepala' => redirect()->route('kepala.dashboard'),
        default => redirect()->route('login'),
    };
});

/*
|--------------------------------------------------------------------------
| AUTHENTICATED AREA
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | PROFILE
    |--------------------------------------------------------------------------
    */

    Route::resource('profile', ProfileController::class)
        ->only(['edit', 'update', 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD PER ROLE
    |--------------------------------------------------------------------------
    */

    Route::get('/admin', [DashboardController::class, 'admin'])
        ->middleware('role:admin')
        ->name('admin.dashboard');

    Route::get('/kepala', [DashboardController::class, 'kepala'])
        ->middleware('role:kepala')
        ->name('kepala.dashboard');

    Route::get('/ketua', [DashboardController::class, 'ketua'])
        ->middleware('role:ketua')
        ->name('ketua.dashboard');

    Route::get('/anggota', [DashboardController::class, 'anggota'])
        ->middleware('role:anggota')
        ->name('anggota.dashboard');

    Route::get('/calendar/events', [DashboardController::class, 'calendar'])
        ->name('calendar.events');


    /*
    |--------------------------------------------------------------------------
    | SEARCH ROUTES
    |--------------------------------------------------------------------------
    | Search harus tetap auth supaya endpoint data internal tidak bisa diakses
    | tanpa login. Path tetap sama agar Blade/AJAX lama tidak rusak.
    */

    Route::get('/iku/search', [IkuController::class, 'search'])
        ->name('iku.search');

    Route::get('/rk-ketua/search', [RkKetuaController::class, 'search'])
        ->name('rk_ketua.search');

    Route::get('/project/search', [ProjectController::class, 'search'])
        ->name('project.search');


    /*
    |--------------------------------------------------------------------------
    | ADMIN ROUTES
    |--------------------------------------------------------------------------
    */

    Route::prefix('admin')
        ->middleware('role:admin')
        ->group(function () {

            /*
            |--------------------------------------------------------------------------
            | USER & TEAM MANAGEMENT
            |--------------------------------------------------------------------------
            */

            Route::resource('users', UserController::class)
                ->except(['create', 'edit'])
                ->names('admin.users');

            Route::post('users/import', [UserController::class, 'import'])
                ->name('admin.users.import');

            Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])
                ->name('admin.users.reset');

            Route::resource('team', TeamController::class)
                ->names('admin.team');


            /*
            |--------------------------------------------------------------------------
            | CORE SYSTEM MANAGEMENT
            |--------------------------------------------------------------------------
            */

            Route::resource('iku', IkuController::class);

            Route::post('iku/import', [IkuImportController::class, 'import'])
                ->name('iku.import');

            Route::resource('rk-ketua', RkKetuaController::class);

            /*
            |--------------------------------------------------------------------------
            | PROJECT
            |--------------------------------------------------------------------------
            | Export harus diletakkan sebelum resource project agar tidak dianggap
            | sebagai parameter {project}.
            */

            Route::get('project/export', [ProjectController::class, 'export'])
                ->name('project.export');

            Route::resource('project', ProjectController::class);

            Route::resource('rk-anggota', RkAnggotaController::class)
                ->except(['create', 'edit']);

            Route::patch('rk-anggota/{id}/submit', [RkAnggotaController::class, 'submit'])
                ->name('admin.rk-anggota.submit');

            Route::patch('rk-anggota/{id}/approve', [RkAnggotaController::class, 'approve'])
                ->name('admin.rk-anggota.approve');

            Route::patch('rk-anggota/{id}/reject', [RkAnggotaController::class, 'reject'])
                ->name('admin.rk-anggota.reject');

            Route::resource('daily-task', DailyTaskController::class);


            /*
            |--------------------------------------------------------------------------
            | ADMIN EXTRA
            |--------------------------------------------------------------------------
            */

            Route::get('stats', [DashboardController::class, 'stats'])
                ->name('admin.stats');
        });


    /*
    |--------------------------------------------------------------------------
    | KETUA ROUTES
    |--------------------------------------------------------------------------
    | Flow final ketua:
    | - Ketua bisa mengelola RK Ketua miliknya sendiri.
    | - Ketua bisa mengelola project yang dia pimpin.
    | - Ketua bisa melihat project yang dia pimpin atau dia ikuti sebagai member.
    | - Ketua melihat RK Anggota dari project yang dia pimpin untuk review.
    | - Ketua bisa mengelola RK pribadi dan Daily Task pribadi lewat mode=mine.
    | - Approval dilakukan di RK Anggota, bukan Daily Task.
    */

    Route::prefix('ketua')
        ->middleware('role:ketua')
        ->group(function () {

            Route::resource('rk-ketua', RkKetuaController::class)
                ->except(['create', 'edit'])
                ->names('ketua.rk-ketua');

            Route::resource('project', ProjectController::class)
                ->except(['create', 'edit'])
                ->names('ketua.project');

            Route::resource('rk-anggota', RkAnggotaController::class)
                ->except(['create', 'edit']);

            Route::patch('rk-anggota/{id}/submit', [RkAnggotaController::class, 'submit'])
                ->name('ketua.rk-anggota.submit');

            Route::patch('rk-anggota/{id}/approve', [RkAnggotaController::class, 'approve'])
                ->name('ketua.rk-anggota.approve');

            Route::patch('rk-anggota/{id}/reject', [RkAnggotaController::class, 'reject'])
                ->name('ketua.rk-anggota.reject');

            Route::resource('daily-task', DailyTaskController::class)
                ->except(['create', 'edit']);
        });


    /*
    |--------------------------------------------------------------------------
    | ANGGOTA ROUTES
    |--------------------------------------------------------------------------
    | Flow anggota:
    | - Anggota melihat RK Anggota miliknya.
    | - Anggota membuat Daily Task.
    | - Anggota submit RK Anggota.
    */

    Route::prefix('anggota')
        ->middleware('role:anggota')
        ->group(function () {

            Route::resource('daily-task', DailyTaskController::class);

            Route::resource('rk-anggota', RkAnggotaController::class)
                ->except(['create', 'edit']);
            
            Route::resource('project', ProjectController::class)
                ->only(['index', 'show'])
                ->names('anggota.project');

            Route::patch('rk-anggota/{id}/submit', [RkAnggotaController::class, 'submit'])
                ->name('anggota.rk-anggota.submit');
            
            
        });


    /*
    |--------------------------------------------------------------------------
    | AJAX ROUTES
    |--------------------------------------------------------------------------
    */

    Route::get('rk-ketua/{id}/members', [ProjectController::class, 'getMembers'])
        ->name('rk-ketua.members');


    /*
    |--------------------------------------------------------------------------
    | NOTIFICATION ROUTES
    |--------------------------------------------------------------------------
    */

    Route::prefix('notification')->group(function () {

        Route::get('/', [NotificationController::class, 'index'])
            ->name('notification.index');

        Route::get('/unread-count', [NotificationController::class, 'unreadCount'])
            ->name('notification.unread-count');

        Route::post('{id}/read', [NotificationController::class, 'markAsRead'])
            ->name('notification.read');

        Route::post('read-all', [NotificationController::class, 'markAllAsRead'])
            ->name('notification.readAll');
    });


    /*
    |--------------------------------------------------------------------------
    | MODE SWITCH
    |--------------------------------------------------------------------------
    */

    Route::post('/switch-mode', function (Request $request) {
        session(['mode' => $request->mode]);

        return back();
    })->name('switch.mode');
});


require __DIR__ . '/auth.php';