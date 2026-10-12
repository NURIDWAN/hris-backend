<?php

namespace App\Exports\Templates;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeeTemplateExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles
{
    public function headings(): array
    {
        return [
            'NIK',
            'Nama Depan',
            'Nama Belakang',
            'Email',
            'Telepon',
            'Jenis Kelamin',
            'Tanggal Lahir',
            'Status Pernikahan',
            'Agama',
            'Golongan Darah',
            'No KTP',
            'Alamat KTP',
            'Alamat',
            'Kota',
            'Provinsi',
            'Kode Pos',
            'Kode Departemen',
            'Kode Jabatan',
            'Kode Jadwal',
            'Tanggal Masuk',
            'Status Karyawan',
            'Tanggal Mulai Kontrak',
            'Tanggal Selesai Kontrak',
            'Gaji Pokok',
            'Nama Bank',
            'Nomor Rekening',
            'Nama Rekening',
            'NPWP',
            'Status Pajak',
            'BPJS Kesehatan',
            'BPJS Ketenagakerjaan',
            'Nama Kontak Darurat',
            'Telepon Kontak Darurat',
            'Hubungan Kontak Darurat',
            'Aktif',
        ];
    }

    public function array(): array
    {
        return [
            [
                'EMP001',
                'John',
                'Doe',
                'john.doe@example.com',
                '081234567890',
                'Laki-laki',
                '1990-01-15',
                'Menikah',
                'Islam',
                'O',
                '1234567890123456',
                'Jl. Contoh No. 123',
                'Jl. Domisili No. 456',
                'Jakarta',
                'DKI Jakarta',
                '12345',
                'IT',
                'SE',
                'PAGI',
                '2023-01-01',
                'Tetap',
                '',
                '',
                10000000,
                'BCA',
                '1234567890',
                'John Doe',
                '12.345.678.9-012.345',
                'K/1',
                '0001234567890',
                '0009876543210',
                'Jane Doe',
                '081234567899',
                'Istri',
                'Ya',
            ],
            [
                'EMP002',
                'Jane',
                'Smith',
                'jane.smith@example.com',
                '081234567891',
                'Perempuan',
                '1992-05-20',
                'Belum Menikah',
                'Kristen',
                'A',
                '1234567890123457',
                'Jl. Sample No. 789',
                'Jl. Tinggal No. 012',
                'Bandung',
                'Jawa Barat',
                '40123',
                'HR',
                'HRM',
                'PAGI',
                '2023-06-01',
                'Kontrak',
                '2023-06-01',
                '2024-06-01',
                8000000,
                'Mandiri',
                '0987654321',
                'Jane Smith',
                '98.765.432.1-098.765',
                'TK/0',
                '0001234567891',
                '0009876543211',
                'John Smith',
                '081234567898',
                'Ayah',
                'Ya',
            ],
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
