@extends('layouts.admin')

@section('title', 'Reset Password - ' . $employee->full_name)

@section('breadcrumb')
    <a href="{{ route('employees.index') }}" class="text-slate-500 hover:text-primary-600">Karyawan</a>
    <svg class="w-4 h-4 mx-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <a href="{{ route('employees.show', $employee) }}" class="text-slate-500 hover:text-primary-600">{{ $employee->full_name }}</a>
    <svg class="w-4 h-4 mx-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Reset Password</span>
@endsection

@section('header')
    <div class="flex items-center gap-4">
        <a href="{{ route('employees.show', $employee) }}" class="btn btn-ghost btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Reset Password</h1>
            <p class="text-secondary-500 mt-1">Reset password untuk {{ $employee->full_name }}</p>
        </div>
    </div>
@endsection

@section('content')
    <div class="max-w-2xl">
        {{-- Employee Info Card --}}
        <div class="card mb-6">
            <div class="card-body">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center text-primary-600 font-bold text-lg">
                        {{ strtoupper(substr($employee->first_name, 0, 1)) }}
                    </div>
                    <div>
                        <h3 class="font-medium text-secondary-900">{{ $employee->full_name }}</h3>
                        <p class="text-sm text-secondary-500">{{ $employee->email }}</p>
                    </div>
                    @if($employee->user)
                        <x-badge type="success" class="ml-auto">Memiliki Akun</x-badge>
                    @else
                        <x-badge type="secondary" class="ml-auto">Tidak Ada Akun</x-badge>
                    @endif
                </div>
            </div>
        </div>

        @if(!$employee->user)
            <div class="card">
                <div class="card-body">
                    <div class="text-center py-8">
                        <svg class="w-16 h-16 mx-auto text-secondary-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <h3 class="text-lg font-medium text-secondary-900 mb-2">Tidak Ada Akun Pengguna</h3>
                        <p class="text-secondary-500 mb-4">Karyawan ini belum memiliki akun pengguna untuk login ke sistem.</p>
                        <a href="{{ route('employees.edit', $employee) }}" class="btn btn-primary">
                            Buat Akun Pengguna
                        </a>
                    </div>
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Manual Password Reset --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Reset Manual</h3>
                    </div>
                    <form action="{{ route('employees.reset-password.update', $employee) }}" method="POST">
                        @csrf
                        <div class="card-body space-y-4">
                            <p class="text-sm text-secondary-500">Masukkan password baru untuk karyawan ini.</p>

                            <div>
                                <label for="password" class="block text-sm font-medium text-secondary-700 mb-1">
                                    Password Baru <span class="text-danger-500">*</span>
                                </label>
                                <input type="password" name="password" id="password"
                                    class="input w-full @error('password') border-danger-500 @enderror"
                                    placeholder="Minimal 8 karakter"
                                    required>
                                @error('password')
                                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="password_confirmation" class="block text-sm font-medium text-secondary-700 mb-1">
                                    Konfirmasi Password <span class="text-danger-500">*</span>
                                </label>
                                <input type="password" name="password_confirmation" id="password_confirmation"
                                    class="input w-full"
                                    placeholder="Ulangi password baru"
                                    required>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary w-full">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                Reset Password
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Generate Random Password --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Generate Otomatis</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-sm text-secondary-500 mb-4">Generate password acak secara otomatis. Password akan ditampilkan sekali setelah di-generate.</p>

                        <div class="bg-warning-50 border border-warning-200 rounded-lg p-4 mb-4">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-warning-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <div class="text-sm text-warning-700">
                                    <p class="font-medium mb-1">Perhatian</p>
                                    <p>Pastikan untuk mencatat password yang di-generate dan berikan kepada karyawan.</p>
                                </div>
                            </div>
                        </div>

                        <form action="{{ route('employees.reset-password.generate', $employee) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-secondary w-full">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                Generate Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
