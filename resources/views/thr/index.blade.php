@extends('layouts.admin')

@section('title', 'THR')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Pajak & BPJS</span>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">THR</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Tunjangan Hari Raya (THR)</h1>
            <p class="text-secondary-500 mt-1">Kelola pembayaran THR karyawan.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('thr-settings.index') }}" class="btn btn-secondary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Pengaturan
            </a>
            <a href="{{ route('thr.calculate') }}" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                Hitung THR
            </a>
        </div>
    </div>
@endsection

@section('content')
    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body-sm">
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-[180px]">
                    <label class="block text-xs font-medium text-secondary-500 mb-1">Cari</label>
                    <input type="text" name="search" placeholder="Nama karyawan..." value="{{ request('search') }}" class="input w-full">
                </div>
                <div class="w-28">
                    <label class="block text-xs font-medium text-secondary-500 mb-1">Tahun</label>
                    <select name="year" class="input w-full">
                        @foreach($years as $y)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-36">
                    <label class="block text-xs font-medium text-secondary-500 mb-1">Hari Raya</label>
                    <select name="religious_holiday" class="input w-full">
                        <option value="">Semua</option>
                        <option value="idul_fitri" {{ request('religious_holiday') === 'idul_fitri' ? 'selected' : '' }}>Idul Fitri</option>
                        <option value="natal" {{ request('religious_holiday') === 'natal' ? 'selected' : '' }}>Natal</option>
                        <option value="nyepi" {{ request('religious_holiday') === 'nyepi' ? 'selected' : '' }}>Nyepi</option>
                        <option value="waisak" {{ request('religious_holiday') === 'waisak' ? 'selected' : '' }}>Waisak</option>
                        <option value="imlek" {{ request('religious_holiday') === 'imlek' ? 'selected' : '' }}>Imlek</option>
                    </select>
                </div>
                <div class="w-32">
                    <label class="block text-xs font-medium text-secondary-500 mb-1">Status</label>
                    <select name="status" class="input w-full">
                        <option value="">Semua</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Menunggu</option>
                        <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Dibayar</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    @if(request()->hasAny(['search', 'religious_holiday', 'status']) || request('year') != now()->year)
                        <a href="{{ route('thr.index') }}" class="btn btn-ghost btn-sm">Reset</a>
                    @endif
                    <button type="submit" class="btn btn-primary btn-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        Cari
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Payments Table --}}
    <div class="card">
        <x-table>
            <x-slot name="header">
                <th>Karyawan</th>
                <th>Departemen</th>
                <th>Masa Kerja</th>
                <th>Hari Raya</th>
                <th class="text-right">Jumlah THR</th>
                <th>Status</th>
                <th class="text-center">Aksi</th>
            </x-slot>

            @forelse($payments as $payment)
                <tr>
                    <td>
                        <div>
                            <p class="font-medium text-secondary-900">{{ $payment->employee->full_name }}</p>
                            <p class="text-xs text-secondary-400">{{ $payment->employee->employee_number ?? $payment->employee->employee_id }}</p>
                        </div>
                    </td>
                    <td class="text-secondary-600">{{ $payment->employee->department?->name ?? '-' }}</td>
                    <td>
                        <span class="text-secondary-700">{{ $payment->service_months }}</span>
                        <span class="text-secondary-400 text-xs">bulan</span>
                    </td>
                    <td class="text-secondary-600">{{ $payment->religious_holiday_label }}</td>
                    <td class="text-right font-medium text-secondary-900">{{ $payment->formatted_amount }}</td>
                    <td>
                        @if($payment->isPending())
                            <x-badge type="warning">{{ $payment->status_label }}</x-badge>
                        @elseif($payment->isPaid())
                            <x-badge type="success">{{ $payment->status_label }}</x-badge>
                        @else
                            <x-badge type="secondary">{{ $payment->status_label }}</x-badge>
                        @endif
                    </td>
                    <td>
                        <div class="flex items-center justify-center gap-1">
                            @if($payment->isPending())
                                <form action="{{ route('thr.pay', $payment) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="p-1.5 text-secondary-400 hover:text-success-600 hover:bg-success-50 rounded-md transition-colors" title="Bayar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </button>
                                </form>
                                <form action="{{ route('thr.cancel', $payment) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="p-1.5 text-secondary-400 hover:text-danger-600 hover:bg-danger-50 rounded-md transition-colors" title="Batalkan">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </form>
                            @else
                                <span class="text-secondary-400">-</span>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-12">
                        <div class="flex flex-col items-center">
                            <svg class="w-12 h-12 text-secondary-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-secondary-500 mb-4">Belum ada data pembayaran THR untuk tahun {{ $year }}.</p>
                            <a href="{{ route('thr.calculate') }}" class="btn btn-primary">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                                Hitung THR Sekarang
                            </a>
                        </div>
                    </td>
                </tr>
            @endforelse
        </x-table>

        @if($payments->hasPages())
            <div class="card-footer">
                {{ $payments->links() }}
            </div>
        @endif
    </div>
@endsection
