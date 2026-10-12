@extends('layouts.admin')

@section('title', 'Daftar Karyawan')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Karyawan</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Daftar Karyawan</h1>
            <p class="text-secondary-500 mt-1">Kelola data karyawan perusahaan Anda.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('imports.employees.index') }}" class="btn btn-secondary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Import
            </a>
            <a href="{{ route('employees.create') }}" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Tambah Karyawan
            </a>
        </div>
    </div>
@endsection

@section('content')
    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body-sm">
            <form action="{{ route('employees.index') }}" method="GET" class="flex flex-wrap items-end gap-3">
                {{-- Search --}}
                <div class="flex-1 min-w-[200px]">
                    <label for="search" class="block text-xs font-medium text-secondary-500 mb-1">Cari</label>
                    <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Nama, ID, atau email..." class="input w-full">
                </div>

                {{-- Department --}}
                <div class="w-40">
                    <label for="department_id" class="block text-xs font-medium text-secondary-500 mb-1">Departemen</label>
                    <select name="department_id" id="department_id" class="input w-full">
                        <option value="">Semua</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" {{ request('department_id') == $department->id ? 'selected' : '' }}>
                                {{ $department->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Employment Status --}}
                <div class="w-32">
                    <label for="employment_status" class="block text-xs font-medium text-secondary-500 mb-1">Status Kerja</label>
                    <select name="employment_status" id="employment_status" class="input w-full">
                        <option value="">Semua</option>
                        <option value="permanent" {{ request('employment_status') == 'permanent' ? 'selected' : '' }}>Tetap</option>
                        <option value="contract" {{ request('employment_status') == 'contract' ? 'selected' : '' }}>Kontrak</option>
                        <option value="probation" {{ request('employment_status') == 'probation' ? 'selected' : '' }}>Probation</option>
                        <option value="intern" {{ request('employment_status') == 'intern' ? 'selected' : '' }}>Magang</option>
                    </select>
                </div>

                {{-- Active Status --}}
                <div class="w-28">
                    <label for="is_active" class="block text-xs font-medium text-secondary-500 mb-1">Status</label>
                    <select name="is_active" id="is_active" class="input w-full">
                        <option value="">Semua</option>
                        <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Aktif</option>
                        <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    @if(request()->hasAny(['search', 'department_id', 'employment_status', 'is_active']))
                        <a href="{{ route('employees.index') }}" class="btn btn-ghost btn-sm">Reset</a>
                    @endif
                    <button type="submit" class="btn btn-primary btn-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        Cari
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Employee List --}}
    <div class="card">
        <x-table>
            <x-slot name="header">
                <th>Karyawan</th>
                <th>ID Karyawan</th>
                <th>Departemen</th>
                <th>Jabatan</th>
                <th>Status Kerja</th>
                <th>Status</th>
                <th class="text-right">Aksi</th>
            </x-slot>

            @forelse($employees as $employee)
                <tr>
                    <td>
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center text-primary-600 font-medium text-xs flex-shrink-0">
                                {{ strtoupper(substr($employee->first_name, 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <a href="{{ route('employees.show', $employee) }}" class="font-medium text-secondary-900 hover:text-primary-600 block truncate">
                                    {{ $employee->full_name }}
                                </a>
                                @if($employee->email)
                                    <p class="text-xs text-secondary-400 truncate">{{ $employee->email }}</p>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="font-mono text-xs text-secondary-600">{{ $employee->employee_id }}</span>
                    </td>
                    <td class="text-secondary-600">{{ $employee->department?->name ?? '-' }}</td>
                    <td class="text-secondary-600">{{ $employee->position?->name ?? '-' }}</td>
                    <td>
                        @switch($employee->employment_status)
                            @case('permanent')
                                <x-badge type="success">Tetap</x-badge>
                                @break
                            @case('contract')
                                <x-badge type="info">Kontrak</x-badge>
                                @break
                            @case('probation')
                                <x-badge type="warning">Probation</x-badge>
                                @break
                            @case('intern')
                                <x-badge type="secondary">Magang</x-badge>
                                @break
                            @default
                                <x-badge type="secondary">-</x-badge>
                        @endswitch
                    </td>
                    <td>
                        @if($employee->is_active)
                            <x-badge type="success">Aktif</x-badge>
                        @else
                            <x-badge type="danger">Nonaktif</x-badge>
                        @endif
                    </td>
                    <td>
                        <div class="flex items-center justify-end gap-1">
                            <a href="{{ route('employees.show', $employee) }}" class="p-1.5 text-secondary-400 hover:text-primary-600 hover:bg-primary-50 rounded-md transition-colors" title="Lihat Detail">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </a>
                            <a href="{{ route('employees.edit', $employee) }}" class="p-1.5 text-secondary-400 hover:text-primary-600 hover:bg-primary-50 rounded-md transition-colors" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            <button
                                type="button"
                                @click="$dispatch('confirm-dialog', {
                                    title: 'Hapus Karyawan',
                                    message: 'Apakah Anda yakin ingin menghapus karyawan {{ $employee->full_name }}?',
                                    confirmText: 'Ya, Hapus',
                                    type: 'danger',
                                    formAction: '{{ route('employees.destroy', $employee) }}'
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
                            <svg class="w-12 h-12 text-secondary-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            <p class="text-secondary-500">Belum ada data karyawan.</p>
                            <a href="{{ route('employees.create') }}" class="btn btn-primary mt-4">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                Tambah Karyawan
                            </a>
                        </div>
                    </td>
                </tr>
            @endforelse
        </x-table>

        @if($employees->hasPages())
            <div class="card-footer">
                {{ $employees->links() }}
            </div>
        @endif
    </div>
@endsection
