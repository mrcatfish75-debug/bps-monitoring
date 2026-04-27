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
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| AUTH
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    /*
    | PROFILE
    */
    Route::resource('profile', ProfileController::class)
        ->only(['edit','update','destroy']);

    /*
    | DASHBOARD
    */
    Route::get('/admin', [DashboardController::class, 'admin'])->middleware('role:admin');
    Route::get('/kepala', [DashboardController::class, 'kepala'])->middleware('role:kepala');
    Route::get('/ketua', [DashboardController::class, 'ketua'])->middleware('role:ketua');
    Route::get('/anggota', [DashboardController::class, 'anggota'])->middleware('role:anggota');

    Route::get('/calendar/events', [DashboardController::class, 'calendar']);

    /*
    |--------------------------------------------------------------------------
    | ADMIN
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin')->middleware('role:admin')->group(function () {

        // USER & TEAM
        Route::resource('users', UserController::class)->names('admin.users');
        Route::resource('team', TeamController::class)->names('admin.team');

        // CORE SYSTEM
        Route::resource('iku', IkuController::class);
        Route::resource('rk-ketua', RkKetuaController::class);
        Route::resource('project', ProjectController::class);
        Route::resource('rk-anggota', RkAnggotaController::class);
        Route::patch('rk-anggota/{id}/submit', [RkAnggotaController::class, 'submit'])
         ->name('admin.rk-anggota.submit');

        Route::patch('rk-anggota/{id}/approve', [RkAnggotaController::class, 'approve'])
            ->name('admin.rk-anggota.approve');

        Route::patch('rk-anggota/{id}/reject', [RkAnggotaController::class, 'reject'])
            ->name('admin.rk-anggota.reject');

        Route::resource('daily-task', DailyTaskController::class);

        // EXTRA
        Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])
            ->name('admin.users.reset');

        Route::post('iku/import', [IkuImportController::class, 'import'])
            ->name('iku.import');

        Route::get('project/export', [ProjectController::class, 'export'])
            ->name('project.export');

        Route::get('stats', [DashboardController::class, 'stats'])
            ->name('admin.stats');
    });

    /*
    |--------------------------------------------------------------------------
    | KETUA
    |--------------------------------------------------------------------------
    */
   Route::prefix('ketua')->middleware('role:ketua')->group(function () {

    Route::resource('rk-ketua', RkKetuaController::class)
        ->only(['index','store','show']);

    Route::resource('project', ProjectController::class)
        ->only(['index','store','show']);
    
    Route::resource('rk-anggota', RkAnggotaController::class)
    ->only(['index', 'show']);

    Route::post('daily-task/{id}/approve', [DailyTaskController::class, 'approve'])
        ->name('daily-task.approve');

    Route::post('daily-task/{id}/reject', [DailyTaskController::class, 'reject'])
        ->name('daily-task.reject');

    Route::patch('rk-anggota/{id}/approve', [RkAnggotaController::class, 'approve'])
        ->name('ketua.rk-anggota.approve');

    Route::patch('rk-anggota/{id}/reject', [RkAnggotaController::class, 'reject'])
        ->name('ketua.rk-anggota.reject');

    Route::resource('daily-task', DailyTaskController::class)
    ->only(['index', 'show']);
});


    /*
    |--------------------------------------------------------------------------
    | ANGGOTA
    |--------------------------------------------------------------------------
    */
    Route::prefix('anggota')->middleware('role:anggota')->group(function () {

    Route::resource('daily-task', DailyTaskController::class);

    Route::resource('rk-anggota', RkAnggotaController::class)
        ->only(['index', 'show']);

    Route::patch('rk-anggota/{id}/submit', [RkAnggotaController::class, 'submit'])
        ->name('anggota.rk-anggota.submit');
});

    /*
    |--------------------------------------------------------------------------
    | AJAX (GLOBAL)
    |--------------------------------------------------------------------------
    */
    Route::get('rk-ketua/{id}/members', [ProjectController::class, 'getMembers'])
        ->name('rk-ketua.members');

    /*
    |--------------------------------------------------------------------------
    | NOTIFICATION
    |--------------------------------------------------------------------------
    */
    Route::prefix('notification')->group(function () {

        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);

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


    Route::post('/admin/users/import', [UserController::class, 'import'])
    ->name('admin.users.import');

Route::post('/admin/users/{id}/reset-password', [UserController::class, 'resetPassword'])
    ->name('admin.users.reset');

    Route::post('/admin/users/import', [UserController::class, 'import'])->name('admin.users.import');

});

Route::get('/iku/search', [IkuController::class, 'search'])->name('iku.search');
Route::get('/rk-ketua/search', [RkKetuaController::class, 'search'])->name('rk_ketua.search');
Route::get('/project/search', [ProjectController::class, 'search'])->name('project.search');

require __DIR__.'/auth.php';