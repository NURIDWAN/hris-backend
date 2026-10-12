@extends('layouts.admin')

@section('title', 'Ajukan Lembur')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Lembur</span>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <a href="{{ route('overtime-requests.index') }}" class="text-primary-600 hover:text-primary-700">Pengajuan Lembur</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Ajukan Lembur</span>
@endsection

@section('header')
    <div>
        <h1 class="text-2xl font-bold text-secondary-900">Ajukan Lembur</h1>
        <p class="text-secondary-500 mt-1">Buat pengajuan lembur untuk karyawan.</p>
    </div>
@endsection

@section('content')
    <div class="card max-w-2xl">
        <div class="card-header">
            <h3 class="card-title">Form Pengajuan Lembur</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('overtime-requests.store') }}" method="POST">
                @csrf

                {{-- Employee --}}
                <div class="mb-4">
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
                <div class="mb-4">
                    <label for="date" class="block text-sm font-medium text-secondary-700 mb-1">
                        Tanggal Lembur <span class="text-danger-500">*</span>
                    </label>
                    <input type="date" name="date" id="date"
                           value="{{ old('date') }}"
                           class="input w-full @error('date') border-danger-500 @enderror" required>
                    @error('date')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Time --}}
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="start_time" class="block text-sm font-medium text-secondary-700 mb-1">
                            Jam Mulai <span class="text-danger-500">*</span>
                        </label>
                        <input type="time" name="start_time" id="start_time"
                               value="{{ old('start_time', '18:00') }}"
                               class="input w-full @error('start_time') border-danger-500 @enderror" required>
                        @error('start_time')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="end_time" class="block text-sm font-medium text-secondary-700 mb-1">
                            Jam Selesai <span class="text-danger-500">*</span>
                        </label>
                        <input type="time" name="end_time" id="end_time"
                               value="{{ old('end_time', '21:00') }}"
                               class="input w-full @error('end_time') border-danger-500 @enderror" required>
                        @error('end_time')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Overtime Type --}}
                <div class="mb-4">
                    <label for="overtime_type" class="block text-sm font-medium text-secondary-700 mb-1">
                        Tipe Lembur <span class="text-danger-500">*</span>
                    </label>
                    <select name="overtime_type" id="overtime_type"
                            class="input w-full @error('overtime_type') border-danger-500 @enderror" required>
                        <option value="weekday" {{ old('overtime_type') == 'weekday' ? 'selected' : '' }}>Hari Kerja (Weekday)</option>
                        <option value="weekend" {{ old('overtime_type') == 'weekend' ? 'selected' : '' }}>Akhir Pekan (Weekend)</option>
                        <option value="holiday" {{ old('overtime_type') == 'holiday' ? 'selected' : '' }}>Hari Libur Nasional</option>
                    </select>
                    @if($setting)
                        <p class="mt-1 text-sm text-secondary-500">
                            Tarif: Weekday {{ $setting->weekday_rate_first_hour }}x (jam 1), {{ $setting->weekday_rate_next_hours }}x (jam berikut) |
                            Weekend {{ $setting->weekend_rate }}x |
                            Libur {{ $setting->holiday_rate }}x
                        </p>
                    @endif
                    @error('overtime_type')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Reason --}}
                <div class="mb-4">
                    <label for="reason" class="block text-sm font-medium text-secondary-700 mb-1">
                        Alasan Lembur <span class="text-danger-500">*</span>
                    </label>
                    <textarea name="reason" id="reason" rows="3"
                              class="input w-full @error('reason') border-danger-500 @enderror"
                              placeholder="Jelaskan alasan/pekerjaan yang akan dilakukan" required>{{ old('reason') }}</textarea>
                    @error('reason')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Notes --}}
                <div class="mb-4">
                    <label for="notes" class="block text-sm font-medium text-secondary-700 mb-1">
                        Catatan Tambahan
                    </label>
                    <textarea name="notes" id="notes" rows="2"
                              class="input w-full @error('notes') border-danger-500 @enderror"
                              placeholder="Catatan tambahan (opsional)">{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t">
                    <a href="{{ route('overtime-requests.index') }}" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Ajukan Lembur
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
