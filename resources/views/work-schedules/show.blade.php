@extends('layouts.admin')

@section('title', 'Detail Jadwal Kerja')

@section('breadcrumb')
    <a href="{{ route('work-schedules.index') }}" class="text-slate-500 hover:text-primary-600">Jadwal Kerja</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">{{ $workSchedule->name }}</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-secondary-900">{{ $workSchedule->name }}</h1>
                @if($workSchedule->is_default)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-primary-100 text-primary-800">Default</span>
                @endif
                @if($workSchedule->is_flexible)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">Fleksibel</span>
                @endif
            </div>
            @if($workSchedule->code)
                <p class="text-secondary-500 mt-1">{{ $workSchedule->code }}</p>
            @endif
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('work-schedules.edit', $workSchedule) }}" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Edit
            </a>
            <a href="{{ route('work-schedules.index') }}" class="btn btn-ghost">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Kembali
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Info --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Working Hours --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Jam Kerja</h3>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                        <div>
                            <p class="text-sm text-secondary-500">Jam Masuk</p>
                            <p class="text-lg font-semibold text-secondary-900">{{ $workSchedule->start_time->format('H:i') }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-secondary-500">Jam Pulang</p>
                            <p class="text-lg font-semibold text-secondary-900">{{ $workSchedule->end_time->format('H:i') }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-secondary-500">Total Jam Kerja</p>
                            <p class="text-lg font-semibold text-secondary-900">{{ $workSchedule->working_hours }} jam</p>
                        </div>
                        <div>
                            <p class="text-sm text-secondary-500">Durasi Istirahat</p>
                            <p class="text-lg font-semibold text-secondary-900">{{ $workSchedule->break_duration }} menit</p>
                        </div>
                    </div>

                    @if($workSchedule->break_start && $workSchedule->break_end)
                        <div class="mt-4 pt-4 border-t border-secondary-100">
                            <p class="text-sm text-secondary-500">Jam Istirahat</p>
                            <p class="text-lg font-semibold text-secondary-900">
                                {{ $workSchedule->break_start->format('H:i') }} - {{ $workSchedule->break_end->format('H:i') }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Working Days --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Hari Kerja</h3>
                </div>
                <div class="card-body">
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
                        $workingDays = $workSchedule->working_days ?? [];
                    @endphp
                    <div class="grid grid-cols-7 gap-2">
                        @foreach($days as $value => $label)
                            <div class="text-center p-3 rounded-lg {{ in_array($value, $workingDays) ? 'bg-primary-100 text-primary-800' : 'bg-secondary-100 text-secondary-400' }}">
                                <span class="text-sm font-medium">{{ substr($label, 0, 3) }}</span>
                            </div>
                        @endforeach
                    </div>
                    <p class="mt-3 text-sm text-secondary-500">
                        {{ $workSchedule->working_days_text }}
                    </p>
                </div>
            </div>

            {{-- Tolerance Settings --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Pengaturan Toleransi</h3>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <p class="text-sm text-secondary-500">Toleransi Keterlambatan</p>
                            <p class="text-lg font-semibold text-secondary-900">{{ $workSchedule->late_tolerance }} menit</p>
                            <p class="text-sm text-secondary-500 mt-1">
                                Karyawan dianggap terlambat jika datang setelah {{ $workSchedule->start_time->copy()->addMinutes($workSchedule->late_tolerance)->format('H:i') }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-secondary-500">Toleransi Pulang Awal</p>
                            <p class="text-lg font-semibold text-secondary-900">{{ $workSchedule->early_leave_tolerance }} menit</p>
                            <p class="text-sm text-secondary-500 mt-1">
                                Karyawan dianggap pulang awal jika pulang sebelum {{ $workSchedule->end_time->copy()->subMinutes($workSchedule->early_leave_tolerance)->format('H:i') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            @if($workSchedule->description)
            {{-- Description --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Deskripsi</h3>
                </div>
                <div class="card-body">
                    <p class="text-secondary-700">{{ $workSchedule->description }}</p>
                </div>
            </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Status --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Status</h3>
                </div>
                <div class="card-body space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-secondary-500">Status</span>
                        @if($workSchedule->is_active)
                            <x-badge type="success">Aktif</x-badge>
                        @else
                            <x-badge type="danger">Tidak Aktif</x-badge>
                        @endif
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-secondary-500">Jadwal Default</span>
                        @if($workSchedule->is_default)
                            <x-badge type="info">Ya</x-badge>
                        @else
                            <span class="text-secondary-700">Tidak</span>
                        @endif
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-secondary-500">Jadwal Fleksibel</span>
                        @if($workSchedule->is_flexible)
                            <x-badge type="info">Ya</x-badge>
                        @else
                            <span class="text-secondary-700">Tidak</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Statistics --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Statistik</h3>
                </div>
                <div class="card-body">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-secondary-500">Jumlah Karyawan</span>
                        <span class="text-lg font-semibold text-secondary-900">{{ $workSchedule->employees_count }}</span>
                    </div>
                </div>
            </div>

            {{-- Timestamps --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Informasi</h3>
                </div>
                <div class="card-body space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-secondary-500">Dibuat</span>
                        <span class="text-sm text-secondary-700">{{ $workSchedule->created_at->format('d M Y H:i') }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-secondary-500">Diperbarui</span>
                        <span class="text-sm text-secondary-700">{{ $workSchedule->updated_at->format('d M Y H:i') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
