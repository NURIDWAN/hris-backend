<!-- Navbar -->
<nav x-data="{ mobileMenuOpen: false, scrolled: false }"
     x-init="window.addEventListener('scroll', () => { scrolled = window.scrollY > 20 })"
     :class="scrolled ? 'bg-white/95 backdrop-blur-md shadow-sm' : 'bg-transparent'"
     class="fixed top-0 left-0 right-0 z-50 transition-all duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-20">
            <!-- Logo -->
            <a href="{{ url('/') }}" class="flex items-center gap-2">
                <div class="w-10 h-10 bg-primary-600 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span :class="scrolled ? 'text-secondary-900' : 'text-white'" class="text-xl font-bold transition-colors">GajiPro</span>
            </a>

            <!-- Desktop Menu -->
            <div class="hidden lg:flex items-center gap-8">
                <a href="#fitur" :class="scrolled ? 'text-secondary-600 hover:text-primary-600' : 'text-white/90 hover:text-white'" class="font-medium transition-colors">Fitur</a>
                <a href="{{ route('pricing') }}" :class="scrolled ? 'text-secondary-600 hover:text-primary-600' : 'text-white/90 hover:text-white'" class="font-medium transition-colors">Harga</a>
                <a href="#tentang" :class="scrolled ? 'text-secondary-600 hover:text-primary-600' : 'text-white/90 hover:text-white'" class="font-medium transition-colors">Tentang</a>
                <a href="#kontak" :class="scrolled ? 'text-secondary-600 hover:text-primary-600' : 'text-white/90 hover:text-white'" class="font-medium transition-colors">Kontak</a>
            </div>

            <!-- Desktop CTA -->
            <div class="hidden lg:flex items-center gap-4">
                <a href="{{ route('login') }}" :class="scrolled ? 'text-secondary-700 hover:text-primary-600' : 'text-white hover:text-white/80'" class="font-semibold transition-colors">Masuk</a>
                <a href="{{ route('register') }}" class="btn btn-primary text-sm py-2.5 px-5">Daftar Gratis</a>
            </div>

            <!-- Mobile Menu Button -->
            <button @click="mobileMenuOpen = !mobileMenuOpen" class="lg:hidden p-2" :class="scrolled ? 'text-secondary-700' : 'text-white'">
                <svg x-show="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                <svg x-show="mobileMenuOpen" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Mobile Menu -->
        <div x-show="mobileMenuOpen" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-4"
             class="lg:hidden bg-white rounded-2xl shadow-xl p-6 mb-4">
            <div class="flex flex-col gap-4">
                <a href="#fitur" class="text-secondary-700 hover:text-primary-600 font-medium py-2">Fitur</a>
                <a href="{{ route('pricing') }}" class="text-secondary-700 hover:text-primary-600 font-medium py-2">Harga</a>
                <a href="#tentang" class="text-secondary-700 hover:text-primary-600 font-medium py-2">Tentang</a>
                <a href="#kontak" class="text-secondary-700 hover:text-primary-600 font-medium py-2">Kontak</a>
                <hr class="border-secondary-200">
                <a href="{{ route('login') }}" class="text-secondary-700 hover:text-primary-600 font-semibold py-2">Masuk</a>
                <a href="{{ route('register') }}" class="btn btn-primary w-full">Daftar Gratis</a>
            </div>
        </div>
    </div>
</nav>
