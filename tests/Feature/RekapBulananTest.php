<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\HariLibur;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class RekapBulananTest extends TestCase
{
    use RefreshDatabase;

    private User $guru1;
    private User $guru2;
    private User $admin;
    private Kelas $kelas1;
    private Kelas $kelas2;
    private Siswa $siswa1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guru1 = User::factory()->create([
            'username' => 'guru_rekap_1',
            'peran' => 'guru',
            'aktif' => true,
        ]);

        $this->guru2 = User::factory()->create([
            'username' => 'guru_rekap_2',
            'peran' => 'guru',
            'aktif' => true,
        ]);

        $this->admin = User::factory()->create([
            'username' => 'admin_rekap',
            'peran' => 'admin',
            'aktif' => true,
        ]);

        $this->kelas1 = Kelas::create([
            'nama' => '1A',
            'tahun_ajaran' => '2026/2027',
            'wali_id' => $this->guru1->id,
        ]);

        $this->kelas2 = Kelas::create([
            'nama' => '2B',
            'tahun_ajaran' => '2026/2027',
            'wali_id' => $this->guru2->id,
        ]);

        $this->siswa1 = Siswa::create([
            'nis' => '1001',
            'nama' => 'Ahmad Santoso',
            'jenis_kelamin' => 'L',
            'kelas_id' => $this->kelas1->id,
            'nama_ortu' => 'Pak Joko',
            'no_wa' => '08123456789',
            'aktif' => true,
        ]);
    }

    public function test_guru_can_access_rekap_for_own_class(): void
    {
        $response = $this->actingAs($this->guru1)->get('/rekap');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Rekap/Index')
            ->where('kelas.id', $this->kelas1->id)
            ->has('rekap', 1)
        );
    }

    public function test_guru_cannot_access_rekap_of_another_class(): void
    {
        $response = $this->actingAs($this->guru1)->get('/rekap?kelas_id=' . $this->kelas2->id);

        $response->assertStatus(403);
    }

    public function test_admin_can_access_rekap_and_switch_classes(): void
    {
        $response = $this->actingAs($this->admin)->get('/rekap?kelas_id=' . $this->kelas2->id);

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Rekap/Index')
            ->where('kelas.id', $this->kelas2->id)
            ->has('kelasList', 2)
        );
    }

    public function test_rekap_calculates_effective_days_excluding_holidays(): void
    {
        $bulan = '2026-11';
        $tglAbsen = '2026-11-02'; // Senin
        $tglLibur = '2026-11-03'; // Selasa

        // Buat hari libur
        HariLibur::create([
            'tanggal' => $tglLibur,
            'keterangan' => 'Libur Nasional Contoh',
            'berlaku_untuk' => 'semua',
            'dicatat_oleh' => $this->admin->id,
        ]);

        // Buat absensi hadir untuk siswa1 pada tglAbsen
        Absensi::create([
            'siswa_id' => $this->siswa1->id,
            'tanggal' => $tglAbsen,
            'status' => 'H',
            'dicatat_oleh' => $this->guru1->id,
            'dicatat_pada' => Carbon::now(),
        ]);

        $response = $this->actingAs($this->guru1)->get("/rekap?bulan={$bulan}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Rekap/Index')
            ->where('hariEfektif', 1)
            ->has('daftarHariLibur', 1)
            ->where('rekap.0.hadir', 1)
            ->where('rekap.0.persentase', 100)
        );
    }

    public function test_export_excel_endpoint_works(): void
    {
        Excel::fake();

        $response = $this->actingAs($this->guru1)->get('/rekap/export/excel?bulan=2026-11');

        $response->assertSuccessful();
        Excel::assertDownloaded('Rekap_Absensi_Kelas_1A_2026-11.xlsx');
    }
}
