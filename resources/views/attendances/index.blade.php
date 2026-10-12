@extends('layouts.admin')

@section('title', 'Daftar Kehadiran')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Kehadiran</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Daftar Kehadiran</h1>
            <p class="text-secondary-500 mt-1">Kelola data kehadiran karyawan.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('attendances.create') }}" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Input Manual
            </a>
        </div>
    </div>
@endsection

@section('content')
    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body-sm">
            <form action="{{ route('attendances.index') }}" method="GET" class="flex flex-wrap items-end gap-3">
                {{-- Date --}}
                <div class="w-36">
                    <label for="date" class="block text-xs font-medium text-secondary-500 mb-1">Tanggal</label>
                    <input type="date" name="date" id="date" value="{{ request('date') }}" class="input w-full">
                </div>

                {{-- Month --}}
                <div class="w-36">
                    <label for="month" class="block text-xs font-medium text-secondary-500 mb-1">Bulan</label>
                    <input type="month" name="month" id="month" value="{{ request('month') }}" class="input w-full">
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
                        <option value="absent" {{ request('status') === 'absent' ? 'selected' : '' }}>Tidak Hadir</option>
                        <option value="late" {{ request('status') === 'late' ? 'selected' : '' }}>Terlambat</option>
                        <option value="half_day" {{ request('status') === 'half_day' ? 'selected' : '' }}>Setengah Hari</option>
                        <option value="leave" {{ request('status') === 'leave' ? 'selected' : '' }}>Cuti</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    @if(request()->hasAny(['date', 'month', 'employee_id', 'status']))
                        <a href="{{ route('attendances.index') }}" class="btn btn-ghost btn-sm">Reset</a>
                    @endif
                    <button type="submit" class="btn btn-primary btn-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        Cari
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Attendance List --}}
    <div class="card">
        <x-table>
            <x-slot name="header">
                <th>Tanggal</th>
                <th>Karyawan</th>
                <th>Jam Masuk</th>
                <th>Jam Pulang</th>
                <th>Durasi</th>
                <th>Status</th>
                <th class="text-right">Aksi</th>
            </x-slot>

            @forelse($attendances as $attendance)
                <tr>
                    <td>
                        <span class="font-medium text-secondary-900">{{ $attendance->date->format('d M Y') }}</span>
                        <p class="text-xs text-secondary-400">{{ $attendance->date->translatedFormat('l') }}</p>
                    </td>
                    <td>
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center flex-shrink-0">
                                <span class="text-primary-700 text-xs font-medium">{{ substr($attendance->employee->first_name, 0, 1) }}</span>
                            </div>
                            <div class="min-w-0">
                                <span class="font-medium text-secondary-900 block truncate">{{ $attendance->employee->full_name }}</span>
                                <p class="text-xs text-secondary-400">{{ $attendance->employee->employee_id }}</p>
                            </div>
                        </div>
                    </td>
                    <td>
                        @if($attendance->clock_in)
                            <span class="font-medium text-secondary-900">{{ $attendance->formatted_clock_in }}</span>
                            @if($attendance->clock_in_status === 'late')
                                <p class="text-xs text-warning-600">Terlambat {{ $attendance->late_minutes }}m</p>
                            @elseif($attendance->clock_in_status === 'very_late')
                                <p class="text-xs text-danger-600">Sangat Terlambat</p>
                            @endif
                        @else
                            <span class="text-secondary-400">-</span>
                        @endif
                    </td>
                    <td>
                        @if($attendance->clock_out)
                            <span class="font-medium text-secondary-900">{{ $attendance->formatted_clock_out }}</span>
                            @if($attendance->clock_out_status === 'early')
                                <p class="text-xs text-warning-600">Pulang Awal</p>
                            @elseif($attendance->clock_out_status === 'overtime')
                                <p class="text-xs text-primary-600">Lembur {{ $attendance->overtime_minutes }}m</p>
                            @endif
                        @else
                            <span class="text-secondary-400">-</span>
                        @endif
                    </td>
                    <td>
                        @if($attendance->working_minutes > 0)
                            <span class="text-secondary-700">{{ $attendance->working_hours }}</span>
                        @else
                            <span class="text-secondary-400">-</span>
                        @endif
                    </td>
                    <td>
                        <x-badge :type="$attendance->status_color">{{ $attendance->status_label }}</x-badge>
                        @if($attendance->is_manual_entry)
                            <span class="text-xs text-secondary-400 block mt-0.5">Manual</span>
                        @endif
                    </td>
                    <td>
                        <div class="flex items-center justify-end gap-1">
                            <a href="{{ route('attendances.show', $attendance) }}" class="p-1.5 text-secondary-400 hover:text-primary-600 hover:bg-primary-50 rounded-md transition-colors" title="Detail">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </a>
                            <a href="{{ route('attendances.edit', $attendance) }}" class="p-1.5 text-secondary-400 hover:text-primary-600 hover:bg-primary-50 rounded-md transition-colors" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            <button
                                type="button"
                                @click="$dispatch('confirm-dialog', {
                                    title: 'Hapus Data Kehadiran',
                                    message: 'Apakah Anda yakin ingin menghapus data kehadiran ini?',
                                    confirmText: 'Ya, Hapus',
                                    type: 'danger',
                                    formAction: '{{ route('attendances.destroy', $attendance) }}'
                                })"
                                class="p-1.5 text-secondary-400 hover:text-danger-600 hover:bg-danger-50 rounded-md transition-colors"
                                title="Hapus"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-12">
                        <div class="flex flex-col items-center">
                            <svg class="w-12 h-12 text-secondary-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                            <p class="text-secondary-500">Belum ada data kehadiran.</p>
                            <a href="{{ route('attendances.create') }}" class="btn btn-primary mt-4">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                Input Manual
                            </a>
                        </div>
                    </td>
                </tr>
            @endforelse
        </x-table>

        @if($attendances->hasPages())
            <div class="card-footer">
                {{ $attendances->links() }}
            </div>
        @endif
    </div>
@endsection
