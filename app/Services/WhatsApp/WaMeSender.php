<?php

namespace App\Services\WhatsApp;

class WaMeSender implements WhatsAppSenderInterface
{
    public const DEFAULT_TEMPLATE = 'Assalamualaikum Bapak/Ibu {nama_ortu}. Kami informasikan bahwa {nama_siswa} kelas {nama_kelas} pada {tanggal} tercatat {status}{catatan}. Mohon konfirmasinya. Terima kasih. Wali kelas {nama_guru}';

    public function getDefaultTemplate(): string
    {
        return self::DEFAULT_TEMPLATE;
    }

    public function formatMessage(string $template, array $variables): string
    {
        $placeholders = [
            '{nama_ortu}' => $variables['nama_ortu'] ?? 'Wali Murid',
            '{nama_siswa}' => $variables['nama_siswa'] ?? 'Siswa',
            '{nama_kelas}' => $variables['nama_kelas'] ?? '',
            '{tanggal}' => $variables['tanggal'] ?? '',
            '{status}' => $variables['status'] ?? 'Hadir',
            '{catatan}' => !empty($variables['catatan']) ? " ({$variables['catatan']})" : '',
            '{nama_guru}' => $variables['nama_guru'] ?? 'Wali Kelas',
        ];

        return str_replace(array_keys($placeholders), array_values($placeholders), $template);
    }

    public function generateUrl(string $noWa, string $message): string
    {
        // Bersihkan dan normalisasi ke format 62
        $cleaned = preg_replace('/[^0-9]/', '', $noWa);
        if (str_starts_with($cleaned, '0')) {
            $cleaned = '62' . substr($cleaned, 1);
        } elseif (str_starts_with($cleaned, '8')) {
            $cleaned = '62' . $cleaned;
        }

        return 'https://wa.me/' . $cleaned . '?text=' . rawurlencode($message);
    }
}
