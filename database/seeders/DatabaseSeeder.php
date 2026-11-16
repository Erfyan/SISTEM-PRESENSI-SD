<?php

namespace Database\Seeders;

use App\Models\Absensi;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Admin
        $admin = User::create([
            'username' => 'admin',
            'nama' => 'Kepala Tata Usaha / Admin',
            'password' => Hash::make('admin123'),
            'peran' => 'admin',
            'aktif' => true,
        ]);

        // 2. Guru
        $guru1 = User::create([
            'username' => 'guru1',
            'nama' => 'Siti Rahayu, S.Pd.',
            'password' => Hash::make('password'),
            'peran' => 'guru',
            'aktif' => true,
        ]);

        $guru2 = User::create([
            'username' => 'guru2',
            'nama' => 'Ahmad Fauzi, S.Pd.',
            'password' => Hash::make('password'),
            'peran' => 'guru',
            'aktif' => true,
        ]);

        // 3. Kelas
        $kelas1A = Kelas::create([
            'nama' => '1A',
            'tahun_ajaran' => '2026/2027',
            'wali_id' => $guru1->id,
        ]);

        $kelas4B = Kelas::create([
            'nama' => '4B',
            'tahun_ajaran' => '2026/2027',
            'wali_id' => $guru2->id,
        ]);

        // 4. Siswa Kelas 1A
        $siswaList = [
            [
                'nis' => '1001',
                'nama' => 'Muhammad Rizky Pratama',
                'jenis_kelamin' => 'L',
                'kelas_id' => $kelas1A->id,
                'nama_ortu' => 'Bpk. Joko Susanto',
                'no_wa' => '6281234567801',
            ],
            [
                'nis' => '1002',
                'nama' => 'Siti Aisyah Azzahra',
                'jenis_kelamin' => 'P',
                'kelas_id' => $kelas1A->id,
                'nama_ortu' => 'Ibu Fatimah Zahra',
                'no_wa' => '6281234567802',
            ],
            [
                'nis' => '1003',
                'nama' => 'Budi Santoso',
                'jenis_kelamin' => 'L',
                'kelas_id' => $kelas1A->id,
                'nama_ortu' => 'Bpk. Hartono',
                'no_wa' => '6281234567803',
            ],
            [
                'nis' => '1004',
                'nama' => 'Dewi Lestari',
                'jenis_kelamin' => 'P',
                'kelas_id' => $kelas1A->id,
                'nama_ortu' => 'Ibu Sri Wahyuni',
                'no_wa' => '6281234567804',
            ],
            [
                'nis' => '1005',
                'nama' => 'Farhan Nugroho Susanto',
                'jenis_kelamin' => 'L',
                'kelas_id' => $kelas1A->id,
                'nama_ortu' => 'Bpk. Joko Susanto',
                'no_wa' => '6281234567801',
            ],
            [
                'nis' => '1006',
                'nama' => 'Annisa Tri Putri',
                'jenis_kelamin' => 'P',
                'kelas_id' => $kelas1A->id,
                'nama_ortu' => 'Ibu Ratna Juwita',
                'no_wa' => '6281234567806',
            ],
        ];

        $createdSiswa = [];
        foreach ($siswaList as $item) {
            $createdSiswa[] = Siswa::create($item);
        }

        // Siswa di Kelas 4B (contoh)
        Siswa::create([
            'nis' => '4001',
            'nama' => 'Alif Ramadhan',
            'jenis_kelamin' => 'L',
            'kelas_id' => $kelas4B->id,
            'nama_ortu' => 'Bpk. Gunawan',
            'no_wa' => '6281299998801',
        ]);

        // 5. Orang Tua
        // Ortu 1: Bpk Joko (punya 2 anak: Rizky [1001] & Farhan [1005])
        $ortu1 = User::create([
            'username' => 'ortu_joko',
            'nama' => 'Bpk. Joko Susanto',
            'password' => Hash::make('password'),
            'peran' => 'ortu',
            'aktif' => true,
        ]);
        $ortu1->anak()->attach([$createdSiswa[0]->id, $createdSiswa[4]->id]);

        // Ortu 2: Ibu Fatimah (anak: Siti Aisyah [1002])
        $ortu2 = User::create([
            'username' => 'ortu_fatimah',
            'nama' => 'Ibu Fatimah Zahra',
            'password' => Hash::make('password'),
            'peran' => 'ortu',
            'aktif' => true,
        ]);
        $ortu2->anak()->attach([$createdSiswa[1]->id]);

        // 6. Data Absensi Contoh Bulan Ini
        $today = Carbon::now();
        // Buat 5 hari sekolah sebelumnya
        $dates = [];
        for ($i = 5; $i >= 1; $i--) {
            $d = $today->copy()->subDays($i);
            if (!$d->isWeekend()) {
                $dates[] = $d->format('Y-m-d');
            }
        }
        $dates[] = $today->format('Y-m-d'); // hari ini

        foreach ($dates as $tglIndex => $tgl) {
            foreach ($createdSiswa as $sIndex => $s) {
                // Tentukan status simulasi
                $status = 'H';
                $catatan = null;

                if ($tglIndex === count($dates) - 1) {
                    // Hari ini: Rizky Hadir, Aisyah Sakit (demam), Budi Izin (acara keluarga), Dewi Hadir, Farhan Hadir, Annisa Alpa
                    if ($sIndex === 1) {
                        $status = 'S';
                        $catatan = 'Demam dan flu';
                    } elseif ($sIndex === 2) {
                        $status = 'I';
                        $catatan = 'Acara keluarga';
                    } elseif ($sIndex === 5) {
                        $status = 'A';
                        $catatan = 'Tanpa keterangan';
                    }
                } else {
                    // Hari sebelumnya: sebagian besar hadir, sesekali izin/sakit
                    if ($sIndex === 3 && $tglIndex === 0) {
                        $status = 'I';
                        $catatan = 'Izin keluar kota';
                    } elseif ($sIndex === 4 && $tglIndex === 1) {
                        $status = 'S';
                        $catatan = 'Sakit gigi';
                    }
                }

                Absensi::create([
                    'siswa_id' => $s->id,
                    'tanggal' => $tgl,
                    'status' => $status,
                    'catatan' => $catatan,
                    'dicatat_oleh' => $guru1->id,
                    'dicatat_pada' => Carbon::parse($tgl . ' 07:30:00'),
                ]);
            }
        }
    }
}
