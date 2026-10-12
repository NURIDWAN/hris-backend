@extends('layouts.admin')

@section('title', 'Laporan Kehadiran')

@section('breadcrumb')
    <a href="{{ route('attendances.index') }}" class="text-slate-500 hover:text-primary-600">Kehadiran</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Laporan</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Laporan Kehadiran</h1>
            <p class="text-secondary-500 mt-1">Rekap dan statistik kehadiran karyawan.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('attendances.export', request()->query()) }}" class="btn btn-outline">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Export CSV
            </a>
        </div>
    </div>
@endsection

@section('content')
    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body-sm">
            <form action="{{ route('attendances.report') }}" method="GET" class="flex flex-wrap items-end gap-3">
                {{-- Month --}}
                <div class="w-36">
                    <label for="month" class="block text-xs font-medium text-secondary-500 mb-1">Bulan</label>
                    <input type="month" name="month" id="month" value="{{ request('month', now()->format('Y-m')) }}" class="input w-full">
                </div>

                {{-- Employee --}}
                <div class="w-44">
                    <label for="employee_id" class="block text-xs font-medium text-secondary-500 mb-1">Karyawan</label>
                    <select name="employee_id" id="employee_id" class="input w-full">
                        <option value="">Semua</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>
                                {{ $employee->full_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Status --}}
                <div class="w-32">
                    <label for="status" class="block text-xs font-medium text-secondary-500 mb-1">Status</label>
                    <select name="status" id="status" class="input w-full">
                        <option value="">Semua</option>
                        <option value="present" {{ request('status') === 'present' ? 'selected' : '' }}>Hadir</option>
                        <option value="late" {{ request('status') === 'late' ? 'selected' : '' }}>Terlambat</option>
                        <option value="absent" {{ request('status') === 'absent' ? 'selected' : '' }}>Tidak Hadir</option>
                        <option value="leave" {{ request('status') === 'leave' ? 'selected' : '' }}>Cuti</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    @if(request()->hasAny(['month', 'employee_id', 'status']))
                        <a href="{{ route('attendances.report') }}" class="btn btn-ghost btn-sm">Reset</a>
                    @endif
                    <button type="submit" class="btn btn-primary btn-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        Tampilkan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Summary Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3 mb-4">
        <div class="stat-card">
            <p class="stat-card-label">Total Hadir</p>
            <p class="text-lg font-bold text-success-600">{{ $summary['present'] }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-card-label">Terlambat</p>
            <p class="text-lg font-bold text-warning-600">{{ $summary['late'] }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-card-label">Tidak Hadir</p>
            <p class="text-lg font-bold text-danger-600">{{ $summary['absent'] }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-card-label">Cuti</p>
            <p class="text-lg font-bold text-info-600">{{ $summary['leave'] }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-card-label">Jam Kerja</p>
            <p class="text-lg font-bold text-primary-600">{{ $summary['total_working_hours'] }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-card-label">Total Lembur</p>
            <p class="text-lg font-bold text-primary-600">{{ $summary['total_overtime_hours'] }}j</p>
        </div>
        <div class="stat-card">
            <p class="stat-card-label">Keterlambatan</p>
            <p class="text-lg font-bold text-warning-600">{{ $summary['total_late_hours'] }}j</p>
        </div>
        <div class="stat-card">
            <p class="stat-card-label">Karyawan</p>
            <p class="text-lg font-bold text-secondary-900">{{ $summary['total_employees'] }}</p>
        </div>
    </div>

    {{-- Per Employee Report --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Rekap Per Karyawan</h3>
        </div>
        <x-table>
            <x-slot name="header">
                <th>Karyawan</th>
                <th class="text-center">Hadir</th>
                <th class="text-center">Terlambat</th>
                <th class="text-center">Tidak Hadir</th>
                <th class="text-center">Cuti</th>
                <th class="text-center">Jam Kerja</th>
                <th class="text-center">Lembur</th>
            </x-slot>

            @forelse($reportData as $data)
                <tr>
                    <td>
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center flex-shrink-0">
                                <span class="text-primary-700 text-xs font-medium">{{ substr($data['employee']->first_name, 0, 1) }}</span>
                            </div>
                            <div class="min-w-0">
                                <span class="font-medium text-secondary-900 block truncate">{{ $data['employee']->full_name }}</span>
                                <p class="text-xs text-secondary-400">{{ $data['employee']->employee_id }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="text-center">
                        <span class="text-success-600 font-medium">{{ $data['present'] }}</span>
                    </td>
                    <td class="text-center">
                        <span class="text-warning-600 font-medium">{{ $data['late'] }}</span>
                    </td>
                    <td class="text-center">
                        <span class="text-danger-600 font-medium">{{ $data['absent'] }}</span>
                    </td>
                    <td class="text-center">
                        <span class="text-info-600 font-medium">{{ $data['leave'] }}</span>
                    </td>
                    <td class="text-center">
                        <span class="font-medium text-secondary-700">{{ $data['working_hours'] }}j</span>
                    </td>
                    <td class="text-center">
                        @if($data['overtime_hours'] > 0)
                            <span class="text-primary-600 font-medium">{{ $data['overtime_hours'] }}j</span>
                        @else
                            <span class="text-secondary-400">-</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-12">
                        <div class="flex flex-col items-center">
                            <svg class="w-12 h-12 text-secondary-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <p class="text-secondary-500">Tidak ada data untuk periode ini.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </x-table>
    </div>
@endsection
