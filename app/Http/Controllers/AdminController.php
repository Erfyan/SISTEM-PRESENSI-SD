<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    // Overview Dashboard Admin (A3)
    public function dashboard(): Response
    {
        $today = Carbon::today()->format('Y-m-d');

        $totalKelas = Kelas::count();
        $totalSiswa = Siswa::where('aktif', true)->count();
        $totalGuru = User::where('peran', 'guru')->where('aktif', true)->count();

        // Absensi hari ini per kelas
        $kelasList = Kelas::with(['wali', 'siswa' => fn ($q) => $q->where('aktif', true)])
            ->orderBy('nama')
            ->get()
            ->map(function ($k) use ($today) {
                $siswaIds = $k->siswa->pluck('id');
                $totalSiswa = $siswaIds->count();

                $absensiHariIni = Absensi::whereIn('siswa_id', $siswaIds)
                    ->whereDate('tanggal', $today)
                    ->get();

                $terisi = $absensiHariIni->isNotEmpty();
                $hadir = $absensiHariIni->where('status', 'H')->count();
                $izin = $absensiHariIni->where('status', 'I')->count();
                $sakit = $absensiHariIni->where('status', 'S')->count();
                $alpa = $absensiHariIni->where('status', 'A')->count();

                return [
                    'id' => $k->id,
                    'nama' => $k->nama,
                    'tahun_ajaran' => $k->tahun_ajaran,
                    'wali_nama' => $k->wali?->nama ?? 'Belum ada',
                    'total_siswa' => $totalSiswa,
                    'terisi' => $terisi,
                    'hadir' => $hadir,
                    'izin' => $izin,
                    'sakit' => $sakit,
                    'alpa' => $alpa,
                ];
            });

        return Inertia::render('Admin/Dashboard', [
            'tanggal' => Carbon::parse($today)->locale('id')->isoFormat('dddd, D MMMM Y'),
            'totalKelas' => $totalKelas,
            'totalSiswa' => $totalSiswa,
            'totalGuru' => $totalGuru,
            'kelasList' => $kelasList,
        ]);
    }

    // Kelola Kelas (A1)
    public function indexKelas(): Response
    {
        $kelas = Kelas::with(['wali', 'siswa' => fn ($q) => $q->where('aktif', true)])
            ->orderBy('nama')
            ->get()
            ->map(fn ($k) => [
                'id' => $k->id,
                'nama' => $k->nama,
                'tahun_ajaran' => $k->tahun_ajaran,
                'wali_id' => $k->wali_id,
                'wali_nama' => $k->wali?->nama ?? 'Belum ada wali kelas',
                'total_siswa' => $k->siswa->count(),
            ]);

        $guruList = User::where('peran', 'guru')
            ->where('aktif', true)
            ->orderBy('nama')
            ->get(['id', 'nama', 'username']);

        return Inertia::render('Admin/Kelas', [
            'kelas' => $kelas,
            'guruList' => $guruList,
        ]);
    }

    public function storeKelas(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:50'],
            'tahun_ajaran' => ['required', 'string', 'max:20'],
            'wali_id' => ['nullable', 'exists:users,id'],
        ], [
            'nama.required' => 'Nama kelas wajib diisi.',
            'tahun_ajaran.required' => 'Tahun ajaran wajib diisi.',
        ]);

        Kelas::create($validated);

        return back()->with('success', "Kelas {$validated['nama']} berhasil dibuat.");
    }

    public function updateKelas(Request $request, Kelas $kelas): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:50'],
            'tahun_ajaran' => ['required', 'string', 'max:20'],
            'wali_id' => ['nullable', 'exists:users,id'],
        ]);

        $kelas->update($validated);

        return back()->with('success', "Data kelas {$kelas->nama} berhasil diperbarui.");
    }

    public function destroyKelas(Kelas $kelas): RedirectResponse
    {
        if ($kelas->siswa()->exists()) {
            return back()->with('error', 'Kelas ini masih memiliki data siswa. Pindahkan siswa terlebih dahulu.');
        }

        $nama = $kelas->nama;
        $kelas->delete();

        return back()->with('success', "Kelas {$nama} berhasil dihapus.");
    }

    // Kelola Akun Guru (A2)
    public function indexGuru(): Response
    {
        $guru = User::where('peran', 'guru')
            ->with('kelasDiampu')
            ->orderBy('nama')
            ->get()
            ->map(fn ($g) => [
                'id' => $g->id,
                'username' => $g->username,
                'nama' => $g->nama,
                'aktif' => $g->aktif,
                'kelas_nama' => $g->kelasDiampu?->nama ?? '-',
            ]);

        return Inertia::render('Admin/Guru', [
            'guru' => $guru,
        ]);
    }

    public function storeGuru(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username'],
            'password' => ['required', 'string', 'min:6'],
        ], [
            'username.unique' => 'Username ini sudah digunakan.',
            'password.min' => 'Kata sandi minimal 6 karakter.',
        ]);

        User::create([
            'nama' => $validated['nama'],
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'peran' => 'guru',
            'aktif' => true,
        ]);

        return back()->with('success', "Akun guru {$validated['nama']} berhasil dibuat.");
    }

    public function updateGuru(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username,' . $user->id],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $data = [
            'nama' => $validated['nama'],
            'username' => $validated['username'],
        ];

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        return back()->with('success', "Data guru {$user->nama} berhasil diperbarui.");
    }

    public function toggleAktifGuru(User $user): RedirectResponse
    {
        if ($user->isAdmin()) {
            abort(403, 'Tidak dapat menonaktifkan akun admin.');
        }

        $user->update(['aktif' => !$user->aktif]);
        $status = $user->aktif ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Akun guru {$user->nama} berhasil {$status}.");
    }

    /**
     * Halaman Status Sistem & Manajemen Cadangan Database
     */
    public function statusSistem(): Response
    {
        $dbPath = config('database.connections.sqlite.database');
        $dbSize = \Illuminate\Support\Facades\File::exists($dbPath) ? \Illuminate\Support\Facades\File::size($dbPath) : 0;
        $dbSizeFormatted = $dbSize >= 1048576 
            ? round($dbSize / 1048576, 2) . ' MB' 
            : round($dbSize / 1024, 2) . ' KB';

        // Informasi cadangan
        $backupDir = storage_path('app/backups');
        $backupFiles = [];
        if (\Illuminate\Support\Facades\File::exists($backupDir)) {
            $files = collect(\Illuminate\Support\Facades\File::files($backupDir))
                ->filter(fn ($f) => $f->getExtension() === 'sqlite')
                ->sortByDesc(fn ($f) => $f->getMTime());

            foreach ($files as $f) {
                $backupFiles[] = [
                    'nama' => $f->getFilename(),
                    'ukuran' => round($f->getSize() / 1024, 2) . ' KB',
                    'waktu' => Carbon::createFromTimestamp($f->getMTime())->locale('id')->isoFormat('D MMMM Y, HH:mm:ss'),
                ];
            }
        }

        $terakhirCadanganAt = \App\Models\Pengaturan::get('terakhir_cadangan_at');
        if ($terakhirCadanganAt) {
            $terakhirCadanganFormatted = Carbon::parse($terakhirCadanganAt)->locale('id')->isoFormat('D MMMM Y, HH:mm:ss');
        } elseif (!empty($backupFiles)) {
            $terakhirCadanganFormatted = $backupFiles[0]['waktu'];
        } else {
            $terakhirCadanganFormatted = 'Belum pernah dicadangkan';
        }

        // Mode SQLite WAL dari konfigurasi
        $walMode = strtoupper(config('database.connections.sqlite.journal_mode', 'WAL'));

        return Inertia::render('Admin/StatusSistem', [
            'status' => [
                'versi_aplikasi' => 'v1.2.0 (Sipreline PWA Offline-Ready)',
                'versi_laravel' => app()->version(),
                'versi_php' => PHP_VERSION,
                'db_driver' => config('database.default'),
                'db_path' => basename($dbPath),
                'db_size' => $dbSizeFormatted,
                'wal_mode' => $walMode,
                'busy_timeout' => config('database.connections.sqlite.busy_timeout', 5000) . ' ms',
                'terakhir_cadangan' => $terakhirCadanganFormatted,
                'total_cadangan' => count($backupFiles),
            ],
            'daftarCadangan' => array_slice($backupFiles, 0, 14),
        ]);
    }

    /**
     * Trigger pencadangan manual dari UI admin
     */
    public function triggerBackup(): RedirectResponse
    {
        try {
            \Illuminate\Support\Facades\Artisan::call('app:backup-db');
            return back()->with('success', 'Cadangan database berhasil dibuat.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal membuat cadangan database: ' . $e->getMessage());
        }
    }
}
