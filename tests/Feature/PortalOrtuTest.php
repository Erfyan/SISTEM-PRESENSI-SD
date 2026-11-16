<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalOrtuTest extends TestCase
{
    use RefreshDatabase;

    private User $ortu;
    private Siswa $anak1;
    private Siswa $anak2;
    private Siswa $anakLain;
    private Kelas $kelas;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ortu = User::factory()->create([
            'username' => 'ortu_budi',
            'peran' => 'ortu',
            'aktif' => true,
        ]);

        $guru = User::factory()->create([
            'username' => 'guru_sd',
            'peran' => 'guru',
        ]);

        $this->kelas = Kelas::create([
            'nama' => '1A',
            'tahun_ajaran' => '2026/2027',
            'wali_id' => $guru->id,
        ]);

        $this->anak1 = Siswa::create([
            'nis' => '1001',
            'nama' => 'Budi Pratama',
            'jenis_kelamin' => 'L',
            'kelas_id' => $this->kelas->id,
            'nama_ortu' => 'Pak Joko',
            'no_wa' => '08123456789',
            'aktif' => true,
        ]);

        $this->anak2 = Siswa::create([
            'nis' => '1002',
            'nama' => 'Rina Lestari',
            'jenis_kelamin' => 'P',
            'kelas_id' => $this->kelas->id,
            'nama_ortu' => 'Pak Joko',
            'no_wa' => '08123456789',
            'aktif' => true,
        ]);

        $this->anakLain = Siswa::create([
            'nis' => '2001',
            'nama' => 'Siswa Orang Lain',
            'jenis_kelamin' => 'L',
            'kelas_id' => $this->kelas->id,
            'nama_ortu' => 'Pak Budi',
            'no_wa' => '081299998888',
            'aktif' => true,
        ]);

        // Hubungkan orang tua dengan anak1 dan anak2
        $this->ortu->anak()->attach([$this->anak1->id, $this->anak2->id]);
    }

    public function test_parent_can_view_portal_ortu(): void
    {
        $response = $this->actingAs($this->ortu)->get('/portal-ortu');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('PortalOrtu/Index')
            ->has('anakList', 2)
            ->where('anakTerpilih.id', $this->anak1->id)
            ->has('ringkasan')
        );
    }

    public function test_parent_can_switch_child(): void
    {
        $response = $this->actingAs($this->ortu)->get('/portal-ortu?siswa_id=' . $this->anak2->id);

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('PortalOrtu/Index')
            ->where('anakTerpilih.id', $this->anak2->id)
        );
    }

    public function test_parent_cannot_view_unauthorized_student(): void
    {
        $response = $this->actingAs($this->ortu)->get('/portal-ortu?siswa_id=' . $this->anakLain->id);

        $response->assertStatus(403);
    }

    public function test_parent_sees_child_attendance_records(): void
    {
        Absensi::create([
            'siswa_id' => $this->anak1->id,
            'tanggal' => Carbon::now()->format('Y-m-d'),
            'status' => 'S',
            'catatan' => 'Demam flu',
            'dicatat_pada' => Carbon::now(),
        ]);

        $response = $this->actingAs($this->ortu)->get('/portal-ortu');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('PortalOrtu/Index')
            ->where('ringkasan.sakit', 1)
            ->has('daftarAbsensi', 1)
        );
    }
}
