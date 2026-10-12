@extends('superadmin.layouts.app')

@section('title', 'Rate Limit Logs')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Rate Limits</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Rate Limit Logs</h1>
            <p class="text-secondary-500 mt-1">Monitor request yang terkena rate limiting (429).</p>
        </div>
        <form method="POST" action="{{ route('superadmin.system.rate-limits.clear') }}" class="flex items-center gap-2">
            @csrf
            <select name="days" class="input text-sm">
                <option value="7">Lebih dari 7 hari</option>
                <option value="14">Lebih dari 14 hari</option>
                <option value="30" selected>Lebih dari 30 hari</option>
            </select>
            <button type="submit" class="btn btn-secondary btn-sm">Hapus Log Lama</button>
        </form>
    </div>
@endsection

@section('content')
    {{-- Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="stat-card">
            <div class="stat-card-icon" style="background-color: #fef2f2;">
                <svg class="w-6 h-6 text-danger-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div>
                <div class="stat-card-value">{{ number_format($stats['total_24h']) }}</div>
                <div class="stat-card-label">Hit 24 Jam</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-icon" style="background-color: #fff7ed;">
                <svg class="w-6 h-6 text-accent-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
            </div>
            <div>
                <div class="stat-card-value">{{ number_format($stats['unique_ips_24h']) }}</div>
                <div class="stat-card-label">IP Unik 24 Jam</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-icon" style="background-color: #eff6ff;">
                <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </div>
            <div>
                <div class="stat-card-value">{{ $stats['top_routes']->count() }}</div>
                <div class="stat-card-label">Route Terdampak</div>
            </div>
        </div>
    </div>

    {{-- Top Routes --}}
    @if($stats['top_routes']->isNotEmpty())
        <div class="card mb-6">
            <div class="card-header">
                <h3 class="card-title">Top Route Terkena Rate Limit (24 Jam)</h3>
            </div>
            <div class="card-body p-0">
                <table class="w-full">
                    <thead class="bg-secondary-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-secondary-500 uppercase">Route</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-secondary-500 uppercase">Hit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-secondary-100">
                        @foreach($stats['top_routes'] as $route => $hits)
                            <tr>
                                <td class="px-4 py-3 text-sm font-mono text-secondary-900">{{ $route }}</td>
                                <td class="px-4 py-3 text-sm text-right font-semibold text-danger-600">{{ number_format($hits) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Log Table --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Semua Log</h3>
        </div>

        <x-table>
            <x-slot name="header">
                <th>Waktu</th>
                <th>IP Address</th>
                <th>Pengguna</th>
                <th>Method</th>
                <th>Route</th>
            </x-slot>
            @forelse($logs as $log)
                <tr>
                    <td class="text-xs text-secondary-500 whitespace-nowrap">
                        {{ $log->created_at->format('d M Y') }}<br>
                        {{ $log->created_at->format('H:i:s') }}
                    </td>
                    <td class="text-sm font-mono text-secondary-600">{{ $log->ip_address }}</td>
                    <td class="text-sm text-secondary-600">
                        {{ $log->user?->name ?? '-' }}
                    </td>
                    <td>
                        <span class="px-2 py-0.5 bg-secondary-100 text-secondary-700 rounded text-xs font-mono">{{ $log->method }}</span>
                    </td>
                    <td class="text-sm font-mono text-secondary-600 max-w-xs truncate">{{ $log->route }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center py-8 text-secondary-500">Tidak ada log rate limit.</td>
                </tr>
            @endforelse
        </x-table>

        @if($logs->hasPages())
            <div class="card-footer">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
@endsection
