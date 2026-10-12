<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Bukti Potong 1721-A1 - {{ $taxForm->employee_name }} - {{ $taxForm->tax_year }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 15px;
        }
        .header {
            text-align: center;
            border: 2px solid #000;
            padding: 10px;
            margin-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            font-size: 12px;
            font-weight: bold;
        }
        .header h2 {
            margin: 5px 0 0;
            font-size: 14px;
            font-weight: bold;
        }
        .header p {
            margin: 3px 0 0;
            font-size: 10px;
        }
        .form-number {
            text-align: right;
            font-size: 10px;
            margin-bottom: 10px;
        }
        .section {
            margin-bottom: 12px;
        }
        .section-title {
            font-weight: bold;
            font-size: 10px;
            background-color: #f0f0f0;
            padding: 5px;
            margin-bottom: 8px;
            border: 1px solid #ccc;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 3px 5px;
            vertical-align: top;
        }
        .info-table .label {
            width: 100px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table td, .data-table th {
            padding: 4px 6px;
            border: 1px solid #ccc;
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
            margin-top: 30px;
            width: 100%;
        }
        .signature-box {
            width: 45%;
            float: right;
            text-align: center;
        }
        .signature-box p {
            margin: 5px 0;
        }
        .signature-line {
            margin-top: 50px;
            border-top: 1px solid #333;
            padding-top: 5px;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 8px;
            color: #666;
            clear: both;
        }
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
    </style>
</head>
<body>
    <div class="form-number">
        <strong>Nomor:</strong> {{ $taxForm->form_number }}
    </div>

    <div class="header">
        <h1>BUKTI PEMOTONGAN PAJAK PENGHASILAN</h1>
        <h1>PASAL 21 BAGI PEGAWAI TETAP</h1>
        <h1>ATAU PENERIMA PENSIUN ATAU TUNJANGAN HARI TUA/</h1>
        <h1>JAMINAN HARI TUA BERKALA</h1>
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
            <tr>
                <td class="label">3. Alamat</td>
                <td>: {{ $taxForm->company_address ?? '-' }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">B. IDENTITAS PENERIMA PENGHASILAN</div>
        <table class="info-table">
            <tr>
                <td class="label">1. NPWP</td>
                <td>: <span class="mono">{{ $taxForm->employee_npwp ?? '-' }}</span></td>
            </tr>
            <tr>
                <td class="label">2. NIK/No. Paspor</td>
                <td>: <span class="mono">{{ $taxForm->employee_nik ?? '-' }}</span></td>
            </tr>
            <tr>
                <td class="label">3. Nama</td>
                <td>: {{ $taxForm->employee_name }}</td>
            </tr>
            <tr>
                <td class="label">4. Alamat</td>
                <td>: {{ $taxForm->employee_address ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">5. Jenis Kelamin</td>
                <td>: {{ $taxForm->employee?->gender === 'male' ? 'Laki-Laki' : 'Perempuan' }}</td>
            </tr>
            <tr>
                <td class="label">6. Status/Jumlah Tanggungan</td>
                <td>: {{ $taxForm->ptkp_status }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">C. RINCIAN PENGHASILAN DAN PENGHITUNGAN PPh PASAL 21</div>

        <table class="info-table" style="margin-bottom: 10px;">
            <tr>
                <td class="label">Masa Perolehan</td>
                <td>: {{ $taxForm->work_period }}</td>
            </tr>
        </table>

        <table class="data-table">
            <tr>
                <th colspan="2">PENGHASILAN BRUTO</th>
            </tr>
            <tr>
                <td>1. Gaji/Pensiun atau THT/JHT</td>
                <td class="right mono">{{ number_format($taxForm->gaji_pokok, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>2. Tunjangan PPh</td>
                <td class="right mono">{{ number_format($taxForm->tunjangan_pph, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>3. Tunjangan lainnya, uang lembur, dsb</td>
                <td class="right mono">{{ number_format($taxForm->tunjangan_lainnya, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>4. Honorarium dan imbalan lain sejenisnya</td>
                <td class="right mono">{{ number_format($taxForm->honorarium, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>5. Premi asuransi yang dibayar pemberi kerja</td>
                <td class="right mono">{{ number_format($taxForm->premi_asuransi, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>6. Penerimaan dalam bentuk natura dan kenikmatan lainnya</td>
                <td class="right mono">{{ number_format($taxForm->natura, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>7. Tantiem, Bonus, Gratifikasi, THR dan sejenisnya</td>
                <td class="right mono">{{ number_format($taxForm->tantiem_bonus_thr, 0, ',', '.') }}</td>
            </tr>
            <tr class="total-row">
                <td>8. JUMLAH PENGHASILAN BRUTO (1 s.d. 7)</td>
                <td class="right mono">{{ number_format($taxForm->gross_salary, 0, ',', '.') }}</td>
            </tr>

            <tr>
                <th colspan="2">PENGURANG</th>
            </tr>
            <tr>
                <td>9. Biaya Jabatan / Biaya Pensiun</td>
                <td class="right mono">{{ number_format($taxForm->biaya_jabatan, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>10. Iuran Pensiun atau THT/JHT yang dibayar sendiri</td>
                <td class="right mono">{{ number_format($taxForm->iuran_pensiun, 0, ',', '.') }}</td>
            </tr>
            <tr class="total-row">
                <td>11. JUMLAH PENGURANG (9 + 10)</td>
                <td class="right mono">{{ number_format($taxForm->total_pengurang, 0, ',', '.') }}</td>
            </tr>

            <tr>
                <th colspan="2">PENGHITUNGAN PPh PASAL 21</th>
            </tr>
            <tr>
                <td>12. JUMLAH PENGHASILAN NETO (8 - 11)</td>
                <td class="right mono">{{ number_format($taxForm->neto_salary, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>13. PENGHASILAN NETO MASA SEBELUMNYA</td>
                <td class="right mono">-</td>
            </tr>
            <tr>
                <td>14. JUMLAH PENGHASILAN NETO UNTUK PERHITUNGAN PPh PASAL 21 (12 + 13)</td>
                <td class="right mono">{{ number_format($taxForm->neto_salary, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>15. PENGHASILAN TIDAK KENA PAJAK (PTKP) - {{ $taxForm->ptkp_status }}</td>
                <td class="right mono">{{ number_format($taxForm->ptkp, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>16. PENGHASILAN KENA PAJAK SETAHUN/DISETAHUNKAN (14 - 15)</td>
                <td class="right mono">{{ number_format($taxForm->pkp, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>17. PPh PASAL 21 ATAS PENGHASILAN KENA PAJAK SETAHUN</td>
                <td class="right mono">{{ number_format($taxForm->pph21_terutang, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>18. PPh PASAL 21 YANG TELAH DIPOTONG MASA SEBELUMNYA</td>
                <td class="right mono">{{ number_format($taxForm->pph21_dipotong_sebelumnya, 0, ',', '.') }}</td>
            </tr>
            <tr class="total-row" style="background-color: #d4edda;">
                <td><strong>19. PPh PASAL 21 TERUTANG</strong></td>
                <td class="right mono"><strong>{{ number_format($taxForm->total_pph21_withheld, 0, ',', '.') }}</strong></td>
            </tr>
        </table>
    </div>

    <div class="signature-section clearfix">
        <div class="signature-box">
            <p>{{ $taxForm->company_address ?? 'Indonesia' }}, {{ $taxForm->generated_at?->format('d F Y') ?? now()->format('d F Y') }}</p>
            <p>Pemotong Pajak</p>
            <div class="signature-line">
                <p><strong>{{ $taxForm->company_name }}</strong></p>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>Bukti Potong ini digenerate secara otomatis oleh sistem GajiPro HRIS.</p>
        <p>Dicetak pada: {{ now()->format('d M Y H:i:s') }}</p>
    </div>
</body>
</html>
