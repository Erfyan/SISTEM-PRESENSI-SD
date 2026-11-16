<?php

namespace App\Http\Controllers;

use App\Models\HariLibur;
use App\Models\Kelas;
use App\Models\Pengaturan;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class HariLiburController extends Controller
{
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $tahun = $request->input('tahun', Carbon::now()->year);

        $kelasList = [];
        if ($user->isAdmin()) {
            $kelasList = Kelas::orderBy('nama')->get();
        } elseif ($user->isGuru() && $user->kelasDiampu) {
            $kelasList = [$user->kelasDiampu];
        }

        $hariLibur = HariLibur::with('kelas')
            ->whereYear('tanggal', $tahun)
            ->orderBy('tanggal')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'tanggal' => $item->tanggal->format('Y-m-d'),
                    'tanggal_indo' => $item->tanggal->locale('id')->isoFormat('D MMMM Y'),
                    'hari' => $item->tanggal->locale('id')->isoFormat('dddd'),
                    'keterangan' => $item->keterangan,
                    'berlaku_untuk' => $item->berlaku_untuk,
                    'kelas_id' => $item->kelas_id,
                    'nama_kelas' => $item->kelas ? $item->kelas->nama : 'Semua Kelas',
                ];
            });

        $jamBatas = Pengaturan::get('jam_batas_absen', '09:00');

        return Inertia::render('KalenderLibur/Index', [
            'hariLibur' => $hariLibur,
            'kelasList' => $kelasList,
            'tahun' => (int) $tahun,
            'jamBatas' => $jamBatas,
            'isAdmin' => $user->isAdmin(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'keterangan' => ['required', 'string', 'max:255'],
            'berlaku_untuk' => ['required', 'in:semua,kelas'],
            'kelas_id' => ['nullable', 'exists:kelas,id'],
        ]);

        if ($user->isGuru()) {
            // Guru hanya boleh menandai libur untuk kelasnya
            $kelasDiampu = $user->kelasDiampu;
            if (!$kelasDiampu) {
                abort(403, 'Anda belum ditugaskan sebagai wali kelas.');
            }
            $validated['berlaku_untuk'] = 'kelas';
            $validated['kelas_id'] = $kelasDiampu->id;
        }

        if ($validated['berlaku_untuk'] === 'semua') {
            $validated['kelas_id'] = null;
        }

        $validated['dicatat_oleh'] = $user->id;

        HariLibur::create($validated);

        return back()->with('success', 'Hari libur berhasil ditambahkan.');
    }

    public function destroy(HariLibur $hariLibur): RedirectResponse
    {
        $user = Auth::user();

        if ($user->isGuru() && $hariLibur->kelas_id !== $user->kelasDiampu?->id) {
            abort(403, 'Anda tidak berwenang menghapus hari libur ini.');
        }

        $hariLibur->delete();

        return back()->with('success', 'Hari libur berhasil dihapus.');
    }

    public function updateJamBatas(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'jam_batas_absen' => ['required', 'regex:/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/'],
        ]);

        Pengaturan::set('jam_batas_absen', $validated['jam_batas_absen'], 'Batas jam pengingat absen kosong');

        return back()->with('success', 'Jam batas pengingat absen berhasil diperbarui.');
    }
}
