@extends('layouts.admin')

@section('title', 'Laporan Karyawan')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Laporan Karyawan</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Laporan Karyawan</h1>
            <p class="text-secondary-500 mt-1">Lihat dan export data karyawan perusahaan</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('reports.employees.export', array_merge(request()->query(), ['format' => 'excel'])) }}" class="btn btn-ghost">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export Excel
            </a>
            <a href="{{ route('reports.employees.export', array_merge(request()->query(), ['format' => 'pdf'])) }}" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                Export PDF
            </a>
        </div>
    </div>
@endsection

@section('content')
    {{-- Summary Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-4">
        <div class="stat-card">
            <p class="stat-card-label">Total</p>
            <p class="text-lg font-bold text-secondary-900">{{ $summary['total'] }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-card-label">Aktif</p>
            <p class="text-lg font-bold text-success-600">{{ $summary['active'] }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-card-label">Tidak Aktif</p>
            <p class="text-lg font-bold text-danger-600">{{ $summary['inactive'] }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-card-label">Permanen</p>
            <p class="text-lg font-bold text-primary-600">{{ $summary['permanent'] }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-card-label">Kontrak</p>
            <p class="text-lg font-bold text-warning-600">{{ $summary['contract'] }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-card-label">Probation</p>
            <p class="text-lg font-bold text-info-600">{{ $summary['probation'] }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body-sm">
            <form action="{{ route('reports.employees') }}" method="GET" class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-[180px]">
                    <label class="block text-xs font-medium text-secondary-500 mb-1">Cari</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama, email, atau ID..." class="input w-full">
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
                <div class="w-32">
                    <label class="block text-xs font-medium text-secondary-500 mb-1">Status Kerja</label>
                    <select name="employment_status" class="input w-full">
                        <option value="">Semua</option>
                        <option value="permanent" {{ request('employment_status') === 'permanent' ? 'selected' : '' }}>Permanen</option>
                        <option value="contract" {{ request('employment_status') === 'contract' ? 'selected' : '' }}>Kontrak</option>
                        <option value="probation" {{ request('employment_status') === 'probation' ? 'selected' : '' }}>Probation</option>
                    </select>
                </div>
                <div class="w-28">
                    <label class="block text-xs font-medium text-secondary-500 mb-1">Status</label>
                    <select name="is_active" class="input w-full">
                        <option value="">Semua</option>
                        <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Aktif</option>
                        <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Tidak Aktif</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    @if(request()->hasAny(['search', 'department_id', 'employment_status', 'is_active']))
                        <a href="{{ route('reports.employees') }}" class="btn btn-ghost btn-sm">Reset</a>
                    @endif
                    <button type="submit" class="btn btn-primary btn-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Employee Table --}}
    <div class="card">
        <x-table>
            <x-slot name="header">
                <th>No</th>
                <th>Karyawan</th>
                <th>Departemen</th>
                <th>Jabatan</th>
                <th>Status Kerja</th>
                <th>Bergabung</th>
                <th>Status</th>
            </x-slot>

            @forelse($employees as $index => $employee)
                <tr>
                    <td class="text-secondary-500">{{ $index + 1 }}</td>
                    <td>
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="text-primary-700 text-xs font-medium">{{ substr($employee->first_name, 0, 1) }}</span>
                            </div>
                            <div class="min-w-0">
                                <span class="font-medium text-secondary-900 block truncate">{{ $employee->full_name }}</span>
                                <p class="text-xs text-secondary-400">{{ $employee->employee_id }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="text-secondary-600">{{ $employee->department->name ?? '-' }}</td>
                    <td class="text-secondary-600">{{ $employee->position->name ?? '-' }}</td>
                    <td>
                        @switch($employee->employment_status)
                            @case('permanent')
                                <x-badge type="success">Permanen</x-badge>
                                @break
                            @case('contract')
                                <x-badge type="warning">Kontrak</x-badge>
                                @break
                            @case('probation')
                                <x-badge type="info">Probation</x-badge>
                                @break
                        @endswitch
                    </td>
                    <td class="text-secondary-600">{{ $employee->hire_date?->format('d M Y') ?? '-' }}</td>
                    <td>
                        @if($employee->is_active)
                            <x-badge type="success">Aktif</x-badge>
                        @else
                            <x-badge type="danger">Tidak Aktif</x-badge>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-12">
                        <div class="flex flex-col items-center">
                            <svg class="w-12 h-12 text-secondary-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            <p class="text-secondary-500">Tidak ada data karyawan yang sesuai filter</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </x-table>

        <div class="card-footer">
            <p class="text-sm text-secondary-500">Menampilkan {{ $employees->count() }} karyawan</p>
        </div>
    </div>
@endsection
