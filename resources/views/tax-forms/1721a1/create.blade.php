@extends('layouts.admin')

@section('title', 'Buat Bukti Potong 1721-A1')

@section('breadcrumb')
    <a href="{{ route('tax-forms.1721a1.index') }}" class="text-primary-600 hover:text-primary-700">Bukti Potong 1721-A1</a>
    <span class="text-secondary-400 mx-2">/</span>
    <span class="text-slate-700 font-medium">Buat Baru</span>
@endsection

@section('header')
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Buat Bukti Potong 1721-A1</h1>
            <p class="text-secondary-500 mt-1">Generate bukti pemotongan pajak PPh 21 untuk karyawan</p>
        </div>
    </div>
@endsection

@section('content')
    <div class="max-w-2xl">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('tax-forms.1721a1.store') }}" method="POST">
                    @csrf

                    <div class="mb-4">
                        <label for="employee_id" class="block text-sm font-medium text-secondary-700 mb-1">
                            Karyawan <span class="text-danger-500">*</span>
                        </label>
                        <select name="employee_id" id="employee_id"
                                class="input w-full @error('employee_id') border-danger-500 @enderror" required>
                            <option value="">Pilih Karyawan...</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" {{ old('employee_id') == $employee->id ? 'selected' : '' }}>
                                    {{ $employee->full_name }} ({{ $employee->employee_id }}) - {{ $employee->tax_status ?? 'TK/0' }}
                                </option>
                            @endforeach
                        </select>
                        @error('employee_id')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-6">
                        <label for="tax_year" class="block text-sm font-medium text-secondary-700 mb-1">
                            Tahun Pajak <span class="text-danger-500">*</span>
                        </label>
                        <select name="tax_year" id="tax_year"
                                class="input w-full @error('tax_year') border-danger-500 @enderror" required>
                            @for($y = now()->year; $y >= now()->year - 3; $y--)
                                <option value="{{ $y }}" {{ old('tax_year', now()->year - 1) == $y ? 'selected' : '' }}>
                                    {{ $y }}
                                </option>
                            @endfor
                        </select>
                        @error('tax_year')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-secondary-500">
                            Bukti potong akan digenerate berdasarkan data payroll tahun yang dipilih
                        </p>
                    </div>

                    <div class="bg-info-50 border border-info-200 rounded-lg p-4 mb-6">
                        <div class="flex">
                            <svg class="w-5 h-5 text-info-500 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="text-sm text-info-700">
                                <p class="font-medium">Informasi</p>
                                <p class="mt-1">Sistem akan mengambil data dari slip gaji yang sudah dibayarkan (status: paid) selama tahun pajak yang dipilih.</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="submit" class="btn btn-primary">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Generate Bukti Potong
                        </button>
                        <a href="{{ route('tax-forms.1721a1.index') }}" class="btn btn-ghost">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
