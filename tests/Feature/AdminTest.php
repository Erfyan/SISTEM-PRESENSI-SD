<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $guru;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'username' => 'admin_super',
            'peran' => 'admin',
            'aktif' => true,
        ]);

        $this->guru = User::factory()->create([
            'username' => 'guru_biasa',
            'peran' => 'guru',
            'aktif' => true,
        ]);
    }

    public function test_admin_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Admin/Dashboard'));
    }

    public function test_non_admin_cannot_access_admin_dashboard(): void
    {
        $response = $this->actingAs($this->guru)->get('/admin/dashboard');

        $response->assertRedirect(route('absensi.index'));
        $response->assertSessionHas('error');
    }

    public function test_admin_can_create_kelas(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/kelas', [
            'nama' => '5A',
            'tahun_ajaran' => '2026/2027',
            'wali_id' => $this->guru->id,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('kelas', [
            'nama' => '5A',
            'wali_id' => $this->guru->id,
        ]);
    }

    public function test_admin_can_update_kelas(): void
    {
        $kelas = Kelas::create([
            'nama' => '3A',
            'tahun_ajaran' => '2026/2027',
            'wali_id' => null,
        ]);

        $response = $this->actingAs($this->admin)->put("/admin/kelas/{$kelas->id}", [
            'nama' => '3A Unggulan',
            'tahun_ajaran' => '2026/2027',
            'wali_id' => $this->guru->id,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('kelas', [
            'id' => $kelas->id,
            'nama' => '3A Unggulan',
            'wali_id' => $this->guru->id,
        ]);
    }

    public function test_admin_cannot_delete_kelas_with_active_students(): void
    {
        $kelas = Kelas::create([
            'nama' => '1B',
            'tahun_ajaran' => '2026/2027',
            'wali_id' => $this->guru->id,
        ]);

        Siswa::create([
            'nis' => '1099',
            'nama' => 'Siswa Test',
            'jenis_kelamin' => 'L',
            'kelas_id' => $kelas->id,
            'nama_ortu' => 'Pak Joko',
            'no_wa' => '08123456789',
            'aktif' => true,
        ]);

        $response = $this->actingAs($this->admin)->delete("/admin/kelas/{$kelas->id}");

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('kelas', ['id' => $kelas->id]);
    }

    public function test_admin_can_create_new_guru(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/guru', [
            'nama' => 'Budi Santoso, S.Pd.',
            'username' => 'guru_budi',
            'password' => 'password123',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'username' => 'guru_budi',
            'nama' => 'Budi Santoso, S.Pd.',
            'peran' => 'guru',
            'aktif' => true,
        ]);
    }

    public function test_admin_can_toggle_guru_active_status(): void
    {
        $response = $this->actingAs($this->admin)->patch("/admin/guru/{$this->guru->id}/toggle-aktif");

        $response->assertSessionHas('success');
        $this->guru->refresh();
        $this->assertFalse($this->guru->aktif);
    }

    public function test_admin_can_view_status_sistem(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/status-sistem');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/StatusSistem')
            ->has('status')
            ->has('daftarCadangan')
        );
    }
}
