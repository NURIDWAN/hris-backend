@extends('superadmin.layouts.app')

@section('title', 'Session Management')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Sessions</span>
@endsection

@section('header')
    <div>
        <h1 class="text-2xl font-bold text-secondary-900">Session Management</h1>
        <p class="text-secondary-500 mt-1">Monitor dan kelola sesi login seluruh pengguna.</p>
    </div>
@endsection

@section('content')
    {{-- Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="stat-card">
            <div class="stat-card-icon" style="background-color: #dbeafe;">
                <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </div>
            <div>
                <div class="stat-card-value">{{ number_format($stats['total']) }}</div>
                <div class="stat-card-label">Total Sesi</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-icon" style="background-color: #ecfdf5;">
                <svg class="w-6 h-6 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            </div>
            <div>
                <div class="stat-card-value">{{ number_format($stats['authenticated']) }}</div>
                <div class="stat-card-label">Terautentikasi</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-icon" style="background-color: #f1f5f9;">
                <svg class="w-6 h-6 text-secondary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </div>
            <div>
                <div class="stat-card-value">{{ number_format($stats['guest']) }}</div>
                <div class="stat-card-label">Tamu</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Sesi Aktif</h3>
        </div>

        <x-table>
            <x-slot name="header">
                <th>Pengguna</th>
                <th>Perangkat</th>
                <th>IP Address</th>
                <th>Aktivitas Terakhir</th>
                <th></th>
            </x-slot>
            @forelse($sessions as $session)
                <tr>
                    <td>
                        @if($session->user_id)
                            <div>
                                <p class="text-sm font-medium text-secondary-900">{{ $session->user_name }}</p>
                                <p class="text-xs text-secondary-500">{{ $session->user_email }}</p>
                            </div>
                        @else
                            <span class="text-sm text-secondary-400 italic">Tamu</span>
                        @endif
                    </td>
                    <td>
                        <p class="text-sm text-secondary-900">{{ $session->browser }}</p>
                        <p class="text-xs text-secondary-500">{{ $session->os }}</p>
                    </td>
                    <td class="text-sm font-mono text-secondary-600">{{ $session->ip_address }}</td>
                    <td class="text-sm text-secondary-600">{{ $session->last_activity_at->diffForHumans() }}</td>
                    <td>
                        <button
                            type="button"
                            @click="$dispatch('confirm-dialog', {
                                title: 'Hapus Sesi',
                                message: 'Apakah Anda yakin ingin menghapus sesi ini?',
                                confirmText: 'Ya, Hapus',
                                type: 'danger',
                                formAction: '{{ route('superadmin.system.sessions.destroy', $session->id) }}'
                            })"
                            class="btn btn-ghost btn-sm text-danger-600"
                        >
                            Hapus
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center py-8 text-secondary-500">Tidak ada sesi aktif.</td>
                </tr>
            @endforelse
        </x-table>

        @if($sessions->hasPages())
            <div class="card-footer">
                {{ $sessions->links() }}
            </div>
        @endif
    </div>
@endsection
