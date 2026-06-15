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
use App\Http\Controllers\IkiController;
use App\Http\Controllers\DailyTaskController;
use App\Http\Controllers\IkuImportController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Auth\ForcePasswordChangeController;
use App\Http\Controllers\Auth\ForgotPasswordController;

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
| FORGOT PASSWORD ROUTES
|--------------------------------------------------------------------------
| Reset password mandiri via email token.
| Route ini harus bisa diakses user yang belum login.
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showForgotForm'])
        ->name('password.request');

    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])
        ->middleware('throttle:6,1')
        ->name('password.email');

    Route::get('/reset-password/{token}', [ForgotPasswordController::class, 'showResetForm'])
        ->name('password.reset');

    Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword'])
        ->middleware('throttle:6,1')
        ->name('password.store');
});

/*
|--------------------------------------------------------------------------
| AUTHENTICATED AREA
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'force.password.change'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | PROFILE
    |--------------------------------------------------------------------------
    */

    Route::resource('profile', ProfileController::class)
        ->only(['edit', 'update', 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | FORCE PASSWORD CHANGE
    |--------------------------------------------------------------------------
    | User dengan password sementara/default wajib mengganti password sebelum
    | masuk dashboard atau halaman sistem lain.
    |--------------------------------------------------------------------------
    */

    Route::get('/force-change-password', [ForcePasswordChangeController::class, 'edit'])
        ->name('password.force-change');

    Route::put('/force-change-password', [ForcePasswordChangeController::class, 'update'])
        ->middleware('throttle:10,1')
        ->name('password.force-change.update');

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
        ->middleware('throttle:60,1')
        ->name('calendar.events');
    
    Route::get('/export/daily-tasks', [DashboardController::class, 'exportDailyTasks'])
        ->middleware('throttle:10,1')
        ->name('export.daily-tasks');

    /*
    |--------------------------------------------------------------------------
    | SEARCH ROUTES
    |--------------------------------------------------------------------------
    | Search tetap auth supaya endpoint data internal tidak bisa diakses tanpa
    | login. Path tetap sama agar Blade/AJAX lama tidak rusak.
    |--------------------------------------------------------------------------
    */

    Route::get('/iku/search', [IkuController::class, 'search'])
        ->middleware('throttle:60,1')
        ->name('iku.search');

    Route::get('/rk-ketua/search', [RkKetuaController::class, 'search'])
        ->middleware('throttle:60,1')
        ->name('rk_ketua.search');

    Route::get('/project/search', [ProjectController::class, 'search'])
        ->middleware('throttle:60,1')
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
                ->middleware('throttle:10,1')
                ->name('admin.users.import');

            Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])
                ->middleware('throttle:20,1')
                ->name('admin.users.reset');

            Route::resource('team', TeamController::class)
                ->names('admin.team');

            /*
            |--------------------------------------------------------------------------
            | IKU
            |--------------------------------------------------------------------------
            | IKU adalah level paling atas.
            |
            | Flow:
            | IKU -> RK Ketua -> Project -> RK Anggota -> IKI -> Daily Task
            |--------------------------------------------------------------------------
            */

            Route::resource('iku', IkuController::class);

            Route::post('iku/import', [IkuImportController::class, 'import'])
                ->middleware('throttle:10,1')
                ->name('iku.import');

            /*
            |--------------------------------------------------------------------------
            | RK KETUA
            |--------------------------------------------------------------------------
            | RK Ketua adalah turunan IKU dan dasar pembuatan Project.
            |--------------------------------------------------------------------------
            */

            Route::resource('rk-ketua', RkKetuaController::class);

            /*
            |--------------------------------------------------------------------------
            | PROJECT
            |--------------------------------------------------------------------------
            | Export harus diletakkan sebelum resource project agar tidak dianggap
            | sebagai parameter {project}.
            |--------------------------------------------------------------------------
            */

            Route::get('project/export', [ProjectController::class, 'export'])
                ->middleware('throttle:20,1')
                ->name('project.export');

            Route::resource('project', ProjectController::class);

            /*
            |--------------------------------------------------------------------------
            | RK ANGGOTA
            |--------------------------------------------------------------------------
            | RK Anggota sekarang menjadi wadah kerja.
            | Approval utama sudah dipindahkan ke IKI.
            |
            | Route submit/approve/reject RK Anggota tetap dipertahankan sebagai
            | legacy route agar tombol/link lama tidak menimbulkan 404, tetapi
            | controller sudah mengarahkan user untuk memakai IKI.
            |--------------------------------------------------------------------------
            */

            Route::resource('rk-anggota', RkAnggotaController::class)
                ->except(['create', 'edit']);

            Route::patch('rk-anggota/{id}/submit', [RkAnggotaController::class, 'submit'])
                ->whereNumber('id')
                ->middleware('throttle:30,1')
                ->name('admin.rk-anggota.submit');

            Route::patch('rk-anggota/{id}/approve', [RkAnggotaController::class, 'approve'])
                ->whereNumber('id')
                ->middleware('throttle:30,1')
                ->name('admin.rk-anggota.approve');

            Route::patch('rk-anggota/{id}/reject', [RkAnggotaController::class, 'reject'])
                ->whereNumber('id')
                ->middleware('throttle:30,1')
                ->name('admin.rk-anggota.reject');

            /*
            |--------------------------------------------------------------------------
            | IKI
            |--------------------------------------------------------------------------
            | IKI adalah unit approval utama.
            |
            | Admin bisa:
            | - melihat semua IKI
            | - membuat/mengubah/menghapus bila diperlukan
            | - submit/approve/reject untuk kebutuhan administrasi
            |--------------------------------------------------------------------------
            */

            Route::post('iki/bulk-approve', [IkiController::class, 'bulkApprove'])
                ->middleware('throttle:20,1')
                ->name('admin.iki.bulk-approve');

            Route::resource('iki', IkiController::class)
                ->except(['create', 'edit'])
                ->names('admin.iki');

            Route::patch('iki/{id}/submit', [IkiController::class, 'submit'])
                ->whereNumber('id')
                ->middleware('throttle:30,1')
                ->name('admin.iki.submit');

            Route::patch('iki/{id}/approve', [IkiController::class, 'approve'])
                ->whereNumber('id')
                ->middleware('throttle:30,1')
                ->name('admin.iki.approve');

            Route::patch('iki/{id}/reject', [IkiController::class, 'reject'])
                ->whereNumber('id')
                ->middleware('throttle:30,1')
                ->name('admin.iki.reject');

            /*
            |--------------------------------------------------------------------------
            | DAILY TASK
            |--------------------------------------------------------------------------
            | Daily Task berada di bawah IKI sebagai monitoring aktivitas.
            |--------------------------------------------------------------------------
            */

            Route::resource('daily-task', DailyTaskController::class);

            /*
            |--------------------------------------------------------------------------
            | ADMIN EXTRA
            |--------------------------------------------------------------------------
            */

            Route::get('stats', [DashboardController::class, 'stats'])
                ->middleware('throttle:60,1')
                ->name('admin.stats');
        });

    /*
    |--------------------------------------------------------------------------
    | KETUA ROUTES
    |--------------------------------------------------------------------------
    | Flow final Ketua:
    | - Ketua mengelola RK Ketua miliknya sendiri.
    | - Ketua mengelola project yang dia pimpin.
    | - Ketua melihat project yang dia pimpin atau dia ikuti sebagai member.
    | - Ketua melihat RK Anggota dari project yang dia pimpin.
    | - Ketua bisa punya RK Anggota/IKI pribadi lewat mode=mine jika dia menjadi
    |   anggota di project lain.
    | - Approval utama dilakukan di IKI.
    |--------------------------------------------------------------------------
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

            /*
            |--------------------------------------------------------------------------
            | Legacy RK Anggota Review Routes
            |--------------------------------------------------------------------------
            | Dipertahankan agar route lama tidak 404.
            | Controller sudah mengarahkan approval ke IKI.
            |--------------------------------------------------------------------------
            */

            Route::patch('rk-anggota/{id}/submit', [RkAnggotaController::class, 'submit'])
                ->whereNumber('id')
                ->middleware('throttle:30,1')
                ->name('ketua.rk-anggota.submit');

            Route::patch('rk-anggota/{id}/approve', [RkAnggotaController::class, 'approve'])
                ->whereNumber('id')
                ->middleware('throttle:30,1')
                ->name('ketua.rk-anggota.approve');

            Route::patch('rk-anggota/{id}/reject', [RkAnggotaController::class, 'reject'])
                ->whereNumber('id')
                ->middleware('throttle:30,1')
                ->name('ketua.rk-anggota.reject');

            /*
            |--------------------------------------------------------------------------
            | IKI
            |--------------------------------------------------------------------------
            | /ketua/iki
            | - mode normal: Ketua review IKI dari project yang dia pimpin.
            |
            | /ketua/iki?mode=mine
            | - mode pribadi: Ketua mengelola IKI miliknya sendiri saat dia
            |   menjadi anggota di project lain.
            |--------------------------------------------------------------------------
            */

            Route::post('iki/bulk-approve', [IkiController::class, 'bulkApprove'])
                ->middleware('throttle:20,1')
                ->name('ketua.iki.bulk-approve');

            Route::resource('iki', IkiController::class)
                ->except(['create', 'edit'])
                ->names('ketua.iki');

            Route::patch('iki/{id}/submit', [IkiController::class, 'submit'])
                ->whereNumber('id')
                ->middleware('throttle:30,1')
                ->name('ketua.iki.submit');

            Route::patch('iki/{id}/approve', [IkiController::class, 'approve'])
                ->whereNumber('id')
                ->middleware('throttle:30,1')
                ->name('ketua.iki.approve');

            Route::patch('iki/{id}/reject', [IkiController::class, 'reject'])
                ->whereNumber('id')
                ->middleware('throttle:30,1')
                ->name('ketua.iki.reject');

            /*
            |--------------------------------------------------------------------------
            | DAILY TASK
            |--------------------------------------------------------------------------
            | Ketua bisa melihat/mengelola Daily Task sesuai hak akses controller.
            |--------------------------------------------------------------------------
            */

            Route::resource('daily-task', DailyTaskController::class)
                ->except(['create', 'edit']);
        });

    /*
    |--------------------------------------------------------------------------
    | ANGGOTA ROUTES
    |--------------------------------------------------------------------------
    | Flow Anggota:
    | - Anggota melihat project yang dia ikuti.
    | - Anggota membuat RK Anggota miliknya.
    | - Anggota membuat IKI di bawah RK Anggota.
    | - Anggota membuat Daily Task di bawah IKI.
    | - Anggota submit IKI untuk direview Ketua.
    |--------------------------------------------------------------------------
    */

    Route::prefix('anggota')
        ->middleware('role:anggota')
        ->group(function () {

            Route::resource('project', ProjectController::class)
                ->only(['index', 'show'])
                ->names('anggota.project');

            Route::resource('rk-anggota', RkAnggotaController::class)
                ->except(['create', 'edit']);

            /*
            |--------------------------------------------------------------------------
            | Legacy RK Anggota Submit
            |--------------------------------------------------------------------------
            | Dipertahankan agar route lama tidak 404.
            | Submit utama sekarang dilakukan di IKI.
            |--------------------------------------------------------------------------
            */

            Route::patch('rk-anggota/{id}/submit', [RkAnggotaController::class, 'submit'])
                ->whereNumber('id')
                ->middleware('throttle:30,1')
                ->name('anggota.rk-anggota.submit');

            Route::resource('iki', IkiController::class)
                ->except(['create', 'edit'])
                ->names('anggota.iki');

            Route::patch('iki/{id}/submit', [IkiController::class, 'submit'])
                ->whereNumber('id')
                ->middleware('throttle:30,1')
                ->name('anggota.iki.submit');

            Route::resource('daily-task', DailyTaskController::class);
        });

    /*
    |--------------------------------------------------------------------------
    | KEPALA ROUTES
    |--------------------------------------------------------------------------
    | Kepala hanya monitoring/read-only.
    | Tidak ada route store/update/delete/submit/approve/reject.
    |--------------------------------------------------------------------------
    */

    Route::prefix('kepala')
        ->middleware('role:kepala')
        ->name('kepala.')
        ->group(function () {

            /*
            |--------------------------------------------------------------------------
            | IKU MONITORING
            |--------------------------------------------------------------------------
            | Route show diperlukan untuk modal View IKU yang mengambil detail
            | melalui AJAX: /kepala/iku/{id}
            |--------------------------------------------------------------------------
            */

            Route::get('/iku', [IkuController::class, 'index'])
                ->name('iku.index');

            Route::get('/iku/{id}', [IkuController::class, 'show'])
                ->whereNumber('id')
                ->name('iku.show');

            Route::get('/rk-ketua', [RkKetuaController::class, 'index'])
                ->name('rk-ketua.index');

            Route::get('/rk-ketua/{id}', [RkKetuaController::class, 'show'])
                ->whereNumber('id')
                ->name('rk-ketua.show');

            Route::get('/project', [ProjectController::class, 'index'])
                ->name('project.index');

            Route::get('/project/{id}', [ProjectController::class, 'show'])
                ->whereNumber('id')
                ->name('project.show');

            Route::get('/rk-anggota', [RkAnggotaController::class, 'index'])
                ->name('rk-anggota.index');

            Route::get('/rk-anggota/{id}', [RkAnggotaController::class, 'show'])
                ->whereNumber('id')
                ->name('rk-anggota.show');

            Route::get('/iki', [IkiController::class, 'index'])
                ->name('iki.index');

            Route::get('/iki/{id}', [IkiController::class, 'show'])
                ->whereNumber('id')
                ->name('iki.show');

            Route::get('/daily-task', [DailyTaskController::class, 'index'])
                ->name('daily-task.index');

            Route::get('/daily-task/{id}', [DailyTaskController::class, 'show'])
                ->whereNumber('id')
                ->name('daily-task.show');
        });

    /*
    |--------------------------------------------------------------------------
    | AJAX ROUTES
    |--------------------------------------------------------------------------
    */

    Route::get('rk-ketua/{id}/members', [ProjectController::class, 'getMembers'])
        ->whereNumber('id')
        ->middleware('throttle:60,1')
        ->name('rk-ketua.members');

    /*
    |--------------------------------------------------------------------------
    | NOTIFICATION ROUTES
    |--------------------------------------------------------------------------
    | Notifikasi bersifat global untuk semua role yang sudah login.
    |
    | Dipakai untuk:
    | - Icon/badge notifikasi di sidebar/topbar
    | - Halaman daftar notifikasi
    | - Tandai satu notifikasi sebagai dibaca
    | - Tandai semua notifikasi sebagai dibaca
    |--------------------------------------------------------------------------
    */

    Route::prefix('notification')
        ->name('notification.')
        ->group(function () {

            Route::get('/', [NotificationController::class, 'index'])
                ->middleware('throttle:60,1')
                ->name('index');

            Route::get('/unread-count', [NotificationController::class, 'unreadCount'])
                ->middleware('throttle:60,1')
                ->name('unread-count');

            Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])
                ->middleware('throttle:30,1')
                ->name('readAll');

            Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])
                ->whereNumber('id')
                ->middleware('throttle:30,1')
                ->name('read');
        });

    /*
    |--------------------------------------------------------------------------
    | MODE SWITCH
    |--------------------------------------------------------------------------
    */

    Route::post('/switch-mode', function (Request $request) {
        $validated = $request->validate([
            'mode' => ['required', 'string', 'in:normal,mine'],
        ]);

        session(['mode' => $validated['mode']]);

        return back();
    })->middleware('throttle:30,1')->name('switch.mode');
});

require __DIR__ . '/auth.php';