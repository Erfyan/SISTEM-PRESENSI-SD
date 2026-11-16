<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use App\Exports\DaftarPinOrtuExport;
use App\Exports\TemplateSiswaExport;
use App\Imports\SiswaImportParser;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SiswaController extends Controller
{
    private function checkAuthorization(?Kelas $kelas): void
    {
        $user = Auth::user();
        if (!$kelas) {
            abort(404, 'Data kelas siswa tidak ditemukan.');
        }
        if ($user->isGuru() && (int) $kelas->wali_id !== (int) $user->id) {
            abort(403, 'Anda hanya dapat mengelola siswa di kelas yang Anda ampu.');
        }
    }

    public function index(Request $request): Response
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            $kelasList = Kelas::orderBy('nama')->get();
            $kelasId = $request->input('kelas_id') ?? ($kelasList->first()?->id);
            $kelas = Kelas::with('wali')->find($kelasId);
        } else {
            $kelas = $user->kelasDiampu;
            if (!$kelas) {
                return Inertia::render('Siswa/Index', [
                    'error_message' => 'Anda belum ditugaskan sebagai wali kelas.',
                    'kelasList' => [],
                    'kelas' => null,
                    'siswa' => [],
                ]);
            }
            $kelasList = [$kelas];
            $kelasId = $kelas->id;
        }

        $siswa = [];
        if ($kelas) {
            $siswa = Siswa::where('kelas_id', $kelas->id)
                ->with(['orangTua'])
                ->orderBy('nama')
                ->get()
                ->map(function ($s) {
                    $ortu = $s->orangTua->first();
                    return [
                        'id' => $s->id,
                        'nis' => $s->nis,
                        'nama' => $s->nama,
                        'jenis_kelamin' => $s->jenis_kelamin,
                        'kelas_id' => $s->kelas_id,
                        'nama_ortu' => $s->nama_ortu,
                        'no_wa' => $s->no_wa,
                        'aktif' => $s->aktif,
                        'akun_ortu' => $ortu ? [
                            'id' => $ortu->id,
                            'username' => $ortu->username,
                            'nama' => $ortu->nama,
                            'aktif' => $ortu->aktif,
                        ] : null,
                    ];
                });
        }

        return Inertia::render('Siswa/Index', [
            'kelasList' => $kelasList,
            'kelas' => $kelas,
            'siswa' => $siswa,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kelas_id' => ['required', 'exists:kelas,id'],
            'nis' => ['required', 'string', 'max:20', 'unique:siswa,nis'],
            'nama' => ['required', 'string', 'max:100'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'nama_ortu' => ['required', 'string', 'max:100'],
            'no_wa' => ['required', 'string', 'max:20'],
        ], [
            'nis.unique' => 'NIS ini sudah terdaftar untuk siswa lain.',
            'no_wa.required' => 'Nomor WhatsApp wajib diisi.',
        ]);

        $kelas = Kelas::findOrFail($validated['kelas_id']);
        $this->checkAuthorization($kelas);

        // Standarisasi no_wa ke format internasional 62...
        $noWa = preg_replace('/[^0-9]/', '', $validated['no_wa']);
        if (str_starts_with($noWa, '0')) {
            $noWa = '62' . substr($noWa, 1);
        }
        $validated['no_wa'] = $noWa;
        $validated['aktif'] = true;

        Siswa::create($validated);

        return back()->with('success', "Data siswa {$validated['nama']} berhasil ditambahkan.");
    }

    public function update(Request $request, Siswa $siswa): RedirectResponse
    {
        $kelas = $siswa->kelas;
        $this->checkAuthorization($kelas);

        $validated = $request->validate([
            'nis' => ['required', 'string', 'max:20', 'unique:siswa,nis,' . $siswa->id],
            'nama' => ['required', 'string', 'max:100'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'nama_ortu' => ['required', 'string', 'max:100'],
            'no_wa' => ['required', 'string', 'max:20'],
            'aktif' => ['boolean'],
        ]);

        // Standarisasi no_wa
        $noWa = preg_replace('/[^0-9]/', '', $validated['no_wa']);
        if (str_starts_with($noWa, '0')) {
            $noWa = '62' . substr($noWa, 1);
        }
        $validated['no_wa'] = $noWa;

        $siswa->update($validated);

        return back()->with('success', "Data siswa {$siswa->nama} berhasil diperbarui.");
    }

    public function toggleAktif(Siswa $siswa): RedirectResponse
    {
        $this->checkAuthorization($siswa->kelas);

        $siswa->update(['aktif' => !$siswa->aktif]);

        $status = $siswa->aktif ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Siswa {$siswa->nama} berhasil {$status}.");
    }

    public function generateAkunOrtu(Request $request, Siswa $siswa): RedirectResponse
    {
        $this->checkAuthorization($siswa->kelas);

        // Cek apakah siswa sudah punya akun ortu
        $existingOrtu = $siswa->orangTua()->first();

        // Buat 6 digit PIN acak
        $pin = (string) mt_rand(100000, 999999);

        if ($existingOrtu) {
            // Reset PIN akun yang sudah ada
            $existingOrtu->update([
                'password' => Hash::make($pin),
                'aktif' => true,
                'wajib_ganti_password' => true,
            ]);

            \App\Models\LogAudit::catat(auth()->user(), 'reset_pin', $existingOrtu->username, [
                'siswa_id' => $siswa->id,
                'nama_siswa' => $siswa->nama,
            ]);

            return back()->with([
                'success' => "PIN akun orang tua untuk {$siswa->nama} berhasil direset.",
                'temp_username' => $existingOrtu->username,
                'temp_password' => $pin,
            ]);
        }

        // Buat akun ortu baru dengan username unik
        $baseUsername = 'ortu_' . $siswa->nis;
        $username = $baseUsername;
        $counter = 1;
        while (User::where('username', $username)->exists()) {
            $username = $baseUsername . '_' . $counter;
            $counter++;
        }

        $ortu = User::create([
            'username' => $username,
            'nama' => $siswa->nama_ortu,
            'password' => Hash::make($pin),
            'peran' => 'ortu',
            'aktif' => true,
            'wajib_ganti_password' => true,
        ]);

        $siswa->orangTua()->attach($ortu->id);

        \App\Models\LogAudit::catat(auth()->user(), 'reset_pin', $ortu->username, [
            'siswa_id' => $siswa->id,
            'nama_siswa' => $siswa->nama,
            'keterangan' => 'Pembuatan akun ortu baru',
        ]);

        return back()->with([
            'success' => "Akun orang tua untuk {$siswa->nama} berhasil dibuat.",
            'temp_username' => $username,
            'temp_password' => $pin,
        ]);
    }

    /**
     * Unduh template file Excel untuk impor siswa massal.
     */
    public function downloadTemplate(): BinaryFileResponse
    {
        return Excel::download(new TemplateSiswaExport, 'Template_Impor_Siswa.xlsx');
    }

    /**
     * Upload & Pratinjau data siswa dari file Excel/CSV.
     * Memisahkan baris valid dan baris bermasalah beserta alasannya.
     */
    public function previewImport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kelas_id' => ['required', 'exists:kelas,id'],
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:5120'],
        ]);

        $kelas = Kelas::findOrFail($validated['kelas_id']);
        $this->checkAuthorization($kelas);

        $parsed = SiswaImportParser::parse($request->file('file'));

        return response()->json([
            'success' => true,
            'kelas_id' => $kelas->id,
            'kelas_nama' => $kelas->nama,
            'valid_rows' => $parsed['valid_rows'],
            'invalid_rows' => $parsed['invalid_rows'],
            'total_rows' => $parsed['total_rows'],
        ]);
    }

    /**
     * Konfirmasi dan simpan baris valid hasil impor.
     * Baris salah tidak membatalkan baris benar.
     * Opsi membuat akun ortu otomatis dan mengembalikan daftar PIN awal.
     */
    public function storeImport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kelas_id' => ['required', 'exists:kelas,id'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.nis' => ['required', 'string'],
            'rows.*.nama' => ['required', 'string'],
            'rows.*.jenis_kelamin' => ['required', 'in:L,P'],
            'rows.*.nama_ortu' => ['nullable', 'string'],
            'rows.*.no_wa' => ['nullable', 'string'],
            'buat_akun_ortu' => ['nullable', 'boolean'],
        ]);

        $kelas = Kelas::findOrFail($validated['kelas_id']);
        $this->checkAuthorization($kelas);

        $buatAkunOrtu = (bool) ($validated['buat_akun_ortu'] ?? false);
        $savedCount = 0;
        $akunOrtuCreated = [];

        foreach ($validated['rows'] as $row) {
            // Cek kembali NIS agar aman
            if (Siswa::where('nis', $row['nis'])->exists()) {
                continue;
            }

            $siswa = Siswa::create([
                'kelas_id' => $kelas->id,
                'nis' => $row['nis'],
                'nama' => $row['nama'],
                'jenis_kelamin' => $row['jenis_kelamin'],
                'nama_ortu' => !empty($row['nama_ortu']) ? $row['nama_ortu'] : 'Orang Tua ' . $row['nama'],
                'no_wa' => !empty($row['no_wa']) ? $row['no_wa'] : null,
                'aktif' => true,
            ]);

            $savedCount++;

            // Buat akun orang tua otomatis jika dipilih dan nomor WA tersedia
            if ($buatAkunOrtu && !empty($siswa->no_wa)) {
                $baseUsername = 'ortu_' . $siswa->nis;
                $username = $baseUsername;
                $counter = 1;
                while (User::where('username', $username)->exists()) {
                    $username = $baseUsername . '_' . $counter;
                    $counter++;
                }

                $pin = (string) mt_rand(100000, 999999);

                $ortu = User::create([
                    'username' => $username,
                    'nama' => $siswa->nama_ortu,
                    'password' => Hash::make($pin),
                    'peran' => 'ortu',
                    'aktif' => true,
                    'wajib_ganti_password' => true,
                ]);

                $siswa->orangTua()->attach($ortu->id);

                \App\Models\LogAudit::catat(auth()->user(), 'reset_pin', $username, [
                    'siswa_id' => $siswa->id,
                    'keterangan' => 'Pembuatan akun ortu otomatis via impor Excel',
                ]);

                $akunOrtuCreated[] = [
                    'nis' => $siswa->nis,
                    'nama' => $siswa->nama,
                    'nama_ortu' => $siswa->nama_ortu,
                    'no_wa' => $siswa->no_wa,
                    'username' => $username,
                    'pin' => $pin,
                ];
            }
        }

        // Simpan akun ortu created ke session untuk opsi unduh berikutnya
        if (!empty($akunOrtuCreated)) {
            session(['last_imported_pins_' . $kelas->id => $akunOrtuCreated]);
        }

        return response()->json([
            'success' => true,
            'message' => "Berhasil menyimpan {$savedCount} siswa ke kelas {$kelas->nama}.",
            'saved_count' => $savedCount,
            'akun_ortu' => $akunOrtuCreated,
        ]);
    }

    /**
     * Unduh daftar PIN akun ortu yang baru dibuat dalam format Excel atau PDF.
     */
    public function downloadPin(Request $request, Kelas $kelas)
    {
        $this->checkAuthorization($kelas);

        $format = $request->query('format', 'excel');
        $sessionPins = session('last_imported_pins_' . $kelas->id, []);

        if (empty($sessionPins)) {
            // Jika session kosong, ambil semua siswa kelas ini yang punya akun ortu
            $siswaList = Siswa::where('kelas_id', $kelas->id)->with('orangTua')->get();
            foreach ($siswaList as $s) {
                $ortu = $s->orangTua->first();
                if ($ortu) {
                    $sessionPins[] = [
                        'nis' => $s->nis,
                        'nama' => $s->nama,
                        'nama_ortu' => $s->nama_ortu,
                        'no_wa' => $s->no_wa,
                        'username' => $ortu->username,
                        'pin' => '(Sudah tersimpan)',
                    ];
                }
            }
        }

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('pdf.daftar-pin', [
                'namaKelas' => $kelas->nama,
                'items' => $sessionPins,
            ]);
            return $pdf->download("Daftar_PIN_Ortu_Kelas_{$kelas->nama}.pdf");
        }

        $exportData = array_map(function ($item) {
            return [
                $item['nis'],
                $item['nama'],
                $item['nama_ortu'],
                $item['no_wa'],
                $item['username'],
                $item['pin'],
            ];
        }, $sessionPins);

        return Excel::download(new DaftarPinOrtuExport($exportData, $kelas->nama), "Daftar_PIN_Ortu_Kelas_{$kelas->nama}.xlsx");
    }
}
