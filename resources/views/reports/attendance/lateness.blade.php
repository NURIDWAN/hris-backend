@extends('layouts.admin')

@section('title', 'Laporan Keterlambatan')

@section('breadcrumb')
    <a href="{{ route('reports.attendance') }}" class="text-primary-600 hover:text-primary-700">Laporan Kehadiran</a>
    <span class="mx-2 text-secondary-400">/</span>
    <span class="text-slate-700 font-medium">Keterlambatan</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Laporan Keterlambatan</h1>
            <p class="text-secondary-500 mt-1">{{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</p>
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
    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body-sm">
            <form action="{{ route('reports.attendance.lateness') }}" method="GET" class="flex flex-wrap items-end gap-3">
                <div class="w-36">
                    <label class="block text-xs font-medium text-secondary-500 mb-1">Dari Tanggal</label>
                    <input type="date" name="start_date" value="{{ $startDate }}" class="input w-full">
                </div>
                <div class="w-36">
                    <label class="block text-xs font-medium text-secondary-500 mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" value="{{ $endDate }}" class="input w-full">
                </div>
                <div class="flex items-center gap-2">
                    @if(request()->hasAny(['start_date', 'end_date']))
                        <a href="{{ route('reports.attendance.lateness') }}" class="btn btn-ghost btn-sm">Reset</a>
                    @endif
                    <button type="submit" class="btn btn-primary btn-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Summary by Employee --}}
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="font-semibold text-secondary-900">Ringkasan Keterlambatan per Karyawan</h3>
        </div>
        <x-table>
            <x-slot name="header">
                <th>Peringkat</th>
                <th>Karyawan</th>
                <th>Departemen</th>
                <th class="text-center">Jumlah Terlambat</th>
                <th class="text-right">Total Menit</th>
                <th class="text-right">Rata-rata/Hari</th>
            </x-slot>

            @forelse($employeeLateStats as $index => $stat)
                <tr>
                    <td>
                        @if($index < 3)
                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-full {{ $index === 0 ? 'bg-danger-100 text-danger-700' : ($index === 1 ? 'bg-warning-100 text-warning-700' : 'bg-orange-100 text-orange-700') }} font-bold text-sm">
                                {{ $index + 1 }}
                            </span>
                        @else
                            <span class="text-secondary-500 pl-2">{{ $index + 1 }}</span>
                        @endif
                    </td>
                    <td>
                        <span class="font-medium text-secondary-900">{{ $stat['employee']->full_name ?? '-' }}</span>
                        <p class="text-xs text-secondary-400">{{ $stat['employee']->employee_id ?? '-' }}</p>
                    </td>
                    <td class="text-secondary-600">{{ $stat['employee']->department?->name ?? '-' }}</td>
                    <td class="text-center">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-warning-100 text-warning-800">
                            {{ $stat['total_late'] }}x
                        </span>
                    </td>
                    <td class="text-right">
                        <span class="font-medium text-warning-600">{{ floor($stat['total_minutes'] / 60) }}j {{ $stat['total_minutes'] % 60 }}m</span>
                    </td>
                    <td class="text-right text-secondary-600">{{ $stat['avg_minutes'] }} menit</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center py-12">
                        <div class="flex flex-col items-center">
                            <svg class="w-12 h-12 text-success-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <p class="text-secondary-500">Tidak ada data keterlambatan pada periode ini</p>
                            <p class="text-success-600 text-sm mt-1">Semua karyawan tepat waktu!</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </x-table>
    </div>

    {{-- Detail Lateness Records --}}
    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold text-secondary-900">Detail Catatan Keterlambatan</h3>
        </div>
        <x-table>
            <x-slot name="header">
                <th>Tanggal</th>
                <th>Karyawan</th>
                <th>Departemen</th>
                <th class="text-center">Jadwal</th>
                <th class="text-center">Clock In</th>
                <th class="text-center">Status</th>
                <th class="text-right">Terlambat</th>
            </x-slot>

            @forelse($lateAttendances as $attendance)
                <tr>
                    <td class="text-secondary-900">{{ $attendance->date->format('d M Y') }}</td>
                    <td>
                        <span class="font-medium text-secondary-900">{{ $attendance->employee->full_name ?? '-' }}</span>
                        <p class="text-xs text-secondary-400">{{ $attendance->employee->employee_id ?? '-' }}</p>
                    </td>
                    <td class="text-secondary-600">{{ $attendance->employee->department?->name ?? '-' }}</td>
                    <td class="text-center text-secondary-600">{{ $attendance->scheduled_clock_in?->format('H:i') ?? '-' }}</td>
                    <td class="text-center text-secondary-900 font-medium">{{ $attendance->clock_in?->format('H:i') ?? '-' }}</td>
                    <td class="text-center">
                        @if($attendance->clock_in_status === 'very_late')
                            <x-badge type="danger">Sangat Terlambat</x-badge>
                        @else
                            <x-badge type="warning">Terlambat</x-badge>
                        @endif
                    </td>
                    <td class="text-right">
                        <span class="text-warning-600 font-medium">{{ $attendance->late_minutes }} menit</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-12">
                        <div class="flex flex-col items-center">
                            <svg class="w-12 h-12 text-success-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <p class="text-secondary-500">Tidak ada catatan keterlambatan</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </x-table>

        <div class="card-footer">
            <p class="text-sm text-secondary-500">Menampilkan {{ $lateAttendances->count() }} catatan keterlambatan</p>
        </div>
    </div>
@endsection
