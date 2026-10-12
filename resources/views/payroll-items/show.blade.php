@extends('layouts.admin')

@section('title', 'Slip Gaji')

@section('breadcrumb')
    <a href="{{ route('payrolls.index') }}" class="text-slate-500 hover:text-primary-600">Payroll</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <a href="{{ route('payrolls.show', $payrollItem->payroll) }}" class="text-slate-500 hover:text-primary-600">{{ $payrollItem->payroll->name }}</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">{{ $payrollItem->employee_name }}</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('payrolls.show', $payrollItem->payroll) }}" class="btn btn-ghost btn-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">Slip Gaji</h1>
                <p class="text-secondary-500 mt-1">{{ $payrollItem->payroll->period_label }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            @if($payrollItem->payroll->canBeAdjusted())
                <button type="button" class="btn btn-warning"
                    @click="$dispatch('confirm-dialog', {
                        title: 'Hitung Ulang Slip Gaji',
                        message: 'Sistem akan menghitung ulang gaji karyawan ini berdasarkan data terbaru. Data adjustment manual akan di-reset. Lanjutkan?',
                        confirmText: 'Ya, Hitung Ulang',
                        type: 'warning',
                        method: 'POST',
                        formAction: '{{ route('payroll-items.recalculate', $payrollItem) }}'
                    })">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Hitung Ulang
                </button>
                <a href="{{ route('payroll-items.edit', $payrollItem) }}" class="btn btn-accent">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Adjust Gaji
                </a>
            @endif
            <a href="{{ route('payroll-items.pdf', $payrollItem) }}" class="btn btn-primary" target="_blank">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Download PDF
            </a>
            <button onclick="window.print()" class="btn btn-ghost">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Cetak
            </button>
        </div>
    </div>
@endsection

@section('content')
    <div class="max-w-4xl mx-auto">
        {{-- Slip Gaji Card --}}
        <div class="card print:shadow-none print:border-0" id="slip-gaji">
            {{-- Header --}}
            <div class="card-body border-b border-secondary-200 print:border-black">
                <div class="flex justify-between items-start">
                    <div>
                        <h2 class="text-xl font-bold text-secondary-900">{{ auth()->user()->company->name ?? 'Nama Perusahaan' }}</h2>
                        <p class="text-sm text-secondary-500 mt-1">{{ auth()->user()->company->address ?? 'Alamat Perusahaan' }}</p>
                    </div>
                    <div class="text-right">
                        <h3 class="text-lg font-bold text-secondary-900">SLIP GAJI</h3>
                        <p class="text-sm text-secondary-500">{{ $payrollItem->payroll->period_label }}</p>
                        <p class="text-sm text-secondary-500">{{ $payrollItem->payroll->payroll_number }}</p>
                    </div>
                </div>
            </div>

            {{-- Employee Info --}}
            <div class="card-body border-b border-secondary-200">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <dl class="space-y-2 text-sm">
                            <div class="flex">
                                <dt class="w-32 text-secondary-500">Nama</dt>
                                <dd class="font-medium text-secondary-900">: {{ $payrollItem->employee_name }}</dd>
                            </div>
                            <div class="flex">
                                <dt class="w-32 text-secondary-500">NIK</dt>
                                <dd class="font-medium text-secondary-900">: {{ $payrollItem->employee_number }}</dd>
                            </div>
                            <div class="flex">
                                <dt class="w-32 text-secondary-500">Departemen</dt>
                                <dd class="font-medium text-secondary-900">: {{ $payrollItem->department_name ?? '-' }}</dd>
                            </div>
                        </dl>
                    </div>
                    <div>
                        <dl class="space-y-2 text-sm">
                            <div class="flex">
                                <dt class="w-32 text-secondary-500">Jabatan</dt>
                                <dd class="font-medium text-secondary-900">: {{ $payrollItem->position_name ?? '-' }}</dd>
                            </div>
                            <div class="flex">
                                <dt class="w-32 text-secondary-500">Status</dt>
                                <dd class="font-medium text-secondary-900">:
                                    @switch($payrollItem->status)
                                        @case('pending')
                                            <span class="text-secondary-600">Menunggu</span>
                                            @break
                                        @case('calculated')
                                            <span class="text-warning-600">Terhitung</span>
                                            @break
                                        @case('approved')
                                            <span class="text-primary-600">Disetujui</span>
                                            @break
                                        @case('paid')
                                            <span class="text-success-600">Dibayar</span>
                                            @break
                                    @endswitch
                                </dd>
                            </div>
                            <div class="flex">
                                <dt class="w-32 text-secondary-500">Periode</dt>
                                <dd class="font-medium text-secondary-900">: {{ $payrollItem->payroll->period_start->format('d M') }} - {{ $payrollItem->payroll->period_end->format('d M Y') }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            {{-- Attendance Summary --}}
            <div class="card-body border-b border-secondary-200 bg-secondary-50">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-medium text-secondary-700">Ringkasan Kehadiran</h4>
                    @if($payrollItem->working_days > 0)
                        @php
                            $attendancePercent = round(($payrollItem->present_days / $payrollItem->working_days) * 100, 1);
                        @endphp
                        <span class="text-sm font-medium {{ $attendancePercent >= 90 ? 'text-success-600' : ($attendancePercent >= 75 ? 'text-warning-600' : 'text-danger-600') }}">
                            {{ $attendancePercent }}% Kehadiran
                        </span>
                    @endif
                </div>
                <div class="grid grid-cols-6 gap-4 text-center">
                    <div>
                        <p class="text-xs text-secondary-500">Hari Kerja</p>
                        <p class="text-lg font-bold text-secondary-900">{{ $payrollItem->working_days }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-secondary-500">Hadir</p>
                        <p class="text-lg font-bold text-success-600">{{ $payrollItem->present_days }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-secondary-500">Tidak Hadir</p>
                        <p class="text-lg font-bold text-danger-600">{{ $payrollItem->absent_days }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-secondary-500">Terlambat</p>
                        <p class="text-lg font-bold text-warning-600">{{ $payrollItem->late_days }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-secondary-500">Cuti</p>
                        <p class="text-lg font-bold text-primary-600">{{ $payrollItem->leave_days }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-secondary-500">Lembur</p>
                        <p class="text-lg font-bold text-purple-600">{{ $payrollItem->overtime_hours }} <span class="text-xs font-normal">jam</span></p>
                    </div>
                </div>
            </div>

            {{-- Salary Details --}}
            <div class="card-body">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Pendapatan --}}
                    <div>
                        <h4 class="text-sm font-medium text-secondary-700 mb-3 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-success-500"></span>
                            PENDAPATAN
                        </h4>
                        <table class="w-full text-sm">
                            <tbody>
                                <tr class="border-b border-secondary-100">
                                    <td class="py-2 text-secondary-600">Gaji Pokok</td>
                                    <td class="py-2 text-right font-medium text-secondary-900">Rp {{ number_format($payrollItem->basic_salary, 0, ',', '.') }}</td>
                                </tr>
                                @php
                                    $earnings = $payrollItem->details->where('type', 'earning')->filter(fn ($e) => $e->component_code !== 'BASIC');
                                @endphp
                                @foreach($earnings as $earning)
                                    <tr class="border-b border-secondary-100">
                                        <td class="py-2 text-secondary-600">{{ $earning->component_name }}</td>
                                        <td class="py-2 text-right font-medium text-secondary-900">Rp {{ number_format($earning->amount, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-success-50">
                                    <td class="py-2 px-2 font-medium text-success-700">Total Pendapatan</td>
                                    <td class="py-2 px-2 text-right font-bold text-success-700">Rp {{ number_format((float)$payrollItem->basic_salary + (float)$payrollItem->total_earnings, 0, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    {{-- Potongan --}}
                    <div>
                        <h4 class="text-sm font-medium text-secondary-700 mb-3 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-danger-500"></span>
                            POTONGAN
                        </h4>
                        <table class="w-full text-sm">
                            <tbody>
                                @php
                                    $deductions = $payrollItem->details->where('type', 'deduction');
                                @endphp
                                @forelse($deductions as $deduction)
                                    <tr class="border-b border-secondary-100">
                                        <td class="py-2 text-secondary-600">{{ $deduction->component_name }}</td>
                                        <td class="py-2 text-right font-medium text-secondary-900">Rp {{ number_format($deduction->amount, 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr class="border-b border-secondary-100">
                                        <td class="py-2 text-secondary-400 italic" colspan="2">Tidak ada potongan</td>
                                    </tr>
                                @endforelse
                                @if($payrollItem->tax_amount > 0)
                                    <tr class="border-b border-secondary-100">
                                        <td class="py-2 text-secondary-600">PPh 21</td>
                                        <td class="py-2 text-right font-medium text-secondary-900">Rp {{ number_format($payrollItem->tax_amount, 0, ',', '.') }}</td>
                                    </tr>
                                @endif
                            </tbody>
                            <tfoot>
                                <tr class="bg-danger-50">
                                    <td class="py-2 px-2 font-medium text-danger-700">Total Potongan</td>
                                    <td class="py-2 px-2 text-right font-bold text-danger-700">Rp {{ number_format((float)$payrollItem->total_deductions + (float)$payrollItem->tax_amount, 0, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Net Salary --}}
            <div class="card-body bg-primary-50 border-t border-primary-200">
                <div class="flex justify-between items-center">
                    <div>
                        <h4 class="text-lg font-bold text-primary-900">GAJI BERSIH (Take Home Pay)</h4>
                        <p class="text-sm text-primary-600">Diterima melalui {{ $payrollItem->payment_info }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-3xl font-bold text-primary-700">{{ $payrollItem->formatted_net_salary }}</p>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="card-body border-t border-secondary-200 print:border-black">
                <div class="flex justify-between items-center text-sm text-secondary-500">
                    <div>
                        <p>Dicetak pada: {{ now()->format('d M Y H:i') }}</p>
                    </div>
                    <div class="text-right">
                        @if($payrollItem->isPaid())
                            <p class="text-success-600 font-medium">Dibayar pada: {{ $payrollItem->paid_at?->format('d M Y') }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Print Notice --}}
        <div class="mt-4 text-center text-sm text-secondary-500 print:hidden">
            <p>Slip gaji ini digenerate secara otomatis dan sah tanpa tanda tangan.</p>
        </div>
    </div>

    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            #slip-gaji, #slip-gaji * {
                visibility: visible;
            }
            #slip-gaji {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
        }
    </style>
@endsection
