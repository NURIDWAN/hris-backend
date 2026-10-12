@extends('layouts.admin')

@section('title', 'Detail Pengajuan Lembur')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Lembur</span>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <a href="{{ route('overtime-requests.index') }}" class="text-primary-600 hover:text-primary-700">Pengajuan Lembur</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Detail</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Detail Pengajuan Lembur</h1>
            <p class="text-secondary-500 mt-1">Informasi lengkap pengajuan lembur karyawan.</p>
        </div>
        <a href="{{ route('overtime-requests.index') }}" class="btn btn-secondary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali
        </a>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Info --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Employee & Request Info --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Informasi Pengajuan</h3>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-secondary-500 mb-1">Karyawan</label>
                            <p class="text-secondary-900 font-medium">{{ $overtimeRequest->employee->full_name }}</p>
                            <p class="text-sm text-secondary-500">{{ $overtimeRequest->employee->employee_id }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-secondary-500 mb-1">Departemen</label>
                            <p class="text-secondary-900">{{ $overtimeRequest->employee->department->name ?? '-' }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-secondary-500 mb-1">Tanggal Lembur</label>
                            <p class="text-secondary-900 font-medium">{{ $overtimeRequest->date->format('l, d F Y') }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-secondary-500 mb-1">Waktu</label>
                            <p class="text-secondary-900">
                                {{ \Carbon\Carbon::parse($overtimeRequest->start_time)->format('H:i') }} -
                                {{ \Carbon\Carbon::parse($overtimeRequest->end_time)->format('H:i') }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-secondary-500 mb-1">Durasi</label>
                            <p class="text-secondary-900 font-medium">{{ $overtimeRequest->overtime_hours }} jam</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-secondary-500 mb-1">Tipe Lembur</label>
                            <x-badge type="{{ $overtimeRequest->overtime_type == 'holiday' ? 'danger' : ($overtimeRequest->overtime_type == 'weekend' ? 'warning' : 'info') }}">
                                {{ $overtimeRequest->overtime_type_label }}
                            </x-badge>
                        </div>
                    </div>

                    <div class="mt-6 pt-6 border-t">
                        <label class="block text-sm font-medium text-secondary-500 mb-2">Alasan Lembur</label>
                        <p class="text-secondary-900">{{ $overtimeRequest->reason }}</p>
                    </div>

                    @if($overtimeRequest->notes)
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-secondary-500 mb-2">Catatan Tambahan</label>
                            <p class="text-secondary-900">{{ $overtimeRequest->notes }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Approval History --}}
            @if($overtimeRequest->approved_by || $overtimeRequest->rejected_by)
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Riwayat Persetujuan</h3>
                    </div>
                    <div class="card-body">
                        @if($overtimeRequest->isApproved())
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-full bg-success-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium text-secondary-900">Disetujui oleh {{ $overtimeRequest->approver->name ?? 'Admin' }}</p>
                                    <p class="text-sm text-secondary-500">{{ $overtimeRequest->approved_at?->format('d M Y, H:i') }}</p>
                                </div>
                            </div>
                        @elseif($overtimeRequest->isRejected())
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-full bg-danger-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-danger-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium text-secondary-900">Ditolak oleh {{ $overtimeRequest->rejecter->name ?? 'Admin' }}</p>
                                    <p class="text-sm text-secondary-500">{{ $overtimeRequest->rejected_at?->format('d M Y, H:i') }}</p>
                                    @if($overtimeRequest->rejection_reason)
                                        <p class="mt-2 text-secondary-700">Alasan: {{ $overtimeRequest->rejection_reason }}</p>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Status Card --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Status</h3>
                </div>
                <div class="card-body text-center">
                    <div class="mb-4">
                        @if($overtimeRequest->isPending())
                            <div class="w-16 h-16 mx-auto rounded-full bg-warning-100 flex items-center justify-center">
                                <svg class="w-8 h-8 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        @elseif($overtimeRequest->isApproved())
                            <div class="w-16 h-16 mx-auto rounded-full bg-success-100 flex items-center justify-center">
                                <svg class="w-8 h-8 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        @elseif($overtimeRequest->isRejected())
                            <div class="w-16 h-16 mx-auto rounded-full bg-danger-100 flex items-center justify-center">
                                <svg class="w-8 h-8 text-danger-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        @else
                            <div class="w-16 h-16 mx-auto rounded-full bg-secondary-100 flex items-center justify-center">
                                <svg class="w-8 h-8 text-secondary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                </svg>
                            </div>
                        @endif
                    </div>
                    <x-badge type="{{ match($overtimeRequest->status) { 'approved' => 'success', 'rejected' => 'danger', 'cancelled' => 'secondary', default => 'warning' } }}">
                        {{ $overtimeRequest->status_label }}
                    </x-badge>
                    <p class="text-sm text-secondary-500 mt-2">
                        Diajukan {{ $overtimeRequest->created_at->diffForHumans() }}
                    </p>
                </div>
            </div>

            {{-- Calculation Card --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Perhitungan</h3>
                </div>
                <div class="card-body">
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-secondary-500">Durasi</span>
                            <span class="font-medium">{{ $overtimeRequest->overtime_hours }} jam</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-secondary-500">Tipe</span>
                            <span class="font-medium">{{ $overtimeRequest->overtime_type_label }}</span>
                        </div>
                        <div class="pt-3 border-t">
                            <div class="flex justify-between">
                                <span class="text-secondary-700 font-medium">Nilai Lembur</span>
                                <span class="text-lg font-bold text-primary-600">{{ $overtimeRequest->formatted_overtime_amount }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            @if($overtimeRequest->isPending())
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Aksi</h3>
                    </div>
                    <div class="card-body space-y-3">
                        <form action="{{ route('overtime-requests.approve', $overtimeRequest) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-success w-full">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Setujui Pengajuan
                            </button>
                        </form>
                        <button type="button"
                                @click="$dispatch('confirm-dialog', {
                                    title: 'Tolak Pengajuan',
                                    message: 'Apakah Anda yakin ingin menolak pengajuan lembur ini?',
                                    confirmText: 'Ya, Tolak',
                                    type: 'danger',
                                    formAction: '{{ route('overtime-requests.reject', $overtimeRequest) }}',
                                    showReasonField: true
                                })"
                                class="btn btn-danger w-full">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Tolak Pengajuan
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
