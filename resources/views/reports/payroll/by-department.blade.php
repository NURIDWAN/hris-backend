@extends('layouts.admin')

@section('title', 'Laporan Penggajian Per Departemen')

@section('breadcrumb')
    <a href="{{ route('reports.payroll') }}" class="text-primary-600 hover:text-primary-700">Laporan Penggajian</a>
    <span class="mx-2 text-secondary-400">/</span>
    <span class="text-slate-700 font-medium">Per Departemen</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Laporan Penggajian Per Departemen</h1>
            <p class="text-secondary-500 mt-1">{{ \Carbon\Carbon::create($year, $month)->translatedFormat('F Y') }}</p>
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
            <form action="{{ route('reports.payroll.by-department') }}" method="GET" class="flex flex-wrap items-end gap-3">
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
                        @foreach(['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'] as $index => $monthName)
                            <option value="{{ $index + 1 }}" {{ $month == $index + 1 ? 'selected' : '' }}>{{ $monthName }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    Filter
                </button>
            </form>
        </div>
    </div>

    @if(!$payroll)
        <div class="card">
            <div class="card-body text-center py-12">
                <svg class="w-12 h-12 text-secondary-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="text-secondary-500">Tidak ada data payroll untuk periode ini</p>
            </div>
        </div>
    @else
        {{-- Department Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
            @forelse($departments as $department)
                @if($department->employee_count > 0)
                    <div class="card">
                        <div class="card-body">
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <h3 class="font-semibold text-secondary-900">{{ $department->name }}</h3>
                                    <p class="text-sm text-secondary-500">{{ $department->employee_count }} karyawan</p>
                                </div>
                                <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-primary-100 text-primary-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                </span>
                            </div>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-secondary-500">Gaji Kotor</span>
                                    <span class="font-medium text-secondary-900">Rp {{ number_format($department->total_gross, 0, ',', '.') }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-secondary-500">Potongan</span>
                                    <span class="font-medium text-danger-600">Rp {{ number_format($department->total_deductions, 0, ',', '.') }}</span>
                                </div>
                                <div class="pt-3 border-t border-secondary-100 flex justify-between items-center">
                                    <span class="text-sm font-medium text-secondary-700">Gaji Bersih</span>
                                    <span class="font-bold text-success-600">Rp {{ number_format($department->total_net, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @empty
                <div class="col-span-full">
                    <p class="text-center text-secondary-500">Tidak ada departemen dengan data penggajian</p>
                </div>
            @endforelse
        </div>

        {{-- Summary Table --}}
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold text-secondary-900">Ringkasan Per Departemen</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-secondary-50 border-b border-secondary-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-secondary-500 uppercase tracking-wider">Departemen</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-secondary-500 uppercase tracking-wider">Jumlah Karyawan</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-secondary-500 uppercase tracking-wider">Total Gaji Kotor</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-secondary-500 uppercase tracking-wider">Total Potongan</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-secondary-500 uppercase tracking-wider">Total Gaji Bersih</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-secondary-200">
                        @php
                            $grandTotalGross = 0;
                            $grandTotalDeductions = 0;
                            $grandTotalNet = 0;
                            $grandTotalEmployees = 0;
                        @endphp
                        @foreach($departments->where('employee_count', '>', 0) as $department)
                            @php
                                $grandTotalGross += $department->total_gross;
                                $grandTotalDeductions += $department->total_deductions;
                                $grandTotalNet += $department->total_net;
                                $grandTotalEmployees += $department->employee_count;
                            @endphp
                            <tr class="hover:bg-secondary-50 transition-colors">
                                <td class="px-4 py-3 font-medium text-secondary-900">{{ $department->name }}</td>
                                <td class="px-4 py-3 text-center text-secondary-600">{{ $department->employee_count }}</td>
                                <td class="px-4 py-3 text-right text-secondary-900">Rp {{ number_format($department->total_gross, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right text-danger-600">Rp {{ number_format($department->total_deductions, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right font-medium text-success-600">Rp {{ number_format($department->total_net, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-secondary-50 border-t-2 border-secondary-200">
                        <tr>
                            <td class="px-4 py-3 font-bold text-secondary-900">TOTAL</td>
                            <td class="px-4 py-3 text-center font-bold text-secondary-900">{{ $grandTotalEmployees }}</td>
                            <td class="px-4 py-3 text-right font-bold text-secondary-900">Rp {{ number_format($grandTotalGross, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right font-bold text-danger-600">Rp {{ number_format($grandTotalDeductions, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right font-bold text-success-600">Rp {{ number_format($grandTotalNet, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif
@endsection
