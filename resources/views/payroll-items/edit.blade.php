@extends('layouts.admin')

@section('title', 'Adjust Slip Gaji')

@section('breadcrumb')
    <a href="{{ route('payrolls.index') }}" class="text-slate-500 hover:text-primary-600">Payroll</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <a href="{{ route('payrolls.show', $payrollItem->payroll) }}" class="text-slate-500 hover:text-primary-600">{{ $payrollItem->payroll->name }}</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <a href="{{ route('payroll-items.show', $payrollItem) }}" class="text-slate-500 hover:text-primary-600">{{ $payrollItem->employee_name }}</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Adjust</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('payroll-items.show', $payrollItem) }}" class="btn btn-ghost btn-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">Adjust Slip Gaji</h1>
                <p class="text-secondary-500 mt-1">{{ $payrollItem->employee_name }} &mdash; {{ $payrollItem->payroll->period_label }}</p>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="max-w-4xl mx-auto space-y-6">

        {{-- Flash Messages --}}
        @if(session('success'))
            <x-alert type="success" dismissible>{{ session('success') }}</x-alert>
        @endif
        @if(session('error'))
            <x-alert type="danger" dismissible>{{ session('error') }}</x-alert>
        @endif

        {{-- Main Adjustment Form --}}
        <form method="POST" action="{{ route('payroll-items.update', $payrollItem) }}">
            @csrf
            @method('PUT')

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Gaji Pokok & Komponen</h3>
                </div>
                <div class="card-body space-y-6">

                    {{-- Basic Salary --}}
                    <div x-data="currencyInput({{ old('basic_salary', (int) $payrollItem->basic_salary) }})">
                        <label for="basic_salary" class="block text-sm font-medium text-secondary-700 mb-1">
                            Gaji Pokok <span class="text-danger-500">*</span>
                        </label>
                        <input type="hidden" name="basic_salary" :value="value">
                        <div class="input-group" style="position: relative;">
                            <span class="input-group-icon">Rp</span>
                            <input type="text" id="basic_salary"
                                   x-model="display" @input="updateValue($event)" @blur="formatDisplay()"
                                   class="input w-full @error('basic_salary') border-danger-500 @enderror"
                                   inputmode="numeric" required>
                        </div>
                        @error('basic_salary')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Earnings Section --}}
                    <div>
                        <h4 class="text-sm font-medium text-secondary-700 mb-3 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-success-500"></span>
                            PENDAPATAN
                        </h4>

                        @php
                            $earnings = $payrollItem->details->where('type', 'earning')->filter(fn ($e) => $e->component_code !== 'BASIC');
                            $detailIndex = 0;
                        @endphp

                        @if($earnings->count() > 0)
                            <div class="space-y-3">
                                @foreach($earnings as $earning)
                                    <div class="flex items-center gap-3">
                                        <div class="flex-1">
                                            <label class="block text-xs text-secondary-500 mb-1">
                                                {{ $earning->component_name }}
                                                @if(in_array($earning->category, $autoCategories))
                                                    <span class="text-info-500">(Otomatis)</span>
                                                @endif
                                            </label>
                                            @if(in_array($earning->category, $autoCategories))
                                                <div class="input-group" style="position: relative;">
                                                    <span class="input-group-icon">Rp</span>
                                                    <input type="text" value="{{ number_format((int) $earning->amount, 0, ',', '.') }}"
                                                           class="input w-full bg-secondary-100" disabled>
                                                </div>
                                            @else
                                                <div x-data="currencyInput({{ old("details.{$detailIndex}.amount", (int) $earning->amount) }})">
                                                    <input type="hidden" name="details[{{ $detailIndex }}][id]" value="{{ $earning->id }}">
                                                    <input type="hidden" name="details[{{ $detailIndex }}][amount]" :value="value">
                                                    <div class="input-group" style="position: relative;">
                                                        <span class="input-group-icon">Rp</span>
                                                        <input type="text"
                                                               x-model="display" @input="updateValue($event)" @blur="formatDisplay()"
                                                               class="input w-full" inputmode="numeric">
                                                    </div>
                                                </div>
                                                @php $detailIndex++ @endphp
                                            @endif
                                        </div>
                                        @if(!in_array($earning->category, $autoCategories))
                                            <div class="pt-5">
                                                <button type="button"
                                                    @click="$dispatch('confirm-dialog', {
                                                        title: 'Hapus Komponen',
                                                        message: 'Hapus komponen {{ $earning->component_name }}?',
                                                        confirmText: 'Ya, Hapus',
                                                        type: 'danger',
                                                        formAction: '{{ route('payroll-items.remove-detail', [$payrollItem, $earning]) }}'
                                                    })"
                                                    class="btn btn-ghost btn-sm text-danger-600" title="Hapus">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-secondary-400 italic">Belum ada komponen pendapatan.</p>
                        @endif
                    </div>

                    {{-- Deductions Section --}}
                    <div>
                        <h4 class="text-sm font-medium text-secondary-700 mb-3 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-danger-500"></span>
                            POTONGAN
                        </h4>

                        @php
                            $deductions = $payrollItem->details->where('type', 'deduction');
                        @endphp

                        @if($deductions->count() > 0)
                            <div class="space-y-3">
                                @foreach($deductions as $deduction)
                                    <div class="flex items-center gap-3">
                                        <div class="flex-1">
                                            <label class="block text-xs text-secondary-500 mb-1">
                                                {{ $deduction->component_name }}
                                                @if(in_array($deduction->category, $autoCategories))
                                                    <span class="text-info-500">(Otomatis)</span>
                                                @endif
                                            </label>
                                            @if(in_array($deduction->category, $autoCategories))
                                                <div class="input-group" style="position: relative;">
                                                    <span class="input-group-icon">Rp</span>
                                                    <input type="text" value="{{ number_format((int) $deduction->amount, 0, ',', '.') }}"
                                                           class="input w-full bg-secondary-100" disabled>
                                                </div>
                                            @else
                                                <div x-data="currencyInput({{ old("details.{$detailIndex}.amount", (int) $deduction->amount) }})">
                                                    <input type="hidden" name="details[{{ $detailIndex }}][id]" value="{{ $deduction->id }}">
                                                    <input type="hidden" name="details[{{ $detailIndex }}][amount]" :value="value">
                                                    <div class="input-group" style="position: relative;">
                                                        <span class="input-group-icon">Rp</span>
                                                        <input type="text"
                                                               x-model="display" @input="updateValue($event)" @blur="formatDisplay()"
                                                               class="input w-full" inputmode="numeric">
                                                    </div>
                                                </div>
                                                @php $detailIndex++ @endphp
                                            @endif
                                        </div>
                                        @if(!in_array($deduction->category, $autoCategories))
                                            <div class="pt-5">
                                                <button type="button"
                                                    @click="$dispatch('confirm-dialog', {
                                                        title: 'Hapus Komponen',
                                                        message: 'Hapus komponen {{ $deduction->component_name }}?',
                                                        confirmText: 'Ya, Hapus',
                                                        type: 'danger',
                                                        formAction: '{{ route('payroll-items.remove-detail', [$payrollItem, $deduction]) }}'
                                                    })"
                                                    class="btn btn-ghost btn-sm text-danger-600" title="Hapus">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-secondary-400 italic">Belum ada komponen potongan.</p>
                        @endif

                        {{-- PPh 21 (always read-only) --}}
                        @if($payrollItem->tax_amount > 0)
                            <div class="mt-3">
                                <label class="block text-xs text-secondary-500 mb-1">
                                    PPh 21 <span class="text-info-500">(Otomatis)</span>
                                </label>
                                <div class="input-group" style="position: relative;">
                                    <span class="input-group-icon">Rp</span>
                                    <input type="text" value="{{ number_format((int) $payrollItem->tax_amount, 0, ',', '.') }}"
                                           class="input w-full bg-secondary-100" disabled>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Summary Preview --}}
                    <div class="border-t border-secondary-200 pt-4">
                        <h4 class="text-sm font-medium text-secondary-700 mb-3">Ringkasan Saat Ini</h4>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div class="flex justify-between">
                                <span class="text-secondary-500">Total Pendapatan</span>
                                <span class="font-medium text-success-600">Rp {{ number_format((float)$payrollItem->basic_salary + (float)$payrollItem->total_earnings, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-secondary-500">Total Potongan</span>
                                <span class="font-medium text-danger-600">Rp {{ number_format((float)$payrollItem->total_deductions + (float)$payrollItem->tax_amount, 0, ',', '.') }}</span>
                            </div>
                            <div class="col-span-2 flex justify-between border-t border-secondary-200 pt-2">
                                <span class="font-bold text-secondary-900">Gaji Bersih (Take Home Pay)</span>
                                <span class="font-bold text-primary-700 text-lg">{{ $payrollItem->formatted_net_salary }}</span>
                            </div>
                        </div>
                        <p class="text-xs text-secondary-400 mt-2">
                            * BPJS dan PPh21 akan dihitung ulang otomatis setelah disimpan.
                        </p>
                    </div>
                </div>

                <div class="card-footer flex justify-end gap-3">
                    <a href="{{ route('payroll-items.show', $payrollItem) }}" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Simpan Adjustment
                    </button>
                </div>
            </div>
        </form>

        {{-- Add Component Form --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Tambah Komponen Baru</h3>
            </div>
            <form method="POST" action="{{ route('payroll-items.add-detail', $payrollItem) }}">
                @csrf
                <div class="card-body">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="component_name" class="block text-sm font-medium text-secondary-700 mb-1">
                                Nama Komponen <span class="text-danger-500">*</span>
                            </label>
                            <input type="text" name="component_name" id="component_name"
                                   value="{{ old('component_name') }}"
                                   class="input w-full @error('component_name') border-danger-500 @enderror"
                                   placeholder="Contoh: Bonus Kinerja" required>
                            @error('component_name')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div x-data="currencyInput({{ old('amount', 0) }})">
                            <label for="add_amount" class="block text-sm font-medium text-secondary-700 mb-1">
                                Jumlah <span class="text-danger-500">*</span>
                            </label>
                            <input type="hidden" name="amount" :value="value">
                            <div class="input-group" style="position: relative;">
                                <span class="input-group-icon">Rp</span>
                                <input type="text" id="add_amount"
                                       x-model="display" @input="updateValue($event)" @blur="formatDisplay()"
                                       class="input w-full @error('amount') border-danger-500 @enderror"
                                       inputmode="numeric" placeholder="500.000" required>
                            </div>
                            @error('amount')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="type" class="block text-sm font-medium text-secondary-700 mb-1">
                                Tipe <span class="text-danger-500">*</span>
                            </label>
                            <select name="type" id="type"
                                    class="input w-full @error('type') border-danger-500 @enderror" required>
                                <option value="earning" {{ old('type') === 'earning' ? 'selected' : '' }}>Pendapatan</option>
                                <option value="deduction" {{ old('type') === 'deduction' ? 'selected' : '' }}>Potongan</option>
                            </select>
                            @error('type')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="category" class="block text-sm font-medium text-secondary-700 mb-1">
                                Kategori <span class="text-danger-500">*</span>
                            </label>
                            <select name="category" id="category"
                                    class="input w-full @error('category') border-danger-500 @enderror" required>
                                <option value="">Pilih kategori...</option>
                                <option value="fixed" {{ old('category') === 'fixed' ? 'selected' : '' }}>Tetap</option>
                                <option value="variable" {{ old('category') === 'variable' ? 'selected' : '' }}>Variabel</option>
                                <option value="benefit" {{ old('category') === 'benefit' ? 'selected' : '' }}>Tunjangan</option>
                                <option value="overtime" {{ old('category') === 'overtime' ? 'selected' : '' }}>Lembur</option>
                                <option value="loan" {{ old('category') === 'loan' ? 'selected' : '' }}>Pinjaman</option>
                                <option value="penalty" {{ old('category') === 'penalty' ? 'selected' : '' }}>Denda</option>
                                <option value="reimbursement" {{ old('category') === 'reimbursement' ? 'selected' : '' }}>Reimbursement</option>
                                <option value="other" {{ old('category') === 'other' ? 'selected' : '' }}>Lainnya</option>
                            </select>
                            @error('category')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="is_taxable" class="block text-sm font-medium text-secondary-700 mb-1">
                                Kena Pajak?
                            </label>
                            <select name="is_taxable" id="is_taxable" class="input w-full">
                                <option value="1" {{ old('is_taxable', '1') === '1' ? 'selected' : '' }}>Ya</option>
                                <option value="0" {{ old('is_taxable') === '0' ? 'selected' : '' }}>Tidak</option>
                            </select>
                        </div>

                        <div>
                            <label for="notes" class="block text-sm font-medium text-secondary-700 mb-1">
                                Catatan
                            </label>
                            <input type="text" name="notes" id="notes"
                                   value="{{ old('notes') }}"
                                   class="input w-full"
                                   placeholder="Opsional">
                        </div>
                    </div>
                </div>
                <div class="card-footer flex justify-end">
                    <button type="submit" class="btn btn-success">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        Tambah Komponen
                    </button>
                </div>
            </form>
        </div>
    </div>

    <x-confirm-dialog />
@endsection
