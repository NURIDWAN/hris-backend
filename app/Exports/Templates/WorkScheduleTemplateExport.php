<?php

namespace App\Exports\Templates;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WorkScheduleTemplateExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles
{
    public function headings(): array
    {
        return [
            'Nama',
            'Kode',
            'Jam Masuk',
            'Jam Keluar',
            'Jam Istirahat Mulai',
            'Jam Istirahat Selesai',
            'Durasi Istirahat',
            'Hari Kerja',
            'Toleransi Terlambat',
            'Toleransi Pulang Awal',
            'Fleksibel',
            'Default',
            'Aktif',
            'Deskripsi',
        ];
    }

    public function array(): array
    {
        return [
            ['Shift Pagi', 'PAGI', '08:00', '17:00', '12:00', '13:00', 60, '1,2,3,4,5', 15, 0, 'Tidak', 'Ya', 'Ya', 'Jadwal kerja reguler pagi'],
            ['Shift Siang', 'SIANG', '14:00', '22:00', '18:00', '19:00', 60, '1,2,3,4,5', 15, 0, 'Tidak', 'Tidak', 'Ya', 'Jadwal kerja siang'],
            ['Shift Malam', 'MALAM', '22:00', '06:00', '02:00', '03:00', 60, '1,2,3,4,5', 15, 0, 'Tidak', 'Tidak', 'Ya', 'Jadwal kerja malam'],
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
