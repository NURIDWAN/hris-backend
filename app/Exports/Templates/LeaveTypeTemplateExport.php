<?php

namespace App\Exports\Templates;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LeaveTypeTemplateExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles
{
    public function headings(): array
    {
        return [
            'Nama',
            'Kode',
            'Jatah Hari',
            'Berbayar',
            'Perlu Persetujuan',
            'Perlu Lampiran',
            'Maksimal Hari Berturut',
            'Minimal Hari Pengajuan',
            'Bisa Dibawa',
            'Maksimal Hari Dibawa',
            'Tahunan',
            'Aktif',
            'Warna',
            'Deskripsi',
        ];
    }

    public function array(): array
    {
        return [
            ['Cuti Tahunan', 'CT', 12, 'Ya', 'Ya', 'Tidak', '', 3, 'Ya', 6, 'Ya', 'Ya', 'primary', 'Cuti tahunan reguler'],
            ['Cuti Sakit', 'CS', 14, 'Ya', 'Ya', 'Ya', '', 0, 'Tidak', '', 'Tidak', 'Ya', 'warning', 'Cuti karena sakit'],
            ['Cuti Melahirkan', 'CM', 90, 'Ya', 'Ya', 'Ya', '', 30, 'Tidak', '', 'Tidak', 'Ya', 'info', 'Cuti melahirkan'],
            ['Cuti Menikah', 'CMN', 3, 'Ya', 'Ya', 'Ya', 3, 7, 'Tidak', '', 'Tidak', 'Ya', 'success', 'Cuti pernikahan'],
            ['Izin Tidak Berbayar', 'ITB', 0, 'Tidak', 'Ya', 'Tidak', '', 1, 'Tidak', '', 'Tidak', 'Ya', 'secondary', 'Izin tanpa gaji'],
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
