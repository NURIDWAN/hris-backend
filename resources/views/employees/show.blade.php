@extends('layouts.admin')

@section('title', $employee->full_name)

@section('breadcrumb')
    <a href="{{ route('employees.index') }}" class="text-slate-500 hover:text-primary-600">Karyawan</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">{{ $employee->full_name }}</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center text-primary-600 font-bold text-2xl">
                {{ strtoupper(substr($employee->first_name, 0, 1)) }}
            </div>
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">{{ $employee->full_name }}</h1>
                <div class="flex items-center gap-2 mt-1">
                    <span class="text-secondary-500">{{ $employee->position?->name ?? '-' }}</span>
                    <span class="text-secondary-300">|</span>
                    <span class="text-secondary-500">{{ $employee->department?->name ?? '-' }}</span>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('employees.index') }}" class="btn btn-ghost">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Kembali
            </a>
            @if($employee->user)
                <a href="{{ route('employees.reset-password', $employee) }}" class="btn btn-ghost">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                    Reset Password
                </a>
            @endif
            <a href="{{ route('employees.edit', $employee) }}" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Edit
            </a>
        </div>
    </div>
@endsection

@section('content')
    {{-- Generated Password Alert --}}
    @if(session('generated_password'))
        <div class="mb-6 bg-success-50 border border-success-200 rounded-xl p-4">
            <div class="flex items-start gap-3">
                <svg class="w-6 h-6 text-success-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="flex-1">
                    <h3 class="font-medium text-success-800 mb-1">Password Berhasil Di-generate</h3>
                    <p class="text-sm text-success-700 mb-3">Catat password ini dan berikan kepada karyawan. Password hanya ditampilkan sekali.</p>
                    <div class="flex items-center gap-3">
                        <code class="px-4 py-2 bg-white rounded-lg font-mono text-lg text-secondary-900 border border-success-300">{{ session('generated_password') }}</code>
                        <button type="button" onclick="copyPassword('{{ session('generated_password') }}')" class="btn btn-sm bg-success-600 hover:bg-success-700 text-white">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                            Salin
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <script>
            function copyPassword(password) {
                navigator.clipboard.writeText(password).then(function() {
                    alert('Password berhasil disalin!');
                });
            }
        </script>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Column --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Personal Information --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Informasi Pribadi</h3>
                </div>
                <div class="card-body">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">ID Karyawan</dt>
                            <dd class="mt-1 text-sm text-secondary-900 font-mono">{{ $employee->employee_id }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Nama Lengkap</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $employee->full_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Email</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $employee->email ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">No. Telepon</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $employee->phone ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Jenis Kelamin</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                @if($employee->gender === 'male')
                                    Laki-laki
                                @elseif($employee->gender === 'female')
                                    Perempuan
                                @else
                                    -
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Tanggal Lahir</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                {{ $employee->date_of_birth ? $employee->date_of_birth->format('d F Y') . ' (' . $employee->age . ' tahun)' : '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Status Pernikahan</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                @switch($employee->marital_status)
                                    @case('single')
                                        Belum Menikah
                                        @break
                                    @case('married')
                                        Menikah
                                        @break
                                    @case('divorced')
                                        Cerai
                                        @break
                                    @case('widowed')
                                        Duda/Janda
                                        @break
                                    @default
                                        -
                                @endswitch
                            </dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-secondary-500">Alamat</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                {{ $employee->address ?? '-' }}
                                @if($employee->city)
                                    <br>{{ $employee->city }}
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Employment Information --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Informasi Pekerjaan</h3>
                </div>
                <div class="card-body">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Departemen</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $employee->department?->name ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Jabatan</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $employee->position?->name ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Atasan / Manager</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                @if($employee->manager)
                                    <a href="{{ route('employees.show', $employee->manager) }}" class="text-primary-600 hover:text-primary-700 font-medium">
                                        {{ $employee->manager->full_name }}
                                    </a>
                                @else
                                    <span class="text-secondary-400">Tidak ada</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Tanggal Bergabung</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                {{ $employee->hire_date ? $employee->hire_date->format('d F Y') : '-' }}
                                @if($employee->years_of_service > 0)
                                    <span class="text-secondary-500">({{ $employee->years_of_service }} tahun)</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Status Kerja</dt>
                            <dd class="mt-1">
                                @switch($employee->employment_status)
                                    @case('permanent')
                                        <x-badge type="success">Tetap</x-badge>
                                        @break
                                    @case('contract')
                                        <x-badge type="info">Kontrak</x-badge>
                                        @break
                                    @case('probation')
                                        <x-badge type="warning">Probation</x-badge>
                                        @break
                                    @case('intern')
                                        <x-badge type="secondary">Magang</x-badge>
                                        @break
                                    @default
                                        -
                                @endswitch
                            </dd>
                        </div>
                        @if($employee->employment_status === 'contract')
                            <div>
                                <dt class="text-sm font-medium text-secondary-500">Periode Kontrak</dt>
                                <dd class="mt-1 text-sm text-secondary-900">
                                    @if($employee->contract_start_date && $employee->contract_end_date)
                                        {{ $employee->contract_start_date->format('d F Y') }} - {{ $employee->contract_end_date->format('d F Y') }}
                                        @if($employee->isContractExpiring())
                                            <br><span class="text-warning-600 font-medium">Kontrak akan berakhir dalam {{ now()->diffInDays($employee->contract_end_date) }} hari</span>
                                        @endif
                                    @else
                                        -
                                    @endif
                                </dd>
                            </div>
                        @endif
                        <div class="{{ $employee->weeklySchedules->isNotEmpty() ? 'sm:col-span-2' : '' }}">
                            <dt class="text-sm font-medium text-secondary-500">
                                @if($employee->weeklySchedules->isNotEmpty())
                                    Jadwal Mingguan
                                @else
                                    Jadwal Kerja
                                @endif
                            </dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                @if($employee->weeklySchedules->isNotEmpty())
                                    <div class="border border-secondary-200 rounded-lg overflow-hidden mt-1">
                                        @php
                                            $days = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
                                            $weeklyMap = $employee->weeklySchedules->keyBy('day_of_week');
                                        @endphp
                                        @foreach($days as $dayNum => $dayName)
                                            @php $ws = $weeklyMap->get($dayNum); @endphp
                                            <div class="flex items-center gap-4 px-4 py-2 {{ !$loop->last ? 'border-b border-secondary-100' : '' }}">
                                                <span class="text-sm font-medium text-secondary-600 w-16">{{ $dayName }}</span>
                                                @if($ws && $ws->workSchedule)
                                                    <span class="text-sm text-secondary-900">{{ $ws->workSchedule->name }}</span>
                                                    <span class="text-xs text-secondary-500">({{ \Carbon\Carbon::parse($ws->workSchedule->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($ws->workSchedule->end_time)->format('H:i') }})</span>
                                                @else
                                                    <span class="text-sm text-secondary-400">Libur</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @elseif($employee->workSchedule)
                                    {{ $employee->workSchedule->name }}
                                    <span class="text-secondary-500">({{ \Carbon\Carbon::parse($employee->workSchedule->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($employee->workSchedule->end_time)->format('H:i') }})</span>
                                @else
                                    <span class="text-secondary-400">Belum diatur</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Status</dt>
                            <dd class="mt-1">
                                @if($employee->is_active)
                                    <x-badge type="success">Aktif</x-badge>
                                @else
                                    <x-badge type="danger">Tidak Aktif</x-badge>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Gaji Pokok</dt>
                            <dd class="mt-1 text-sm text-secondary-900 font-medium">
                                @if($employee->base_salary)
                                    Rp {{ number_format($employee->base_salary, 0, ',', '.') }}
                                @else
                                    -
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Bank Information --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Informasi Bank</h3>
                </div>
                <div class="card-body">
                    <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Nama Bank</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $employee->bank_name ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Nomor Rekening</dt>
                            <dd class="mt-1 text-sm text-secondary-900 font-mono">{{ $employee->bank_account_number ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Nama Pemilik</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $employee->bank_account_name ?? '-' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        {{-- Right Column --}}
        <div class="space-y-6">
            {{-- Status Card --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Status</h3>
                </div>
                <div class="card-body space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-secondary-600">Status Aktif</span>
                        @if($employee->is_active)
                            <x-badge type="success">Aktif</x-badge>
                        @else
                            <x-badge type="danger">Tidak Aktif</x-badge>
                        @endif
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-secondary-600">Akun Terhubung</span>
                        @if($employee->user)
                            <x-badge type="success">Ya</x-badge>
                        @else
                            <x-badge type="secondary">Tidak</x-badge>
                        @endif
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-secondary-600">Status Wajah</span>
                        @if($employee->faceEmbedding && $employee->faceEmbedding->is_active)
                            <x-badge type="success">Terdaftar</x-badge>
                        @else
                            <x-badge type="secondary">Belum Terdaftar</x-badge>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Tax & BPJS --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Pajak & BPJS</h3>
                </div>
                <div class="card-body">
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">NPWP</dt>
                            <dd class="mt-1 text-sm text-secondary-900 font-mono">{{ $employee->npwp ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Status Pajak</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $employee->tax_status ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">BPJS Kesehatan</dt>
                            <dd class="mt-1 text-sm text-secondary-900 font-mono">{{ $employee->bpjs_kesehatan ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">BPJS Ketenagakerjaan</dt>
                            <dd class="mt-1 text-sm text-secondary-900 font-mono">{{ $employee->bpjs_ketenagakerjaan ?? '-' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Salary Information --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Informasi Gaji</h3>
                </div>
                <div class="card-body">
                    @if($employee->currentSalary)
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-sm font-medium text-secondary-500">Gaji Pokok</dt>
                                <dd class="mt-1 text-sm text-secondary-900 font-medium">Rp {{ number_format($employee->currentSalary->basic_salary, 0, ',', '.') }}</dd>
                            </div>

                            @php
                                $earnings = $employee->currentSalary->components->filter(fn ($c) => $c->salaryComponent && $c->salaryComponent->type === 'earning');
                                $deductions = $employee->currentSalary->components->filter(fn ($c) => $c->salaryComponent && $c->salaryComponent->type === 'deduction');
                            @endphp

                            @if($earnings->isNotEmpty())
                                <div>
                                    <dt class="text-sm font-medium text-secondary-500 mb-1">Tunjangan</dt>
                                    @foreach($earnings as $comp)
                                        <dd class="flex items-center justify-between text-sm">
                                            <span class="text-secondary-600">{{ $comp->salaryComponent->name }}</span>
                                            <span class="text-success-600">+ Rp {{ number_format($comp->amount, 0, ',', '.') }}</span>
                                        </dd>
                                    @endforeach
                                </div>
                            @endif

                            @if($deductions->isNotEmpty())
                                <div>
                                    <dt class="text-sm font-medium text-secondary-500 mb-1">Potongan</dt>
                                    @foreach($deductions as $comp)
                                        <dd class="flex items-center justify-between text-sm">
                                            <span class="text-secondary-600">{{ $comp->salaryComponent->name }}</span>
                                            <span class="text-danger-600">- Rp {{ number_format($comp->amount, 0, ',', '.') }}</span>
                                        </dd>
                                    @endforeach
                                </div>
                            @endif

                            <div class="pt-2 border-t border-secondary-200">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-semibold text-secondary-700">Take Home Pay</span>
                                    <span class="text-sm font-bold text-secondary-900">Rp {{ number_format($employee->currentSalary->getNetSalary(), 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </dl>

                        <a href="{{ route('employee-salaries.show', $employee->currentSalary) }}" class="btn btn-ghost btn-sm w-full mt-4">
                            Lihat Detail Gaji
                        </a>
                    @else
                        <div class="text-center py-4">
                            <svg class="w-10 h-10 text-secondary-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <p class="text-sm text-secondary-500 mb-3">Belum ada data gaji</p>
                            <a href="{{ route('employee-salaries.create', ['employee_id' => $employee->id]) }}" class="btn btn-primary btn-sm">Atur Gaji</a>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Actions --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Aksi</h3>
                </div>
                <div class="card-body space-y-3">
                    <a href="{{ route('employees.documents.index', $employee) }}" class="btn btn-secondary w-full">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Kelola Dokumen
                    </a>
                    <a href="{{ route('employees.edit', $employee) }}" class="btn btn-primary w-full">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Edit Data
                    </a>

                    {{-- Reset Face Button (tampil hanya jika wajah sudah terdaftar) --}}
                    @if($employee->faceEmbedding && $employee->faceEmbedding->is_active)
                    <button
                        type="button"
                        @click="$dispatch('confirm-dialog', {
                            title: 'Reset Data Wajah',
                            message: 'Apakah Anda yakin ingin menghapus data wajah {{ $employee->full_name }}? Karyawan perlu logout dan mendaftarkan wajah ulang saat absen.',
                            confirmText: 'Ya, Reset Wajah',
                            type: 'warning',
                            formAction: '{{ route('face-recognition.destroy', $employee) }}',
                            method: 'DELETE'
                        })"
                        class="btn btn-warning w-full"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Reset Face
                    </button>
                    @endif

                    <button
                        type="button"
                        @click="$dispatch('confirm-dialog', {
                            title: 'Hapus Karyawan',
                            message: 'Apakah Anda yakin ingin menghapus karyawan {{ $employee->full_name }}? Data yang sudah dihapus tidak dapat dikembalikan.',
                            confirmText: 'Ya, Hapus',
                            type: 'danger',
                            formAction: '{{ route('employees.destroy', $employee) }}'
                        })"
                        class="btn btn-danger w-full"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Hapus Karyawan
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection
