<?php

namespace App\Exports\Templates;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PositionTemplateExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles
{
    public function headings(): array
    {
        return [
            'Nama',
            'Kode',
            'Kode Departemen',
            'Level',
            'Gaji Pokok',
            'Deskripsi',
            'Aktif',
        ];
    }

    public function array(): array
    {
        return [
            ['Software Engineer', 'SE', 'IT', 3, 10000000, 'Pengembang Software', 'Ya'],
            ['Senior Software Engineer', 'SSE', 'IT', 4, 15000000, 'Pengembang Software Senior', 'Ya'],
            ['HR Manager', 'HRM', 'HR', 5, 12000000, 'Manager SDM', 'Ya'],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5'],
                ],
                'font' => [
                    'color' => ['rgb' => 'FFFFFF'],
                    'bold' => true,
                ],
            ],
        ];
    }
}
