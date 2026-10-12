@extends('layouts.admin')

@section('title', 'Laporan Penggajian')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Laporan Penggajian</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Laporan Penggajian</h1>
            <p class="text-secondary-500 mt-1">Rekap penggajian karyawan tahun {{ $year }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('reports.payroll.export', array_merge(request()->query(), ['format' => 'excel'])) }}" class="btn btn-ghost">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export Excel
            </a>
            <a href="{{ route('reports.payroll.export', array_merge(request()->query(), ['format' => 'pdf'])) }}" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                Export PDF
            </a>
        </div>
    </div>
@endsection

@section('content')
    {{-- Quick Links --}}
    <div class="flex flex-wrap gap-2 mb-6">
        <a href="{{ route('reports.payroll.by-department') }}" class="btn btn-ghost btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            Per Departemen
        </a>
        <a href="{{ route('reports.payroll.tax-summary') }}" class="btn btn-ghost btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"/></svg>
            Ringkasan Pajak
        </a>
    </div>

    @php
        // Format currency short (e.g., 252.5 Jt)
        $formatShort = function($value) {
            if ($value >= 1000000000) {
                return 'Rp ' . number_format($value / 1000000000, 1, ',', '.') . ' M';
            } elseif ($value >= 1000000) {
                return 'Rp ' . number_format($value / 1000000, 1, ',', '.') . ' Jt';
            } elseif ($value >= 1000) {
                return 'Rp ' . number_format($value / 1000, 1, ',', '.') . ' Rb';
            }
            return 'Rp ' . number_format($value, 0, ',', '.');
        };
    @endphp

    {{-- Summary Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-4">
        <div class="stat-card">
            <p class="stat-card-label">Periode</p>
            <p class="text-lg font-bold text-secondary-900">{{ $summary['total_payrolls'] }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-card-label">Karyawan</p>
            <p class="text-lg font-bold text-secondary-900">{{ $summary['employee_count'] }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-card-label">Gaji Kotor</p>
            <p class="text-lg font-bold text-secondary-900">{{ $formatShort($summary['total_gross']) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-card-label">Potongan</p>
            <p class="text-lg font-bold text-danger-600">{{ $formatShort($summary['total_deductions']) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-card-label">Pajak</p>
            <p class="text-lg font-bold text-warning-600">{{ $formatShort($summary['total_pph21']) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-card-label">Gaji Bersih</p>
            <p class="text-lg font-bold text-success-600">{{ $formatShort($summary['total_net']) }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body-sm">
            <form action="{{ route('reports.payroll') }}" method="GET" class="flex flex-wrap items-end gap-3">
                <div class="w-24">
                    <label class="block text-xs font-medium text-secondary-500 mb-1">Tahun</label>
                    <select name="year" class="input w-full">
                        @for($y = now()->year; $y >= now()->year - 5; $y--)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="w-32">
                    <label class="block text-xs font-medium text-secondary-500 mb-1">Bulan</label>
                    <select name="month" class="input w-full">
                        <option value="">Semua</option>
                        @foreach(['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'] as $index => $monthName)
                            <option value="{{ $index + 1 }}" {{ $month == $index + 1 ? 'selected' : '' }}>{{ $monthName }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    @if(request()->hasAny(['year', 'month']))
                        <a href="{{ route('reports.payroll') }}" class="btn btn-ghost btn-sm">Reset</a>
                    @endif
                    <button type="submit" class="btn btn-primary btn-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Payroll List Table --}}
    <div class="card">
        <x-table>
            <x-slot name="header">
                <th>Periode</th>
                <th class="text-center">Karyawan</th>
                <th class="text-right">Gaji Kotor</th>
                <th class="text-right">Potongan</th>
                <th class="text-right">Gaji Bersih</th>
                <th class="text-center">Status</th>
                <th class="text-right">Aksi</th>
            </x-slot>

            @forelse($payrolls as $payroll)
                <tr>
                    <td>
                        <span class="font-medium text-secondary-900">{{ \Carbon\Carbon::create($payroll->period_year, $payroll->period_month)->translatedFormat('F Y') }}</span>
                        <p class="text-xs text-secondary-400">{{ $payroll->name ?? 'Payroll #' . $payroll->id }}</p>
                    </td>
                    <td class="text-center">
                        <span class="font-medium text-secondary-900">{{ $payroll->items->count() }}</span>
                    </td>
                    <td class="text-right text-secondary-900">
                        Rp {{ number_format($payroll->items->sum('gross_salary'), 0, ',', '.') }}
                    </td>
                    <td class="text-right text-danger-600">
                        Rp {{ number_format($payroll->items->sum('total_deductions'), 0, ',', '.') }}
                    </td>
                    <td class="text-right font-medium text-success-600">
                        Rp {{ number_format($payroll->items->sum('net_salary'), 0, ',', '.') }}
                    </td>
                    <td class="text-center">
                        @switch($payroll->status)
                            @case('draft')
                                <x-badge type="secondary">Draft</x-badge>
                                @break
                            @case('processed')
                                <x-badge type="info">Diproses</x-badge>
                                @break
                            @case('approved')
                                <x-badge type="warning">Disetujui</x-badge>
                                @break
                            @case('paid')
                                <x-badge type="success">Dibayar</x-badge>
                                @break
                            @case('cancelled')
                                <x-badge type="danger">Dibatalkan</x-badge>
                                @break
                            @default
                                <x-badge type="secondary">{{ ucfirst($payroll->status) }}</x-badge>
                        @endswitch
                    </td>
                    <td>
                        <div class="flex items-center justify-end">
                            <a href="{{ route('payrolls.show', $payroll) }}" class="p-1.5 text-secondary-400 hover:text-primary-600 hover:bg-primary-50 rounded-md transition-colors" title="Lihat Detail">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-12">
                        <div class="flex flex-col items-center">
                            <svg class="w-12 h-12 text-secondary-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <p class="text-secondary-500">Tidak ada data payroll untuk periode ini</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </x-table>

        <div class="card-footer">
            <p class="text-sm text-secondary-500">Menampilkan {{ $payrolls->count() }} periode payroll</p>
        </div>
    </div>
@endsection
