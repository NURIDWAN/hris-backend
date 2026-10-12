@extends('layouts.admin')

@section('title', 'Daftar Jenis Cuti')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Jenis Cuti</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Daftar Jenis Cuti</h1>
            <p class="text-secondary-500 mt-1">Kelola jenis cuti dan izin karyawan.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('imports.leave-types.index') }}" class="btn btn-secondary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Import
            </a>
            <a href="{{ route('leave-types.create') }}" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Tambah Jenis Cuti
            </a>
        </div>
    </div>
@endsection

@section('content')
    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body-sm">
            <form action="{{ route('leave-types.index') }}" method="GET" class="flex flex-wrap items-end gap-3">
                {{-- Search --}}
                <div class="flex-1 min-w-[180px]">
                    <label for="search" class="block text-xs font-medium text-secondary-500 mb-1">Cari</label>
                    <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Nama atau kode cuti..." class="input w-full">
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
                        <a href="{{ route('leave-types.index') }}" class="btn btn-ghost btn-sm">Reset</a>
                    @endif
                    <button type="submit" class="btn btn-primary btn-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        Cari
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Leave Type List --}}
    <div class="card">
        <x-table>
            <x-slot name="header">
                <th>Nama Jenis Cuti</th>
                <th>Jatah Hari</th>
                <th>Ketentuan</th>
                <th>Carry Forward</th>
                <th>Status</th>
                <th class="text-right">Aksi</th>
            </x-slot>

            @forelse($leaveTypes as $leaveType)
                <tr>
                    <td>
                        <div class="flex items-center gap-2">
                            <div class="w-2.5 h-2.5 rounded-full bg-{{ $leaveType->color_class }}-500 flex-shrink-0"></div>
                            <div>
                                <span class="font-medium text-secondary-900">{{ $leaveType->name }}</span>
                                @if($leaveType->code)
                                    <p class="text-xs text-secondary-400">{{ $leaveType->code }}</p>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="font-semibold text-secondary-900">{{ $leaveType->default_days }}</span>
                        <span class="text-secondary-500 text-xs">hari</span>
                        <p class="text-xs text-secondary-400">{{ $leaveType->paid_label }}</p>
                    </td>
                    <td>
                        <div class="flex flex-wrap gap-1">
                            @if($leaveType->requires_approval)
                                <span class="text-xs text-blue-600">Approval</span>
                            @endif
                            @if($leaveType->requires_attachment)
                                <span class="text-xs text-yellow-600">Lampiran</span>
                            @endif
                            @if($leaveType->min_notice_days > 0)
                                <span class="text-xs text-secondary-500">Min. {{ $leaveType->min_notice_days }}hr</span>
                            @endif
                        </div>
                    </td>
                    <td>
                        @if($leaveType->is_carry_forward)
                            <span class="text-success-600 font-medium">Ya</span>
                            <p class="text-xs text-secondary-400">Max {{ $leaveType->max_carry_forward_days }}hr</p>
                        @else
                            <span class="text-secondary-400">Tidak</span>
                        @endif
                    </td>
                    <td>
                        @if($leaveType->is_active)
                            <x-badge type="success">Aktif</x-badge>
                        @else
                            <x-badge type="danger">Nonaktif</x-badge>
                        @endif
                    </td>
                    <td>
                        <div class="flex items-center justify-end gap-1">
                            <a href="{{ route('leave-types.show', $leaveType) }}" class="p-1.5 text-secondary-400 hover:text-primary-600 hover:bg-primary-50 rounded-md transition-colors" title="Detail">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </a>
                            <a href="{{ route('leave-types.edit', $leaveType) }}" class="p-1.5 text-secondary-400 hover:text-primary-600 hover:bg-primary-50 rounded-md transition-colors" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            <form action="{{ route('leave-types.toggle-status', $leaveType) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="p-1.5 text-secondary-400 hover:text-warning-600 hover:bg-warning-50 rounded-md transition-colors" title="{{ $leaveType->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                    @if($leaveType->is_active)
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                    @else
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    @endif
                                </button>
                            </form>
                            <button
                                type="button"
                                @click="$dispatch('confirm-dialog', {
                                    title: 'Hapus Jenis Cuti',
                                    message: 'Apakah Anda yakin ingin menghapus jenis cuti {{ $leaveType->name }}?',
                                    confirmText: 'Ya, Hapus',
                                    type: 'danger',
                                    formAction: '{{ route('leave-types.destroy', $leaveType) }}'
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
                            <svg class="w-12 h-12 text-secondary-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <p class="text-secondary-500">Belum ada data jenis cuti.</p>
                            <a href="{{ route('leave-types.create') }}" class="btn btn-primary mt-4">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                Tambah Jenis Cuti
                            </a>
                        </div>
                    </td>
                </tr>
            @endforelse
        </x-table>

        @if($leaveTypes->hasPages())
            <div class="card-footer">
                {{ $leaveTypes->links() }}
            </div>
        @endif
    </div>
@endsection
