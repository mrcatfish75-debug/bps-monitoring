<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
public function handle($request, Closure $next, ...$roles)
{
    if (!auth()->check()) {
        abort(403);
    }

    $userRole = auth()->user()->role;

    $roleMap = [
        'admin' => ['admin'],
        'ketua' => ['ketua', 'ketua_tim'],
        'kepala' => ['kepala', 'kepala_bps'],
        'anggota' => ['anggota'],
    ];

    foreach ($roles as $role) {
        if (in_array($userRole, $roleMap[$role] ?? [])) {
            return $next($request);
        }
    }

    abort(403, 'Akses Ditolak');
}
}