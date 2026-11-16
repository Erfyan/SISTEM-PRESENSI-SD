<?php

namespace Tests\Feature;

use App\Models\HariLibur;
use App\Models\Kelas;
use App\Models\Pengaturan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HariLiburTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $guru1;
    private User $guru2;
    private Kelas $kelas1;
    private Kelas $kelas2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'username' => 'admin_libur',
            'peran' => 'admin',
            'aktif' => true,
        ]);

        $this->guru1 = User::factory()->create([
            'username' => 'guru_libur_1',
            'peran' => 'guru',
            'aktif' => true,
        ]);

        $this->guru2 = User::factory()->create([
            'username' => 'guru_libur_2',
            'peran' => 'guru',
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

    public function test_admin_can_view_calendar(): void
    {
        $response = $this->actingAs($this->admin)->get('/kalender-libur');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('KalenderLibur/Index'));
    }

    public function test_admin_can_add_holiday_for_all_classes(): void
    {
        $response = $this->actingAs($this->admin)->post('/kalender-libur', [
            'tanggal' => '2026-12-25',
            'keterangan' => 'Hari Raya Natal',
            'berlaku_untuk' => 'semua',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('hari_libur', [
            'tanggal' => '2026-12-25',
            'keterangan' => 'Hari Raya Natal',
            'berlaku_untuk' => 'semua',
            'kelas_id' => null,
            'dicatat_oleh' => $this->admin->id,
        ]);
    }

    public function test_guru_adds_holiday_scoped_to_own_class(): void
    {
        $response = $this->actingAs($this->guru1)->post('/kalender-libur', [
            'tanggal' => '2026-11-20',
            'keterangan' => 'Study Tour Kelas 1A',
            'berlaku_untuk' => 'semua', // meskipun dikirim 'semua', controller memaksakan 'kelas'
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('hari_libur', [
            'tanggal' => '2026-11-20',
            'berlaku_untuk' => 'kelas',
            'kelas_id' => $this->kelas1->id,
        ]);
    }

    public function test_guru_cannot_delete_global_or_other_class_holiday(): void
    {
        $liburSemua = HariLibur::create([
            'tanggal' => '2026-08-17',
            'keterangan' => 'Hari Kemerdekaan RI',
            'berlaku_untuk' => 'semua',
            'dicatat_oleh' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->guru1)->delete("/kalender-libur/{$liburSemua->id}");

        $response->assertStatus(403);
    }

    public function test_user_can_update_jam_batas(): void
    {
        $response = $this->actingAs($this->admin)->post('/kalender-libur/pengaturan-jam', [
            'jam_batas_absen' => '08:30',
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals('08:30', Pengaturan::get('jam_batas_absen'));
    }
}
