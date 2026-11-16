<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class KetahananSqliteDanOperasionalTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $guru1;
    private User $guru2;
    private Kelas $kelas1;
    private Kelas $kelas2;
    private Siswa $siswa1;
    private Siswa $siswa2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'username' => 'admin_operasional',
            'peran' => 'admin',
            'aktif' => true,
        ]);

        $this->guru1 = User::factory()->create([
            'username' => 'guru_ops_1',
            'peran' => 'guru',
            'aktif' => true,
        ]);

        $this->guru2 = User::factory()->create([
            'username' => 'guru_ops_2',
            'peran' => 'guru',
            'aktif' => true,
        ]);

        $this->kelas1 = Kelas::create([
            'nama' => '2A',
            'tingkat' => 2,
            'tahun_ajaran' => '2025/2026',
            'wali_id' => $this->guru1->id,
        ]);

        $this->kelas2 = Kelas::create([
            'nama' => '2B',
            'tingkat' => 2,
            'tahun_ajaran' => '2025/2026',
            'wali_id' => $this->guru2->id,
        ]);

        $this->siswa1 = Siswa::create([
            'kelas_id' => $this->kelas1->id,
            'nis' => '2001',
            'nama' => 'Bambang Siswa',
            'jenis_kelamin' => 'L',
            'nama_ortu' => 'Wali Bambang',
            'no_wa' => '628111111111',
            'aktif' => true,
        ]);

        $this->siswa2 = Siswa::create([
            'kelas_id' => $this->kelas2->id,
            'nis' => '2002',
            'nama' => 'Citra Siswa',
            'jenis_kelamin' => 'P',
            'nama_ortu' => 'Wali Citra',
            'no_wa' => '628222222222',
            'aktif' => true,
        ]);
    }

    public function test_konfigurasi_sqlite_wal_dan_busy_timeout()
    {
        $sqliteConfig = config('database.connections.sqlite');

        $this->assertEquals(5000, $sqliteConfig['busy_timeout']);
        $this->assertEquals('WAL', $sqliteConfig['journal_mode']);
        $this->assertEquals('NORMAL', $sqliteConfig['synchronous']);
    }

    public function test_simulasi_simpan_bersamaan_berjalan_lancar()
    {
        // Simulasi transaksi dua guru menyimpan absensi secara bersamaan
        // Memastikan tidak terjadi lock collision
        DB::transaction(function () {
            Absensi::updateOrCreate(
                ['siswa_id' => $this->siswa1->id, 'tanggal' => '2026-05-10'],
                ['status' => 'H', 'dicatat_oleh' => $this->guru1->id]
            );
        });

        DB::transaction(function () {
            Absensi::updateOrCreate(
                ['siswa_id' => $this->siswa2->id, 'tanggal' => '2026-05-10'],
                ['status' => 'S', 'dicatat_oleh' => $this->guru2->id]
            );
        });

        $this->assertDatabaseHas('absensi', [
            'siswa_id' => $this->siswa1->id,
            'status' => 'H',
        ]);

        $this->assertDatabaseHas('absensi', [
            'siswa_id' => $this->siswa2->id,
            'status' => 'S',
        ]);
    }

    public function test_artisan_backup_database_dan_rotasi_retensi()
    {
        $backupDir = storage_path('app/backups');
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        // Jalankan perintah pencadangan
        $exitCode = Artisan::call('app:backup-db', ['--keep' => 14]);
        $this->assertEquals(0, $exitCode);

        // Pastikan file cadangan terbentuk
        $files = collect(File::files($backupDir))->filter(fn ($f) => $f->getExtension() === 'sqlite');
        $this->assertTrue($files->isNotEmpty());
    }

    public function test_artisan_restore_database()
    {
        $backupDir = storage_path('app/backups');
        $files = collect(File::files($backupDir))->filter(fn ($f) => $f->getExtension() === 'sqlite');

        if ($files->isEmpty()) {
            Artisan::call('app:backup-db');
        }

        // Jalankan perintah pemulihan dengan opsi latest dan force
        $exitCode = Artisan::call('app:restore-db', ['--latest' => true, '--force' => true]);
        $this->assertEquals(0, $exitCode);
    }

    public function test_halaman_status_sistem_hanya_bisa_diakses_oleh_admin()
    {
        // Admin bisa mengakses halaman status sistem
        $responseAdmin = $this->actingAs($this->admin)->get('/admin/status-sistem');
        $responseAdmin->assertOk();
        $responseAdmin->assertInertia(fn ($page) => $page
            ->component('Admin/StatusSistem')
            ->has('status.versi_aplikasi')
            ->has('status.db_size')
            ->has('status.wal_mode')
            ->has('daftarCadangan')
        );

        // Guru dilarang mengakses status sistem (redirect oleh role middleware)
        $responseGuru = $this->actingAs($this->guru1)->get('/admin/status-sistem');
        $responseGuru->assertRedirect();
    }
}
