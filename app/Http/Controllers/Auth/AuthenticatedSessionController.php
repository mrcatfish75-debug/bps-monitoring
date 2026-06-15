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
        /*
        |--------------------------------------------------------------------------
        | Authenticate User
        |--------------------------------------------------------------------------
        | LoginRequest menangani validasi credential, throttle, reCAPTCHA,
        | dan pesan error.
        |--------------------------------------------------------------------------
        */
        $request->authenticate();

        /*
        |--------------------------------------------------------------------------
        | Session Regeneration
        |--------------------------------------------------------------------------
        | Wajib setelah login untuk mencegah session fixation.
        |--------------------------------------------------------------------------
        */
        $request->session()->regenerate();

        /*
        |--------------------------------------------------------------------------
        | Resolve Authenticated User
        |--------------------------------------------------------------------------
        | Ambil user dari guard web, lalu refresh dari database agar field terbaru
        | seperti is_default_password terbaca akurat setelah reset password.
        |--------------------------------------------------------------------------
        */
        $user = Auth::guard('web')->user();

        if (!$user) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('error', 'Sesi login tidak valid. Silakan login ulang.');
        }

        $user->refresh();

        /*
        |--------------------------------------------------------------------------
        | Role Normalization
        |--------------------------------------------------------------------------
        | Role final:
        | - admin
        | - kepala
        | - ketua
        | - anggota
        |
        | Fallback role lama tetap didukung sementara:
        | - kepala_bps -> kepala
        | - ketua_tim  -> ketua
        |--------------------------------------------------------------------------
        */
        $role = match ($user->role) {
            'admin' => 'admin',
            'kepala', 'kepala_bps' => 'kepala',
            'ketua', 'ketua_tim' => 'ketua',
            'anggota' => 'anggota',
            default => null,
        };

        /*
        |--------------------------------------------------------------------------
        | Invalid Role Guard
        |--------------------------------------------------------------------------
        | Kalau role tidak dikenali, user langsung dilogout.
        |--------------------------------------------------------------------------
        */
        if (!$role) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('error', 'Akun kamu belum memiliki role yang valid. Hubungi Admin.');
        }

        /*
        |--------------------------------------------------------------------------
        | Force Password Change
        |--------------------------------------------------------------------------
        | User dengan password sementara/default hasil create/reset Admin wajib
        | mengganti password sebelum masuk dashboard.
        |--------------------------------------------------------------------------
        */
        if ((int) $user->is_default_password === 1) {
            /*
            | Hapus intended URL lama agar tidak ada redirect ke dashboard
            | sebelum user mengganti password.
            */
            $request->session()->forget('url.intended');

            return redirect()
                ->route('password.force-change')
                ->with('error', 'Kamu menggunakan password sementara. Silakan ganti password terlebih dahulu.');
        }

        /*
        |--------------------------------------------------------------------------
        | Role-based Redirect
        |--------------------------------------------------------------------------
        | Semua role diarahkan ke dashboard masing-masing.
        |--------------------------------------------------------------------------
        */
        return redirect()->route($this->dashboardRouteForRole($role));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('success', 'Kamu berhasil logout.');
    }

    /**
     * Get dashboard route by normalized role.
     */
    private function dashboardRouteForRole(string $role): string
    {
        return match ($role) {
            'admin' => 'admin.dashboard',
            'kepala' => 'kepala.dashboard',
            'ketua' => 'ketua.dashboard',
            'anggota' => 'anggota.dashboard',
            default => 'login',
        };
    }
}