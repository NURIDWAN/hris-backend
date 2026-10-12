@extends('layouts.admin')

@section('title', 'Detail Pengajuan Cuti')

@section('breadcrumb')
    <a href="{{ route('leave-requests.index') }}" class="text-slate-500 hover:text-primary-600">Pengajuan Cuti</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">{{ $leaveRequest->request_number }}</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Detail Pengajuan Cuti</h1>
            <p class="text-secondary-500 mt-1">{{ $leaveRequest->request_number }}</p>
        </div>
        <div class="flex items-center gap-3">
            @if($leaveRequest->isPending())
                <a href="{{ route('leave-requests.edit', $leaveRequest) }}" class="btn btn-ghost">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Edit
                </a>
            @endif
            <a href="{{ route('leave-requests.index') }}" class="btn btn-ghost">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Kembali
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Info --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Status Card --}}
            <div class="card">
                <div class="card-body">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-full bg-{{ $leaveRequest->status_color }}-100 text-{{ $leaveRequest->status_color }}-600 flex items-center justify-center">
                                @if($leaveRequest->isPending())
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                @elseif($leaveRequest->isApproved())
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                @elseif($leaveRequest->isRejected())
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                @else
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                @endif
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-secondary-900">{{ $leaveRequest->status_label }}</h3>
                                <p class="text-sm text-secondary-500">
                                    @if($leaveRequest->isApproved() && $leaveRequest->approvedBy)
                                        Disetujui oleh {{ $leaveRequest->approvedBy->name }} pada {{ $leaveRequest->approved_at->format('d M Y H:i') }}
                                    @elseif($leaveRequest->isRejected() && $leaveRequest->rejectedBy)
                                        Ditolak oleh {{ $leaveRequest->rejectedBy->name }} pada {{ $leaveRequest->rejected_at->format('d M Y H:i') }}
                                    @elseif($leaveRequest->isCancelled() && $leaveRequest->cancelledBy)
                                        Dibatalkan oleh {{ $leaveRequest->cancelledBy->name }} pada {{ $leaveRequest->cancelled_at->format('d M Y H:i') }}
                                    @else
                                        Menunggu persetujuan
                                    @endif
                                </p>
                            </div>
                        </div>
                        <x-badge type="{{ $leaveRequest->status_color }}">{{ $leaveRequest->status_label }}</x-badge>
                    </div>

                    @if($leaveRequest->isRejected() && $leaveRequest->rejection_reason)
                        <div class="mt-4 p-3 bg-danger-50 rounded-lg">
                            <p class="text-sm font-medium text-danger-800">Alasan Penolakan:</p>
                            <p class="text-sm text-danger-700">{{ $leaveRequest->rejection_reason }}</p>
                        </div>
                    @endif

                    @if($leaveRequest->isCancelled() && $leaveRequest->cancellation_reason)
                        <div class="mt-4 p-3 bg-secondary-50 rounded-lg">
                            <p class="text-sm font-medium text-secondary-800">Alasan Pembatalan:</p>
                            <p class="text-sm text-secondary-700">{{ $leaveRequest->cancellation_reason }}</p>
                        </div>
                    @endif

                    @if($leaveRequest->isApproved() && $leaveRequest->approval_notes)
                        <div class="mt-4 p-3 bg-success-50 rounded-lg">
                            <p class="text-sm font-medium text-success-800">Catatan Persetujuan:</p>
                            <p class="text-sm text-success-700">{{ $leaveRequest->approval_notes }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Employee Info --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Informasi Karyawan</h3>
                </div>
                <div class="card-body">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center font-semibold text-xl">
                            {{ strtoupper(substr($leaveRequest->employee->full_name, 0, 1)) }}
                        </div>
                        <div>
                            <h4 class="font-semibold text-secondary-900">{{ $leaveRequest->employee->full_name }}</h4>
                            <p class="text-sm text-secondary-500">{{ $leaveRequest->employee->employee_id }}</p>
                            @if($leaveRequest->employee->position)
                                <p class="text-sm text-secondary-500">{{ $leaveRequest->employee->position->name }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Leave Details --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Detail Cuti</h3>
                </div>
                <div class="card-body">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Jenis Cuti</dt>
                            <dd class="mt-1 flex items-center gap-2">
                                <div class="w-3 h-3 rounded-full bg-{{ $leaveRequest->leaveType->color_class }}-500"></div>
                                <span class="text-secondary-900">{{ $leaveRequest->leaveType->name }}</span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Durasi</dt>
                            <dd class="mt-1 text-secondary-900 font-semibold">{{ $leaveRequest->duration_text }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Tanggal Mulai</dt>
                            <dd class="mt-1 text-secondary-900">{{ $leaveRequest->start_date->format('d F Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Tanggal Selesai</dt>
                            <dd class="mt-1 text-secondary-900">{{ $leaveRequest->end_date->format('d F Y') }}</dd>
                        </div>
                        @if($leaveRequest->is_half_day)
                            <div>
                                <dt class="text-sm font-medium text-secondary-500">Waktu</dt>
                                <dd class="mt-1 text-secondary-900">{{ $leaveRequest->half_day_type === 'morning' ? 'Pagi (08:00 - 12:00)' : 'Siang (13:00 - 17:00)' }}</dd>
                            </div>
                        @endif
                        <div class="md:col-span-2">
                            <dt class="text-sm font-medium text-secondary-500">Alasan</dt>
                            <dd class="mt-1 text-secondary-900">{{ $leaveRequest->reason }}</dd>
                        </div>
                        @if($leaveRequest->emergency_contact)
                            <div>
                                <dt class="text-sm font-medium text-secondary-500">Kontak Darurat</dt>
                                <dd class="mt-1 text-secondary-900">{{ $leaveRequest->emergency_contact }}</dd>
                            </div>
                        @endif
                    </dl>

                    @if($leaveRequest->attachment)
                        <div class="mt-4 pt-4 border-t">
                            <dt class="text-sm font-medium text-secondary-500 mb-2">Lampiran</dt>
                            <a href="{{ Storage::url($leaveRequest->attachment) }}" target="_blank" class="inline-flex items-center gap-2 text-primary-600 hover:text-primary-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                Lihat Lampiran
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Quick Actions --}}
            @if($leaveRequest->isPending())
                @if($leaveRequest->hasWorkflow())
                    {{-- Workflow-based: only show if user can approve current step --}}
                    @if($canApproveCurrentStep)
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Aksi (Langkah {{ $leaveRequest->current_approval_step }})</h3>
                            </div>
                            <div class="card-body space-y-2">
                                <form action="{{ route('leave-requests.approve', $leaveRequest) }}" method="POST">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="notes" class="block text-sm font-medium text-secondary-700 mb-1">Catatan (Opsional)</label>
                                        <textarea name="notes" id="notes" rows="2" class="input w-full" placeholder="Tambahkan catatan..."></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-success w-full">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Setujui Langkah Ini
                                    </button>
                                </form>

                                <div x-data="{ showReject: false }">
                                    <button @click="showReject = !showReject" class="btn btn-danger w-full">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        Tolak
                                    </button>

                                    <form x-show="showReject" x-cloak action="{{ route('leave-requests.reject', $leaveRequest) }}" method="POST" class="mt-3">
                                        @csrf
                                        <div class="mb-3">
                                            <label for="reject_reason" class="block text-sm font-medium text-secondary-700 mb-1">Alasan Penolakan <span class="text-danger-500">*</span></label>
                                            <textarea name="reason" id="reject_reason" rows="2" class="input w-full" placeholder="Masukkan alasan..." required></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-danger w-full">Konfirmasi Tolak</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="card">
                            <div class="card-body">
                                <div class="flex items-center gap-3 text-warning-600">
                                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <p class="text-sm">Menunggu persetujuan langkah {{ $leaveRequest->current_approval_step }}</p>
                                </div>
                            </div>
                        </div>
                    @endif
                @else
                    {{-- Legacy: no workflow, show original approve/reject --}}
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Aksi</h3>
                        </div>
                        <div class="card-body space-y-2">
                            <form action="{{ route('leave-requests.approve', $leaveRequest) }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label for="notes" class="block text-sm font-medium text-secondary-700 mb-1">Catatan (Opsional)</label>
                                    <textarea name="notes" id="notes" rows="2" class="input w-full" placeholder="Tambahkan catatan..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-success w-full">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Setujui
                                </button>
                            </form>

                            <div x-data="{ showReject: false }">
                                <button @click="showReject = !showReject" class="btn btn-danger w-full">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    Tolak
                                </button>

                                <form x-show="showReject" x-cloak action="{{ route('leave-requests.reject', $leaveRequest) }}" method="POST" class="mt-3">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="reject_reason" class="block text-sm font-medium text-secondary-700 mb-1">Alasan Penolakan <span class="text-danger-500">*</span></label>
                                        <textarea name="reason" id="reject_reason" rows="2" class="input w-full" placeholder="Masukkan alasan..." required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-danger w-full">Konfirmasi Tolak</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif
            @endif

            @if($leaveRequest->canBeCancelled())
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Pembatalan</h3>
                    </div>
                    <div class="card-body">
                        <div x-data="{ showCancel: false }">
                            <button @click="showCancel = !showCancel" class="btn btn-ghost w-full text-warning-600 border-warning-300 hover:bg-warning-50">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                Batalkan Pengajuan
                            </button>

                            <form x-show="showCancel" x-cloak action="{{ route('leave-requests.cancel', $leaveRequest) }}" method="POST" class="mt-3">
                                @csrf
                                <div class="mb-3">
                                    <label for="cancel_reason" class="block text-sm font-medium text-secondary-700 mb-1">Alasan Pembatalan</label>
                                    <textarea name="reason" id="cancel_reason" rows="2" class="input w-full" placeholder="Masukkan alasan..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-warning w-full">Konfirmasi Batalkan</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Approval Workflow Progress --}}
            @if($leaveRequest->hasWorkflow() && $approvalRecords->isNotEmpty())
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Alur Persetujuan</h3>
                    </div>
                    <div class="card-body">
                        <div class="space-y-3">
                            @foreach($approvalRecords as $record)
                                <div class="flex gap-3 items-start">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0
                                        @if($record->isApproved()) bg-success-100 text-success-600
                                        @elseif($record->isRejected()) bg-danger-100 text-danger-600
                                        @elseif($record->isSkipped()) bg-secondary-100 text-secondary-400
                                        @else bg-warning-100 text-warning-600
                                        @endif">
                                        <span class="text-xs font-bold">{{ $record->step_order }}</span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <p class="text-sm font-medium text-secondary-900">{{ $record->step->approver_description }}</p>
                                            @if($record->isApproved())
                                                <x-badge type="success">Disetujui</x-badge>
                                            @elseif($record->isRejected())
                                                <x-badge type="danger">Ditolak</x-badge>
                                            @elseif($record->isSkipped())
                                                <x-badge type="secondary">Dilewati</x-badge>
                                            @else
                                                <x-badge type="warning">Menunggu</x-badge>
                                            @endif
                                        </div>
                                        <p class="text-xs text-secondary-500">{{ $record->step->approver_type_label }}</p>
                                        @if($record->actor)
                                            <p class="text-xs text-secondary-500 mt-1">{{ $record->actor->name }} &middot; {{ $record->acted_at->format('d M Y H:i') }}</p>
                                        @endif
                                        @if($record->notes)
                                            <p class="text-xs text-secondary-600 mt-1 italic">"{{ $record->notes }}"</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            {{-- Timeline --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Riwayat</h3>
                </div>
                <div class="card-body">
                    <div class="space-y-4">
                        <div class="flex gap-3">
                            <div class="w-8 h-8 rounded-full bg-primary-100 text-primary-600 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-secondary-900">Pengajuan Dibuat</p>
                                <p class="text-xs text-secondary-500">{{ $leaveRequest->created_at->format('d M Y H:i') }}</p>
                            </div>
                        </div>

                        @if($leaveRequest->isApproved())
                            <div class="flex gap-3">
                                <div class="w-8 h-8 rounded-full bg-success-100 text-success-600 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-secondary-900">Disetujui</p>
                                    <p class="text-xs text-secondary-500">{{ $leaveRequest->approved_at->format('d M Y H:i') }}</p>
                                </div>
                            </div>
                        @endif

                        @if($leaveRequest->isRejected())
                            <div class="flex gap-3">
                                <div class="w-8 h-8 rounded-full bg-danger-100 text-danger-600 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-secondary-900">Ditolak</p>
                                    <p class="text-xs text-secondary-500">{{ $leaveRequest->rejected_at->format('d M Y H:i') }}</p>
                                </div>
                            </div>
                        @endif

                        @if($leaveRequest->isCancelled())
                            <div class="flex gap-3">
                                <div class="w-8 h-8 rounded-full bg-secondary-100 text-secondary-600 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-secondary-900">Dibatalkan</p>
                                    <p class="text-xs text-secondary-500">{{ $leaveRequest->cancelled_at->format('d M Y H:i') }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
