@extends('layouts.admin')

@section('title', 'Billing & Langganan')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Pengaturan</span>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Billing</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Billing & Langganan</h1>
            <p class="text-secondary-500 mt-1">Kelola langganan dan pembayaran perusahaan Anda.</p>
        </div>
    </div>
@endsection

@section('content')
    <div class="space-y-6">
        {{-- Current Subscription --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Langganan Saat Ini</h3>
            </div>
            <div class="card-body">
                @if($currentSubscription && $currentSubscription->plan)
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-lg bg-primary-100 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-lg font-semibold text-secondary-900">{{ $currentSubscription->plan->name }}</h4>
                                    <p class="text-sm text-secondary-500">{{ $currentSubscription->cycle_label }}</p>
                                </div>
                            </div>
                            <div class="mt-4 flex flex-wrap items-center gap-4 text-sm">
                                <div>
                                    <span class="text-secondary-500">Status:</span>
                                    @if($currentSubscription->isActive())
                                        <x-badge type="success">Aktif</x-badge>
                                    @elseif($currentSubscription->status === 'cancelled')
                                        <x-badge type="warning">Dibatalkan</x-badge>
                                    @else
                                        <x-badge type="secondary">{{ $currentSubscription->status_label }}</x-badge>
                                    @endif
                                </div>
                                @if($currentSubscription->ends_at)
                                    <div>
                                        <span class="text-secondary-500">Berlaku hingga:</span>
                                        <span class="font-medium">{{ $currentSubscription->ends_at->format('d M Y') }}</span>
                                    </div>
                                @endif
                                @if($currentSubscription->onTrial())
                                    <div>
                                        <span class="text-secondary-500">Masa trial berakhir:</span>
                                        <span class="font-medium">{{ $currentSubscription->trial_ends_at->format('d M Y') }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <a href="{{ route('settings.billing.upgrade') }}" class="btn btn-primary">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/>
                                </svg>
                                Upgrade Paket
                            </a>
                            @if($currentSubscription->isActive() && !$currentSubscription->plan->isFree())
                                <button type="button"
                                    @click="$dispatch('confirm-dialog', {
                                        title: 'Batalkan Langganan',
                                        message: 'Apakah Anda yakin ingin membatalkan langganan? Anda masih dapat menggunakan fitur hingga periode langganan berakhir.',
                                        confirmText: 'Ya, Batalkan',
                                        type: 'danger',
                                        formAction: '{{ route('settings.billing.cancel') }}'
                                    })"
                                    class="btn btn-ghost text-danger-600">
                                    Batalkan
                                </button>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="w-12 h-12 mx-auto text-secondary-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                        </svg>
                        <p class="text-secondary-500 mb-4">Belum ada langganan aktif.</p>
                        <a href="{{ route('settings.billing.upgrade') }}" class="btn btn-primary">
                            Pilih Paket
                        </a>
                    </div>
                @endif
            </div>
        </div>

        {{-- Available Plans --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Paket Tersedia</h3>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    @foreach($plans as $plan)
                        <div class="border border-secondary-200 rounded-lg p-6 {{ $currentSubscription && $currentSubscription->subscription_plan_id == $plan->id ? 'ring-2 ring-primary-500' : '' }}">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-lg font-semibold text-secondary-900">{{ $plan->name }}</h4>
                                @if($currentSubscription && $currentSubscription->subscription_plan_id == $plan->id)
                                    <x-badge type="primary">Aktif</x-badge>
                                @endif
                            </div>
                            <div class="mb-4">
                                <span class="text-3xl font-bold text-secondary-900">{{ $plan->formatted_price_monthly }}</span>
                                <span class="text-secondary-500">/bulan</span>
                            </div>
                            <ul class="space-y-2 text-sm text-secondary-600 mb-6">
                                <li class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    @if($plan->hasUnlimitedEmployees())
                                        Unlimited karyawan
                                    @else
                                        Maks. {{ $plan->max_employees }} karyawan
                                    @endif
                                </li>
                                <li class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    @if($plan->hasUnlimitedUsers())
                                        Unlimited pengguna
                                    @else
                                        Maks. {{ $plan->max_users }} pengguna
                                    @endif
                                </li>
                                @if($plan->features)
                                    @foreach($plan->features as $feature)
                                        <li class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            {{ $feature }}
                                        </li>
                                    @endforeach
                                @endif
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Invoice History --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Riwayat Invoice</h3>
            </div>
            @if($invoices->count() > 0)
                <div class="overflow-x-auto">
                    <x-table>
                        <x-slot name="header">
                            <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 uppercase tracking-wider">No. Invoice</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 uppercase tracking-wider">Tanggal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 uppercase tracking-wider">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500 uppercase tracking-wider">Aksi</th>
                        </x-slot>
                        @foreach($invoices as $invoice)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap font-medium">{{ $invoice->invoice_number }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-secondary-500">{{ $invoice->issued_at->format('d M Y') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $invoice->formatted_total }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($invoice->isPaid())
                                        <x-badge type="success">Lunas</x-badge>
                                    @elseif($invoice->isOverdue())
                                        <x-badge type="danger">Jatuh Tempo</x-badge>
                                    @elseif($invoice->isPending())
                                        <x-badge type="warning">Menunggu</x-badge>
                                    @else
                                        <x-badge type="secondary">{{ $invoice->status_label }}</x-badge>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <a href="{{ route('settings.billing.invoice', $invoice) }}" class="btn btn-ghost btn-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        Lihat
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </x-table>
                </div>
            @else
                <div class="card-body">
                    <div class="text-center py-8 text-secondary-500">
                        <svg class="w-12 h-12 mx-auto text-secondary-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p>Belum ada riwayat invoice.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
