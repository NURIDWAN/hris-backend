<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Bukti Potong 1721-A1 - Tahun {{ $year }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
            line-height: 1.3;
            color: #333;
            margin: 0;
            padding: 10px;
        }
        .page-break {
            page-break-after: always;
        }
        .header {
            text-align: center;
            border: 2px solid #000;
            padding: 8px;
            margin-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 11px;
            font-weight: bold;
        }
        .header h2 {
            margin: 4px 0 0;
            font-size: 12px;
            font-weight: bold;
        }
        .header p {
            margin: 2px 0 0;
            font-size: 9px;
        }
        .form-number {
            text-align: right;
            font-size: 9px;
            margin-bottom: 8px;
        }
        .section {
            margin-bottom: 8px;
        }
        .section-title {
            font-weight: bold;
            font-size: 9px;
            background-color: #f0f0f0;
            padding: 4px;
            margin-bottom: 5px;
            border: 1px solid #ccc;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 2px 4px;
            vertical-align: top;
            font-size: 9px;
        }
        .info-table .label {
            width: 90px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table td, .data-table th {
            padding: 3px 5px;
            border: 1px solid #ccc;
            font-size: 9px;
        }
        .data-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: left;
        }
        .data-table .right {
            text-align: right;
        }
        .data-table .total-row {
            font-weight: bold;
            background-color: #f8f8f8;
        }
        .mono {
            font-family: monospace;
        }
        .signature-section {
            margin-top: 20px;
            width: 100%;
        }
        .signature-box {
            width: 40%;
            float: right;
            text-align: center;
            font-size: 9px;
        }
        .signature-box p {
            margin: 3px 0;
        }
        .signature-line {
            margin-top: 35px;
            border-top: 1px solid #333;
            padding-top: 3px;
        }
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
    </style>
</head>
<body>
    @foreach($taxForms as $index => $taxForm)
    <div class="form-number">
        <strong>Nomor:</strong> {{ $taxForm->form_number }}
    </div>

    <div class="header">
        <h1>BUKTI PEMOTONGAN PAJAK PENGHASILAN PASAL 21</h1>
        <h1>BAGI PEGAWAI TETAP ATAU PENERIMA PENSIUN BERKALA</h1>
        <h2>FORMULIR 1721-A1</h2>
        <p>TAHUN PAJAK {{ $taxForm->tax_year }}</p>
    </div>

    <div class="section">
        <div class="section-title">A. IDENTITAS PEMOTONG PAJAK</div>
        <table class="info-table">
            <tr>
                <td class="label">1. NPWP</td>
                <td>: <span class="mono">{{ $taxForm->company_npwp ?? '-' }}</span></td>
            </tr>
            <tr>
                <td class="label">2. Nama</td>
                <td>: {{ $taxForm->company_name }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">B. IDENTITAS PENERIMA PENGHASILAN</div>
        <table class="info-table">
            <tr>
                <td class="label">1. NPWP</td>
                <td>: <span class="mono">{{ $taxForm->employee_npwp ?? '-' }}</span></td>
                <td class="label">2. NIK</td>
                <td>: <span class="mono">{{ $taxForm->employee_nik ?? '-' }}</span></td>
            </tr>
            <tr>
                <td class="label">3. Nama</td>
                <td colspan="3">: {{ $taxForm->employee_name }}</td>
            </tr>
            <tr>
                <td class="label">4. Status PTKP</td>
                <td colspan="3">: {{ $taxForm->ptkp_status }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">C. RINCIAN PENGHASILAN DAN PENGHITUNGAN PPh PASAL 21 (Masa: {{ $taxForm->work_period }})</div>

        <table class="data-table">
            <tr>
                <td>8. JUMLAH PENGHASILAN BRUTO</td>
                <td class="right mono" style="width:100px;">{{ number_format($taxForm->gross_salary, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>11. JUMLAH PENGURANG</td>
                <td class="right mono">{{ number_format($taxForm->total_pengurang, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>12. PENGHASILAN NETO</td>
                <td class="right mono">{{ number_format($taxForm->neto_salary, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>15. PTKP ({{ $taxForm->ptkp_status }})</td>
                <td class="right mono">{{ number_format($taxForm->ptkp, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>16. PENGHASILAN KENA PAJAK</td>
                <td class="right mono">{{ number_format($taxForm->pkp, 0, ',', '.') }}</td>
            </tr>
            <tr class="total-row" style="background-color: #d4edda;">
                <td><strong>19. PPh PASAL 21 TERUTANG</strong></td>
                <td class="right mono"><strong>{{ number_format($taxForm->total_pph21_withheld, 0, ',', '.') }}</strong></td>
            </tr>
        </table>
    </div>

    <div class="signature-section clearfix">
        <div class="signature-box">
            <p>{{ $taxForm->generated_at?->format('d F Y') ?? now()->format('d F Y') }}</p>
            <p>Pemotong Pajak</p>
            <div class="signature-line">
                <p><strong>{{ $taxForm->company_name }}</strong></p>
            </div>
        </div>
    </div>

    @if(!$loop->last)
    <div class="page-break"></div>
    @endif
    @endforeach
</body>
</html>
