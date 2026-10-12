@extends('layouts.admin')

@section('title', 'Detail Exit Karyawan')

@section('breadcrumb')
    <a href="{{ route('employee-exits.index') }}" class="text-slate-500 hover:text-primary-600">Exit Management</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">{{ $employeeExit->employee->full_name }}</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Detail Exit Karyawan</h1>
            <p class="text-secondary-500 mt-1">{{ $employeeExit->employee->full_name }}</p>
        </div>
        <div class="flex items-center gap-3">
            @if($employeeExit->status === 'pending' || $employeeExit->status === 'approved')
                <a href="{{ route('employee-exits.edit', $employeeExit) }}" class="btn btn-ghost">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Edit
                </a>
            @endif
            <a href="{{ route('employee-exits.index') }}" class="btn btn-ghost">
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
                            <div class="w-12 h-12 rounded-full bg-{{ $employeeExit->status_color }}-100 text-{{ $employeeExit->status_color }}-600 flex items-center justify-center">
                                @if($employeeExit->status === 'pending')
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                @elseif($employeeExit->status === 'approved')
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                @elseif($employeeExit->status === 'completed')
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                @elseif($employeeExit->status === 'rejected')
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                @else
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                @endif
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-secondary-900">{{ $employeeExit->status_label }}</h3>
                                <p class="text-sm text-secondary-500">
                                    @if($employeeExit->status === 'approved' && $employeeExit->approvedBy)
                                        Disetujui oleh {{ $employeeExit->approvedBy->name }} pada {{ $employeeExit->approved_at->format('d M Y H:i') }}
                                    @elseif($employeeExit->status === 'rejected' && $employeeExit->approvedBy)
                                        Ditolak oleh {{ $employeeExit->approvedBy->name }} pada {{ $employeeExit->approved_at->format('d M Y H:i') }}
                                    @elseif($employeeExit->status === 'completed')
                                        Proses exit selesai
                                    @else
                                        Menunggu persetujuan
                                    @endif
                                </p>
                            </div>
                        </div>
                        <x-badge type="{{ $employeeExit->status_color }}">{{ $employeeExit->status_label }}</x-badge>
                    </div>

                    @if($employeeExit->approval_notes)
                        <div class="mt-4 p-3 bg-{{ $employeeExit->status === 'rejected' ? 'danger' : 'success' }}-50 rounded-lg">
                            <p class="text-sm font-medium text-{{ $employeeExit->status === 'rejected' ? 'danger' : 'success' }}-800">
                                {{ $employeeExit->status === 'rejected' ? 'Alasan Penolakan:' : 'Catatan Persetujuan:' }}
                            </p>
                            <p class="text-sm text-{{ $employeeExit->status === 'rejected' ? 'danger' : 'success' }}-700">{{ $employeeExit->approval_notes }}</p>
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
                            {{ strtoupper(substr($employeeExit->employee->full_name, 0, 1)) }}
                        </div>
                        <div>
                            <h4 class="font-semibold text-secondary-900">{{ $employeeExit->employee->full_name }}</h4>
                            <p class="text-sm text-secondary-500">{{ $employeeExit->employee->employee_id }}</p>
                            @if($employeeExit->employee->position)
                                <p class="text-sm text-secondary-500">{{ $employeeExit->employee->position->name }}</p>
                            @endif
                            @if($employeeExit->employee->department)
                                <p class="text-sm text-secondary-400">{{ $employeeExit->employee->department->name }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Exit Details --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Detail Exit</h3>
                </div>
                <div class="card-body">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Jenis Exit</dt>
                            <dd class="mt-1">
                                <x-badge type="{{ $employeeExit->exit_type_color }}">{{ $employeeExit->exit_type_label }}</x-badge>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Tanggal Pengajuan</dt>
                            <dd class="mt-1 text-secondary-900">{{ $employeeExit->request_date->format('d F Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Hari Kerja Terakhir</dt>
                            <dd class="mt-1 text-secondary-900">{{ $employeeExit->last_working_day->format('d F Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Tanggal Efektif</dt>
                            <dd class="mt-1 text-secondary-900 font-semibold">{{ $employeeExit->effective_date->format('d F Y') }}</dd>
                        </div>
                        @if($employeeExit->reason)
                            <div class="md:col-span-2">
                                <dt class="text-sm font-medium text-secondary-500">Alasan</dt>
                                <dd class="mt-1 text-secondary-900">{{ $employeeExit->reason }}</dd>
                            </div>
                        @endif
                        @if($employeeExit->notes)
                            <div class="md:col-span-2">
                                <dt class="text-sm font-medium text-secondary-500">Catatan Internal</dt>
                                <dd class="mt-1 text-secondary-900">{{ $employeeExit->notes }}</dd>
                            </div>
                        @endif
                    </dl>

                    {{-- Rehire Eligibility --}}
                    <div class="mt-4 pt-4 border-t">
                        <div class="flex items-center gap-2">
                            @if($employeeExit->is_rehire_eligible)
                                <svg class="w-5 h-5 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span class="text-sm text-success-700 font-medium">Dapat direkrut kembali</span>
                            @else
                                <svg class="w-5 h-5 text-danger-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span class="text-sm text-danger-700 font-medium">Tidak dapat direkrut kembali</span>
                            @endif
                        </div>
                        @if($employeeExit->rehire_notes)
                            <p class="mt-2 text-sm text-secondary-600">{{ $employeeExit->rehire_notes }}</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Checklist --}}
            @if($employeeExit->status === 'pending' || $employeeExit->status === 'approved')
                <div class="card">
                    <div class="card-header flex items-center justify-between">
                        <h3 class="card-title">Exit Checklist</h3>
                        <span class="text-sm text-secondary-500">{{ $employeeExit->checklist_progress }}% selesai</span>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('employee-exits.checklist', $employeeExit) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="space-y-4">
                                <label class="flex items-start gap-3 cursor-pointer">
                                    <input type="checkbox" name="assets_returned" value="1"
                                           class="w-5 h-5 mt-0.5 text-primary-600 border-secondary-300 rounded focus:ring-primary-500"
                                           {{ $employeeExit->assets_returned ? 'checked' : '' }}>
                                    <div>
                                        <span class="font-medium text-secondary-900">Aset Dikembalikan</span>
                                        <p class="text-sm text-secondary-500">Laptop, kartu akses, seragam, dan aset perusahaan lainnya sudah dikembalikan.</p>
                                    </div>
                                </label>

                                <label class="flex items-start gap-3 cursor-pointer">
                                    <input type="checkbox" name="knowledge_transfer_done" value="1"
                                           class="w-5 h-5 mt-0.5 text-primary-600 border-secondary-300 rounded focus:ring-primary-500"
                                           {{ $employeeExit->knowledge_transfer_done ? 'checked' : '' }}>
                                    <div>
                                        <span class="font-medium text-secondary-900">Knowledge Transfer</span>
                                        <p class="text-sm text-secondary-500">Transfer pengetahuan dan handover pekerjaan sudah selesai.</p>
                                    </div>
                                </label>

                                <label class="flex items-start gap-3 cursor-pointer">
                                    <input type="checkbox" name="final_settlement_done" value="1"
                                           class="w-5 h-5 mt-0.5 text-primary-600 border-secondary-300 rounded focus:ring-primary-500"
                                           {{ $employeeExit->final_settlement_done ? 'checked' : '' }}>
                                    <div>
                                        <span class="font-medium text-secondary-900">Penyelesaian Finansial</span>
                                        <p class="text-sm text-secondary-500">Gaji terakhir, pesangon, dan kewajiban finansial lainnya sudah diselesaikan.</p>
                                    </div>
                                </label>

                                <label class="flex items-start gap-3 cursor-pointer">
                                    <input type="checkbox" name="exit_interview_done" value="1"
                                           class="w-5 h-5 mt-0.5 text-primary-600 border-secondary-300 rounded focus:ring-primary-500"
                                           {{ $employeeExit->exit_interview_done ? 'checked' : '' }}>
                                    <div>
                                        <span class="font-medium text-secondary-900">Exit Interview</span>
                                        <p class="text-sm text-secondary-500">Wawancara exit dengan HR sudah dilakukan.</p>
                                    </div>
                                </label>

                                <div class="pt-2">
                                    <label for="exit_interview_notes" class="block text-sm font-medium text-secondary-700 mb-1">
                                        Catatan Exit Interview
                                    </label>
                                    <textarea name="exit_interview_notes" id="exit_interview_notes" rows="3"
                                              class="input w-full"
                                              placeholder="Ringkasan hasil exit interview...">{{ $employeeExit->exit_interview_notes }}</textarea>
                                </div>
                            </div>

                            <div class="mt-4 pt-4 border-t flex justify-end">
                                <button type="submit" class="btn btn-primary">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Simpan Checklist
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @else
                {{-- Read-only checklist for completed/rejected --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Exit Checklist</h3>
                    </div>
                    <div class="card-body">
                        <div class="space-y-3">
                            <div class="flex items-center gap-3">
                                @if($employeeExit->assets_returned)
                                    <svg class="w-5 h-5 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                @else
                                    <svg class="w-5 h-5 text-secondary-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                @endif
                                <span class="text-secondary-700">Aset Dikembalikan</span>
                            </div>
                            <div class="flex items-center gap-3">
                                @if($employeeExit->knowledge_transfer_done)
                                    <svg class="w-5 h-5 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                @else
                                    <svg class="w-5 h-5 text-secondary-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                @endif
                                <span class="text-secondary-700">Knowledge Transfer</span>
                            </div>
                            <div class="flex items-center gap-3">
                                @if($employeeExit->final_settlement_done)
                                    <svg class="w-5 h-5 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                @else
                                    <svg class="w-5 h-5 text-secondary-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                @endif
                                <span class="text-secondary-700">Penyelesaian Finansial</span>
                            </div>
                            <div class="flex items-center gap-3">
                                @if($employeeExit->exit_interview_done)
                                    <svg class="w-5 h-5 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                @else
                                    <svg class="w-5 h-5 text-secondary-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                @endif
                                <span class="text-secondary-700">Exit Interview</span>
                            </div>
                        </div>
                        @if($employeeExit->exit_interview_notes)
                            <div class="mt-4 pt-4 border-t">
                                <p class="text-sm font-medium text-secondary-500 mb-1">Catatan Exit Interview</p>
                                <p class="text-secondary-700">{{ $employeeExit->exit_interview_notes }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Quick Actions --}}
            @if($employeeExit->status === 'pending')
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Aksi</h3>
                    </div>
                    <div class="card-body space-y-2">
                        <form action="{{ route('employee-exits.approve', $employeeExit) }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="approval_notes" class="block text-sm font-medium text-secondary-700 mb-1">Catatan (Opsional)</label>
                                <textarea name="approval_notes" id="approval_notes" rows="2" class="input w-full" placeholder="Tambahkan catatan..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-success w-full">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Setujui Exit
                            </button>
                        </form>

                        <div x-data="{ showReject: false }">
                            <button @click="showReject = !showReject" class="btn btn-danger w-full">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                Tolak Exit
                            </button>

                            <form x-show="showReject" x-cloak action="{{ route('employee-exits.reject', $employeeExit) }}" method="POST" class="mt-3">
                                @csrf
                                <div class="mb-3">
                                    <label for="reject_notes" class="block text-sm font-medium text-secondary-700 mb-1">Alasan Penolakan</label>
                                    <textarea name="approval_notes" id="reject_notes" rows="2" class="input w-full" placeholder="Masukkan alasan..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-danger w-full">Konfirmasi Tolak</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            @if($employeeExit->status === 'approved')
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Selesaikan Exit</h3>
                    </div>
                    <div class="card-body">
                        @if($employeeExit->isChecklistComplete())
                            <p class="text-sm text-secondary-600 mb-4">Semua checklist sudah selesai. Klik tombol di bawah untuk menyelesaikan proses exit dan menonaktifkan karyawan.</p>
                            <form action="{{ route('employee-exits.complete', $employeeExit) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-success w-full">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Selesaikan Exit
                                </button>
                            </form>
                        @else
                            <div class="flex items-center gap-2 text-warning-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <span class="text-sm font-medium">Checklist Belum Lengkap</span>
                            </div>
                            <p class="text-sm text-secondary-600 mt-2">Selesaikan semua item checklist sebelum menyelesaikan proses exit.</p>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Delete --}}
            @if($employeeExit->status !== 'completed')
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Hapus Data</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-sm text-secondary-600 mb-3">Hapus data exit ini. Tindakan ini tidak dapat dibatalkan.</p>
                        <button
                            type="button"
                            @click="$dispatch('confirm-dialog', {
                                title: 'Hapus Data Exit',
                                message: 'Apakah Anda yakin ingin menghapus data exit untuk {{ $employeeExit->employee->full_name }}?',
                                confirmText: 'Ya, Hapus',
                                type: 'danger',
                                formAction: '{{ route('employee-exits.destroy', $employeeExit) }}'
                            })"
                            class="btn btn-ghost w-full text-danger-600 border-danger-300 hover:bg-danger-50"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Hapus Data Exit
                        </button>
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
                                <p class="text-xs text-secondary-500">{{ $employeeExit->created_at->format('d M Y H:i') }}</p>
                                @if($employeeExit->createdBy)
                                    <p class="text-xs text-secondary-400">oleh {{ $employeeExit->createdBy->name }}</p>
                                @endif
                            </div>
                        </div>

                        @if(in_array($employeeExit->status, ['approved', 'completed']))
                            <div class="flex gap-3">
                                <div class="w-8 h-8 rounded-full bg-success-100 text-success-600 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-secondary-900">Disetujui</p>
                                    @if($employeeExit->approved_at)
                                        <p class="text-xs text-secondary-500">{{ $employeeExit->approved_at->format('d M Y H:i') }}</p>
                                    @endif
                                    @if($employeeExit->approvedBy)
                                        <p class="text-xs text-secondary-400">oleh {{ $employeeExit->approvedBy->name }}</p>
                                    @endif
                                </div>
                            </div>
                        @endif

                        @if($employeeExit->status === 'rejected')
                            <div class="flex gap-3">
                                <div class="w-8 h-8 rounded-full bg-danger-100 text-danger-600 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-secondary-900">Ditolak</p>
                                    @if($employeeExit->approved_at)
                                        <p class="text-xs text-secondary-500">{{ $employeeExit->approved_at->format('d M Y H:i') }}</p>
                                    @endif
                                    @if($employeeExit->approvedBy)
                                        <p class="text-xs text-secondary-400">oleh {{ $employeeExit->approvedBy->name }}</p>
                                    @endif
                                </div>
                            </div>
                        @endif

                        @if($employeeExit->status === 'completed')
                            <div class="flex gap-3">
                                <div class="w-8 h-8 rounded-full bg-success-100 text-success-600 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-secondary-900">Selesai</p>
                                    <p class="text-xs text-secondary-500">{{ $employeeExit->updated_at->format('d M Y H:i') }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
