@extends('layouts.admin')

@section('title', 'Tambah Exit Karyawan')

@section('breadcrumb')
    <a href="{{ route('employee-exits.index') }}" class="text-slate-500 hover:text-primary-600">Exit Management</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Tambah Exit</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Tambah Exit Karyawan</h1>
            <p class="text-secondary-500 mt-1">Buat pengajuan exit untuk karyawan.</p>
        </div>
        <div>
            <a href="{{ route('employee-exits.index') }}" class="btn btn-ghost">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Kembali
            </a>
        </div>
    </div>
@endsection

@section('content')
    <form action="{{ route('employee-exits.store') }}" method="POST" class="max-w-2xl">
        @csrf

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Informasi Exit</h3>
            </div>
            <div class="card-body space-y-4">
                {{-- Employee --}}
                <div>
                    <label for="employee_id" class="block text-sm font-medium text-secondary-700 mb-1">
                        Karyawan <span class="text-danger-500">*</span>
                    </label>
                    <select name="employee_id" id="employee_id" class="input w-full @error('employee_id') border-danger-500 @enderror" required>
                        <option value="">Pilih Karyawan...</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" {{ old('employee_id', $selectedEmployee?->id) == $employee->id ? 'selected' : '' }}>
                                {{ $employee->full_name }} ({{ $employee->employee_id }})
                            </option>
                        @endforeach
                    </select>
                    @error('employee_id')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Exit Type --}}
                <div>
                    <label for="exit_type" class="block text-sm font-medium text-secondary-700 mb-1">
                        Jenis Exit <span class="text-danger-500">*</span>
                    </label>
                    <select name="exit_type" id="exit_type" class="input w-full @error('exit_type') border-danger-500 @enderror" required>
                        <option value="">Pilih Jenis Exit...</option>
                        <option value="resignation" {{ old('exit_type') === 'resignation' ? 'selected' : '' }}>Resign (Pengunduran Diri)</option>
                        <option value="termination" {{ old('exit_type') === 'termination' ? 'selected' : '' }}>Terminasi (PHK)</option>
                        <option value="retirement" {{ old('exit_type') === 'retirement' ? 'selected' : '' }}>Pensiun</option>
                        <option value="contract_end" {{ old('exit_type') === 'contract_end' ? 'selected' : '' }}>Kontrak Berakhir</option>
                        <option value="mutual_agreement" {{ old('exit_type') === 'mutual_agreement' ? 'selected' : '' }}>Kesepakatan Bersama</option>
                        <option value="death" {{ old('exit_type') === 'death' ? 'selected' : '' }}>Meninggal Dunia</option>
                    </select>
                    @error('exit_type')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Request Date --}}
                <div>
                    <label for="request_date" class="block text-sm font-medium text-secondary-700 mb-1">
                        Tanggal Pengajuan <span class="text-danger-500">*</span>
                    </label>
                    <input type="date" name="request_date" id="request_date"
                           value="{{ old('request_date', date('Y-m-d')) }}"
                           class="input w-full @error('request_date') border-danger-500 @enderror" required>
                    @error('request_date')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Last Working Day --}}
                <div>
                    <label for="last_working_day" class="block text-sm font-medium text-secondary-700 mb-1">
                        Hari Kerja Terakhir <span class="text-danger-500">*</span>
                    </label>
                    <input type="date" name="last_working_day" id="last_working_day"
                           value="{{ old('last_working_day') }}"
                           class="input w-full @error('last_working_day') border-danger-500 @enderror" required>
                    @error('last_working_day')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Effective Date --}}
                <div>
                    <label for="effective_date" class="block text-sm font-medium text-secondary-700 mb-1">
                        Tanggal Efektif Keluar <span class="text-danger-500">*</span>
                    </label>
                    <input type="date" name="effective_date" id="effective_date"
                           value="{{ old('effective_date') }}"
                           class="input w-full @error('effective_date') border-danger-500 @enderror" required>
                    <p class="mt-1 text-xs text-secondary-500">Tanggal karyawan secara resmi keluar dari perusahaan</p>
                    @error('effective_date')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Reason --}}
                <div>
                    <label for="reason" class="block text-sm font-medium text-secondary-700 mb-1">
                        Alasan
                    </label>
                    <textarea name="reason" id="reason" rows="3"
                              class="input w-full @error('reason') border-danger-500 @enderror"
                              placeholder="Jelaskan alasan exit...">{{ old('reason') }}</textarea>
                    @error('reason')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Notes --}}
                <div>
                    <label for="notes" class="block text-sm font-medium text-secondary-700 mb-1">
                        Catatan Internal
                    </label>
                    <textarea name="notes" id="notes" rows="2"
                              class="input w-full @error('notes') border-danger-500 @enderror"
                              placeholder="Catatan tambahan (hanya untuk internal)...">{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Rehire Eligibility --}}
                <div class="border-t pt-4">
                    <label class="flex items-center gap-3">
                        <input type="checkbox" name="is_rehire_eligible" value="1"
                               class="w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500"
                               {{ old('is_rehire_eligible', true) ? 'checked' : '' }}>
                        <span class="text-sm font-medium text-secondary-700">Dapat direkrut kembali</span>
                    </label>
                    <p class="mt-1 ml-7 text-xs text-secondary-500">Centang jika karyawan eligible untuk direkrut kembali di masa depan</p>
                </div>

                {{-- Rehire Notes --}}
                <div>
                    <label for="rehire_notes" class="block text-sm font-medium text-secondary-700 mb-1">
                        Catatan Rehire
                    </label>
                    <textarea name="rehire_notes" id="rehire_notes" rows="2"
                              class="input w-full @error('rehire_notes') border-danger-500 @enderror"
                              placeholder="Catatan terkait eligibilitas rehire...">{{ old('rehire_notes') }}</textarea>
                    @error('rehire_notes')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div class="card-footer flex justify-end gap-3">
                <a href="{{ route('employee-exits.index') }}" class="btn btn-ghost">Batal</a>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Simpan
                </button>
            </div>
        </div>
    </form>
@endsection
