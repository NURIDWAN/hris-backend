@extends('layouts.admin')

@section('title', 'Detail Komponen Gaji')

@section('breadcrumb')
    <a href="{{ route('salary-components.index') }}" class="text-slate-500 hover:text-primary-600">Komponen Gaji</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">{{ $salaryComponent->name }}</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">{{ $salaryComponent->name }}</h1>
                <div class="flex items-center gap-2 mt-1">
                    @if($salaryComponent->code)
                        <span class="text-secondary-500">{{ $salaryComponent->code }}</span>
                        <span class="text-secondary-300">|</span>
                    @endif
                    <x-badge type="{{ $salaryComponent->type_color }}">{{ $salaryComponent->type_label }}</x-badge>
                    @if($salaryComponent->is_active)
                        <x-badge type="success">Aktif</x-badge>
                    @else
                        <x-badge type="danger">Tidak Aktif</x-badge>
                    @endif
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('salary-components.edit', $salaryComponent) }}" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Edit
            </a>
            <a href="{{ route('salary-components.index') }}" class="btn btn-ghost">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Kembali
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Info --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Basic Info --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Informasi Dasar</h3>
                </div>
                <div class="card-body">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Nama Komponen</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $salaryComponent->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Kode</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $salaryComponent->code ?: '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Tipe</dt>
                            <dd class="mt-1">
                                <x-badge type="{{ $salaryComponent->type_color }}">{{ $salaryComponent->type_label }}</x-badge>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Kategori</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $salaryComponent->category_label }}</dd>
                        </div>
                        @if($salaryComponent->description)
                            <div class="sm:col-span-2">
                                <dt class="text-sm font-medium text-secondary-500">Deskripsi</dt>
                                <dd class="mt-1 text-sm text-secondary-900">{{ $salaryComponent->description }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>

            {{-- Calculation Info --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Perhitungan</h3>
                </div>
                <div class="card-body">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Tipe Perhitungan</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $salaryComponent->calculation_type_label }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Nilai</dt>
                            <dd class="mt-1 text-sm font-semibold text-secondary-900">{{ $salaryComponent->formatted_amount }}</dd>
                        </div>
                        @if($salaryComponent->calculation_type === 'percentage' && $salaryComponent->percentage_base)
                            <div>
                                <dt class="text-sm font-medium text-secondary-500">Basis Persentase</dt>
                                <dd class="mt-1 text-sm text-secondary-900">
                                    {{ $salaryComponent->percentage_base === 'basic_salary' ? 'Gaji Pokok' : 'Gaji Kotor' }}
                                </dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Urutan Tampilan</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $salaryComponent->sort_order }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Settings --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Pengaturan</h3>
                </div>
                <div class="card-body space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-secondary-600">Kena Pajak</span>
                        @if($salaryComponent->is_taxable)
                            <x-badge type="warning">Ya</x-badge>
                        @else
                            <x-badge type="secondary">Tidak</x-badge>
                        @endif
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-secondary-600">Wajib</span>
                        @if($salaryComponent->is_mandatory)
                            <x-badge type="primary">Ya</x-badge>
                        @else
                            <x-badge type="secondary">Tidak</x-badge>
                        @endif
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-secondary-600">Status</span>
                        @if($salaryComponent->is_active)
                            <x-badge type="success">Aktif</x-badge>
                        @else
                            <x-badge type="danger">Tidak Aktif</x-badge>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Aksi</h3>
                </div>
                <div class="card-body space-y-2">
                    <form action="{{ route('salary-components.toggle-status', $salaryComponent) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-ghost w-full justify-start">
                            @if($salaryComponent->is_active)
                                <svg class="w-4 h-4 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                Nonaktifkan
                            @else
                                <svg class="w-4 h-4 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Aktifkan
                            @endif
                        </button>
                    </form>
                    <button
                        type="button"
                        @click="$dispatch('confirm-dialog', {
                            title: 'Hapus Komponen Gaji',
                            message: 'Apakah Anda yakin ingin menghapus komponen {{ $salaryComponent->name }}?',
                            confirmText: 'Ya, Hapus',
                            type: 'danger',
                            formAction: '{{ route('salary-components.destroy', $salaryComponent) }}'
                        })"
                        class="btn btn-ghost w-full justify-start text-danger-600 hover:text-danger-700"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Hapus
                    </button>
                </div>
            </div>

            {{-- Timestamps --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Informasi</h3>
                </div>
                <div class="card-body space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-secondary-500">Dibuat</span>
                        <span class="text-secondary-900">{{ $salaryComponent->created_at->format('d M Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-secondary-500">Diperbarui</span>
                        <span class="text-secondary-900">{{ $salaryComponent->updated_at->format('d M Y H:i') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
