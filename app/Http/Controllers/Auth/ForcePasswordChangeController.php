<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ForcePasswordChangeController extends Controller
{
    public function edit(): View|RedirectResponse
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->is_default_password) {
            return redirect()->route($this->dashboardRouteForRole($user->role));
        }

        return view('auth.force-change-password');
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $request->validate([
            'password' => [
                'required',
                'confirmed',
                Password::min(10)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ], [
            'password.required' => 'Password baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        if (Hash::check($request->password, $user->password)) {
            return back()
                ->withErrors([
                    'password' => 'Password baru tidak boleh sama dengan password sementara.',
                ])
                ->withInput();
        }

        $user->forceFill([
            'password' => Hash::make($request->password),
            'is_default_password' => false,
            'password_changed_at' => now(),
            'remember_token' => Str::random(60),
        ])->save();

        $request->session()->regenerate();

        return redirect()
            ->route($this->dashboardRouteForRole($user->role))
            ->with('success', 'Password berhasil diganti. Kamu sekarang bisa menggunakan sistem.');
    }

    private function dashboardRouteForRole(string $role): string
    {
        return match ($role) {
            'admin' => 'admin.dashboard',
            'kepala', 'kepala_bps' => 'kepala.dashboard',
            'ketua', 'ketua_tim' => 'ketua.dashboard',
            'anggota' => 'anggota.dashboard',
            default => 'login',
        };
    }
}