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

class Spt1721LampiranIISheet implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected int $rowNumber = 0;

    public function __construct(
        protected Spt1721 $spt
    ) {}

    public function collection(): Collection
    {
        return $this->spt->employees()->orderBy('employee_name')->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'NPWP Pemotong',
            'Tahun Pajak',
            'NPWP Penerima',
            'NIK',
            'Nama',
            'Status PTKP',
            'Masa Awal',
            'Masa Akhir',
            'Bruto',
            'Neto',
            'PTKP',
            'PKP',
            'PPh 21 Terutang',
            'PPh 21 Dipotong',
            'Kurang/Lebih',
            'Jenis Bukpot',
        ];
    }

    public function map($employee): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $this->spt->company_npwp,
            $this->spt->tax_year,
            $employee->employee_npwp,
            $employee->employee_nik,
            $employee->employee_name,
            $employee->ptkp_status,
            $employee->work_start_month,
            $employee->work_end_month,
            $employee->gross_salary,
            $employee->neto_salary,
            $employee->ptkp,
            $employee->pkp,
            $employee->pph21_terutang,
            $employee->pph21_telah_dipotong,
            $employee->pph21_kurang_lebih,
            $employee->employee_type === 'tetap' ? '1721-A1' : '1721-A2',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $this->spt->employees()->count() + 1;

        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2563EB'],
                ],
                'alignment' => ['horizontal' => 'center'],
            ],
            "J2:P{$lastRow}" => [
                'numberFormat' => ['formatCode' => '#,##0'],
                'alignment' => ['horizontal' => 'right'],
            ],
        ];
    }

    public function title(): string
    {
        return 'Lampiran II - Bukti Potong';
    }
}
