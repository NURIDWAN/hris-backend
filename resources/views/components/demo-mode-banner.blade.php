@if(auth()->check() && auth()->user()->company && auth()->user()->company->is_demo_mode)
<div x-data="{ show: true }" x-show="show" class="bg-slate-700 text-white text-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-1.5">
        <div class="flex items-center justify-between">
            <p>
                <span class="text-amber-400 font-medium">Mode Simulasi</span>
                <span class="text-white/70 ml-1.5">— Data contoh untuk demo. Anda dapat mencoba semua fitur.</span>
            </p>
            <div class="flex items-center gap-3">
                <a href="{{ route('demo.settings') }}" class="text-white/90 hover:text-white text-xs underline underline-offset-2">
                    Gunakan data asli
                </a>
                <button @click="show = false" class="text-white/50 hover:text-white" title="Tutup">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>
@endif
