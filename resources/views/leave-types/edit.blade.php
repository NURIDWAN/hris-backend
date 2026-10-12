@extends('layouts.admin')

@section('title', 'Edit Jenis Cuti')

@section('breadcrumb')
    <a href="{{ route('leave-types.index') }}" class="text-slate-500 hover:text-primary-600">Jenis Cuti</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Edit Jenis Cuti</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Edit Jenis Cuti</h1>
            <p class="text-secondary-500 mt-1">Ubah informasi jenis cuti {{ $leaveType->name }}.</p>
        </div>
        <a href="{{ route('leave-types.index') }}" class="btn btn-ghost">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali
        </a>
    </div>
@endsection

@section('content')
    <div class="max-w-3xl">
        <form action="{{ route('leave-types.update', $leaveType) }}" method="POST" x-data="{ isCarryForward: {{ old('is_carry_forward', $leaveType->is_carry_forward) ? 'true' : 'false' }} }">
            @csrf
            @method('PUT')

            {{-- Basic Info --}}
            <div class="card mb-6">
                <div class="card-header">
                    <h3 class="card-title">Informasi Dasar</h3>
                </div>
                <div class="card-body space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Name --}}
                        <div>
                            <label for="name" class="block text-sm font-medium text-secondary-700 mb-1">
                                Nama Jenis Cuti <span class="text-danger-500">*</span>
                            </label>
                            <input type="text" name="name" id="name" value="{{ old('name', $leaveType->name) }}"
                                   class="input w-full @error('name') border-danger-500 @enderror"
                                   placeholder="Contoh: Cuti Tahunan" required>
                            @error('name')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Code --}}
                        <div>
                            <label for="code" class="block text-sm font-medium text-secondary-700 mb-1">
                                Kode
                            </label>
                            <input type="text" name="code" id="code" value="{{ old('code', $leaveType->code) }}"
                                   class="input w-full @error('code') border-danger-500 @enderror"
                                   placeholder="Contoh: CT">
                            @error('code')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Description --}}
                    <div>
                        <label for="description" class="block text-sm font-medium text-secondary-700 mb-1">
                            Deskripsi
                        </label>
                        <textarea name="description" id="description" rows="2"
                                  class="input w-full @error('description') border-danger-500 @enderror"
                                  placeholder="Deskripsi singkat tentang jenis cuti ini...">{{ old('description', $leaveType->description) }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Default Days --}}
                        <div>
                            <label for="default_days" class="block text-sm font-medium text-secondary-700 mb-1">
                                Jatah Hari Default <span class="text-danger-500">*</span>
                            </label>
                            <input type="number" name="default_days" id="default_days" value="{{ old('default_days', $leaveType->default_days) }}"
                                   class="input w-full @error('default_days') border-danger-500 @enderror"
                                   min="0" max="365" required>
                            @error('default_days')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-secondary-500">Jumlah hari cuti yang diberikan per tahun.</p>
                        </div>

                        {{-- Color --}}
                        <div>
                            <label for="color" class="block text-sm font-medium text-secondary-700 mb-1">
                                Warna
                            </label>
                            <select name="color" id="color" class="input w-full @error('color') border-danger-500 @enderror">
                                <option value="primary" {{ old('color', $leaveType->color) === 'primary' ? 'selected' : '' }}>Biru</option>
                                <option value="success" {{ old('color', $leaveType->color) === 'success' ? 'selected' : '' }}>Hijau</option>
                                <option value="warning" {{ old('color', $leaveType->color) === 'warning' ? 'selected' : '' }}>Kuning</option>
                                <option value="danger" {{ old('color', $leaveType->color) === 'danger' ? 'selected' : '' }}>Merah</option>
                                <option value="info" {{ old('color', $leaveType->color) === 'info' ? 'selected' : '' }}>Cyan</option>
                                <option value="secondary" {{ old('color', $leaveType->color) === 'secondary' ? 'selected' : '' }}>Abu-abu</option>
                            </select>
                            @error('color')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Requirements --}}
            <div class="card mb-6">
                <div class="card-header">
                    <h3 class="card-title">Ketentuan</h3>
                </div>
                <div class="card-body space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Min Notice Days --}}
                        <div>
                            <label for="min_notice_days" class="block text-sm font-medium text-secondary-700 mb-1">
                                Minimal Hari Pengajuan Sebelumnya <span class="text-danger-500">*</span>
                            </label>
                            <input type="number" name="min_notice_days" id="min_notice_days" value="{{ old('min_notice_days', $leaveType->min_notice_days) }}"
                                   class="input w-full @error('min_notice_days') border-danger-500 @enderror"
                                   min="0" max="90" required>
                            @error('min_notice_days')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-secondary-500">0 = dapat mengajukan kapan saja.</p>
                        </div>

                        {{-- Max Consecutive Days --}}
                        <div>
                            <label for="max_consecutive_days" class="block text-sm font-medium text-secondary-700 mb-1">
                                Maksimal Hari Berturut-turut
                            </label>
                            <input type="number" name="max_consecutive_days" id="max_consecutive_days" value="{{ old('max_consecutive_days', $leaveType->max_consecutive_days) }}"
                                   class="input w-full @error('max_consecutive_days') border-danger-500 @enderror"
                                   min="1" max="365">
                            @error('max_consecutive_days')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-secondary-500">Kosongkan jika tidak ada batasan.</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <input type="hidden" name="is_paid" value="0">
                        <input type="checkbox" name="is_paid" id="is_paid" value="1"
                               class="w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500"
                               {{ old('is_paid', $leaveType->is_paid) ? 'checked' : '' }}>
                        <div>
                            <label for="is_paid" class="text-sm font-medium text-secondary-700">
                                Cuti Berbayar
                            </label>
                            <p class="text-sm text-secondary-500">Karyawan tetap mendapat gaji selama cuti.</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <input type="hidden" name="requires_approval" value="0">
                        <input type="checkbox" name="requires_approval" id="requires_approval" value="1"
                               class="w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500"
                               {{ old('requires_approval', $leaveType->requires_approval) ? 'checked' : '' }}>
                        <div>
                            <label for="requires_approval" class="text-sm font-medium text-secondary-700">
                                Memerlukan Persetujuan
                            </label>
                            <p class="text-sm text-secondary-500">Cuti harus disetujui oleh atasan.</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <input type="hidden" name="requires_attachment" value="0">
                        <input type="checkbox" name="requires_attachment" id="requires_attachment" value="1"
                               class="w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500"
                               {{ old('requires_attachment', $leaveType->requires_attachment) ? 'checked' : '' }}>
                        <div>
                            <label for="requires_attachment" class="text-sm font-medium text-secondary-700">
                                Memerlukan Lampiran
                            </label>
                            <p class="text-sm text-secondary-500">Karyawan harus melampirkan dokumen pendukung (contoh: surat dokter).</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Carry Forward --}}
            <div class="card mb-6">
                <div class="card-header">
                    <h3 class="card-title">Carry Forward</h3>
                </div>
                <div class="card-body space-y-4">
                    <div class="flex items-center gap-3">
                        <input type="hidden" name="is_carry_forward" value="0">
                        <input type="checkbox" name="is_carry_forward" id="is_carry_forward" value="1"
                               class="w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500"
                               x-model="isCarryForward"
                               {{ old('is_carry_forward', $leaveType->is_carry_forward) ? 'checked' : '' }}>
                        <div>
                            <label for="is_carry_forward" class="text-sm font-medium text-secondary-700">
                                Izinkan Carry Forward
                            </label>
                            <p class="text-sm text-secondary-500">Sisa cuti dapat dibawa ke tahun berikutnya.</p>
                        </div>
                    </div>

                    <div x-show="isCarryForward" x-cloak>
                        <label for="max_carry_forward_days" class="block text-sm font-medium text-secondary-700 mb-1">
                            Maksimal Hari Carry Forward <span class="text-danger-500">*</span>
                        </label>
                        <input type="number" name="max_carry_forward_days" id="max_carry_forward_days" value="{{ old('max_carry_forward_days', $leaveType->max_carry_forward_days) }}"
                               class="input w-full md:w-1/3 @error('max_carry_forward_days') border-danger-500 @enderror"
                               min="1" max="365">
                        @error('max_carry_forward_days')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Status --}}
            <div class="card mb-6">
                <div class="card-header">
                    <h3 class="card-title">Status</h3>
                </div>
                <div class="card-body">
                    <div class="flex items-center gap-3">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" id="is_active" value="1"
                               class="w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500"
                               {{ old('is_active', $leaveType->is_active) ? 'checked' : '' }}>
                        <div>
                            <label for="is_active" class="text-sm font-medium text-secondary-700">
                                Aktif
                            </label>
                            <p class="text-sm text-secondary-500">Jenis cuti ini dapat digunakan oleh karyawan.</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex justify-end gap-3">
                <a href="{{ route('leave-types.index') }}" class="btn btn-ghost">Batal</a>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
@endsection
