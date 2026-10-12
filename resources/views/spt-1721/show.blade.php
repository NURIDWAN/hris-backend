@extends('layouts.admin')

@section('title', 'Detail SPT 1721 - ' . $spt1721->tax_year)

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('spt-1721.index') }}" class="btn btn-ghost">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">{{ $spt1721->spt_number }}</h1>
                <p class="text-secondary-600 mt-1">Tahun Pajak {{ $spt1721->tax_year }}</p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <x-badge :type="$spt1721->status_color" class="text-sm px-3 py-1">{{ $spt1721->status_label }}</x-badge>

            {{-- Action Buttons --}}
            @if($spt1721->canEdit())
                <form action="{{ route('spt-1721.calculate', $spt1721) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn-sm">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Hitung Ulang
                    </button>
                </form>
                <form action="{{ route('spt-1721.sync-tax-forms', $spt1721) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn-sm">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                        </svg>
                        Sync 1721-A1
                    </button>
                </form>
            @endif

            {{-- Export Dropdown --}}
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" class="btn btn-primary btn-sm">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Export Excel
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open" @click.away="open = false" x-cloak
                     class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-secondary-200 z-10">
                    <a href="{{ route('spt-1721.export', [$spt1721]) }}" class="block px-4 py-2 text-sm text-secondary-700 hover:bg-secondary-50 border-b border-secondary-100 font-medium">
                        <svg class="w-4 h-4 inline mr-2 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Semua Data (3 Sheet)
                    </a>
                    <a href="{{ route('spt-1721.export', [$spt1721, 'induk']) }}" class="block px-4 py-2 text-sm text-secondary-700 hover:bg-secondary-50">
                        SPT Induk
                    </a>
                    <a href="{{ route('spt-1721.export', [$spt1721, 'lampiran_i']) }}" class="block px-4 py-2 text-sm text-secondary-700 hover:bg-secondary-50">
                        Lampiran I (Bulanan)
                    </a>
                    <a href="{{ route('spt-1721.export', [$spt1721, 'lampiran_ii']) }}" class="block px-4 py-2 text-sm text-secondary-700 hover:bg-secondary-50">
                        Lampiran II (Karyawan)
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="card">
            <div class="card-body">
                <p class="text-sm text-secondary-500">Total Karyawan</p>
                <p class="text-2xl font-bold text-secondary-900">{{ number_format($spt1721->total_employees) }}</p>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <p class="text-sm text-secondary-500">Penghasilan Bruto</p>
                <p class="text-2xl font-bold text-secondary-900">{{ $spt1721->formatted_total_bruto }}</p>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <p class="text-sm text-secondary-500">PPh 21 Terutang</p>
                <p class="text-2xl font-bold text-primary-600">{{ $spt1721->formatted_total_pph21_terutang }}</p>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <p class="text-sm text-secondary-500">Kurang/Lebih Bayar</p>
                <p class="text-2xl font-bold {{ $spt1721->total_pph21_kurang_lebih > 0 ? 'text-danger-600' : ($spt1721->total_pph21_kurang_lebih < 0 ? 'text-success-600' : 'text-secondary-900') }}">
                    {{ $spt1721->formatted_total_pph21_kurang_lebih }}
                </p>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div x-data="{ activeTab: 'monthly' }" class="card">
        <div class="border-b border-secondary-200">
            <nav class="flex -mb-px">
                <button @click="activeTab = 'monthly'"
                        :class="activeTab === 'monthly' ? 'border-primary-500 text-primary-600' : 'border-transparent text-secondary-500 hover:text-secondary-700'"
                        class="px-6 py-3 text-sm font-medium border-b-2 transition-colors">
                    Lampiran I - Data Bulanan
                </button>
                <button @click="activeTab = 'employees'"
                        :class="activeTab === 'employees' ? 'border-primary-500 text-primary-600' : 'border-transparent text-secondary-500 hover:text-secondary-700'"
                        class="px-6 py-3 text-sm font-medium border-b-2 transition-colors">
                    Lampiran II - Daftar Karyawan ({{ $spt1721->employees->count() }})
                </button>
                <button @click="activeTab = 'signer'"
                        :class="activeTab === 'signer' ? 'border-primary-500 text-primary-600' : 'border-transparent text-secondary-500 hover:text-secondary-700'"
                        class="px-6 py-3 text-sm font-medium border-b-2 transition-colors">
                    Penandatangan
                </button>
            </nav>
        </div>

        {{-- Monthly Data Tab --}}
        <div x-show="activeTab === 'monthly'" class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-secondary-50 border-b border-secondary-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-secondary-600 uppercase">Masa</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-secondary-600 uppercase">Jml Karyawan</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-secondary-600 uppercase">Bruto</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-secondary-600 uppercase">PPh 21</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-secondary-600 uppercase">Disetor</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-secondary-600 uppercase">NTPN</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-secondary-600 uppercase">Tgl Setor</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-secondary-200">
                    @php $totalBruto = 0; $totalPph = 0; $totalSetor = 0; @endphp
                    @foreach($spt1721->monthlyData as $monthly)
                        @php
                            $totalBruto += $monthly->total_bruto;
                            $totalPph += $monthly->total_pph21;
                            $totalSetor += $monthly->pph21_disetor;
                        @endphp
                        <tr class="hover:bg-secondary-50">
                            <td class="px-4 py-3 font-medium">{{ $monthly->month_name }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($monthly->employee_count) }}</td>
                            <td class="px-4 py-3 text-right font-mono text-sm">{{ $monthly->formatted_total_bruto }}</td>
                            <td class="px-4 py-3 text-right font-mono text-sm">{{ $monthly->formatted_total_pph21 }}</td>
                            <td class="px-4 py-3 text-right font-mono text-sm">{{ $monthly->formatted_pph21_disetor }}</td>
                            <td class="px-4 py-3 font-mono text-sm">{{ $monthly->ssp_ntpn ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm">{{ $monthly->ssp_date?->format('d/m/Y') ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-secondary-100 font-semibold">
                    <tr>
                        <td class="px-4 py-3">TOTAL</td>
                        <td class="px-4 py-3 text-right">-</td>
                        <td class="px-4 py-3 text-right font-mono">Rp {{ number_format($totalBruto, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right font-mono">Rp {{ number_format($totalPph, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right font-mono">Rp {{ number_format($totalSetor, 0, ',', '.') }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Employees Tab --}}
        <div x-show="activeTab === 'employees'" x-cloak class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-secondary-50 border-b border-secondary-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-secondary-600 uppercase">No</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-secondary-600 uppercase">NPWP</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-secondary-600 uppercase">Nama</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-secondary-600 uppercase">PTKP</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-secondary-600 uppercase">Masa</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-secondary-600 uppercase">Bruto</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-secondary-600 uppercase">PPh 21</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-secondary-600 uppercase">1721-A1</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-secondary-200">
                    @foreach($spt1721->employees as $index => $emp)
                        <tr class="hover:bg-secondary-50">
                            <td class="px-4 py-3 text-sm">{{ $index + 1 }}</td>
                            <td class="px-4 py-3 font-mono text-sm">{{ $emp->employee_npwp ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $emp->employee_name }}</div>
                                <div class="text-xs text-secondary-500">{{ $emp->employee_nik }}</div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-1 text-xs font-medium bg-secondary-100 rounded">{{ $emp->ptkp_status }}</span>
                            </td>
                            <td class="px-4 py-3 text-center text-sm">{{ $emp->work_period }}</td>
                            <td class="px-4 py-3 text-right font-mono text-sm">{{ $emp->formatted_gross_salary }}</td>
                            <td class="px-4 py-3 text-right font-mono text-sm text-primary-600">{{ $emp->formatted_pph21_terutang }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($emp->tax_form_1721a1_id)
                                    <a href="{{ route('tax-forms.1721a1.show', $emp->tax_form_1721a1_id) }}" class="text-primary-600 hover:underline text-sm">
                                        Lihat
                                    </a>
                                @else
                                    <span class="text-secondary-400 text-sm">-</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Signer Tab --}}
        <div x-show="activeTab === 'signer'" x-cloak class="p-6">
            <form action="{{ route('spt-1721.update-signer', $spt1721) }}" method="POST" class="max-w-xl space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <label class="block text-sm font-medium text-secondary-700 mb-1">Nama Penandatangan</label>
                    <input type="text" name="signer_name" value="{{ old('signer_name', $spt1721->signer_name) }}"
                           class="input w-full" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-secondary-700 mb-1">NPWP Penandatangan</label>
                    <input type="text" name="signer_npwp" value="{{ old('signer_npwp', $spt1721->signer_npwp) }}"
                           class="input w-full" placeholder="00.000.000.0-000.000">
                </div>

                <div>
                    <label class="block text-sm font-medium text-secondary-700 mb-1">Jabatan</label>
                    <input type="text" name="signer_position" value="{{ old('signer_position', $spt1721->signer_position) }}"
                           class="input w-full" required>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-secondary-700 mb-1">Tempat</label>
                        <input type="text" name="sign_place" value="{{ old('sign_place', $spt1721->sign_place) }}"
                               class="input w-full" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-secondary-700 mb-1">Tanggal</label>
                        <input type="date" name="sign_date" value="{{ old('sign_date', $spt1721->sign_date?->format('Y-m-d')) }}"
                               class="input w-full" required>
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit" class="btn btn-primary">Simpan Penandatangan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Status Actions --}}
    @if($spt1721->status === 'calculated')
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Setor PPh 21</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('spt-1721.submit', $spt1721) }}" method="POST" class="flex items-end gap-4">
                    @csrf
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-secondary-700 mb-1">NTPN (Nomor Transaksi Penerimaan Negara)</label>
                        <input type="text" name="ntpn" class="input w-full font-mono" maxlength="16" placeholder="16 karakter" required>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Tandai Sudah Disetor
                    </button>
                </form>
            </div>
        </div>
    @endif

    @if($spt1721->status === 'submitted')
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Lapor SPT ke DJP</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('spt-1721.report', $spt1721) }}" method="POST" class="flex items-end gap-4">
                    @csrf
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-secondary-700 mb-1">Bukti Penerimaan Elektronik (BPE)</label>
                        <input type="text" name="submission_receipt" class="input w-full font-mono" placeholder="Nomor BPE dari DJP Online" required>
                    </div>
                    <button type="submit" class="btn btn-success">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Tandai Sudah Dilaporkan
                    </button>
                </form>
            </div>
        </div>
    @endif

    {{-- Danger Zone --}}
    @if($spt1721->status === 'draft')
        <div class="card border-danger-200">
            <div class="card-header bg-danger-50">
                <h3 class="card-title text-danger-700">Zona Berbahaya</h3>
            </div>
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-secondary-900">Hapus SPT</p>
                        <p class="text-sm text-secondary-600">SPT ini akan dihapus permanen.</p>
                    </div>
                    <button type="button"
                            @click="$dispatch('confirm-dialog', {
                                title: 'Hapus SPT',
                                message: 'Apakah Anda yakin ingin menghapus SPT {{ $spt1721->spt_number }}?',
                                confirmText: 'Ya, Hapus',
                                type: 'danger',
                                formAction: '{{ route('spt-1721.destroy', $spt1721) }}'
                            })"
                            class="btn btn-danger">
                        Hapus SPT
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
