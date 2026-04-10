<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }

    protected function authenticated(Request $request, $user)
    {
        if ($user->role == 'admin') {
            return redirect('/admin');
        } elseif ($user->role == 'kepala_bps') {
            return redirect('/kepala');
        } elseif ($user->role == 'ketua_tim') {
            return redirect('/ketua');
        } else {
            return redirect('/anggota');
        }
    }
}
