@extends('layouts.admin')

@section('title', 'Daftar Lokasi Kantor')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Lokasi Kantor</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Daftar Lokasi Kantor</h1>
            <p class="text-secondary-500 mt-1">Kelola lokasi kantor dan pengaturan GPS untuk absensi.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('office-locations.create') }}" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Tambah Lokasi
            </a>
        </div>
    </div>
@endsection

@section('content')
    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body-sm">
            <form action="{{ route('office-locations.index') }}" method="GET" class="flex flex-wrap items-end gap-3">
                {{-- Search --}}
                <div class="flex-1 min-w-[180px]">
                    <label for="search" class="block text-xs font-medium text-secondary-500 mb-1">Cari</label>
                    <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Nama, kode, atau kota..." class="input w-full">
                </div>

                <div class="flex items-center gap-2">
                    @if(request()->hasAny(['search']))
                        <a href="{{ route('office-locations.index') }}" class="btn btn-ghost btn-sm">Reset</a>
                    @endif
                    <button type="submit" class="btn btn-primary btn-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        Cari
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Office Location List --}}
    <div class="card">
        <x-table>
            <x-slot name="header">
                <th>Nama Lokasi</th>
                <th>Alamat</th>
                <th>Koordinat GPS</th>
                <th>Radius</th>
                <th>Karyawan</th>
                <th>Status</th>
                <th class="text-right">Aksi</th>
            </x-slot>

            @forelse($officeLocations as $location)
                <tr>
                    <td>
                        <div>
                            <span class="font-medium text-secondary-900">{{ $location->name }}</span>
                            <p class="text-sm text-secondary-500">{{ $location->code }}</p>
                            @if($location->is_headquarters)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-primary-100 text-primary-800">Kantor Pusat</span>
                            @endif
                        </div>
                    </td>
                    <td>
                        <div class="text-sm">
                            @if($location->address)
                                <p>{{ Str::limit($location->address, 30) }}</p>
                            @endif
                            @if($location->city || $location->province)
                                <p class="text-secondary-500">{{ $location->city }}{{ $location->city && $location->province ? ', ' : '' }}{{ $location->province }}</p>
                            @endif
                        </div>
                    </td>
                    <td>
                        @if($location->latitude && $location->longitude)
                            <div class="flex items-center gap-1 text-sm">
                                <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <span>{{ number_format($location->latitude, 6) }}, {{ number_format($location->longitude, 6) }}</span>
                            </div>
                        @else
                            <span class="text-secondary-400 text-sm">Belum diatur</span>
                        @endif
                    </td>
                    <td>
                        <span class="font-medium">{{ $location->radius }}</span>
                        <span class="text-secondary-500 text-sm">meter</span>
                    </td>
                    <td>
                        <span class="font-medium">{{ $location->employees->count() }}</span>
                        <span class="text-secondary-500">orang</span>
                    </td>
                    <td>
                        @if($location->is_active)
                            <x-badge type="success">Aktif</x-badge>
                        @else
                            <x-badge type="danger">Tidak Aktif</x-badge>
                        @endif
                    </td>
                    <td>
                        <div class="flex items-center justify-end gap-1">
                            <a href="{{ route('office-locations.show', $location) }}" class="p-1.5 text-secondary-400 hover:text-primary-600 hover:bg-primary-50 rounded-md transition-colors" title="Detail">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </a>
                            <a href="{{ route('office-locations.edit', $location) }}" class="p-1.5 text-secondary-400 hover:text-primary-600 hover:bg-primary-50 rounded-md transition-colors" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            <button
                                type="button"
                                @click="$dispatch('confirm-dialog', {
                                    title: 'Hapus Lokasi Kantor',
                                    message: 'Apakah Anda yakin ingin menghapus lokasi {{ $location->name }}?',
                                    confirmText: 'Ya, Hapus',
                                    type: 'danger',
                                    formAction: '{{ route('office-locations.destroy', $location) }}'
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
                            <svg class="w-12 h-12 text-secondary-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <p class="text-secondary-500">Belum ada data lokasi kantor.</p>
                            <a href="{{ route('office-locations.create') }}" class="btn btn-primary mt-4">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                Tambah Lokasi
                            </a>
                        </div>
                    </td>
                </tr>
            @endforelse
        </x-table>

        @if($officeLocations->hasPages())
            <div class="card-footer">
                {{ $officeLocations->links() }}
            </div>
        @endif
    </div>
@endsection
