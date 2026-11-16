<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RekapAbsensiExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    protected array $data;
    protected string $namaKelas;
    protected string $namaBulan;
    protected int $hariEfektif;

    public function __construct(array $data, string $namaKelas, string $namaBulan, int $hariEfektif)
    {
        $this->data = $data;
        $this->namaKelas = $namaKelas;
        $this->namaBulan = $namaBulan;
        $this->hariEfektif = $hariEfektif;
    }

    public function headings(): array
    {
        return [
            ['REKAP KEHADIRAN SISWA - KELAS ' . strtoupper($this->namaKelas)],
            ['Periode: ' . $this->namaBulan . ' | Hari Efektif: ' . $this->hariEfektif . ' hari'],
            [],
            ['No', 'NIS', 'Nama Siswa', 'L/P', 'Hadir (H)', 'Izin (I)', 'Sakit (S)', 'Alpa (A)', 'Persentase Kehadiran']
        ];
    }

    public function array(): array
    {
        $rows = [];
        foreach ($this->data as $index => $item) {
            $rows[] = [
                $index + 1,
                $item['nis'],
                $item['nama'],
                $item['jenis_kelamin'],
                $item['hadir'],
                $item['izin'],
                $item['sakit'],
                $item['alpa'],
                $item['persentase'] . '%',
            ];
        }
        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $totalRows = count($this->data) + 4;

        // Merge title rows
        $sheet->mergeCells('A1:I1');
        $sheet->mergeCells('A2:I2');

        return [
            1 => [
                'font' => ['bold' => true, 'size' => 14],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => [
                'font' => ['italic' => true, 'size' => 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            4 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0D9488'] // Teal
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            'A4:I' . $totalRows => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CBD5E1'],
                    ],
                ],
            ],
        ];
    }
}
