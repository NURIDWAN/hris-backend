<?php

namespace App\Exports;

use App\Models\Spt1721;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class Spt1721IndukSheet implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    public function __construct(
        protected Spt1721 $spt
    ) {}

    public function array(): array
    {
        return [[
            $this->spt->company_npwp,
            $this->spt->company_name,
            $this->spt->company_address,
            $this->spt->company_phone,
            $this->spt->company_email,
            $this->spt->tax_year,
            $this->spt->spt_type === 'normal' ? 'Normal' : 'Pembetulan',
            $this->spt->pembetulan_ke,
            $this->spt->total_employees,
            $this->spt->total_bruto,
            $this->spt->total_pph21_terutang,
            $this->spt->total_pph21_disetor,
            $this->spt->total_pph21_kurang_lebih,
            $this->spt->signer_name,
            $this->spt->signer_position,
            $this->spt->sign_date?->format('d-m-Y'),
            $this->spt->sign_place,
        ]];
    }

    public function headings(): array
    {
        return [
            'NPWP',
            'Nama Perusahaan',
            'Alamat',
            'Telepon',
            'Email',
            'Tahun Pajak',
            'Jenis SPT',
            'Pembetulan Ke',
            'Total Karyawan',
            'Total Bruto',
            'Total PPh 21 Terutang',
            'Total PPh 21 Disetor',
            'Kurang/Lebih Bayar',
            'Penandatangan',
            'Jabatan',
            'Tanggal',
            'Tempat',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2563EB'],
                ],
                'alignment' => ['horizontal' => 'center'],
            ],
            'J2:M2' => [
                'numberFormat' => ['formatCode' => '#,##0'],
                'alignment' => ['horizontal' => 'right'],
            ],
        ];
    }

    public function title(): string
    {
        return 'Induk';
    }
}
