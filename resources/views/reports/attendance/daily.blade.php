@extends('layouts.admin')

@section('title', 'Laporan Kehadiran Harian')

@section('breadcrumb')
    <a href="{{ route('reports.attendance') }}" class="text-primary-600 hover:text-primary-700">Laporan Kehadiran</a>
    <span class="mx-2 text-secondary-400">/</span>
    <span class="text-slate-700 font-medium">Harian</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Laporan Kehadiran Harian</h1>
            <p class="text-secondary-500 mt-1">{{ \Carbon\Carbon::parse($date)->translatedFormat('l, d F Y') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('reports.attendance') }}" class="btn btn-ghost">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/></svg>
                Kembali
            </a>
        </div>
    </div>
@endsection

@section('content')
    {{-- Summary Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-4">
        <div class="stat-card">
            <p class="stat-card-label">Total Karyawan</p>
            <p class="text-lg font-bold text-secondary-900">{{ $summary['total_employees'] }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-card-label">Hadir</p>
            <p class="text-lg font-bold text-success-600">{{ $summary['present'] + $summary['late'] }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-card-label">Tidak Hadir</p>
            <p class="text-lg font-bold text-danger-600">{{ $summary['absent'] }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-card-label">Kehadiran</p>
            <p class="text-lg font-bold text-primary-600">{{ $summary['attendance_rate'] }}%</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body-sm">
            <form action="{{ route('reports.attendance.daily') }}" method="GET" class="flex flex-wrap items-end gap-3">
                <div class="w-36">
                    <label class="block text-xs font-medium text-secondary-500 mb-1">Tanggal</label>
                    <input type="date" name="date" value="{{ $date }}" class="input w-full">
                </div>
                <div class="w-40">
                    <label class="block text-xs font-medium text-secondary-500 mb-1">Departemen</label>
                    <select name="department_id" class="input w-full">
                        <option value="">Semua</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" {{ request('department_id') == $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    @if(request()->hasAny(['date', 'department_id']))
                        <a href="{{ route('reports.attendance.daily') }}" class="btn btn-ghost btn-sm">Reset</a>
                    @endif
                    <button type="submit" class="btn btn-primary btn-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Attendance Status Breakdown --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
        {{-- On Time --}}
        <div class="card">
            <div class="card-header bg-success-50 border-b border-success-100">
                <h3 class="font-semibold text-success-700">Tepat Waktu ({{ $summary['present'] }})</h3>
            </div>
            <div class="card-body max-h-64 overflow-y-auto">
                @forelse($attendances->where('clock_in_status', 'on_time') as $attendance)
                    <div class="flex items-center justify-between py-2 border-b border-secondary-100 last:border-0">
                        <div>
                            <p class="font-medium text-secondary-900">{{ $attendance->employee->full_name ?? '-' }}</p>
                            <p class="text-sm text-secondary-500">{{ $attendance->employee->department?->name ?? '-' }}</p>
                        </div>
                        <span class="text-sm text-secondary-600">{{ $attendance->clock_in?->format('H:i') }}</span>
                    </div>
                @empty
                    <p class="text-secondary-400 text-sm">Tidak ada data</p>
                @endforelse
            </div>
        </div>

        {{-- Late --}}
        <div class="card">
            <div class="card-header bg-warning-50 border-b border-warning-100">
                <h3 class="font-semibold text-warning-700">Terlambat ({{ $summary['late'] }})</h3>
            </div>
            <div class="card-body max-h-64 overflow-y-auto">
                @forelse($attendances->whereIn('clock_in_status', ['late', 'very_late']) as $attendance)
                    <div class="flex items-center justify-between py-2 border-b border-secondary-100 last:border-0">
                        <div>
                            <p class="font-medium text-secondary-900">{{ $attendance->employee->full_name ?? '-' }}</p>
                            <p class="text-sm text-secondary-500">{{ $attendance->employee->department?->name ?? '-' }}</p>
                        </div>
                        <div class="text-right">
                            <span class="text-sm text-secondary-600">{{ $attendance->clock_in?->format('H:i') }}</span>
                            <p class="text-xs text-warning-600">+{{ $attendance->late_minutes }} menit</p>
                        </div>
                    </div>
                @empty
                    <p class="text-secondary-400 text-sm">Tidak ada data</p>
                @endforelse
            </div>
        </div>

        {{-- Absent (would need employee list comparison) --}}
        <div class="card">
            <div class="card-header bg-danger-50 border-b border-danger-100">
                <h3 class="font-semibold text-danger-700">Tidak Hadir ({{ $summary['absent'] }})</h3>
            </div>
            <div class="card-body max-h-64 overflow-y-auto">
                <p class="text-secondary-400 text-sm">Data tidak hadir dapat dilihat dari selisih total karyawan dengan total kehadiran.</p>
            </div>
        </div>
    </div>

    {{-- Full Attendance List --}}
    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold text-secondary-900">Daftar Kehadiran</h3>
        </div>
        <x-table>
            <x-slot name="header">
                <th>Karyawan</th>
                <th>Departemen</th>
                <th>Jabatan</th>
                <th class="text-center">Clock In</th>
                <th class="text-center">Clock Out</th>
                <th class="text-center">Status</th>
                <th class="text-right">Terlambat</th>
            </x-slot>

            @forelse($attendances as $attendance)
                <tr>
                    <td>
                        <span class="font-medium text-secondary-900">{{ $attendance->employee->full_name ?? '-' }}</span>
                        <p class="text-xs text-secondary-400">{{ $attendance->employee->employee_id ?? '-' }}</p>
                    </td>
                    <td class="text-secondary-600">{{ $attendance->employee->department?->name ?? '-' }}</td>
                    <td class="text-secondary-600">{{ $attendance->employee->position?->name ?? '-' }}</td>
                    <td class="text-center text-secondary-900">{{ $attendance->clock_in?->format('H:i') ?? '-' }}</td>
                    <td class="text-center text-secondary-900">{{ $attendance->clock_out?->format('H:i') ?? '-' }}</td>
                    <td class="text-center">
                        @switch($attendance->clock_in_status)
                            @case('on_time')
                                <x-badge type="success">Tepat Waktu</x-badge>
                                @break
                            @case('late')
                                <x-badge type="warning">Terlambat</x-badge>
                                @break
                            @case('very_late')
                                <x-badge type="danger">Sangat Terlambat</x-badge>
                                @break
                            @default
                                <x-badge type="secondary">{{ ucfirst($attendance->status) }}</x-badge>
                        @endswitch
                    </td>
                    <td class="text-right">
                        @if($attendance->late_minutes > 0)
                            <span class="text-warning-600 font-medium">{{ $attendance->late_minutes }} menit</span>
                        @else
                            <span class="text-secondary-400">-</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-12">
                        <div class="flex flex-col items-center">
                            <svg class="w-12 h-12 text-secondary-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <p class="text-secondary-500">Tidak ada data kehadiran untuk tanggal ini</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </x-table>

        <div class="card-footer">
            <p class="text-sm text-secondary-500">Menampilkan {{ $attendances->count() }} data kehadiran</p>
        </div>
    </div>
@endsection
