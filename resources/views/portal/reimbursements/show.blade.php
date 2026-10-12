@extends('layouts.portal')

@section('title', 'Detail Reimbursement')

@section('content')
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex items-center gap-4">
            <a href="{{ route('portal.reimbursements.index') }}" class="btn btn-ghost btn-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">Detail Reimbursement</h1>
                <p class="text-secondary-500 mt-1">Informasi lengkap pengajuan reimbursement.</p>
            </div>
        </div>

        {{-- Info Card --}}
        <div class="card">
            <div class="card-header">
                <div class="flex items-center justify-between">
                    <h3 class="card-title">Detail Pengajuan</h3>
                    <x-badge type="{{ match($reimbursement->status) { 'approved' => 'info', 'rejected' => 'danger', 'paid' => 'success', default => 'warning' } }}">
                        {{ $reimbursement->status_label }}
                    </x-badge>
                </div>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div>
                            <p class="text-sm text-secondary-500">Kategori</p>
                            <p class="font-medium text-secondary-900">{{ $reimbursement->category->name }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-secondary-500">Tanggal Pengeluaran</p>
                            <p class="font-medium text-secondary-900">{{ $reimbursement->expense_date->format('d F Y') }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-secondary-500">Tanggal Pengajuan</p>
                            <p class="font-medium text-secondary-900">{{ $reimbursement->created_at->format('d F Y H:i') }}</p>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <p class="text-sm text-secondary-500">Jumlah</p>
                            <p class="text-2xl font-bold text-primary-600">{{ $reimbursement->formatted_amount }}</p>
                        </div>
                        @if($reimbursement->paid_at)
                            <div>
                                <p class="text-sm text-secondary-500">Tanggal Pembayaran</p>
                                <p class="font-medium text-success-600">{{ $reimbursement->paid_at->format('d F Y') }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="mt-6 pt-6 border-t border-secondary-200">
                    <p class="text-sm text-secondary-500 mb-1">Deskripsi</p>
                    <p class="text-secondary-900">{{ $reimbursement->description }}</p>
                </div>

                @if($reimbursement->rejection_reason)
                    <div class="mt-4 bg-danger-50 border border-danger-200 rounded-lg p-4">
                        <p class="text-sm font-medium text-danger-800">Alasan Penolakan:</p>
                        <p class="text-danger-700">{{ $reimbursement->rejection_reason }}</p>
                    </div>
                @endif

                @if($reimbursement->receipt_path)
                    <div class="mt-6 pt-6 border-t border-secondary-200">
                        <p class="text-sm text-secondary-500 mb-2">Bukti/Struk</p>
                        <a href="{{ Storage::url($reimbursement->receipt_path) }}" target="_blank" class="inline-block">
                            <img src="{{ Storage::url($reimbursement->receipt_path) }}" alt="Receipt" class="max-w-sm rounded-lg border shadow-sm">
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
