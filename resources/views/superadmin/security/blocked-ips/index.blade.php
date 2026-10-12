@extends('superadmin.layouts.app')

@section('title', 'Blocked IPs')

@section('header')
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Blocked IPs</h1>
            <p class="text-secondary-600">Daftar IP yang diblokir dari mengakses sistem</p>
        </div>
        <a href="{{ route('superadmin.security.blocked-ips.create') }}" class="btn btn-primary">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Block IP Baru
        </a>
    </div>
@endsection

@section('content')
    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="card">
            <div class="card-body flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background-color: #fee2e2;">
                    <svg class="w-6 h-6" style="color: #dc2626;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-secondary-600">Total Aktif</p>
                    <p class="text-2xl font-bold" style="color: #dc2626;">{{ number_format($stats['total_active']) }}</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background-color: #e2e8f0;">
                    <svg class="w-6 h-6" style="color: #475569;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-secondary-600">Permanent</p>
                    <p class="text-2xl font-bold text-secondary-900">{{ number_format($stats['total_permanent']) }}</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background-color: #fef3c7;">
                    <svg class="w-6 h-6" style="color: #d97706;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-secondary-600">Temporary</p>
                    <p class="text-2xl font-bold" style="color: #d97706;">{{ number_format($stats['total_temporary']) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-6">
        <div class="card-body">
            <form method="GET" action="{{ route('superadmin.security.blocked-ips.index') }}" class="flex gap-4">
                <div class="w-40">
                    <select name="status" class="input w-full">
                        <option value="">Semua Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
                    </select>
                </div>
                <div class="flex-1">
                    <input type="text" name="ip" value="{{ request('ip') }}" placeholder="Cari IP address..." class="input w-full">
                </div>
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="{{ route('superadmin.security.blocked-ips.index') }}" class="btn btn-secondary">Reset</a>
            </form>
        </div>
    </div>

    {{-- Blocked IPs Table --}}
    <div class="card">
        <div class="card-body p-0">
            <x-table>
                <x-slot name="header">
                    <th class="px-6 py-3 text-left">IP Address</th>
                    <th class="px-6 py-3 text-left">Reason</th>
                    <th class="px-6 py-3 text-left">Blocked By</th>
                    <th class="px-6 py-3 text-left">Duration</th>
                    <th class="px-6 py-3 text-center">Attempts</th>
                    <th class="px-6 py-3 text-center">Status</th>
                    <th class="px-6 py-3 text-center">Aksi</th>
                </x-slot>

                @forelse($blockedIps as $blocked)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4">
                            <code class="text-sm bg-slate-100 px-2 py-1 rounded">{{ $blocked->ip_address }}</code>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm text-secondary-900">{{ $blocked->reason }}</p>
                            <p class="text-xs text-secondary-500">{{ $blocked->created_at->format('d M Y H:i') }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm text-secondary-900">{{ $blocked->blockedByUser?->name ?? 'System' }}</p>
                        </td>
                        <td class="px-6 py-4">
                            @if($blocked->isPermanent())
                                <x-badge type="secondary">Permanent</x-badge>
                            @else
                                <p class="text-sm text-secondary-900">{{ $blocked->remaining_time }}</p>
                                <p class="text-xs text-secondary-500">{{ $blocked->blocked_until->format('d M Y H:i') }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-800">
                                {{ $blocked->attempt_count }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($blocked->is_active)
                                <x-badge type="danger">Blocked</x-badge>
                            @else
                                <x-badge type="secondary">Inactive</x-badge>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                @if($blocked->is_active)
                                    <form method="POST" action="{{ route('superadmin.security.blocked-ips.unblock', $blocked) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="btn btn-ghost btn-sm text-success-600" title="Unblock"
                                                onclick="return confirm('Unblock IP {{ $blocked->ip_address }}?')">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('superadmin.security.blocked-ips.destroy', $blocked) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-ghost btn-sm text-danger-600" title="Hapus"
                                            onclick="return confirm('Hapus record IP {{ $blocked->ip_address }}?')">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-secondary-500">
                            Belum ada IP yang diblokir
                        </td>
                    </tr>
                @endforelse
            </x-table>
        </div>

        @if($blockedIps->hasPages())
            <div class="card-footer">
                {{ $blockedIps->links() }}
            </div>
        @endif
    </div>
@endsection
