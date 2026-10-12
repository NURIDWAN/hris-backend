@extends('superadmin.layouts.app')

@section('title', 'Riwayat Pembayaran')

@section('breadcrumb')
    <span class="text-secondary-900 font-medium">Pembayaran</span>
@endsection

@section('header')
    <h1 class="text-2xl font-bold text-secondary-900">Riwayat Pembayaran</h1>
    <p class="text-secondary-500 mt-1">Semua transaksi pembayaran</p>
@endsection

@section('content')
    {{-- Filter Card --}}
    <div class="card mb-6">
        <div class="card-body-sm">
            <form action="{{ route('superadmin.payments.index') }}" method="GET" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <input type="text" name="search" value="{{ request('search') }}"
                           class="input w-full" placeholder="Cari no. pembayaran atau perusahaan...">
                </div>
                <div class="w-36">
                    <select name="status" class="input w-full">
                        <option value="">Semua Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="success" {{ request('status') == 'success' ? 'selected' : '' }}>Success</option>
                        <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                        <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                        <option value="refunded" {{ request('status') == 'refunded' ? 'selected' : '' }}>Refunded</option>
                    </select>
                </div>
                <div class="w-36">
                    <select name="gateway" class="input w-full">
                        <option value="">Semua Gateway</option>
                        <option value="midtrans" {{ request('gateway') == 'midtrans' ? 'selected' : '' }}>Midtrans</option>
                        <option value="xendit" {{ request('gateway') == 'xendit' ? 'selected' : '' }}>Xendit</option>
                        <option value="doku" {{ request('gateway') == 'doku' ? 'selected' : '' }}>DOKU</option>
                        <option value="manual" {{ request('gateway') == 'manual' ? 'selected' : '' }}>Manual</option>
                    </select>
                </div>
                <div class="w-36">
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                           class="input w-full" placeholder="Dari tanggal">
                </div>
                <div class="w-36">
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                           class="input w-full" placeholder="Sampai tanggal">
                </div>
                <button type="submit" class="btn btn-primary">Filter</button>
                @if(request()->hasAny(['search', 'status', 'gateway', 'date_from', 'date_to']))
                    <a href="{{ route('superadmin.payments.index') }}" class="btn btn-secondary">Reset</a>
                @endif
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body-sm">
            <x-table>
                <x-slot name="header">
                    <th class="px-6 py-3 text-left">No. Pembayaran</th>
                    <th class="px-6 py-3 text-left">Perusahaan</th>
                    <th class="px-6 py-3 text-left">Gateway</th>
                    <th class="px-6 py-3 text-right">Jumlah</th>
                    <th class="px-6 py-3 text-center">Status</th>
                    <th class="px-6 py-3 text-left">Tanggal</th>
                    <th class="px-6 py-3 text-center">Aksi</th>
                </x-slot>

                @forelse($payments as $payment)
                    <tr class="hover:bg-secondary-50">
                        <td class="px-6 py-4">
                            <span class="font-mono text-sm">{{ $payment->payment_number }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <p class="font-medium text-secondary-900">{{ $payment->company->name ?? 'N/A' }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <span class="capitalize">{{ $payment->gateway }}</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="font-semibold">Rp {{ number_format($payment->amount, 0, ',', '.') }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @php
                                $statusType = match($payment->status) {
                                    'success' => 'success',
                                    'pending' => 'warning',
                                    'failed', 'expired' => 'danger',
                                    'refunded' => 'info',
                                    default => 'secondary'
                                };
                            @endphp
                            <x-badge :type="$statusType">
                                {{ ucfirst($payment->status) }}
                            </x-badge>
                        </td>
                        <td class="px-6 py-4">
                            {{ $payment->created_at->format('d M Y H:i') }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('superadmin.payments.show', $payment) }}" class="btn btn-ghost btn-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-secondary-500">
                            Belum ada riwayat pembayaran
                        </td>
                    </tr>
                @endforelse
            </x-table>
        </div>

        @if($payments->hasPages())
            <div class="card-footer">
                {{ $payments->links() }}
            </div>
        @endif
    </div>
@endsection
