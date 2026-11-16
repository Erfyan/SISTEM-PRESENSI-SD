<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    /**
     * Pastikan user yang wajib mengganti password diarahkan ke halaman ganti password terlebih dahulu.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->wajib_ganti_password) {
            $allowedRoutes = ['profil.password', 'profil.password.update', 'logout'];

            if (!in_array($request->route()?->getName(), $allowedRoutes)) {
                return redirect()->route('profil.password')->with('warning', 'Demi keamanan, Anda wajib mengganti PIN awal sebelum melanjutkan.');
            }
        }

        return $next($request);
    }
}
