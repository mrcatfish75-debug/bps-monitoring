<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        $allowedRoutes = [
            'password.force-change',
            'password.force-change.update',
            'logout',
        ];

        if (
            $user->is_default_password
            && !$request->routeIs($allowedRoutes)
        ) {
            return redirect()
                ->route('password.force-change')
                ->with('error', 'Kamu wajib mengganti password sementara sebelum melanjutkan.');
        }

        return $next($request);
    }
}