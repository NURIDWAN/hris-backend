@extends('layouts.admin')

@section('title', 'Pengaturan Mode Demo')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Mode Demo</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Pengaturan Mode Demo</h1>
            <p class="text-secondary-500 mt-1">Kelola data demo dan beralih ke mode produksi.</p>
        </div>
    </div>
@endsection

@section('content')
    {{-- Demo Status Card --}}
    <div class="card mb-6">
        <div class="card-body">
            <div class="flex items-center gap-2 mb-2">
                <h3 class="text-lg font-semibold text-secondary-900">Mode Demo Aktif</h3>
                <span class="px-2 py-0.5 bg-amber-100 text-amber-700 text-xs font-medium rounded-full">Demo</span>
            </div>
            <p class="text-secondary-600 mb-4">
                Anda sedang menggunakan data sampel untuk eksplorasi fitur GajiPro.
                Semua data saat ini adalah data demo dan tidak akan mempengaruhi operasional sebenarnya.
            </p>
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-secondary-50 rounded-lg p-3 text-center">
                    <p class="text-2xl font-bold text-secondary-900">{{ $stats['employees'] }}</p>
                    <p class="text-xs text-secondary-500">Karyawan Demo</p>
                </div>
                <div class="bg-secondary-50 rounded-lg p-3 text-center">
                    <p class="text-2xl font-bold text-secondary-900">{{ $stats['departments'] }}</p>
                    <p class="text-xs text-secondary-500">Departemen</p>
                </div>
                <div class="bg-secondary-50 rounded-lg p-3 text-center">
                    <p class="text-2xl font-bold text-secondary-900">{{ $stats['positions'] }}</p>
                    <p class="text-xs text-secondary-500">Jabatan</p>
                </div>
            </div>
            @if($company->demo_started_at)
                <p class="text-xs text-secondary-400 mt-3">
                    Demo dimulai: {{ $company->demo_started_at->translatedFormat('d F Y, H:i') }}
                </p>
            @endif
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-6">
        {{-- Switch to Production --}}
        <div class="card border-2 border-primary-200 bg-primary-50/30">
            <div class="card-header bg-transparent border-0">
                <div>
                    <h3 class="font-semibold text-secondary-900">Mulai dengan Data Asli</h3>
                    <p class="text-xs text-secondary-500">Hapus data demo dan mulai fresh</p>
                </div>
            </div>
            <div class="card-body pt-0">
                <p class="text-secondary-600 text-sm mb-4">
                    Siap menggunakan GajiPro untuk perusahaan Anda? Hapus semua data demo dan mulai memasukkan data karyawan yang sebenarnya.
                </p>
                <ul class="text-sm text-secondary-600 space-y-2 mb-6">
                    <li class="flex items-start gap-2">
                        <span class="text-success-500">✓</span>
                        <span>Semua data demo akan dihapus</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-success-500">✓</span>
                        <span>Pengaturan perusahaan akan tetap tersimpan</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-success-500">✓</span>
                        <span>Akun pengguna Anda tetap aktif</span>
                    </li>
                </ul>

                <form action="{{ route('demo.switch-to-production') }}" method="POST" x-data="{ confirmed: false }">
                    @csrf
                    <label class="flex items-center gap-2 mb-4 cursor-pointer">
                        <input type="checkbox" name="confirm" x-model="confirmed" class="rounded border-secondary-300 text-primary-600 focus:ring-primary-500">
                        <span class="text-sm text-secondary-700">Saya mengerti bahwa semua data demo akan dihapus permanen</span>
                    </label>
                    <button type="submit" :disabled="!confirmed" class="btn btn-primary w-full disabled:opacity-50 disabled:cursor-not-allowed">
                        Mulai Mode Produksi
                    </button>
                </form>
            </div>
        </div>

        {{-- Reset Demo Data --}}
        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="font-semibold text-secondary-900">Reset Data Demo</h3>
                    <p class="text-xs text-secondary-500">Kembalikan ke data demo awal</p>
                </div>
            </div>
            <div class="card-body">
                <p class="text-secondary-600 text-sm mb-4">
                    Ingin mengulang eksplorasi dari awal? Reset akan menghapus semua perubahan yang Anda buat pada data demo dan mengembalikannya ke kondisi awal.
                </p>
                <ul class="text-sm text-secondary-600 space-y-2 mb-6">
                    <li class="flex items-start gap-2">
                        <span class="text-warning-500">!</span>
                        <span>Semua perubahan data demo akan hilang</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-success-500">✓</span>
                        <span>Data demo baru akan di-generate</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-success-500">✓</span>
                        <span>Tetap dalam mode demo</span>
                    </li>
                </ul>

                <form action="{{ route('demo.reset') }}" method="POST" x-data="{ confirmed: false }">
                    @csrf
                    <label class="flex items-center gap-2 mb-4 cursor-pointer">
                        <input type="checkbox" name="confirm" x-model="confirmed" class="rounded border-secondary-300 text-primary-600 focus:ring-primary-500">
                        <span class="text-sm text-secondary-700">Saya ingin mereset semua data demo</span>
                    </label>
                    <button type="submit" :disabled="!confirmed" class="btn btn-secondary w-full disabled:opacity-50 disabled:cursor-not-allowed">
                        Reset Data Demo
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- FAQ --}}
    <div class="card mt-6">
        <div class="card-header">
            <h3 class="card-title">Pertanyaan Umum</h3>
        </div>
        <div class="card-body">
            <div class="space-y-4" x-data="{ active: null }">
                <div class="border border-secondary-200 rounded-lg">
                    <button @click="active = active === 1 ? null : 1" class="w-full px-4 py-3 text-left flex items-center justify-between">
                        <span class="font-medium text-secondary-900">Apa itu Mode Demo?</span>
                        <svg :class="active === 1 ? 'rotate-180' : ''" class="w-5 h-5 text-secondary-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="active === 1" x-collapse class="px-4 pb-3">
                        <p class="text-secondary-600 text-sm">Mode Demo memungkinkan Anda mencoba semua fitur GajiPro menggunakan data sampel. Anda bisa mengeksplorasi tanpa risiko mengacaukan data perusahaan yang sebenarnya.</p>
                    </div>
                </div>

                <div class="border border-secondary-200 rounded-lg">
                    <button @click="active = active === 2 ? null : 2" class="w-full px-4 py-3 text-left flex items-center justify-between">
                        <span class="font-medium text-secondary-900">Apakah data demo bisa dikembalikan setelah switch ke produksi?</span>
                        <svg :class="active === 2 ? 'rotate-180' : ''" class="w-5 h-5 text-secondary-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="active === 2" x-collapse class="px-4 pb-3">
                        <p class="text-secondary-600 text-sm">Tidak. Setelah Anda beralih ke mode produksi, data demo akan dihapus permanen dan tidak bisa dikembalikan. Pastikan Anda sudah siap sebelum beralih.</p>
                    </div>
                </div>

                <div class="border border-secondary-200 rounded-lg">
                    <button @click="active = active === 3 ? null : 3" class="w-full px-4 py-3 text-left flex items-center justify-between">
                        <span class="font-medium text-secondary-900">Apakah ada batasan waktu untuk mode demo?</span>
                        <svg :class="active === 3 ? 'rotate-180' : ''" class="w-5 h-5 text-secondary-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="active === 3" x-collapse class="px-4 pb-3">
                        <p class="text-secondary-600 text-sm">Mode demo tersedia selama masa trial 14 hari. Setelah itu, Anda perlu beralih ke mode produksi atau berlangganan untuk melanjutkan menggunakan GajiPro.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
