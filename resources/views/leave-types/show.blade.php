@extends('layouts.admin')

@section('title', 'Detail Jenis Cuti')

@section('breadcrumb')
    <a href="{{ route('leave-types.index') }}" class="text-slate-500 hover:text-primary-600">Jenis Cuti</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">{{ $leaveType->name }}</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-4 h-4 rounded-full bg-{{ $leaveType->color_class }}-500"></div>
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">{{ $leaveType->name }}</h1>
                @if($leaveType->code)
                    <p class="text-secondary-500 mt-1">Kode: {{ $leaveType->code }}</p>
                @endif
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('leave-types.edit', $leaveType) }}" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Edit
            </a>
            <a href="{{ route('leave-types.index') }}" class="btn btn-ghost">
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
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Nama Jenis Cuti</dt>
                            <dd class="mt-1 text-secondary-900">{{ $leaveType->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Kode</dt>
                            <dd class="mt-1 text-secondary-900">{{ $leaveType->code ?? '-' }}</dd>
                        </div>
                        <div class="md:col-span-2">
                            <dt class="text-sm font-medium text-secondary-500">Deskripsi</dt>
                            <dd class="mt-1 text-secondary-900">{{ $leaveType->description ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Jatah Hari Default</dt>
                            <dd class="mt-1 text-secondary-900 font-semibold">{{ $leaveType->default_days }} hari</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Tipe Pembayaran</dt>
                            <dd class="mt-1">
                                @if($leaveType->is_paid)
                                    <x-badge type="success">Berbayar</x-badge>
                                @else
                                    <x-badge type="secondary">Tidak Berbayar</x-badge>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Requirements --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Ketentuan</h3>
                </div>
                <div class="card-body">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Minimal Pengajuan Sebelumnya</dt>
                            <dd class="mt-1 text-secondary-900">
                                @if($leaveType->min_notice_days > 0)
                                    {{ $leaveType->min_notice_days }} hari
                                @else
                                    Tidak ada minimum
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Maksimal Hari Berturut-turut</dt>
                            <dd class="mt-1 text-secondary-900">{{ $leaveType->max_consecutive_days ? $leaveType->max_consecutive_days . ' hari' : 'Tidak ada batasan' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Perlu Persetujuan</dt>
                            <dd class="mt-1">
                                @if($leaveType->requires_approval)
                                    <x-badge type="info">Ya</x-badge>
                                @else
                                    <x-badge type="secondary">Tidak</x-badge>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Perlu Lampiran</dt>
                            <dd class="mt-1">
                                @if($leaveType->requires_attachment)
                                    <x-badge type="warning">Ya</x-badge>
                                @else
                                    <x-badge type="secondary">Tidak</x-badge>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Carry Forward --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Carry Forward</h3>
                </div>
                <div class="card-body">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Izinkan Carry Forward</dt>
                            <dd class="mt-1">
                                @if($leaveType->is_carry_forward)
                                    <x-badge type="success">Ya</x-badge>
                                @else
                                    <x-badge type="secondary">Tidak</x-badge>
                                @endif
                            </dd>
                        </div>
                        @if($leaveType->is_carry_forward)
                            <div>
                                <dt class="text-sm font-medium text-secondary-500">Maksimal Hari Carry Forward</dt>
                                <dd class="mt-1 text-secondary-900">{{ $leaveType->max_carry_forward_days }} hari</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Status --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Status</h3>
                </div>
                <div class="card-body">
                    @if($leaveType->is_active)
                        <div class="flex items-center gap-2 text-success-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span class="font-medium">Aktif</span>
                        </div>
                        <p class="text-sm text-secondary-500 mt-2">Jenis cuti ini dapat digunakan oleh karyawan.</p>
                    @else
                        <div class="flex items-center gap-2 text-danger-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span class="font-medium">Tidak Aktif</span>
                        </div>
                        <p class="text-sm text-secondary-500 mt-2">Jenis cuti ini tidak dapat digunakan.</p>
                    @endif

                    <form action="{{ route('leave-types.toggle-status', $leaveType) }}" method="POST" class="mt-4">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-ghost w-full">
                            @if($leaveType->is_active)
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                Nonaktifkan
                            @else
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Aktifkan
                            @endif
                        </button>
                    </form>
                </div>
            </div>

            {{-- Timestamps --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Informasi Sistem</h3>
                </div>
                <div class="card-body">
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Dibuat</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $leaveType->created_at->format('d M Y, H:i') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Terakhir Diupdate</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $leaveType->updated_at->format('d M Y, H:i') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Actions --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Aksi</h3>
                </div>
                <div class="card-body space-y-2">
                    <a href="{{ route('leave-types.edit', $leaveType) }}" class="btn btn-ghost w-full justify-start">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Edit Jenis Cuti
                    </a>
                    <button
                        type="button"
                        @click="$dispatch('confirm-dialog', {
                            title: 'Hapus Jenis Cuti',
                            message: 'Apakah Anda yakin ingin menghapus jenis cuti {{ $leaveType->name }}?',
                            confirmText: 'Ya, Hapus',
                            type: 'danger',
                            formAction: '{{ route('leave-types.destroy', $leaveType) }}'
                        })"
                        class="btn btn-ghost w-full justify-start text-danger-600 hover:text-danger-700"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Hapus Jenis Cuti
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection
