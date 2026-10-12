@extends('layouts.admin')

@section('title', 'Detail Kehadiran')

@section('breadcrumb')
    <a href="{{ route('attendances.index') }}" class="text-slate-500 hover:text-primary-600">Kehadiran</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Detail</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Detail Kehadiran</h1>
            <p class="text-secondary-500 mt-1">{{ $attendance->date->format('d M Y') }} - {{ $attendance->employee->full_name }}</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('attendances.edit', $attendance) }}" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Edit
            </a>
            <a href="{{ route('attendances.index') }}" class="btn btn-ghost">
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
            {{-- Employee Info --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Informasi Karyawan</h3>
                </div>
                <div class="card-body">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-full bg-primary-100 flex items-center justify-center">
                            <span class="text-primary-700 text-2xl font-medium">{{ substr($attendance->employee->first_name, 0, 1) }}</span>
                        </div>
                        <div>
                            <h4 class="text-lg font-semibold text-secondary-900">{{ $attendance->employee->full_name }}</h4>
                            <p class="text-secondary-500">{{ $attendance->employee->employee_id }}</p>
                            @if($attendance->employee->position)
                                <p class="text-secondary-500">{{ $attendance->employee->position->name }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Attendance Times --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Waktu Kehadiran</h3>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                        <div>
                            <p class="text-sm text-secondary-500">Jam Masuk</p>
                            <p class="text-lg font-semibold text-secondary-900">
                                {{ $attendance->clock_in ? $attendance->formatted_clock_in : '-' }}
                            </p>
                            @if($attendance->clock_in_status)
                                @if($attendance->clock_in_status === 'on_time')
                                    <span class="text-sm text-success-600">Tepat Waktu</span>
                                @elseif($attendance->clock_in_status === 'late')
                                    <span class="text-sm text-warning-600">Terlambat {{ $attendance->late_minutes }} menit</span>
                                @else
                                    <span class="text-sm text-danger-600">Sangat Terlambat</span>
                                @endif
                            @endif
                        </div>
                        <div>
                            <p class="text-sm text-secondary-500">Jam Pulang</p>
                            <p class="text-lg font-semibold text-secondary-900">
                                {{ $attendance->clock_out ? $attendance->formatted_clock_out : '-' }}
                            </p>
                            @if($attendance->clock_out_status)
                                @if($attendance->clock_out_status === 'on_time')
                                    <span class="text-sm text-success-600">Tepat Waktu</span>
                                @elseif($attendance->clock_out_status === 'early')
                                    <span class="text-sm text-warning-600">Pulang Awal {{ $attendance->early_leave_minutes }} menit</span>
                                @else
                                    <span class="text-sm text-primary-600">Lembur {{ $attendance->overtime_minutes }} menit</span>
                                @endif
                            @endif
                        </div>
                        <div>
                            <p class="text-sm text-secondary-500">Durasi Kerja</p>
                            <p class="text-lg font-semibold text-secondary-900">{{ $attendance->working_hours }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-secondary-500">Jadwal</p>
                            <p class="text-lg font-semibold text-secondary-900">
                                {{ $attendance->scheduled_start ?? '-' }} - {{ $attendance->scheduled_end ?? '-' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Location Info --}}
            @if($attendance->clock_in_latitude || $attendance->clock_out_latitude)
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Lokasi</h3>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-2 gap-6">
                        @if($attendance->clock_in_latitude)
                        <div>
                            <p class="text-sm text-secondary-500">Lokasi Clock In</p>
                            <p class="text-sm text-secondary-700">
                                {{ $attendance->clock_in_latitude }}, {{ $attendance->clock_in_longitude }}
                            </p>
                            @if($attendance->clock_in_ip)
                                <p class="text-sm text-secondary-500 mt-1">IP: {{ $attendance->clock_in_ip }}</p>
                            @endif
                        </div>
                        @endif

                        @if($attendance->clock_out_latitude)
                        <div>
                            <p class="text-sm text-secondary-500">Lokasi Clock Out</p>
                            <p class="text-sm text-secondary-700">
                                {{ $attendance->clock_out_latitude }}, {{ $attendance->clock_out_longitude }}
                            </p>
                            @if($attendance->clock_out_ip)
                                <p class="text-sm text-secondary-500 mt-1">IP: {{ $attendance->clock_out_ip }}</p>
                            @endif
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            {{-- Notes --}}
            @if($attendance->clock_in_notes || $attendance->clock_out_notes || $attendance->admin_notes)
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Catatan</h3>
                </div>
                <div class="card-body space-y-4">
                    @if($attendance->clock_in_notes)
                    <div>
                        <p class="text-sm font-medium text-secondary-700">Catatan Clock In</p>
                        <p class="text-secondary-600 mt-1">{{ $attendance->clock_in_notes }}</p>
                    </div>
                    @endif

                    @if($attendance->clock_out_notes)
                    <div>
                        <p class="text-sm font-medium text-secondary-700">Catatan Clock Out</p>
                        <p class="text-secondary-600 mt-1">{{ $attendance->clock_out_notes }}</p>
                    </div>
                    @endif

                    @if($attendance->admin_notes)
                    <div>
                        <p class="text-sm font-medium text-secondary-700">Catatan Admin</p>
                        <p class="text-secondary-600 mt-1">{{ $attendance->admin_notes }}</p>
                    </div>
                    @endif
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
                        <x-badge :type="$attendance->status_color">{{ $attendance->status_label }}</x-badge>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-secondary-500">Input Manual</span>
                        @if($attendance->is_manual_entry)
                            <x-badge type="warning">Ya</x-badge>
                        @else
                            <span class="text-secondary-700">Tidak</span>
                        @endif
                    </div>
                    @if($attendance->approvedBy)
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-secondary-500">Disetujui Oleh</span>
                        <span class="text-secondary-700">{{ $attendance->approvedBy->name }}</span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Work Schedule --}}
            @if($attendance->workSchedule)
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Jadwal Kerja</h3>
                </div>
                <div class="card-body space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-secondary-500">Nama</span>
                        <span class="text-secondary-700">{{ $attendance->workSchedule->name }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-secondary-500">Jam Kerja</span>
                        <span class="text-secondary-700">{{ $attendance->workSchedule->formatted_working_hours }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-secondary-500">Toleransi Terlambat</span>
                        <span class="text-secondary-700">{{ $attendance->workSchedule->late_tolerance }} menit</span>
                    </div>
                </div>
            </div>
            @endif

            {{-- Timestamps --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Informasi</h3>
                </div>
                <div class="card-body space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-secondary-500">Tanggal</span>
                        <span class="text-sm text-secondary-700">{{ $attendance->date->format('d M Y') }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-secondary-500">Hari</span>
                        <span class="text-sm text-secondary-700">{{ $attendance->date->translatedFormat('l') }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-secondary-500">Dibuat</span>
                        <span class="text-sm text-secondary-700">{{ $attendance->created_at->setTimezone($attendance->company->timezone ?? config('app.timezone'))->format('d M Y H:i') }}</span>
                    </div>
                    @if($attendance->approved_at)
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-secondary-500">Disetujui</span>
                        <span class="text-sm text-secondary-700">{{ $attendance->approved_at->setTimezone($attendance->company->timezone ?? config('app.timezone'))->format('d M Y H:i') }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
