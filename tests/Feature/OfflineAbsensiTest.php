<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfflineAbsensiTest extends TestCase
{
    use RefreshDatabase;

    private User $guru;
    private Kelas $kelas;
    private Siswa $siswa1;
    private Siswa $siswa2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guru = User::factory()->create([
            'username' => 'guru1',
            'peran' => 'guru',
            'aktif' => true,
        ]);

        $this->kelas = Kelas::create([
            'nama' => '1A',
            'tahun_ajaran' => '2026/2027',
            'wali_id' => $this->guru->id,
        ]);

        $this->siswa1 = Siswa::create([
            'nis' => '1001',
            'nama' => 'Ahmad Dahlan',
            'jenis_kelamin' => 'L',
            'kelas_id' => $this->kelas->id,
            'nama_ortu' => 'Bpk. Dahlan',
            'no_wa' => '081234567890',
            'aktif' => true,
        ]);

        $this->siswa2 = Siswa::create([
            'nis' => '1002',
            'nama' => 'Budi Santoso',
            'jenis_kelamin' => 'L',
            'kelas_id' => $this->kelas->id,
            'nama_ortu' => 'Bpk. Santoso',
            'no_wa' => '081298765432',
            'aktif' => true,
        ]);
    }

    /**
     * Test simulasi pengisian offline dan sinkronisasi ketika online kembali.
     * dicatat_offline_pada tersimpan, dicatat_pada menggunakan waktu server.
     */
    public function test_offline_attendance_can_be_synced_successfully(): void
    {
        $offlineTime = '2026-11-13 07:30:00';
        $tanggal = '2026-11-13';

        $payload = [
            'kelas_id' => $this->kelas->id,
            'tanggal' => $tanggal,
            'absensi' => [
                [
                    'siswa_id' => $this->siswa1->id,
                    'status' => 'H',
                    'catatan' => null,
                    'dicatat_offline_pada' => $offlineTime,
                ],
                [
                    'siswa_id' => $this->siswa2->id,
                    'status' => 'S',
                    'catatan' => 'Demam panas',
                    'dicatat_offline_pada' => $offlineTime,
                ],
            ],
        ];

        $response = $this->actingAs($this->guru)
            ->postJson(route('absensi.sync'), $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'conflict' => false,
                'synced_count' => 2,
            ]);

        $this->assertDatabaseHas('absensi', [
            'siswa_id' => $this->siswa1->id,
            'tanggal' => $tanggal,
            'status' => 'H',
        ]);

        $this->assertDatabaseHas('absensi', [
            'siswa_id' => $this->siswa2->id,
            'tanggal' => $tanggal,
            'status' => 'S',
            'catatan' => 'Demam panas',
        ]);

        $record = Absensi::where('siswa_id', $this->siswa2->id)->whereDate('tanggal', $tanggal)->first();
        $this->assertNotNull($record->dicatat_offline_pada);
        $this->assertNotNull($record->dicatat_pada);
        $this->assertEquals($this->guru->id, $record->dicatat_oleh);
    }

    /**
     * Test sinkronisasi harus idempotent: kirim ulang payload yang sama
     * tidak menimbulkan duplikat record dan tetap 1 data per siswa+tanggal.
     */
    public function test_sync_is_idempotent_and_does_not_create_duplicates(): void
    {
        $tanggal = '2026-11-13';
        $payload = [
            'kelas_id' => $this->kelas->id,
            'tanggal' => $tanggal,
            'absensi' => [
                [
                    'siswa_id' => $this->siswa1->id,
                    'status' => 'H',
                    'catatan' => null,
                    'dicatat_offline_pada' => '2026-11-13 07:30:00',
                ],
            ],
        ];

        // Pengiriman pertama
        $this->actingAs($this->guru)->postJson(route('absensi.sync'), $payload)->assertStatus(200);

        // Pengiriman kedua (misal koneksi flacky atau retry otomatis)
        $responseSecond = $this->actingAs($this->guru)->postJson(route('absensi.sync'), $payload);
        $responseSecond->assertStatus(200);

        // Pastikan hanya ada 1 record di database untuk siswa1 pada tanggal tersebut
        $count = Absensi::where('siswa_id', $this->siswa1->id)->whereDate('tanggal', $tanggal)->count();
        $this->assertEquals(1, $count);
    }

    /**
     * Test deteksi konflik bila server sudah punya record absensi yang berbeda
     * dari yang dicatat offline. Server harus mengembalikan HTTP 409 dan daftar konflik.
     */
    public function test_sync_detects_conflict_when_server_has_different_data(): void
    {
        $tanggal = '2026-11-13';

        // Server sudah memiliki data (misal diisi dari perangkat lain atau diubah guru piket)
        Absensi::create([
            'siswa_id' => $this->siswa1->id,
            'tanggal' => $tanggal,
            'status' => 'I', // Izin di server
            'catatan' => 'Surat izin keluarga',
            'dicatat_oleh' => $this->guru->id,
            'dicatat_pada' => Carbon::now(),
        ]);

        // Client offline mencoba sinkronisasi dengan status 'H' (Hadir)
        $payload = [
            'kelas_id' => $this->kelas->id,
            'tanggal' => $tanggal,
            'absensi' => [
                [
                    'siswa_id' => $this->siswa1->id,
                    'status' => 'H',
                    'catatan' => null,
                    'dicatat_offline_pada' => '2026-11-13 07:15:00',
                ],
            ],
        ];

        $response = $this->actingAs($this->guru)->postJson(route('absensi.sync'), $payload);

        $response->assertStatus(409)
            ->assertJson([
                'conflict' => true,
            ])
            ->assertJsonStructure([
                'conflicts' => [
                    '*' => [
                        'siswa_id',
                        'nama',
                        'server_status',
                        'server_catatan',
                        'offline_status',
                    ],
                ],
            ]);

        // Pastikan server tidak menimpa diam-diam sebelum guru memilih
        $currentAbsen = Absensi::where('siswa_id', $this->siswa1->id)->whereDate('tanggal', $tanggal)->first();
        $this->assertEquals('I', $currentAbsen->status);
    }

    /**
     * Test resolusi konflik bila guru memilih menggunakan versi offline atau versi server.
     */
    public function test_conflict_can_be_resolved_with_chosen_version(): void
    {
        $tanggal = '2026-11-13';

        Absensi::create([
            'siswa_id' => $this->siswa1->id,
            'tanggal' => $tanggal,
            'status' => 'I',
            'catatan' => 'Izin di server',
            'dicatat_oleh' => $this->guru->id,
            'dicatat_pada' => Carbon::now(),
        ]);

        // Guru memilih 'offline' untuk menimpa versi server dengan data dari HP
        $payloadOffline = [
            'kelas_id' => $this->kelas->id,
            'tanggal' => $tanggal,
            'absensi' => [
                [
                    'siswa_id' => $this->siswa1->id,
                    'status' => 'H',
                    'catatan' => 'Hadir fisik di kelas',
                    'dicatat_offline_pada' => '2026-11-13 07:15:00',
                ],
            ],
            'resolve_conflicts' => 'offline',
        ];

        $response = $this->actingAs($this->guru)->postJson(route('absensi.sync'), $payloadOffline);
        $response->assertStatus(200);

        $absenUpdated = Absensi::where('siswa_id', $this->siswa1->id)->whereDate('tanggal', $tanggal)->first();
        $this->assertEquals('H', $absenUpdated->status);
        $this->assertEquals('Hadir fisik di kelas', $absenUpdated->catatan);

        // Jika resolusi memilih 'server', perubahan tidak menimpa server
        $payloadServer = [
            'kelas_id' => $this->kelas->id,
            'tanggal' => $tanggal,
            'absensi' => [
                [
                    'siswa_id' => $this->siswa1->id,
                    'status' => 'A', // Client offline ingin ubah ke Alpa
                    'catatan' => 'Alpa',
                    'dicatat_offline_pada' => '2026-11-13 07:15:00',
                ],
            ],
            'resolve_conflicts' => 'server',
        ];

        $responseServer = $this->actingAs($this->guru)->postJson(route('absensi.sync'), $payloadServer);
        $responseServer->assertStatus(200);

        // Status di server tetap 'H' (tidak diubah ke 'A')
        $absenFinal = Absensi::where('siswa_id', $this->siswa1->id)->whereDate('tanggal', $tanggal)->first();
        $this->assertEquals('H', $absenFinal->status);
    }

    /**
     * Test otorisasi: Guru tidak boleh sync kelas lain.
     */
    public function test_guru_cannot_sync_attendance_for_another_class(): void
    {
        $guruLain = User::factory()->create([
            'username' => 'gurulain',
            'peran' => 'guru',
            'aktif' => true,
        ]);

        $payload = [
            'kelas_id' => $this->kelas->id,
            'tanggal' => '2026-11-13',
            'absensi' => [
                [
                    'siswa_id' => $this->siswa1->id,
                    'status' => 'H',
                ],
            ],
        ];

        $response = $this->actingAs($guruLain)->postJson(route('absensi.sync'), $payload);
        $response->assertStatus(403);
    }
}
