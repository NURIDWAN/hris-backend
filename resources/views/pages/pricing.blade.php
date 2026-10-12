@extends('layouts.guest')

@section('title', 'Harga - GajiPro | Paket Payroll & HR')
@section('description', 'Pilih paket GajiPro sesuai kebutuhan. Mulai gratis untuk 5 karyawan. Paket Professional mulai Rp 12.000/user/bulan.')

@section('content')
    @include('components.navbar')

    <!-- Header -->
    <section class="bg-hero-gradient pt-32 pb-20" x-data="{ yearly: true }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">
                Pilih Paket yang Sesuai untuk Bisnis Anda
            </h1>
            <p class="text-lg text-primary-200 max-w-2xl mx-auto mb-8">
                Mulai gratis, upgrade kapan saja. Harga transparan tanpa biaya tersembunyi.
            </p>

            <!-- Billing Toggle -->
            <div class="inline-flex items-center gap-4 bg-white/10 backdrop-blur-sm rounded-full p-1">
                <button @click="yearly = false; $dispatch('billing-change', { yearly: false })"
                        :class="!yearly ? 'bg-white text-primary-600' : 'text-white'"
                        class="px-6 py-2 rounded-full font-medium transition-all">
                    Bulanan
                </button>
                <button @click="yearly = true; $dispatch('billing-change', { yearly: true })"
                        :class="yearly ? 'bg-white text-primary-600' : 'text-white'"
                        class="px-6 py-2 rounded-full font-medium transition-all flex items-center gap-2">
                    Tahunan
                    <span class="bg-accent-500 text-white text-xs px-2 py-0.5 rounded-full">Hemat 20%</span>
                </button>
            </div>
        </div>
    </section>

    <!-- Pricing Cards -->
    <section class="py-20 -mt-10" x-data="{ yearly: true }" @billing-change.window="yearly = $event.detail.yearly">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-3 gap-8">
                @foreach($plans as $plan)
                    @if($plan->slug === 'professional')
                        <!-- Professional - Popular -->
                        <div class="bg-primary-600 rounded-2xl p-8 text-white relative transform md:-translate-y-4 shadow-2xl ring-4 ring-primary-300">
                            <div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-accent-500 text-white text-sm font-bold px-6 py-1.5 rounded-full shadow-lg">
                                PALING POPULER
                            </div>

                            <div class="mb-6">
                                <h3 class="text-2xl font-bold mb-2">{{ $plan->name }}</h3>
                                <p class="text-primary-200">{{ $plan->description }}</p>
                            </div>

                            <div class="mb-6">
                                <div class="flex items-baseline gap-1">
                                    <span class="text-5xl font-bold" x-text="yearly ? 'Rp {{ number_format($plan->price_yearly / 12, 0, ',', '.') }}' : 'Rp {{ number_format($plan->price_monthly, 0, ',', '.') }}'">Rp {{ number_format($plan->price_yearly / 12, 0, ',', '.') }}</span>
                                </div>
                                <p class="text-primary-200 mt-1">/user/bulan <span x-show="yearly">(tagih tahunan)</span></p>
                            </div>

                            <a href="{{ route('register') }}" class="btn btn-white w-full mb-8">Coba 14 Hari Gratis</a>

                            <div class="space-y-4">
                                <p class="font-semibold">Semua di Starter, plus:</p>
                                <ul class="space-y-3">
                                    @foreach($plan->features as $feature)
                                    <li class="flex items-start gap-3">
                                        <svg class="w-5 h-5 text-primary-200 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        <span>{{ $feature }}</span>
                                    </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @elseif($plan->slug === 'enterprise')
                        <!-- Enterprise -->
                        <div class="bg-white rounded-2xl p-8 border border-secondary-200 hover:border-primary-300 hover:shadow-xl transition-all">
                            <div class="mb-6">
                                <h3 class="text-2xl font-bold text-secondary-900 mb-2">{{ $plan->name }}</h3>
                                <p class="text-secondary-500">{{ $plan->description }}</p>
                            </div>

                            <div class="mb-6">
                                <div class="flex items-baseline gap-1">
                                    <span class="text-5xl font-bold text-secondary-900">Custom</span>
                                </div>
                                <p class="text-secondary-500 mt-1">Disesuaikan kebutuhan</p>
                            </div>

                            <a href="#kontak" class="btn btn-secondary w-full mb-8">Hubungi Sales</a>

                            <div class="space-y-4">
                                <p class="font-semibold text-secondary-900">Semua di Professional, plus:</p>
                                <ul class="space-y-3">
                                    @foreach($plan->features as $feature)
                                    <li class="flex items-start gap-3">
                                        <svg class="w-5 h-5 text-success-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        <span class="text-secondary-600">{{ $feature }}</span>
                                    </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @else
                        <!-- Starter -->
                        <div class="bg-white rounded-2xl p-8 border border-secondary-200 hover:border-primary-300 hover:shadow-xl transition-all">
                            <div class="mb-6">
                                <h3 class="text-2xl font-bold text-secondary-900 mb-2">{{ $plan->name }}</h3>
                                <p class="text-secondary-500">{{ $plan->description }}</p>
                            </div>

                            <div class="mb-6">
                                <div class="flex items-baseline gap-1">
                                    @if($plan->price_monthly == 0)
                                        <span class="text-5xl font-bold text-secondary-900">Gratis</span>
                                    @else
                                        <span class="text-5xl font-bold text-secondary-900">Rp {{ number_format($plan->price_monthly, 0, ',', '.') }}</span>
                                    @endif
                                </div>
                                <p class="text-secondary-500 mt-1">{{ $plan->price_monthly == 0 ? 'Selamanya, tanpa batas waktu' : '/user/bulan' }}</p>
                            </div>

                            <a href="{{ route('register') }}" class="btn btn-secondary w-full mb-8">Mulai Gratis</a>

                            <div class="space-y-4">
                                <p class="font-semibold text-secondary-900">Yang didapat:</p>
                                <ul class="space-y-3">
                                    @foreach($plan->features as $feature)
                                    <li class="flex items-start gap-3">
                                        <svg class="w-5 h-5 text-success-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        <span class="text-secondary-600">{{ $feature }}</span>
                                    </li>
                                    @endforeach
                                    @if($plan->slug === 'starter')
                                    <li class="flex items-start gap-3 text-secondary-400">
                                        <svg class="w-5 h-5 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                                        <span>Payroll</span>
                                    </li>
                                    <li class="flex items-start gap-3 text-secondary-400">
                                        <svg class="w-5 h-5 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                                        <span>PPh 21 & BPJS</span>
                                    </li>
                                    @endif
                                </ul>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </section>

    <!-- Feature Comparison -->
    <section class="py-20 bg-secondary-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-secondary-900 text-center mb-12">Perbandingan Fitur Lengkap</h2>

            <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-secondary-50 border-b border-secondary-200">
                                <th class="text-left py-4 px-6 font-semibold text-secondary-900">Fitur</th>
                                <th class="text-center py-4 px-6 font-semibold text-secondary-900">Starter</th>
                                <th class="text-center py-4 px-6 font-semibold text-primary-600 bg-primary-50">Professional</th>
                                <th class="text-center py-4 px-6 font-semibold text-secondary-900">Enterprise</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-secondary-100">
                            <!-- Karyawan -->
                            <tr class="bg-secondary-50">
                                <td colspan="4" class="py-3 px-6 font-semibold text-secondary-700 uppercase text-sm">Karyawan</td>
                            </tr>
                            <tr>
                                <td class="py-3 px-6 text-secondary-600">Jumlah karyawan</td>
                                <td class="py-3 px-6 text-center">5</td>
                                <td class="py-3 px-6 text-center bg-primary-50 font-medium text-primary-700">Unlimited</td>
                                <td class="py-3 px-6 text-center">Unlimited</td>
                            </tr>
                            <tr>
                                <td class="py-3 px-6 text-secondary-600">Data karyawan lengkap</td>
                                <td class="py-3 px-6 text-center"><svg class="w-5 h-5 text-success-500 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg></td>
                                <td class="py-3 px-6 text-center bg-primary-50"><svg class="w-5 h-5 text-success-500 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg></td>
                                <td class="py-3 px-6 text-center"><svg class="w-5 h-5 text-success-500 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg></td>
                            </tr>
                            <tr>
                                <td class="py-3 px-6 text-secondary-600">Import/Export data</td>
                                <td class="py-3 px-6 text-center"><svg class="w-5 h-5 text-secondary-300 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg></td>
                                <td class="py-3 px-6 text-center bg-primary-50"><svg class="w-5 h-5 text-success-500 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg></td>
                                <td class="py-3 px-6 text-center"><svg class="w-5 h-5 text-success-500 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg></td>
                            </tr>

                            <!-- Payroll -->
                            <tr class="bg-secondary-50">
                                <td colspan="4" class="py-3 px-6 font-semibold text-secondary-700 uppercase text-sm">Payroll</td>
                            </tr>
                            <tr>
                                <td class="py-3 px-6 text-secondary-600">Slip gaji</td>
                                <td class="py-3 px-6 text-center"><svg class="w-5 h-5 text-secondary-300 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg></td>
                                <td class="py-3 px-6 text-center bg-primary-50"><svg class="w-5 h-5 text-success-500 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg></td>
                                <td class="py-3 px-6 text-center"><svg class="w-5 h-5 text-success-500 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg></td>
                            </tr>
                            <tr>
                                <td class="py-3 px-6 text-secondary-600">PPh 21 otomatis</td>
                                <td class="py-3 px-6 text-center"><svg class="w-5 h-5 text-secondary-300 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg></td>
                                <td class="py-3 px-6 text-center bg-primary-50"><svg class="w-5 h-5 text-success-500 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg></td>
                                <td class="py-3 px-6 text-center"><svg class="w-5 h-5 text-success-500 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg></td>
                            </tr>
                            <tr>
                                <td class="py-3 px-6 text-secondary-600">BPJS Kesehatan & TK</td>
                                <td class="py-3 px-6 text-center"><svg class="w-5 h-5 text-secondary-300 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg></td>
                                <td class="py-3 px-6 text-center bg-primary-50"><svg class="w-5 h-5 text-success-500 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg></td>
                                <td class="py-3 px-6 text-center"><svg class="w-5 h-5 text-success-500 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg></td>
                            </tr>
                            <tr>
                                <td class="py-3 px-6 text-secondary-600">Bank transfer integration</td>
                                <td class="py-3 px-6 text-center"><svg class="w-5 h-5 text-secondary-300 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg></td>
                                <td class="py-3 px-6 text-center bg-primary-50"><svg class="w-5 h-5 text-secondary-300 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg></td>
                                <td class="py-3 px-6 text-center"><svg class="w-5 h-5 text-success-500 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg></td>
                            </tr>

                            <!-- Support -->
                            <tr class="bg-secondary-50">
                                <td colspan="4" class="py-3 px-6 font-semibold text-secondary-700 uppercase text-sm">Support</td>
                            </tr>
                            <tr>
                                <td class="py-3 px-6 text-secondary-600">Email support</td>
                                <td class="py-3 px-6 text-center"><svg class="w-5 h-5 text-success-500 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg></td>
                                <td class="py-3 px-6 text-center bg-primary-50"><svg class="w-5 h-5 text-success-500 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg></td>
                                <td class="py-3 px-6 text-center"><svg class="w-5 h-5 text-success-500 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg></td>
                            </tr>
                            <tr>
                                <td class="py-3 px-6 text-secondary-600">Chat support</td>
                                <td class="py-3 px-6 text-center"><svg class="w-5 h-5 text-secondary-300 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg></td>
                                <td class="py-3 px-6 text-center bg-primary-50"><svg class="w-5 h-5 text-success-500 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg></td>
                                <td class="py-3 px-6 text-center"><svg class="w-5 h-5 text-success-500 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg></td>
                            </tr>
                            <tr>
                                <td class="py-3 px-6 text-secondary-600">Phone support</td>
                                <td class="py-3 px-6 text-center"><svg class="w-5 h-5 text-secondary-300 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg></td>
                                <td class="py-3 px-6 text-center bg-primary-50"><svg class="w-5 h-5 text-secondary-300 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg></td>
                                <td class="py-3 px-6 text-center"><svg class="w-5 h-5 text-success-500 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg></td>
                            </tr>
                            <tr>
                                <td class="py-3 px-6 text-secondary-600">SLA</td>
                                <td class="py-3 px-6 text-center text-secondary-400">-</td>
                                <td class="py-3 px-6 text-center bg-primary-50 font-medium">99.5%</td>
                                <td class="py-3 px-6 text-center font-medium">99.9%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ -->
    <section class="py-20">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-secondary-900 text-center mb-12">Pertanyaan Seputar Harga</h2>

            <div x-data="{ active: null }" class="space-y-4">
                <div class="bg-white rounded-2xl border border-secondary-200">
                    <button @click="active = active === 1 ? null : 1" class="w-full px-6 py-5 text-left flex items-center justify-between">
                        <span class="font-semibold text-secondary-900">Bagaimana cara upgrade paket?</span>
                        <svg :class="active === 1 ? 'rotate-180' : ''" class="w-5 h-5 text-secondary-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="active === 1" x-collapse class="px-6 pb-5">
                        <p class="text-secondary-600">Anda bisa upgrade kapan saja dari dashboard. Pembayaran akan dihitung pro-rata untuk sisa periode billing Anda.</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-secondary-200">
                    <button @click="active = active === 2 ? null : 2" class="w-full px-6 py-5 text-left flex items-center justify-between">
                        <span class="font-semibold text-secondary-900">Apakah ada biaya setup?</span>
                        <svg :class="active === 2 ? 'rotate-180' : ''" class="w-5 h-5 text-secondary-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="active === 2" x-collapse class="px-6 pb-5">
                        <p class="text-secondary-600">Tidak ada biaya setup untuk paket Starter dan Professional. Untuk Enterprise, biaya setup (jika ada) akan didiskusikan sesuai kebutuhan custom.</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-secondary-200">
                    <button @click="active = active === 3 ? null : 3" class="w-full px-6 py-5 text-left flex items-center justify-between">
                        <span class="font-semibold text-secondary-900">Bagaimana jika karyawan saya bertambah?</span>
                        <svg :class="active === 3 ? 'rotate-180' : ''" class="w-5 h-5 text-secondary-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="active === 3" x-collapse class="px-6 pb-5">
                        <p class="text-secondary-600">Untuk paket Professional, Anda tinggal menambah karyawan dan tagihan akan menyesuaikan otomatis. Tidak perlu upgrade paket.</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-secondary-200">
                    <button @click="active = active === 4 ? null : 4" class="w-full px-6 py-5 text-left flex items-center justify-between">
                        <span class="font-semibold text-secondary-900">Metode pembayaran apa saja yang tersedia?</span>
                        <svg :class="active === 4 ? 'rotate-180' : ''" class="w-5 h-5 text-secondary-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="active === 4" x-collapse class="px-6 pb-5">
                        <p class="text-secondary-600">Kami menerima pembayaran via Transfer Bank, Kartu Kredit/Debit, Virtual Account, dan QRIS. Untuk Enterprise, invoice payment tersedia.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="py-20 bg-secondary-50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-bold text-secondary-900 mb-4">Masih ragu?</h2>
            <p class="text-lg text-secondary-600 mb-8">Jadwalkan demo gratis dengan tim kami untuk melihat GajiPro beraksi.</p>
            <a href="#kontak" class="btn btn-primary text-base py-4 px-8">Jadwalkan Demo Gratis</a>
        </div>
    </section>

    @include('components.footer')
@endsection

@push('scripts')
<script src="https://unpkg.com/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
@endpush
