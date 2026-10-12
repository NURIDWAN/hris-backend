@extends('layouts.admin')

@section('title', 'Upload Dokumen - ' . $employee->full_name)

@section('breadcrumb')
    <a href="{{ route('employees.index') }}" class="text-slate-500 hover:text-primary-600">Karyawan</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <a href="{{ route('employees.show', $employee) }}" class="text-slate-500 hover:text-primary-600">{{ $employee->full_name }}</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <a href="{{ route('employees.documents.index', $employee) }}" class="text-slate-500 hover:text-primary-600">Dokumen</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Upload</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Upload Dokumen</h1>
            <p class="text-secondary-500 mt-1">Upload dokumen baru untuk {{ $employee->full_name }}</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('employees.documents.index', $employee) }}" class="btn btn-ghost">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Kembali
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="card max-w-2xl">
        <form action="{{ route('employees.documents.store', $employee) }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="card-body space-y-6">
                {{-- Document Type --}}
                <div>
                    <label for="document_type" class="block text-sm font-medium text-secondary-700 mb-1">
                        Tipe Dokumen <span class="text-danger-500">*</span>
                    </label>
                    <select name="document_type" id="document_type" class="input w-full @error('document_type') border-danger-500 @enderror" required>
                        <option value="">Pilih tipe dokumen...</option>
                        @foreach($documentTypes as $key => $label)
                            <option value="{{ $key }}" {{ old('document_type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('document_type')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Document Number --}}
                <div>
                    <label for="document_number" class="block text-sm font-medium text-secondary-700 mb-1">
                        Nomor Dokumen
                    </label>
                    <input type="text" name="document_number" id="document_number" value="{{ old('document_number') }}" class="input w-full @error('document_number') border-danger-500 @enderror" placeholder="Contoh: 3201234567890001">
                    @error('document_number')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Document Name --}}
                <div>
                    <label for="document_name" class="block text-sm font-medium text-secondary-700 mb-1">
                        Nama Dokumen
                    </label>
                    <input type="text" name="document_name" id="document_name" value="{{ old('document_name') }}" class="input w-full @error('document_name') border-danger-500 @enderror" placeholder="Contoh: S1 Teknik Informatika">
                    <p class="mt-1 text-xs text-secondary-500">Opsional. Berguna untuk dokumen seperti ijazah atau sertifikat.</p>
                    @error('document_name')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- File Upload --}}
                <div>
                    <label for="file" class="block text-sm font-medium text-secondary-700 mb-1">
                        File Dokumen <span class="text-danger-500">*</span>
                    </label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-secondary-300 border-dashed rounded-lg hover:border-primary-400 transition-colors @error('file') border-danger-500 @enderror" x-data="{ fileName: '' }">
                        <div class="space-y-1 text-center">
                            <svg class="mx-auto h-12 w-12 text-secondary-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <div class="flex text-sm text-secondary-600">
                                <label for="file" class="relative cursor-pointer bg-white rounded-md font-medium text-primary-600 hover:text-primary-500 focus-within:outline-none">
                                    <span x-text="fileName || 'Pilih file'">Pilih file</span>
                                    <input id="file" name="file" type="file" class="sr-only" accept=".pdf,.jpg,.jpeg,.png" required @change="fileName = $event.target.files[0]?.name || ''">
                                </label>
                                <p class="pl-1" x-show="!fileName">atau drag and drop</p>
                            </div>
                            <p class="text-xs text-secondary-500">PDF, JPG, JPEG, PNG maksimal 10MB</p>
                        </div>
                    </div>
                    @error('file')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Issue Date --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="issue_date" class="block text-sm font-medium text-secondary-700 mb-1">
                            Tanggal Terbit
                        </label>
                        <input type="date" name="issue_date" id="issue_date" value="{{ old('issue_date') }}" class="input w-full @error('issue_date') border-danger-500 @enderror">
                        @error('issue_date')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Expiry Date --}}
                    <div>
                        <label for="expiry_date" class="block text-sm font-medium text-secondary-700 mb-1">
                            Tanggal Expired
                        </label>
                        <input type="date" name="expiry_date" id="expiry_date" value="{{ old('expiry_date') }}" class="input w-full @error('expiry_date') border-danger-500 @enderror">
                        @error('expiry_date')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Notes --}}
                <div>
                    <label for="notes" class="block text-sm font-medium text-secondary-700 mb-1">
                        Catatan
                    </label>
                    <textarea name="notes" id="notes" rows="3" class="input w-full @error('notes') border-danger-500 @enderror" placeholder="Catatan tambahan (opsional)">{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="card-footer flex items-center justify-end gap-3">
                <a href="{{ route('employees.documents.index', $employee) }}" class="btn btn-ghost">Batal</a>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Upload Dokumen
                </button>
            </div>
        </form>
    </div>
@endsection
