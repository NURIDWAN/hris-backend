@extends('superadmin.layouts.app')

@section('title', 'Block IP Baru')

@section('header')
    <div class="flex items-center gap-4">
        <a href="{{ route('superadmin.security.blocked-ips.index') }}" class="btn btn-ghost">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Block IP Baru</h1>
            <p class="text-secondary-600">Tambahkan IP ke daftar blokir</p>
        </div>
    </div>
@endsection

@section('content')
    <div class="max-w-2xl">
        <div class="card">
            <form method="POST" action="{{ route('superadmin.security.blocked-ips.store') }}">
                @csrf
                <div class="card-body space-y-6">
                    <div>
                        <label for="ip_address" class="block text-sm font-medium text-secondary-700 mb-1">
                            IP Address <span class="text-danger-500">*</span>
                        </label>
                        <input type="text" name="ip_address" id="ip_address"
                               value="{{ old('ip_address') }}"
                               class="input w-full @error('ip_address') border-danger-500 @enderror"
                               placeholder="192.168.1.1"
                               required>
                        @error('ip_address')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-secondary-500">Format: IPv4 (192.168.1.1) atau IPv6</p>
                    </div>

                    <div>
                        <label for="reason" class="block text-sm font-medium text-secondary-700 mb-1">
                            Alasan <span class="text-danger-500">*</span>
                        </label>
                        <input type="text" name="reason" id="reason"
                               value="{{ old('reason') }}"
                               class="input w-full @error('reason') border-danger-500 @enderror"
                               placeholder="Contoh: Brute force attack"
                               required>
                        @error('reason')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="duration" class="block text-sm font-medium text-secondary-700 mb-1">
                            Durasi (menit)
                        </label>
                        <input type="number" name="duration" id="duration"
                               value="{{ old('duration') }}"
                               class="input w-full @error('duration') border-danger-500 @enderror"
                               placeholder="Kosongkan untuk permanent"
                               min="1">
                        @error('duration')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-secondary-500">Kosongkan untuk blokir permanen</p>
                    </div>

                    <div class="bg-warning-50 border border-warning-200 rounded-lg p-4">
                        <div class="flex">
                            <svg class="w-5 h-5 text-warning-600 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-warning-800">Perhatian</p>
                                <p class="text-sm text-warning-700 mt-1">
                                    IP yang diblokir tidak akan dapat mengakses sistem sama sekali, termasuk API dan halaman web.
                                    Pastikan IP yang diblokir bukan milik pengguna yang sah.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer flex justify-end gap-3">
                    <a href="{{ route('superadmin.security.blocked-ips.index') }}" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-danger">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                        </svg>
                        Block IP
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
