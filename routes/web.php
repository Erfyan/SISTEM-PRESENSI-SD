<?php

use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HariLiburController;
use App\Http\Controllers\PortalOrtuController;
use App\Http\Controllers\RekapController;
use App\Http\Controllers\SiswaController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Redirect Root
Route::get('/', function () {
    if (Auth::check()) {
        $user = Auth::user();
        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        } elseif ($user->isGuru()) {
            return redirect()->route('absensi.index');
        } else {
            return redirect()->route('portal-ortu.index');
        }
    }
    return redirect()->route('login');
});

Route::get('/favicon.ico', fn () => response()->file(public_path('icons/icon-192x192.png')));
Route::get('/favicon.png', fn () => response()->file(public_path('icons/icon-192x192.png')));

// Route pengujian koneksi database (bisa dibuka langsung di browser)
Route::get('/test-db', function () {
    try {
        $dbDriver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
        $usersCount = \App\Models\User::count();
        $kelasCount = \App\Models\Kelas::count();
        $siswaCount = \App\Models\Siswa::count();
        $adminUser = \App\Models\User::where('peran', 'admin')->first(['id', 'username', 'nama', 'peran']);

        return response()->json([
            'status' => 'success',
            'pesan' => 'Koneksi ke database Supabase BERHASIL!',
            'driver' => $dbDriver,
            'data' => [
                'total_users' => $usersCount,
                'total_kelas' => $kelasCount,
                'total_siswa' => $siswaCount,
                'admin' => $adminUser,
            ],
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'status' => 'error',
            'pesan' => 'Gagal terhubung ke database: ' . $e->getMessage(),
        ], 500);
    }
});

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/profil/password', [AuthController::class, 'showPassword'])->name('profil.password');
    Route::put('/profil/password', [AuthController::class, 'updatePassword'])->name('profil.password.update');

    // Role: Khusus Guru (Absensi Harian & Komunikasi WA)
    Route::middleware('role:guru')->group(function () {
        Route::get('/absensi', [AbsensiController::class, 'index'])->name('absensi.index');
        Route::post('/absensi', [AbsensiController::class, 'store'])->name('absensi.store');
        Route::post('/absensi/sync', [AbsensiController::class, 'sync'])->name('absensi.sync');
        Route::post('/absensi/{absensi}/tandai-wa-terkirim', [AbsensiController::class, 'tandaiWaTerkirim'])->name('absensi.tandai-wa');
        Route::post('/profil/template-wa', [AbsensiController::class, 'updateTemplateWa'])->name('profil.template-wa');
    });

    // Role: Guru & Admin (Rekap, Kalender Libur, Manajemen Siswa)
    Route::middleware('role:guru,admin')->group(function () {
        Route::get('/rekap', [RekapController::class, 'index'])->name('rekap.index');
        Route::get('/rekap/export/excel', [RekapController::class, 'exportExcel'])->name('rekap.export.excel');
        Route::get('/rekap/export/pdf', [RekapController::class, 'exportPdf'])->name('rekap.export.pdf');

        Route::get('/kalender-libur', [HariLiburController::class, 'index'])->name('kalender-libur.index');
        Route::post('/kalender-libur', [HariLiburController::class, 'store'])->name('kalender-libur.store');
        Route::delete('/kalender-libur/{hariLibur}', [HariLiburController::class, 'destroy'])->name('kalender-libur.destroy');
        Route::post('/kalender-libur/pengaturan-jam', [HariLiburController::class, 'updateJamBatas'])->name('kalender-libur.update-jam');

        Route::get('/siswa', [SiswaController::class, 'index'])->name('siswa.index');
        Route::get('/siswa/import/template', [SiswaController::class, 'downloadTemplate'])->name('siswa.import.template');
        Route::post('/siswa/import/preview', [SiswaController::class, 'previewImport'])->name('siswa.import.preview');
        Route::post('/siswa/import/store', [SiswaController::class, 'storeImport'])->name('siswa.import.store');
        Route::get('/siswa/kelas/{kelas}/download-pin', [SiswaController::class, 'downloadPin'])->name('siswa.download-pin');
        Route::post('/siswa', [SiswaController::class, 'store'])->name('siswa.store');
        Route::put('/siswa/{siswa}', [SiswaController::class, 'update'])->name('siswa.update');
        Route::patch('/siswa/{siswa}/toggle-aktif', [SiswaController::class, 'toggleAktif'])->name('siswa.toggle-aktif');
        Route::post('/siswa/{siswa}/generate-akun-ortu', [SiswaController::class, 'generateAkunOrtu'])->name('siswa.generate-akun-ortu');
    });

    // Role: Orang Tua
    Route::middleware('role:ortu')->group(function () {
        Route::get('/portal-ortu', [PortalOrtuController::class, 'index'])->name('portal-ortu.index');
    });

    // Role: Admin
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

        Route::get('/kelas', [AdminController::class, 'indexKelas'])->name('kelas.index');
        Route::post('/kelas', [AdminController::class, 'storeKelas'])->name('kelas.store');
        Route::put('/kelas/{kelas}', [AdminController::class, 'updateKelas'])->name('kelas.update');
        Route::delete('/kelas/{kelas}', [AdminController::class, 'destroyKelas'])->name('kelas.destroy');

        Route::get('/guru', [AdminController::class, 'indexGuru'])->name('guru.index');
        Route::post('/guru', [AdminController::class, 'storeGuru'])->name('guru.store');
        Route::put('/guru/{user}', [AdminController::class, 'updateGuru'])->name('guru.update');
        Route::patch('/guru/{user}/toggle-aktif', [AdminController::class, 'toggleAktifGuru'])->name('guru.toggle-aktif');

        Route::get('/status-sistem', [AdminController::class, 'statusSistem'])->name('status-sistem');
        Route::post('/status-sistem/backup', [AdminController::class, 'triggerBackup'])->name('backup.trigger');
    });
});
