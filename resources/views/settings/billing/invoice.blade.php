@extends('layouts.admin')

@section('title', 'Invoice ' . $payment->payment_number)

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Pengaturan</span>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <a href="{{ route('settings.billing.index') }}" class="text-slate-500 hover:text-primary-600">Billing</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">{{ $payment->payment_number }}</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">{{ $payment->payment_number }}</h1>
            <p class="text-secondary-500 mt-1">Detail invoice pembayaran.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('settings.billing.index') }}" class="btn btn-ghost">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Kembali
            </a>
            <button onclick="window.print()" class="btn btn-secondary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Cetak
            </button>
        </div>
    </div>
@endsection

@section('content')
    <div class="card max-w-3xl mx-auto">
        <div class="card-body p-8">
            {{-- Header --}}
            <div class="flex items-start justify-between mb-8">
                <div>
                    <h2 class="text-2xl font-bold text-primary-600">GajiPro</h2>
                    <p class="text-sm text-secondary-500 mt-1">HRIS & Payroll System</p>
                </div>
                <div class="text-right">
                    <div class="text-xl font-bold text-secondary-900">INVOICE</div>
                    <div class="text-sm text-secondary-500"># {{ $payment->payment_number }}</div>
                </div>
            </div>

            {{-- Info --}}
            <div class="grid grid-cols-2 gap-8 mb-8">
                <div>
                    <div class="text-sm text-secondary-500 mb-1">Ditagihkan kepada</div>
                    <div class="font-semibold text-secondary-900">{{ $payment->company->name }}</div>
                    <div class="text-sm text-secondary-600">{{ $payment->company->email }}</div>
                    @if($payment->company->address)
                        <div class="text-sm text-secondary-600 mt-1">{{ $payment->company->address }}</div>
                    @endif
                </div>
                <div class="text-right">
                    <div class="mb-3">
                        <div class="text-sm text-secondary-500">Tanggal</div>
                        <div class="font-medium text-secondary-900">{{ $payment->created_at->format('d M Y') }}</div>
                    </div>
                    <div class="mb-3">
                        <div class="text-sm text-secondary-500">Jatuh Tempo</div>
                        <div class="font-medium text-secondary-900">{{ $payment->expired_at ? $payment->expired_at->format('d M Y') : '-' }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-secondary-500">Status</div>
                        @if($payment->status === 'success')
                            <x-badge type="success">Lunas</x-badge>
                        @elseif($payment->status === 'failed')
                            <x-badge type="danger">Gagal</x-badge>
                        @elseif($payment->status === 'pending')
                            <x-badge type="warning">Menunggu Pembayaran</x-badge>
                        @else
                            <x-badge type="secondary">{{ $payment->status_label ?? ucfirst($payment->status) }}</x-badge>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Items --}}
            <table class="w-full mb-8">
                <thead>
                    <tr class="border-b border-secondary-200">
                        <th class="py-3 text-left text-sm font-medium text-secondary-500">Deskripsi</th>
                        <th class="py-3 text-right text-sm font-medium text-secondary-500">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="border-b border-secondary-100">
                        <td class="py-4">
                            <div class="font-medium text-secondary-900">{{ $payment->description }}</div>
                            @if($payment->subscription && $payment->subscription->plan)
                                <div class="text-sm text-secondary-500">
                                    Paket {{ $payment->subscription->plan->name }} -
                                    {{ ucfirst($payment->billing_cycle) }}
                                </div>
                            @endif
                        </td>
                        <td class="py-4 text-right font-medium">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>

            {{-- Totals --}}
            <div class="border-t border-secondary-200 pt-4">
                <div class="flex justify-end">
                    <div class="w-64">
                        <div class="flex justify-between py-2">
                            <span class="text-secondary-500">Subtotal</span>
                            <span class="font-medium">Rp {{ number_format($payment->amount, 0, ',', '.') }}</span>
                        </div>
                        @if($payment->fee > 0)
                            <div class="flex justify-between py-2">
                                <span class="text-secondary-500">Biaya Admin</span>
                                <span class="font-medium">Rp {{ number_format($payment->fee, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between py-3 border-t border-secondary-200 mt-2">
                            <span class="font-semibold text-secondary-900">Total</span>
                            <span class="font-bold text-lg text-secondary-900">{{ $payment->formatted_amount ?? 'Rp ' . number_format($payment->amount, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Payment Info --}}
            @if($payment->status === 'success')
                <div class="mt-8 p-4 bg-success-50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <svg class="w-6 h-6 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <div class="font-medium text-success-800">Pembayaran diterima</div>
                            <div class="text-sm text-success-600">
                                {{ $payment->paid_at ? $payment->paid_at->format('d M Y, H:i') : $payment->updated_at->format('d M Y, H:i') }}
                                via {{ ucfirst($payment->gateway) }}
                            </div>
                        </div>
                    </div>
                </div>
            @elseif($payment->status === 'pending' && $payment->gateway === 'manual' && $gatewaySetting)
                <div class="mt-8 p-4 bg-warning-50 rounded-lg">
                    <div class="flex items-start gap-3">
                        <svg class="w-6 h-6 text-warning-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <div class="font-medium text-warning-800">Menunggu Pembayaran</div>
                            <div class="text-sm text-warning-600 mt-1">
                                Silakan transfer ke rekening berikut:
                            </div>
                            <div class="mt-2 text-sm">
                                @php $credentials = $gatewaySetting->credentials ?? []; @endphp
                                <div><strong>Bank:</strong> {{ $credentials['bank_name'] ?? '-' }}</div>
                                <div><strong>No. Rekening:</strong> {{ $credentials['account_number'] ?? '-' }}</div>
                                <div><strong>Atas Nama:</strong> {{ $credentials['account_name'] ?? '-' }}</div>
                            </div>
                            <div class="mt-3 text-sm text-warning-700">
                                <strong>Total Transfer:</strong> Rp {{ number_format($payment->amount, 0, ',', '.') }}
                            </div>
                        </div>
                    </div>
                </div>
            @elseif($payment->status === 'pending')
                <div class="mt-8 p-4 bg-info-50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <svg class="w-6 h-6 text-info-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <div class="font-medium text-info-800">Menunggu Pembayaran</div>
                            <div class="text-sm text-info-600">
                                Pembayaran melalui {{ ucfirst($payment->gateway) }} sedang diproses.
                            </div>
                        </div>
                    </div>
                </div>
            @elseif($payment->status === 'failed')
                <div class="mt-8 p-4 bg-danger-50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <svg class="w-6 h-6 text-danger-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <div class="font-medium text-danger-800">Pembayaran Gagal</div>
                            <div class="text-sm text-danger-600">
                                Pembayaran tidak berhasil diproses.
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
