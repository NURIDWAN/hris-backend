@extends('superadmin.layouts.app')

@section('title', 'Dashboard')

@section('header')
    <h1 class="text-2xl font-bold text-secondary-900">Dashboard</h1>
    <p class="text-secondary-500 mt-1">Overview sistem GajiPro</p>
@endsection

@section('content')
    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        {{-- Total Companies --}}
        <div class="stat-card">
            <div class="flex items-start justify-between">
                <div>
                    <p class="stat-card-label">Total Perusahaan</p>
                    <p class="stat-card-value">{{ number_format($stats['total_companies']) }}</p>
                </div>
                <div class="stat-card-icon bg-primary-100">
                    <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Active Subscriptions --}}
        <div class="stat-card">
            <div class="flex items-start justify-between">
                <div>
                    <p class="stat-card-label">Subscription Aktif</p>
                    <p class="stat-card-value">{{ number_format($stats['active_subscriptions']) }}</p>
                </div>
                <div class="stat-card-icon bg-success-50">
                    <svg class="w-6 h-6 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Total Revenue --}}
        <div class="stat-card">
            <div class="flex items-start justify-between">
                <div>
                    <p class="stat-card-label">Total Revenue</p>
                    <p class="stat-card-value text-2xl">Rp {{ number_format($stats['total_revenue'] / 1000000, 0, ',', '.') }}jt</p>
                </div>
                <div class="stat-card-icon bg-accent-50">
                    <svg class="w-6 h-6 text-accent-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Pending Payments --}}
        <div class="stat-card">
            <div class="flex items-start justify-between">
                <div>
                    <p class="stat-card-label">Pembayaran Pending</p>
                    <p class="stat-card-value">{{ number_format($stats['pending_payments']) }}</p>
                </div>
                <div class="stat-card-icon bg-warning-50">
                    <svg class="w-6 h-6 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            @if($stats['pending_payments'] > 0)
                <div class="mt-4">
                    <span class="text-sm text-warning-600 font-medium">Perlu konfirmasi</span>
                </div>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Recent Payments --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Pembayaran Terbaru</h3>
                <a href="{{ route('superadmin.payments.index') }}" class="text-sm text-primary-600 hover:text-primary-700">Lihat Semua</a>
            </div>
            <div class="card-body-sm">
                @forelse($recentPayments as $payment)
                    <div class="flex items-center justify-between py-3 {{ !$loop->last ? 'border-b border-secondary-100' : '' }}">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-secondary-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-secondary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-secondary-900">{{ $payment->company->name ?? 'N/A' }}</p>
                                <p class="text-sm text-secondary-500">{{ $payment->payment_number }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-secondary-900">Rp {{ number_format($payment->amount, 0, ',', '.') }}</p>
                            <x-badge :type="$payment->status === 'success' ? 'success' : ($payment->status === 'pending' ? 'warning' : 'danger')">
                                {{ ucfirst($payment->status) }}
                            </x-badge>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-secondary-500 py-4">Belum ada pembayaran</p>
                @endforelse
            </div>
        </div>

        {{-- Recent Subscriptions --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Subscription Terbaru</h3>
                <a href="{{ route('superadmin.subscriptions.index') }}" class="text-sm text-primary-600 hover:text-primary-700">Lihat Semua</a>
            </div>
            <div class="card-body-sm">
                @forelse($recentSubscriptions as $subscription)
                    <div class="flex items-center justify-between py-3 {{ !$loop->last ? 'border-b border-secondary-100' : '' }}">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-secondary-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-secondary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-secondary-900">{{ $subscription->company->name ?? 'N/A' }}</p>
                                <p class="text-sm text-secondary-500">{{ $subscription->plan->name ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <x-badge :type="$subscription->status === 'active' ? 'success' : ($subscription->status === 'pending' ? 'warning' : 'danger')">
                                {{ ucfirst($subscription->status) }}
                            </x-badge>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-secondary-500 py-4">Belum ada subscription</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
