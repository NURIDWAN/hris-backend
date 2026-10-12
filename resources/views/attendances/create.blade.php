@extends('layouts.admin')

@section('title', 'Input Kehadiran Manual')

@section('breadcrumb')
    <a href="{{ route('attendances.index') }}" class="text-slate-500 hover:text-primary-600">Kehadiran</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Input Manual</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Input Kehadiran Manual</h1>
            <p class="text-secondary-500 mt-1">Tambahkan data kehadiran karyawan secara manual.</p>
        </div>
        <a href="{{ route('attendances.index') }}" class="btn btn-ghost">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali
        </a>
    </div>
@endsection

@section('content')
    <div class="max-w-2xl">
        <form action="{{ route('attendances.store') }}" method="POST">
            @csrf

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Data Kehadiran</h3>
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
                                <option value="{{ $employee->id }}" {{ old('employee_id') == $employee->id ? 'selected' : '' }}>
                                    {{ $employee->full_name }} ({{ $employee->employee_id }})
                                </option>
                            @endforeach
                        </select>
                        @error('employee_id')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Date --}}
                    <div>
                        <label for="date" class="block text-sm font-medium text-secondary-700 mb-1">
                            Tanggal <span class="text-danger-500">*</span>
                        </label>
                        <input type="date" name="date" id="date" value="{{ old('date', date('Y-m-d')) }}"
                               class="input w-full @error('date') border-danger-500 @enderror" required>
                        @error('date')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        {{-- Clock In --}}
                        <div>
                            <label for="clock_in" class="block text-sm font-medium text-secondary-700 mb-1">
                                Jam Masuk
                            </label>
                            <input type="time" name="clock_in" id="clock_in" value="{{ old('clock_in') }}"
                                   class="input w-full @error('clock_in') border-danger-500 @enderror">
                            @error('clock_in')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Clock Out --}}
                        <div>
                            <label for="clock_out" class="block text-sm font-medium text-secondary-700 mb-1">
                                Jam Pulang
                            </label>
                            <input type="time" name="clock_out" id="clock_out" value="{{ old('clock_out') }}"
                                   class="input w-full @error('clock_out') border-danger-500 @enderror">
                            @error('clock_out')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Status --}}
                    <div>
                        <label for="status" class="block text-sm font-medium text-secondary-700 mb-1">
                            Status
                        </label>
                        <select name="status" id="status" class="input w-full @error('status') border-danger-500 @enderror">
                            <option value="present" {{ old('status', 'present') === 'present' ? 'selected' : '' }}>Hadir</option>
                            <option value="absent" {{ old('status') === 'absent' ? 'selected' : '' }}>Tidak Hadir</option>
                            <option value="late" {{ old('status') === 'late' ? 'selected' : '' }}>Terlambat</option>
                            <option value="half_day" {{ old('status') === 'half_day' ? 'selected' : '' }}>Setengah Hari</option>
                            <option value="leave" {{ old('status') === 'leave' ? 'selected' : '' }}>Cuti</option>
                            <option value="holiday" {{ old('status') === 'holiday' ? 'selected' : '' }}>Libur</option>
                            <option value="weekend" {{ old('status') === 'weekend' ? 'selected' : '' }}>Akhir Pekan</option>
                        </select>
                        @error('status')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Admin Notes --}}
                    <div>
                        <label for="admin_notes" class="block text-sm font-medium text-secondary-700 mb-1">
                            Catatan Admin
                        </label>
                        <textarea name="admin_notes" id="admin_notes" rows="3"
                                  class="input w-full @error('admin_notes') border-danger-500 @enderror"
                                  placeholder="Alasan input manual, keterangan khusus, dll...">{{ old('admin_notes') }}</textarea>
                        @error('admin_notes')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <div class="card-footer flex justify-end gap-3">
                    <a href="{{ route('attendances.index') }}" class="btn btn-ghost">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Simpan
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
