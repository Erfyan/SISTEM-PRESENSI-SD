<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\HariLibur;
use App\Models\Kelas;
use App\Models\Pengaturan;
use App\Models\Siswa;
use App\Services\WhatsApp\WhatsAppSenderInterface;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AbsensiController extends Controller
{
    public function __construct(
        protected WhatsAppSenderInterface $waSender
    ) {
    }

    public function index(Request $request): Response|RedirectResponse
    {
        $user = Auth::user();

        // Otorisasi ketat: Admin tidak boleh mengakses pengisian absensi harian
        if (!$user->isGuru()) {
            abort(403, 'Akses ditolak. Pengisian absensi harian khusus untuk guru kelas.');
        }

        $kelas = $user->kelasDiampu;
        $requestedKelasId = $request->input('kelas_id');
        if ($requestedKelasId && $kelas && (int) $requestedKelasId !== (int) $kelas->id) {
            abort(403, 'Anda tidak memiliki hak akses ke absensi kelas lain.');
        }

        if (!$kelas) {
            return Inertia::render('Absensi/Index', [
                'error_message' => 'Anda belum ditugaskan sebagai wali kelas. Silakan hubungi admin sekolah.',
                'kelasList' => [],
                'kelas' => null,
                'tanggal' => Carbon::today()->format('Y-m-d'),
                'siswa' => [],
                'sudahDisimpan' => false,
                'antreanWa' => [],
                'templateWa' => $this->waSender->getDefaultTemplate(),
                'defaultTemplateWa' => $this->waSender->getDefaultTemplate(),
                'waStats' => ['belum_dihubungi' => 0, 'sudah_dikirim' => 0, 'persentase' => 100],
            ]);
        }
        $kelasList = [$kelas];
        $kelasId = $kelas->id;

        $tanggal = $request->input('tanggal', Carbon::today()->format('Y-m-d'));

        $siswa = [];
        $antreanWa = [];
        $sudahDisimpan = false;

        $templateWa = !empty($user->template_wa) ? $user->template_wa : $this->waSender->getDefaultTemplate();

        $waStats = [
            'belum_dihubungi' => 0,
            'sudah_dikirim' => 0,
            'total_tidak_hadir' => 0,
            'persentase' => 100,
        ];

        if ($kelas) {
            $daftarSiswa = Siswa::where('kelas_id', $kelas->id)
                ->where('aktif', true)
                ->orderBy('nama')
                ->get();

            $absensiHariIni = Absensi::whereIn('siswa_id', $daftarSiswa->pluck('id'))
                ->whereDate('tanggal', $tanggal)
                ->get()
                ->keyBy('siswa_id');

            $sudahDisimpan = $absensiHariIni->isNotEmpty();

            // Format tanggal Indonesia untuk pesan WA
            $carbonTanggal = Carbon::parse($tanggal)->locale('id');
            $tanggalIndo = $carbonTanggal->isoFormat('D MMMM Y');

            $namaGuru = $kelas->wali ? $kelas->wali->nama : ($user->nama ?? 'Wali Kelas');

            $siswa = $daftarSiswa->map(function ($s) use ($absensiHariIni, $kelas, $tanggalIndo, $namaGuru, $templateWa, &$antreanWa, &$waStats) {
                $absen = $absensiHariIni->get($s->id);
                $status = $absen ? $absen->status : 'H';
                $catatan = $absen ? $absen->catatan : '';
                $waStatus = $absen ? ($absen->wa_status ?? 'belum_dihubungi') : 'belum_dihubungi';
                $waDikirimPada = $absen?->wa_dikirim_pada ? $absen->wa_dikirim_pada->format('H:i') : null;

                $statusTeks = match ($status) {
                    'I' => 'Izin',
                    'S' => 'Sakit',
                    'A' => 'Alpa (Tanpa Keterangan)',
                    default => 'Hadir'
                };

                $waLink = null;
                $formattedMessage = '';

                if ($status !== 'H') {
                    $waStats['total_tidak_hadir']++;
                    if ($waStatus === 'sudah_dikirim') {
                        $waStats['sudah_dikirim']++;
                    } else {
                        $waStats['belum_dihubungi']++;
                    }

                    if (!empty($s->no_wa)) {
                        $formattedMessage = $this->waSender->formatMessage($templateWa, [
                            'nama_ortu' => $s->nama_ortu,
                            'nama_siswa' => $s->nama,
                            'nama_kelas' => $kelas->nama,
                            'tanggal' => $tanggalIndo,
                            'status' => $statusTeks,
                            'catatan' => $catatan,
                            'nama_guru' => $namaGuru,
                        ]);

                        $waLink = $this->waSender->generateUrl($s->no_wa, $formattedMessage);

                        $antreanWa[] = [
                            'absensi_id' => $absen?->id,
                            'siswa_id' => $s->id,
                            'nama' => $s->nama,
                            'nama_ortu' => $s->nama_ortu,
                            'no_wa' => $s->no_wa,
                            'status' => $status,
                            'status_teks' => $statusTeks,
                            'catatan' => $catatan ?? '',
                            'wa_status' => $waStatus,
                            'wa_dikirim_pada' => $waDikirimPada,
                            'pesan' => $formattedMessage,
                            'wa_link' => $waLink,
                        ];
                    }
                }

                return [
                    'id' => $s->id,
                    'absensi_id' => $absen?->id,
                    'nis' => $s->nis,
                    'nama' => $s->nama,
                    'jenis_kelamin' => $s->jenis_kelamin,
                    'nama_ortu' => $s->nama_ortu,
                    'no_wa' => $s->no_wa,
                    'status' => $status,
                    'catatan' => $catatan ?? '',
                    'wa_status' => $waStatus,
                    'wa_dikirim_pada' => $waDikirimPada,
                    'wa_link' => $waLink,
                    'status_teks' => $statusTeks,
                ];
            });

            if ($waStats['total_tidak_hadir'] > 0) {
                $waStats['persentase'] = round(($waStats['sudah_dikirim'] / $waStats['total_tidak_hadir']) * 100);
            }
        }

        // Evaluasi pengingat absen kosong
        $peringatanAbsenKosong = null;
        $isHariIni = ($tanggal === Carbon::today()->format('Y-m-d'));
        if ($kelas && $isHariIni && Carbon::today()->isWeekday() && !$sudahDisimpan) {
            $isLiburHariIni = HariLibur::forKelas($kelas->id)->whereDate('tanggal', $tanggal)->exists();
            if (!$isLiburHariIni) {
                $jamBatas = Pengaturan::get('jam_batas_absen', '09:00');
                if (Carbon::now()->format('H:i') >= $jamBatas) {
                    $peringatanAbsenKosong = [
                        'tampilkan' => true,
                        'jam_batas' => $jamBatas,
                        'pesan' => "Peringatan: Absen hari ini belum diisi setelah pukul {$jamBatas}.",
                    ];
                }
            }
        }

        return Inertia::render('Absensi/Index', [
            'kelasList' => $kelasList,
            'kelas' => $kelas,
            'tanggal' => $tanggal,
            'siswa' => $siswa,
            'sudahDisimpan' => $sudahDisimpan,
            'antreanWa' => $antreanWa,
            'templateWa' => $templateWa,
            'defaultTemplateWa' => $this->waSender->getDefaultTemplate(),
            'waStats' => $waStats,
            'peringatanAbsenKosong' => $peringatanAbsenKosong,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kelas_id' => ['required', 'exists:kelas,id'],
            'tanggal' => ['required', 'date'],
            'absensi' => ['required', 'array'],
            'absensi.*.siswa_id' => ['required', 'exists:siswa,id'],
            'absensi.*.status' => ['required', 'in:H,I,S,A'],
            'absensi.*.catatan' => ['nullable', 'string', 'max:255'],
            'absensi.*.dicatat_offline_pada' => ['nullable', 'date'],
        ]);

        $user = Auth::user();
        if (!$user->isGuru()) {
            abort(403, 'Akses ditolak. Pengisian absensi harian khusus untuk guru kelas.');
        }

        $kelas = Kelas::findOrFail($validated['kelas_id']);

        // Otorisasi: Guru hanya boleh simpan kelasnya sendiri
        if ($kelas->wali_id !== $user->id) {
            abort(403, 'Anda tidak berwenang mengisi absensi kelas ini.');
        }

        $now = Carbon::now();

        foreach ($validated['absensi'] as $item) {
            Absensi::updateOrCreate(
                [
                    'siswa_id' => $item['siswa_id'],
                    'tanggal' => $validated['tanggal'],
                ],
                [
                    'status' => $item['status'],
                    'catatan' => $item['catatan'] ?? null,
                    'dicatat_oleh' => $user->id,
                    'dicatat_pada' => $now,
                    'dicatat_offline_pada' => !empty($item['dicatat_offline_pada']) ? Carbon::parse($item['dicatat_offline_pada']) : null,
                ]
            );
        }

        return redirect()->route('absensi.index', [
            'kelas_id' => $kelas->id,
            'tanggal' => $validated['tanggal'],
        ])->with('success', 'Data absensi tanggal ' . Carbon::parse($validated['tanggal'])->format('d/m/Y') . ' berhasil disimpan.');
    }

    /**
     * Endpoint untuk sinkronisasi absensi yang diisi offline.
     * Idempotent & mendeteksi konflik jika server sudah punya data berbeda.
     */
    public function sync(Request $request)
    {
        $validated = $request->validate([
            'kelas_id' => ['required', 'exists:kelas,id'],
            'tanggal' => ['required', 'date'],
            'absensi' => ['required', 'array'],
            'absensi.*.siswa_id' => ['required', 'exists:siswa,id'],
            'absensi.*.status' => ['required', 'in:H,I,S,A'],
            'absensi.*.catatan' => ['nullable', 'string', 'max:255'],
            'absensi.*.dicatat_offline_pada' => ['nullable', 'date'],
            'resolve_conflicts' => ['nullable'], // 'offline', 'server', atau array mapping [siswa_id => 'offline'|'server']
        ]);

        $user = Auth::user();
        if (!$user->isGuru()) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Pengisian absensi harian khusus untuk guru kelas.',
            ], 403);
        }

        $kelas = Kelas::findOrFail($validated['kelas_id']);

        if ($kelas->wali_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak berwenang mengisi absensi kelas ini.',
            ], 403);
        }

        $siswaIds = collect($validated['absensi'])->pluck('siswa_id');
        $existingRecords = Absensi::with('siswa')
            ->whereIn('siswa_id', $siswaIds)
            ->whereDate('tanggal', $validated['tanggal'])
            ->get()
            ->keyBy('siswa_id');

        $resolutions = $validated['resolve_conflicts'] ?? null;
        $conflicts = [];

        // Deteksi konflik bila server sudah punya record yang berbeda dan belum ada resolusi
        if (empty($resolutions)) {
            foreach ($validated['absensi'] as $item) {
                $serverAbsen = $existingRecords->get($item['siswa_id']);
                if ($serverAbsen) {
                    $itemCatatan = $item['catatan'] ?? '';
                    $serverCatatan = $serverAbsen->catatan ?? '';
                    // Terjadi konflik jika status atau catatan berbeda
                    if ($serverAbsen->status !== $item['status'] || $serverCatatan !== $itemCatatan) {
                        $conflicts[] = [
                            'siswa_id' => $item['siswa_id'],
                            'nama' => $serverAbsen->siswa ? $serverAbsen->siswa->nama : 'Siswa #' . $item['siswa_id'],
                            'server_status' => $serverAbsen->status,
                            'server_catatan' => $serverAbsen->catatan,
                            'server_dicatat_pada' => $serverAbsen->dicatat_pada?->format('Y-m-d H:i:s'),
                            'offline_status' => $item['status'],
                            'offline_catatan' => $item['catatan'] ?? null,
                            'dicatat_offline_pada' => $item['dicatat_offline_pada'] ?? null,
                        ];
                    }
                }
            }

            if (!empty($conflicts)) {
                return response()->json([
                    'conflict' => true,
                    'message' => 'Terdapat ' . count($conflicts) . ' data absensi di server yang berbeda dengan data offline. Silakan pilih versi yang akan disimpan.',
                    'conflicts' => $conflicts,
                ], 409);
            }
        }

        // Terapkan penyimpanan
        $now = Carbon::now();
        $syncedCount = 0;

        foreach ($validated['absensi'] as $item) {
            $siswaId = $item['siswa_id'];
            $serverAbsen = $existingRecords->get($siswaId);

            // Tentukan pilihan resolusi jika ada konflik sebelumnya
            $choice = 'offline';
            if (is_string($resolutions)) {
                $choice = $resolutions; // 'offline' atau 'server'
            } elseif (is_array($resolutions) && isset($resolutions[$siswaId])) {
                $choice = $resolutions[$siswaId];
            }

            // Jika pilih server dan server sudah punya record, biarkan record server
            if ($choice === 'server' && $serverAbsen) {
                $syncedCount++;
                continue;
            }

            // Simpan / update versi offline secara idempotent
            Absensi::updateOrCreate(
                [
                    'siswa_id' => $siswaId,
                    'tanggal' => $validated['tanggal'],
                ],
                [
                    'status' => $item['status'],
                    'catatan' => $item['catatan'] ?? null,
                    'dicatat_oleh' => $user->id,
                    'dicatat_pada' => $now, // Waktu pencatatan server
                    'dicatat_offline_pada' => !empty($item['dicatat_offline_pada'])
                        ? Carbon::parse($item['dicatat_offline_pada'])
                        : null,
                ]
            );
            $syncedCount++;
        }

        return response()->json([
            'success' => true,
            'conflict' => false,
            'message' => 'Sinkronisasi berhasil. ' . $syncedCount . ' data absensi tersimpan.',
            'synced_count' => $syncedCount,
        ]);
    }

    /**
     * Tandai status pengiriman WhatsApp untuk record absensi siswa tertentu.
     */
    public function tandaiWaTerkirim(Request $request, Absensi $absensi): JsonResponse
    {
        $user = Auth::user();
        $kelas = $absensi->siswa->kelas;

        if ($user->isGuru() && $kelas->wali_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak berwenang mengelola data kelas ini.',
            ], 403);
        }

        $now = Carbon::now();
        $absensi->update([
            'wa_status' => 'sudah_dikirim',
            'wa_dikirim_pada' => $now,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Pesan WhatsApp ke orang tua {$absensi->siswa->nama} ditandai sudah dikirim.",
            'wa_status' => 'sudah_dikirim',
            'wa_dikirim_pada' => $now->format('H:i'),
        ]);
    }

    /**
     * Perbarui template pesan WhatsApp per guru.
     */
    public function updateTemplateWa(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'template_wa' => ['required', 'string', 'max:1000'],
        ]);

        $user = Auth::user();
        $user->update([
            'template_wa' => $validated['template_wa'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Template pesan WhatsApp berhasil disimpan.',
            'template_wa' => $user->template_wa,
        ]);
    }
}
