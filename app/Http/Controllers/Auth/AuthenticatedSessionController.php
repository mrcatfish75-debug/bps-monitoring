<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();

        /*
        |--------------------------------------------------------------------------
        | Role-based Redirect
        |--------------------------------------------------------------------------
        | Role final sistem:
        | - admin   -> /admin
        | - kepala  -> /kepala
        | - ketua   -> /ketua
        | - anggota -> /anggota
        |
        | Catatan:
        | kepala_bps dan ketua_tim disupport sementara sebagai fallback
        | jika masih ada data lama di database.
        */
        return match ($user->role) {
            'admin' => redirect()->route('admin.dashboard'),

            'kepala',
            'kepala_bps' => redirect()->route('kepala.dashboard'),

            'ketua',
            'ketua_tim' => redirect()->route('ketua.dashboard'),

            'anggota' => redirect()->route('anggota.dashboard'),

            default => redirect('/'),
        };
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}