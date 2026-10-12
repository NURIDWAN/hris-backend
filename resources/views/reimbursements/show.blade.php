@extends('layouts.admin')

@section('title', 'Detail Reimbursement')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Keuangan</span>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <a href="{{ route('reimbursements.index') }}" class="text-primary-600 hover:underline">Reimbursement</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Detail</span>
@endsection

@section('content')
    <div class="max-w-4xl space-y-6">
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
                            <p class="text-sm text-secondary-500">Karyawan</p>
                            <p class="font-medium text-secondary-900">{{ $reimbursement->employee->full_name }}</p>
                            <p class="text-sm text-secondary-600">{{ $reimbursement->employee->department?->name }} - {{ $reimbursement->employee->position?->name }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-secondary-500">Kategori</p>
                            <p class="font-medium text-secondary-900">{{ $reimbursement->category->name }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-secondary-500">Tanggal Pengeluaran</p>
                            <p class="font-medium text-secondary-900">{{ $reimbursement->expense_date->format('d F Y') }}</p>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <p class="text-sm text-secondary-500">Jumlah</p>
                            <p class="text-2xl font-bold text-primary-600">{{ $reimbursement->formatted_amount }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-secondary-500">Tanggal Pengajuan</p>
                            <p class="font-medium text-secondary-900">{{ $reimbursement->created_at->format('d F Y H:i') }}</p>
                        </div>
                        @if($reimbursement->approver)
                            <div>
                                <p class="text-sm text-secondary-500">Diproses Oleh</p>
                                <p class="font-medium text-secondary-900">{{ $reimbursement->approver->name }}</p>
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

        {{-- Actions --}}
        @if($reimbursement->isPending())
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Tindakan</h3>
                </div>
                <div class="card-body flex flex-wrap gap-3">
                    <form action="{{ route('reimbursements.approve', $reimbursement) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="btn btn-primary">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Setujui
                        </button>
                    </form>

                    <button type="button"
                            x-data
                            @click="$refs.rejectModal.showModal()"
                            class="btn btn-danger">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Tolak
                    </button>

                    <dialog x-ref="rejectModal" class="p-6 rounded-xl shadow-xl backdrop:bg-black/50 max-w-md w-full">
                        <form action="{{ route('reimbursements.reject', $reimbursement) }}" method="POST">
                            @csrf
                            <h3 class="text-lg font-semibold text-secondary-900 mb-4">Tolak Reimbursement</h3>
                            <div class="mb-4">
                                <label for="rejection_reason" class="block text-sm font-medium text-secondary-700 mb-1">
                                    Alasan Penolakan <span class="text-danger-500">*</span>
                                </label>
                                <textarea name="rejection_reason" id="rejection_reason" rows="3"
                                          class="input w-full" required></textarea>
                            </div>
                            <div class="flex justify-end gap-2">
                                <button type="button" onclick="this.closest('dialog').close()" class="btn btn-secondary">Batal</button>
                                <button type="submit" class="btn btn-danger">Tolak</button>
                            </div>
                        </form>
                    </dialog>
                </div>
            </div>
        @endif

        @if($reimbursement->isApproved())
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Pembayaran</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('reimbursements.pay', $reimbursement) }}" method="POST" class="flex flex-wrap items-end gap-4">
                        @csrf
                        <div>
                            <label for="payment_date" class="block text-sm font-medium text-secondary-700 mb-1">
                                Tanggal Bayar
                            </label>
                            <input type="date" name="payment_date" id="payment_date"
                                   value="{{ date('Y-m-d') }}"
                                   class="input" required>
                        </div>
                        <div>
                            <label for="payment_method" class="block text-sm font-medium text-secondary-700 mb-1">
                                Metode Pembayaran
                            </label>
                            <select name="payment_method" id="payment_method" class="input" required>
                                <option value="transfer">Transfer Bank</option>
                                <option value="cash">Tunai</option>
                                <option value="payroll">Potong Gaji</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            Bayar
                        </button>
                    </form>
                </div>
            </div>
        @endif

        <div class="flex gap-2">
            <a href="{{ route('reimbursements.index') }}" class="btn btn-secondary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Kembali
            </a>
        </div>
    </div>
@endsection
