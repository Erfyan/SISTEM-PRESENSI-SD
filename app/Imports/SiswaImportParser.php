<?php

namespace App\Imports;

use App\Models\Siswa;
use Maatwebsite\Excel\Facades\Excel;

class SiswaImportParser
{
    public static function normalizeWhatsApp(?string $number): string
    {
        if (!$number) {
            return '';
        }

        // Hapus semua karakter non-angka
        $cleaned = preg_replace('/[^0-9]/', '', (string) $number);

        if (empty($cleaned)) {
            return '';
        }

        // 08... -> 628...
        if (str_starts_with($cleaned, '0')) {
            $cleaned = '62' . substr($cleaned, 1);
        }
        // 8... -> 628...
        elseif (str_starts_with($cleaned, '8')) {
            $cleaned = '62' . $cleaned;
        }

        return $cleaned;
    }

    public static function parse($file): array
    {
        $extension = '';
        if (is_object($file) && method_exists($file, 'getClientOriginalExtension')) {
            $extension = strtolower((string) $file->getClientOriginalExtension());
        }

        $readerType = match ($extension) {
            'csv', 'txt' => \Maatwebsite\Excel\Excel::CSV,
            'xls' => \Maatwebsite\Excel\Excel::XLS,
            default => null,
        };

        $sheets = Excel::toArray([], $file, null, $readerType);
        $rows = $sheets[0] ?? [];

        if (empty($rows)) {
            return [
                'valid_rows' => [],
                'invalid_rows' => [],
                'total_rows' => 0,
            ];
        }

        // Temukan index kolom berdasarkan header di baris 0
        $header = array_map(function ($col) {
            return strtolower(trim((string) $col));
        }, $rows[0]);

        $nisIdx = null;
        $namaIdx = null;
        $jkIdx = null;
        $ortuIdx = null;
        $waIdx = null;

        foreach ($header as $idx => $colName) {
            if (str_contains($colName, 'nis')) {
                $nisIdx = $idx;
            } elseif (str_contains($colName, 'nama siswa') || $colName === 'nama') {
                $namaIdx = $idx;
            } elseif (str_contains($colName, 'kelamin') || str_contains($colName, 'jk') || $colName === 'jenis kelamin') {
                $jkIdx = $idx;
            } elseif (str_contains($colName, 'ortu') || str_contains($colName, 'orang tua') || str_contains($colName, 'wali')) {
                $ortuIdx = $idx;
            } elseif (str_contains($colName, 'wa') || str_contains($colName, 'whatsapp') || str_contains($colName, 'telepon') || str_contains($colName, 'hp')) {
                $waIdx = $idx;
            }
        }

        // Fallback index default jika header tidak dinamai persis
        $nisIdx = $nisIdx ?? 0;
        $namaIdx = $namaIdx ?? 1;
        $jkIdx = $jkIdx ?? 2;
        $ortuIdx = $ortuIdx ?? 3;
        $waIdx = $waIdx ?? 4;

        $validRows = [];
        $invalidRows = [];
        $seenNis = [];

        // Iterasi mulai baris 1 (lewati header)
        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            
            // Lewati jika baris kosong total
            if (empty(array_filter($row, fn($v) => !is_null($v) && trim((string)$v) !== ''))) {
                continue;
            }

            $rawNis = trim((string) ($row[$nisIdx] ?? ''));
            $rawNama = trim((string) ($row[$namaIdx] ?? ''));
            $rawJk = strtoupper(trim((string) ($row[$jkIdx] ?? '')));
            $rawOrtu = trim((string) ($row[$ortuIdx] ?? ''));
            $rawWa = trim((string) ($row[$waIdx] ?? ''));

            $errors = [];

            // 1. Validasi NIS
            if ($rawNis === '') {
                $errors[] = 'NIS tidak boleh kosong.';
            } elseif (in_array($rawNis, $seenNis, true)) {
                $errors[] = "NIS '{$rawNis}' duplikat di dalam file impor ini.";
            } elseif (Siswa::where('nis', $rawNis)->exists()) {
                $errors[] = "NIS '{$rawNis}' sudah terdaftar pada siswa lain di database.";
            }

            // 2. Validasi Nama
            if ($rawNama === '') {
                $errors[] = 'Nama siswa wajib diisi.';
            }

            // 3. Validasi Jenis Kelamin (L/P)
            $jk = null;
            if (in_array($rawJk, ['L', 'LAKI-LAKI', 'LAKI', 'PRIA', 'MALE', 'M'], true)) {
                $jk = 'L';
            } elseif (in_array($rawJk, ['P', 'PEREMPUAN', 'WANITA', 'FEMALE', 'F'], true)) {
                $jk = 'P';
            } else {
                $errors[] = "Jenis kelamin harus 'L' atau 'P' (ditemukan: '{$rawJk}').";
            }

            // 4. Validasi & Normalisasi Nomor WhatsApp
            $normWa = self::normalizeWhatsApp($rawWa);
            if ($rawWa !== '' && (strlen($normWa) < 10 || strlen($normWa) > 16 || !str_starts_with($normWa, '62'))) {
                $errors[] = "Nomor WhatsApp tidak valid ('{$rawWa}'). Harus format nomor aktif (misal 08123456789).";
            }

            $rowData = [
                'row_number' => $i + 1,
                'nis' => $rawNis,
                'nama' => $rawNama,
                'jenis_kelamin' => $jk ?? $rawJk,
                'nama_ortu' => $rawOrtu !== '' ? $rawOrtu : 'Orang Tua ' . $rawNama,
                'no_wa' => $normWa,
            ];

            if (empty($errors)) {
                $seenNis[] = $rawNis;
                $validRows[] = $rowData;
            } else {
                $rowData['errors'] = $errors;
                $invalidRows[] = $rowData;
            }
        }

        return [
            'valid_rows' => $validRows,
            'invalid_rows' => $invalidRows,
            'total_rows' => count($validRows) + count($invalidRows),
        ];
    }
}
