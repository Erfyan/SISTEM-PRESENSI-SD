<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\LogAudit;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class KeamananAkunTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $guru1;
    private User $guru2;
    private Kelas $kelas1;
    private Kelas $kelas2;
    private Siswa $siswa1;
    private Siswa $siswa2;
    private User $ortu1;
    private User $ortu2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'username' => 'admin_keamanan',
            'peran' => 'admin',
            'aktif' => true,
        ]);

        $this->guru1 = User::factory()->create([
            'username' => 'guru1',
            'peran' => 'guru',
            'aktif' => true,
        ]);

        $this->guru2 = User::factory()->create([
            'username' => 'guru2',
            'peran' => 'guru',
            'aktif' => true,
        ]);

        $this->kelas1 = Kelas::create([
            'nama' => '1A',
            'tingkat' => 1,
            'tahun_ajaran' => '2025/2026',
            'wali_id' => $this->guru1->id,
        ]);

        $this->kelas2 = Kelas::create([
            'nama' => '1B',
            'tingkat' => 1,
            'tahun_ajaran' => '2025/2026',
            'wali_id' => $this->guru2->id,
        ]);

        $this->siswa1 = Siswa::create([
            'kelas_id' => $this->kelas1->id,
            'nis' => '1001',
            'nama' => 'Anak Ortu 1',
            'jenis_kelamin' => 'L',
            'nama_ortu' => 'Wali Satu',
            'no_wa' => '628123456789',
            'aktif' => true,
        ]);

        $this->siswa2 = Siswa::create([
            'kelas_id' => $this->kelas2->id,
            'nis' => '1002',
            'nama' => 'Anak Ortu 2',
            'jenis_kelamin' => 'P',
            'nama_ortu' => 'Wali Dua',
            'no_wa' => '628987654321',
            'aktif' => true,
        ]);

        $this->ortu1 = User::create([
            'username' => 'ortu_1001',
            'nama' => 'Wali Satu',
            'password' => Hash::make('123456'),
            'peran' => 'ortu',
            'aktif' => true,
            'wajib_ganti_password' => true,
        ]);
        $this->siswa1->orangTua()->attach($this->ortu1->id);

        $this->ortu2 = User::create([
            'username' => 'ortu_1002',
            'nama' => 'Wali Dua',
            'password' => Hash::make('123456'),
            'peran' => 'ortu',
            'aktif' => true,
            'wajib_ganti_password' => false,
        ]);
        $this->siswa2->orangTua()->attach($this->ortu2->id);
    }

    public function test_header_keamanan_tersedia_pada_response()
    {
        $response = $this->get('/login');
        $response->assertOk();
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_login_pertama_wajib_ganti_pin()
    {
        // Ortu1 memiliki wajib_ganti_password = true
        $response = $this->post('/login', [
            'username' => 'ortu_1001',
            'password' => '123456',
        ]);

        $response->assertRedirect('/profil/password');
        $this->assertAuthenticatedAs($this->ortu1);

        // Jika mencoba mengakses portal-ortu sebelum mengganti password, diarahkan kembali
        $responsePortal = $this->actingAs($this->ortu1)->get('/portal-ortu');
        $responsePortal->assertRedirect('/profil/password');
    }

    public function test_berhasil_ganti_password_dan_tercatat_di_log_audit()
    {
        $response = $this->actingAs($this->ortu1)->put('/profil/password', [
            'current_password' => '123456',
            'password' => '654321',
            'password_confirmation' => '654321',
        ]);

        $response->assertRedirect('/portal-ortu');

        $this->ortu1->refresh();
        $this->assertFalse($this->ortu1->wajib_ganti_password);
        $this->assertTrue(Hash::check('654321', $this->ortu1->password));

        $this->assertDatabaseHas('log_audit', [
            'user_id' => $this->ortu1->id,
            'aksi' => 'ganti_password',
            'username' => 'ortu_1001',
        ]);
    }

    public function test_rate_limiter_mengunci_setelah_5_kali_gagal_login()
    {
        RateLimiter::clear('login_user|ortu_1001');
        RateLimiter::clear('login_ip|127.0.0.1');

        for ($i = 1; $i <= 5; $i++) {
            $response = $this->post('/login', [
                'username' => 'ortu_1001',
                'password' => 'wrong_password',
            ]);
            $response->assertSessionHasErrors('username');
        }

        // Percobaan ke-6 harus ditolak oleh RateLimiter
        $responseKeenam = $this->post('/login', [
            'username' => 'ortu_1001',
            'password' => 'wrong_password',
        ]);

        $responseKeenam->assertSessionHasErrors(['username' => 'Terlalu banyak percobaan login yang gagal. Akun dikunci sementara. Silakan coba lagi dalam 15 menit.']);

        $this->assertDatabaseHas('log_audit', [
            'aksi' => 'login_terkunci',
            'username' => 'ortu_1001',
        ]);
    }

    public function test_guru_mengakses_kelas_lain_ditolak_403()
    {
        // Guru 1 mencoba mengakses absensi kelas 2
        $response = $this->actingAs($this->guru1)->get("/absensi?kelas_id={$this->kelas2->id}");
        $response->assertForbidden();

        // Guru 1 mencoba simpan absensi kelas 2
        $responseStore = $this->actingAs($this->guru1)->post('/absensi', [
            'kelas_id' => $this->kelas2->id,
            'tanggal' => '2026-05-04',
            'absensi' => [
                [
                    'siswa_id' => $this->siswa2->id,
                    'status' => 'H',
                ],
            ],
        ]);
        $responseStore->assertForbidden();

        // Guru 1 mencoba akses rekap kelas 2
        $responseRekap = $this->actingAs($this->guru1)->get("/rekap?kelas_id={$this->kelas2->id}&bulan=2026-05");
        $responseRekap->assertForbidden();
    }

    public function test_ortu_mengakses_data_anak_lain_ditolak_403()
    {
        // Ortu 2 mencoba melihat data siswa 1 (anak dari Ortu 1)
        $response = $this->actingAs($this->ortu2)->get("/portal-ortu?siswa_id={$this->siswa1->id}");
        $response->assertForbidden();

        // Ortu 2 mengakses anaknya sendiri diizinkan
        $responseAnakSendiri = $this->actingAs($this->ortu2)->get("/portal-ortu?siswa_id={$this->siswa2->id}");
        $responseAnakSendiri->assertOk();
    }
}
