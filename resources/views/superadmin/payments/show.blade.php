@extends('superadmin.layouts.app')

@section('title', 'Detail Pembayaran')

@section('breadcrumb')
    <a href="{{ route('superadmin.payments.index') }}" class="text-secondary-500 hover:text-primary-600">Pembayaran</a>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-secondary-900 font-medium">Detail</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Detail Pembayaran</h1>
            <p class="text-secondary-500 mt-1 font-mono">{{ $payment->payment_number }}</p>
        </div>
        @php
            $statusType = match($payment->status) {
                'success' => 'success',
                'pending' => 'warning',
                'failed', 'expired' => 'danger',
                'refunded' => 'info',
                default => 'secondary'
            };
        @endphp
        <x-badge :type="$statusType" class="text-base px-4 py-2">
            {{ ucfirst($payment->status) }}
        </x-badge>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Info --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Payment Details --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Informasi Pembayaran</h3>
                </div>
                <div class="card-body">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm text-secondary-500">No. Pembayaran</dt>
                            <dd class="font-mono font-medium text-secondary-900">{{ $payment->payment_number }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-secondary-500">Perusahaan</dt>
                            <dd class="font-medium text-secondary-900">{{ $payment->company->name ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-secondary-500">Gateway</dt>
                            <dd class="font-medium text-secondary-900 capitalize">{{ $payment->gateway }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-secondary-500">Metode Pembayaran</dt>
                            <dd class="font-medium text-secondary-900">{{ $payment->payment_method ?? '-' }} {{ $payment->payment_channel ? '(' . $payment->payment_channel . ')' : '' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-secondary-500">Gateway Order ID</dt>
                            <dd class="font-mono text-sm text-secondary-900">{{ $payment->gateway_order_id ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-secondary-500">Gateway Transaction ID</dt>
                            <dd class="font-mono text-sm text-secondary-900">{{ $payment->gateway_transaction_id ?? '-' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Amount Details --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Detail Nominal</h3>
                </div>
                <div class="card-body">
                    <dl class="space-y-3">
                        <div class="flex items-center justify-between py-2 border-b border-secondary-100">
                            <dt class="text-secondary-600">Jumlah</dt>
                            <dd class="font-semibold text-secondary-900">Rp {{ number_format($payment->amount, 0, ',', '.') }}</dd>
                        </div>
                        <div class="flex items-center justify-between py-2 border-b border-secondary-100">
                            <dt class="text-secondary-600">Biaya Gateway</dt>
                            <dd class="text-secondary-900">Rp {{ number_format($payment->fee, 0, ',', '.') }}</dd>
                        </div>
                        <div class="flex items-center justify-between py-2">
                            <dt class="text-secondary-600 font-semibold">Jumlah Bersih</dt>
                            <dd class="text-lg font-bold text-success-600">Rp {{ number_format($payment->net_amount, 0, ',', '.') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Subscription Info --}}
            @if($payment->subscription)
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Subscription Terkait</h3>
                    </div>
                    <div class="card-body">
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <dt class="text-sm text-secondary-500">Plan</dt>
                                <dd class="font-medium text-secondary-900">{{ $payment->subscription->plan->name ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-secondary-500">Status</dt>
                                <dd>
                                    <x-badge :type="$payment->subscription->status === 'active' ? 'success' : 'warning'">
                                        {{ ucfirst($payment->subscription->status) }}
                                    </x-badge>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            @endif

            {{-- Timeline --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Timeline</h3>
                </div>
                <div class="card-body">
                    <dl class="space-y-3">
                        <div class="flex items-center justify-between py-2 border-b border-secondary-100">
                            <dt class="text-secondary-600">Dibuat</dt>
                            <dd class="text-secondary-900">{{ $payment->created_at->format('d M Y H:i:s') }}</dd>
                        </div>
                        @if($payment->paid_at)
                            <div class="flex items-center justify-between py-2 border-b border-secondary-100">
                                <dt class="text-secondary-600">Dibayar</dt>
                                <dd class="text-success-600 font-medium">{{ $payment->paid_at->format('d M Y H:i:s') }}</dd>
                            </div>
                        @endif
                        @if($payment->expired_at)
                            <div class="flex items-center justify-between py-2">
                                <dt class="text-secondary-600">Kadaluarsa</dt>
                                <dd class="text-secondary-900">{{ $payment->expired_at->format('d M Y H:i:s') }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        {{-- Actions Sidebar --}}
        <div class="space-y-6">
            {{-- Quick Actions --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Aksi</h3>
                </div>
                <div class="card-body space-y-3">
                    @if($payment->status === 'pending')
                        <form action="{{ route('superadmin.payments.confirm', $payment) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-success w-full">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Konfirmasi Pembayaran
                            </button>
                        </form>
                    @endif

                    @if($payment->status === 'success')
                        <form action="{{ route('superadmin.payments.refund', $payment) }}" method="POST"
                              x-data="{ showReason: false }">
                            @csrf
                            @method('PATCH')
                            <div x-show="showReason" class="mb-3">
                                <label class="block text-sm font-medium text-secondary-700 mb-1">Alasan Refund</label>
                                <textarea name="reason" rows="2" class="input w-full" placeholder="Alasan refund..."></textarea>
                            </div>
                            <button type="button" x-show="!showReason" @click="showReason = true" class="btn btn-warning w-full">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                </svg>
                                Refund
                            </button>
                            <button type="submit" x-show="showReason" class="btn btn-warning w-full">
                                Proses Refund
                            </button>
                        </form>
                    @endif

                    <a href="{{ route('superadmin.payments.index') }}" class="btn btn-secondary w-full">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Kembali
                    </a>
                </div>
            </div>

            {{-- Description --}}
            @if($payment->description)
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Deskripsi</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-secondary-600">{{ $payment->description }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
