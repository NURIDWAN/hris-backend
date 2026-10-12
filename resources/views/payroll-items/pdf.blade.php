<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Slip Gaji - {{ $payrollItem->employee_name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
        }

        .container {
            padding: 20px;
        }

        .header {
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }

        .header-flex {
            display: table;
            width: 100%;
        }

        .header-left {
            display: table-cell;
            width: 60%;
            vertical-align: top;
        }

        .header-right {
            display: table-cell;
            width: 40%;
            text-align: right;
            vertical-align: top;
        }

        .company-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .slip-title {
            font-size: 14px;
            font-weight: bold;
            color: #333;
        }

        .info-section {
            margin-bottom: 15px;
        }

        .info-row {
            display: table;
            width: 100%;
            margin-bottom: 3px;
        }

        .info-label {
            display: table-cell;
            width: 100px;
            color: #666;
        }

        .info-value {
            display: table-cell;
            font-weight: normal;
        }

        .attendance-box {
            background: #f5f5f5;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
        }

        .attendance-title {
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 10px;
            text-transform: uppercase;
            color: #666;
        }

        .attendance-grid {
            display: table;
            width: 100%;
        }

        .attendance-item {
            display: table-cell;
            text-align: center;
            width: 20%;
        }

        .attendance-number {
            font-size: 14px;
            font-weight: bold;
            color: #333;
        }

        .attendance-label {
            font-size: 9px;
            color: #666;
        }

        .salary-section {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }

        .salary-column {
            display: table-cell;
            width: 48%;
            vertical-align: top;
        }

        .salary-column-gap {
            display: table-cell;
            width: 4%;
        }

        .section-title {
            font-size: 10px;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 8px;
            padding-bottom: 5px;
            border-bottom: 1px solid #ddd;
        }

        .section-title.earning {
            color: #22c55e;
        }

        .section-title.deduction {
            color: #ef4444;
        }

        .salary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .salary-table td {
            padding: 4px 0;
            border-bottom: 1px solid #eee;
        }

        .salary-table td:last-child {
            text-align: right;
        }

        .total-row {
            font-weight: bold;
            background: #f5f5f5;
        }

        .total-row.earning {
            color: #22c55e;
        }

        .total-row.deduction {
            color: #ef4444;
        }

        .net-salary-box {
            background: #3b82f6;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .net-salary-flex {
            display: table;
            width: 100%;
        }

        .net-salary-label {
            display: table-cell;
            vertical-align: middle;
        }

        .net-salary-title {
            font-weight: bold;
            font-size: 12px;
        }

        .net-salary-sub {
            font-size: 9px;
            opacity: 0.8;
        }

        .net-salary-value {
            display: table-cell;
            text-align: right;
            vertical-align: middle;
        }

        .net-salary-amount {
            font-size: 20px;
            font-weight: bold;
        }

        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 9px;
        }

        .footer-flex {
            display: table;
            width: 100%;
        }

        .footer-left {
            display: table-cell;
            width: 50%;
        }

        .footer-right {
            display: table-cell;
            width: 50%;
            text-align: right;
        }

        .status-paid {
            color: #22c55e;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        {{-- Header --}}
        <div class="header">
            <div class="header-flex">
                <div class="header-left">
                    <div class="company-name">{{ $company->name ?? 'Nama Perusahaan' }}</div>
                    <div style="color: #666; font-size: 10px;">{{ $company->address ?? 'Alamat Perusahaan' }}</div>
                </div>
                <div class="header-right">
                    <div class="slip-title">SLIP GAJI</div>
                    <div style="color: #666; font-size: 10px;">{{ $payrollItem->payroll->period_label }}</div>
                    <div style="color: #666; font-size: 10px;">{{ $payrollItem->payroll->payroll_number }}</div>
                </div>
            </div>
        </div>

        {{-- Employee Info --}}
        <div class="info-section">
            <div style="display: table; width: 100%;">
                <div style="display: table-cell; width: 50%; vertical-align: top;">
                    <div class="info-row">
                        <div class="info-label">Nama</div>
                        <div class="info-value">: <strong>{{ $payrollItem->employee_name }}</strong></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">NIK</div>
                        <div class="info-value">: {{ $payrollItem->employee_number }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Departemen</div>
                        <div class="info-value">: {{ $payrollItem->department_name ?? '-' }}</div>
                    </div>
                </div>
                <div style="display: table-cell; width: 50%; vertical-align: top;">
                    <div class="info-row">
                        <div class="info-label">Jabatan</div>
                        <div class="info-value">: {{ $payrollItem->position_name ?? '-' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Status</div>
                        <div class="info-value">:
                            @if($payrollItem->isPaid())
                                <span class="status-paid">Dibayar</span>
                            @elseif($payrollItem->isApproved())
                                Disetujui
                            @elseif($payrollItem->isCalculated())
                                Terhitung
                            @else
                                Menunggu
                            @endif
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Periode</div>
                        <div class="info-value">: {{ $payrollItem->payroll->period_start->format('d M') }} - {{ $payrollItem->payroll->period_end->format('d M Y') }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Attendance Summary --}}
        <div class="attendance-box">
            <div style="display: table; width: 100%; margin-bottom: 8px;">
                <div style="display: table-cell;">
                    <div class="attendance-title" style="margin-bottom: 0;">Ringkasan Kehadiran</div>
                </div>
                @if($payrollItem->working_days > 0)
                    @php
                        $attendancePercent = round(($payrollItem->present_days / $payrollItem->working_days) * 100, 1);
                    @endphp
                    <div style="display: table-cell; text-align: right;">
                        <span style="font-size: 10px; font-weight: bold; color: {{ $attendancePercent >= 90 ? '#22c55e' : ($attendancePercent >= 75 ? '#f59e0b' : '#ef4444') }};">
                            {{ $attendancePercent }}% Kehadiran
                        </span>
                    </div>
                @endif
            </div>
            <div class="attendance-grid">
                <div class="attendance-item" style="width: 16.6%;">
                    <div class="attendance-number">{{ $payrollItem->working_days }}</div>
                    <div class="attendance-label">Hari Kerja</div>
                </div>
                <div class="attendance-item" style="width: 16.6%;">
                    <div class="attendance-number" style="color: #22c55e;">{{ $payrollItem->present_days }}</div>
                    <div class="attendance-label">Hadir</div>
                </div>
                <div class="attendance-item" style="width: 16.6%;">
                    <div class="attendance-number" style="color: #ef4444;">{{ $payrollItem->absent_days }}</div>
                    <div class="attendance-label">Tidak Hadir</div>
                </div>
                <div class="attendance-item" style="width: 16.6%;">
                    <div class="attendance-number" style="color: #f59e0b;">{{ $payrollItem->late_days }}</div>
                    <div class="attendance-label">Terlambat</div>
                </div>
                <div class="attendance-item" style="width: 16.6%;">
                    <div class="attendance-number" style="color: #3b82f6;">{{ $payrollItem->leave_days }}</div>
                    <div class="attendance-label">Cuti</div>
                </div>
                <div class="attendance-item" style="width: 16.6%;">
                    <div class="attendance-number" style="color: #8b5cf6;">{{ $payrollItem->overtime_hours }}</div>
                    <div class="attendance-label">Jam Lembur</div>
                </div>
            </div>
        </div>

        {{-- Salary Details --}}
        <div class="salary-section">
            {{-- Pendapatan --}}
            <div class="salary-column">
                <div class="section-title earning">PENDAPATAN</div>
                <table class="salary-table">
                    <tr>
                        <td>Gaji Pokok</td>
                        <td>Rp {{ number_format($payrollItem->basic_salary, 0, ',', '.') }}</td>
                    </tr>
                    @php
                        $earnings = $payrollItem->details->where('type', 'earning')->filter(fn ($e) => $e->component_code !== 'BASIC');
                    @endphp
                    @foreach($earnings as $earning)
                        <tr>
                            <td>{{ $earning->component_name }}</td>
                            <td>Rp {{ number_format($earning->amount, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    <tr class="total-row earning">
                        <td>Total Pendapatan</td>
                        <td>Rp {{ number_format((float)$payrollItem->basic_salary + (float)$payrollItem->total_earnings, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </div>

            <div class="salary-column-gap"></div>

            {{-- Potongan --}}
            <div class="salary-column">
                <div class="section-title deduction">POTONGAN</div>
                <table class="salary-table">
                    @php
                        $deductions = $payrollItem->details->where('type', 'deduction');
                    @endphp
                    @forelse($deductions as $deduction)
                        <tr>
                            <td>{{ $deduction->component_name }}</td>
                            <td>Rp {{ number_format($deduction->amount, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" style="color: #999; font-style: italic;">Tidak ada potongan</td>
                        </tr>
                    @endforelse
                    @if($payrollItem->tax_amount > 0)
                        <tr>
                            <td>PPh 21</td>
                            <td>Rp {{ number_format($payrollItem->tax_amount, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    <tr class="total-row deduction">
                        <td>Total Potongan</td>
                        <td>Rp {{ number_format((float)$payrollItem->total_deductions + (float)$payrollItem->tax_amount, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- Net Salary --}}
        <div class="net-salary-box">
            <div class="net-salary-flex">
                <div class="net-salary-label">
                    <div class="net-salary-title">GAJI BERSIH (Take Home Pay)</div>
                    <div class="net-salary-sub">Diterima melalui {{ $payrollItem->payment_info }}</div>
                </div>
                <div class="net-salary-value">
                    <div class="net-salary-amount">Rp {{ number_format($payrollItem->net_salary, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="footer">
            <div class="footer-flex">
                <div class="footer-left">
                    Dicetak pada: {{ now()->format('d M Y H:i') }}
                </div>
                <div class="footer-right">
                    @if($payrollItem->isPaid())
                        <span class="status-paid">Dibayar pada: {{ $payrollItem->paid_at?->format('d M Y') }}</span>
                    @endif
                </div>
            </div>
            <div style="margin-top: 10px; text-align: center; color: #999;">
                Slip gaji ini digenerate secara otomatis dan sah tanpa tanda tangan.
            </div>
        </div>
    </div>
</body>
</html>
