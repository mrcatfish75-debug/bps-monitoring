<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IkuController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\ProjectController;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/admin', fn() => view('dashboard.admin'));
    Route::get('/kepala', fn() => view('dashboard.kepala'));
    Route::get('/ketua', fn() => view('dashboard.ketua'));
    Route::get('/anggota', fn() => view('dashboard.anggota'));

    // IKU CRUD
    Route::resource('iku', IkuController::class);
    
    Route::resource('project', ProjectController::class);
    
    Route::resource('team', TeamController::class);

});


});

require __DIR__.'/auth.php';
