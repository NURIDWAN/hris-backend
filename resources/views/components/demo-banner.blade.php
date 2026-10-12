@props(['dismissible' => true])

@php
    $isDemoCompany = auth()->check() && auth()->user()->company_id === 1;
    $isProduction = app()->environment('production');
@endphp

@if($isDemoCompany || $isProduction)
<div
    x-data="{ show: !localStorage.getItem('demoBannerDismissed_{{ now()->format('Y-m-d') }}') }"
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 -translate-y-2"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 -translate-y-2"
    class="bg-gradient-to-r from-amber-500 via-orange-500 to-amber-500 text-white relative"
>
    <div class="max-w-7xl mx-auto px-4 py-3">
        <div class="flex flex-col sm:flex-row items-center justify-center gap-2 sm:gap-4 text-center sm:text-left">
            {{-- Icon --}}
            <div class="flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>

            {{-- Message --}}
            <div class="flex-1">
                <p class="font-semibold text-sm sm:text-base">
                    Versi Demo - Sistem masih dalam pengembangan
                </p>
                <p class="text-xs sm:text-sm text-amber-100 mt-0.5">
                    Jika menemukan bug atau kendala, hubungi admin di
                    <a href="https://wa.me/6285640899224" target="_blank" class="underline font-semibold hover:text-white">
                        0856-4089-9224
                    </a>
                    (WhatsApp). Versi final akan diumumkan pada event mendatang.
                </p>
            </div>

            {{-- Dismiss Button --}}
            @if($dismissible)
            <button
                @click="show = false; localStorage.setItem('demoBannerDismissed_{{ now()->format('Y-m-d') }}', 'true')"
                class="flex-shrink-0 p-1 rounded-full hover:bg-white/20 transition-colors"
                title="Tutup untuk hari ini"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            @endif
        </div>
    </div>
</div>
@endif
