{{-- Persistent Academy Promo Banner - Always visible, no close option --}}
@props(['fixed' => false])

<div class="{{ $fixed ? 'fixed top-0 left-0 right-0 z-[60]' : '' }} bg-gradient-to-r from-secondary-800 via-secondary-900 to-primary-900 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2.5">
        <div class="flex flex-col sm:flex-row items-center justify-center gap-2 sm:gap-4">
            {{-- Message --}}
            <div class="text-center sm:text-left">
                <p class="text-sm font-medium">
                    <span class="text-amber-400 font-bold">Mau Full Source Code?</span>
                    <span class="text-white/90 ml-1">Dashboard Web + Mobile App Flutter — Join</span>
                    <a
                        href="https://jagoflutter.com/academy/gajipro"
                        target="_blank"
                        rel="noopener"
                        class="text-amber-400 hover:text-amber-300 underline underline-offset-2 font-bold ml-1"
                    >
                        jagoflutter.com/academy/gajipro
                    </a>
                </p>
            </div>

            {{-- CTA Buttons --}}
            <div class="flex items-center gap-2">
                {{-- Join Academy --}}
                <a
                    href="https://jagoflutter.com/academy/gajipro"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex items-center gap-1.5 bg-amber-500 hover:bg-amber-400 text-secondary-900 text-xs font-bold px-3 py-1.5 rounded-full transition-colors shadow-sm"
                >
                    Join Sekarang
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </a>

                {{-- Download App --}}
                <a
                    href="https://play.google.com/store/apps/details?id=com.jagoflutter.gajipro"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex items-center gap-1.5 bg-white/10 hover:bg-white/20 border border-white/20 text-white text-xs font-medium px-3 py-1.5 rounded-full transition-colors"
                >
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M3.609 1.814L13.792 12 3.61 22.186a.996.996 0 0 1-.61-.92V2.734a1 1 0 0 1 .609-.92z" fill="#4285F4"/>
                        <path d="M14.499 12.707l2.302 2.302-10.937 6.333 8.635-8.635z" fill="#FBBC04"/>
                        <path d="M5.864 2.658L16.8 9.99l-2.302 2.302-8.634-8.634z" fill="#EA4335"/>
                        <path d="M17.698 10.509l2.807 1.626a1 1 0 0 1 0 1.73l-2.808 1.626L15.206 12l2.492-2.491z" fill="#34A853"/>
                    </svg>
                    <span class="sm:hidden">Install App</span>
                    <span class="hidden sm:inline">Install App, Tersedia di Google Play</span>
                </a>
            </div>
        </div>
    </div>
</div>
