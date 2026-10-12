@extends('layouts.guest')

@section('title', 'Masuk - ' . brand_name())
@section('description', 'Masuk ke akun ' . brand_name() . ' Anda untuk mengelola payroll dan HR.')

@section('content')
<div class="min-h-screen flex">
    <!-- Left Panel - Branding (Redesigned) -->
    <div class="hidden lg:flex lg:w-1/2 bg-hero-gradient p-12 flex-col relative overflow-hidden">
        <!-- Background Decorations -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute -top-24 -left-24 w-96 h-96 bg-white/5 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-white/5 rounded-full blur-3xl"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-white/3 rounded-full blur-3xl"></div>
            <!-- Grid Pattern -->
            <div class="absolute inset-0 opacity-10" style="background-image: url('data:image/svg+xml,%3Csvg width=\"60\" height=\"60\" viewBox=\"0 0 60 60\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cg fill=\"none\" fill-rule=\"evenodd\"%3E%3Cg fill=\"%23ffffff\" fill-opacity=\"0.4\"%3E%3Cpath d=\"M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
        </div>

        <!-- Content -->
        <div class="relative z-10 flex flex-col h-full">
            <!-- Logo -->
            <div>
                <a href="{{ url('/') }}" class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <span class="text-2xl font-bold text-white">{{ brand_name() }}</span>
                </a>
            </div>

            <!-- Main Content - Centered -->
            <div class="flex-1 flex items-center justify-center">
                <div class="max-w-md">
                    @unless(single_tenant_mode())
                        <!-- Badge -->
                        <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm rounded-full px-4 py-2 mb-6">
                            <svg class="w-4 h-4 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-white/90 text-sm font-medium">Platform HR #1 di Indonesia</span>
                        </div>
                    @endunless

                    <!-- Headline -->
                    <h1 class="text-4xl lg:text-5xl font-bold text-white mb-4 leading-tight">
                        Selamat Datang<br>Kembali!
                    </h1>
                    <p class="text-primary-100 text-lg mb-10">
                        @if(single_tenant_mode())
                            Silakan masuk menggunakan akun karyawan Anda untuk mengakses sistem {{ brand_name() }}.
                        @else
                            Kelola payroll dan HR perusahaan Anda dengan mudah, aman, dan efisien.
                        @endif
                    </p>

                    @unless(single_tenant_mode())
                        <!-- Stats Grid -->
                        <div class="grid grid-cols-3 gap-4 mb-2">
                            <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-4 text-center">
                                <div class="text-3xl font-bold text-white mb-1">500+</div>
                                <div class="text-primary-200 text-sm">Perusahaan*</div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-4 text-center">
                                <div class="text-3xl font-bold text-white mb-1">50K+</div>
                                <div class="text-primary-200 text-sm">Karyawan*</div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-4 text-center">
                                <div class="text-3xl font-bold text-white mb-1">99%</div>
                                <div class="text-primary-200 text-sm">Uptime*</div>
                            </div>
                        </div>
                        <p class="text-primary-200 text-xs text-center mb-8">*Data ilustrasi</p>

                        <!-- Features List -->
                        <div class="space-y-4">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-white/10 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <span class="text-white">Dashboard real-time & analytics</span>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-white/10 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <span class="text-white">Payroll otomatis dengan PPh 21 & BPJS</span>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-white/10 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <span class="text-white">Mobile app untuk karyawan</span>
                            </div>
                        </div>
                    @endunless
                </div>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-between">
                <div class="text-primary-300 text-sm">
                    &copy; {{ date('Y') }} {{ brand_name() }}. All rights reserved.
                </div>
                <a href="https://jagoflutter.com" target="_blank" class="text-white/60 hover:text-white/80 text-sm transition-colors">
                    Powered by jagoflutter.com
                </a>
            </div>
        </div>
    </div>

    <!-- Right Panel - Form -->
    <div class="w-full lg:w-1/2 flex items-center justify-center p-6 lg:p-8 bg-white">
        <div class="w-full max-w-md">
            <!-- Mobile Logo -->
            <div class="lg:hidden mb-8 text-center">
                <a href="{{ url('/') }}" class="inline-flex items-center gap-2">
                    <div class="w-10 h-10 bg-primary-600 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <span class="text-xl font-bold text-secondary-900">{{ brand_name() }}</span>
                </a>
            </div>

            <!-- Header -->
            <div class="text-center mb-8">
                <h2 class="text-2xl lg:text-3xl font-bold text-secondary-900 mb-2">Masuk ke Akun Anda</h2>
                <p class="text-secondary-500">Masukkan email dan password untuk melanjutkan</p>
            </div>

            <!-- Login Form -->
            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-secondary-700 mb-2">Email</label>
                    <div class="relative">
                        <div class="absolute left-4 top-1/2 -translate-y-1/2 text-secondary-400 pointer-events-none z-10">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                               class="form-input w-full @error('email') border-danger-500 @enderror"
                               style="padding-left: 3rem;"
                               placeholder="nama@perusahaan.com">
                    </div>
                    @error('email')
                        <p class="mt-1 text-sm text-danger-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password -->
                <div x-data="{ show: false }">
                    <label for="password" class="block text-sm font-medium text-secondary-700 mb-2">Password</label>
                    <div class="relative">
                        <div class="absolute left-4 top-1/2 -translate-y-1/2 text-secondary-400 pointer-events-none z-10">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <input :type="show ? 'text' : 'password'" id="password" name="password" required
                               class="form-input w-full @error('password') border-danger-500 @enderror"
                               style="padding-left: 3rem; padding-right: 3rem;"
                               placeholder="Masukkan password">
                        <button type="button" @click="show = !show"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-secondary-400 hover:text-secondary-600">
                            <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg x-show="show" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-1 text-sm text-danger-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Remember & Forgot -->
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember" class="w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500">
                        <span class="text-sm text-secondary-600">Ingat saya</span>
                    </label>
                    <a href="{{ route('password.request') }}" class="text-sm text-primary-600 hover:text-primary-700 font-medium">Lupa password?</a>
                </div>

                <!-- Submit -->
                <button type="submit" class="btn btn-primary w-full py-3.5 text-base">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                    </svg>
                    Masuk
                </button>

                {{-- Google Login - Hidden until integration is ready
                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-secondary-200"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-4 bg-white text-secondary-500">atau masuk dengan</span>
                    </div>
                </div>
                <button type="button" class="w-full flex items-center justify-center gap-3 px-4 py-3.5 border-2 border-secondary-200 rounded-full hover:bg-secondary-50 hover:border-secondary-300 transition-all duration-200">
                    <svg class="w-5 h-5" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                    <span class="font-medium text-secondary-700">Google</span>
                </button>
                --}}
            </form>

            @unless(single_tenant_mode())
                <!-- Register Link -->
                <p class="text-center mt-8 text-secondary-600">
                    Belum punya akun?
                    <a href="{{ route('register') }}" class="text-primary-600 hover:text-primary-700 font-semibold">Daftar gratis</a>
                </p>
            @endunless

            <!-- Security Note -->
            <p class="mt-8 text-center text-secondary-400 text-sm">
                Data Anda tersimpan aman di server kami
            </p>

            @if(config('app.show_demo_accounts', false))
            <!-- Demo Portal Selection -->
            <div class="mt-8 border-t border-secondary-200 pt-6" x-data="{ activePortal: 'company' }">
                <div class="flex items-center gap-2 mb-4">
                    <svg class="w-5 h-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <span class="text-sm font-semibold text-secondary-700">Pilih Portal untuk Demo</span>
                </div>

                <!-- Portal Tabs -->
                <div class="grid grid-cols-3 gap-2 mb-4">
                    <button type="button" @click="activePortal = 'superadmin'"
                            :class="activePortal === 'superadmin' ? 'bg-danger-500 text-white border-danger-500' : 'bg-white text-secondary-700 border-secondary-300 hover:border-danger-400'"
                            class="px-3 py-2.5 rounded-lg border-2 text-sm font-medium transition-all">
                        <svg class="w-5 h-5 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        Super Admin
                    </button>
                    <button type="button" @click="activePortal = 'company'"
                            :class="activePortal === 'company' ? 'bg-primary-500 text-white border-primary-500' : 'bg-white text-secondary-700 border-secondary-300 hover:border-primary-400'"
                            class="px-3 py-2.5 rounded-lg border-2 text-sm font-medium transition-all">
                        <svg class="w-5 h-5 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        Company
                    </button>
                    <button type="button" @click="activePortal = 'employee'"
                            :class="activePortal === 'employee' ? 'bg-success-500 text-white border-success-500' : 'bg-white text-secondary-700 border-secondary-300 hover:border-success-400'"
                            class="px-3 py-2.5 rounded-lg border-2 text-sm font-medium transition-all">
                        <svg class="w-5 h-5 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Employee
                    </button>
                </div>

                <!-- Portal Description -->
                <div class="mb-4 p-3 rounded-lg bg-secondary-50 text-sm">
                    <template x-if="activePortal === 'superadmin'">
                        <div class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-danger-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <p class="font-medium text-secondary-900">Super Admin Portal</p>
                                <p class="text-secondary-600">Kelola semua perusahaan, billing, subscription, dan pengaturan platform.</p>
                            </div>
                        </div>
                    </template>
                    <template x-if="activePortal === 'company'">
                        <div class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-primary-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <p class="font-medium text-secondary-900">Company Portal (Admin/HR/Payroll)</p>
                                <p class="text-secondary-600">Kelola karyawan, kehadiran, cuti, payroll, dan laporan perusahaan.</p>
                            </div>
                        </div>
                    </template>
                    <template x-if="activePortal === 'employee'">
                        <div class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-success-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <p class="font-medium text-secondary-900">Employee Portal</p>
                                <p class="text-secondary-600">Lihat slip gaji, ajukan cuti/lembur, dan kelola profil karyawan.</p>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Demo Accounts based on Portal -->
                <div class="space-y-2">
                    <!-- Super Admin Portal -->
                    <template x-if="activePortal === 'superadmin'">
                        <div class="space-y-2">
                            <a href="{{ route('superadmin.login') }}"
                               class="block w-full text-left px-3 py-2 rounded-lg border border-secondary-200 hover:border-danger-300 hover:bg-danger-50 transition-colors">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <span class="text-sm font-medium text-secondary-900">Super Admin</span>
                                        <p class="text-xs text-secondary-500">Login via Super Admin Portal →</p>
                                    </div>
                                    <span class="text-xs bg-danger-100 text-danger-700 px-2 py-1 rounded-full">Super Admin</span>
                                </div>
                            </a>
                        </div>
                    </template>

                    <!-- Company Portal -->
                    <template x-if="activePortal === 'company'">
                        <div class="space-y-2">
                            <button type="button"
                                    @click="document.getElementById('email').value = 'admin@demo.gajipro.com'; document.getElementById('password').value = 'password';"
                                    class="w-full text-left px-3 py-2 rounded-lg border border-secondary-200 hover:border-primary-300 hover:bg-primary-50 transition-colors">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <span class="text-sm font-medium text-secondary-900">Admin Demo</span>
                                        <p class="text-xs text-secondary-500">admin@demo.gajipro.com</p>
                                    </div>
                                    <span class="text-xs bg-primary-100 text-primary-700 px-2 py-1 rounded-full">Admin</span>
                                </div>
                            </button>
                            <button type="button"
                                    @click="document.getElementById('email').value = 'hr@demo.gajipro.com'; document.getElementById('password').value = 'password';"
                                    class="w-full text-left px-3 py-2 rounded-lg border border-secondary-200 hover:border-success-300 hover:bg-success-50 transition-colors">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <span class="text-sm font-medium text-secondary-900">HR Manager Demo</span>
                                        <p class="text-xs text-secondary-500">hr@demo.gajipro.com</p>
                                    </div>
                                    <span class="text-xs bg-success-100 text-success-700 px-2 py-1 rounded-full">HR Manager</span>
                                </div>
                            </button>
                        </div>
                    </template>

                    <!-- Employee Portal -->
                    <template x-if="activePortal === 'employee'">
                        <div class="space-y-2">
                            <button type="button"
                                    @click="document.getElementById('email').value = 'karyawan@demo.gajipro.com'; document.getElementById('password').value = 'password';"
                                    class="w-full text-left px-3 py-2 rounded-lg border border-secondary-200 hover:border-success-300 hover:bg-success-50 transition-colors">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <span class="text-sm font-medium text-secondary-900">Karyawan Demo</span>
                                        <p class="text-xs text-secondary-500">karyawan@demo.gajipro.com</p>
                                    </div>
                                    <span class="text-xs bg-success-100 text-success-700 px-2 py-1 rounded-full">Employee</span>
                                </div>
                            </button>
                        </div>
                    </template>
                </div>

                <p class="text-xs text-secondary-400 mt-3 text-center">Password untuk semua akun: <code class="bg-secondary-100 px-1 py-0.5 rounded">password</code></p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
