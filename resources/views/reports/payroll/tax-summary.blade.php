@extends('layouts.admin')

@section('title', 'Ringkasan Pajak PPh 21')

@section('breadcrumb')
    <a href="{{ route('reports.payroll') }}" class="text-primary-600 hover:text-primary-700">Laporan Penggajian</a>
    <span class="mx-2 text-secondary-400">/</span>
    <span class="text-slate-700 font-medium">Ringkasan Pajak</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Ringkasan Pajak PPh 21</h1>
            <p class="text-secondary-500 mt-1">Tahun {{ $year }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('reports.payroll') }}" class="btn btn-ghost">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/></svg>
                Kembali
            </a>
        </div>
    </div>
@endsection

@section('content')
    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body-sm">
            <form action="{{ route('reports.payroll.tax-summary') }}" method="GET" class="flex flex-wrap items-end gap-3">
                <div class="w-24">
                    <label class="block text-xs font-medium text-secondary-500 mb-1">Tahun</label>
                    <select name="year" class="input w-full">
                        @for($y = now()->year; $y >= now()->year - 5; $y--)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    Filter
                </button>
            </form>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="flex items-center gap-4">
                    <div class="flex-shrink-0 w-12 h-12 rounded-full bg-warning-100 flex items-center justify-center">
                        <svg class="w-6 h-6 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-sm text-secondary-500">Total PPh 21 Tahun {{ $year }}</p>
                        <p class="text-2xl font-bold text-warning-600">Rp {{ number_format($grandTotal['total_pph21'], 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="flex items-center gap-4">
                    <div class="flex-shrink-0 w-12 h-12 rounded-full bg-primary-100 flex items-center justify-center">
                        <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-sm text-secondary-500">Total Gaji Kotor Tahun {{ $year }}</p>
                        <p class="text-2xl font-bold text-primary-600">Rp {{ number_format($grandTotal['total_gross'], 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Monthly Tax Table --}}
    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold text-secondary-900">Rincian Bulanan</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-secondary-50 border-b border-secondary-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-secondary-500 uppercase tracking-wider">Bulan</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-secondary-500 uppercase tracking-wider">Jumlah Karyawan</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-secondary-500 uppercase tracking-wider">Total Gaji Kotor</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-secondary-500 uppercase tracking-wider">Total PPh 21</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-secondary-500 uppercase tracking-wider">Persentase</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-secondary-200">
                    @php
                        $months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                    @endphp
                    @foreach($monthlyTax as $index => $data)
                        <tr class="hover:bg-secondary-50 transition-colors {{ $data['total_pph21'] == 0 ? 'opacity-50' : '' }}">
                            <td class="px-4 py-3 font-medium text-secondary-900">{{ $months[$data['month'] - 1] }}</td>
                            <td class="px-4 py-3 text-center text-secondary-600">{{ $data['employee_count'] }}</td>
                            <td class="px-4 py-3 text-right text-secondary-900">
                                @if($data['total_gross'] > 0)
                                    Rp {{ number_format($data['total_gross'], 0, ',', '.') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-medium text-warning-600">
                                @if($data['total_pph21'] > 0)
                                    Rp {{ number_format($data['total_pph21'], 0, ',', '.') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-secondary-600">
                                @if($data['total_gross'] > 0)
                                    {{ number_format(($data['total_pph21'] / $data['total_gross']) * 100, 2) }}%
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-secondary-50 border-t-2 border-secondary-200">
                    <tr>
                        <td class="px-4 py-3 font-bold text-secondary-900">TOTAL</td>
                        <td class="px-4 py-3 text-center font-bold text-secondary-900">-</td>
                        <td class="px-4 py-3 text-right font-bold text-secondary-900">Rp {{ number_format($grandTotal['total_gross'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right font-bold text-warning-600">Rp {{ number_format($grandTotal['total_pph21'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right font-bold text-secondary-600">
                            @if($grandTotal['total_gross'] > 0)
                                {{ number_format(($grandTotal['total_pph21'] / $grandTotal['total_gross']) * 100, 2) }}%
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection
