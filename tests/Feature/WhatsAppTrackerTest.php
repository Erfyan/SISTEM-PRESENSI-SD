<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use App\Services\WhatsApp\WhatsAppSenderInterface;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppTrackerTest extends TestCase
{
    use RefreshDatabase;

    private User $guru;
    private Kelas $kelas;
    private Siswa $siswa1;
    private Siswa $siswa2;
    private WhatsAppSenderInterface $sender;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sender = app(WhatsAppSenderInterface::class);

        $this->guru = User::factory()->create([
            'username' => 'guru_wali',
            'nama' => 'Budi Raharjo, S.Pd.',
            'peran' => 'guru',
            'aktif' => true,
        ]);

        $this->kelas = Kelas::create([
            'nama' => '2B',
            'tahun_ajaran' => '2026/2027',
            'wali_id' => $this->guru->id,
        ]);

        $this->siswa1 = Siswa::create([
            'nis' => '4001',
            'nama' => 'Farhan Pratama',
            'jenis_kelamin' => 'L',
            'kelas_id' => $this->kelas->id,
            'nama_ortu' => 'Bpk. Pratama',
            'no_wa' => '081234567890',
            'aktif' => true,
        ]);

        $this->siswa2 = Siswa::create([
            'nis' => '4002',
            'nama' => 'Gita Gutawa',
            'jenis_kelamin' => 'P',
            'kelas_id' => $this->kelas->id,
            'nama_ortu' => 'Ibu Gutawa',
            'no_wa' => '081298765432',
            'aktif' => true,
        ]);
    }

    /**
     * Test format template pesan dan pembentukan URL wa.me melalui service class.
     */
    public function test_whatsapp_service_formats_message_and_url(): void
    {
        $template = 'Halo {nama_ortu}, ananda {nama_siswa} kelas {nama_kelas} hari ini {status}{catatan}. Salam, {nama_guru}';
        $vars = [
            'nama_ortu' => 'Bpk. Pratama',
            'nama_siswa' => 'Farhan Pratama',
            'nama_kelas' => '2B',
            'status' => 'Sakit',
            'catatan' => 'Demam',
            'nama_guru' => 'Budi Raharjo',
        ];

        $pesan = $this->sender->formatMessage($template, $vars);
        $this->assertEquals('Halo Bpk. Pratama, ananda Farhan Pratama kelas 2B hari ini Sakit (Demam). Salam, Budi Raharjo', $pesan);

        $url = $this->sender->generateUrl('081234567890', $pesan);
        $this->assertStringStartsWith('https://wa.me/6281234567890?text=', $url);
        $this->assertStringContainsString(rawurlencode('Halo Bpk. Pratama'), $url);
    }

    /**
     * Test status awal absensi dan menandai status WA sudah dikirim.
     */
    public function test_mark_wa_status_as_sent_with_timestamp(): void
    {
        $absensi = Absensi::create([
            'siswa_id' => $this->siswa1->id,
            'tanggal' => Carbon::today()->format('Y-m-d'),
            'status' => 'S',
            'catatan' => 'Sakit perut',
            'dicatat_oleh' => $this->guru->id,
            'wa_status' => 'belum_dihubungi',
            'wa_dikirim_pada' => null,
        ]);

        $this->assertEquals('belum_dihubungi', $absensi->wa_status);
        $this->assertNull($absensi->wa_dikirim_pada);

        $response = $this->actingAs($this->guru)
            ->postJson(route('absensi.tandai-wa', $absensi));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'wa_status' => 'sudah_dikirim',
            ]);

        $absensi->refresh();
        $this->assertEquals('sudah_dikirim', $absensi->wa_status);
        $this->assertNotNull($absensi->wa_dikirim_pada);
    }

    /**
     * Test kustomisasi template WhatsApp per guru.
     */
    public function test_teacher_can_save_custom_wa_template(): void
    {
        $newTemplate = 'Yth. {nama_ortu}, ananda {nama_siswa} tercatat {status}. Mohon segera info ke wali kelas.';

        $response = $this->actingAs($this->guru)
            ->postJson(route('profil.template-wa'), [
                'template_wa' => $newTemplate,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'template_wa' => $newTemplate,
            ]);

        $this->guru->refresh();
        $this->assertEquals($newTemplate, $this->guru->template_wa);
    }

    /**
     * Test perhitungan metrik "N siswa belum dihubungi hari ini" pada respon AbsensiController.
     */
    public function test_uncontacted_students_count_and_percentage(): void
    {
        $today = Carbon::today()->format('Y-m-d');

        // Siswa 1: Sakit, belum dihubungi
        Absensi::create([
            'siswa_id' => $this->siswa1->id,
            'tanggal' => $today,
            'status' => 'S',
            'catatan' => 'Flu',
            'dicatat_oleh' => $this->guru->id,
            'wa_status' => 'belum_dihubungi',
        ]);

        // Siswa 2: Izin, sudah dikirim
        Absensi::create([
            'siswa_id' => $this->siswa2->id,
            'tanggal' => $today,
            'status' => 'I',
            'catatan' => 'Acara keluarga',
            'dicatat_oleh' => $this->guru->id,
            'wa_status' => 'sudah_dikirim',
            'wa_dikirim_pada' => Carbon::now(),
        ]);

        $response = $this->actingAs($this->guru)
            ->get(route('absensi.index', [
                'kelas_id' => $this->kelas->id,
                'tanggal' => $today,
            ]));

        $response->assertStatus(200);

        // Periksa props yang diteruskan ke Inertia
        $response->assertInertia(function ($page) {
            $page->component('Absensi/Index')
                ->has('waStats', function ($stats) {
                    $stats->where('belum_dihubungi', 1)
                        ->where('sudah_dikirim', 1)
                        ->where('total_tidak_hadir', 2)
                        ->where('persentase', 50);
                })
                ->has('antreanWa', 2);
        });
    }

    /**
     * Test otorisasi: Guru dilarang menandai status WA untuk absensi kelas orang lain.
     */
    public function test_guru_cannot_mark_wa_for_other_class(): void
    {
        $guruLain = User::factory()->create([
            'username' => 'guru_lain',
            'peran' => 'guru',
            'aktif' => true,
        ]);

        $absensi = Absensi::create([
            'siswa_id' => $this->siswa1->id,
            'tanggal' => Carbon::today()->format('Y-m-d'),
            'status' => 'A',
            'dicatat_oleh' => $this->guru->id,
        ]);

        $response = $this->actingAs($guruLain)
            ->postJson(route('absensi.tandai-wa', $absensi));

        $response->assertStatus(403);
    }
}
