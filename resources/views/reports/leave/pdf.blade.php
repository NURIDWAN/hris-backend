<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Cuti</title>
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
        .status-pending {
            color: #d97706;
            font-weight: bold;
        }
        .status-approved {
            color: #059669;
            font-weight: bold;
        }
        .status-rejected {
            color: #dc2626;
            font-weight: bold;
        }
        .status-cancelled {
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
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $company->name ?? 'Perusahaan' }}</div>
        <div class="report-title">LAPORAN CUTI KARYAWAN</div>
        <div class="report-period">Periode: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</div>
    </div>

    <div class="meta-info">
        Dicetak pada: {{ now()->format('d M Y H:i') }}
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 30px;">No</th>
                <th>Nama Karyawan</th>
                <th>Departemen</th>
                <th>Jenis Cuti</th>
                <th class="text-center">Tanggal Mulai</th>
                <th class="text-center">Tanggal Selesai</th>
                <th class="text-center">Hari</th>
                <th class="text-center">Status</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($leaveRequests as $index => $leave)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $leave->employee->full_name ?? '-' }}</td>
                    <td>{{ $leave->employee->department?->name ?? '-' }}</td>
                    <td>{{ $leave->leaveType->name ?? '-' }}</td>
                    <td class="text-center">{{ $leave->start_date->format('d/m/Y') }}</td>
                    <td class="text-center">{{ $leave->end_date->format('d/m/Y') }}</td>
                    <td class="text-center">{{ $leave->total_days }}</td>
                    <td class="text-center">
                        @switch($leave->status)
                            @case('pending')
                                <span class="status-pending">Menunggu</span>
                                @break
                            @case('approved')
                                <span class="status-approved">Disetujui</span>
                                @break
                            @case('rejected')
                                <span class="status-rejected">Ditolak</span>
                                @break
                            @case('cancelled')
                                <span class="status-cancelled">Dibatalkan</span>
                                @break
                            @default
                                {{ ucfirst($leave->status) }}
                        @endswitch
                    </td>
                    <td>{{ Str::limit($leave->reason, 50) ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">Tidak ada data pengajuan cuti</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Halaman {PAGE_NUM} dari {PAGE_COUNT} | Laporan Cuti {{ $company->name ?? '' }} | Dicetak: {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
