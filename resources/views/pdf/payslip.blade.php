<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Slip Gaji - {{ $employee->full_name }} - {{ $payroll->period_label }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            background: #fff;
        }
        .container {
            padding: 30px;
            max-width: 800px;
            margin: 0 auto;
        }

        /* Header */
        .header {
            border-bottom: 3px solid #3B82F6;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }
        .header-content {
            display: table;
            width: 100%;
        }
        .company-info {
            display: table-cell;
            vertical-align: top;
            width: 70%;
        }
        .company-name {
            font-size: 22px;
            font-weight: bold;
            color: #1E3A8A;
            margin-bottom: 5px;
        }
        .company-address {
            font-size: 10px;
            color: #666;
            line-height: 1.5;
        }
        .slip-info {
            display: table-cell;
            vertical-align: top;
            text-align: right;
            width: 30%;
        }
        .slip-title {
            font-size: 18px;
            font-weight: bold;
            color: #3B82F6;
            margin-bottom: 8px;
        }
        .slip-period {
            font-size: 14px;
            font-weight: bold;
            color: #333;
        }
        .slip-date {
            font-size: 10px;
            color: #666;
            margin-top: 5px;
        }

        /* Employee Info */
        .employee-section {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .employee-grid {
            display: table;
            width: 100%;
        }
        .employee-col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .info-row {
            margin-bottom: 8px;
        }
        .info-label {
            font-size: 9px;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-value {
            font-size: 12px;
            font-weight: 600;
            color: #1E293B;
        }

        /* Tables */
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #1E3A8A;
            padding: 8px 12px;
            background: #EFF6FF;
            border-left: 4px solid #3B82F6;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid #E2E8F0;
        }
        th {
            background: #F8FAFC;
            font-size: 10px;
            font-weight: 600;
            color: #64748B;
            text-transform: uppercase;
        }
        td {
            font-size: 11px;
        }
        .amount {
            text-align: right;
            font-family: 'DejaVu Sans Mono', monospace;
        }
        .earning {
            color: #059669;
        }
        .deduction {
            color: #DC2626;
        }
        .subtotal-row {
            background: #F8FAFC;
            font-weight: bold;
        }
        .subtotal-row td {
            border-top: 2px solid #E2E8F0;
        }

        /* Summary */
        .summary-section {
            background: linear-gradient(135deg, #1E3A8A 0%, #3B82F6 100%);
            border-radius: 8px;
            padding: 20px;
            color: white;
            margin-top: 25px;
        }
        .summary-grid {
            display: table;
            width: 100%;
        }
        .summary-col {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            padding: 10px;
        }
        .summary-col.main {
            border-left: 1px solid rgba(255,255,255,0.2);
            border-right: 1px solid rgba(255,255,255,0.2);
        }
        .summary-label {
            font-size: 9px;
            text-transform: uppercase;
            opacity: 0.8;
            margin-bottom: 5px;
        }
        .summary-value {
            font-size: 16px;
            font-weight: bold;
        }
        .summary-value.large {
            font-size: 22px;
        }

        /* Footer */
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #E2E8F0;
        }
        .footer-grid {
            display: table;
            width: 100%;
        }
        .footer-col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .signature-box {
            text-align: center;
            padding: 20px;
        }
        .signature-line {
            border-bottom: 1px solid #333;
            width: 150px;
            margin: 40px auto 10px;
        }
        .signature-name {
            font-size: 11px;
            font-weight: bold;
        }
        .signature-title {
            font-size: 9px;
            color: #666;
        }

        /* Notes */
        .notes {
            margin-top: 20px;
            padding: 12px;
            background: #FFFBEB;
            border: 1px solid #FCD34D;
            border-radius: 6px;
            font-size: 9px;
            color: #92400E;
        }
        .notes-title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        /* Watermark */
        .confidential {
            text-align: center;
            font-size: 8px;
            color: #94A3B8;
            margin-top: 20px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-content">
                <div class="company-info">
                    <div class="company-name">{{ $company->name }}</div>
                    <div class="company-address">
                        @if($company->address){{ $company->address }}<br>@endif
                        @if($company->phone)Telp: {{ $company->phone }}@endif
                        @if($company->email) | Email: {{ $company->email }}@endif
                    </div>
                </div>
                <div class="slip-info">
                    <div class="slip-title">SLIP GAJI</div>
                    <div class="slip-period">{{ $payroll->period_label }}</div>
                    <div class="slip-date">
                        Tanggal Cetak: {{ now()->format('d M Y') }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Employee Info -->
        <div class="employee-section">
            <div class="employee-grid">
                <div class="employee-col">
                    <div class="info-row">
                        <div class="info-label">Nama Karyawan</div>
                        <div class="info-value">{{ $employee->full_name }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">ID Karyawan</div>
                        <div class="info-value">{{ $employee->employee_id }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Departemen</div>
                        <div class="info-value">{{ $employee->department?->name ?? '-' }}</div>
                    </div>
                </div>
                <div class="employee-col">
                    <div class="info-row">
                        <div class="info-label">Jabatan</div>
                        <div class="info-value">{{ $employee->position?->name ?? '-' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Status Karyawan</div>
                        <div class="info-value">{{ ucfirst($employee->employment_status ?? 'Permanent') }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Tanggal Pembayaran</div>
                        <div class="info-value">{{ $payroll->payment_date?->format('d M Y') ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Earnings -->
        <div class="section">
            <div class="section-title">PENDAPATAN</div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 60%">Komponen</th>
                        <th style="width: 40%" class="amount">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Gaji Pokok</td>
                        <td class="amount earning">Rp {{ number_format($payslip->basic_salary, 0, ',', '.') }}</td>
                    </tr>
                    @foreach($earnings as $item)
                    <tr>
                        <td>{{ $item['name'] }}</td>
                        <td class="amount earning">Rp {{ number_format($item['amount'], 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                    <tr class="subtotal-row">
                        <td><strong>Total Pendapatan</strong></td>
                        <td class="amount earning"><strong>Rp {{ number_format($payslip->total_earnings, 0, ',', '.') }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Deductions -->
        <div class="section">
            <div class="section-title">POTONGAN</div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 60%">Komponen</th>
                        <th style="width: 40%" class="amount">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deductions as $item)
                    <tr>
                        <td>{{ $item['name'] }}</td>
                        <td class="amount deduction">Rp {{ number_format($item['amount'], 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="2" style="text-align: center; color: #666;">Tidak ada potongan</td>
                    </tr>
                    @endforelse
                    <tr class="subtotal-row">
                        <td><strong>Total Potongan</strong></td>
                        <td class="amount deduction"><strong>Rp {{ number_format($payslip->total_deductions, 0, ',', '.') }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Summary -->
        <div class="summary-section">
            <div class="summary-grid">
                <div class="summary-col">
                    <div class="summary-label">Total Pendapatan</div>
                    <div class="summary-value">Rp {{ number_format($payslip->total_earnings, 0, ',', '.') }}</div>
                </div>
                <div class="summary-col main">
                    <div class="summary-label">Take Home Pay</div>
                    <div class="summary-value large">Rp {{ number_format($payslip->net_salary, 0, ',', '.') }}</div>
                </div>
                <div class="summary-col">
                    <div class="summary-label">Total Potongan</div>
                    <div class="summary-value">Rp {{ number_format($payslip->total_deductions, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>

        <!-- Footer with Signatures -->
        <div class="footer">
            <div class="footer-grid">
                <div class="footer-col">
                    <div class="signature-box">
                        <div class="signature-line"></div>
                        <div class="signature-name">HRD Manager</div>
                        <div class="signature-title">Human Resources</div>
                    </div>
                </div>
                <div class="footer-col">
                    <div class="signature-box">
                        <div class="signature-line"></div>
                        <div class="signature-name">{{ $employee->full_name }}</div>
                        <div class="signature-title">Karyawan</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notes -->
        <div class="notes">
            <div class="notes-title">Catatan:</div>
            <ul style="margin-left: 15px; margin-top: 5px;">
                <li>Slip gaji ini diterbitkan secara elektronik dan sah tanpa tanda tangan basah.</li>
                <li>Jika ada pertanyaan mengenai slip gaji ini, silakan hubungi HRD.</li>
                <li>Slip gaji bersifat rahasia dan hanya untuk kepentingan karyawan yang bersangkutan.</li>
            </ul>
        </div>

        <!-- Confidential -->
        <div class="confidential">
            Dokumen ini bersifat rahasia - {{ $company->name }} - {{ now()->format('Y') }}
        </div>
    </div>
</body>
</html>
