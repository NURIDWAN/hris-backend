@extends('layouts.admin')

@section('title', 'Import Karyawan')

@section('breadcrumb')
    <a href="{{ route('employees.index') }}" class="text-slate-500 hover:text-primary-600">Karyawan</a>
    <svg class="w-4 h-4 mx-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Import</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Import Karyawan</h1>
            <p class="text-secondary-500 mt-1">Upload file Excel untuk mengimpor data karyawan secara massal.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('employees.index') }}" class="btn btn-ghost">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Kembali
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Import Status (shown when processing) --}}
        @if(session('import_id'))
            <div class="lg:col-span-2" x-data="importStatus('{{ session('import_id') }}')" x-init="startPolling()">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Status Import</h3>
                    </div>
                    <div class="card-body">
                        {{-- Processing State --}}
                        <div x-show="status === 'processing'" class="text-center py-8">
                            <svg class="animate-spin w-12 h-12 mx-auto text-primary-500 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p class="text-lg font-medium text-secondary-700 mb-2">Sedang Memproses Import...</p>
                            <p class="text-sm text-secondary-500">Proses berjalan di background. Anda bisa meninggalkan halaman ini.</p>
                            <div class="mt-4 text-sm">
                                <span class="text-success-600 font-medium" x-text="successCount + ' berhasil'"></span>
                                <span class="mx-2 text-secondary-300">|</span>
                                <span class="text-warning-600 font-medium" x-text="skipCount + ' dilewati'"></span>
                            </div>
                        </div>

                        {{-- Completed State --}}
                        <div x-show="status === 'completed'" x-cloak class="text-center py-8">
                            <svg class="w-16 h-16 mx-auto text-success-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-lg font-medium text-success-700 mb-2">Import Selesai!</p>
                            <div class="mt-2 space-y-1">
                                <p class="text-success-600"><span class="font-bold" x-text="successCount"></span> karyawan berhasil diimport</p>
                                <p class="text-warning-600" x-show="skipCount > 0"><span class="font-bold" x-text="skipCount"></span> data dilewati</p>
                            </div>
                            <div class="mt-6 flex justify-center gap-3">
                                <a href="{{ route('employees.index') }}" class="btn btn-primary">Lihat Data Karyawan</a>
                                <button @click="resetForm()" class="btn btn-secondary">Import Lagi</button>
                            </div>
                        </div>

                        {{-- Failed State --}}
                        <div x-show="status === 'failed'" x-cloak class="text-center py-8">
                            <svg class="w-16 h-16 mx-auto text-danger-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-lg font-medium text-danger-700 mb-2">Import Gagal</p>
                            <p class="text-sm text-secondary-500" x-text="errorMessage"></p>
                            <div class="mt-6">
                                <button @click="resetForm()" class="btn btn-primary">Coba Lagi</button>
                            </div>
                        </div>

                        {{-- Errors List --}}
                        <div x-show="errors.length > 0" x-cloak class="mt-6 border-t pt-4">
                            <h4 class="font-medium text-warning-700 mb-2">Peringatan:</h4>
                            <div class="max-h-40 overflow-y-auto">
                                <ul class="list-disc list-inside space-y-1 text-sm text-secondary-600">
                                    <template x-for="error in errors.slice(0, 50)" :key="error">
                                        <li x-text="error"></li>
                                    </template>
                                </ul>
                                <p x-show="errors.length > 50" class="mt-2 text-sm text-secondary-500">
                                    ... dan <span x-text="errors.length - 50"></span> peringatan lainnya
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
        {{-- Upload Form --}}
        <div class="lg:col-span-2">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Upload File</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('imports.employees.store') }}" method="POST" enctype="multipart/form-data">
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
                                    <p class="text-sm text-secondary-400">Format: .xlsx, .xls, .csv (Maks. 20MB)</p>
                                </label>
                            </div>
                            @error('file')
                                <p class="mt-2 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('imports.employees.template') }}" class="btn btn-secondary">
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
                    <div class="card-body max-h-60 overflow-y-auto">
                        <ul class="list-disc list-inside space-y-1 text-sm text-secondary-600">
                            @foreach(session('import_errors') as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>
        @endif

        {{-- Instructions --}}
        <div class="lg:col-span-1">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Panduan Import</h3>
                </div>
                <div class="card-body space-y-4 text-sm text-secondary-600">
                    <div class="bg-warning-50 text-warning-700 p-3 rounded-lg">
                        <p class="font-medium mb-1">Persiapan:</p>
                        <p class="text-xs">Pastikan data master berikut sudah diimport terlebih dahulu:</p>
                        <ul class="list-disc list-inside text-xs mt-1 space-y-0.5">
                            <li>Departemen</li>
                            <li>Jabatan</li>
                            <li>Jadwal Kerja</li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="font-medium text-secondary-900 mb-2">Kolom Wajib:</h4>
                        <ul class="space-y-1">
                            <li><span class="font-medium">NIK</span> - Nomor Induk Karyawan</li>
                            <li><span class="font-medium">Nama Depan</span> - Nama depan karyawan</li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="font-medium text-secondary-900 mb-2">Kolom Referensi:</h4>
                        <ul class="space-y-1 text-xs">
                            <li><span class="font-medium">Kode Departemen</span> - Mengacu ke kode departemen</li>
                            <li><span class="font-medium">Kode Jabatan</span> - Mengacu ke kode jabatan</li>
                            <li><span class="font-medium">Kode Jadwal</span> - Mengacu ke kode jadwal kerja</li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="font-medium text-secondary-900 mb-2">Format Data:</h4>
                        <ul class="space-y-1 text-xs">
                            <li><span class="font-medium">Tanggal</span> - YYYY-MM-DD atau DD/MM/YYYY</li>
                            <li><span class="font-medium">Jenis Kelamin</span> - Laki-laki / Perempuan</li>
                            <li><span class="font-medium">Status Karyawan</span> - Tetap / Kontrak / Magang</li>
                            <li><span class="font-medium">Gaji</span> - Angka tanpa titik/koma</li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Quick Links --}}
            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title">Import Data Master</h3>
                </div>
                <div class="card-body">
                    <p class="text-xs text-secondary-500 mb-3">Import data master terlebih dahulu:</p>
                    <div class="space-y-2">
                        <a href="{{ route('imports.departments.index') }}" class="flex items-center text-sm text-primary-600 hover:text-primary-700">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            Import Departemen
                        </a>
                        <a href="{{ route('imports.positions.index') }}" class="flex items-center text-sm text-primary-600 hover:text-primary-700">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            Import Jabatan
                        </a>
                        <a href="{{ route('imports.work-schedules.index') }}" class="flex items-center text-sm text-primary-600 hover:text-primary-700">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Import Jadwal Kerja
                        </a>
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

        function importStatus(importId) {
            return {
                importId: importId,
                status: 'processing',
                successCount: 0,
                skipCount: 0,
                errors: [],
                errorMessage: '',
                polling: null,

                async checkStatus() {
                    try {
                        const response = await fetch(`{{ url('imports/employees/status') }}/${this.importId}`);
                        if (!response.ok) {
                            throw new Error('Failed to fetch status');
                        }

                        const data = await response.json();
                        this.status = data.status;
                        this.successCount = data.success_count || 0;
                        this.skipCount = data.skip_count || 0;
                        this.errors = data.errors || [];
                        this.errorMessage = data.error_message || '';

                        if (this.status === 'completed' || this.status === 'failed') {
                            this.stopPolling();
                        }
                    } catch (error) {
                        console.error('Error checking import status:', error);
                    }
                },

                startPolling() {
                    this.checkStatus();
                    this.polling = setInterval(() => this.checkStatus(), 2000);
                },

                stopPolling() {
                    if (this.polling) {
                        clearInterval(this.polling);
                        this.polling = null;
                    }
                },

                resetForm() {
                    window.location.href = '{{ route('imports.employees.index') }}';
                }
            };
        }
    </script>
@endsection
