@extends('layouts.admin')

@section('title', 'Riwayat Payroll')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Riwayat Payroll</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Riwayat Payroll</h1>
            <p class="text-secondary-500 mt-1">Lihat riwayat slip gaji semua karyawan</p>
        </div>
    </div>
@endsection

@section('content')
    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body-sm">
            <form action="{{ route('payroll-items.index') }}" method="GET" class="flex flex-wrap items-end gap-3">
                <div class="w-48">
                    <label class="block text-xs font-medium text-secondary-500 mb-1">Karyawan</label>
                    <select name="employee_id" class="input w-full">
                        <option value="">Semua</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>
                                {{ $employee->full_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="w-28">
                    <label class="block text-xs font-medium text-secondary-500 mb-1">Tahun</label>
                    <select name="year" class="input w-full">
                        <option value="">Semua</option>
                        @foreach($years as $year)
                            <option value="{{ $year }}" {{ request('year') == $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-32">
                    <label class="block text-xs font-medium text-secondary-500 mb-1">Status</label>
                    <select name="status" class="input w-full">
                        <option value="">Semua</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Menunggu</option>
                        <option value="calculated" {{ request('status') === 'calculated' ? 'selected' : '' }}>Terhitung</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Disetujui</option>
                        <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Dibayar</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    @if(request()->hasAny(['employee_id', 'year', 'status']))
                        <a href="{{ route('payroll-items.index') }}" class="btn btn-ghost btn-sm">Reset</a>
                    @endif
                    <button type="submit" class="btn btn-primary btn-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Payroll Items List --}}
    <div class="card">
        <x-table>
            <x-slot name="header">
                <th>Karyawan</th>
                <th>Periode</th>
                <th class="text-right">Gaji Pokok</th>
                <th class="text-right">Pendapatan</th>
                <th class="text-right">Potongan</th>
                <th class="text-right">Gaji Bersih</th>
                <th>Status</th>
                <th class="text-right">Aksi</th>
            </x-slot>

            @forelse($payrollItems as $item)
                <tr>
                    <td>
                        <span class="font-medium text-secondary-900">{{ $item->employee_name }}</span>
                        <p class="text-xs text-secondary-400">{{ $item->employee_number }}</p>
                    </td>
                    <td>
                        <span class="text-secondary-700">{{ $item->payroll->period_label }}</span>
                        <p class="text-xs text-secondary-400">{{ $item->payroll->payroll_number }}</p>
                    </td>
                    <td class="text-right text-secondary-700">{{ number_format($item->basic_salary, 0, ',', '.') }}</td>
                    <td class="text-right text-success-600">+{{ number_format($item->total_earnings, 0, ',', '.') }}</td>
                    <td class="text-right text-danger-600">-{{ number_format($item->total_deductions, 0, ',', '.') }}</td>
                    <td class="text-right font-bold text-secondary-900">{{ $item->formatted_net_salary }}</td>
                    <td>
                        @switch($item->status)
                            @case('pending')
                                <x-badge type="secondary">{{ $item->status_label }}</x-badge>
                                @break
                            @case('calculated')
                                <x-badge type="warning">{{ $item->status_label }}</x-badge>
                                @break
                            @case('approved')
                                <x-badge type="primary">{{ $item->status_label }}</x-badge>
                                @break
                            @case('paid')
                                <x-badge type="success">{{ $item->status_label }}</x-badge>
                                @break
                        @endswitch
                    </td>
                    <td>
                        <div class="flex items-center justify-end gap-1">
                            <a href="{{ route('payroll-items.show', $item) }}" class="p-1.5 text-secondary-400 hover:text-primary-600 hover:bg-primary-50 rounded-md transition-colors" title="Lihat Slip Gaji">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </a>
                            <a href="{{ route('payroll-items.pdf', $item) }}" class="p-1.5 text-secondary-400 hover:text-primary-600 hover:bg-primary-50 rounded-md transition-colors" title="Download PDF" target="_blank">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center py-12">
                        <div class="flex flex-col items-center">
                            <svg class="w-12 h-12 text-secondary-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            <p class="text-secondary-500 mb-4">Belum ada riwayat payroll</p>
                            <a href="{{ route('payrolls.index') }}" class="btn btn-primary">Lihat Payroll</a>
                        </div>
                    </td>
                </tr>
            @endforelse
        </x-table>

        @if($payrollItems->hasPages())
            <div class="card-footer">
                {{ $payrollItems->links() }}
            </div>
        @endif
    </div>
@endsection
