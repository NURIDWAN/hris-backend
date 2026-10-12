@extends('layouts.admin')

@section('title', $document->document_type_label . ' - ' . $employee->full_name)

@section('breadcrumb')
    <a href="{{ route('employees.index') }}" class="text-slate-500 hover:text-primary-600">Karyawan</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <a href="{{ route('employees.show', $employee) }}" class="text-slate-500 hover:text-primary-600">{{ $employee->full_name }}</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <a href="{{ route('employees.documents.index', $employee) }}" class="text-slate-500 hover:text-primary-600">Dokumen</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">{{ $document->document_type_label }}</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-lg flex items-center justify-center {{ str_starts_with($document->mime_type, 'image/') ? 'bg-blue-100' : 'bg-red-100' }}">
                @if(str_starts_with($document->mime_type, 'image/'))
                    <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                @else
                    <svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                @endif
            </div>
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">{{ $document->document_type_label }}</h1>
                <p class="text-secondary-500 mt-1">{{ $document->file_name }}</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('employees.documents.index', $employee) }}" class="btn btn-ghost">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Kembali
            </a>
            <a href="{{ route('employees.documents.download', [$employee, $document]) }}" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Download
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Column - Document Details --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Document Preview --}}
            @if(str_starts_with($document->mime_type, 'image/'))
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Preview Dokumen</h3>
                    </div>
                    <div class="card-body">
                        <img src="{{ Storage::disk('public')->url($document->file_path) }}" alt="{{ $document->document_type_label }}" class="max-w-full h-auto rounded-lg shadow-sm">
                    </div>
                </div>
            @elseif($document->mime_type === 'application/pdf')
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Preview PDF</h3>
                    </div>
                    <div class="card-body p-0">
                        <iframe src="{{ Storage::disk('public')->url($document->file_path) }}" class="w-full h-[600px] rounded-b-lg"></iframe>
                    </div>
                </div>
            @endif

            {{-- Document Information --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Informasi Dokumen</h3>
                </div>
                <div class="card-body">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Tipe Dokumen</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $document->document_type_label }}</dd>
                        </div>
                        @if($document->document_name)
                            <div>
                                <dt class="text-sm font-medium text-secondary-500">Nama Dokumen</dt>
                                <dd class="mt-1 text-sm text-secondary-900">{{ $document->document_name }}</dd>
                            </div>
                        @endif
                        @if($document->document_number)
                            <div>
                                <dt class="text-sm font-medium text-secondary-500">Nomor Dokumen</dt>
                                <dd class="mt-1 text-sm text-secondary-900 font-mono">{{ $document->document_number }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Nama File</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $document->file_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Ukuran File</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $document->human_file_size }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Tipe File</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $document->mime_type }}</dd>
                        </div>
                        @if($document->issue_date)
                            <div>
                                <dt class="text-sm font-medium text-secondary-500">Tanggal Terbit</dt>
                                <dd class="mt-1 text-sm text-secondary-900">{{ $document->issue_date->format('d F Y') }}</dd>
                            </div>
                        @endif
                        @if($document->expiry_date)
                            <div>
                                <dt class="text-sm font-medium text-secondary-500">Tanggal Expired</dt>
                                <dd class="mt-1 text-sm {{ $document->is_expired ? 'text-danger-600' : ($document->is_expiring_soon ? 'text-warning-600' : 'text-secondary-900') }}">
                                    {{ $document->expiry_date->format('d F Y') }}
                                    @if($document->is_expired)
                                        <span class="text-xs">(Sudah expired)</span>
                                    @elseif($document->is_expiring_soon)
                                        <span class="text-xs">(Akan expired)</span>
                                    @endif
                                </dd>
                            </div>
                        @endif
                        @if($document->notes)
                            <div class="sm:col-span-2">
                                <dt class="text-sm font-medium text-secondary-500">Catatan</dt>
                                <dd class="mt-1 text-sm text-secondary-900">{{ $document->notes }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        {{-- Right Column --}}
        <div class="space-y-6">
            {{-- Status --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Status</h3>
                </div>
                <div class="card-body space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-secondary-600">Verifikasi</span>
                        @if($document->is_verified)
                            <x-badge type="success">Terverifikasi</x-badge>
                        @else
                            <x-badge type="secondary">Belum Verifikasi</x-badge>
                        @endif
                    </div>
                    @if($document->expiry_date)
                        <div class="flex items-center justify-between">
                            <span class="text-secondary-600">Status Expired</span>
                            @if($document->is_expired)
                                <x-badge type="danger">Expired</x-badge>
                            @elseif($document->is_expiring_soon)
                                <x-badge type="warning">Akan Expired</x-badge>
                            @else
                                <x-badge type="success">Aktif</x-badge>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            {{-- Verification Info --}}
            @if($document->is_verified && $document->verifiedBy)
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Info Verifikasi</h3>
                    </div>
                    <div class="card-body">
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-sm font-medium text-secondary-500">Diverifikasi oleh</dt>
                                <dd class="mt-1 text-sm text-secondary-900">{{ $document->verifiedBy->name }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-secondary-500">Tanggal Verifikasi</dt>
                                <dd class="mt-1 text-sm text-secondary-900">{{ $document->verified_at?->format('d F Y H:i') }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            @endif

            {{-- Upload Info --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Info Upload</h3>
                </div>
                <div class="card-body">
                    <dl class="space-y-3">
                        @if($document->uploadedBy)
                            <div>
                                <dt class="text-sm font-medium text-secondary-500">Diupload oleh</dt>
                                <dd class="mt-1 text-sm text-secondary-900">{{ $document->uploadedBy->name }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Tanggal Upload</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $document->created_at->format('d F Y H:i') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Actions --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Aksi</h3>
                </div>
                <div class="card-body space-y-3">
                    <a href="{{ route('employees.documents.download', [$employee, $document]) }}" class="btn btn-primary w-full">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Download Dokumen
                    </a>
                    @if($document->is_verified)
                        <form action="{{ route('employees.documents.unverify', [$employee, $document]) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-warning w-full">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Batalkan Verifikasi
                            </button>
                        </form>
                    @else
                        <form action="{{ route('employees.documents.verify', [$employee, $document]) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-success w-full">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Verifikasi Dokumen
                            </button>
                        </form>
                    @endif
                    <button
                        type="button"
                        @click="$dispatch('confirm-dialog', {
                            title: 'Hapus Dokumen',
                            message: 'Apakah Anda yakin ingin menghapus dokumen {{ $document->document_type_label }}? Data yang sudah dihapus tidak dapat dikembalikan.',
                            confirmText: 'Ya, Hapus',
                            type: 'danger',
                            formAction: '{{ route('employees.documents.destroy', [$employee, $document]) }}'
                        })"
                        class="btn btn-danger w-full"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Hapus Dokumen
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection
