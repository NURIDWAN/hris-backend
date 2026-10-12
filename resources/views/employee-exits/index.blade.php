@extends('layouts.admin')

@section('title', 'Exit Management')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Exit Management</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Exit Management</h1>
            <p class="text-secondary-500 mt-1">Kelola proses keluar karyawan (resign, terminasi, pensiun, dll).</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('employee-exits.create') }}" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Tambah Exit
            </a>
        </div>
    </div>
@endsection

@section('content')
    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="card">
            <div class="card-body-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-full bg-warning-100 text-warning-600 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-secondary-900">{{ $stats['pending'] }}</p>
                    <p class="text-sm text-secondary-500">Menunggu</p>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-full bg-primary-100 text-primary-600 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-secondary-900">{{ $stats['approved'] }}</p>
                    <p class="text-sm text-secondary-500">Disetujui</p>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-full bg-success-100 text-success-600 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-secondary-900">{{ $stats['completed'] }}</p>
                    <p class="text-sm text-secondary-500">Selesai</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body-sm">
            <form action="{{ route('employee-exits.index') }}" method="GET" class="flex flex-wrap items-end gap-3">
                {{-- Status --}}
                <div class="w-32">
                    <label for="status" class="block text-xs font-medium text-secondary-500 mb-1">Status</label>
                    <select name="status" id="status" class="input w-full">
                        <option value="">Semua</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Menunggu</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Disetujui</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Selesai</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Ditolak</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                    </select>
                </div>

                {{-- Exit Type --}}
                <div class="w-36">
                    <label for="exit_type" class="block text-xs font-medium text-secondary-500 mb-1">Jenis Exit</label>
                    <select name="exit_type" id="exit_type" class="input w-full">
                        <option value="">Semua</option>
                        <option value="resignation" {{ request('exit_type') === 'resignation' ? 'selected' : '' }}>Resign</option>
                        <option value="termination" {{ request('exit_type') === 'termination' ? 'selected' : '' }}>Terminasi</option>
                        <option value="retirement" {{ request('exit_type') === 'retirement' ? 'selected' : '' }}>Pensiun</option>
                        <option value="contract_end" {{ request('exit_type') === 'contract_end' ? 'selected' : '' }}>Kontrak Habis</option>
                        <option value="mutual_agreement" {{ request('exit_type') === 'mutual_agreement' ? 'selected' : '' }}>Kesepakatan</option>
                    </select>
                </div>

                {{-- Search --}}
                <div class="w-48">
                    <label for="search" class="block text-xs font-medium text-secondary-500 mb-1">Cari</label>
                    <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Nama atau ID karyawan..." class="input w-full">
                </div>

                <div class="flex items-center gap-2">
                    @if(request()->hasAny(['status', 'exit_type', 'search']))
                        <a href="{{ route('employee-exits.index') }}" class="btn btn-ghost btn-sm">Reset</a>
                    @endif
                    <button type="submit" class="btn btn-primary btn-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        Cari
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Exit List --}}
    <div class="card">
        <x-table>
            <x-slot name="header">
                <th>Karyawan</th>
                <th>Jenis Exit</th>
                <th>Tanggal Efektif</th>
                <th>Status</th>
                <th>Checklist</th>
                <th class="text-right">Aksi</th>
            </x-slot>

            @forelse($employeeExits as $exit)
                <tr>
                    <td>
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center flex-shrink-0">
                                <span class="text-primary-700 text-xs font-medium">{{ strtoupper(substr($exit->employee->full_name, 0, 1)) }}</span>
                            </div>
                            <div class="min-w-0">
                                <span class="font-medium text-secondary-900 block truncate">{{ $exit->employee->full_name }}</span>
                                <p class="text-xs text-secondary-400">{{ $exit->employee->employee_id }}</p>
                            </div>
                        </div>
                    </td>
                    <td>
                        <x-badge type="{{ $exit->exit_type_color }}">{{ $exit->exit_type_label }}</x-badge>
                    </td>
                    <td class="text-secondary-700">{{ $exit->effective_date->format('d M Y') }}</td>
                    <td>
                        <x-badge type="{{ $exit->status_color }}">{{ $exit->status_label }}</x-badge>
                    </td>
                    <td>
                        <div class="flex items-center gap-2">
                            <div class="w-16 bg-secondary-200 rounded-full h-1.5">
                                <div class="bg-primary-600 h-1.5 rounded-full" style="width: {{ $exit->checklist_progress }}%"></div>
                            </div>
                            <span class="text-xs text-secondary-500">{{ $exit->checklist_progress }}%</span>
                        </div>
                    </td>
                    <td>
                        <div class="flex items-center justify-end gap-1">
                            <a href="{{ route('employee-exits.show', $exit) }}" class="p-1.5 text-secondary-400 hover:text-primary-600 hover:bg-primary-50 rounded-md transition-colors" title="Detail">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </a>
                            @if($exit->status === 'pending')
                                <a href="{{ route('employee-exits.edit', $exit) }}" class="p-1.5 text-secondary-400 hover:text-warning-600 hover:bg-warning-50 rounded-md transition-colors" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <form action="{{ route('employee-exits.approve', $exit) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="p-1.5 text-secondary-400 hover:text-success-600 hover:bg-success-50 rounded-md transition-colors" title="Setujui">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center py-12">
                        <div class="flex flex-col items-center">
                            <svg class="w-12 h-12 text-secondary-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            <p class="text-secondary-500">Belum ada data exit karyawan.</p>
                            <a href="{{ route('employee-exits.create') }}" class="btn btn-primary mt-4">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                Tambah Exit
                            </a>
                        </div>
                    </td>
                </tr>
            @endforelse
        </x-table>

        @if($employeeExits->hasPages())
            <div class="card-footer">
                {{ $employeeExits->links() }}
            </div>
        @endif
    </div>
@endsection
