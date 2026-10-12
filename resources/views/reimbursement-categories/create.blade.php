@extends('layouts.admin')

@section('title', 'Tambah Kategori Reimbursement')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Keuangan</span>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <a href="{{ route('reimbursement-categories.index') }}" class="text-primary-600 hover:underline">Kategori Reimbursement</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Tambah</span>
@endsection

@section('content')
    <div class="card max-w-2xl">
        <div class="card-header">
            <h3 class="card-title">Tambah Kategori Reimbursement</h3>
        </div>
        <form action="{{ route('reimbursement-categories.store') }}" method="POST">
            @csrf
            <div class="card-body space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-secondary-700 mb-1">
                        Nama Kategori <span class="text-danger-500">*</span>
                    </label>
                    <input type="text" name="name" id="name"
                           value="{{ old('name') }}"
                           class="input w-full @error('name') border-danger-500 @enderror"
                           placeholder="Contoh: Transportasi"
                           required>
                    @error('name')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-secondary-700 mb-1">
                        Deskripsi
                    </label>
                    <textarea name="description" id="description" rows="3"
                              class="input w-full @error('description') border-danger-500 @enderror"
                              placeholder="Deskripsi singkat tentang kategori ini...">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                <div x-data="currencyInput({{ old('max_amount', 0) }})">
                    <label for="max_amount_display" class="block text-sm font-medium text-secondary-700 mb-1">
                        Maksimum Amount
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="text" id="max_amount_display" x-model="display" @input="updateValue($event)"
                               class="input @error('max_amount') border-danger-500 @enderror"
                               placeholder="0" inputmode="numeric">
                        <input type="hidden" name="max_amount" :value="value || ''">
                    </div>
                    <p class="mt-1 text-sm text-secondary-500">Batas maksimal per pengajuan. Kosongkan jika tidak ada batas.</p>
                    @error('max_amount')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-3">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="hidden" name="requires_receipt" value="0">
                        <input type="checkbox" name="requires_receipt" value="1"
                               {{ old('requires_receipt', true) ? 'checked' : '' }}
                               class="w-5 h-5 rounded border-secondary-300 text-primary-600 focus:ring-primary-500">
                        <div>
                            <span class="text-sm font-medium text-secondary-700">Wajib Lampirkan Bukti</span>
                            <p class="text-sm text-secondary-500">Karyawan wajib upload struk/bukti pengeluaran</p>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1"
                               {{ old('is_active', true) ? 'checked' : '' }}
                               class="w-5 h-5 rounded border-secondary-300 text-primary-600 focus:ring-primary-500">
                        <div>
                            <span class="text-sm font-medium text-secondary-700">Aktif</span>
                            <p class="text-sm text-secondary-500">Kategori dapat dipilih saat pengajuan</p>
                        </div>
                    </label>
                </div>
            </div>

            <div class="card-footer flex justify-end gap-2">
                <a href="{{ route('reimbursement-categories.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
@endsection
