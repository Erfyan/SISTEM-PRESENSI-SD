<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class SiswaManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $guru1;
    private User $guru2;
    private User $admin;
    private Kelas $kelas1;
    private Kelas $kelas2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guru1 = User::factory()->create([
            'username' => 'guru_wali_1',
            'peran' => 'guru',
            'aktif' => true,
        ]);

        $this->guru2 = User::factory()->create([
            'username' => 'guru_wali_2',
            'peran' => 'guru',
            'aktif' => true,
        ]);

        $this->admin = User::factory()->create([
            'username' => 'admin_siswa',
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
    }

    public function test_guru_can_view_students_in_own_class(): void
    {
        Siswa::create([
            'nis' => '1001',
            'nama' => 'Budi Pratama',
            'jenis_kelamin' => 'L',
            'kelas_id' => $this->kelas1->id,
            'nama_ortu' => 'Pak Joko',
            'no_wa' => '08123456789',
            'aktif' => true,
        ]);

        $response = $this->actingAs($this->guru1)->get('/siswa');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Siswa/Index')
            ->where('kelas.id', $this->kelas1->id)
            ->has('siswa', 1)
        );
    }

    public function test_guru_can_add_student_to_own_class(): void
    {
        $payload = [
            'kelas_id' => $this->kelas1->id,
            'nis' => '1002',
            'nama' => 'Siti Aisyah',
            'jenis_kelamin' => 'P',
            'nama_ortu' => 'Ibu Fatimah',
            'no_wa' => '081298765432',
        ];

        $response = $this->actingAs($this->guru1)->post('/siswa', $payload);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('siswa', [
            'nis' => '1002',
            'nama' => 'Siti Aisyah',
            'kelas_id' => $this->kelas1->id,
            'no_wa' => '6281298765432', // normalized format
            'aktif' => true,
        ]);
    }

    public function test_guru_cannot_add_student_to_another_class(): void
    {
        $payload = [
            'kelas_id' => $this->kelas2->id,
            'nis' => '2001',
            'nama' => 'Farhan Nugroho',
            'jenis_kelamin' => 'L',
            'nama_ortu' => 'Pak Bambang',
            'no_wa' => '081211112222',
        ];

        $response = $this->actingAs($this->guru1)->post('/siswa', $payload);

        $response->assertStatus(403);
    }

    public function test_guru_can_generate_parent_account_with_pin(): void
    {
        $siswa = Siswa::create([
            'nis' => '1003',
            'nama' => 'Dewi Sartika',
            'jenis_kelamin' => 'P',
            'kelas_id' => $this->kelas1->id,
            'nama_ortu' => 'Ibu Kartini',
            'no_wa' => '081233334444',
            'aktif' => true,
        ]);

        $response = $this->actingAs($this->guru1)->post("/siswa/{$siswa->id}/generate-akun-ortu");

        $response->assertSessionHas('success');
        $response->assertSessionHas('temp_password');

        $ortu = User::where('username', 'ortu_1003')->first();
        $this->assertNotNull($ortu);
        $this->assertEquals('ortu', $ortu->peran);
        $this->assertTrue($ortu->wajib_ganti_password);
        $this->assertTrue($siswa->orangTua()->where('users.id', $ortu->id)->exists());
    }

    public function test_guru_can_toggle_student_active_status(): void
    {
        $siswa = Siswa::create([
            'nis' => '1004',
            'nama' => 'Andi Wijaya',
            'jenis_kelamin' => 'L',
            'kelas_id' => $this->kelas1->id,
            'nama_ortu' => 'Pak Hendra',
            'no_wa' => '081255556666',
            'aktif' => true,
        ]);

        $response = $this->actingAs($this->guru1)->patch("/siswa/{$siswa->id}/toggle-aktif");

        $response->assertSessionHas('success');
        $siswa->refresh();
        $this->assertFalse($siswa->aktif);
    }

    public function test_download_import_template(): void
    {
        Excel::fake();

        $response = $this->actingAs($this->guru1)->get('/siswa/import/template');

        $response->assertSuccessful();
        Excel::assertDownloaded('Template_Impor_Siswa.xlsx');
    }
}
