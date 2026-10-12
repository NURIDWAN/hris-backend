<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Karyawan</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            color: #1e40af;
        }
        .header p {
            margin: 5px 0 0;
            color: #6b7280;
        }
        .info {
            margin-bottom: 20px;
        }
        .info p {
            margin: 3px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #e5e7eb;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f3f4f6;
            font-weight: bold;
            color: #374151;
        }
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
        }
        .badge-success {
            background-color: #dcfce7;
            color: #166534;
        }
        .badge-warning {
            background-color: #fef3c7;
            color: #92400e;
        }
        .badge-info {
            background-color: #dbeafe;
            color: #1e40af;
        }
        .badge-danger {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9px;
            color: #9ca3af;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $company->name ?? 'Nama Perusahaan' }}</h1>
        <p>Laporan Data Karyawan</p>
    </div>

    <div class="info">
        <p><strong>Tanggal Cetak:</strong> {{ now()->format('d F Y H:i') }}</p>
        <p><strong>Total Karyawan:</strong> {{ $employees->count() }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 30px;">No</th>
                <th style="width: 70px;">ID</th>
                <th>Nama</th>
                <th>Email</th>
                <th>Departemen</th>
                <th>Jabatan</th>
                <th style="width: 70px;">Status Kerja</th>
                <th style="width: 80px;">Tgl Bergabung</th>
                <th style="width: 50px;">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($employees as $index => $employee)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $employee->employee_id }}</td>
                    <td>{{ $employee->full_name }}</td>
                    <td>{{ $employee->email }}</td>
                    <td>{{ $employee->department?->name ?? '-' }}</td>
                    <td>{{ $employee->position?->name ?? '-' }}</td>
                    <td>
                        @switch($employee->employment_status)
                            @case('permanent')
                                <span class="badge badge-success">Permanen</span>
                                @break
                            @case('contract')
                                <span class="badge badge-warning">Kontrak</span>
                                @break
                            @case('probation')
                                <span class="badge badge-info">Probation</span>
                                @break
                        @endswitch
                    </td>
                    <td>{{ $employee->hire_date?->format('d/m/Y') ?? '-' }}</td>
                    <td>
                        @if($employee->is_active)
                            <span class="badge badge-success">Aktif</span>
                        @else
                            <span class="badge badge-danger">Tidak Aktif</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Dokumen ini digenerate secara otomatis oleh sistem GajiPro</p>
    </div>
</body>
</html>
