@extends('layouts.admin')

@section('title', 'Tambah Reimbursement')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Keuangan</span>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <a href="{{ route('reimbursements.index') }}" class="text-primary-600 hover:underline">Reimbursement</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Tambah</span>
@endsection

@section('content')
    <div class="card max-w-2xl">
        <div class="card-header">
            <h3 class="card-title">Tambah Pengajuan Reimbursement</h3>
        </div>
        <form action="{{ route('reimbursements.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="card-body space-y-4">
                <div>
                    <label for="employee_id" class="block text-sm font-medium text-secondary-700 mb-1">
                        Karyawan <span class="text-danger-500">*</span>
                    </label>
                    <select name="employee_id" id="employee_id"
                            class="input w-full @error('employee_id') border-danger-500 @enderror"
                            required>
                        <option value="">Pilih Karyawan</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" {{ old('employee_id') == $employee->id ? 'selected' : '' }}>
                                {{ $employee->full_name }} - {{ $employee->employee_id }}
                            </option>
                        @endforeach
                    </select>
                    @error('employee_id')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="category_id" class="block text-sm font-medium text-secondary-700 mb-1">
                        Kategori <span class="text-danger-500">*</span>
                    </label>
                    <select name="category_id" id="category_id"
                            class="input w-full @error('category_id') border-danger-500 @enderror"
                            required>
                        <option value="">Pilih Kategori</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}"
                                    data-max-amount="{{ $category->max_amount }}"
                                    data-requires-receipt="{{ $category->requires_receipt ? '1' : '0' }}"
                                    {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                                @if($category->max_amount)
                                    (Maks: Rp {{ number_format($category->max_amount, 0, ',', '.') }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="expense_date" class="block text-sm font-medium text-secondary-700 mb-1">
                        Tanggal Pengeluaran <span class="text-danger-500">*</span>
                    </label>
                    <input type="date" name="expense_date" id="expense_date"
                           value="{{ old('expense_date') }}"
                           max="{{ date('Y-m-d') }}"
                           class="input w-full @error('expense_date') border-danger-500 @enderror"
                           required>
                    @error('expense_date')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                <div x-data="currencyInput({{ old('amount', 0) }})">
                    <label for="amount_display" class="block text-sm font-medium text-secondary-700 mb-1">
                        Jumlah <span class="text-danger-500">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="text" id="amount_display" x-model="display" @input="updateValue($event)"
                               class="input @error('amount') border-danger-500 @enderror"
                               placeholder="0" inputmode="numeric" required>
                        <input type="hidden" name="amount" :value="value">
                    </div>
                    @error('amount')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-secondary-700 mb-1">
                        Deskripsi <span class="text-danger-500">*</span>
                    </label>
                    <textarea name="description" id="description" rows="3"
                              class="input w-full @error('description') border-danger-500 @enderror"
                              placeholder="Jelaskan detail pengeluaran..."
                              required>{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="receipt" class="block text-sm font-medium text-secondary-700 mb-1">
                        Bukti/Struk
                    </label>
                    <input type="file" name="receipt" id="receipt"
                           accept="image/*"
                           class="input w-full @error('receipt') border-danger-500 @enderror">
                    <p class="mt-1 text-sm text-secondary-500">Format: JPG, PNG, GIF. Maksimal 5MB</p>
                    @error('receipt')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="card-footer flex justify-end gap-2">
                <a href="{{ route('reimbursements.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
@endsection
