<?php

namespace App\Http\Controllers;

use App\Models\LogAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function showLogin(): Response|RedirectResponse
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->wajib_ganti_password) {
                return redirect()->route('profil.password');
            }
            if ($user->isAdmin()) {
                return redirect()->route('admin.dashboard');
            } elseif ($user->isGuru()) {
                return redirect()->route('absensi.index');
            } else {
                return redirect()->route('portal-ortu.index');
            }
        }

        return Inertia::render('Auth/Login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => 'Username wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $username = Str::lower($request->input('username'));
        $usernameKey = 'login_user|' . $username;
        $ipKey = 'login_ip|' . $request->ip();

        // Cek RateLimiter: 5 percobaan gagal per 15 menit (900 detik)
        if (RateLimiter::tooManyAttempts($usernameKey, 5) || RateLimiter::tooManyAttempts($ipKey, 5)) {
            $seconds = max(RateLimiter::availableIn($usernameKey), RateLimiter::availableIn($ipKey));
            $minutes = max(1, (int) ceil($seconds / 60));

            LogAudit::catat(null, 'login_terkunci', $request->input('username'), [
                'sisa_detik' => $seconds,
                'sisa_menit' => $minutes,
            ]);

            throw ValidationException::withMessages([
                'username' => "Terlalu banyak percobaan login yang gagal. Akun dikunci sementara. Silakan coba lagi dalam {$minutes} menit.",
            ]);
        }

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($usernameKey, 900);
            RateLimiter::hit($ipKey, 900);

            LogAudit::catat(null, 'login_gagal', $request->input('username'), [
                'percobaan_ke' => RateLimiter::attempts($usernameKey),
            ]);

            throw ValidationException::withMessages([
                'username' => 'Username atau kata sandi yang Anda masukkan salah.',
            ]);
        }

        // Login berhasil, bersihkan limiter
        RateLimiter::clear($usernameKey);
        RateLimiter::clear($ipKey);

        $user = Auth::user();
        if (!$user->aktif) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            LogAudit::catat($user, 'login_ditolak_nonaktif', $user->username);

            throw ValidationException::withMessages([
                'username' => 'Akun Anda telah dinonaktifkan. Silakan hubungi pihak sekolah.',
            ]);
        }

        $request->session()->regenerate();

        LogAudit::catat($user, 'login_berhasil', $user->username);

        // Jika user wajib ganti password (misal akun ortu baru dengan PIN acak)
        if ($user->wajib_ganti_password) {
            return redirect()->route('profil.password')->with('info', 'Demi keamanan data anak Anda, silakan ubah PIN/kata sandi awal Anda terlebih dahulu.');
        }

        if ($user->isAdmin()) {
            return redirect()->intended(route('admin.dashboard'))->with('success', 'Selamat datang, ' . $user->nama);
        } elseif ($user->isGuru()) {
            return redirect()->intended(route('absensi.index'))->with('success', 'Selamat datang, ' . $user->nama);
        } else {
            return redirect()->intended(route('portal-ortu.index'))->with('success', 'Selamat datang, ' . $user->nama);
        }
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if ($user) {
            LogAudit::catat($user, 'logout', $user->username);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('info', 'Anda telah berhasil keluar.');
    }

    public function showPassword(): Response
    {
        return Inertia::render('Auth/ChangePassword');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'current_password.required' => 'Kata sandi saat ini wajib diisi.',
            'current_password.current_password' => 'Kata sandi saat ini tidak cocok.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.min' => 'Kata sandi baru minimal 6 karakter/digit.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);

        $user = $request->user();
        $user->update([
            'password' => Hash::make($request->password),
            'wajib_ganti_password' => false,
        ]);

        // Cabut sesi lain untuk keamanan saat kata sandi diganti
        try {
            Auth::logoutOtherDevices($request->password);
        } catch (\Throwable $e) {
            // Ignore jika session driver tidak mendukung
        }

        LogAudit::catat($user, 'ganti_password', $user->username);

        $pesan = $user->isOrtu() ? 'PIN berhasil diperbarui. Silakan gunakan PIN baru Anda.' : 'Kata sandi berhasil diperbarui.';

        if ($user->isOrtu()) {
            return redirect()->route('portal-ortu.index')->with('success', $pesan);
        }

        return back()->with('success', $pesan);
    }
}
