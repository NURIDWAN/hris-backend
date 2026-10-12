@extends('superadmin.layouts.app')

@section('title', 'Tambah Payment Gateway')

@section('breadcrumb')
    <a href="{{ route('superadmin.payment-gateways.index') }}" class="text-secondary-500 hover:text-primary-600">Payment Gateways</a>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-secondary-900 font-medium">Tambah</span>
@endsection

@section('header')
    <h1 class="text-2xl font-bold text-secondary-900">Tambah Payment Gateway</h1>
    <p class="text-secondary-500 mt-1">Konfigurasi gateway pembayaran baru</p>
@endsection

@section('content')
    <form action="{{ route('superadmin.payment-gateways.store') }}" method="POST" x-data="{ gateway: '{{ old('gateway', '') }}' }">
        @csrf

        <div class="card">
            <div class="card-body space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Gateway --}}
                    <div>
                        <label for="gateway" class="block text-sm font-medium text-secondary-700 mb-1">
                            Gateway ID <span class="text-danger-500">*</span>
                        </label>
                        <select name="gateway" id="gateway"
                                class="input w-full @error('gateway') border-danger-500 @enderror"
                                x-model="gateway" required>
                            <option value="">Pilih Gateway</option>
                            <option value="midtrans" {{ old('gateway') == 'midtrans' ? 'selected' : '' }}>Midtrans</option>
                            <option value="xendit" {{ old('gateway') == 'xendit' ? 'selected' : '' }}>Xendit</option>
                            <option value="manual" {{ old('gateway') == 'manual' ? 'selected' : '' }}>Manual Transfer</option>
                        </select>
                        @error('gateway')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Name --}}
                    <div>
                        <label for="name" class="block text-sm font-medium text-secondary-700 mb-1">
                            Nama Display <span class="text-danger-500">*</span>
                        </label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}"
                               class="input w-full @error('name') border-danger-500 @enderror"
                               placeholder="Midtrans" required>
                        @error('name')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Environment --}}
                    <div>
                        <label for="environment" class="block text-sm font-medium text-secondary-700 mb-1">
                            Environment
                        </label>
                        <select name="environment" id="environment"
                                class="input w-full @error('environment') border-danger-500 @enderror">
                            <option value="sandbox" {{ old('environment') == 'sandbox' ? 'selected' : '' }}>Sandbox</option>
                            <option value="production" {{ old('environment') == 'production' ? 'selected' : '' }}>Production</option>
                        </select>
                        @error('environment')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Sort Order --}}
                    <div>
                        <label for="sort_order" class="block text-sm font-medium text-secondary-700 mb-1">
                            Urutan
                        </label>
                        <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', 0) }}"
                               class="input w-full @error('sort_order') border-danger-500 @enderror"
                               placeholder="0">
                        @error('sort_order')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Credentials Section --}}
                <div class="border-t border-secondary-200 pt-6">
                    <h3 class="text-lg font-medium text-secondary-900 mb-4">Kredensial</h3>

                    {{-- Midtrans Credentials --}}
                    <div x-show="gateway === 'midtrans'" x-cloak class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="midtrans_server_key" class="block text-sm font-medium text-secondary-700 mb-1">
                                Server Key <span class="text-danger-500">*</span>
                            </label>
                            <input type="password" name="credentials[server_key]" id="midtrans_server_key"
                                   value="{{ old('credentials.server_key') }}"
                                   class="input w-full"
                                   placeholder="SB-Mid-server-xxx">
                            <p class="mt-1 text-xs text-secondary-500">Server Key dari dashboard Midtrans</p>
                        </div>

                        <div>
                            <label for="midtrans_client_key" class="block text-sm font-medium text-secondary-700 mb-1">
                                Client Key <span class="text-danger-500">*</span>
                            </label>
                            <input type="password" name="credentials[client_key]" id="midtrans_client_key"
                                   value="{{ old('credentials.client_key') }}"
                                   class="input w-full"
                                   placeholder="SB-Mid-client-xxx">
                            <p class="mt-1 text-xs text-secondary-500">Client Key dari dashboard Midtrans</p>
                        </div>

                        <div>
                            <label for="midtrans_merchant_id" class="block text-sm font-medium text-secondary-700 mb-1">
                                Merchant ID
                            </label>
                            <input type="text" name="credentials[merchant_id]" id="midtrans_merchant_id"
                                   value="{{ old('credentials.merchant_id') }}"
                                   class="input w-full"
                                   placeholder="G123456">
                            <p class="mt-1 text-xs text-secondary-500">Merchant ID (opsional)</p>
                        </div>
                    </div>

                    {{-- Xendit Credentials --}}
                    <div x-show="gateway === 'xendit'" x-cloak class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="xendit_secret_key" class="block text-sm font-medium text-secondary-700 mb-1">
                                Secret Key <span class="text-danger-500">*</span>
                            </label>
                            <input type="password" name="credentials[secret_key]" id="xendit_secret_key"
                                   value="{{ old('credentials.secret_key') }}"
                                   class="input w-full"
                                   placeholder="xnd_development_xxx atau xnd_production_xxx">
                            <p class="mt-1 text-xs text-secondary-500">API Key dari dashboard Xendit (Settings > API Keys)</p>
                        </div>

                        <div>
                            <label for="xendit_callback_token" class="block text-sm font-medium text-secondary-700 mb-1">
                                Callback Verification Token
                            </label>
                            <input type="password" name="credentials[callback_token]" id="xendit_callback_token"
                                   value="{{ old('credentials.callback_token') }}"
                                   class="input w-full"
                                   placeholder="Token untuk verifikasi webhook">
                            <p class="mt-1 text-xs text-secondary-500">Callback Token dari Xendit (Settings > Callbacks)</p>
                        </div>

                        <div class="md:col-span-2">
                            <label for="xendit_webhook_url" class="block text-sm font-medium text-secondary-700 mb-1">
                                Webhook URL (untuk konfigurasi di Xendit)
                            </label>
                            <div class="flex">
                                <input type="text" id="xendit_webhook_url" readonly
                                       value="{{ url('/api/webhooks/xendit') }}"
                                       class="input w-full bg-secondary-50 text-secondary-600">
                                <button type="button" onclick="navigator.clipboard.writeText('{{ url('/api/webhooks/xendit') }}')"
                                        class="btn btn-secondary ml-2" title="Copy URL">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                </button>
                            </div>
                            <p class="mt-1 text-xs text-secondary-500">Masukkan URL ini di Xendit Dashboard > Settings > Callbacks > Invoice paid</p>
                        </div>
                    </div>

                    {{-- Manual Transfer Info --}}
                    <div x-show="gateway === 'manual'" x-cloak class="grid grid-cols-1 gap-6">
                        <div>
                            <label for="manual_bank_name" class="block text-sm font-medium text-secondary-700 mb-1">
                                Nama Bank <span class="text-danger-500">*</span>
                            </label>
                            <input type="text" name="credentials[bank_name]" id="manual_bank_name"
                                   value="{{ old('credentials.bank_name') }}"
                                   class="input w-full"
                                   placeholder="BCA, Mandiri, BNI, dll">
                        </div>

                        <div>
                            <label for="manual_account_number" class="block text-sm font-medium text-secondary-700 mb-1">
                                Nomor Rekening <span class="text-danger-500">*</span>
                            </label>
                            <input type="text" name="credentials[account_number]" id="manual_account_number"
                                   value="{{ old('credentials.account_number') }}"
                                   class="input w-full"
                                   placeholder="1234567890">
                        </div>

                        <div>
                            <label for="manual_account_name" class="block text-sm font-medium text-secondary-700 mb-1">
                                Nama Pemilik Rekening <span class="text-danger-500">*</span>
                            </label>
                            <input type="text" name="credentials[account_name]" id="manual_account_name"
                                   value="{{ old('credentials.account_name') }}"
                                   class="input w-full"
                                   placeholder="PT. Nama Perusahaan">
                        </div>
                    </div>

                    {{-- No Gateway Selected --}}
                    <div x-show="!gateway" class="text-center py-8 text-secondary-500">
                        <svg class="w-12 h-12 mx-auto mb-3 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                        </svg>
                        <p>Pilih gateway terlebih dahulu untuk menampilkan form kredensial</p>
                    </div>

                    <p class="mt-4 text-sm text-secondary-500">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        Kredensial akan dienkripsi sebelum disimpan
                    </p>
                </div>

                {{-- Is Active --}}
                <div class="flex items-center gap-3">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" id="is_active" value="1"
                           class="w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500"
                           {{ old('is_active') ? 'checked' : '' }}>
                    <label for="is_active" class="text-sm font-medium text-secondary-700">
                        Aktifkan gateway ini
                    </label>
                </div>
            </div>

            <div class="card-footer flex items-center justify-end gap-3">
                <a href="{{ route('superadmin.payment-gateways.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Gateway</button>
            </div>
        </div>
    </form>
@endsection
