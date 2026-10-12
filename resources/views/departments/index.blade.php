@extends('layouts.admin')

@section('title', 'Daftar Departemen')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Departemen</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Daftar Departemen</h1>
            <p class="text-secondary-500 mt-1">Kelola struktur departemen perusahaan Anda.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('imports.departments.index') }}" class="btn btn-secondary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Import
            </a>
            <a href="{{ route('departments.create') }}" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Tambah Departemen
            </a>
        </div>
    </div>
@endsection

@section('content')
    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body-sm">
            <form action="{{ route('departments.index') }}" method="GET" class="flex flex-wrap items-end gap-3">
                {{-- Search --}}
                <div class="flex-1 min-w-[200px]">
                    <label for="search" class="block text-xs font-medium text-secondary-500 mb-1">Cari</label>
                    <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Nama atau kode departemen..." class="input w-full">
                </div>

                {{-- Status --}}
                <div class="w-32">
                    <label for="is_active" class="block text-xs font-medium text-secondary-500 mb-1">Status</label>
                    <select name="is_active" id="is_active" class="input w-full">
                        <option value="">Semua</option>
                        <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Aktif</option>
                        <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    @if(request()->hasAny(['search', 'is_active']))
                        <a href="{{ route('departments.index') }}" class="btn btn-ghost btn-sm">Reset</a>
                    @endif
                    <button type="submit" class="btn btn-primary btn-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        Cari
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Department List --}}
    <div class="card">
        <x-table>
            <x-slot name="header">
                <th>Departemen</th>
                <th>Kode</th>
                <th>Jumlah Karyawan</th>
                <th>Jumlah Jabatan</th>
                <th>Status</th>
                <th class="text-right">Aksi</th>
            </x-slot>

            @forelse($departments as $department)
                <tr>
                    <td>
                        <div>
                            <span class="font-medium text-secondary-900">{{ $department->name }}</span>
                            @if($department->parent)
                                <p class="text-xs text-secondary-400">Induk: {{ $department->parent->name }}</p>
                            @endif
                        </div>
                    </td>
                    <td>
                        @if($department->code)
                            <span class="font-mono text-xs bg-secondary-100 text-secondary-600 px-2 py-0.5 rounded">{{ $department->code }}</span>
                        @else
                            <span class="text-secondary-400">-</span>
                        @endif
                    </td>
                    <td>
                        <span class="text-secondary-700">{{ $department->employees_count }}</span>
                        <span class="text-secondary-400 text-xs">orang</span>
                    </td>
                    <td>
                        <span class="text-secondary-700">{{ $department->positions->count() }}</span>
                        <span class="text-secondary-400 text-xs">jabatan</span>
                    </td>
                    <td>
                        @if($department->is_active)
                            <x-badge type="success">Aktif</x-badge>
                        @else
                            <x-badge type="danger">Nonaktif</x-badge>
                        @endif
                    </td>
                    <td>
                        <div class="flex items-center justify-end gap-1">
                            <a href="{{ route('departments.edit', $department) }}" class="p-1.5 text-secondary-400 hover:text-primary-600 hover:bg-primary-50 rounded-md transition-colors" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            <button
                                type="button"
                                @click="$dispatch('confirm-dialog', {
                                    title: 'Hapus Departemen',
                                    message: 'Apakah Anda yakin ingin menghapus departemen {{ $department->name }}?',
                                    confirmText: 'Ya, Hapus',
                                    type: 'danger',
                                    formAction: '{{ route('departments.destroy', $department) }}'
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
                            <svg class="w-12 h-12 text-secondary-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            <p class="text-secondary-500">Belum ada data departemen.</p>
                            <a href="{{ route('departments.create') }}" class="btn btn-primary mt-4">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                Tambah Departemen
                            </a>
                        </div>
                    </td>
                </tr>
            @endforelse
        </x-table>

        @if($departments->hasPages())
            <div class="card-footer">
                {{ $departments->links() }}
            </div>
        @endif
    </div>
@endsection
