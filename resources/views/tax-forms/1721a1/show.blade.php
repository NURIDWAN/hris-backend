@extends('layouts.admin')

@section('title', 'Detail Bukti Potong 1721-A1')

@section('breadcrumb')
    <a href="{{ route('tax-forms.1721a1.index') }}" class="text-primary-600 hover:text-primary-700">Bukti Potong 1721-A1</a>
    <span class="text-secondary-400 mx-2">/</span>
    <span class="text-slate-700 font-medium">{{ $taxForm->form_number }}</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Bukti Potong 1721-A1</h1>
            <p class="text-secondary-500 mt-1">{{ $taxForm->employee_name }} - Tahun {{ $taxForm->tax_year }}</p>
        </div>
        <div class="flex items-center gap-2">
            <form action="{{ route('tax-forms.1721a1.regenerate', $taxForm) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="btn btn-ghost">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Regenerate
                </button>
            </form>
            <a href="{{ route('tax-forms.1721a1.pdf', $taxForm) }}" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Download PDF
            </a>
        </div>
    </div>
@endsection

@section('content')
    @php
        $formatCurrency = function($value) {
            return 'Rp ' . number_format($value, 0, ',', '.');
        };
    @endphp

    <div class="max-w-4xl">
        {{-- Header Info --}}
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">BUKTI PEMOTONGAN PAJAK PENGHASILAN PASAL 21 BAGI PEGAWAI TETAP</h3>
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <p class="text-lg font-bold">FORMULIR 1721-A1</p>
                    <p class="font-mono">{{ $taxForm->form_number }}</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Pemotong Pajak --}}
                    <div>
                        <h4 class="font-semibold text-secondary-700 mb-2 uppercase text-sm">A. Identitas Pemotong Pajak</h4>
                        <table class="text-sm w-full">
                            <tr>
                                <td class="text-secondary-500 py-1 w-24">NPWP</td>
                                <td class="font-mono">: {{ $taxForm->company_npwp ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary-500 py-1">Nama</td>
                                <td>: {{ $taxForm->company_name }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary-500 py-1">Alamat</td>
                                <td>: {{ $taxForm->company_address ?? '-' }}</td>
                            </tr>
                        </table>
                    </div>

                    {{-- Penerima Penghasilan --}}
                    <div>
                        <h4 class="font-semibold text-secondary-700 mb-2 uppercase text-sm">B. Identitas Penerima Penghasilan</h4>
                        <table class="text-sm w-full">
                            <tr>
                                <td class="text-secondary-500 py-1 w-24">NPWP</td>
                                <td class="font-mono">: {{ $taxForm->employee_npwp ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary-500 py-1">NIK</td>
                                <td class="font-mono">: {{ $taxForm->employee_nik ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary-500 py-1">Nama</td>
                                <td>: {{ $taxForm->employee_name }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary-500 py-1">Alamat</td>
                                <td>: {{ $taxForm->employee_address ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary-500 py-1">Status</td>
                                <td>: {{ $taxForm->ptkp_status }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Penghasilan Bruto --}}
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">C. Rincian Penghasilan dan Penghitungan PPh Pasal 21</h3>
            </div>
            <div class="card-body">
                {{-- Masa Perolehan --}}
                <div class="mb-4 p-3 bg-secondary-50 rounded-lg">
                    <p class="text-sm text-secondary-600">
                        <strong>Masa Perolehan Penghasilan:</strong>
                        {{ $taxForm->work_period }}
                    </p>
                </div>

                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-secondary-200">
                            <th class="text-left py-2 font-semibold text-secondary-700">PENGHASILAN BRUTO</th>
                            <th class="text-right py-2 font-semibold text-secondary-700 w-40">Jumlah (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b border-secondary-100">
                            <td class="py-2">1. Gaji/Pensiun atau THT/JHT</td>
                            <td class="text-right font-mono">{{ $formatCurrency($taxForm->gaji_pokok) }}</td>
                        </tr>
                        <tr class="border-b border-secondary-100">
                            <td class="py-2">2. Tunjangan PPh</td>
                            <td class="text-right font-mono">{{ $formatCurrency($taxForm->tunjangan_pph) }}</td>
                        </tr>
                        <tr class="border-b border-secondary-100">
                            <td class="py-2">3. Tunjangan lainnya, uang lembur, dsb</td>
                            <td class="text-right font-mono">{{ $formatCurrency($taxForm->tunjangan_lainnya) }}</td>
                        </tr>
                        <tr class="border-b border-secondary-100">
                            <td class="py-2">4. Honorarium dan imbalan lain sejenisnya</td>
                            <td class="text-right font-mono">{{ $formatCurrency($taxForm->honorarium) }}</td>
                        </tr>
                        <tr class="border-b border-secondary-100">
                            <td class="py-2">5. Premi asuransi yang dibayar pemberi kerja</td>
                            <td class="text-right font-mono">{{ $formatCurrency($taxForm->premi_asuransi) }}</td>
                        </tr>
                        <tr class="border-b border-secondary-100">
                            <td class="py-2">6. Penerimaan dalam bentuk natura</td>
                            <td class="text-right font-mono">{{ $formatCurrency($taxForm->natura) }}</td>
                        </tr>
                        <tr class="border-b border-secondary-100">
                            <td class="py-2">7. Tantiem, Bonus, Gratifikasi, THR</td>
                            <td class="text-right font-mono">{{ $formatCurrency($taxForm->tantiem_bonus_thr) }}</td>
                        </tr>
                        <tr class="border-b-2 border-secondary-300 bg-secondary-50 font-semibold">
                            <td class="py-2">8. JUMLAH PENGHASILAN BRUTO (1 s.d. 7)</td>
                            <td class="text-right font-mono">{{ $formatCurrency($taxForm->gross_salary) }}</td>
                        </tr>
                    </tbody>
                </table>

                <table class="w-full text-sm mt-4">
                    <thead>
                        <tr class="border-b border-secondary-200">
                            <th class="text-left py-2 font-semibold text-secondary-700">PENGURANG</th>
                            <th class="text-right py-2 font-semibold text-secondary-700 w-40">Jumlah (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b border-secondary-100">
                            <td class="py-2">9. Biaya Jabatan (5% x bruto, maks Rp 6.000.000)</td>
                            <td class="text-right font-mono">{{ $formatCurrency($taxForm->biaya_jabatan) }}</td>
                        </tr>
                        <tr class="border-b border-secondary-100">
                            <td class="py-2">10. Iuran Pensiun/JHT yang dibayar sendiri</td>
                            <td class="text-right font-mono">{{ $formatCurrency($taxForm->iuran_pensiun) }}</td>
                        </tr>
                        <tr class="border-b-2 border-secondary-300 bg-secondary-50 font-semibold">
                            <td class="py-2">11. JUMLAH PENGURANG (9 + 10)</td>
                            <td class="text-right font-mono">{{ $formatCurrency($taxForm->total_pengurang) }}</td>
                        </tr>
                    </tbody>
                </table>

                <table class="w-full text-sm mt-4">
                    <thead>
                        <tr class="border-b border-secondary-200">
                            <th class="text-left py-2 font-semibold text-secondary-700">PENGHITUNGAN PPh PASAL 21</th>
                            <th class="text-right py-2 font-semibold text-secondary-700 w-40">Jumlah (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b border-secondary-100">
                            <td class="py-2">12. PENGHASILAN NETO (8 - 11)</td>
                            <td class="text-right font-mono">{{ $formatCurrency($taxForm->neto_salary) }}</td>
                        </tr>
                        <tr class="border-b border-secondary-100">
                            <td class="py-2">13. PTKP ({{ $taxForm->ptkp_status }})</td>
                            <td class="text-right font-mono">{{ $formatCurrency($taxForm->ptkp) }}</td>
                        </tr>
                        <tr class="border-b border-secondary-100">
                            <td class="py-2">14. PKP (Penghasilan Kena Pajak) (12 - 13)</td>
                            <td class="text-right font-mono">{{ $formatCurrency($taxForm->pkp) }}</td>
                        </tr>
                        <tr class="border-b border-secondary-100">
                            <td class="py-2">15. PPh Pasal 21 Terutang</td>
                            <td class="text-right font-mono">{{ $formatCurrency($taxForm->pph21_terutang) }}</td>
                        </tr>
                        <tr class="border-b border-secondary-100">
                            <td class="py-2">16. PPh Pasal 21 yang telah dipotong sebelumnya</td>
                            <td class="text-right font-mono">{{ $formatCurrency($taxForm->pph21_dipotong_sebelumnya) }}</td>
                        </tr>
                        <tr class="border-b-2 border-success-500 bg-success-50 font-semibold">
                            <td class="py-2 text-success-700">17. PPh PASAL 21 YANG TELAH DIPOTONG</td>
                            <td class="text-right font-mono text-success-700">{{ $formatCurrency($taxForm->total_pph21_withheld) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Footer Info --}}
        <div class="card">
            <div class="card-body">
                <div class="flex flex-wrap justify-between text-sm text-secondary-500">
                    <div>
                        <p>Dibuat pada: {{ $taxForm->generated_at?->format('d M Y H:i') ?? '-' }}</p>
                        <p>Oleh: {{ $taxForm->generatedBy?->name ?? '-' }}</p>
                    </div>
                    <div class="text-right">
                        <p>Form dibuat tanggal: {{ now()->format('d M Y') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
