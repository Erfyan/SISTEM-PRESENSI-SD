<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TemplateSiswaExport implements FromArray, WithHeadings, WithColumnWidths, WithStyles
{
    public function headings(): array
    {
        return [
            'NIS',
            'Nama',
            'Jenis Kelamin',
            'Nama Orang Tua',
            'No WhatsApp',
        ];
    }

    public function array(): array
    {
        return [
            [
                '1001',
                'Ahmad Dahlan',
                'L',
                'Bpk. Dahlan',
                '081234567890',
            ],
            [
                '1002',
                'Siti Aisyah',
                'P',
                'Ibu Aisyah',
                '081298765432',
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 30,
            'C' => 15,
            'D' => 25,
            'E' => 20,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF0D9488'], // Teal color
                ],
            ],
        ];
    }
}
