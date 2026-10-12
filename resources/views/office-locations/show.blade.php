@extends('layouts.admin')

@section('title', 'Detail Lokasi Kantor')

@section('breadcrumb')
    <a href="{{ route('office-locations.index') }}" class="text-primary-600 hover:text-primary-700">Lokasi Kantor</a>
    <svg class="w-4 h-4 mx-2 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">{{ $officeLocation->name }}</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">{{ $officeLocation->name }}</h1>
            <p class="text-secondary-500 mt-1">{{ $officeLocation->code }}</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('office-locations.edit', $officeLocation) }}" class="btn btn-secondary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Edit
            </a>
            <button
                type="button"
                @click="$dispatch('confirm-dialog', {
                    title: 'Hapus Lokasi Kantor',
                    message: 'Apakah Anda yakin ingin menghapus lokasi {{ $officeLocation->name }}?',
                    confirmText: 'Ya, Hapus',
                    type: 'danger',
                    formAction: '{{ route('office-locations.destroy', $officeLocation) }}'
                })"
                class="btn btn-danger"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                Hapus
            </button>
        </div>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Info --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Informasi Lokasi</h3>
                </div>
                <div class="card-body">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Nama Lokasi</dt>
                            <dd class="mt-1 text-secondary-900">{{ $officeLocation->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Kode Lokasi</dt>
                            <dd class="mt-1 text-secondary-900">{{ $officeLocation->code }}</dd>
                        </div>
                        <div class="md:col-span-2">
                            <dt class="text-sm font-medium text-secondary-500">Alamat</dt>
                            <dd class="mt-1 text-secondary-900">{{ $officeLocation->address ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Kota</dt>
                            <dd class="mt-1 text-secondary-900">{{ $officeLocation->city ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Provinsi</dt>
                            <dd class="mt-1 text-secondary-900">{{ $officeLocation->province ?? '-' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Koordinat GPS</h3>
                </div>
                <div class="card-body">
                    <dl class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Latitude</dt>
                            <dd class="mt-1 text-secondary-900">
                                {{ $officeLocation->latitude ? number_format($officeLocation->latitude, 8) : '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Longitude</dt>
                            <dd class="mt-1 text-secondary-900">
                                {{ $officeLocation->longitude ? number_format($officeLocation->longitude, 8) : '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Radius</dt>
                            <dd class="mt-1 text-secondary-900">{{ $officeLocation->radius }} meter</dd>
                        </div>
                    </dl>

                    @if($officeLocation->latitude && $officeLocation->longitude)
                        <div class="mt-4 p-4 bg-secondary-50 rounded-lg">
                            <div class="flex items-center gap-2 text-sm text-secondary-600">
                                <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <a href="https://www.google.com/maps?q={{ $officeLocation->latitude }},{{ $officeLocation->longitude }}" target="_blank" class="text-primary-600 hover:text-primary-700 underline">
                                    Lihat di Google Maps
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Employee List --}}
            <div class="card">
                <div class="card-header flex items-center justify-between">
                    <h3 class="card-title">Karyawan di Lokasi Ini</h3>
                    <span class="text-sm text-secondary-500">{{ $officeLocation->employees->count() }} orang</span>
                </div>
                <div class="card-body p-0">
                    @if($officeLocation->employees->count() > 0)
                        <div class="divide-y divide-secondary-100">
                            @foreach($officeLocation->employees as $employee)
                                <div class="flex items-center justify-between p-4 hover:bg-secondary-50">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center">
                                            <span class="text-primary-700 font-medium">{{ substr($employee->full_name, 0, 1) }}</span>
                                        </div>
                                        <div>
                                            <p class="font-medium text-secondary-900">{{ $employee->full_name }}</p>
                                            <p class="text-sm text-secondary-500">{{ $employee->employee_id }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @if($employee->pivot->is_primary)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-primary-100 text-primary-800">Primary</span>
                                        @endif
                                        <a href="{{ route('employees.show', $employee) }}" class="text-primary-600 hover:text-primary-700 text-sm">
                                            Lihat
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-8 text-center">
                            <svg class="w-12 h-12 text-secondary-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            <p class="text-secondary-500">Belum ada karyawan di lokasi ini.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Status</h3>
                </div>
                <div class="card-body space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-secondary-600">Status Aktif</span>
                        @if($officeLocation->is_active)
                            <x-badge type="success">Aktif</x-badge>
                        @else
                            <x-badge type="danger">Tidak Aktif</x-badge>
                        @endif
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-secondary-600">Kantor Pusat</span>
                        @if($officeLocation->is_headquarters)
                            <x-badge type="info">Ya</x-badge>
                        @else
                            <x-badge type="secondary">Tidak</x-badge>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Informasi Sistem</h3>
                </div>
                <div class="card-body space-y-3">
                    <div>
                        <dt class="text-xs font-medium text-secondary-500">Dibuat</dt>
                        <dd class="text-sm text-secondary-900">{{ $officeLocation->created_at->format('d M Y H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-secondary-500">Terakhir Diupdate</dt>
                        <dd class="text-sm text-secondary-900">{{ $officeLocation->updated_at->format('d M Y H:i') }}</dd>
                    </div>
                </div>
            </div>

            <a href="{{ route('office-locations.index') }}" class="btn btn-ghost w-full">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Kembali ke Daftar
            </a>
        </div>
    </div>
@endsection
