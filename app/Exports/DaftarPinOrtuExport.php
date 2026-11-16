<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DaftarPinOrtuExport implements FromArray, WithHeadings, WithColumnWidths, WithStyles
{
    public function __construct(private array $data, private string $namaKelas = '')
    {
    }

    public function headings(): array
    {
        return [
            'NIS',
            'Nama Siswa',
            'Nama Orang Tua',
            'No WhatsApp',
            'Username Login',
            'PIN Awal (6 Digit)',
        ];
    }

    public function array(): array
    {
        return $this->data;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12,
            'B' => 25,
            'C' => 25,
            'D' => 18,
            'E' => 18,
            'F' => 20,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF0D9488'],
                ],
            ],
        ];
    }
}
