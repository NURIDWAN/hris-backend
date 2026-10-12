@extends('layouts.admin')

@section('title', 'Alur Persetujuan')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Pengaturan</span>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Alur Persetujuan</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Alur Persetujuan</h1>
            <p class="text-secondary-500 mt-1">Konfigurasi alur persetujuan untuk berbagai permintaan.</p>
        </div>
        <a href="{{ route('settings.approval-workflows.create') }}" class="btn btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Tambah Alur
        </a>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach($workflowTypes as $type => $label)
            @php
                $workflow = $workflows->firstWhere('type', $type);
            @endphp
            <div class="card">
                <div class="card-body">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-lg bg-primary-100 flex items-center justify-center">
                                @if($type === 'leave_request')
                                    <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                @elseif($type === 'overtime')
                                    <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                @elseif($type === 'expense')
                                    <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                @else
                                    <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                    </svg>
                                @endif
                            </div>
                            <div>
                                <h3 class="font-semibold text-secondary-900">{{ $label }}</h3>
                                @if($workflow)
                                    <p class="text-sm text-secondary-500">{{ $workflow->name }}</p>
                                @else
                                    <p class="text-sm text-secondary-400">Belum dikonfigurasi</p>
                                @endif
                            </div>
                        </div>
                        @if($workflow)
                            @if($workflow->is_active)
                                <x-badge type="success">Aktif</x-badge>
                            @else
                                <x-badge type="secondary">Nonaktif</x-badge>
                            @endif
                        @endif
                    </div>

                    @if($workflow)
                        <div class="mt-4 pt-4 border-t border-secondary-200">
                            <div class="text-sm text-secondary-600 mb-3">
                                <span class="font-medium">{{ $workflow->steps->count() }}</span> langkah persetujuan
                            </div>
                            @if($workflow->steps->count() > 0)
                                <div class="flex items-center gap-2 flex-wrap">
                                    @foreach($workflow->steps->take(3) as $index => $step)
                                        <div class="flex items-center gap-1">
                                            <span class="w-5 h-5 rounded-full bg-primary-100 text-primary-600 text-xs flex items-center justify-center font-medium">
                                                {{ $index + 1 }}
                                            </span>
                                            <span class="text-sm text-secondary-600">{{ $step->approver_description }}</span>
                                        </div>
                                        @if(!$loop->last && $index < 2)
                                            <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        @endif
                                    @endforeach
                                    @if($workflow->steps->count() > 3)
                                        <span class="text-sm text-secondary-400">+{{ $workflow->steps->count() - 3 }} lainnya</span>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <div class="mt-4 flex items-center gap-2">
                            <a href="{{ route('settings.approval-workflows.edit', $workflow) }}" class="btn btn-secondary btn-sm flex-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                Kelola
                            </a>
                            <form action="{{ route('settings.approval-workflows.toggle-status', $workflow) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-ghost btn-sm" title="{{ $workflow->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                    @if($workflow->is_active)
                                        <svg class="w-4 h-4 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                        </svg>
                                    @else
                                        <svg class="w-4 h-4 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    @endif
                                </button>
                            </form>
                            <button type="button"
                                @click="$dispatch('confirm-dialog', {
                                    title: 'Hapus Alur Persetujuan',
                                    message: 'Apakah Anda yakin ingin menghapus alur persetujuan {{ $workflow->name }}?',
                                    confirmText: 'Ya, Hapus',
                                    type: 'danger',
                                    formAction: '{{ route('settings.approval-workflows.destroy', $workflow) }}'
                                })"
                                class="btn btn-ghost btn-sm text-danger-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    @else
                        <div class="mt-4">
                            <a href="{{ route('settings.approval-workflows.create') }}?type={{ $type }}" class="btn btn-primary btn-sm w-full">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                Konfigurasi Alur
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endsection
