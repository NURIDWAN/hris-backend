<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Penggajian</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #333;
        }
        .company-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .report-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .report-period {
            font-size: 10px;
            color: #666;
        }
        .meta-info {
            margin-bottom: 15px;
            font-size: 9px;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px 8px;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
            font-size: 8px;
            text-transform: uppercase;
        }
        td {
            font-size: 8px;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #999;
            padding: 10px;
            border-top: 1px solid #ddd;
        }
        .summary-row {
            background-color: #f9f9f9;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $company->name ?? 'Perusahaan' }}</div>
        <div class="report-title">LAPORAN PENGGAJIAN</div>
        @php
            $months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        @endphp
        <div class="report-period">Periode: {{ $months[$month - 1] }} {{ $year }}</div>
    </div>

    <div class="meta-info">
        Dicetak pada: {{ now()->format('d M Y H:i') }}
        @if($payroll)
            | Status: {{ ucfirst($payroll->status) }}
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 25px;">No</th>
                <th>Nama Karyawan</th>
                <th>Departemen</th>
                <th class="text-right">Gaji Pokok</th>
                <th class="text-right">Tunjangan</th>
                <th class="text-right">Gaji Kotor</th>
                <th class="text-right">Potongan</th>
                <th class="text-right">PPh 21</th>
                <th class="text-right">Gaji Bersih</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalBase = 0;
                $totalAllowances = 0;
                $totalGross = 0;
                $totalDeductions = 0;
                $totalPph21 = 0;
                $totalNet = 0;
            @endphp
            @forelse($payrollItems as $index => $item)
                @php
                    $totalBase += $item->base_salary;
                    $totalAllowances += $item->total_allowances;
                    $totalGross += $item->gross_salary;
                    $totalDeductions += $item->total_deductions;
                    $totalPph21 += $item->pph21;
                    $totalNet += $item->net_salary;
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item->employee->full_name ?? '-' }}</td>
                    <td>{{ $item->employee->department?->name ?? '-' }}</td>
                    <td class="text-right">{{ number_format($item->base_salary, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($item->total_allowances, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($item->gross_salary, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($item->total_deductions, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($item->pph21, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($item->net_salary, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">Tidak ada data penggajian</td>
                </tr>
            @endforelse
        </tbody>
        @if($payrollItems->count() > 0)
            <tfoot>
                <tr class="summary-row">
                    <td colspan="3" class="text-right">TOTAL</td>
                    <td class="text-right">{{ number_format($totalBase, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($totalAllowances, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($totalGross, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($totalDeductions, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($totalPph21, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($totalNet, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <div class="footer">
        Halaman {PAGE_NUM} dari {PAGE_COUNT} | Laporan Penggajian {{ $company->name ?? '' }} | Dicetak: {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
