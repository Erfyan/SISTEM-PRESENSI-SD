<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AbsensiAuthorizationTest extends TestCase
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
            'username' => 'guru_satu',
            'peran' => 'guru',
            'aktif' => true,
        ]);

        $this->guru2 = User::factory()->create([
            'username' => 'guru_dua',
            'peran' => 'guru',
            'aktif' => true,
        ]);

        $this->admin = User::factory()->create([
            'username' => 'admin_sekolah',
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
            'nama' => 'Budi Santoso',
            'jenis_kelamin' => 'L',
            'kelas_id' => $this->kelas1->id,
            'nama_ortu' => 'Pak Joko',
            'no_wa' => '08123456789',
            'aktif' => true,
        ]);
    }

    public function test_guru_can_access_absensi_for_own_class(): void
    {
        $response = $this->actingAs($this->guru1)->get('/absensi');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Absensi/Index')
            ->has('siswa', 1)
            ->where('kelas.id', $this->kelas1->id)
        );
    }

    public function test_guru_without_class_sees_unassigned_message(): void
    {
        $guruTanpaKelas = User::factory()->create([
            'username' => 'guru_baru',
            'peran' => 'guru',
            'aktif' => true,
        ]);

        $response = $this->actingAs($guruTanpaKelas)->get('/absensi');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Absensi/Index')
            ->where('kelas', null)
            ->has('error_message')
        );
    }

    public function test_guru_cannot_access_absensi_for_another_class(): void
    {
        $response = $this->actingAs($this->guru1)->get('/absensi?kelas_id=' . $this->kelas2->id);

        $response->assertStatus(403);
    }

    public function test_admin_is_strictly_prohibited_from_accessing_absensi(): void
    {
        $response = $this->actingAs($this->admin)->get('/absensi');

        $response->assertRedirect(route('admin.dashboard'));
        $response->assertSessionHas('error');
    }

    public function test_guru_can_save_attendance_successfully(): void
    {
        $tanggal = '2026-11-16';

        $payload = [
            'kelas_id' => $this->kelas1->id,
            'tanggal' => $tanggal,
            'absensi' => [
                [
                    'siswa_id' => $this->siswa1->id,
                    'status' => 'S',
                    'catatan' => 'Demam tinggi',
                ],
            ],
        ];

        $response = $this->actingAs($this->guru1)->post('/absensi', $payload);

        $response->assertRedirect(route('absensi.index', [
            'kelas_id' => $this->kelas1->id,
            'tanggal' => $tanggal,
        ]));

        $this->assertDatabaseHas('absensi', [
            'siswa_id' => $this->siswa1->id,
            'tanggal' => $tanggal,
            'status' => 'S',
            'catatan' => 'Demam tinggi',
            'dicatat_oleh' => $this->guru1->id,
        ]);
    }

    public function test_guru_cannot_save_attendance_for_another_class(): void
    {
        $payload = [
            'kelas_id' => $this->kelas2->id,
            'tanggal' => '2026-11-16',
            'absensi' => [
                [
                    'siswa_id' => $this->siswa1->id,
                    'status' => 'H',
                    'catatan' => null,
                ],
            ],
        ];

        $response = $this->actingAs($this->guru1)->post('/absensi', $payload);

        $response->assertStatus(403);
    }

    public function test_guru_can_sync_offline_attendance(): void
    {
        $tanggal = '2026-11-16';
        $payload = [
            'kelas_id' => $this->kelas1->id,
            'tanggal' => $tanggal,
            'absensi' => [
                [
                    'siswa_id' => $this->siswa1->id,
                    'status' => 'I',
                    'catatan' => 'Acara keluarga',
                    'dicatat_offline_pada' => '2026-11-16 07:15:00',
                ],
            ],
        ];

        $response = $this->actingAs($this->guru1)->postJson('/absensi/sync', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'conflict' => false,
            'synced_count' => 1,
        ]);

        $this->assertDatabaseHas('absensi', [
            'siswa_id' => $this->siswa1->id,
            'tanggal' => $tanggal,
            'status' => 'I',
            'catatan' => 'Acara keluarga',
        ]);
    }

    public function test_sync_detects_conflict_when_server_has_different_data(): void
    {
        $tanggal = '2026-11-16';

        // Data sudah ada di server (status Hadir)
        Absensi::create([
            'siswa_id' => $this->siswa1->id,
            'tanggal' => $tanggal,
            'status' => 'H',
            'dicatat_oleh' => $this->guru1->id,
            'dicatat_pada' => Carbon::now(),
        ]);

        // Kirim data offline yang berbeda (status Izin)
        $payload = [
            'kelas_id' => $this->kelas1->id,
            'tanggal' => $tanggal,
            'absensi' => [
                [
                    'siswa_id' => $this->siswa1->id,
                    'status' => 'I',
                    'catatan' => 'Ada acara',
                    'dicatat_offline_pada' => '2026-11-16 07:20:00',
                ],
            ],
        ];

        $response = $this->actingAs($this->guru1)->postJson('/absensi/sync', $payload);

        $response->assertStatus(409);
        $response->assertJson([
            'conflict' => true,
        ]);
    }

    public function test_sync_resolves_conflict_with_offline_choice(): void
    {
        $tanggal = '2026-11-16';

        Absensi::create([
            'siswa_id' => $this->siswa1->id,
            'tanggal' => $tanggal,
            'status' => 'H',
            'dicatat_oleh' => $this->guru1->id,
            'dicatat_pada' => Carbon::now(),
        ]);

        $payload = [
            'kelas_id' => $this->kelas1->id,
            'tanggal' => $tanggal,
            'absensi' => [
                [
                    'siswa_id' => $this->siswa1->id,
                    'status' => 'A',
                    'catatan' => 'Tanpa kabar',
                ],
            ],
            'resolve_conflicts' => 'offline',
        ];

        $response = $this->actingAs($this->guru1)->postJson('/absensi/sync', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'conflict' => false,
        ]);

        $this->assertDatabaseHas('absensi', [
            'siswa_id' => $this->siswa1->id,
            'tanggal' => $tanggal,
            'status' => 'A',
        ]);
    }

    public function test_guru_can_mark_whatsapp_sent(): void
    {
        $absensi = Absensi::create([
            'siswa_id' => $this->siswa1->id,
            'tanggal' => '2026-11-16',
            'status' => 'S',
            'wa_status' => 'belum_dihubungi',
            'dicatat_oleh' => $this->guru1->id,
            'dicatat_pada' => Carbon::now(),
        ]);

        $response = $this->actingAs($this->guru1)->postJson("/absensi/{$absensi->id}/tandai-wa-terkirim");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'wa_status' => 'sudah_dikirim',
        ]);

        $absensi->refresh();
        $this->assertEquals('sudah_dikirim', $absensi->wa_status);
        $this->assertNotNull($absensi->wa_dikirim_pada);
    }

    public function test_guru_can_update_whatsapp_template(): void
    {
        $templateBaru = 'Yth. {nama_ortu}, {nama_siswa} tercatat {status}. Hubungi kami jika ada pertanyaan.';

        $response = $this->actingAs($this->guru1)->postJson('/profil/template-wa', [
            'template_wa' => $templateBaru,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'template_wa' => $templateBaru,
        ]);

        $this->guru1->refresh();
        $this->assertEquals($templateBaru, $this->guru1->template_wa);
    }
}
