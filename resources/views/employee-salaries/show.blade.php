@extends('layouts.admin')

@section('title', 'Detail Gaji Karyawan')

@section('breadcrumb')
    <a href="{{ route('employee-salaries.index') }}" class="text-slate-500 hover:text-primary-600">Gaji Karyawan</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">{{ $employeeSalary->employee->full_name }}</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-full bg-primary-100 flex items-center justify-center">
                <span class="text-primary-700 font-bold text-xl">{{ substr($employeeSalary->employee->first_name, 0, 1) }}{{ substr($employeeSalary->employee->last_name, 0, 1) }}</span>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">{{ $employeeSalary->employee->full_name }}</h1>
                <div class="flex items-center gap-2 mt-1">
                    <span class="text-secondary-500">{{ $employeeSalary->employee->employee_id }}</span>
                    <span class="text-secondary-300">|</span>
                    @if($employeeSalary->is_active)
                        <x-badge type="success">Aktif</x-badge>
                    @else
                        <x-badge type="secondary">Tidak Aktif</x-badge>
                    @endif
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('employee-salaries.edit', $employeeSalary) }}" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Edit
            </a>
            <a href="{{ route('employee-salaries.index') }}" class="btn btn-ghost">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Kembali
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Salary Summary --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Ringkasan Gaji</h3>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                        <div class="text-center p-4 bg-secondary-50 rounded-lg">
                            <p class="text-sm text-secondary-500 mb-1">Gaji Pokok</p>
                            <p class="text-xl font-bold text-secondary-900">{{ $employeeSalary->formatted_basic_salary }}</p>
                        </div>
                        <div class="text-center p-4 bg-success-50 rounded-lg">
                            <p class="text-sm text-success-600 mb-1">Total Pendapatan</p>
                            <p class="text-xl font-bold text-success-700">Rp {{ number_format($employeeSalary->getTotalEarnings(), 0, ',', '.') }}</p>
                        </div>
                        <div class="text-center p-4 bg-danger-50 rounded-lg">
                            <p class="text-sm text-danger-600 mb-1">Total Potongan</p>
                            <p class="text-xl font-bold text-danger-700">Rp {{ number_format($employeeSalary->getTotalDeductions(), 0, ',', '.') }}</p>
                        </div>
                    </div>

                    <div class="mt-6 pt-6 border-t border-secondary-200">
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-medium text-secondary-700">Gaji Kotor</span>
                            <span class="text-lg font-bold text-secondary-900">Rp {{ number_format($employeeSalary->getGrossSalary(), 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between items-center mt-2 pt-2 border-t border-secondary-200">
                            <span class="text-xl font-semibold text-secondary-900">Gaji Bersih (Take Home Pay)</span>
                            <span class="text-xl font-bold text-primary-600">Rp {{ number_format($employeeSalary->getNetSalary(), 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Salary Components --}}
            @if($employeeSalary->components->count() > 0)
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Komponen Gaji</h3>
                    </div>
                    <div class="card-body">
                        @php
                            $earnings = $employeeSalary->components->filter(fn($c) => $c->salaryComponent->type === 'earning');
                            $deductions = $employeeSalary->components->filter(fn($c) => $c->salaryComponent->type === 'deduction');
                        @endphp

                        {{-- Earnings --}}
                        @if($earnings->count() > 0)
                            <div class="mb-6">
                                <h4 class="text-sm font-medium text-secondary-700 mb-3 flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-success-500"></span>
                                    Pendapatan / Tunjangan
                                </h4>
                                <div class="space-y-2">
                                    @foreach($earnings as $compItem)
                                        <div class="flex justify-between items-center py-2 border-b border-secondary-100 last:border-0">
                                            <span class="text-secondary-600">
                                                {{ $compItem->salaryComponent->name }}
                                                @if($compItem->salaryComponent->code)
                                                    <span class="text-secondary-400">({{ $compItem->salaryComponent->code }})</span>
                                                @endif
                                            </span>
                                            <span class="font-medium text-success-600">+ Rp {{ number_format($compItem->amount, 0, ',', '.') }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Deductions --}}
                        @if($deductions->count() > 0)
                            <div>
                                <h4 class="text-sm font-medium text-secondary-700 mb-3 flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-danger-500"></span>
                                    Potongan
                                </h4>
                                <div class="space-y-2">
                                    @foreach($deductions as $compItem)
                                        <div class="flex justify-between items-center py-2 border-b border-secondary-100 last:border-0">
                                            <span class="text-secondary-600">
                                                {{ $compItem->salaryComponent->name }}
                                                @if($compItem->salaryComponent->code)
                                                    <span class="text-secondary-400">({{ $compItem->salaryComponent->code }})</span>
                                                @endif
                                            </span>
                                            <span class="font-medium text-danger-600">- Rp {{ number_format($compItem->amount, 0, ',', '.') }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Payment Method --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Metode Pembayaran</h3>
                </div>
                <div class="card-body">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-secondary-100 flex items-center justify-center">
                            @if($employeeSalary->payment_method === 'transfer')
                                <svg class="w-5 h-5 text-secondary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                            @else
                                <svg class="w-5 h-5 text-secondary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            @endif
                        </div>
                        <div>
                            <p class="font-medium text-secondary-900">{{ $employeeSalary->payment_method_label }}</p>
                        </div>
                    </div>

                    @if($employeeSalary->payment_method === 'transfer' && $employeeSalary->bank_name)
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-secondary-500">Bank</dt>
                                <dd class="text-secondary-900 font-medium">{{ $employeeSalary->bank_name }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-secondary-500">No. Rekening</dt>
                                <dd class="text-secondary-900 font-medium">{{ $employeeSalary->bank_account_number }}</dd>
                            </div>
                            @if($employeeSalary->bank_account_name)
                                <div class="flex justify-between">
                                    <dt class="text-secondary-500">Atas Nama</dt>
                                    <dd class="text-secondary-900 font-medium">{{ $employeeSalary->bank_account_name }}</dd>
                                </div>
                            @endif
                        </dl>
                    @endif
                </div>
            </div>

            {{-- Period --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Periode Berlaku</h3>
                </div>
                <div class="card-body">
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-secondary-500">Berlaku Sejak</dt>
                            <dd class="text-secondary-900 font-medium">{{ $employeeSalary->effective_date->format('d M Y') }}</dd>
                        </div>
                        @if($employeeSalary->end_date)
                            <div class="flex justify-between">
                                <dt class="text-secondary-500">Berakhir</dt>
                                <dd class="text-secondary-900 font-medium">{{ $employeeSalary->end_date->format('d M Y') }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>

            {{-- Actions --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Aksi</h3>
                </div>
                <div class="card-body space-y-2">
                    <button
                        type="button"
                        @click="$dispatch('confirm-dialog', {
                            title: 'Hapus Gaji Karyawan',
                            message: 'Apakah Anda yakin ingin menghapus pengaturan gaji ini?',
                            confirmText: 'Ya, Hapus',
                            type: 'danger',
                            formAction: '{{ route('employee-salaries.destroy', $employeeSalary) }}'
                        })"
                        class="btn btn-ghost w-full justify-start text-danger-600 hover:text-danger-700"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Hapus
                    </button>
                </div>
            </div>

            {{-- Timestamps --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Informasi</h3>
                </div>
                <div class="card-body space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-secondary-500">Dibuat</span>
                        <span class="text-secondary-900">{{ $employeeSalary->created_at->format('d M Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-secondary-500">Diperbarui</span>
                        <span class="text-secondary-900">{{ $employeeSalary->updated_at->format('d M Y H:i') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
