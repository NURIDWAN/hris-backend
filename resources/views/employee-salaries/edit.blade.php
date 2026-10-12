@extends('layouts.admin')

@section('title', 'Edit Gaji Karyawan')

@section('breadcrumb')
    <a href="{{ route('employee-salaries.index') }}" class="text-slate-500 hover:text-primary-600">Gaji Karyawan</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Edit Gaji</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Edit Gaji Karyawan</h1>
            <p class="text-secondary-500 mt-1">Perbarui pengaturan gaji karyawan.</p>
        </div>
        <a href="{{ route('employee-salaries.index') }}" class="btn btn-ghost">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali
        </a>
    </div>
@endsection

@section('content')
    <form action="{{ route('employee-salaries.update', $employeeSalary) }}" method="POST"
          x-data="{ paymentMethod: '{{ old('payment_method', $employeeSalary->payment_method) }}' }">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                {{-- Basic Info --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Informasi Dasar</h3>
                    </div>
                    <div class="card-body space-y-4">
                        {{-- Employee --}}
                        <div>
                            <label for="employee_id" class="block text-sm font-medium text-secondary-700 mb-1">
                                Karyawan <span class="text-danger-500">*</span>
                            </label>
                            <select name="employee_id" id="employee_id"
                                    class="input w-full @error('employee_id') border-danger-500 @enderror" required>
                                <option value="">Pilih Karyawan</option>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}" {{ old('employee_id', $employeeSalary->employee_id) == $employee->id ? 'selected' : '' }}>
                                        {{ $employee->full_name }} ({{ $employee->employee_id }})
                                    </option>
                                @endforeach
                            </select>
                            @error('employee_id')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {{-- Basic Salary --}}
                            <div x-data="currencyInput({{ old('basic_salary', $employeeSalary->basic_salary) }})">
                                <label for="basic_salary_display" class="block text-sm font-medium text-secondary-700 mb-1">
                                    Gaji Pokok <span class="text-danger-500">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" id="basic_salary_display" x-model="display" @input="updateValue($event)"
                                           class="input @error('basic_salary') border-danger-500 @enderror"
                                           placeholder="0" inputmode="numeric" required>
                                    <input type="hidden" name="basic_salary" :value="value">
                                </div>
                                @error('basic_salary')
                                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Effective Date --}}
                            <div>
                                <label for="effective_date" class="block text-sm font-medium text-secondary-700 mb-1">
                                    Berlaku Sejak <span class="text-danger-500">*</span>
                                </label>
                                <input type="date" name="effective_date" id="effective_date" value="{{ old('effective_date', $employeeSalary->effective_date->format('Y-m-d')) }}"
                                       class="input w-full @error('effective_date') border-danger-500 @enderror" required>
                                @error('effective_date')
                                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Notes --}}
                        <div>
                            <label for="notes" class="block text-sm font-medium text-secondary-700 mb-1">
                                Catatan
                            </label>
                            <textarea name="notes" id="notes" rows="2"
                                      class="input w-full @error('notes') border-danger-500 @enderror"
                                      placeholder="Catatan opsional...">{{ old('notes', $employeeSalary->notes) }}</textarea>
                            @error('notes')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Payment Method --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Metode Pembayaran</h3>
                    </div>
                    <div class="card-body space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-secondary-700 mb-3">
                                Metode <span class="text-danger-500">*</span>
                            </label>
                            <div class="flex flex-wrap gap-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="payment_method" value="transfer" x-model="paymentMethod"
                                           class="w-4 h-4 text-primary-600 border-secondary-300 focus:ring-primary-500">
                                    <span class="text-sm text-secondary-700">Transfer Bank</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="payment_method" value="cash" x-model="paymentMethod"
                                           class="w-4 h-4 text-primary-600 border-secondary-300 focus:ring-primary-500">
                                    <span class="text-sm text-secondary-700">Tunai</span>
                                </label>
                            </div>
                        </div>

                        <div x-show="paymentMethod === 'transfer'" x-cloak class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {{-- Bank Name --}}
                            <div>
                                <label for="bank_name" class="block text-sm font-medium text-secondary-700 mb-1">
                                    Nama Bank <span class="text-danger-500">*</span>
                                </label>
                                <select name="bank_name" id="bank_name"
                                        class="input w-full @error('bank_name') border-danger-500 @enderror">
                                    <option value="">Pilih Bank</option>
                                    <option value="BCA" {{ old('bank_name', $employeeSalary->bank_name) === 'BCA' ? 'selected' : '' }}>BCA</option>
                                    <option value="Mandiri" {{ old('bank_name', $employeeSalary->bank_name) === 'Mandiri' ? 'selected' : '' }}>Mandiri</option>
                                    <option value="BNI" {{ old('bank_name', $employeeSalary->bank_name) === 'BNI' ? 'selected' : '' }}>BNI</option>
                                    <option value="BRI" {{ old('bank_name', $employeeSalary->bank_name) === 'BRI' ? 'selected' : '' }}>BRI</option>
                                    <option value="CIMB Niaga" {{ old('bank_name', $employeeSalary->bank_name) === 'CIMB Niaga' ? 'selected' : '' }}>CIMB Niaga</option>
                                    <option value="Permata" {{ old('bank_name', $employeeSalary->bank_name) === 'Permata' ? 'selected' : '' }}>Permata</option>
                                    <option value="Danamon" {{ old('bank_name', $employeeSalary->bank_name) === 'Danamon' ? 'selected' : '' }}>Danamon</option>
                                    <option value="Other" {{ old('bank_name', $employeeSalary->bank_name) === 'Other' ? 'selected' : '' }}>Lainnya</option>
                                </select>
                                @error('bank_name')
                                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Account Number --}}
                            <div>
                                <label for="bank_account_number" class="block text-sm font-medium text-secondary-700 mb-1">
                                    Nomor Rekening <span class="text-danger-500">*</span>
                                </label>
                                <input type="text" name="bank_account_number" id="bank_account_number" value="{{ old('bank_account_number', $employeeSalary->bank_account_number) }}"
                                       class="input w-full @error('bank_account_number') border-danger-500 @enderror"
                                       placeholder="1234567890">
                                @error('bank_account_number')
                                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Account Name --}}
                            <div class="md:col-span-2">
                                <label for="bank_account_name" class="block text-sm font-medium text-secondary-700 mb-1">
                                    Nama Pemilik Rekening
                                </label>
                                <input type="text" name="bank_account_name" id="bank_account_name" value="{{ old('bank_account_name', $employeeSalary->bank_account_name) }}"
                                       class="input w-full @error('bank_account_name') border-danger-500 @enderror"
                                       placeholder="Nama sesuai buku rekening">
                                @error('bank_account_name')
                                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Salary Components --}}
                @php
                    $currentComponents = $employeeSalary->components->keyBy('salary_component_id');
                @endphp
                <div class="card" x-data="salaryComponents()">
                    <div class="card-header">
                        <h3 class="card-title">Komponen Gaji</h3>
                    </div>
                    <div class="card-body">
                        {{-- Earnings --}}
                        <div class="mb-6">
                            <h4 class="text-sm font-medium text-secondary-700 mb-3 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-success-500"></span>
                                Pendapatan / Tunjangan
                            </h4>
                            <div class="space-y-3">
                                <template x-for="(comp, index) in earnings" :key="'earning-' + index">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-1">
                                            <span class="text-sm text-secondary-600" x-text="comp.name"></span>
                                            <span class="text-secondary-400 text-sm" x-show="comp.code" x-text="'(' + comp.code + ')'"></span>
                                        </div>
                                        <div class="w-52">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">Rp</span>
                                                <input type="text" :value="formatCurrency(comp.amount)" @input="updateAmount(comp, $event)"
                                                       class="input" placeholder="0" inputmode="numeric">
                                                <input type="hidden" :name="'components[' + comp.id + '][amount]'" :value="comp.amount">
                                            </div>
                                        </div>
                                        <button type="button" @click="removeComponent(comp, 'earning')"
                                                class="text-danger-500 hover:text-danger-700 p-1" title="Hapus">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </template>
                                <div x-show="earnings.length === 0" class="text-sm text-secondary-400 italic">
                                    Belum ada komponen pendapatan.
                                </div>
                            </div>

                            {{-- Add Earning --}}
                            <div class="mt-3 flex items-end gap-2">
                                <div class="flex-1">
                                    <select x-model="newEarningId" class="input w-full text-sm">
                                        <option value="">Pilih komponen...</option>
                                        <template x-for="comp in availableEarnings" :key="comp.id">
                                            <option :value="comp.id" x-text="comp.name"></option>
                                        </template>
                                    </select>
                                </div>
                                <button type="button" @click="addComponent('earning')"
                                        class="btn btn-ghost btn-sm text-success-600" :disabled="!newEarningId">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                    Tambah
                                </button>
                            </div>
                        </div>

                        {{-- Deductions --}}
                        <div>
                            <h4 class="text-sm font-medium text-secondary-700 mb-3 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-danger-500"></span>
                                Potongan
                            </h4>
                            <div class="space-y-3">
                                <template x-for="(comp, index) in deductions" :key="'deduction-' + index">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-1">
                                            <span class="text-sm text-secondary-600" x-text="comp.name"></span>
                                            <span class="text-secondary-400 text-sm" x-show="comp.code" x-text="'(' + comp.code + ')'"></span>
                                        </div>
                                        <div class="w-52">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">Rp</span>
                                                <input type="text" :value="formatCurrency(comp.amount)" @input="updateAmount(comp, $event)"
                                                       class="input" placeholder="0" inputmode="numeric">
                                                <input type="hidden" :name="'components[' + comp.id + '][amount]'" :value="comp.amount">
                                            </div>
                                        </div>
                                        <button type="button" @click="removeComponent(comp, 'deduction')"
                                                class="text-danger-500 hover:text-danger-700 p-1" title="Hapus">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </template>
                                <div x-show="deductions.length === 0" class="text-sm text-secondary-400 italic">
                                    Belum ada komponen potongan.
                                </div>
                            </div>

                            {{-- Add Deduction --}}
                            <div class="mt-3 flex items-end gap-2">
                                <div class="flex-1">
                                    <select x-model="newDeductionId" class="input w-full text-sm">
                                        <option value="">Pilih komponen...</option>
                                        <template x-for="comp in availableDeductions" :key="comp.id">
                                            <option :value="comp.id" x-text="comp.name"></option>
                                        </template>
                                    </select>
                                </div>
                                <button type="button" @click="addComponent('deduction')"
                                        class="btn btn-ghost btn-sm text-danger-600" :disabled="!newDeductionId">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                    Tambah
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                {{-- Status --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Status</h3>
                    </div>
                    <div class="card-body">
                        <div class="flex items-center gap-3">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" id="is_active" value="1"
                                   class="w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500"
                                   {{ old('is_active', $employeeSalary->is_active) ? 'checked' : '' }}>
                            <div>
                                <label for="is_active" class="text-sm font-medium text-secondary-700">
                                    Aktif
                                </label>
                                <p class="text-sm text-secondary-500">Tandai sebagai gaji aktif karyawan.</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="card">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-full">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Simpan Perubahan
                        </button>
                        <a href="{{ route('employee-salaries.index') }}" class="btn btn-ghost w-full mt-2">Batal</a>
                    </div>
                </div>
            </div>
        </div>
    </form>

@endsection

@php
    $componentsJson = $salaryComponents->map(function ($c) {
        return ['id' => $c->id, 'name' => $c->name, 'code' => $c->code, 'type' => $c->type, 'default_amount' => $c->default_amount ?? 0];
    });
    $existingJson = $employeeSalary->components->map(function ($c) {
        return ['id' => $c->salary_component_id, 'name' => $c->salaryComponent->name, 'code' => $c->salaryComponent->code, 'type' => $c->salaryComponent->type, 'amount' => (int) $c->amount];
    });
@endphp

@push('scripts')
<script>
function salaryComponents() {
    const allComponents = @json($componentsJson);
    const existingComponents = @json($existingJson);

    const existingEarnings = existingComponents.filter(c => c.type === 'earning' && c.code !== 'BASIC');
    const existingDeductions = existingComponents.filter(c => c.type === 'deduction');

    return {
        allComponents: allComponents,
        earnings: existingEarnings,
        deductions: existingDeductions,
        newEarningId: '',
        newDeductionId: '',

        get availableEarnings() {
            const usedIds = this.earnings.map(c => c.id);
            return this.allComponents.filter(c => c.type === 'earning' && c.code !== 'BASIC' && !usedIds.includes(c.id));
        },

        get availableDeductions() {
            const usedIds = this.deductions.map(c => c.id);
            return this.allComponents.filter(c => c.type === 'deduction' && !usedIds.includes(c.id));
        },

        addComponent(type) {
            const selectId = type === 'earning' ? this.newEarningId : this.newDeductionId;
            if (!selectId) return;

            const comp = this.allComponents.find(c => c.id == selectId);
            if (!comp) return;

            const item = { id: comp.id, name: comp.name, code: comp.code, amount: comp.default_amount || 0 };

            if (type === 'earning') {
                this.earnings.push(item);
                this.newEarningId = '';
            } else {
                this.deductions.push(item);
                this.newDeductionId = '';
            }
        },

        removeComponent(comp, type) {
            if (type === 'earning') {
                this.earnings = this.earnings.filter(c => c.id !== comp.id);
            } else {
                this.deductions = this.deductions.filter(c => c.id !== comp.id);
            }
        },

        updateAmount(comp, event) {
            let raw = event.target.value.replace(/\D/g, '');
            comp.amount = parseInt(raw) || 0;
            event.target.value = this.formatCurrency(comp.amount);
        },

        formatCurrency(value) {
            if (!value) return '';
            return new Intl.NumberFormat('id-ID').format(value);
        }
    };
}
</script>
@endpush
