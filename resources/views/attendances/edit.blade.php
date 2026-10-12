@extends('layouts.admin')

@section('title', 'Edit Kehadiran')

@section('breadcrumb')
    <a href="{{ route('attendances.index') }}" class="text-slate-500 hover:text-primary-600">Kehadiran</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Edit</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Edit Kehadiran</h1>
            <p class="text-secondary-500 mt-1">Ubah data kehadiran karyawan.</p>
        </div>
        <a href="{{ route('attendances.index') }}" class="btn btn-ghost">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali
        </a>
    </div>
@endsection

@section('content')
    <div class="max-w-2xl">
        <form action="{{ route('attendances.update', $attendance) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Data Kehadiran</h3>
                </div>
                <div class="card-body space-y-4">
                    {{-- Employee (Read-only) --}}
                    <div>
                        <label class="block text-sm font-medium text-secondary-700 mb-1">
                            Karyawan
                        </label>
                        <input type="hidden" name="employee_id" value="{{ $attendance->employee_id }}">
                        <input type="text" value="{{ $attendance->employee->full_name }} ({{ $attendance->employee->employee_id }})"
                               class="input w-full bg-secondary-50" disabled>
                    </div>

                    {{-- Date (Read-only) --}}
                    <div>
                        <label class="block text-sm font-medium text-secondary-700 mb-1">
                            Tanggal
                        </label>
                        <input type="hidden" name="date" value="{{ $attendance->date->format('Y-m-d') }}">
                        <input type="text" value="{{ $attendance->date->format('d M Y') }} ({{ $attendance->date->translatedFormat('l') }})"
                               class="input w-full bg-secondary-50" disabled>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        {{-- Clock In --}}
                        <div>
                            <label for="clock_in" class="block text-sm font-medium text-secondary-700 mb-1">
                                Jam Masuk
                            </label>
                            <input type="time" name="clock_in" id="clock_in"
                                   value="{{ old('clock_in', $attendance->clock_in?->format('H:i')) }}"
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
                            <input type="time" name="clock_out" id="clock_out"
                                   value="{{ old('clock_out', $attendance->clock_out?->format('H:i')) }}"
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
                            <option value="present" {{ old('status', $attendance->status) === 'present' ? 'selected' : '' }}>Hadir</option>
                            <option value="absent" {{ old('status', $attendance->status) === 'absent' ? 'selected' : '' }}>Tidak Hadir</option>
                            <option value="late" {{ old('status', $attendance->status) === 'late' ? 'selected' : '' }}>Terlambat</option>
                            <option value="half_day" {{ old('status', $attendance->status) === 'half_day' ? 'selected' : '' }}>Setengah Hari</option>
                            <option value="leave" {{ old('status', $attendance->status) === 'leave' ? 'selected' : '' }}>Cuti</option>
                            <option value="holiday" {{ old('status', $attendance->status) === 'holiday' ? 'selected' : '' }}>Libur</option>
                            <option value="weekend" {{ old('status', $attendance->status) === 'weekend' ? 'selected' : '' }}>Akhir Pekan</option>
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
                                  placeholder="Alasan perubahan, keterangan khusus, dll...">{{ old('admin_notes', $attendance->admin_notes) }}</textarea>
                        @error('admin_notes')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    @if($attendance->is_manual_entry)
                        <div class="p-3 bg-warning-50 border border-warning-200 rounded-lg">
                            <p class="text-sm text-warning-700">
                                <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                Data ini diinput secara manual
                                @if($attendance->approvedBy)
                                    oleh {{ $attendance->approvedBy->name }}
                                @endif
                            </p>
                        </div>
                    @endif
                </div>
                <div class="card-footer flex justify-end gap-3">
                    <a href="{{ route('attendances.index') }}" class="btn btn-ghost">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Simpan Perubahan
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
