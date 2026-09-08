<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Mapping role parent → semua role yang dianggap satu keluarga.
     * Misal: 'validator' mencakup 'validator', 'validator_btel', 'validator_suel'.
     */
    private const ROLE_GROUPS = [
        'validator' => ['validator', 'validator_btel', 'validator_suel'],
        'alih_media' => ['alih_media', 'alih_media_btel', 'alih_media_suel'],
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login')->with('error', 'Silakan masuk terlebih dahulu.');
        }

        if (!$user->is_active) {
            Auth::logout();
            return redirect()->route('login')->with('error', 'Akun Anda telah dinonaktifkan.');
        }

        // Admin can access everything
        if ($user->role === 'admin') {
            return $next($request);
        }

        // Expand role groups: 'validator' → ['validator', 'validator_btel', 'validator_suel']
        $expandedRoles = [];
        foreach ($roles as $role) {
            $expandedRoles[] = $role;
            if (isset(self::ROLE_GROUPS[$role])) {
                $expandedRoles = array_merge($expandedRoles, self::ROLE_GROUPS[$role]);
            }
        }
        $expandedRoles = array_unique($expandedRoles);

        if (in_array($user->role, $expandedRoles)) {
            return $next($request);
        }

        abort(403, 'Anda tidak memiliki akses ke halaman ini.');
    }
}
