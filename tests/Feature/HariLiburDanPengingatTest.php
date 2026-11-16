<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\HariLibur;
use App\Models\Kelas;
use App\Models\Pengaturan;
use App\Models\Siswa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HariLiburDanPengingatTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $guru;
    private Kelas $kelas;
    private Siswa $siswa1;
    private Siswa $siswa2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'username' => 'admin_test',
            'peran' => 'admin',
            'aktif' => true,
        ]);

        $this->guru = User::factory()->create([
            'username' => 'guru_test',
            'peran' => 'guru',
            'aktif' => true,
        ]);

        $this->kelas = Kelas::create([
            'nama' => '1A',
            'tingkat' => 1,
            'tahun_ajaran' => '2025/2026',
            'wali_id' => $this->guru->id,
        ]);

        $this->siswa1 = Siswa::create([
            'kelas_id' => $this->kelas->id,
            'nis' => '1001',
            'nama' => 'Budi Santoso',
            'jenis_kelamin' => 'L',
            'nama_ortu' => 'Bapak Joko',
            'no_wa' => '628123456789',
            'aktif' => true,
        ]);

        $this->siswa2 = Siswa::create([
            'kelas_id' => $this->kelas->id,
            'nis' => '1002',
            'nama' => 'Siti Aminah',
            'jenis_kelamin' => 'P',
            'nama_ortu' => 'Ibu Siti',
            'no_wa' => '628987654321',
            'aktif' => true,
        ]);
    }

    public function test_admin_dan_guru_bisa_menambah_hari_libur()
    {
        // Admin tambah libur nasional (semua kelas)
        $response = $this->actingAs($this->admin)->post('/kalender-libur', [
            'tanggal' => '2026-05-01',
            'keterangan' => 'Hari Buruh',
            'berlaku_untuk' => 'semua',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('hari_libur', [
            'tanggal' => '2026-05-01',
            'keterangan' => 'Hari Buruh',
            'berlaku_untuk' => 'semua',
            'kelas_id' => null,
        ]);

        // Guru tambah libur khusus kelasnya
        $response = $this->actingAs($this->guru)->post('/kalender-libur', [
            'tanggal' => '2026-05-02',
            'keterangan' => 'Kegiatan Kelas 1A',
            'berlaku_untuk' => 'kelas',
            'kelas_id' => $this->kelas->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('hari_libur', [
            'tanggal' => '2026-05-02',
            'keterangan' => 'Kegiatan Kelas 1A',
            'kelas_id' => $this->kelas->id,
        ]);
    }

    public function test_hapus_hari_libur()
    {
        $libur = HariLibur::create([
            'tanggal' => '2026-05-01',
            'keterangan' => 'Hari Buruh',
            'berlaku_untuk' => 'semua',
            'dibuat_oleh' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->delete("/kalender-libur/{$libur->id}");
        $response->assertRedirect();
        $this->assertDatabaseMissing('hari_libur', ['id' => $libur->id]);
    }

    public function test_rekap_menghitung_hari_efektif_dan_mengidentifikasi_hari_kosong()
    {
        // Misalnya bulan Mei 2026
        // Hari libur nasional pada hari kerja 2026-05-01 (Jumat)
        HariLibur::create([
            'tanggal' => '2026-05-01',
            'keterangan' => 'Hari Buruh',
            'berlaku_untuk' => 'semua',
            'dibuat_oleh' => $this->admin->id,
        ]);

        // Catat absensi hanya di 2 hari: 2026-05-04 (Senin) dan 2026-05-05 (Selasa)
        Absensi::create([
            'siswa_id' => $this->siswa1->id,
            'tanggal' => '2026-05-04',
            'status' => 'H',
            'dicatat_oleh' => $this->guru->id,
        ]);
        Absensi::create([
            'siswa_id' => $this->siswa2->id,
            'tanggal' => '2026-05-04',
            'status' => 'H',
            'dicatat_oleh' => $this->guru->id,
        ]);

        Absensi::create([
            'siswa_id' => $this->siswa1->id,
            'tanggal' => '2026-05-05',
            'status' => 'I',
            'dicatat_oleh' => $this->guru->id,
        ]);
        Absensi::create([
            'siswa_id' => $this->siswa2->id,
            'tanggal' => '2026-05-05',
            'status' => 'H',
            'dicatat_oleh' => $this->guru->id,
        ]);

        // Cek rekap via endpoint
        $response = $this->actingAs($this->guru)->get("/rekap?kelas_id={$this->kelas->id}&bulan=2026-05");
        $response->assertOk();

        // Hari efektif harus 2 (karena baru 2 hari yang terisi catatan absensi)
        $response->assertInertia(fn ($page) => $page
            ->component('Rekap/Index')
            ->where('hariEfektif', 2)
            ->has('hariBelumDiisi')
            ->has('daftarHariLibur')
        );
    }

    public function test_pengaturan_jam_batas_absen_dan_peringatan_absen_kosong()
    {
        // Set jam batas ke 08:00
        $this->actingAs($this->admin)->post('/kalender-libur/pengaturan-jam', [
            'jam_batas_absen' => '08:00',
        ]);

        $this->assertEquals('08:00', Pengaturan::get('jam_batas_absen'));

        // Mock waktu ke hari kerja jam 10:00 (lewat batas 08:00)
        Carbon::setTestNow(Carbon::parse('2026-05-04 10:00:00')); // Senin

        $response = $this->actingAs($this->guru)->get("/absensi?kelas_id={$this->kelas->id}&tanggal=2026-05-04");
        $response->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->component('Absensi/Index')
            ->where('peringatanAbsenKosong.tampilkan', true)
            ->where('peringatanAbsenKosong.jam_batas', '08:00')
        );

        Carbon::setTestNow(); // Reset mock
    }

    public function test_ortu_tidak_bisa_mengakses_kalender_libur()
    {
        $ortu = User::factory()->create([
            'username' => 'ortu_test',
            'peran' => 'ortu',
            'aktif' => true,
        ]);

        $response = $this->actingAs($ortu)->get('/kalender-libur');
        $response->assertRedirect('/portal-ortu');
    }
}
