@extends('layouts.guest')

@section('title', 'Langganan Berakhir')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-50 via-white to-primary-50 py-12 px-4">
    <div class="max-w-md w-full text-center">
        <div class="bg-white rounded-2xl shadow-xl p-8 border border-slate-100">
            {{-- Icon --}}
            <div class="w-20 h-20 bg-warning-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>

            {{-- Title --}}
            <h1 class="text-2xl font-bold text-secondary-900 mb-2">Langganan Anda Telah Berakhir</h1>
            <p class="text-secondary-600 mb-6">
                Masa langganan perusahaan Anda telah habis. Silakan perpanjang langganan untuk melanjutkan menggunakan GajiPro.
            </p>

            {{-- Actions --}}
            <div class="space-y-3">
                <a href="{{ route('pricing') }}" class="btn btn-primary w-full">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                    Perpanjang Langganan
                </a>
                <form action="{{ route('logout') }}" method="POST" class="inline w-full">
                    @csrf
                    <button type="submit" class="btn btn-outline w-full">
                        Keluar
                    </button>
                </form>
            </div>

            {{-- Contact --}}
            <p class="mt-6 text-sm text-secondary-500">
                Butuh bantuan? <a href="mailto:support@gajipro.com" class="text-primary-600 hover:text-primary-700 font-medium">Hubungi kami</a>
            </p>
        </div>
    </div>
</div>
@endsection
