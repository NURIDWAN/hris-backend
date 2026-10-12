<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Kehadiran</title>
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
            font-size: 9px;
            text-transform: uppercase;
        }
        td {
            font-size: 9px;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .status-on-time {
            color: #059669;
            font-weight: bold;
        }
        .status-late {
            color: #d97706;
            font-weight: bold;
        }
        .status-very-late {
            color: #dc2626;
            font-weight: bold;
        }
        .status-absent {
            color: #6b7280;
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
        .page-break {
            page-break-after: always;
        }
        .summary-box {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 15px;
        }
        .summary-title {
            font-weight: bold;
            margin-bottom: 8px;
        }
        .summary-grid {
            display: table;
            width: 100%;
        }
        .summary-item {
            display: table-cell;
            width: 16.66%;
            text-align: center;
            padding: 5px;
        }
        .summary-value {
            font-size: 14px;
            font-weight: bold;
            color: #333;
        }
        .summary-label {
            font-size: 8px;
            color: #666;
            margin-top: 2px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $company->name ?? 'Perusahaan' }}</div>
        <div class="report-title">LAPORAN KEHADIRAN KARYAWAN</div>
        <div class="report-period">Periode: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</div>
    </div>

    <div class="meta-info">
        Dicetak pada: {{ now()->format('d M Y H:i') }}
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 30px;">No</th>
                <th style="width: 70px;">Tanggal</th>
                <th>Nama Karyawan</th>
                <th>Departemen</th>
                <th class="text-center" style="width: 50px;">Masuk</th>
                <th class="text-center" style="width: 50px;">Pulang</th>
                <th class="text-center" style="width: 80px;">Status</th>
                <th class="text-right" style="width: 60px;">Terlambat</th>
                <th class="text-right" style="width: 50px;">Lembur</th>
            </tr>
        </thead>
        <tbody>
            @forelse($attendances as $index => $attendance)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $attendance->date->format('d/m/Y') }}</td>
                    <td>{{ $attendance->employee->full_name ?? '-' }}</td>
                    <td>{{ $attendance->employee->department?->name ?? '-' }}</td>
                    <td class="text-center">{{ $attendance->clock_in?->format('H:i') ?? '-' }}</td>
                    <td class="text-center">{{ $attendance->clock_out?->format('H:i') ?? '-' }}</td>
                    <td class="text-center">
                        @switch($attendance->clock_in_status)
                            @case('on_time')
                                <span class="status-on-time">Tepat Waktu</span>
                                @break
                            @case('late')
                                <span class="status-late">Terlambat</span>
                                @break
                            @case('very_late')
                                <span class="status-very-late">Sangat Terlambat</span>
                                @break
                            @default
                                <span class="status-absent">{{ ucfirst($attendance->status) }}</span>
                        @endswitch
                    </td>
                    <td class="text-right">
                        @if($attendance->late_minutes > 0)
                            {{ $attendance->late_minutes }}m
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-right">
                        @if($attendance->overtime_minutes > 0)
                            {{ $attendance->overtime_minutes }}m
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">Tidak ada data kehadiran</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Halaman {PAGE_NUM} dari {PAGE_COUNT} | Laporan Kehadiran {{ $company->name ?? '' }} | Dicetak: {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
