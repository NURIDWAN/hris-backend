@extends('layouts.admin')

@section('title', 'Tambah Jadwal Kerja')

@section('breadcrumb')
    <a href="{{ route('work-schedules.index') }}" class="text-slate-500 hover:text-primary-600">Jadwal Kerja</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Tambah Jadwal</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Tambah Jadwal Kerja</h1>
            <p class="text-secondary-500 mt-1">Buat jadwal kerja baru untuk karyawan.</p>
        </div>
        <a href="{{ route('work-schedules.index') }}" class="btn btn-ghost">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali
        </a>
    </div>
@endsection

@section('content')
    <div class="max-w-3xl">
        <form action="{{ route('work-schedules.store') }}" method="POST">
            @csrf

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
                                Nama Jadwal <span class="text-danger-500">*</span>
                            </label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}"
                                   class="input w-full @error('name') border-danger-500 @enderror"
                                   placeholder="Contoh: Regular Office" required>
                            @error('name')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Code --}}
                        <div>
                            <label for="code" class="block text-sm font-medium text-secondary-700 mb-1">
                                Kode Jadwal
                            </label>
                            <input type="text" name="code" id="code" value="{{ old('code') }}"
                                   class="input w-full @error('code') border-danger-500 @enderror"
                                   placeholder="Contoh: REG-01">
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
                                  placeholder="Deskripsi singkat tentang jadwal ini...">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Working Hours --}}
            <div class="card mb-6">
                <div class="card-header">
                    <h3 class="card-title">Jam Kerja</h3>
                </div>
                <div class="card-body space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Start Time --}}
                        <div>
                            <label for="start_time" class="block text-sm font-medium text-secondary-700 mb-1">
                                Jam Masuk <span class="text-danger-500">*</span>
                            </label>
                            <input type="time" name="start_time" id="start_time" value="{{ old('start_time', '08:00') }}"
                                   class="input w-full @error('start_time') border-danger-500 @enderror" required>
                            @error('start_time')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- End Time --}}
                        <div>
                            <label for="end_time" class="block text-sm font-medium text-secondary-700 mb-1">
                                Jam Pulang <span class="text-danger-500">*</span>
                            </label>
                            <input type="time" name="end_time" id="end_time" value="{{ old('end_time', '17:00') }}"
                                   class="input w-full @error('end_time') border-danger-500 @enderror" required>
                            @error('end_time')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        {{-- Break Start --}}
                        <div>
                            <label for="break_start" class="block text-sm font-medium text-secondary-700 mb-1">
                                Mulai Istirahat
                            </label>
                            <input type="time" name="break_start" id="break_start" value="{{ old('break_start', '12:00') }}"
                                   class="input w-full @error('break_start') border-danger-500 @enderror">
                            @error('break_start')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Break End --}}
                        <div>
                            <label for="break_end" class="block text-sm font-medium text-secondary-700 mb-1">
                                Selesai Istirahat
                            </label>
                            <input type="time" name="break_end" id="break_end" value="{{ old('break_end', '13:00') }}"
                                   class="input w-full @error('break_end') border-danger-500 @enderror">
                            @error('break_end')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Break Duration --}}
                        <div>
                            <label for="break_duration" class="block text-sm font-medium text-secondary-700 mb-1">
                                Durasi Istirahat (menit)
                            </label>
                            <input type="number" name="break_duration" id="break_duration" value="{{ old('break_duration', 60) }}"
                                   class="input w-full @error('break_duration') border-danger-500 @enderror"
                                   min="0" max="180">
                            @error('break_duration')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Working Hours --}}
                    <div class="md:w-1/3">
                        <label for="working_hours" class="block text-sm font-medium text-secondary-700 mb-1">
                            Total Jam Kerja
                        </label>
                        <input type="number" name="working_hours" id="working_hours" value="{{ old('working_hours', 8) }}"
                               class="input w-full @error('working_hours') border-danger-500 @enderror"
                               min="1" max="24">
                        @error('working_hours')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Working Days --}}
            <div class="card mb-6">
                <div class="card-header">
                    <h3 class="card-title">Hari Kerja</h3>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
                        @php
                            $days = [
                                1 => 'Senin',
                                2 => 'Selasa',
                                3 => 'Rabu',
                                4 => 'Kamis',
                                5 => 'Jumat',
                                6 => 'Sabtu',
                                7 => 'Minggu',
                            ];
                            $defaultDays = old('working_days', [1, 2, 3, 4, 5]);
                        @endphp
                        @foreach($days as $value => $label)
                            <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:bg-secondary-50 transition-colors">
                                <input type="checkbox" name="working_days[]" value="{{ $value }}"
                                       class="w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500"
                                       {{ in_array($value, $defaultDays) ? 'checked' : '' }}>
                                <span class="text-sm font-medium text-secondary-700">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('working_days')
                        <p class="mt-2 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Tolerance Settings --}}
            <div class="card mb-6">
                <div class="card-header">
                    <h3 class="card-title">Pengaturan Toleransi</h3>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Late Tolerance --}}
                        <div>
                            <label for="late_tolerance" class="block text-sm font-medium text-secondary-700 mb-1">
                                Toleransi Keterlambatan (menit)
                            </label>
                            <input type="number" name="late_tolerance" id="late_tolerance" value="{{ old('late_tolerance', 15) }}"
                                   class="input w-full @error('late_tolerance') border-danger-500 @enderror"
                                   min="0" max="120">
                            @error('late_tolerance')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-secondary-500">Karyawan tidak dianggap terlambat jika datang dalam toleransi ini.</p>
                        </div>

                        {{-- Early Leave Tolerance --}}
                        <div>
                            <label for="early_leave_tolerance" class="block text-sm font-medium text-secondary-700 mb-1">
                                Toleransi Pulang Awal (menit)
                            </label>
                            <input type="number" name="early_leave_tolerance" id="early_leave_tolerance" value="{{ old('early_leave_tolerance', 15) }}"
                                   class="input w-full @error('early_leave_tolerance') border-danger-500 @enderror"
                                   min="0" max="120">
                            @error('early_leave_tolerance')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-secondary-500">Karyawan tidak dianggap pulang awal jika dalam toleransi ini.</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Options --}}
            <div class="card mb-6">
                <div class="card-header">
                    <h3 class="card-title">Opsi Lainnya</h3>
                </div>
                <div class="card-body space-y-4">
                    <div class="flex items-center gap-3">
                        <input type="hidden" name="is_flexible" value="0">
                        <input type="checkbox" name="is_flexible" id="is_flexible" value="1"
                               class="w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500"
                               {{ old('is_flexible') ? 'checked' : '' }}>
                        <div>
                            <label for="is_flexible" class="text-sm font-medium text-secondary-700">
                                Jadwal Fleksibel
                            </label>
                            <p class="text-sm text-secondary-500">Karyawan dapat masuk dan pulang di luar jam yang ditentukan.</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <input type="hidden" name="is_default" value="0">
                        <input type="checkbox" name="is_default" id="is_default" value="1"
                               class="w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500"
                               {{ old('is_default') ? 'checked' : '' }}>
                        <div>
                            <label for="is_default" class="text-sm font-medium text-secondary-700">
                                Jadwal Default
                            </label>
                            <p class="text-sm text-secondary-500">Jadwal ini akan digunakan sebagai default untuk karyawan baru.</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" id="is_active" value="1"
                               class="w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500"
                               {{ old('is_active', true) ? 'checked' : '' }}>
                        <div>
                            <label for="is_active" class="text-sm font-medium text-secondary-700">
                                Jadwal Aktif
                            </label>
                            <p class="text-sm text-secondary-500">Jadwal ini dapat digunakan dan diterapkan ke karyawan.</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex justify-end gap-3">
                <a href="{{ route('work-schedules.index') }}" class="btn btn-ghost">Batal</a>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Simpan
                </button>
            </div>
        </form>
    </div>
@endsection
