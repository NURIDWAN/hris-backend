@extends('layouts.guest')

@section('title', 'Reset Password - GajiPro')
@section('description', 'Buat password baru untuk akun GajiPro Anda.')

@section('content')
<div class="min-h-screen flex">
    <!-- Left Panel - Branding -->
    <div class="hidden lg:flex lg:w-1/2 bg-hero-gradient p-12 flex-col justify-between">
        <div>
            <a href="{{ url('/') }}" class="flex items-center gap-2">
                <div class="w-10 h-10 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-xl font-bold text-white">GajiPro</span>
            </a>
        </div>

        <div class="text-white">
            <h1 class="text-4xl font-bold mb-4">Buat Password Baru</h1>
            <p class="text-primary-200 text-lg mb-8">Masukkan password baru untuk mengamankan akun Anda.</p>

            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-primary-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </div>
                    <span class="text-primary-100">Minimal 8 karakter</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-primary-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <span class="text-primary-100">Gunakan kombinasi huruf dan angka</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-primary-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                    </div>
                    <span class="text-primary-100">Jangan gunakan password yang sama</span>
                </div>
            </div>
        </div>

        <div class="text-primary-300 text-sm">
            &copy; {{ date('Y') }} GajiPro. All rights reserved.
        </div>
    </div>

    <!-- Right Panel - Form -->
    <div class="w-full lg:w-1/2 flex items-center justify-center p-8">
        <div class="w-full max-w-md">
            <!-- Mobile Logo -->
            <div class="lg:hidden mb-8 text-center">
                <a href="{{ url('/') }}" class="inline-flex items-center gap-2">
                    <div class="w-10 h-10 bg-primary-600 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <span class="text-xl font-bold text-secondary-900">GajiPro</span>
                </a>
            </div>

            <div class="text-center mb-8">
                <h2 class="text-2xl font-bold text-secondary-900 mb-2">Reset Password</h2>
                <p class="text-secondary-500">Masukkan password baru Anda</p>
            </div>

            <!-- Reset Password Form -->
            <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
                @csrf

                <input type="hidden" name="token" value="{{ $token }}">

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-secondary-700 mb-2">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $email) }}" required readonly
                           class="input w-full bg-secondary-50 @error('email') border-danger-500 @enderror">
                    @error('email')
                        <p class="mt-1 text-sm text-danger-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- New Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-secondary-700 mb-2">Password Baru</label>
                    <input type="password" id="password" name="password" required autofocus
                           class="input w-full @error('password') border-danger-500 @enderror"
                           placeholder="Minimal 8 karakter">
                    @error('password')
                        <p class="mt-1 text-sm text-danger-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-secondary-700 mb-2">Konfirmasi Password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required
                           class="input w-full"
                           placeholder="Ulangi password baru">
                </div>

                <!-- Submit -->
                <button type="submit" class="btn btn-primary w-full py-3.5">
                    Reset Password
                </button>
            </form>

            <!-- Back to Login -->
            <p class="text-center mt-8 text-secondary-600">
                Ingat password Anda?
                <a href="{{ route('login') }}" class="text-primary-600 hover:text-primary-700 font-semibold">Kembali ke Login</a>
            </p>
        </div>
    </div>
</div>
@endsection
