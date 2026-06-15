<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guest Routes
|--------------------------------------------------------------------------
| Route khusus user yang belum login.
|
| Catatan project:
| - BPS Monitoring adalah sistem internal.
| - Register publik tidak dibuka.
| - User dibuat oleh Admin melalui menu Users / Import.
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | REGISTER - DISABLED FOR PUBLIC
    |--------------------------------------------------------------------------
    | Register publik dimatikan supaya user/role tidak dibuat sembarangan.
    | Jika butuh membuat user baru, gunakan fitur Admin > Users.
    |--------------------------------------------------------------------------
    */

    Route::get('register', function () {
        return redirect()
            ->route('login')
            ->with('error', 'Registrasi publik tidak tersedia. Akun dibuat oleh Admin.');
    })
        ->middleware('throttle:10,1')
        ->name('register');

    Route::post('register', function () {
        return redirect()
            ->route('login')
            ->with('error', 'Registrasi publik tidak tersedia. Akun dibuat oleh Admin.');
    })
        ->middleware('throttle:10,1')
        ->name('register.store');

    /*
    |--------------------------------------------------------------------------
    | LOGIN
    |--------------------------------------------------------------------------
    | Rate limit tambahan:
    | - Maksimal 5 request login per menit per client.
    | - Controller AuthenticatedSessionController tetap boleh punya limiter sendiri.
    |--------------------------------------------------------------------------
    */

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:5,1');

    /*
    |--------------------------------------------------------------------------
    | FORGOT PASSWORD
    |--------------------------------------------------------------------------
    | Tetap dibuka agar user bisa reset password.
    | Diberi throttle agar tidak disalahgunakan untuk spam email reset.
    |--------------------------------------------------------------------------
    */

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:3,1')
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.store');
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
| Route khusus user yang sudah login.
|
| - Email verification disiapkan jika nanti diperlukan.
| - Confirm password tetap aman untuk aksi sensitif.
| - Update password hanya untuk user login.
| - Logout wajib POST.
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | EMAIL VERIFICATION
    |--------------------------------------------------------------------------
    | Jika project tidak memakai email verification, route ini tidak mengganggu.
    | Kalau nanti ingin wajib verifikasi email, tambahkan middleware 'verified'
    | di route area aplikasi utama.
    |--------------------------------------------------------------------------
    */

    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:3,1')
        ->name('verification.send');

    /*
    |--------------------------------------------------------------------------
    | CONFIRM PASSWORD
    |--------------------------------------------------------------------------
    */

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store'])
        ->middleware('throttle:5,1');

    /*
    |--------------------------------------------------------------------------
    | UPDATE PASSWORD
    |--------------------------------------------------------------------------
    */

    Route::put('password', [PasswordController::class, 'update'])
        ->middleware('throttle:5,1')
        ->name('password.update');

    /*
    |--------------------------------------------------------------------------
    | LOGOUT
    |--------------------------------------------------------------------------
    | Logout tetap POST supaya tidak bisa dipicu sembarang link GET.
    |--------------------------------------------------------------------------
    */

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->middleware('throttle:20,1')
        ->name('logout');
});