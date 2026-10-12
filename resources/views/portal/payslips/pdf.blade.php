<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Slip Gaji - {{ $employee->full_name }} - {{ $payrollItem->payroll->period_start->format('M Y') }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #2563eb;
            margin: 0;
            font-size: 24px;
        }
        .header p {
            color: #666;
            margin: 5px 0 0;
        }
        .title {
            text-align: center;
            margin-bottom: 20px;
        }
        .title h2 {
            margin: 0;
            font-size: 18px;
            text-transform: uppercase;
        }
        .title p {
            margin: 5px 0 0;
            color: #666;
        }
        .info-table {
            width: 100%;
            margin-bottom: 20px;
        }
        .info-table td {
            padding: 5px 0;
            vertical-align: top;
        }
        .info-table .label {
            color: #666;
            width: 150px;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #ddd;
        }
        .amount-table {
            width: 100%;
            border-collapse: collapse;
        }
        .amount-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #eee;
        }
        .amount-table .right {
            text-align: right;
        }
        .amount-table .total {
            font-weight: bold;
            background-color: #f8f9fa;
        }
        .net-salary {
            background-color: #2563eb;
            color: white;
            text-align: center;
            padding: 20px;
            margin-top: 20px;
        }
        .net-salary .label {
            font-size: 12px;
            margin-bottom: 5px;
        }
        .net-salary .amount {
            font-size: 24px;
            font-weight: bold;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            color: #666;
            font-size: 10px;
        }
        .success {
            color: #16a34a;
        }
        .danger {
            color: #dc2626;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>GajiPro</h1>
        <p>HRIS & Payroll System</p>
    </div>

    <div class="title">
        <h2>Slip Gaji</h2>
        <p>Periode: {{ $payrollItem->payroll->period_start->format('d M Y') }} - {{ $payrollItem->payroll->period_end->format('d M Y') }}</p>
    </div>

    <table class="info-table">
        <tr>
            <td class="label">Nama Karyawan</td>
            <td>: {{ $employee->full_name }}</td>
            <td class="label">ID Karyawan</td>
            <td>: {{ $employee->employee_id }}</td>
        </tr>
        <tr>
            <td class="label">Departemen</td>
            <td>: {{ $employee->department->name ?? '-' }}</td>
            <td class="label">Jabatan</td>
            <td>: {{ $employee->position->name ?? '-' }}</td>
        </tr>
    </table>

    @if($payrollItem->working_days > 0)
    <div class="section">
        <div class="section-title">Ringkasan Kehadiran</div>
        <table class="amount-table">
            <tr>
                <td>Hari Kerja</td>
                <td class="right">{{ $payrollItem->working_days }} hari</td>
                <td>Hadir</td>
                <td class="right success">{{ $payrollItem->present_days }} hari</td>
            </tr>
            <tr>
                <td>Tidak Hadir</td>
                <td class="right danger">{{ $payrollItem->absent_days }} hari</td>
                <td>Terlambat</td>
                <td class="right" style="color: #ca8a04;">{{ $payrollItem->late_days ?? 0 }} hari</td>
            </tr>
            @if($payrollItem->leave_days > 0 || $payrollItem->overtime_hours > 0)
            <tr>
                @if($payrollItem->leave_days > 0)
                <td>Cuti</td>
                <td class="right">{{ $payrollItem->leave_days }} hari</td>
                @else
                <td></td>
                <td></td>
                @endif
                @if($payrollItem->overtime_hours > 0)
                <td>Lembur</td>
                <td class="right">{{ $payrollItem->overtime_hours }} jam</td>
                @else
                <td></td>
                <td></td>
                @endif
            </tr>
            @endif
        </table>
    </div>
    @endif

    <div class="section">
        <div class="section-title">Penghasilan</div>
        <table class="amount-table">
            <tr>
                <td>Gaji Pokok</td>
                <td class="right">Rp {{ number_format($payrollItem->basic_salary ?? $payrollItem->gross_salary, 0, ',', '.') }}</td>
            </tr>
            @if($payrollItem->allowances && is_array($payrollItem->allowances))
                @foreach($payrollItem->allowances as $name => $amount)
                    <tr>
                        <td>{{ $name }}</td>
                        <td class="right">Rp {{ number_format($amount, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            @endif
            <tr class="total">
                <td>Total Penghasilan</td>
                <td class="right success">Rp {{ number_format($payrollItem->gross_salary, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Potongan</div>
        <table class="amount-table">
            @if($payrollItem->deduction_details && is_array($payrollItem->deduction_details))
                @foreach($payrollItem->deduction_details as $name => $amount)
                    <tr>
                        <td>{{ $name }}</td>
                        <td class="right danger">-Rp {{ number_format($amount, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td>Total Potongan</td>
                    <td class="right danger">-Rp {{ number_format($payrollItem->total_deductions, 0, ',', '.') }}</td>
                </tr>
            @endif
            <tr class="total">
                <td>Total Potongan</td>
                <td class="right danger">-Rp {{ number_format($payrollItem->total_deductions, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <div class="net-salary">
        <div class="label">Gaji Bersih (Take Home Pay)</div>
        <div class="amount">Rp {{ number_format($payrollItem->net_salary, 0, ',', '.') }}</div>
    </div>

    <div class="footer">
        <p>Dokumen ini digenerate secara otomatis oleh sistem GajiPro.</p>
        <p>Dicetak pada: {{ now()->format('d M Y H:i') }}</p>
    </div>
</body>
</html>
