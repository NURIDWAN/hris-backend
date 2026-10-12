@extends('layouts.admin')

@section('title', 'Detail Payroll')

@section('breadcrumb')
    <a href="{{ route('payrolls.index') }}" class="text-slate-500 hover:text-primary-600">Payroll</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">{{ $payroll->name }}</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('payrolls.index') }}" class="btn btn-ghost btn-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">{{ $payroll->name }}</h1>
                <div class="flex items-center gap-2 mt-1">
                    <span class="text-secondary-500">{{ $payroll->payroll_number }}</span>
                    <span class="text-secondary-300">|</span>
                    @switch($payroll->status)
                        @case('draft')
                            <x-badge type="secondary">{{ $payroll->status_label }}</x-badge>
                            @break
                        @case('processing')
                            <x-badge type="info">{{ $payroll->status_label }}</x-badge>
                            @break
                        @case('calculated')
                            <x-badge type="warning">{{ $payroll->status_label }}</x-badge>
                            @break
                        @case('approved')
                            <x-badge type="primary">{{ $payroll->status_label }}</x-badge>
                            @break
                        @case('paid')
                            <x-badge type="success">{{ $payroll->status_label }}</x-badge>
                            @break
                        @case('cancelled')
                            <x-badge type="danger">{{ $payroll->status_label }}</x-badge>
                            @break
                    @endswitch
                </div>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if($payroll->canBeCalculated())
                <button type="button" class="btn btn-primary"
                    @click="$dispatch('confirm-dialog', {
                        title: 'Proses Payroll',
                        message: 'Sistem akan menghitung gaji semua karyawan berdasarkan data gaji dan kehadiran. Lanjutkan?',
                        confirmText: 'Ya, Proses',
                        type: 'primary',
                        method: 'POST',
                        formAction: '{{ route('payrolls.process', $payroll) }}'
                    })">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    Proses Gaji
                </button>
            @endif
            @if($payroll->canBeApproved())
                <button type="button" class="btn btn-success"
                    @click="$dispatch('confirm-dialog', {
                        title: 'Setujui Payroll',
                        message: 'Apakah Anda yakin ingin menyetujui payroll ini? Setelah disetujui, data gaji tidak dapat diubah.',
                        confirmText: 'Ya, Setujui',
                        type: 'primary',
                        method: 'POST',
                        formAction: '{{ route('payrolls.approve', $payroll) }}'
                    })">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Setujui
                </button>
            @endif
            @if($payroll->canBePaid())
                <button type="button" class="btn btn-success"
                    @click="$dispatch('confirm-dialog', {
                        title: 'Tandai Dibayar',
                        message: 'Apakah Anda yakin ingin menandai payroll ini sebagai sudah dibayar?',
                        confirmText: 'Ya, Tandai Dibayar',
                        type: 'primary',
                        method: 'POST',
                        formAction: '{{ route('payrolls.pay', $payroll) }}'
                    })">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    Tandai Dibayar
                </button>
            @endif
            @if(in_array($payroll->status, ['calculated', 'approved', 'paid']))
                <a href="{{ route('payrolls.export-bank', $payroll) }}" class="btn btn-secondary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Export Bank
                </a>
            @endif
            @if($payroll->canBeEdited())
                <a href="{{ route('payrolls.edit', $payroll) }}" class="btn btn-ghost">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Edit
                </a>
            @endif
            @if($payroll->canBeCancelled())
                <button type="button" class="btn btn-danger"
                    @click="$dispatch('confirm-dialog', {
                        title: 'Batalkan Payroll',
                        message: 'Apakah Anda yakin ingin membatalkan payroll ini? Tindakan ini tidak dapat dibatalkan.',
                        confirmText: 'Ya, Batalkan',
                        type: 'danger',
                        method: 'POST',
                        formAction: '{{ route('payrolls.cancel', $payroll) }}'
                    })">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Batalkan
                </button>
            @endif
        </div>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Summary Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div class="card">
                    <div class="card-body text-center">
                        <p class="text-sm text-secondary-500 mb-1">Total Karyawan</p>
                        <p class="text-2xl font-bold text-secondary-900">{{ $payroll->total_employees }}</p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body text-center">
                        <p class="text-sm text-success-600 mb-1">Total Pendapatan</p>
                        <p class="text-xl font-bold text-success-700">{{ $payroll->formatted_total_earnings }}</p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body text-center">
                        <p class="text-sm text-danger-600 mb-1">Total Potongan</p>
                        <p class="text-xl font-bold text-danger-700">{{ $payroll->formatted_total_deductions }}</p>
                    </div>
                </div>
                <div class="card bg-primary-50">
                    <div class="card-body text-center">
                        <p class="text-sm text-primary-600 mb-1">Gaji Bersih</p>
                        <p class="text-xl font-bold text-primary-700">{{ $payroll->formatted_total_net_salary }}</p>
                    </div>
                </div>
            </div>

            {{-- Payroll Items --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Daftar Gaji Karyawan</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-secondary-50 border-b border-secondary-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-secondary-500 uppercase tracking-wider">Karyawan</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-secondary-500 uppercase tracking-wider">Departemen</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-secondary-500 uppercase tracking-wider">Gaji Pokok</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-secondary-500 uppercase tracking-wider">Pendapatan</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-secondary-500 uppercase tracking-wider">Potongan</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-secondary-500 uppercase tracking-wider">Gaji Bersih</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-secondary-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-secondary-200">
                            @forelse($payroll->items as $item)
                                <tr class="hover:bg-secondary-50 transition-colors">
                                    <td class="px-4 py-3">
                                        <div>
                                            <p class="font-medium text-secondary-900">{{ $item->employee_name }}</p>
                                            <p class="text-sm text-secondary-500">{{ $item->employee_number }}</p>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="text-secondary-600">{{ $item->department_name ?? '-' }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="text-secondary-900">Rp {{ number_format($item->basic_salary, 0, ',', '.') }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="text-success-600">+ Rp {{ number_format($item->total_earnings, 0, ',', '.') }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="text-danger-600">- Rp {{ number_format($item->total_deductions, 0, ',', '.') }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="font-bold text-secondary-900">Rp {{ number_format($item->net_salary, 0, ',', '.') }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <a href="{{ route('payroll-items.show', $item) }}" class="btn btn-ghost btn-sm" title="Lihat Slip Gaji">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            </a>
                                            @if($payroll->canBeAdjusted())
                                                <a href="{{ route('payroll-items.edit', $item) }}" class="btn btn-ghost btn-sm text-accent-600" title="Adjust Gaji">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <svg class="w-12 h-12 text-secondary-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/></svg>
                                            <p class="text-secondary-500 mb-4">Belum ada data gaji karyawan</p>
                                            @if($payroll->canBeCalculated())
                                                <button type="button" class="btn btn-primary"
                                                    @click="$dispatch('confirm-dialog', {
                                                        title: 'Proses Payroll',
                                                        message: 'Sistem akan menghitung gaji semua karyawan berdasarkan data gaji dan kehadiran. Lanjutkan?',
                                                        confirmText: 'Ya, Proses',
                                                        type: 'primary',
                                                        method: 'POST',
                                                        formAction: '{{ route('payrolls.process', $payroll) }}'
                                                    })">
                                                    Proses Gaji Sekarang
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Period Info --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Informasi Periode</h3>
                </div>
                <div class="card-body">
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-secondary-500">Periode</dt>
                            <dd class="text-secondary-900 font-medium">{{ $payroll->period_label }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-secondary-500">Tanggal Mulai</dt>
                            <dd class="text-secondary-900">{{ $payroll->period_start->format('d M Y') }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-secondary-500">Tanggal Akhir</dt>
                            <dd class="text-secondary-900">{{ $payroll->period_end->format('d M Y') }}</dd>
                        </div>
                        @if($payroll->payment_date)
                            <div class="flex justify-between">
                                <dt class="text-secondary-500">Tanggal Bayar</dt>
                                <dd class="text-secondary-900">{{ $payroll->payment_date->format('d M Y') }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>

            {{-- Status History --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Riwayat Status</h3>
                </div>
                <div class="card-body">
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-secondary-500">Dibuat oleh</dt>
                            <dd class="text-secondary-900">{{ $payroll->createdBy?->name ?? '-' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-secondary-500">Tanggal Dibuat</dt>
                            <dd class="text-secondary-900">{{ $payroll->created_at->format('d M Y H:i') }}</dd>
                        </div>
                        @if($payroll->approvedBy)
                            <div class="flex justify-between">
                                <dt class="text-secondary-500">Disetujui oleh</dt>
                                <dd class="text-secondary-900">{{ $payroll->approvedBy->name }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-secondary-500">Tanggal Disetujui</dt>
                                <dd class="text-secondary-900">{{ $payroll->approved_at->format('d M Y H:i') }}</dd>
                            </div>
                        @endif
                        @if($payroll->paidBy)
                            <div class="flex justify-between">
                                <dt class="text-secondary-500">Dibayar oleh</dt>
                                <dd class="text-secondary-900">{{ $payroll->paidBy->name }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-secondary-500">Tanggal Dibayar</dt>
                                <dd class="text-secondary-900">{{ $payroll->paid_at->format('d M Y H:i') }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>

            {{-- Notes --}}
            @if($payroll->notes)
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Catatan</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-sm text-secondary-600">{{ $payroll->notes }}</p>
                    </div>
                </div>
            @endif

            {{-- Danger Zone --}}
            @if($payroll->canBeCancelled())
                <div class="card border-danger-200">
                    <div class="card-header bg-danger-50">
                        <h3 class="card-title text-danger-700">Zona Berbahaya</h3>
                    </div>
                    <div class="card-body space-y-3">
                        <button
                            type="button"
                            @click="$dispatch('confirm-dialog', {
                                title: 'Hapus Payroll',
                                message: 'Apakah Anda yakin ingin menghapus payroll ini? Tindakan ini tidak dapat dibatalkan.',
                                confirmText: 'Ya, Hapus',
                                type: 'danger',
                                formAction: '{{ route('payrolls.destroy', $payroll) }}'
                            })"
                            class="btn btn-danger w-full"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Hapus Payroll
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
