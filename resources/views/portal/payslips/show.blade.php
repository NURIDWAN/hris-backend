@extends('layouts.portal')

@section('title', 'Detail Slip Gaji')

@section('breadcrumb')
    <a href="{{ route('portal.dashboard') }}" class="text-slate-500 hover:text-primary-600">Dashboard</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <a href="{{ route('portal.payslips.index') }}" class="text-slate-500 hover:text-primary-600">Slip Gaji</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">{{ $payrollItem->payroll->period_start->format('M Y') }}</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Slip Gaji</h1>
            <p class="text-secondary-500 mt-1">Periode: {{ $payrollItem->payroll->period_start->format('d M Y') }} - {{ $payrollItem->payroll->period_end->format('d M Y') }}</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('portal.payslips.index') }}" class="btn btn-ghost">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Kembali
            </a>
            <a href="{{ route('portal.payslips.pdf', $payrollItem) }}" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Download PDF
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="max-w-3xl mx-auto">
        <div class="card">
            <div class="card-body p-8">
                {{-- Header --}}
                <div class="flex items-start justify-between mb-8 pb-6 border-b border-secondary-200">
                    <div>
                        <h2 class="text-2xl font-bold text-primary-600">GajiPro</h2>
                        <p class="text-sm text-secondary-500 mt-1">HRIS & Payroll System</p>
                    </div>
                    <div class="text-right">
                        <div class="text-xl font-bold text-secondary-900">SLIP GAJI</div>
                        <div class="text-sm text-secondary-500">{{ $payrollItem->payroll->period_start->format('F Y') }}</div>
                    </div>
                </div>

                {{-- Employee Info --}}
                <div class="grid grid-cols-2 gap-6 mb-8">
                    <div>
                        <div class="text-sm text-secondary-500">Nama Karyawan</div>
                        <div class="font-semibold text-secondary-900">{{ $employee->full_name }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-secondary-500">ID Karyawan</div>
                        <div class="font-semibold text-secondary-900">{{ $employee->employee_id }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-secondary-500">Departemen</div>
                        <div class="font-semibold text-secondary-900">{{ $employee->department->name ?? '-' }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-secondary-500">Jabatan</div>
                        <div class="font-semibold text-secondary-900">{{ $employee->position->name ?? '-' }}</div>
                    </div>
                </div>

                {{-- Attendance Summary --}}
                @if($payrollItem->working_days > 0)
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-secondary-900 mb-4">Ringkasan Kehadiran</h3>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <div class="bg-secondary-50 rounded-lg p-4 text-center">
                                <div class="text-2xl font-bold text-secondary-900">{{ $payrollItem->working_days }}</div>
                                <div class="text-sm text-secondary-500">Hari Kerja</div>
                            </div>
                            <div class="bg-success-50 rounded-lg p-4 text-center">
                                <div class="text-2xl font-bold text-success-600">{{ $payrollItem->present_days }}</div>
                                <div class="text-sm text-secondary-500">Hadir</div>
                            </div>
                            <div class="bg-danger-50 rounded-lg p-4 text-center">
                                <div class="text-2xl font-bold text-danger-600">{{ $payrollItem->absent_days }}</div>
                                <div class="text-sm text-secondary-500">Tidak Hadir</div>
                            </div>
                            <div class="bg-warning-50 rounded-lg p-4 text-center">
                                <div class="text-2xl font-bold text-warning-600">{{ $payrollItem->late_days ?? 0 }}</div>
                                <div class="text-sm text-secondary-500">Terlambat</div>
                            </div>
                        </div>
                        @if($payrollItem->leave_days > 0 || $payrollItem->overtime_hours > 0)
                            <div class="grid grid-cols-2 gap-4 mt-4">
                                @if($payrollItem->leave_days > 0)
                                    <div class="bg-info-50 rounded-lg p-4 text-center">
                                        <div class="text-2xl font-bold text-info-600">{{ $payrollItem->leave_days }}</div>
                                        <div class="text-sm text-secondary-500">Hari Cuti</div>
                                    </div>
                                @endif
                                @if($payrollItem->overtime_hours > 0)
                                    <div class="bg-purple-50 rounded-lg p-4 text-center">
                                        <div class="text-2xl font-bold text-purple-600">{{ $payrollItem->overtime_hours }}</div>
                                        <div class="text-sm text-secondary-500">Jam Lembur</div>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Earnings --}}
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-secondary-900 mb-4">Penghasilan</h3>
                    <div class="bg-secondary-50 rounded-lg p-4">
                        <div class="flex justify-between py-2 border-b border-secondary-200">
                            <span class="text-secondary-600">Gaji Pokok</span>
                            <span class="font-medium">Rp {{ number_format($payrollItem->basic_salary ?? $payrollItem->gross_salary, 0, ',', '.') }}</span>
                        </div>
                        @if($payrollItem->allowances && is_array($payrollItem->allowances))
                            @foreach($payrollItem->allowances as $name => $amount)
                                <div class="flex justify-between py-2 border-b border-secondary-200">
                                    <span class="text-secondary-600">{{ $name }}</span>
                                    <span class="font-medium">Rp {{ number_format($amount, 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                        @endif
                        <div class="flex justify-between py-2 font-semibold">
                            <span>Total Penghasilan</span>
                            <span class="text-success-600">Rp {{ number_format($payrollItem->gross_salary, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                {{-- Deductions --}}
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-secondary-900 mb-4">Potongan</h3>
                    <div class="bg-secondary-50 rounded-lg p-4">
                        @if($payrollItem->deduction_details && is_array($payrollItem->deduction_details))
                            @foreach($payrollItem->deduction_details as $name => $amount)
                                <div class="flex justify-between py-2 border-b border-secondary-200">
                                    <span class="text-secondary-600">{{ $name }}</span>
                                    <span class="font-medium text-danger-600">-Rp {{ number_format($amount, 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                        @else
                            <div class="flex justify-between py-2 border-b border-secondary-200">
                                <span class="text-secondary-600">Total Potongan</span>
                                <span class="font-medium text-danger-600">-Rp {{ number_format($payrollItem->total_deductions, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between py-2 font-semibold">
                            <span>Total Potongan</span>
                            <span class="text-danger-600">-Rp {{ number_format($payrollItem->total_deductions, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                {{-- Net Salary --}}
                <div class="bg-primary-50 rounded-lg p-6 text-center">
                    <div class="text-secondary-500 mb-2">Gaji Bersih (Take Home Pay)</div>
                    <div class="text-3xl font-bold text-primary-600">
                        Rp {{ number_format($payrollItem->net_salary, 0, ',', '.') }}
                    </div>
                </div>

                {{-- Payment Info --}}
                @if($payrollItem->payroll->status === 'paid')
                    <div class="mt-6 p-4 bg-success-50 rounded-lg">
                        <div class="flex items-center gap-3">
                            <svg class="w-6 h-6 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <div class="font-medium text-success-800">Gaji Sudah Dibayarkan</div>
                                <div class="text-sm text-success-600">
                                    Ditransfer ke rekening {{ $employee->bank_name ?? '-' }} {{ $employee->bank_account_number ?? '-' }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
