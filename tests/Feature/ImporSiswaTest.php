<?php

namespace Tests\Feature;

use App\Imports\SiswaImportParser;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImporSiswaTest extends TestCase
{
    use RefreshDatabase;

    private User $guru;
    private Kelas $kelas;

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
    }

    /**
     * Test download template impor siswa.
     */
    public function test_template_impor_siswa_can_be_downloaded(): void
    {
        $response = $this->actingAs($this->guru)
            ->get(route('siswa.import.template'));

        $response->assertStatus(200);
        $this->assertTrue($response->headers->get('content-disposition') !== null);
    }

    /**
     * Test normalisasi nomor WhatsApp format campur ke format 62xxxxxxxxxx.
     */
    public function test_whatsapp_number_normalization(): void
    {
        $this->assertEquals('6281234567890', SiswaImportParser::normalizeWhatsApp('081234567890'));
        $this->assertEquals('6281234567890', SiswaImportParser::normalizeWhatsApp('+62 812-3456-7890'));
        $this->assertEquals('6281234567890', SiswaImportParser::normalizeWhatsApp('0812 3456 7890'));
        $this->assertEquals('6281234567890', SiswaImportParser::normalizeWhatsApp('6281234567890'));
        $this->assertEquals('6281234567890', SiswaImportParser::normalizeWhatsApp('81234567890'));
    }

    /**
     * Test pratinjau file impor valid.
     */
    public function test_preview_import_with_valid_file(): void
    {
        $csvContent = "NIS,Nama,Jenis Kelamin,Nama Orang Tua,No WhatsApp\n";
        $csvContent .= "2001,Ahmad Maulana,L,Bpk. Maulana,081234567890\n";
        $csvContent .= "2002,Siti Aminah,P,Ibu Aminah,+6281298765432\n";

        $file = UploadedFile::fake()->createWithContent('siswa.csv', $csvContent);

        $response = $this->actingAs($this->guru)
            ->post(route('siswa.import.preview'), [
                'kelas_id' => $this->kelas->id,
                'file' => $file,
            ], ['Accept' => 'application/json']);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'total_rows' => 2,
            ])
            ->assertJsonCount(2, 'valid_rows')
            ->assertJsonCount(0, 'invalid_rows');

        $validRows = $response->json('valid_rows');
        $this->assertEquals('6281234567890', $validRows[0]['no_wa']);
        $this->assertEquals('6281298765432', $validRows[1]['no_wa']);
    }

    /**
     * Test pratinjau dengan baris bermasalah (NIS duplikat, jenis kelamin salah, nama kosong).
     * Baris yang salah tidak membatalkan baris yang benar.
     */
    public function test_preview_import_with_duplicate_and_invalid_rows(): void
    {
        // Masukkan satu siswa yang sudah ada di database
        Siswa::create([
            'nis' => '9999',
            'nama' => 'Siswa Lama',
            'jenis_kelamin' => 'L',
            'kelas_id' => $this->kelas->id,
            'nama_ortu' => 'Ortu Lama',
            'no_wa' => '628111111111',
            'aktif' => true,
        ]);

        $csvContent = "NIS,Nama,Jenis Kelamin,Nama Orang Tua,No WhatsApp\n";
        // Baris 1: Valid
        $csvContent .= "2003,Budi Santoso,L,Bpk. Santoso,081233334444\n";
        // Baris 2: NIS sudah ada di DB
        $csvContent .= "9999,Duplikat DB,L,Ortu,081244445555\n";
        // Baris 3: Jenis kelamin salah
        $csvContent .= "2004,Dewi Sartika,X,Ibu Sartika,081255556666\n";
        // Baris 4: Duplikat dengan baris 1 di dalam file
        $csvContent .= "2003,Kembar File,L,Ortu Kembar,081266667777\n";

        $file = UploadedFile::fake()->createWithContent('siswa_campur.csv', $csvContent);

        $response = $this->actingAs($this->guru)
            ->post(route('siswa.import.preview'), [
                'kelas_id' => $this->kelas->id,
                'file' => $file,
            ], ['Accept' => 'application/json']);

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('valid_rows')); // Hanya Budi Santoso
        $this->assertCount(3, $response->json('invalid_rows')); // 3 baris salah
    }

    /**
     * Test konfirmasi simpan impor dan opsi pembuatan akun orang tua otomatis dengan PIN awal 6 digit.
     */
    public function test_store_import_creates_students_and_parent_accounts(): void
    {
        $rows = [
            [
                'nis' => '3001',
                'nama' => 'Chandra Wijaya',
                'jenis_kelamin' => 'L',
                'nama_ortu' => 'Bpk. Wijaya',
                'no_wa' => '6281234567890',
            ],
            [
                'nis' => '3002',
                'nama' => 'Dina Fitriani',
                'jenis_kelamin' => 'P',
                'nama_ortu' => 'Ibu Fitriani',
                'no_wa' => '6281298765432',
            ],
        ];

        $response = $this->actingAs($this->guru)
            ->postJson(route('siswa.import.store'), [
                'kelas_id' => $this->kelas->id,
                'rows' => $rows,
                'buat_akun_ortu' => true,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'saved_count' => 2,
            ]);

        $this->assertDatabaseHas('siswa', [
            'nis' => '3001',
            'nama' => 'Chandra Wijaya',
            'kelas_id' => $this->kelas->id,
        ]);

        $this->assertDatabaseHas('siswa', [
            'nis' => '3002',
            'nama' => 'Dina Fitriani',
            'kelas_id' => $this->kelas->id,
        ]);

        // Verifikasi akun orang tua terbuat
        $this->assertDatabaseHas('users', [
            'username' => 'ortu_3001',
            'peran' => 'ortu',
        ]);

        $this->assertDatabaseHas('users', [
            'username' => 'ortu_3002',
            'peran' => 'ortu',
        ]);

        // Cek PIN awal 6 digit di respon
        $akunOrtu = $response->json('akun_ortu');
        $this->assertCount(2, $akunOrtu);
        $this->assertEquals(6, strlen($akunOrtu[0]['pin']));
        $this->assertEquals(6, strlen($akunOrtu[1]['pin']));
    }

    /**
     * Test otorisasi: Guru dilarang mengimpor ke kelas orang lain.
     */
    public function test_guru_cannot_import_to_another_class(): void
    {
        $guruLain = User::factory()->create([
            'username' => 'gurulain',
            'peran' => 'guru',
            'aktif' => true,
        ]);

        $file = UploadedFile::fake()->createWithContent('siswa.csv', "NIS,Nama,Jenis Kelamin,Nama Orang Tua,No WhatsApp\n3003,Eko,L,Ortu,081234567890\n");

        $response = $this->actingAs($guruLain)
            ->postJson(route('siswa.import.preview'), [
                'kelas_id' => $this->kelas->id,
                'file' => $file,
            ]);

        $response->assertStatus(403);
    }
}
