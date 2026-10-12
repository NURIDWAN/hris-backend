<?php

namespace App\Exports;

use App\Models\Payroll;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PayrollBankExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected int $rowNumber = 0;

    protected float $totalAmount = 0;

    public function __construct(
        protected Payroll $payroll
    ) {}

    public function collection(): Collection
    {
        return $this->payroll->items()
            ->with(['employee.currentSalary'])
            ->orderBy('employee_name')
            ->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama Bank',
            'No Rekening',
            'Nama Pemilik Rekening',
            'Jumlah (Rp)',
            'Keterangan',
        ];
    }

    public function map($item): array
    {
        $this->rowNumber++;
        $this->totalAmount += (float) $item->net_salary;

        // Fallback to employee_salaries bank data if payroll_item has no bank info
        $salary = $item->employee?->currentSalary;
        $bankName = $item->bank_name ?? $salary?->bank_name ?? '-';
        $bankAccount = $item->bank_account_number ?? $salary?->bank_account_number ?? '-';
        $bankAccountName = $item->bank_account_name ?? $salary?->bank_account_name ?? $item->employee_name;

        return [
            $this->rowNumber,
            $bankName,
            $bankAccount,
            $bankAccountName,
            (float) $item->net_salary,
            "Gaji {$this->getMonthName($this->payroll->period_month)} {$this->payroll->period_year} - {$item->employee_name}",
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        // Get total rows (header + data)
        $lastRow = $this->rowNumber + 1;

        // Add total row
        $totalRow = $lastRow + 1;
        $sheet->setCellValue("A{$totalRow}", '');
        $sheet->setCellValue("B{$totalRow}", '');
        $sheet->setCellValue("C{$totalRow}", '');
        $sheet->setCellValue("D{$totalRow}", 'TOTAL');
        $sheet->setCellValue("E{$totalRow}", $this->totalAmount);
        $sheet->setCellValue("F{$totalRow}", '');

        return [
            // Header row styling
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2563EB'],
                ],
                'alignment' => ['horizontal' => 'center'],
            ],
            // Total row styling
            $totalRow => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F3F4F6'],
                ],
            ],
            // Amount column number format
            "E2:E{$totalRow}" => [
                'numberFormat' => [
                    'formatCode' => '#,##0',
                ],
                'alignment' => ['horizontal' => 'right'],
            ],
        ];
    }

    public function title(): string
    {
        return 'Bank Transfer';
    }

    protected function getMonthName(int $month): string
    {
        $months = [
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

        return $months[$month] ?? '';
    }

    public function getFilename(): string
    {
        $month = strtolower($this->getMonthName($this->payroll->period_month));

        return "bank_transfer_{$month}_{$this->payroll->period_year}.xlsx";
    }
}
