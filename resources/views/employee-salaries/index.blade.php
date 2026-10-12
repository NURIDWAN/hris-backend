@extends('layouts.admin')

@section('title', 'Daftar Gaji Karyawan')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Gaji Karyawan</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Daftar Gaji Karyawan</h1>
            <p class="text-secondary-500 mt-1">Kelola pengaturan gaji dan komponen untuk setiap karyawan.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('employee-salaries.create') }}" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Tambah Gaji Karyawan
            </a>
        </div>
    </div>
@endsection

@section('content')
    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body-sm">
            <form action="{{ route('employee-salaries.index') }}" method="GET" class="flex flex-wrap items-end gap-3">
                {{-- Search --}}
                <div class="flex-1 min-w-[180px]">
                    <label for="search" class="block text-xs font-medium text-secondary-500 mb-1">Cari</label>
                    <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Nama atau nomor karyawan..." class="input w-full">
                </div>

                {{-- Status --}}
                <div class="w-28">
                    <label for="status" class="block text-xs font-medium text-secondary-500 mb-1">Status</label>
                    <select name="status" id="status" class="input w-full">
                        <option value="">Semua</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    @if(request()->hasAny(['search', 'status']))
                        <a href="{{ route('employee-salaries.index') }}" class="btn btn-ghost btn-sm">Reset</a>
                    @endif
                    <button type="submit" class="btn btn-primary btn-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        Cari
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Employee Salary List --}}
    <div class="card">
        <x-table>
            <x-slot name="header">
                <th>Karyawan</th>
                <th>Gaji Pokok</th>
                <th>Metode Pembayaran</th>
                <th>Berlaku Sejak</th>
                <th>Status</th>
                <th class="text-right">Aksi</th>
            </x-slot>

            @forelse($employeeSalaries as $salary)
                <tr>
                    <td>
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center flex-shrink-0">
                                <span class="text-primary-700 text-xs font-medium">{{ substr($salary->employee->first_name, 0, 1) }}</span>
                            </div>
                            <div class="min-w-0">
                                <span class="font-medium text-secondary-900 block truncate">{{ $salary->employee->full_name }}</span>
                                <p class="text-xs text-secondary-400">{{ $salary->employee->employee_id }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="font-semibold text-secondary-900">{{ $salary->formatted_basic_salary }}</td>
                    <td>
                        <span class="text-secondary-700">{{ $salary->payment_method_label }}</span>
                        @if($salary->bank_name)
                            <p class="text-xs text-secondary-400">{{ $salary->bank_name }}</p>
                        @endif
                    </td>
                    <td>
                        <span class="text-secondary-700">{{ $salary->effective_date->format('d M Y') }}</span>
                        @if($salary->end_date)
                            <p class="text-xs text-secondary-400">s/d {{ $salary->end_date->format('d M Y') }}</p>
                        @endif
                    </td>
                    <td>
                        @if($salary->is_active)
                            <x-badge type="success">Aktif</x-badge>
                        @else
                            <x-badge type="secondary">Nonaktif</x-badge>
                        @endif
                    </td>
                    <td>
                        <div class="flex items-center justify-end gap-1">
                            <a href="{{ route('employee-salaries.show', $salary) }}" class="p-1.5 text-secondary-400 hover:text-primary-600 hover:bg-primary-50 rounded-md transition-colors" title="Detail">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </a>
                            <a href="{{ route('employee-salaries.edit', $salary) }}" class="p-1.5 text-secondary-400 hover:text-primary-600 hover:bg-primary-50 rounded-md transition-colors" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            <button
                                type="button"
                                @click="$dispatch('confirm-dialog', {
                                    title: 'Hapus Gaji Karyawan',
                                    message: 'Apakah Anda yakin ingin menghapus pengaturan gaji {{ $salary->employee->full_name }}?',
                                    confirmText: 'Ya, Hapus',
                                    type: 'danger',
                                    formAction: '{{ route('employee-salaries.destroy', $salary) }}'
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
                    <td colspan="6" class="text-center py-12">
                        <div class="flex flex-col items-center">
                            <svg class="w-12 h-12 text-secondary-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <p class="text-secondary-500">Belum ada data gaji karyawan.</p>
                            <a href="{{ route('employee-salaries.create') }}" class="btn btn-primary mt-4">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                Tambah Gaji Karyawan
                            </a>
                        </div>
                    </td>
                </tr>
            @endforelse
        </x-table>

        @if($employeeSalaries->hasPages())
            <div class="card-footer">
                {{ $employeeSalaries->links() }}
            </div>
        @endif
    </div>
@endsection
