<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->aktif) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['username' => 'Akun Anda dinonaktifkan. Silakan hubungi admin sekolah.']);
        }

        if (!empty($roles) && !in_array($user->peran, $roles)) {
            // Redirect to appropriate landing page
            if ($user->isAdmin()) {
                return redirect()->route('admin.dashboard')->with('error', 'Anda tidak memiliki hak akses ke halaman tersebut.');
            } elseif ($user->isGuru()) {
                return redirect()->route('absensi.index')->with('error', 'Anda tidak memiliki hak akses ke halaman tersebut.');
            } else {
                return redirect()->route('portal-ortu.index')->with('error', 'Anda tidak memiliki hak akses ke halaman tersebut.');
            }
        }

        return $next($request);
    }
}
