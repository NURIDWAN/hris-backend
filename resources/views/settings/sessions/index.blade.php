@extends('layouts.admin')

@section('title', 'Manajemen Sesi')

@section('breadcrumb')
    <a href="#" class="text-slate-500 hover:text-primary-600">Pengaturan</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Sesi Login</span>
@endsection

@section('header')
    <div>
        <h1 class="text-2xl font-bold text-secondary-900">Sesi Login</h1>
        <p class="text-secondary-500 mt-1">Kelola sesi login aktif Anda di berbagai perangkat.</p>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Sesi Aktif</h3>
        </div>

        <x-table>
            <x-slot name="header">
                <th>Perangkat</th>
                <th>IP Address</th>
                <th>Aktivitas Terakhir</th>
                <th>Status</th>
                <th></th>
            </x-slot>
            @forelse($sessions as $session)
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-secondary-100 flex items-center justify-center">
                                @if($session->os === 'Windows')
                                    <svg class="w-5 h-5 text-secondary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                @elseif(in_array($session->os, ['iOS', 'Android']))
                                    <svg class="w-5 h-5 text-secondary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                @else
                                    <svg class="w-5 h-5 text-secondary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                @endif
                            </div>
                            <div>
                                <p class="text-sm font-medium text-secondary-900">{{ $session->browser }}</p>
                                <p class="text-xs text-secondary-500">{{ $session->os }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="text-sm font-mono text-secondary-600">{{ $session->ip_address }}</td>
                    <td class="text-sm text-secondary-600">{{ $session->last_activity->diffForHumans() }}</td>
                    <td>
                        @if($session->id === $currentSessionId)
                            <span class="px-2 py-0.5 bg-success-100 text-success-700 rounded text-xs font-medium">Sesi Ini</span>
                        @else
                            <span class="px-2 py-0.5 bg-secondary-100 text-secondary-700 rounded text-xs font-medium">Aktif</span>
                        @endif
                    </td>
                    <td>
                        @if($session->id !== $currentSessionId)
                            <button
                                type="button"
                                @click="$dispatch('confirm-dialog', {
                                    title: 'Hapus Sesi',
                                    message: 'Apakah Anda yakin ingin menghapus sesi ini? Perangkat terkait akan otomatis logout.',
                                    confirmText: 'Ya, Hapus',
                                    type: 'danger',
                                    formAction: '{{ route('settings.sessions.destroy', $session->id) }}'
                                })"
                                class="btn btn-ghost btn-sm text-danger-600"
                            >
                                Hapus
                            </button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center py-8 text-secondary-500">Tidak ada sesi aktif.</td>
                </tr>
            @endforelse
        </x-table>
    </div>
@endsection
