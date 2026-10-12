<?php

namespace App\Exports;

use App\Models\Spt1721;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class Spt1721LampiranISheet implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected array $monthNames = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    public function __construct(
        protected Spt1721 $spt
    ) {}

    public function collection(): Collection
    {
        return $this->spt->monthlyData()->orderBy('month')->get();
    }

    public function headings(): array
    {
        return [
            'NPWP Pemotong',
            'Tahun Pajak',
            'Masa Pajak',
            'Bulan',
            'Jumlah Penerima',
            'Jumlah Ber-NPWP',
            'Jumlah Non-NPWP',
            'Bruto Pegawai Tetap',
            'PPh 21 Pegawai Tetap',
            'PPh 21 Disetor',
            'NTPN',
            'Tanggal Setor',
        ];
    }

    public function map($monthly): array
    {
        return [
            $this->spt->company_npwp,
            $this->spt->tax_year,
            $monthly->month,
            $this->monthNames[$monthly->month] ?? '',
            $monthly->employee_count,
            $monthly->employee_count_npwp,
            $monthly->employee_count_non_npwp,
            $monthly->bruto_pegawai_tetap,
            $monthly->pph21_pegawai_tetap,
            $monthly->pph21_disetor,
            $monthly->ssp_ntpn,
            $monthly->ssp_date?->format('d-m-Y'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $this->spt->monthlyData()->count() + 1;

        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2563EB'],
                ],
                'alignment' => ['horizontal' => 'center'],
            ],
            "H2:J{$lastRow}" => [
                'numberFormat' => ['formatCode' => '#,##0'],
                'alignment' => ['horizontal' => 'right'],
            ],
        ];
    }

    public function title(): string
    {
        return 'Lampiran I - Bulanan';
    }
}
