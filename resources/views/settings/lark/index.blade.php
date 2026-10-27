@extends('layouts.admin')

@section('title', 'Integrasi Lark')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Pengaturan</span>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Integrasi Lark</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Integrasi Lark (Attendance)</h1>
            <p class="text-secondary-500 mt-1">Kirim otomatis data kehadiran karyawan dari aplikasi JagoGaji (Wajah & GPS) ke Lark Suite.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('settings.lark.mapping') }}" class="btn btn-secondary">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                Pemetaan Karyawan ({{ $stats['mapped_employees'] }}/{{ $stats['total_employees'] }})
            </a>
        </div>
    </div>
@endsection

@section('content')
    {{-- Notifications --}}
    @if(session('success'))
        <div class="mb-6 p-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="card p-5">
            <div class="text-xs font-semibold text-secondary-500 uppercase tracking-wider mb-1">Status Integrasi</div>
            <div class="flex items-center gap-2">
                @if($integration->is_active)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                        <span class="w-1.5 h-1.5 mr-1.5 rounded-full bg-emerald-500"></span>
                        Aktif
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-secondary-100 text-secondary-800">
                        Non-aktif
                    </span>
                @endif
            </div>
            <p class="text-xs text-secondary-400 mt-2">
                Terakhir sync: {{ $integration->last_synced_at ? $integration->last_synced_at->diffForHumans() : '-' }}
            </p>
        </div>

        <div class="card p-5">
            <div class="text-xs font-semibold text-secondary-500 uppercase tracking-wider mb-1">Pemetaan Karyawan</div>
            <div class="text-2xl font-bold text-secondary-900">
                {{ $stats['mapped_employees'] }} <span class="text-sm font-normal text-secondary-500">/ {{ $stats['total_employees'] }}</span>
            </div>
            <p class="text-xs text-secondary-400 mt-2">
                {{ $stats['unmapped_employees'] }} belum terhubung
            </p>
        </div>

        <div class="card p-5">
            <div class="text-xs font-semibold text-secondary-500 uppercase tracking-wider mb-1">Tersinkron Hari Ini</div>
            <div class="text-2xl font-bold text-emerald-600">
                {{ $stats['synced_attendances'] }}
            </div>
            <p class="text-xs text-secondary-400 mt-2">
                Dari total {{ $stats['today_attendances'] }} absensi hari ini
            </p>
        </div>

        <div class="card p-5">
            <div class="text-xs font-semibold text-secondary-500 uppercase tracking-wider mb-1">Tertunda / Gagal</div>
            <div class="text-2xl font-bold text-rose-600">
                {{ $stats['failed_attendances'] }}
            </div>
            <p class="text-xs text-secondary-400 mt-2">
                Di-retry otomatis setiap jam
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Configuration Form --}}
        <div class="lg:col-span-2">
            <div class="card">
                <div class="card-header flex items-center justify-between">
                    <h3 class="card-title">Kredensial & Pengaturan Lark Open API</h3>
                    <span class="text-xs text-secondary-500">Platform: <strong>Lark Global</strong></span>
                </div>
                <div class="card-body">
                    <form action="{{ route('settings.lark.update') }}" method="POST" id="larkForm">
                        @csrf
                        @method('PUT')

                        <div class="space-y-6">
                            {{-- App ID --}}
                            <div>
                                <label for="app_id" class="block text-sm font-medium text-secondary-700 mb-1">
                                    Lark App ID <span class="text-danger-500">*</span>
                                </label>
                                <input type="text" name="app_id" id="app_id"
                                       value="{{ old('app_id', $integration->app_id) }}"
                                       placeholder="cli_a1b2c3d4e5f6..."
                                       required
                                       class="w-full rounded-lg border-secondary-300 focus:border-primary-500 focus:ring-primary-500 text-sm">
                                <p class="text-xs text-secondary-500 mt-1">Ditemukan di Lark Developer Console &rarr; App Details &rarr; Credentials.</p>
                                @error('app_id')
                                    <p class="mt-1 text-xs text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- App Secret --}}
                            <div>
                                <label for="app_secret" class="block text-sm font-medium text-secondary-700 mb-1">
                                    Lark App Secret @if(!$integration->exists)<span class="text-danger-500">*</span>@endif
                                </label>
                                <input type="password" name="app_secret" id="app_secret"
                                       placeholder="{{ $integration->exists ? '•••••••• (Biarkan kosong jika tidak diubah)' : 'Masukkan App Secret' }}"
                                       @if(!$integration->exists) required @endif
                                       class="w-full rounded-lg border-secondary-300 focus:border-primary-500 focus:ring-primary-500 text-sm">
                                <p class="text-xs text-secondary-500 mt-1">Disimpan secara aman dengan enkripsi AES-256 di database.</p>
                                @error('app_secret')
                                    <p class="mt-1 text-xs text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <hr class="border-secondary-200">

                            {{-- Options --}}
                            <div class="space-y-4">
                                <h4 class="text-sm font-semibold text-secondary-900">Perilaku Sinkronisasi</h4>

                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1"
                                           {{ old('is_active', $integration->is_active) ? 'checked' : '' }}
                                           class="w-4 h-4 rounded border-secondary-300 text-primary-600 focus:ring-primary-500">
                                    <div>
                                        <span class="text-sm font-medium text-secondary-800">Aktifkan Pengiriman Absensi ke Lark</span>
                                        <p class="text-xs text-secondary-500">Jika aktif, setiap clock-in/out di aplikasi JagoGaji akan langsung dikirim ke Lark.</p>
                                    </div>
                                </label>

                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="hidden" name="push_clock_in" value="0">
                                    <input type="checkbox" name="push_clock_in" value="1"
                                           {{ old('push_clock_in', $integration->push_clock_in) ? 'checked' : '' }}
                                           class="w-4 h-4 rounded border-secondary-300 text-primary-600 focus:ring-primary-500">
                                    <div>
                                        <span class="text-sm font-medium text-secondary-800">Kirim Absensi Masuk (Clock In)</span>
                                        <p class="text-xs text-secondary-500">Kirim waktu dan lokasi clock-in ke Lark Attendance Flow Records.</p>
                                    </div>
                                </label>

                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="hidden" name="push_clock_out" value="0">
                                    <input type="checkbox" name="push_clock_out" value="1"
                                           {{ old('push_clock_out', $integration->push_clock_out) ? 'checked' : '' }}
                                           class="w-4 h-4 rounded border-secondary-300 text-primary-600 focus:ring-primary-500">
                                    <div>
                                        <span class="text-sm font-medium text-secondary-800">Kirim Absensi Pulang (Clock Out)</span>
                                        <p class="text-xs text-secondary-500">Kirim waktu dan lokasi clock-out ke Lark Attendance Flow Records.</p>
                                    </div>
                                </label>
                            </div>

                            <hr class="border-secondary-200">

                            {{-- Bot Webhook --}}
                            <div>
                                <label class="flex items-center gap-3 cursor-pointer mb-3">
                                    <input type="hidden" name="enable_bot_notifications" value="0">
                                    <input type="checkbox" name="enable_bot_notifications" value="1"
                                           {{ old('enable_bot_notifications', $integration->enable_bot_notifications) ? 'checked' : '' }}
                                           class="w-4 h-4 rounded border-secondary-300 text-primary-600 focus:ring-primary-500">
                                    <span class="text-sm font-medium text-secondary-800">Aktifkan Notifikasi Bot Lark (Opsional)</span>
                                </label>

                                <label for="bot_webhook_url" class="block text-xs font-medium text-secondary-600 mb-1">
                                    Lark Custom Bot Webhook URL
                                </label>
                                <input type="url" name="bot_webhook_url" id="bot_webhook_url"
                                       value="{{ old('bot_webhook_url', $integration->bot_webhook_url) }}"
                                       placeholder="https://open.larksuite.com/open-apis/bot/v2/hook/xxxx"
                                       class="w-full rounded-lg border-secondary-300 focus:border-primary-500 focus:ring-primary-500 text-sm">
                                <p class="text-xs text-secondary-500 mt-1">Webhook URL dari bot grup chat Lark untuk rekap atau alert absensi.</p>
                            </div>

                            <div class="pt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                <button type="submit" class="btn btn-primary">
                                    Simpan Pengaturan
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Side Controls & Help --}}
        <div class="space-y-6">
            {{-- Test Connection Card --}}
            <div class="card p-5">
                <h4 class="text-sm font-semibold text-secondary-900 mb-2">Uji Koneksi Lark</h4>
                <p class="text-xs text-secondary-500 mb-4">
                    Verifikasi bahwa App ID dan App Secret valid dan dapat memperoleh Tenant Access Token dari server Lark.
                </p>
                <form action="{{ route('settings.lark.test-connection') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-secondary w-full justify-center">
                        <svg class="w-4 h-4 mr-1.5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        Uji Koneksi Sekarang
                    </button>
                </form>
            </div>

            {{-- Manual Sync Actions --}}
            <div class="card p-5">
                <h4 class="text-sm font-semibold text-secondary-900 mb-2">Aksi Sinkronisasi</h4>
                <div class="space-y-3">
                    <form action="{{ route('settings.lark.sync-employees') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline w-full justify-center text-xs">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Sinkronkan Karyawan via Email
                        </button>
                    </form>

                    <form action="{{ route('settings.lark.retry-sync') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline w-full justify-center text-xs">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                            Kirim Ulang Absensi Hari Ini
                        </button>
                    </form>
                </div>
            </div>

            {{-- Guide Card --}}
            <div class="card p-5 bg-slate-50 border-slate-200">
                <h4 class="text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Panduan Izin Lark Developer</h4>
                <div class="text-xs text-slate-600 space-y-2">
                    <p>Pastikan aplikasi di <strong>open.larksuite.com</strong> memiliki izin (Permissions) berikut:</p>
                    <ul class="list-disc pl-4 space-y-1">
                        <li><code class="bg-slate-200 px-1 py-0.5 rounded">attendance:attendance</code> (Read & Write)</li>
                        <li><code class="bg-slate-200 px-1 py-0.5 rounded">contact:user.employee_id:readonly</code></li>
                        <li><code class="bg-slate-200 px-1 py-0.5 rounded">contact:contact:readonly_as_app</code></li>
                    </ul>
                    <p class="pt-1">Setelah menambahkan izin, publikasikan versi aplikasi baru (*Create a version & Publish*) di Lark Developer Console.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
