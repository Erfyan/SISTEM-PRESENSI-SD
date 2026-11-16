<?php

namespace App\Http\Controllers;

use App\Exports\RekapAbsensiExport;
use App\Models\Absensi;
use App\Models\HariLibur;
use App\Models\Kelas;
use App\Models\Siswa;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RekapController extends Controller
{
    private function getRekapData(int $kelasId, string $bulan): array
    {
        $kelas = Kelas::with('wali')->findOrFail($kelasId);
        $carbonBulan = Carbon::parse($bulan . '-01')->locale('id');
        $startOfMonth = $carbonBulan->copy()->startOfMonth();
        $endOfMonth = $carbonBulan->copy()->endOfMonth();

        $siswaList = Siswa::where('kelas_id', $kelasId)
            ->where('aktif', true)
            ->orderBy('nama')
            ->get();

        $siswaIds = $siswaList->pluck('id');

        // Ambil hari libur yang berlaku untuk kelas ini pada bulan ini
        $hariLiburList = HariLibur::forKelas($kelasId)
            ->whereBetween('tanggal', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
            ->get()
            ->keyBy(fn($item) => $item->tanggal->format('Y-m-d'));

        // Ambil tanggal-tanggal yang absennya sudah terisi untuk kelas ini
        $tanggalTerisi = Absensi::whereIn('siswa_id', $siswaIds)
            ->whereBetween('tanggal', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
            ->distinct()
            ->pluck('tanggal')
            ->map(fn($t) => Carbon::parse($t)->format('Y-m-d'))
            ->flip()
            ->toArray();

        $hariSekolahEfektif = [];
        $hariBelumDiisi = [];
        $daftarHariLibur = [];

        $today = Carbon::today()->format('Y-m-d');
        $period = CarbonPeriod::create($startOfMonth, $endOfMonth);

        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');

            // Hanya evaluasi hari sekolah: Senin s.d. Jumat (isWeekday)
            if ($date->isWeekday()) {
                if ($hariLiburList->has($dateStr)) {
                    $libur = $hariLiburList->get($dateStr);
                    $daftarHariLibur[] = [
                        'tanggal' => $dateStr,
                        'tanggal_indo' => $date->locale('id')->isoFormat('D MMMM Y'),
                        'hari' => $date->locale('id')->isoFormat('dddd'),
                        'keterangan' => $libur->keterangan,
                    ];
                } else {
                    // Bukan libur: periksa apakah sudah terisi
                    if (isset($tanggalTerisi[$dateStr])) {
                        $hariSekolahEfektif[] = $dateStr;
                    } elseif ($dateStr <= $today) {
                        // Hari sekolah yang telah lewat/hari ini namun belum terisi absensinya
                        $hariBelumDiisi[] = [
                            'tanggal' => $dateStr,
                            'tanggal_indo' => $date->locale('id')->isoFormat('D MMMM Y'),
                            'hari' => $date->locale('id')->isoFormat('dddd'),
                        ];
                    }
                }
            }
        }

        // Hari efektif adalah hari sekolah bukan libur yang absennya sudah terisi
        $hariEfektifCount = count($hariSekolahEfektif);

        // Ambil semua absensi siswa pada bulan ini
        $allAbsensi = Absensi::whereIn('siswa_id', $siswaIds)
            ->whereBetween('tanggal', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
            ->get()
            ->groupBy('siswa_id');

        $rekap = $siswaList->map(function ($s) use ($allAbsensi, $hariEfektifCount) {
            $catatanSiswa = $allAbsensi->get($s->id, collect());

            $hadir = $catatanSiswa->where('status', 'H')->count();
            $izin = $catatanSiswa->where('status', 'I')->count();
            $sakit = $catatanSiswa->where('status', 'S')->count();
            $alpa = $catatanSiswa->where('status', 'A')->count();

            // Persentase kehadiran siswa = jumlah Hadir dibagi hari efektif
            $persentase = $hariEfektifCount > 0 ? round(($hadir / $hariEfektifCount) * 100, 1) : 0;

            return [
                'id' => $s->id,
                'nis' => $s->nis,
                'nama' => $s->nama,
                'jenis_kelamin' => $s->jenis_kelamin,
                'hadir' => $hadir,
                'izin' => $izin,
                'sakit' => $sakit,
                'alpa' => $alpa,
                'persentase' => $persentase,
            ];
        })->toArray();

        return [
            'kelas' => $kelas,
            'bulan' => $bulan,
            'namaBulan' => $carbonBulan->isoFormat('MMMM Y'),
            'hariEfektif' => $hariEfektifCount,
            'hariBelumDiisi' => $hariBelumDiisi,
            'daftarHariLibur' => $daftarHariLibur,
            'rekap' => $rekap,
        ];
    }

    public function index(Request $request): Response
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            $kelasList = Kelas::orderBy('nama')->get();
            $kelasId = $request->input('kelas_id') ?? ($kelasList->first()?->id);
        } else {
            $kelas = $user->kelasDiampu;
            $requestedKelasId = $request->input('kelas_id');
            if ($requestedKelasId && $kelas && (int) $requestedKelasId !== (int) $kelas->id) {
                abort(403, 'Anda tidak memiliki hak akses ke rekap kelas lain.');
            }

            if (!$kelas) {
                return Inertia::render('Rekap/Index', [
                    'error_message' => 'Anda belum ditugaskan sebagai wali kelas.',
                    'kelasList' => [],
                    'kelas' => null,
                    'bulan' => Carbon::now()->format('Y-m'),
                    'namaBulan' => '',
                    'hariEfektif' => 0,
                    'rekap' => [],
                ]);
            }
            $kelasList = [$kelas];
            $kelasId = $kelas->id;
        }

        $bulan = $request->input('bulan', Carbon::now()->format('Y-m'));

        $data = $kelasId ? $this->getRekapData($kelasId, $bulan) : [
            'kelas' => null,
            'bulan' => $bulan,
            'namaBulan' => '',
            'hariEfektif' => 0,
            'hariBelumDiisi' => [],
            'daftarHariLibur' => [],
            'rekap' => [],
        ];

        return Inertia::render('Rekap/Index', [
            'kelasList' => $kelasList,
            'kelas' => $data['kelas'],
            'bulan' => $data['bulan'],
            'namaBulan' => $data['namaBulan'],
            'hariEfektif' => $data['hariEfektif'],
            'hariBelumDiisi' => $data['hariBelumDiisi'],
            'daftarHariLibur' => $data['daftarHariLibur'],
            'rekap' => $data['rekap'],
        ]);
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        $user = Auth::user();
        $kelasId = $request->query('kelas_id');
        $bulan = $request->query('bulan', Carbon::now()->format('Y-m'));

        if ($user->isGuru()) {
            if ($kelasId && (int) $kelasId !== (int) $user->kelasDiampu?->id) {
                abort(403, 'Anda tidak memiliki hak akses ke rekap kelas lain.');
            }
            $kelasId = $user->kelasDiampu?->id;
        }

        if (!$kelasId) {
            abort(404, 'Kelas tidak ditemukan');
        }

        $data = $this->getRekapData((int) $kelasId, $bulan);
        $filename = 'Rekap_Absensi_Kelas_' . $data['kelas']->nama . '_' . $bulan . '.xlsx';

        return Excel::download(
            new RekapAbsensiExport($data['rekap'], $data['kelas']->nama, $data['namaBulan'], $data['hariEfektif']),
            $filename
        );
    }

    public function exportPdf(Request $request)
    {
        $user = Auth::user();
        $kelasId = $request->query('kelas_id');
        $bulan = $request->query('bulan', Carbon::now()->format('Y-m'));

        if ($user->isGuru()) {
            if ($kelasId && (int) $kelasId !== (int) $user->kelasDiampu?->id) {
                abort(403, 'Anda tidak memiliki hak akses ke rekap kelas lain.');
            }
            $kelasId = $user->kelasDiampu?->id;
        }

        if (!$kelasId) {
            abort(404, 'Kelas tidak ditemukan');
        }

        $data = $this->getRekapData((int) $kelasId, $bulan);
        $filename = 'Rekap_Absensi_Kelas_' . $data['kelas']->nama . '_' . $bulan . '.pdf';

        $pdf = Pdf::loadView('exports.rekap-pdf', $data)->setPaper('a4', 'portrait');

        return $pdf->download($filename);
    }
}
