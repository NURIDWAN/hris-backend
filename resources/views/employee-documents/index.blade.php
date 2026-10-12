@extends('layouts.admin')

@section('title', 'Dokumen Karyawan - ' . $employee->full_name)

@section('breadcrumb')
    <a href="{{ route('employees.index') }}" class="text-slate-500 hover:text-primary-600">Karyawan</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <a href="{{ route('employees.show', $employee) }}" class="text-slate-500 hover:text-primary-600">{{ $employee->full_name }}</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Dokumen</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Dokumen Karyawan</h1>
            <p class="text-secondary-500 mt-1">Kelola dokumen untuk {{ $employee->full_name }}</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('employees.show', $employee) }}" class="btn btn-ghost">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Kembali
            </a>
            <a href="{{ route('employees.documents.create', $employee) }}" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Upload Dokumen
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="card">
        <x-table>
            <x-slot name="header">
                <th>Tipe Dokumen</th>
                <th>Nama File</th>
                <th>Nomor Dokumen</th>
                <th>Tanggal Expired</th>
                <th>Status</th>
                <th class="text-right">Aksi</th>
            </x-slot>

            @forelse($documents as $document)
                <tr>
                    <td>
                        <div class="flex items-center gap-2">
                            @if(str_starts_with($document->mime_type, 'image/'))
                                <div class="w-8 h-8 bg-blue-100 rounded flex items-center justify-center">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                            @else
                                <div class="w-8 h-8 bg-red-100 rounded flex items-center justify-center">
                                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                </div>
                            @endif
                            <div>
                                <span class="font-medium text-secondary-900">{{ $document->document_type_label }}</span>
                                @if($document->document_name)
                                    <p class="text-xs text-secondary-500">{{ $document->document_name }}</p>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="text-sm">
                            <p class="text-secondary-900 truncate max-w-[200px]">{{ $document->file_name }}</p>
                            <p class="text-xs text-secondary-500">{{ $document->human_file_size }}</p>
                        </div>
                    </td>
                    <td>
                        @if($document->document_number)
                            <span class="font-mono text-sm text-secondary-700">{{ $document->document_number }}</span>
                        @else
                            <span class="text-secondary-400">-</span>
                        @endif
                    </td>
                    <td>
                        @if($document->expiry_date)
                            @if($document->is_expired)
                                <span class="text-danger-600 font-medium">{{ $document->expiry_date->format('d M Y') }}</span>
                                <p class="text-xs text-danger-500">Sudah expired</p>
                            @elseif($document->is_expiring_soon)
                                <span class="text-warning-600 font-medium">{{ $document->expiry_date->format('d M Y') }}</span>
                                <p class="text-xs text-warning-500">Akan expired</p>
                            @else
                                <span class="text-secondary-700">{{ $document->expiry_date->format('d M Y') }}</span>
                            @endif
                        @else
                            <span class="text-secondary-400">-</span>
                        @endif
                    </td>
                    <td>
                        @if($document->is_verified)
                            <x-badge type="success">Terverifikasi</x-badge>
                        @else
                            <x-badge type="secondary">Belum Verifikasi</x-badge>
                        @endif
                    </td>
                    <td>
                        <div class="flex items-center justify-end gap-1">
                            <a href="{{ route('employees.documents.show', [$employee, $document]) }}" class="p-1.5 text-secondary-400 hover:text-primary-600 hover:bg-primary-50 rounded-md transition-colors" title="Lihat Detail">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </a>
                            <a href="{{ route('employees.documents.download', [$employee, $document]) }}" class="p-1.5 text-secondary-400 hover:text-success-600 hover:bg-success-50 rounded-md transition-colors" title="Download">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            </a>
                            @if($document->is_verified)
                                <form action="{{ route('employees.documents.unverify', [$employee, $document]) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="p-1.5 text-secondary-400 hover:text-warning-600 hover:bg-warning-50 rounded-md transition-colors" title="Batalkan Verifikasi">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </button>
                                </form>
                            @else
                                <form action="{{ route('employees.documents.verify', [$employee, $document]) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="p-1.5 text-secondary-400 hover:text-success-600 hover:bg-success-50 rounded-md transition-colors" title="Verifikasi">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </button>
                                </form>
                            @endif
                            <button
                                type="button"
                                @click="$dispatch('confirm-dialog', {
                                    title: 'Hapus Dokumen',
                                    message: 'Apakah Anda yakin ingin menghapus dokumen {{ $document->document_type_label }}?',
                                    confirmText: 'Ya, Hapus',
                                    type: 'danger',
                                    formAction: '{{ route('employees.documents.destroy', [$employee, $document]) }}'
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
                            <svg class="w-12 h-12 text-secondary-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <p class="text-secondary-500">Belum ada dokumen yang diupload.</p>
                            <a href="{{ route('employees.documents.create', $employee) }}" class="btn btn-primary mt-4">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                Upload Dokumen
                            </a>
                        </div>
                    </td>
                </tr>
            @endforelse
        </x-table>
    </div>
@endsection
