@extends('layouts.admin')

@section('title', 'Edit Pengajuan Cuti')

@section('breadcrumb')
    <a href="{{ route('leave-requests.index') }}" class="text-slate-500 hover:text-primary-600">Pengajuan Cuti</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Edit</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Edit Pengajuan Cuti</h1>
            <p class="text-secondary-500 mt-1">{{ $leaveRequest->request_number }}</p>
        </div>
        <a href="{{ route('leave-requests.show', $leaveRequest) }}" class="btn btn-ghost">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali
        </a>
    </div>
@endsection

@section('content')
    <div class="max-w-3xl">
        <form action="{{ route('leave-requests.update', $leaveRequest) }}" method="POST" enctype="multipart/form-data" x-data="{ isHalfDay: {{ old('is_half_day', $leaveRequest->is_half_day) ? 'true' : 'false' }} }">
            @csrf
            @method('PUT')

            {{-- Employee & Type (Read-only) --}}
            <div class="card mb-6">
                <div class="card-header">
                    <h3 class="card-title">Informasi Pengajuan</h3>
                </div>
                <div class="card-body">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Karyawan</dt>
                            <dd class="mt-1 text-secondary-900">{{ $leaveRequest->employee->full_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Jenis Cuti</dt>
                            <dd class="mt-1 text-secondary-900">{{ $leaveRequest->leaveType->name }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Date Range --}}
            <div class="card mb-6">
                <div class="card-header">
                    <h3 class="card-title">Periode Cuti</h3>
                </div>
                <div class="card-body space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Start Date --}}
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-secondary-700 mb-1">
                                Tanggal Mulai <span class="text-danger-500">*</span>
                            </label>
                            <input type="date" name="start_date" id="start_date" value="{{ old('start_date', $leaveRequest->start_date->format('Y-m-d')) }}"
                                   class="input w-full @error('start_date') border-danger-500 @enderror" required>
                            @error('start_date')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- End Date --}}
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-secondary-700 mb-1">
                                Tanggal Selesai <span class="text-danger-500">*</span>
                            </label>
                            <input type="date" name="end_date" id="end_date" value="{{ old('end_date', $leaveRequest->end_date->format('Y-m-d')) }}"
                                   class="input w-full @error('end_date') border-danger-500 @enderror" required>
                            @error('end_date')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Half Day Option --}}
                    <div class="flex items-center gap-3">
                        <input type="hidden" name="is_half_day" value="0">
                        <input type="checkbox" name="is_half_day" id="is_half_day" value="1"
                               class="w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500"
                               x-model="isHalfDay"
                               {{ old('is_half_day', $leaveRequest->is_half_day) ? 'checked' : '' }}>
                        <div>
                            <label for="is_half_day" class="text-sm font-medium text-secondary-700">
                                Setengah Hari
                            </label>
                        </div>
                    </div>

                    <div x-show="isHalfDay" x-cloak>
                        <label for="half_day_type" class="block text-sm font-medium text-secondary-700 mb-1">
                            Tipe Setengah Hari <span class="text-danger-500">*</span>
                        </label>
                        <select name="half_day_type" id="half_day_type" class="input w-full md:w-1/2 @error('half_day_type') border-danger-500 @enderror">
                            <option value="">Pilih Waktu</option>
                            <option value="morning" {{ old('half_day_type', $leaveRequest->half_day_type) === 'morning' ? 'selected' : '' }}>Pagi</option>
                            <option value="afternoon" {{ old('half_day_type', $leaveRequest->half_day_type) === 'afternoon' ? 'selected' : '' }}>Siang</option>
                        </select>
                        @error('half_day_type')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Reason & Attachment --}}
            <div class="card mb-6">
                <div class="card-header">
                    <h3 class="card-title">Detail Pengajuan</h3>
                </div>
                <div class="card-body space-y-4">
                    {{-- Reason --}}
                    <div>
                        <label for="reason" class="block text-sm font-medium text-secondary-700 mb-1">
                            Alasan Cuti <span class="text-danger-500">*</span>
                        </label>
                        <textarea name="reason" id="reason" rows="3"
                                  class="input w-full @error('reason') border-danger-500 @enderror" required>{{ old('reason', $leaveRequest->reason) }}</textarea>
                        @error('reason')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Emergency Contact --}}
                    <div>
                        <label for="emergency_contact" class="block text-sm font-medium text-secondary-700 mb-1">
                            Kontak Darurat
                        </label>
                        <input type="text" name="emergency_contact" id="emergency_contact" value="{{ old('emergency_contact', $leaveRequest->emergency_contact) }}"
                               class="input w-full @error('emergency_contact') border-danger-500 @enderror">
                        @error('emergency_contact')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Attachment --}}
                    <div>
                        <label for="attachment" class="block text-sm font-medium text-secondary-700 mb-1">
                            Lampiran
                        </label>
                        @if($leaveRequest->attachment)
                            <div class="mb-2">
                                <a href="{{ Storage::url($leaveRequest->attachment) }}" target="_blank" class="text-primary-600 hover:text-primary-700 text-sm">
                                    Lihat lampiran saat ini
                                </a>
                            </div>
                        @endif
                        <input type="file" name="attachment" id="attachment"
                               class="input w-full @error('attachment') border-danger-500 @enderror"
                               accept=".pdf,.jpg,.jpeg,.png">
                        <p class="mt-1 text-sm text-secondary-500">Kosongkan jika tidak ingin mengubah lampiran.</p>
                        @error('attachment')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex justify-end gap-3">
                <a href="{{ route('leave-requests.show', $leaveRequest) }}" class="btn btn-ghost">Batal</a>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
@endsection
