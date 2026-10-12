<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>503 - Sedang Maintenance | GajiPro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="font-sans antialiased bg-gradient-to-br from-slate-50 via-white to-slate-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-lg">
        {{-- Card --}}
        <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden">
            {{-- Header with gradient --}}
            <div class="bg-gradient-to-r from-sky-500 to-blue-600 px-8 py-6">
                <div class="flex items-center justify-center">
                    <div class="w-20 h-20 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center">
                        <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Content --}}
            <div class="px-8 py-8 text-center">
                <h1 class="text-6xl font-bold bg-gradient-to-r from-sky-500 to-blue-600 bg-clip-text text-transparent mb-2">
                    503
                </h1>
                <h2 class="text-xl font-semibold text-slate-800 mb-3">
                    Sedang Maintenance
                </h2>
                <p class="text-slate-500 mb-6 leading-relaxed">
                    @if(isset($exception) && $exception->getMessage())
                        {{ $exception->getMessage() }}
                    @else
                        Sistem sedang dalam pemeliharaan untuk meningkatkan kualitas layanan. Kami akan segera kembali.
                    @endif
                </p>

                {{-- Progress Illustration --}}
                <div class="bg-sky-50 rounded-xl p-6 mb-6">
                    <div class="flex flex-col items-center">
                        <div class="w-full bg-sky-100 rounded-full h-2 mb-3">
                            <div class="bg-gradient-to-r from-sky-500 to-blue-600 h-2 rounded-full w-3/4 animate-pulse"></div>
                        </div>
                        <p class="text-sm text-sky-600 font-medium">Sedang dalam proses...</p>
                    </div>
                </div>

                {{-- Info Box --}}
                <div class="bg-slate-50 rounded-xl p-4 mb-6">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="text-left">
                            <p class="text-sm font-medium text-slate-700">Apa yang sedang terjadi?</p>
                            <p class="text-xs text-slate-500 mt-0.5">Kami sedang melakukan pembaruan sistem untuk memberikan pengalaman yang lebih baik.</p>
                        </div>
                    </div>
                </div>

                {{-- Action --}}
                <div class="flex justify-center">
                    <a href="javascript:location.reload()"
                       class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-gradient-to-r from-primary-500 to-primary-600 hover:from-primary-600 hover:to-primary-700 text-white font-medium rounded-xl transition-all shadow-lg shadow-primary-500/25">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Coba Lagi
                    </a>
                </div>
            </div>

            {{-- Footer --}}
            <div class="px-8 py-4 bg-slate-50 border-t border-slate-100">
                <div class="flex items-center justify-center gap-2">
                    <div class="w-6 h-6 bg-gradient-to-br from-primary-500 to-primary-600 rounded-lg flex items-center justify-center">
                        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <span class="text-sm font-semibold bg-gradient-to-r from-primary-600 to-primary-500 bg-clip-text text-transparent">GajiPro</span>
                </div>
            </div>
        </div>

        {{-- Additional help text --}}
        <p class="text-center text-xs text-slate-400 mt-6">
            Error Code: 503 Service Unavailable
        </p>
    </div>
</body>
</html>
