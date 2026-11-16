<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PortalOrtuController extends Controller
{
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $anakList = $user->anak()->with('kelas.wali')->get();

        if ($anakList->isEmpty()) {
            return Inertia::render('PortalOrtu/Index', [
                'anakList' => [],
                'anakTerpilih' => null,
                'bulan' => Carbon::now()->format('Y-m'),
                'namaBulan' => '',
                'ringkasan' => [
                    'hadir' => 0,
                    'izin' => 0,
                    'sakit' => 0,
                    'alpa' => 0,
                    'hari_efektif' => 0,
                    'persentase' => 0,
                ],
                'daftarAbsensi' => [],
            ]);
        }

        // Tentukan anak yang dipilih
        $siswaId = $request->input('siswa_id');
        if ($siswaId) {
            $anakTerpilih = $anakList->firstWhere('id', (int) $siswaId);
            if (!$anakTerpilih) {
                abort(403, 'Anda tidak memiliki hak akses ke data siswa ini.');
            }
        } else {
            $anakTerpilih = $anakList->first();
        }

        // Bulan
        $bulan = $request->input('bulan', Carbon::now()->format('Y-m'));
        $carbonBulan = Carbon::parse($bulan . '-01')->locale('id');
        $startDate = $carbonBulan->copy()->startOfMonth()->format('Y-m-d');
        $endDate = $carbonBulan->copy()->endOfMonth()->format('Y-m-d');

        // Hari efektif kelas anak pada bulan ini
        $hariEfektif = Absensi::whereHas('siswa', function ($q) use ($anakTerpilih) {
            $q->where('kelas_id', $anakTerpilih->kelas_id);
        })
        ->whereBetween('tanggal', [$startDate, $endDate])
        ->distinct()
        ->pluck('tanggal')
        ->count();

        // Absensi anak terpilih
        $absensi = Absensi::where('siswa_id', $anakTerpilih->id)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->orderBy('tanggal', 'desc')
            ->get();

        $hadir = $absensi->where('status', 'H')->count();
        $izin = $absensi->where('status', 'I')->count();
        $sakit = $absensi->where('status', 'S')->count();
        $alpa = $absensi->where('status', 'A')->count();

        $persentase = $hariEfektif > 0 ? round(($hadir / $hariEfektif) * 100, 1) : 0;

        $daftarAbsensi = $absensi->map(function ($a) {
            $carbon = Carbon::parse($a->tanggal)->locale('id');
            return [
                'id' => $a->id,
                'tanggal' => $a->tanggal->format('Y-m-d'),
                'hari' => $carbon->isoFormat('dddd'),
                'tanggal_formatted' => $carbon->isoFormat('D MMMM Y'),
                'status' => $a->status,
                'catatan' => $a->catatan,
            ];
        });

        return Inertia::render('PortalOrtu/Index', [
            'anakList' => $anakList->map(fn ($s) => [
                'id' => $s->id,
                'nis' => $s->nis,
                'nama' => $s->nama,
                'jenis_kelamin' => $s->jenis_kelamin,
                'kelas' => $s->kelas ? $s->kelas->nama : '-',
                'wali_kelas' => $s->kelas?->wali?->nama ?? '-',
            ]),
            'anakTerpilih' => [
                'id' => $anakTerpilih->id,
                'nis' => $anakTerpilih->nis,
                'nama' => $anakTerpilih->nama,
                'jenis_kelamin' => $anakTerpilih->jenis_kelamin,
                'kelas' => $anakTerpilih->kelas ? $anakTerpilih->kelas->nama : '-',
                'wali_kelas' => $anakTerpilih->kelas?->wali?->nama ?? '-',
            ],
            'bulan' => $bulan,
            'namaBulan' => $carbonBulan->isoFormat('MMMM Y'),
            'ringkasan' => [
                'hadir' => $hadir,
                'izin' => $izin,
                'sakit' => $sakit,
                'alpa' => $alpa,
                'hari_efektif' => $hariEfektif,
                'persentase' => $persentase,
            ],
            'daftarAbsensi' => $daftarAbsensi,
        ]);
    }
}
