<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Primary SEO --}}
    <title>@yield('title', 'GajiPro - Software Payroll & HRIS #1 Indonesia | Kelola Gaji Otomatis')</title>
    <meta name="description" content="@yield('description', 'Software payroll dan HRIS terlengkap untuk Indonesia. Otomatisasi gaji, PPh 21, BPJS, kehadiran GPS, cuti online. Gratis 14 hari. Dipercaya 500+ perusahaan.')">
    <meta name="keywords" content="@yield('keywords', 'software payroll indonesia, aplikasi hris, software gaji karyawan, aplikasi hr indonesia, payroll online, sistem penggajian, hitung pph 21, bpjs ketenagakerjaan, absensi gps, aplikasi cuti karyawan, software hrd, manajemen karyawan')">
    <meta name="author" content="GajiPro">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph / Facebook --}}
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="@yield('og_title', 'GajiPro - Software Payroll & HRIS #1 Indonesia')">
    <meta property="og:description" content="@yield('og_description', 'Kelola gaji, PPh 21, BPJS, kehadiran & cuti dalam satu platform. Gratis 14 hari!')">
    <meta property="og:image" content="@yield('og_image', asset('images/og-image.png'))">
    <meta property="og:locale" content="id_ID">
    <meta property="og:site_name" content="GajiPro">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ url()->current() }}">
    <meta name="twitter:title" content="@yield('twitter_title', 'GajiPro - Software Payroll & HRIS #1 Indonesia')">
    <meta name="twitter:description" content="@yield('twitter_description', 'Kelola gaji, PPh 21, BPJS, kehadiran & cuti dalam satu platform. Gratis 14 hari!')">
    <meta name="twitter:image" content="@yield('twitter_image', asset('images/og-image.png'))">

    {{-- Favicon --}}
    <link rel="icon" type="image/svg+xml" href="{{ brand_asset('favicon_path', 'favicon.svg') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Scripts --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Structured Data --}}
    @stack('structured-data')

    @stack('styles')
</head>
<body class="font-sans antialiased bg-white text-secondary-900">
    @yield('content')

    {{-- Toast Notifications --}}
    <x-toast />

    {{-- Show toast for validation errors --}}
    @if ($errors->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @foreach ($errors->all() as $error)
            window.dispatchEvent(new CustomEvent('toast', {
                detail: {
                    type: 'danger',
                    title: 'Login Gagal',
                    message: '{{ $error }}'
                }
            }));
            @endforeach
        });
    </script>
    @endif

    {{-- Show toast for session messages --}}
    @if (session('success'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            window.dispatchEvent(new CustomEvent('toast', {
                detail: {
                    type: 'success',
                    message: '{{ session('success') }}'
                }
            }));
        });
    </script>
    @endif

    @if (session('error'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            window.dispatchEvent(new CustomEvent('toast', {
                detail: {
                    type: 'danger',
                    message: '{{ session('error') }}'
                }
            }));
        });
    </script>
    @endif

    @stack('scripts')
</body>
</html>
