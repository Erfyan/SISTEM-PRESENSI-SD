<?php

namespace Tests\Unit;

use App\Services\WhatsApp\WaMeSender;
use PHPUnit\Framework\TestCase;

class WaMeSenderTest extends TestCase
{
    private WaMeSender $sender;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sender = new WaMeSender();
    }

    public function test_get_default_template_contains_all_required_placeholders(): void
    {
        $template = $this->sender->getDefaultTemplate();

        $this->assertStringContainsString('{nama_ortu}', $template);
        $this->assertStringContainsString('{nama_siswa}', $template);
        $this->assertStringContainsString('{nama_kelas}', $template);
        $this->assertStringContainsString('{tanggal}', $template);
        $this->assertStringContainsString('{status}', $template);
        $this->assertStringContainsString('{catatan}', $template);
        $this->assertStringContainsString('{nama_guru}', $template);
    }

    public function test_format_message_replaces_placeholders(): void
    {
        $template = 'Halo {nama_ortu}, {nama_siswa} kelas {nama_kelas} pada {tanggal} {status}{catatan}. Salam, {nama_guru}';
        $variables = [
            'nama_ortu' => 'Bpk. Joko',
            'nama_siswa' => 'Rizky',
            'nama_kelas' => '1A',
            'tanggal' => '16 November 2026',
            'status' => 'Sakit',
            'catatan' => 'Demam',
            'nama_guru' => 'Bu Siti',
        ];

        $result = $this->sender->formatMessage($template, $variables);

        $this->assertEquals(
            'Halo Bpk. Joko, Rizky kelas 1A pada 16 November 2026 Sakit (Demam). Salam, Bu Siti',
            $result
        );
    }

    public function test_format_message_handles_empty_notes(): void
    {
        $template = '{nama_siswa} {status}{catatan}';
        $variables = [
            'nama_siswa' => 'Rizky',
            'status' => 'Hadir',
            'catatan' => null,
        ];

        $result = $this->sender->formatMessage($template, $variables);

        $this->assertEquals('Rizky Hadir', $result);
    }

    public function test_generate_url_normalizes_indonesian_phone_numbers(): void
    {
        // 08xx -> 628xx
        $url1 = $this->sender->generateUrl('081234567890', 'Halo');
        $this->assertStringStartsWith('https://wa.me/6281234567890?text=', $url1);

        // 8xx -> 628xx
        $url2 = $this->sender->generateUrl('81234567890', 'Halo');
        $this->assertStringStartsWith('https://wa.me/6281234567890?text=', $url2);

        // 628xx -> 628xx
        $url3 = $this->sender->generateUrl('6281234567890', 'Halo');
        $this->assertStringStartsWith('https://wa.me/6281234567890?text=', $url3);

        // with dashes and spaces
        $url4 = $this->sender->generateUrl('+62 812-3456-7890', 'Halo');
        $this->assertStringStartsWith('https://wa.me/6281234567890?text=', $url4);
    }

    public function test_generate_url_encodes_special_characters_in_message(): void
    {
        $message = "Assalamualaikum Bapak/Ibu,\nAnak Anda tercatat Sakit (Demam).";
        $url = $this->sender->generateUrl('08123456789', $message);

        $this->assertEquals('https://wa.me/628123456789?text=' . rawurlencode($message), $url);
    }
}
