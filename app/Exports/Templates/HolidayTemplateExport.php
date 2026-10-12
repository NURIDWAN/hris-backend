<?php

namespace App\Exports\Templates;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class HolidayTemplateExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles
{
    public function headings(): array
    {
        return [
            'Nama',
            'Tanggal',
            'Jenis',
            'Berulang',
            'Aktif',
            'Deskripsi',
        ];
    }

    public function array(): array
    {
        return [
            ['Tahun Baru', '2026-01-01', 'Nasional', 'Ya', 'Ya', 'Perayaan Tahun Baru Masehi'],
            ['Hari Buruh', '2026-05-01', 'Nasional', 'Ya', 'Ya', 'Hari Buruh Internasional'],
            ['Hari Pancasila', '2026-06-01', 'Nasional', 'Ya', 'Ya', 'Hari Lahir Pancasila'],
            ['Hari Kemerdekaan', '2026-08-17', 'Nasional', 'Ya', 'Ya', 'Hari Kemerdekaan Republik Indonesia'],
            ['Hari Raya Idul Fitri', '2026-03-30', 'Keagamaan', 'Tidak', 'Ya', 'Hari Raya Idul Fitri 1 Syawal'],
            ['Hari Raya Idul Adha', '2026-06-07', 'Keagamaan', 'Tidak', 'Ya', 'Hari Raya Idul Adha'],
            ['Hari Natal', '2026-12-25', 'Keagamaan', 'Ya', 'Ya', 'Hari Raya Natal'],
            ['Cuti Bersama Lebaran', '2026-03-31', 'Perusahaan', 'Tidak', 'Ya', 'Cuti Bersama Idul Fitri'],
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
