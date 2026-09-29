<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * Memeriksa apakah pengguna yang sedang login memiliki peran yang diizinkan.
     * Mendukung multi-role: role:admin,dosen
     *
     * 401 → belum login sama sekali
     * 403 → sudah login tapi perannya tidak sesuai
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Belum login sama sekali → 401 Unauthorized
        if (! $request->user()) {
            abort(401);
        }

        // Sudah login tapi peran tidak cocok → 403 Forbidden
        if (! in_array($request->user()->role, $roles)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
