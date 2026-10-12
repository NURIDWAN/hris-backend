@extends('layouts.guest')

@section('title', 'Daftar - GajiPro')
@section('description', 'Daftar GajiPro. Tanpa kartu kredit. Setup 5 menit. Mulai kelola payroll dan HR dengan mudah.')

@php
    $plans = \App\Models\SubscriptionPlan::where('is_active', true)->orderBy('sort_order')->get();
    $gateways = \App\Models\PaymentGatewaySetting::where('is_active', true)->orderBy('sort_order')->get();
    $selectedPlanSlug = request('plan');
    $selectedBilling = request('billing', 'yearly');
@endphp

@section('content')
<div class="min-h-screen flex">
    <!-- Left Panel - Branding -->
    <div class="hidden lg:flex lg:w-1/2 bg-hero-gradient p-8 xl:p-12 flex-col relative overflow-hidden">
        {{-- Background Decorations --}}
        <div class="absolute top-0 right-0 w-96 h-96 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/2"></div>
        <div class="absolute bottom-0 left-0 w-80 h-80 bg-white/5 rounded-full translate-y-1/2 -translate-x-1/2"></div>
        <div class="absolute top-1/2 left-1/2 w-64 h-64 bg-primary-400/10 rounded-full -translate-x-1/2 -translate-y-1/2"></div>

        {{-- Logo --}}
        <div class="relative z-10">
            <a href="{{ url('/') }}" class="flex items-center gap-3">
                <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-2xl font-bold text-white">GajiPro</span>
            </a>
        </div>

        {{-- Main Content --}}
        <div class="flex-1 flex flex-col justify-center relative z-10 py-8">
            {{-- Headline --}}
            <div class="mb-10">
                <p class="text-primary-300 font-medium mb-3 tracking-wide uppercase text-sm">Platform HR & Payroll #1</p>
                <h1 class="text-4xl xl:text-5xl font-extrabold text-white leading-tight mb-5">
                    Kelola Gaji &<br>Karyawan Jadi<br><span class="text-primary-300">Lebih Mudah</span>
                </h1>
                <p class="text-primary-100 text-lg max-w-sm">
                    Solusi lengkap untuk penggajian, kehadiran, dan manajemen HR perusahaan Anda.
                </p>
            </div>

            {{-- Stats --}}
            <div class="grid grid-cols-3 gap-4 mb-2">
                <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-4 text-center">
                    <p class="text-3xl xl:text-4xl font-bold text-white">500+</p>
                    <p class="text-primary-200 text-sm mt-1">Perusahaan*</p>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-4 text-center">
                    <p class="text-3xl xl:text-4xl font-bold text-white">50K+</p>
                    <p class="text-primary-200 text-sm mt-1">Karyawan*</p>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-4 text-center">
                    <p class="text-3xl xl:text-4xl font-bold text-white">99%</p>
                    <p class="text-primary-200 text-sm mt-1">Kepuasan*</p>
                </div>
            </div>
            <p class="text-primary-200 text-xs text-center mb-8">*Data ilustrasi</p>

            {{-- Benefits --}}
            <div class="space-y-4">
                <div class="flex items-center gap-3 text-white">
                    <div class="w-6 h-6 rounded-full bg-success-500 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <span class="font-medium">Gratis 14 hari, tanpa kartu kredit</span>
                </div>
                <div class="flex items-center gap-3 text-white">
                    <div class="w-6 h-6 rounded-full bg-success-500 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <span class="font-medium">Hitung PPh 21 & BPJS otomatis</span>
                </div>
                <div class="flex items-center gap-3 text-white">
                    <div class="w-6 h-6 rounded-full bg-success-500 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <span class="font-medium">Setup dalam 5 menit, batalkan kapan saja</span>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="relative z-10 flex items-center justify-between">
            <p class="text-primary-300 text-sm">&copy; {{ date('Y') }} GajiPro</p>
            <a href="https://jagoflutter.com" target="_blank" class="text-primary-300 hover:text-primary-200 text-sm transition-colors">
                Powered by jagoflutter.com
            </a>
        </div>
    </div>

    <!-- Right Panel - Form -->
    <div class="w-full lg:w-1/2 flex items-center justify-center p-6 lg:p-8"
         x-data="{
            step: 1,
            name: '{{ old('name') }}',
            email: '{{ old('email') }}',
            company_name: '{{ old('company_name') }}',
            phone: '{{ old('phone') }}',
            password: '',
            password_confirmation: '',
            terms: false,
            showPassword: false,
            showConfirmPassword: false,
            selectedPlan: '{{ $selectedPlanSlug ?: 'trial' }}',
            billingCycle: '{{ $selectedBilling }}',
            selectedGateway: 'xendit',
            plans: {{ Js::from($plans) }},
            gateways: {{ Js::from($gateways) }},
            validateStep1() {
                return this.name && this.email && this.company_name && this.phone;
            },
            validateStep2() {
                return this.password && this.password.length >= 8 && this.password === this.password_confirmation && this.terms;
            },
            getSelectedPlan() {
                return this.plans.find(p => p.slug === this.selectedPlan) || null;
            },
            getPrice() {
                const plan = this.getSelectedPlan();
                if (!plan) return 0;
                return this.billingCycle === 'yearly' ? plan.price_yearly : plan.price_monthly;
            },
            formatPrice(amount) {
                return new Intl.NumberFormat('id-ID').format(amount);
            },
            isPaidPlan() {
                return this.selectedPlan !== 'trial' && this.getPrice() > 0;
            }
         }">
        <div class="w-full max-w-md">
            <!-- Mobile Logo -->
            <div class="lg:hidden mb-6 text-center">
                <a href="{{ url('/') }}" class="inline-flex items-center gap-2">
                    <div class="w-10 h-10 bg-primary-600 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <span class="text-xl font-bold text-secondary-900">GajiPro</span>
                </a>
            </div>

            <!-- Header -->
            <div class="text-center mb-6">
                <h2 class="text-2xl font-bold text-secondary-900 mb-2">Buat Akun Baru</h2>
                <p class="text-secondary-500" x-text="selectedPlan === 'trial' ? 'Mulai gratis 14 hari, tanpa kartu kredit' : 'Pilih paket dan mulai sekarang'"></p>
            </div>

            <!-- Step Indicator -->
            <div class="flex items-center justify-center gap-2 mb-6">
                <div class="flex items-center gap-1.5">
                    <div :class="step >= 1 ? 'bg-primary-600 text-white' : 'bg-secondary-200 text-secondary-500'"
                         class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-semibold transition-colors">
                        1
                    </div>
                    <span class="text-xs font-medium hidden sm:inline" :class="step >= 1 ? 'text-primary-600' : 'text-secondary-400'">Data</span>
                </div>
                <div class="w-6 h-0.5" :class="step >= 2 ? 'bg-primary-600' : 'bg-secondary-200'"></div>
                <div class="flex items-center gap-1.5">
                    <div :class="step >= 2 ? 'bg-primary-600 text-white' : 'bg-secondary-200 text-secondary-500'"
                         class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-semibold transition-colors">
                        2
                    </div>
                    <span class="text-xs font-medium hidden sm:inline" :class="step >= 2 ? 'text-primary-600' : 'text-secondary-400'">Password</span>
                </div>
                <div class="w-6 h-0.5" :class="step >= 3 ? 'bg-primary-600' : 'bg-secondary-200'"></div>
                <div class="flex items-center gap-1.5">
                    <div :class="step >= 3 ? 'bg-primary-600 text-white' : 'bg-secondary-200 text-secondary-500'"
                         class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-semibold transition-colors">
                        3
                    </div>
                    <span class="text-xs font-medium hidden sm:inline" :class="step >= 3 ? 'text-primary-600' : 'text-secondary-400'">Paket</span>
                </div>
            </div>

            <!-- Register Form -->
            <form method="POST" action="{{ route('register') }}">
                @csrf
                <input type="hidden" name="plan" x-model="selectedPlan">
                <input type="hidden" name="billing_cycle" x-model="billingCycle">
                <input type="hidden" name="gateway" x-model="selectedGateway">

                <!-- Step 1: Data Diri -->
                <div x-show="step === 1" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                    <div class="space-y-4">
                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-secondary-700 mb-1.5">Nama Lengkap</label>
                            <input type="text" id="name" name="name" x-model="name" required
                                   class="form-input w-full @error('name') border-danger-500 @enderror"
                                   placeholder="Masukkan nama lengkap">
                            @error('name')
                                <p class="mt-1 text-sm text-danger-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-secondary-700 mb-1.5">Email</label>
                            <input type="email" id="email" name="email" x-model="email" required
                                   class="form-input w-full @error('email') border-danger-500 @enderror"
                                   placeholder="nama@perusahaan.com">
                            @error('email')
                                <p class="mt-1 text-sm text-danger-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Company Name -->
                        <div>
                            <label for="company_name" class="block text-sm font-medium text-secondary-700 mb-1.5">Nama Perusahaan</label>
                            <input type="text" id="company_name" name="company_name" x-model="company_name" required
                                   class="form-input w-full @error('company_name') border-danger-500 @enderror"
                                   placeholder="PT Contoh Indonesia">
                            @error('company_name')
                                <p class="mt-1 text-sm text-danger-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Phone -->
                        <div>
                            <label for="phone" class="block text-sm font-medium text-secondary-700 mb-1.5">No. Telepon</label>
                            <div class="flex">
                                <div class="flex items-center justify-center px-4 border-2 border-r-0 border-secondary-200 rounded-l-xl bg-secondary-50 text-secondary-500 text-sm font-medium min-w-[60px]">
                                    +62
                                </div>
                                <input type="tel" id="phone" name="phone" x-model="phone" required
                                       class="flex-1 border-2 border-secondary-200 rounded-r-xl px-4 py-3 focus:outline-none focus:border-primary-500 focus:ring-4 focus:ring-primary-500/10 transition-all @error('phone') border-danger-500 @enderror"
                                       placeholder="812 3456 7890">
                            </div>
                            @error('phone')
                                <p class="mt-1 text-sm text-danger-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Next Button -->
                    <button type="button" @click="if(validateStep1()) step = 2"
                            class="btn btn-primary w-full py-3.5 mt-6">
                        Lanjutkan
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </button>
                </div>

                <!-- Step 2: Password -->
                <div x-show="step === 2" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                    <div class="space-y-4">
                        <!-- Summary -->
                        <div class="bg-secondary-50 rounded-xl p-4 mb-2">
                            <p class="text-sm text-secondary-500 mb-1">Mendaftar sebagai</p>
                            <p class="font-semibold text-secondary-900" x-text="name"></p>
                            <p class="text-sm text-secondary-600" x-text="email"></p>
                        </div>

                        <!-- Password -->
                        <div>
                            <label for="password" class="block text-sm font-medium text-secondary-700 mb-1.5">Password</label>
                            <div class="relative">
                                <input :type="showPassword ? 'text' : 'password'" id="password" name="password" x-model="password" required
                                       class="form-input w-full pr-12 @error('password') border-danger-500 @enderror"
                                       placeholder="Minimal 8 karakter">
                                <button type="button" @click="showPassword = !showPassword"
                                        class="absolute right-4 top-1/2 -translate-y-1/2 text-secondary-400 hover:text-secondary-600">
                                    <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <svg x-show="showPassword" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                </button>
                            </div>
                            @error('password')
                                <p class="mt-1 text-sm text-danger-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Confirm Password -->
                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-secondary-700 mb-1.5">Konfirmasi Password</label>
                            <div class="relative">
                                <input :type="showConfirmPassword ? 'text' : 'password'" id="password_confirmation" name="password_confirmation" x-model="password_confirmation" required
                                       class="form-input w-full pr-12"
                                       placeholder="Ulangi password">
                                <button type="button" @click="showConfirmPassword = !showConfirmPassword"
                                        class="absolute right-4 top-1/2 -translate-y-1/2 text-secondary-400 hover:text-secondary-600">
                                    <svg x-show="!showConfirmPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <svg x-show="showConfirmPassword" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                </button>
                            </div>
                        </div>

                        <!-- Terms -->
                        <div class="flex items-start gap-3">
                            <input type="checkbox" id="terms" name="terms" x-model="terms" required
                                   class="w-4 h-4 mt-1 text-primary-600 border-secondary-300 rounded focus:ring-primary-500">
                            <label for="terms" class="text-sm text-secondary-600">
                                Saya setuju dengan
                                <a href="{{ route('terms') }}" target="_blank" class="text-primary-600 hover:underline">Syarat & Ketentuan</a>
                                dan
                                <a href="{{ route('privacy') }}" target="_blank" class="text-primary-600 hover:underline">Kebijakan Privasi</a>
                            </label>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-3 mt-6">
                        <button type="button" @click="step = 1"
                                class="btn btn-secondary flex-1 py-3.5">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18"/></svg>
                            Kembali
                        </button>
                        <button type="button" @click="if(validateStep2()) step = 3"
                                class="btn btn-primary flex-[2] py-3.5">
                            Lanjutkan
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Step 3: Pilih Paket -->
                <div x-show="step === 3" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                    <div class="space-y-4">
                        <!-- Plan Selection -->
                        <div>
                            <label class="block text-sm font-medium text-secondary-700 mb-2">Pilih Paket</label>
                            <div class="space-y-2">
                                <!-- Trial Option -->
                                <label class="flex items-center p-4 border-2 rounded-xl cursor-pointer transition-all"
                                       :class="selectedPlan === 'trial' ? 'border-primary-500 bg-primary-50' : 'border-secondary-200 hover:border-secondary-300'">
                                    <input type="radio" name="plan_option" value="trial" x-model="selectedPlan" class="sr-only">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="font-semibold text-secondary-900">Trial Gratis</span>
                                            <span class="text-xs bg-success-100 text-success-700 px-2 py-0.5 rounded-full">14 hari</span>
                                        </div>
                                        <p class="text-sm text-secondary-500 mt-0.5">Coba semua fitur tanpa kartu kredit</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-bold text-secondary-900">Rp 0</p>
                                    </div>
                                </label>

                                <!-- Paid Plans -->
                                <template x-for="plan in plans.filter(p => p.price_monthly > 0 && p.slug !== 'enterprise')" :key="plan.id">
                                    <label class="flex items-center p-4 border-2 rounded-xl cursor-pointer transition-all"
                                           :class="selectedPlan === plan.slug ? 'border-primary-500 bg-primary-50' : 'border-secondary-200 hover:border-secondary-300'">
                                        <input type="radio" name="plan_option" :value="plan.slug" x-model="selectedPlan" class="sr-only">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2">
                                                <span class="font-semibold text-secondary-900" x-text="plan.name"></span>
                                                <span x-show="plan.slug === 'professional'" class="text-xs bg-primary-100 text-primary-700 px-2 py-0.5 rounded-full">Populer</span>
                                            </div>
                                            <p class="text-sm text-secondary-500 mt-0.5" x-text="plan.description"></p>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-bold text-secondary-900">
                                                Rp <span x-text="formatPrice(billingCycle === 'yearly' ? plan.price_yearly : plan.price_monthly)"></span>
                                            </p>
                                            <p class="text-xs text-secondary-500" x-text="billingCycle === 'yearly' ? '/tahun' : '/bulan'"></p>
                                        </div>
                                    </label>
                                </template>
                            </div>
                        </div>

                        <!-- Billing Cycle (only for paid plans) -->
                        <div x-show="isPaidPlan()" x-cloak>
                            <label class="block text-sm font-medium text-secondary-700 mb-2">Periode Pembayaran</label>
                            <div class="flex gap-2">
                                <label class="flex-1 flex items-center justify-center p-3 border-2 rounded-xl cursor-pointer transition-all"
                                       :class="billingCycle === 'monthly' ? 'border-primary-500 bg-primary-50' : 'border-secondary-200'">
                                    <input type="radio" name="billing_option" value="monthly" x-model="billingCycle" class="sr-only">
                                    <span class="font-medium" :class="billingCycle === 'monthly' ? 'text-primary-700' : 'text-secondary-700'">Bulanan</span>
                                </label>
                                <label class="flex-1 flex items-center justify-center gap-2 p-3 border-2 rounded-xl cursor-pointer transition-all"
                                       :class="billingCycle === 'yearly' ? 'border-primary-500 bg-primary-50' : 'border-secondary-200'">
                                    <input type="radio" name="billing_option" value="yearly" x-model="billingCycle" class="sr-only">
                                    <span class="font-medium" :class="billingCycle === 'yearly' ? 'text-primary-700' : 'text-secondary-700'">Tahunan</span>
                                    <span class="text-xs bg-accent-500 text-white px-1.5 py-0.5 rounded">-20%</span>
                                </label>
                            </div>
                        </div>

                        <!-- Payment Gateway (only for paid plans) -->
                        <div x-show="isPaidPlan()" x-cloak>
                            <label class="block text-sm font-medium text-secondary-700 mb-2">Metode Pembayaran</label>
                            <div class="space-y-2">
                                <template x-for="gateway in gateways" :key="gateway.id">
                                    <label class="flex items-center p-3 border-2 rounded-xl cursor-pointer transition-all"
                                           :class="selectedGateway === gateway.gateway ? 'border-primary-500 bg-primary-50' : 'border-secondary-200'">
                                        <input type="radio" name="gateway_option" :value="gateway.gateway" x-model="selectedGateway" class="sr-only">
                                        <div class="flex items-center gap-3 flex-1">
                                            <div class="w-8 h-8 bg-secondary-100 rounded-lg flex items-center justify-center">
                                                <template x-if="gateway.gateway === 'xendit'">
                                                    <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                                </template>
                                                <template x-if="gateway.gateway === 'midtrans'">
                                                    <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                                                </template>
                                                <template x-if="gateway.gateway === 'manual'">
                                                    <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                                </template>
                                            </div>
                                            <div>
                                                <span class="font-medium text-secondary-900" x-text="gateway.name"></span>
                                                <p class="text-xs text-secondary-500" x-text="gateway.gateway === 'manual' ? 'Transfer bank manual' : 'VA, QRIS, E-Wallet, Kartu Kredit'"></p>
                                            </div>
                                        </div>
                                        <div x-show="selectedGateway === gateway.gateway" class="text-primary-600">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        </div>
                                    </label>
                                </template>
                            </div>
                        </div>

                        <!-- Order Summary (only for paid plans) -->
                        <div x-show="isPaidPlan()" x-cloak class="bg-secondary-50 rounded-xl p-4">
                            <h4 class="font-semibold text-secondary-900 mb-3">Ringkasan Pesanan</h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-secondary-600">Paket <span x-text="getSelectedPlan()?.name"></span></span>
                                    <span class="text-secondary-900">Rp <span x-text="formatPrice(getPrice())"></span></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-secondary-600">Periode</span>
                                    <span class="text-secondary-900" x-text="billingCycle === 'yearly' ? '1 Tahun' : '1 Bulan'"></span>
                                </div>
                                <div class="border-t border-secondary-200 pt-2 mt-2">
                                    <div class="flex justify-between font-semibold">
                                        <span class="text-secondary-900">Total</span>
                                        <span class="text-primary-600">Rp <span x-text="formatPrice(getPrice())"></span></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-3 mt-6">
                        <button type="button" @click="step = 2"
                                class="btn btn-secondary flex-1 py-3.5">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18"/></svg>
                            Kembali
                        </button>
                        <button type="submit" class="btn btn-primary flex-[2] py-3.5">
                            <span x-text="isPaidPlan() ? 'Daftar & Bayar' : 'Daftar Sekarang'"></span>
                        </button>
                    </div>
                </div>
            </form>

            <!-- Login Link -->
            <p class="text-center mt-6 text-secondary-600">
                Sudah punya akun?
                <a href="{{ route('login') }}" class="text-primary-600 hover:text-primary-700 font-semibold">Masuk di sini</a>
            </p>
        </div>
    </div>
</div>
@endsection
