@extends('superadmin.layouts.app')

@section('title', 'Detail Subscription')

@section('breadcrumb')
    <a href="{{ route('superadmin.subscriptions.index') }}" class="text-secondary-500 hover:text-primary-600">Subscriptions</a>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-secondary-900 font-medium">Detail</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Detail Subscription</h1>
            <p class="text-secondary-500 mt-1">{{ $subscription->company->name ?? 'N/A' }}</p>
        </div>
        <x-badge :type="$subscription->status === 'active' ? 'success' : ($subscription->status === 'pending' ? 'warning' : 'danger')" class="text-base px-4 py-2">
            {{ ucfirst($subscription->status) }}
        </x-badge>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Info --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Subscription Details --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Informasi Subscription</h3>
                </div>
                <div class="card-body">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm text-secondary-500">Perusahaan</dt>
                            <dd class="font-medium text-secondary-900">{{ $subscription->company->name ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-secondary-500">Plan</dt>
                            <dd class="font-medium text-secondary-900">{{ $subscription->plan->name ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-secondary-500">Tanggal Mulai</dt>
                            <dd class="font-medium text-secondary-900">{{ $subscription->started_at ? $subscription->started_at->format('d M Y') : '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-secondary-500">Tanggal Berakhir</dt>
                            <dd class="font-medium text-secondary-900">{{ $subscription->ends_at ? $subscription->ends_at->format('d M Y') : '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-secondary-500">Billing Cycle</dt>
                            <dd class="font-medium text-secondary-900">{{ ucfirst($subscription->billing_cycle ?? 'monthly') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-secondary-500">Dibuat</dt>
                            <dd class="font-medium text-secondary-900">{{ $subscription->created_at->format('d M Y H:i') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Payment History --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Riwayat Pembayaran</h3>
                </div>
                <div class="card-body-sm">
                    @if($subscription->payments && $subscription->payments->count() > 0)
                        <x-table>
                            <x-slot name="header">
                                <th class="px-6 py-3 text-left">No. Pembayaran</th>
                                <th class="px-6 py-3 text-left">Jumlah</th>
                                <th class="px-6 py-3 text-left">Tanggal</th>
                                <th class="px-6 py-3 text-center">Status</th>
                            </x-slot>

                            @foreach($subscription->payments as $payment)
                                <tr>
                                    <td class="px-6 py-4 font-mono text-sm">{{ $payment->payment_number }}</td>
                                    <td class="px-6 py-4 font-medium">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                                    <td class="px-6 py-4">{{ $payment->created_at->format('d M Y') }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <x-badge :type="$payment->status === 'success' ? 'success' : ($payment->status === 'pending' ? 'warning' : 'danger')">
                                            {{ ucfirst($payment->status) }}
                                        </x-badge>
                                    </td>
                                </tr>
                            @endforeach
                        </x-table>
                    @else
                        <p class="text-center text-secondary-500 py-8">Belum ada riwayat pembayaran</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Actions Sidebar --}}
        <div class="space-y-6">
            {{-- Quick Actions --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Aksi Cepat</h3>
                </div>
                <div class="card-body space-y-3">
                    @if($subscription->status !== 'active')
                        <form action="{{ route('superadmin.subscriptions.activate', $subscription) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-success w-full">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Aktifkan
                            </button>
                        </form>
                    @endif

                    @if($subscription->status === 'active')
                        <form action="{{ route('superadmin.subscriptions.suspend', $subscription) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-warning w-full">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                </svg>
                                Suspend
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- Extend Subscription --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Perpanjang Subscription</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('superadmin.subscriptions.extend', $subscription) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <div class="mb-4">
                            <label for="months" class="block text-sm font-medium text-secondary-700 mb-1">
                                Jumlah Bulan
                            </label>
                            <select name="months" id="months" class="input w-full">
                                <option value="1">1 Bulan</option>
                                <option value="3">3 Bulan</option>
                                <option value="6">6 Bulan</option>
                                <option value="12">12 Bulan</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-full">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Perpanjang
                        </button>
                    </form>
                </div>
            </div>

            {{-- Change Plan --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Ubah Plan</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('superadmin.subscriptions.change-plan', $subscription) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <div class="mb-4">
                            <label for="subscription_plan_id" class="block text-sm font-medium text-secondary-700 mb-1">
                                Plan Baru
                            </label>
                            <select name="subscription_plan_id" id="subscription_plan_id" class="input w-full">
                                @foreach(\App\Models\SubscriptionPlan::where('is_active', true)->get() as $plan)
                                    <option value="{{ $plan->id }}" {{ $subscription->subscription_plan_id == $plan->id ? 'selected' : '' }}>
                                        {{ $plan->name }} - Rp {{ number_format($plan->price_monthly, 0, ',', '.') }}/bln
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-secondary w-full">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Ubah Plan
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
