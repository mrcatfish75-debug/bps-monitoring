<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Reset Password Mandiri
        |--------------------------------------------------------------------------
        | Controller ini tetap memakai flow bawaan Laravel:
        | - validasi token
        | - reset password via Password::reset()
        | - hapus token setelah berhasil
        |
        | Tambahan untuk sistem internal:
        | - plain_password dibersihkan
        | - is_default_password dibuat false
        | - password_changed_at diisi
        | - password_reset_at diisi
        | - password_reset_by dibuat null karena reset dilakukan oleh user sendiri
        |--------------------------------------------------------------------------
        */

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'plain_password' => null,
                    'is_default_password' => false,
                    'password_changed_at' => now(),
                    'password_reset_at' => now(),
                    'password_reset_by' => null,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()
                ->route('login')
                ->with('status', 'Password berhasil direset. Silakan login menggunakan password baru.')
            : back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => __($status),
                ]);
    }
}