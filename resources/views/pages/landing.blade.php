@extends('layouts.guest')

@section('title', 'GajiPro - Software Payroll & HRIS #1 Indonesia | Kelola Gaji Otomatis')
@section('description', 'Software payroll dan HRIS terlengkap untuk Indonesia. Otomatisasi PPh 21, BPJS, kehadiran GPS, cuti online, slip gaji digital. Gratis 14 hari. Dipercaya 500+ perusahaan.')
@section('keywords', 'software payroll indonesia, aplikasi hris, software gaji karyawan, aplikasi hr indonesia, payroll online, sistem penggajian, hitung pph 21 otomatis, bpjs ketenagakerjaan, absensi gps, aplikasi cuti karyawan, software hrd terbaik, manajemen karyawan, slip gaji digital, face recognition attendance')

@section('og_title', 'GajiPro - Software Payroll & HRIS #1 Indonesia')
@section('og_description', 'Otomatisasi penggajian, PPh 21, BPJS, kehadiran GPS & cuti dalam satu platform cloud. Gratis 14 hari!')

@push('structured-data')
{{-- Organization Schema --}}
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "Organization",
    "name": "GajiPro",
    "url": "{{ url('/') }}",
    "logo": "{{ asset('images/logo.png') }}",
    "description": "Software Payroll dan HRIS terlengkap untuk Indonesia",
    "address": {
        "@@type": "PostalAddress",
        "addressCountry": "ID"
    },
    "contactPoint": {
        "@@type": "ContactPoint",
        "telephone": "+62-xxx-xxxx-xxxx",
        "contactType": "customer service",
        "availableLanguage": ["Indonesian", "English"]
    },
    "sameAs": [
        "https://www.linkedin.com/company/gajipro",
        "https://www.instagram.com/gajipro",
        "https://twitter.com/gajipro"
    ]
}
</script>

{{-- SoftwareApplication Schema --}}
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "SoftwareApplication",
    "name": "GajiPro",
    "applicationCategory": "BusinessApplication",
    "operatingSystem": "Web, Android, iOS",
    "offers": {
        "@@type": "Offer",
        "price": "0",
        "priceCurrency": "IDR",
        "description": "14 hari gratis trial"
    },
    "aggregateRating": {
        "@@type": "AggregateRating",
        "ratingValue": "4.8",
        "ratingCount": "127",
        "bestRating": "5"
    },
    "featureList": [
        "Payroll Otomatis",
        "PPh 21 & BPJS",
        "Kehadiran GPS",
        "Face Recognition",
        "Manajemen Cuti",
        "Slip Gaji Digital",
        "Multi-Company",
        "Mobile App"
    ]
}
</script>

{{-- FAQ Schema --}}
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "FAQPage",
    "mainEntity": [
        {
            "@@type": "Question",
            "name": "Apakah data saya aman di GajiPro?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Ya, keamanan data adalah prioritas utama kami. Semua data dienkripsi dengan standar industri (AES-256) dan server kami tersertifikasi ISO 27001 dengan backup harian."
            }
        },
        {
            "@@type": "Question",
            "name": "Bagaimana cara migrasi dari sistem payroll lama?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Kami menyediakan fitur import data via Excel/CSV. Tim kami juga siap membantu proses migrasi untuk paket Professional dan Enterprise, biasanya selesai dalam 1-3 hari kerja."
            }
        },
        {
            "@@type": "Question",
            "name": "Apakah GajiPro bisa integrasi dengan sistem lain?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Ya, GajiPro menyediakan REST API lengkap untuk integrasi dengan sistem akuntansi, ERP, atau sistem internal perusahaan Anda."
            }
        },
        {
            "@@type": "Question",
            "name": "Berapa biaya menggunakan GajiPro?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Kami menyediakan paket Starter gratis selamanya untuk 5 karyawan. Paket Professional mulai dari Rp 15.000/user/bulan dengan fitur lengkap termasuk payroll, PPh 21, dan BPJS."
            }
        }
    ]
}
</script>
@endpush

@section('content')
    @include('components.navbar')

    {{-- Hero Section --}}
    <section class="bg-hero-gradient min-h-screen flex items-center pt-32">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                {{-- Left Content --}}
                <div class="text-white">
                    <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm rounded-full px-4 py-2 mb-6">
                        <span class="w-2 h-2 bg-accent-400 rounded-full animate-pulse"></span>
                        <span class="text-sm font-medium">Software Payroll & HRIS #1 Indonesia</span>
                    </div>

                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold leading-tight mb-6">
                        Kelola Gaji & HR<br>
                        <span class="text-primary-300">Jadi Lebih Mudah</span>
                    </h1>

                    <p class="text-lg md:text-xl text-primary-100 mb-8 max-w-xl">
                        Solusi lengkap penggajian, PPh 21, BPJS, kehadiran GPS, face recognition, dan manajemen cuti. Digunakan oleh <strong>500+ perusahaan</strong> di Indonesia.
                    </p>

                    <div class="flex flex-col sm:flex-row gap-4 mb-4">
                        <a href="{{ route('register') }}" class="btn btn-white text-base py-4 px-8">
                            Uji Coba Gratis
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                        <a href="{{ route('login') }}" class="btn btn-secondary border-white/30 text-white hover:bg-white/10 text-base py-4 px-8">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                            Lihat Demo
                        </a>
                    </div>
                    {{-- Mobile App Download --}}
                    <div class="mb-8 max-sm:text-center">
                        <a href="https://play.google.com/store/apps/details?id=com.jagoflutter.gajipro" target="_blank" rel="noopener" class="inline-flex items-center gap-3 bg-secondary-800/80 hover:bg-secondary-900 border border-white/20 text-white text-sm font-medium py-3 px-5 rounded-xl transition-all">
                            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M3.609 1.814L13.792 12 3.61 22.186a.996.996 0 0 1-.61-.92V2.734a1 1 0 0 1 .609-.92zm10.89 10.893l2.302 2.302-10.937 6.333 8.635-8.635zm3.199-3.198l2.807 1.626a1 1 0 0 1 0 1.73l-2.808 1.626L15.206 12l2.492-2.491zM5.864 2.658L16.8 9.99l-2.302 2.302-8.634-8.634z" fill="#34A853"/>
                                <path d="M3.609 1.814L13.792 12 3.61 22.186a.996.996 0 0 1-.61-.92V2.734a1 1 0 0 1 .609-.92z" fill="#4285F4"/>
                                <path d="M5.864 2.658L16.8 9.99l-2.302 2.302-8.634-8.634z" fill="#EA4335"/>
                                <path d="M14.499 12.707l2.302 2.302-10.937 6.333 8.635-8.635z" fill="#FBBC04"/>
                            </svg>
                            <span>
                                <span class="block text-white/70 text-xs">Tersedia di</span>
                                <span class="block font-semibold">Google Play</span>
                            </span>
                            <svg class="w-4 h-4 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        </a>
                    </div>

                    <div class="flex flex-wrap items-center gap-6 text-sm text-primary-200">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-success-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            Tanpa kartu kredit
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-success-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            Setup 5 menit
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-success-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            Support Indonesia
                        </div>
                    </div>
                </div>

                {{-- Right - Dashboard Preview --}}
                <div class="relative">
                    <div class="relative z-10 bg-white rounded-2xl shadow-2xl p-2 transform lg:rotate-1 hover:rotate-0 transition-transform duration-500">
                        <img src="{{ asset('images/dashboard.png') }}" alt="Dashboard GajiPro - Software Payroll Indonesia" class="rounded-xl w-full" loading="lazy">
                    </div>
                    {{-- Decorative elements --}}
                    <div class="absolute -top-4 -right-4 w-24 h-24 bg-accent-500/20 rounded-full blur-2xl"></div>
                    <div class="absolute -bottom-8 -left-8 w-32 h-32 bg-primary-400/20 rounded-full blur-3xl"></div>
                </div>
            </div>
        </div>
    </section>

    {{-- Stats Section --}}
    <section class="py-12 bg-white border-b border-secondary-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                <div class="text-center">
                    <div class="text-3xl md:text-4xl font-bold text-primary-600 mb-1">500+</div>
                    <div class="text-secondary-600 text-sm">Perusahaan Aktif*</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl md:text-4xl font-bold text-primary-600 mb-1">50.000+</div>
                    <div class="text-secondary-600 text-sm">Karyawan Dikelola*</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl md:text-4xl font-bold text-primary-600 mb-1">99.9%</div>
                    <div class="text-secondary-600 text-sm">Uptime Server*</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl md:text-4xl font-bold text-primary-600 mb-1">4.8/5</div>
                    <div class="text-secondary-600 text-sm">Rating Pengguna*</div>
                </div>
            </div>
            <p class="text-center text-xs text-secondary-400 mt-6">*Data ilustrasi untuk keperluan demo</p>
        </div>
    </section>

    {{-- Video Demo Section --}}
    <section class="py-16 lg:py-24 bg-white">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            {{-- Header --}}
            <div class="text-center mb-10">
                <span class="inline-flex items-center gap-2 bg-danger-100 text-danger-700 text-sm font-semibold px-4 py-2 rounded-full mb-4">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                    Video Demo
                </span>
                <h2 class="text-3xl md:text-4xl font-bold text-secondary-900 mb-4">
                    Demo Aplikasi
                </h2>
                <p class="text-lg text-secondary-600 max-w-2xl mx-auto">
                    Tonton demo lengkap fitur-fitur GajiPro dan cara penggunaannya
                </p>
            </div>

            {{-- YouTube Video Embed --}}
            <div class="relative rounded-2xl overflow-hidden shadow-2xl bg-secondary-900">
                <div class="aspect-video">
                    <iframe
                        src="https://www.youtube.com/embed/-oYQ_6og058"
                        title="GajiPro Demo - Event dan Cara Pakainya"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        referrerpolicy="strict-origin-when-cross-origin"
                        allowfullscreen
                        class="w-full h-full"
                    ></iframe>
                </div>
            </div>
        </div>
    </section>

    {{-- Mobile App Screenshots Section --}}
    <section class="py-16 lg:py-24 bg-gradient-to-b from-white to-secondary-50 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            {{-- Header --}}
            <div class="text-center mb-12">
                <span class="inline-flex items-center gap-2 bg-success-100 text-success-700 text-sm font-semibold px-4 py-2 rounded-full mb-4">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    Mobile App Preview
                </span>
                <h2 class="text-3xl md:text-4xl font-bold text-secondary-900 mb-4">
                    Coba Aplikasi Mobile Sekarang
                </h2>
                <p class="text-lg text-secondary-600 max-w-2xl mx-auto">
                    Karyawan bisa absensi dengan GPS & face recognition, ajukan cuti, lihat slip gaji — langsung dari smartphone.
                </p>
            </div>

            {{-- Scrolling Screenshots --}}
            <div class="relative">
                {{-- Gradient Overlay Left --}}
                <div class="absolute left-0 top-0 bottom-0 w-16 md:w-24 bg-gradient-to-r from-white to-transparent z-10 pointer-events-none"></div>
                {{-- Gradient Overlay Right --}}
                <div class="absolute right-0 top-0 bottom-0 w-16 md:w-24 bg-gradient-to-l from-secondary-50 to-transparent z-10 pointer-events-none"></div>

                {{-- Scrolling container --}}
                <div class="flex gap-4 md:gap-6 overflow-x-auto pb-4 scrollbar-hide snap-x snap-mandatory px-4" id="mobileScreenshots">
                    @for($i = 1; $i <= 13; $i++)
                    <div class="flex-shrink-0 snap-center">
                        <div class="w-44 md:w-52 bg-white rounded-3xl p-2 shadow-xl border border-secondary-200 transform hover:scale-105 hover:shadow-2xl transition-all duration-300">
                            <img
                                src="{{ asset('images/gajipro/mobile/' . $i . '.jpeg') }}"
                                alt="GajiPro Mobile App Screenshot {{ $i }}"
                                class="rounded-2xl w-full"
                                loading="lazy"
                            >
                        </div>
                    </div>
                    @endfor
                </div>
            </div>

            {{-- CTA --}}
            <div class="text-center mt-10">
                <a href="https://play.google.com/store/apps/details?id=com.jagoflutter.gajipro" target="_blank" rel="noopener" class="inline-flex items-center gap-3 bg-secondary-900 hover:bg-black text-white font-bold py-4 px-8 rounded-xl transition-all shadow-lg shadow-secondary-900/25 hover:-translate-y-1">
                    <svg class="w-7 h-7" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M3.609 1.814L13.792 12 3.61 22.186a.996.996 0 0 1-.61-.92V2.734a1 1 0 0 1 .609-.92z" fill="#4285F4"/>
                        <path d="M14.499 12.707l2.302 2.302-10.937 6.333 8.635-8.635z" fill="#FBBC04"/>
                        <path d="M5.864 2.658L16.8 9.99l-2.302 2.302-8.634-8.634z" fill="#EA4335"/>
                        <path d="M17.698 10.509l2.807 1.626a1 1 0 0 1 0 1.73l-2.808 1.626L15.206 12l2.492-2.491z" fill="#34A853"/>
                    </svg>
                    <span class="text-left">
                        <span class="block text-xs text-white/70 font-normal">GET IT ON</span>
                        <span class="block text-lg leading-tight">Google Play</span>
                    </span>
                </a>
                <p class="text-secondary-500 text-sm mt-3">Tersedia untuk Android</p>
            </div>
        </div>
    </section>

    {{-- Features Section --}}
    <section id="fitur" class="py-20 lg:py-32">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <span class="inline-block bg-primary-100 text-primary-700 text-sm font-semibold px-4 py-1.5 rounded-full mb-4">Fitur Lengkap</span>
                <h2 class="text-3xl md:text-4xl font-bold text-secondary-900 mb-4">
                    Semua yang Anda Butuhkan<br>dalam Satu Platform
                </h2>
                <p class="text-lg text-secondary-600">
                    Fitur lengkap untuk mengelola HR dan payroll perusahaan Anda dengan efisien. Hemat waktu hingga 80%.
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                {{-- Feature 1: Payroll --}}
                <div class="bg-white rounded-2xl p-6 border border-secondary-100 hover:border-primary-200 hover:shadow-lg transition-all group">
                    <div class="w-14 h-14 bg-accent-50 rounded-2xl flex items-center justify-center mb-5 group-hover:bg-accent-500 transition-colors">
                        <svg class="w-7 h-7 text-accent-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-secondary-900 mb-2">Payroll Otomatis</h3>
                    <p class="text-secondary-600 text-sm">Hitung gaji, PPh 21 TER, BPJS TK & Kesehatan, THR, lembur dalam hitungan menit.</p>
                </div>

                {{-- Feature 2: Kehadiran GPS --}}
                <div class="bg-white rounded-2xl p-6 border border-secondary-100 hover:border-primary-200 hover:shadow-lg transition-all group">
                    <div class="w-14 h-14 bg-success-50 rounded-2xl flex items-center justify-center mb-5 group-hover:bg-success-500 transition-colors">
                        <svg class="w-7 h-7 text-success-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-secondary-900 mb-2">Kehadiran GPS</h3>
                    <p class="text-secondary-600 text-sm">Clock in/out dengan GPS, selfie, validasi radius kantor. Support multiple lokasi.</p>
                </div>

                {{-- Feature 3: Face Recognition --}}
                <div class="bg-white rounded-2xl p-6 border border-secondary-100 hover:border-primary-200 hover:shadow-lg transition-all group">
                    <div class="w-14 h-14 bg-info-50 rounded-2xl flex items-center justify-center mb-5 group-hover:bg-info-500 transition-colors">
                        <svg class="w-7 h-7 text-info-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-secondary-900 mb-2">Face Recognition</h3>
                    <p class="text-secondary-600 text-sm">Verifikasi wajah dengan AI untuk anti-fraud. Liveness detection built-in.</p>
                </div>

                {{-- Feature 4: Manajemen Cuti --}}
                <div class="bg-white rounded-2xl p-6 border border-secondary-100 hover:border-primary-200 hover:shadow-lg transition-all group">
                    <div class="w-14 h-14 bg-warning-50 rounded-2xl flex items-center justify-center mb-5 group-hover:bg-warning-500 transition-colors">
                        <svg class="w-7 h-7 text-warning-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-secondary-900 mb-2">Manajemen Cuti</h3>
                    <p class="text-secondary-600 text-sm">Pengajuan online, approval workflow, tracking saldo cuti real-time.</p>
                </div>

                {{-- Feature 5: Employee Database --}}
                <div class="bg-white rounded-2xl p-6 border border-secondary-100 hover:border-primary-200 hover:shadow-lg transition-all group">
                    <div class="w-14 h-14 bg-primary-100 rounded-2xl flex items-center justify-center mb-5 group-hover:bg-primary-500 transition-colors">
                        <svg class="w-7 h-7 text-primary-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-secondary-900 mb-2">Employee Database</h3>
                    <p class="text-secondary-600 text-sm">Data karyawan lengkap, dokumen, kontrak, riwayat karir dalam satu tempat.</p>
                </div>

                {{-- Feature 6: Slip Gaji Digital --}}
                <div class="bg-white rounded-2xl p-6 border border-secondary-100 hover:border-primary-200 hover:shadow-lg transition-all group">
                    <div class="w-14 h-14 bg-danger-50 rounded-2xl flex items-center justify-center mb-5 group-hover:bg-danger-500 transition-colors">
                        <svg class="w-7 h-7 text-danger-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-secondary-900 mb-2">Slip Gaji Digital</h3>
                    <p class="text-secondary-600 text-sm">Generate slip gaji PDF otomatis. Karyawan akses via mobile app.</p>
                </div>

                {{-- Feature 7: Mobile App --}}
                <div class="bg-gradient-to-br from-success-50 to-emerald-50 rounded-2xl p-6 border-2 border-success-200 hover:border-success-300 hover:shadow-lg transition-all group relative overflow-hidden">
                    <div class="absolute top-2 right-2">
                        <span class="bg-success-500 text-white text-[10px] font-bold px-2 py-1 rounded-full">LIVE DI PLAY STORE</span>
                    </div>
                    <div class="w-14 h-14 bg-success-100 rounded-2xl flex items-center justify-center mb-5 group-hover:bg-success-500 transition-colors">
                        <svg class="w-7 h-7 text-success-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-secondary-900 mb-2">Mobile App</h3>
                    <p class="text-secondary-600 text-sm mb-4">Aplikasi karyawan untuk Android. Absensi GPS, face recognition, cuti, payslip dari HP.</p>
                    <a href="https://play.google.com/store/apps/details?id=com.jagoflutter.gajipro" target="_blank" rel="noopener" class="inline-flex items-center gap-2 bg-secondary-900 hover:bg-black text-white text-xs font-semibold py-2 px-4 rounded-lg transition-colors">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M3.609 1.814L13.792 12 3.61 22.186a.996.996 0 0 1-.61-.92V2.734a1 1 0 0 1 .609-.92z" fill="#4285F4"/>
                            <path d="M14.499 12.707l2.302 2.302-10.937 6.333 8.635-8.635z" fill="#FBBC04"/>
                            <path d="M5.864 2.658L16.8 9.99l-2.302 2.302-8.634-8.634z" fill="#EA4335"/>
                            <path d="M17.698 10.509l2.807 1.626a1 1 0 0 1 0 1.73l-2.808 1.626L15.206 12l2.492-2.491z" fill="#34A853"/>
                        </svg>
                        Google Play
                    </a>
                </div>

                {{-- Feature 8: Career & Performance --}}
                <div class="bg-white rounded-2xl p-6 border border-secondary-100 hover:border-primary-200 hover:shadow-lg transition-all group">
                    <div class="w-14 h-14 bg-primary-100 rounded-2xl flex items-center justify-center mb-5 group-hover:bg-primary-500 transition-colors">
                        <svg class="w-7 h-7 text-primary-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-secondary-900 mb-2">Career & Performance</h3>
                    <p class="text-secondary-600 text-sm">Jalur karir, KPI, performance review, promosi & mutasi karyawan.</p>
                </div>
            </div>

            {{-- More Features Link --}}
            <div class="text-center mt-12">
                <a href="#fitur-lengkap" class="inline-flex items-center gap-2 text-primary-600 hover:text-primary-700 font-semibold">
                    Lihat 20+ Fitur Lainnya
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </a>
            </div>
        </div>
    </section>

    {{-- JagoFlutter Academy Section --}}
    <section id="sourcecode" class="py-20 lg:py-28 bg-gradient-to-br from-secondary-900 via-secondary-800 to-primary-900 relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            {{-- Badge --}}
            <div class="text-center mb-8">
                <span class="inline-flex items-center gap-2 bg-accent-500/20 backdrop-blur-sm text-accent-300 text-sm font-semibold px-5 py-2 rounded-full border border-accent-500/30">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M12.316 3.051a1 1 0 01.633 1.265l-4 12a1 1 0 11-1.898-.632l4-12a1 1 0 011.265-.633zM5.707 6.293a1 1 0 010 1.414L3.414 10l2.293 2.293a1 1 0 11-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0zm8.586 0a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 11-1.414-1.414L16.586 10l-2.293-2.293a1 1 0 010-1.414z"/></svg>
                    JagoFlutter Academy - Batch 2
                </span>
            </div>

            {{-- Main Content --}}
            <div class="text-center max-w-4xl mx-auto mb-12">
                <h2 class="text-3xl md:text-4xl lg:text-5xl font-extrabold text-white mb-6">
                    Mau Bangun Sistem HRIS &<br>
                    <span class="text-accent-400">Payroll Seperti Ini?</span>
                </h2>
                <p class="text-lg md:text-xl text-secondary-300">
                    Join JagoFlutter Academy dan dapatkan <strong class="text-white">full source code</strong> aplikasi GajiPro — Dashboard Web sampai Mobile App dengan Face Recognition!
                </p>
            </div>

            {{-- Feature Tags --}}
            <div class="flex flex-wrap justify-center gap-3 mb-12">
                <span class="px-4 py-2 bg-sky-500/30 text-sky-200 rounded-full text-sm font-semibold border border-sky-400/40">Flutter</span>
                <span class="px-4 py-2 bg-red-500/30 text-red-200 rounded-full text-sm font-semibold border border-red-400/40">Laravel 12</span>
                <span class="px-4 py-2 bg-emerald-500/30 text-emerald-200 rounded-full text-sm font-semibold border border-emerald-400/40">Multi-tenant SaaS</span>
                <span class="px-4 py-2 bg-amber-500/30 text-amber-200 rounded-full text-sm font-semibold border border-amber-400/40">Face Recognition</span>
            </div>

            {{-- Stats Cards --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6 mb-12">
                <div class="bg-white/5 backdrop-blur rounded-2xl p-6 text-center border border-white/10">
                    <div class="text-3xl md:text-4xl font-bold text-white mb-1">16</div>
                    <div class="text-sm text-secondary-400">Sesi Live</div>
                </div>
                <div class="bg-white/5 backdrop-blur rounded-2xl p-6 text-center border border-white/10">
                    <div class="text-3xl md:text-4xl font-bold text-white mb-1">2</div>
                    <div class="text-sm text-secondary-400">Bulan</div>
                </div>
                <div class="bg-white/5 backdrop-blur rounded-2xl p-6 text-center border border-white/10">
                    <div class="text-3xl md:text-4xl font-bold text-accent-400 mb-1">SaaS</div>
                    <div class="text-sm text-secondary-400">Ready</div>
                </div>
                <div class="bg-white/5 backdrop-blur rounded-2xl p-6 text-center border border-white/10">
                    <div class="text-3xl md:text-4xl font-bold text-white mb-1">Full</div>
                    <div class="text-sm text-secondary-400">Source Code</div>
                </div>
            </div>

            {{-- What You Get Grid --}}
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4 mb-12">
                <div class="bg-white/5 backdrop-blur rounded-xl p-5 border border-white/10">
                    <div class="w-10 h-10 bg-primary-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <h4 class="font-semibold text-white mb-1">Dashboard Admin</h4>
                    <p class="text-sm text-secondary-400">Laravel 12 + Blade + Alpine.js</p>
                </div>
                <div class="bg-white/5 backdrop-blur rounded-xl p-5 border border-white/10">
                    <div class="w-10 h-10 bg-info-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-info-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    </div>
                    <h4 class="font-semibold text-white mb-1">Mobile App</h4>
                    <p class="text-sm text-secondary-400">Flutter + Riverpod</p>
                </div>
                <div class="bg-white/5 backdrop-blur rounded-xl p-5 border border-white/10">
                    <div class="w-10 h-10 bg-success-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-success-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    </div>
                    <h4 class="font-semibold text-white mb-1">16 Sesi Live Zoom</h4>
                    <p class="text-sm text-secondary-400">Tiap Selasa-Kamis malam</p>
                </div>
                <div class="bg-white/5 backdrop-blur rounded-xl p-5 border border-white/10">
                    <div class="w-10 h-10 bg-warning-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-warning-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <h4 class="font-semibold text-white mb-1">Grup Support</h4>
                    <p class="text-sm text-secondary-400">Diskusi & tanya jawab</p>
                </div>
            </div>

            {{-- CTA Button --}}
            <div class="text-center">
                <a href="https://jagoflutter.com/academy/gajipro" target="_blank" rel="noopener" class="inline-flex items-center gap-3 bg-accent-500 hover:bg-accent-600 text-white font-bold text-lg py-4 px-10 rounded-xl transition-all shadow-lg shadow-accent-500/25 hover:-translate-y-1">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"/></svg>
                    Join JagoFlutter Academy
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>
                <p class="text-secondary-400 text-sm mt-4">Kelas dimulai 7 April 2026</p>
            </div>
        </div>
    </section>

    {{-- Why Choose Us Section --}}
    <section class="py-20 lg:py-32 bg-secondary-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <div>
                    <span class="inline-block bg-primary-100 text-primary-700 text-sm font-semibold px-4 py-1.5 rounded-full mb-4">Mengapa GajiPro?</span>
                    <h2 class="text-3xl md:text-4xl font-bold text-secondary-900 mb-6">
                        Software Payroll yang Dibuat Khusus untuk Indonesia
                    </h2>
                    <p class="text-lg text-secondary-600 mb-8">
                        Berbeda dengan software payroll asing, GajiPro dirancang khusus untuk regulasi Indonesia termasuk PPh 21 TER, BPJS Kesehatan & Ketenagakerjaan, THR, dan kepatuhan pajak.
                    </p>

                    <div class="space-y-6">
                        <div class="flex gap-4">
                            <div class="flex-shrink-0 w-12 h-12 bg-success-100 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-secondary-900 mb-1">PPh 21 Tarif Efektif Rata-rata (TER)</h4>
                                <p class="text-secondary-600 text-sm">Otomatis menghitung pajak sesuai regulasi PMK terbaru. Update berkala.</p>
                            </div>
                        </div>

                        <div class="flex gap-4">
                            <div class="flex-shrink-0 w-12 h-12 bg-success-100 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-secondary-900 mb-1">BPJS Terintegrasi</h4>
                                <p class="text-secondary-600 text-sm">Hitung iuran BPJS TK (JHT, JKK, JKM, JP) & Kesehatan otomatis.</p>
                            </div>
                        </div>

                        <div class="flex gap-4">
                            <div class="flex-shrink-0 w-12 h-12 bg-success-100 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-secondary-900 mb-1">Support Bahasa Indonesia</h4>
                                <p class="text-secondary-600 text-sm">Tim support lokal yang siap membantu via chat, email, dan telepon.</p>
                            </div>
                        </div>

                        <div class="flex gap-4">
                            <div class="flex-shrink-0 w-12 h-12 bg-success-100 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-secondary-900 mb-1">Server di Indonesia</h4>
                                <p class="text-secondary-600 text-sm">Data tersimpan di data center Indonesia untuk kecepatan dan kepatuhan.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="relative">
                    <div class="bg-white rounded-2xl shadow-xl p-8">
                        <div class="flex items-center justify-between mb-6">
                            <h4 class="font-semibold text-secondary-900">Perbandingan Waktu Proses</h4>
                        </div>
                        <div class="space-y-6">
                            <div>
                                <div class="flex justify-between text-sm mb-2">
                                    <span class="text-secondary-600">Manual (Excel)</span>
                                    <span class="font-semibold text-secondary-900">2-3 hari</span>
                                </div>
                                <div class="h-3 bg-secondary-200 rounded-full">
                                    <div class="h-3 bg-danger-500 rounded-full" style="width: 100%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-sm mb-2">
                                    <span class="text-secondary-600">Software Lain</span>
                                    <span class="font-semibold text-secondary-900">4-8 jam</span>
                                </div>
                                <div class="h-3 bg-secondary-200 rounded-full">
                                    <div class="h-3 bg-warning-500 rounded-full" style="width: 50%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-sm mb-2">
                                    <span class="text-primary-600 font-semibold">GajiPro</span>
                                    <span class="font-bold text-primary-600">15 menit</span>
                                </div>
                                <div class="h-3 bg-primary-100 rounded-full">
                                    <div class="h-3 bg-primary-500 rounded-full" style="width: 10%"></div>
                                </div>
                            </div>
                        </div>
                        <p class="text-sm text-secondary-500 mt-6 text-center">*Untuk 100 karyawan</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- How It Works --}}
    <section class="py-20 lg:py-32">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <span class="inline-block bg-primary-100 text-primary-700 text-sm font-semibold px-4 py-1.5 rounded-full mb-4">Mudah Dimulai</span>
                <h2 class="text-3xl md:text-4xl font-bold text-secondary-900 mb-4">
                    Mulai dalam 3 Langkah Mudah
                </h2>
                <p class="text-lg text-secondary-600">
                    Setup hanya membutuhkan beberapa menit saja. Tanpa instalasi rumit.
                </p>
            </div>

            <div class="flex flex-col md:flex-row items-start justify-center gap-8 md:gap-4 lg:gap-8">
                {{-- Step 1 --}}
                <div class="text-center flex-1 max-w-xs">
                    <div class="w-16 h-16 bg-primary-600 text-white rounded-2xl flex items-center justify-center text-2xl font-bold mx-auto mb-6">1</div>
                    <h3 class="text-xl font-semibold text-secondary-900 mb-3">Daftar Akun</h3>
                    <p class="text-secondary-600">Buat akun gratis dalam 30 detik. Tanpa kartu kredit diperlukan.</p>
                </div>

                {{-- Arrow 1 --}}
                <div class="hidden md:flex items-center justify-center pt-6">
                    <svg class="w-8 h-8 text-secondary-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </div>

                {{-- Step 2 --}}
                <div class="text-center flex-1 max-w-xs">
                    <div class="w-16 h-16 bg-primary-600 text-white rounded-2xl flex items-center justify-center text-2xl font-bold mx-auto mb-6">2</div>
                    <h3 class="text-xl font-semibold text-secondary-900 mb-3">Setup Perusahaan</h3>
                    <p class="text-secondary-600">Atur profil perusahaan, struktur organisasi, dan pengaturan payroll.</p>
                </div>

                {{-- Arrow 2 --}}
                <div class="hidden md:flex items-center justify-center pt-6">
                    <svg class="w-8 h-8 text-secondary-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </div>

                {{-- Step 3 --}}
                <div class="text-center flex-1 max-w-xs">
                    <div class="w-16 h-16 bg-primary-600 text-white rounded-2xl flex items-center justify-center text-2xl font-bold mx-auto mb-6">3</div>
                    <h3 class="text-xl font-semibold text-secondary-900 mb-3">Undang Tim</h3>
                    <p class="text-secondary-600">Undang karyawan untuk mulai gunakan aplikasi mobile GajiPro.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Testimonials --}}
    <section class="py-20 lg:py-32 bg-secondary-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <span class="inline-block bg-primary-100 text-primary-700 text-sm font-semibold px-4 py-1.5 rounded-full mb-4">Testimoni</span>
                <h2 class="text-3xl md:text-4xl font-bold text-secondary-900 mb-4">
                    Testimoni Pengguna*
                </h2>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                {{-- Testimonial 1 --}}
                <div class="bg-white rounded-2xl p-8 shadow-sm">
                    <div class="flex items-center gap-1 mb-4">
                        @for($i = 0; $i < 5; $i++)
                        <svg class="w-5 h-5 text-warning-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        @endfor
                    </div>
                    <p class="text-secondary-600 mb-6">"Sebelumnya butuh 2 hari untuk proses gaji 150 karyawan. Sekarang dengan GajiPro cuma 30 menit! PPh 21 dan BPJS otomatis terhitung."</p>
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center text-primary-600 font-bold">RS</div>
                        <div>
                            <div class="font-semibold text-secondary-900">Rina Susanti</div>
                            <div class="text-sm text-secondary-500">HR Manager, PT Maju Bersama</div>
                        </div>
                    </div>
                </div>

                {{-- Testimonial 2 --}}
                <div class="bg-white rounded-2xl p-8 shadow-sm">
                    <div class="flex items-center gap-1 mb-4">
                        @for($i = 0; $i < 5; $i++)
                        <svg class="w-5 h-5 text-warning-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        @endfor
                    </div>
                    <p class="text-secondary-600 mb-6">"Face recognition untuk absensi sangat membantu mencegah titip absen. Sekarang data kehadiran 100% akurat dan real-time."</p>
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-success-100 rounded-full flex items-center justify-center text-success-600 font-bold">BW</div>
                        <div>
                            <div class="font-semibold text-secondary-900">Budi Wijaya</div>
                            <div class="text-sm text-secondary-500">CEO, Startup Teknologi</div>
                        </div>
                    </div>
                </div>

                {{-- Testimonial 3 --}}
                <div class="bg-white rounded-2xl p-8 shadow-sm">
                    <div class="flex items-center gap-1 mb-4">
                        @for($i = 0; $i < 5; $i++)
                        <svg class="w-5 h-5 text-warning-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        @endfor
                    </div>
                    <p class="text-secondary-600 mb-6">"Karyawan saya senang bisa akses slip gaji dan ajukan cuti dari HP. Support team juga sangat responsif dan helpful."</p>
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-accent-100 rounded-full flex items-center justify-center text-accent-600 font-bold">AD</div>
                        <div>
                            <div class="font-semibold text-secondary-900">Anita Dewi</div>
                            <div class="text-sm text-secondary-500">Finance Director, PT Retail Indonesia</div>
                        </div>
                    </div>
                </div>
            </div>
            <p class="text-center text-xs text-secondary-400 mt-8">*Testimoni ilustrasi untuk keperluan demo</p>
        </div>
    </section>

    {{-- Pricing Preview --}}
    <section id="harga" class="py-20 lg:py-32">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <span class="inline-block bg-primary-100 text-primary-700 text-sm font-semibold px-4 py-1.5 rounded-full mb-4">Harga</span>
                <h2 class="text-3xl md:text-4xl font-bold text-secondary-900 mb-4">
                    Harga Transparan,<br>Tanpa Biaya Tersembunyi
                </h2>
                <p class="text-lg text-secondary-600">
                    Pilih paket yang sesuai dengan kebutuhan bisnis Anda. Semua paket termasuk support.
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                @foreach($plans as $plan)
                    @if($plan->slug === 'professional')
                        {{-- Professional - Popular --}}
                        <div class="bg-primary-600 rounded-2xl p-8 text-white relative transform md:-translate-y-4 shadow-xl">
                            <div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-accent-500 text-white text-sm font-semibold px-4 py-1 rounded-full">
                                PALING POPULER
                            </div>
                            <h3 class="text-xl font-bold mb-2">{{ $plan->name }}</h3>
                            <p class="text-primary-200 text-sm mb-6">{{ $plan->description }}</p>
                            <div class="mb-6">
                                <span class="text-4xl font-bold">Rp {{ number_format($plan->price_monthly, 0, ',', '.') }}</span>
                                <span class="text-primary-200">/user/bulan</span>
                            </div>
                            <ul class="space-y-3 mb-8">
                                @foreach(array_slice($plan->features, 0, 5) as $feature)
                                <li class="flex items-center gap-3">
                                    <svg class="w-5 h-5 text-primary-200" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                    {{ $feature }}
                                </li>
                                @endforeach
                            </ul>
                            <a href="{{ route('register') }}" class="btn btn-white w-full">Coba 14 Hari Gratis</a>
                        </div>
                    @elseif($plan->slug === 'enterprise')
                        {{-- Enterprise --}}
                        <div class="bg-white rounded-2xl p-8 border border-secondary-200 hover:border-primary-300 transition-colors">
                            <h3 class="text-xl font-bold text-secondary-900 mb-2">{{ $plan->name }}</h3>
                            <p class="text-secondary-500 text-sm mb-6">{{ $plan->description }}</p>
                            <div class="mb-6">
                                <span class="text-4xl font-bold text-secondary-900">Custom</span>
                            </div>
                            <ul class="space-y-3 mb-8">
                                @foreach(array_slice($plan->features, 0, 5) as $feature)
                                <li class="flex items-center gap-3 text-secondary-600">
                                    <svg class="w-5 h-5 text-success-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                    {{ $feature }}
                                </li>
                                @endforeach
                            </ul>
                            <a href="#kontak" class="btn btn-secondary w-full">Hubungi Sales</a>
                        </div>
                    @else
                        {{-- Starter --}}
                        <div class="bg-white rounded-2xl p-8 border border-secondary-200 hover:border-primary-300 transition-colors">
                            <h3 class="text-xl font-bold text-secondary-900 mb-2">{{ $plan->name }}</h3>
                            <p class="text-secondary-500 text-sm mb-6">{{ $plan->description }}</p>
                            <div class="mb-6">
                                @if($plan->price_monthly == 0)
                                    <span class="text-4xl font-bold text-secondary-900">Gratis</span>
                                    <span class="text-secondary-500">/selamanya</span>
                                @else
                                    <span class="text-4xl font-bold text-secondary-900">Rp {{ number_format($plan->price_monthly, 0, ',', '.') }}</span>
                                    <span class="text-secondary-500">/user/bulan</span>
                                @endif
                            </div>
                            <ul class="space-y-3 mb-8">
                                @foreach(array_slice($plan->features, 0, 4) as $feature)
                                <li class="flex items-center gap-3 text-secondary-600">
                                    <svg class="w-5 h-5 text-success-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                    {{ $feature }}
                                </li>
                                @endforeach
                                {{-- Show payroll as disabled for starter --}}
                                @if($plan->slug === 'starter')
                                <li class="flex items-center gap-3 text-secondary-400">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                                    Payroll otomatis
                                </li>
                                @endif
                            </ul>
                            <a href="{{ route('register') }}" class="btn btn-secondary w-full">Mulai Gratis</a>
                        </div>
                    @endif
                @endforeach
            </div>

            <div class="text-center mt-8">
                <a href="{{ route('pricing') }}" class="text-primary-600 hover:text-primary-700 font-semibold inline-flex items-center gap-2">
                    Lihat Perbandingan Lengkap
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </a>
            </div>
        </div>
    </section>

    {{-- FAQ Section --}}
    <section class="py-20 lg:py-32 bg-secondary-50">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <span class="inline-block bg-primary-100 text-primary-700 text-sm font-semibold px-4 py-1.5 rounded-full mb-4">FAQ</span>
                <h2 class="text-3xl md:text-4xl font-bold text-secondary-900 mb-4">
                    Pertanyaan yang Sering Diajukan
                </h2>
            </div>

            <div x-data="{ active: null }" class="space-y-4">
                {{-- FAQ 1 --}}
                <div class="bg-white rounded-2xl border border-secondary-200">
                    <button @click="active = active === 1 ? null : 1" class="w-full px-6 py-5 text-left flex items-center justify-between">
                        <span class="font-semibold text-secondary-900">Apakah data saya aman di GajiPro?</span>
                        <svg :class="active === 1 ? 'rotate-180' : ''" class="w-5 h-5 text-secondary-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="active === 1" x-collapse class="px-6 pb-5">
                        <p class="text-secondary-600">Ya, keamanan data adalah prioritas utama kami. Semua data dienkripsi dengan standar industri (AES-256) dan kami menggunakan server dengan sertifikasi ISO 27001. Kami juga melakukan backup harian dan memiliki disaster recovery plan.</p>
                    </div>
                </div>

                {{-- FAQ 2 --}}
                <div class="bg-white rounded-2xl border border-secondary-200">
                    <button @click="active = active === 2 ? null : 2" class="w-full px-6 py-5 text-left flex items-center justify-between">
                        <span class="font-semibold text-secondary-900">Bagaimana cara migrasi dari sistem payroll lama?</span>
                        <svg :class="active === 2 ? 'rotate-180' : ''" class="w-5 h-5 text-secondary-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="active === 2" x-collapse class="px-6 pb-5">
                        <p class="text-secondary-600">Kami menyediakan fitur import data via Excel/CSV untuk data karyawan. Tim kami juga siap membantu proses migrasi untuk paket Professional dan Enterprise. Proses migrasi biasanya selesai dalam 1-3 hari kerja.</p>
                    </div>
                </div>

                {{-- FAQ 3 --}}
                <div class="bg-white rounded-2xl border border-secondary-200">
                    <button @click="active = active === 3 ? null : 3" class="w-full px-6 py-5 text-left flex items-center justify-between">
                        <span class="font-semibold text-secondary-900">Apakah GajiPro sudah sesuai regulasi pajak Indonesia?</span>
                        <svg :class="active === 3 ? 'rotate-180' : ''" class="w-5 h-5 text-secondary-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="active === 3" x-collapse class="px-6 pb-5">
                        <p class="text-secondary-600">Ya, GajiPro sudah terintegrasi dengan perhitungan PPh 21 menggunakan metode Tarif Efektif Rata-rata (TER) sesuai PMK terbaru, BPJS Kesehatan & Ketenagakerjaan, serta regulasi ketenagakerjaan Indonesia lainnya. Kami update sistem secara berkala mengikuti perubahan regulasi.</p>
                    </div>
                </div>

                {{-- FAQ 4 --}}
                <div class="bg-white rounded-2xl border border-secondary-200">
                    <button @click="active = active === 4 ? null : 4" class="w-full px-6 py-5 text-left flex items-center justify-between">
                        <span class="font-semibold text-secondary-900">Apakah ada mobile app untuk karyawan?</span>
                        <svg :class="active === 4 ? 'rotate-180' : ''" class="w-5 h-5 text-secondary-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="active === 4" x-collapse class="px-6 pb-5">
                        <p class="text-secondary-600">Ya, kami menyediakan aplikasi mobile untuk karyawan (Android & iOS). Karyawan bisa melakukan clock in/out dengan GPS dan selfie, ajukan cuti, lihat slip gaji, dan ajukan reimbursement langsung dari HP mereka.</p>
                    </div>
                </div>

                {{-- FAQ 5 --}}
                <div class="bg-white rounded-2xl border border-secondary-200">
                    <button @click="active = active === 5 ? null : 5" class="w-full px-6 py-5 text-left flex items-center justify-between">
                        <span class="font-semibold text-secondary-900">Bagaimana dukungan pelanggan GajiPro?</span>
                        <svg :class="active === 5 ? 'rotate-180' : ''" class="w-5 h-5 text-secondary-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="active === 5" x-collapse class="px-6 pb-5">
                        <p class="text-secondary-600">Semua paket mendapat dukungan email dan chat. Paket Professional mendapat prioritas response time, dan Enterprise mendapat dedicated account manager serta phone support. Tim support kami 100% berbahasa Indonesia.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA Section --}}
    <section class="py-20 lg:py-32">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-primary-600 rounded-3xl p-8 md:p-16 text-center text-white relative overflow-hidden">
                {{-- Decorative --}}
                <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2"></div>
                <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/10 rounded-full translate-y-1/2 -translate-x-1/2"></div>

                <div class="relative z-10">
                    <h2 class="text-3xl md:text-4xl font-bold mb-4">
                        Siap Tingkatkan Efisiensi HR Anda?
                    </h2>
                    <p class="text-lg text-white/80 mb-8 max-w-xl mx-auto">
                        Bergabung dengan 500+ perusahaan yang sudah menghemat waktu dan biaya dengan GajiPro.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="{{ route('register') }}" class="btn bg-white text-primary-600 hover:bg-white/90 text-base py-4 px-8 font-semibold">
                            Daftar Gratis Sekarang
                        </a>
                        <a href="#kontak" class="btn border-2 border-white/50 text-white hover:bg-white/10 text-base py-4 px-8">
                            Jadwalkan Demo
                        </a>
                    </div>
                    <p class="text-sm text-white/60 mt-6">Tanpa kartu kredit. Setup 5 menit. Batalkan kapan saja.</p>
                </div>
            </div>
        </div>
    </section>

    @include('components.footer')
@endsection

@push('scripts')
<script src="https://unpkg.com/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
@endpush
