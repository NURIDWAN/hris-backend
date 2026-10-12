@extends('layouts.admin')

@section('title', 'Import Jabatan')

@section('breadcrumb')
    <a href="{{ route('positions.index') }}" class="text-slate-500 hover:text-primary-600">Jabatan</a>
    <svg class="w-4 h-4 mx-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Import</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Import Jabatan</h1>
            <p class="text-secondary-500 mt-1">Upload file Excel untuk mengimpor data jabatan secara massal.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('positions.index') }}" class="btn btn-ghost">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Kembali
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Upload Form --}}
        <div class="lg:col-span-2">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Upload File</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('imports.positions.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-secondary-700 mb-2">File Excel</label>
                            <div class="border-2 border-dashed border-secondary-300 rounded-lg p-8 text-center hover:border-primary-400 transition-colors">
                                <input type="file" name="file" id="file" accept=".xlsx,.xls,.csv" class="hidden" onchange="updateFileName(this)">
                                <label for="file" class="cursor-pointer">
                                    <svg class="w-12 h-12 mx-auto text-secondary-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                    </svg>
                                    <p class="text-secondary-600 mb-1" id="file-name">Klik untuk memilih file atau drag & drop</p>
                                    <p class="text-sm text-secondary-400">Format: .xlsx, .xls, .csv (Maks. 5MB)</p>
                                </label>
                            </div>
                            @error('file')
                                <p class="mt-2 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('imports.positions.template') }}" class="btn btn-secondary">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                Download Template
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                Import Data
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            @if(session('import_errors'))
                <div class="card mt-6">
                    <div class="card-header">
                        <h3 class="card-title text-warning-600">Peringatan Import</h3>
                    </div>
                    <div class="card-body">
                        <ul class="list-disc list-inside space-y-1 text-sm text-secondary-600">
                            @foreach(session('import_errors') as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>

        {{-- Instructions --}}
        <div class="lg:col-span-1">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Panduan Import</h3>
                </div>
                <div class="card-body space-y-4 text-sm text-secondary-600">
                    <div>
                        <h4 class="font-medium text-secondary-900 mb-2">Format Kolom:</h4>
                        <ul class="space-y-2">
                            <li><span class="font-medium">Nama</span> - Nama jabatan (wajib)</li>
                            <li><span class="font-medium">Kode</span> - Kode unik jabatan (wajib)</li>
                            <li><span class="font-medium">Kode Departemen</span> - Kode departemen terkait</li>
                            <li><span class="font-medium">Level</span> - Level jabatan (1-10)</li>
                            <li><span class="font-medium">Gaji Pokok</span> - Nominal gaji pokok</li>
                            <li><span class="font-medium">Deskripsi</span> - Deskripsi jabatan</li>
                            <li><span class="font-medium">Aktif</span> - Ya/Tidak</li>
                        </ul>
                    </div>

                    <div class="bg-warning-50 text-warning-700 p-3 rounded-lg">
                        <p class="font-medium mb-1">Penting:</p>
                        <p class="text-xs">Pastikan departemen sudah diimport terlebih dahulu sebelum mengimport jabatan.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function updateFileName(input) {
            const fileName = input.files[0]?.name || 'Klik untuk memilih file atau drag & drop';
            document.getElementById('file-name').textContent = fileName;
        }
    </script>
@endsection
