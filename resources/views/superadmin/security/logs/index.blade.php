@extends('superadmin.layouts.app')

@section('title', 'Attack Logs')

@section('header')
    <h1 class="text-2xl font-bold text-secondary-900">Attack Logs</h1>
    <p class="text-secondary-600">Monitor aktivitas keamanan dan serangan pada sistem</p>
@endsection

@section('content')
    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="card">
            <div class="card-body flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background-color: #dbeafe;">
                    <svg class="w-6 h-6" style="color: #2563eb;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-secondary-600">Total (24 jam)</p>
                    <p class="text-2xl font-bold text-secondary-900">{{ number_format($stats['total_24h']) }}</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background-color: #fee2e2;">
                    <svg class="w-6 h-6" style="color: #dc2626;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-secondary-600">Critical</p>
                    <p class="text-2xl font-bold" style="color: #dc2626;">{{ number_format($stats['critical_24h']) }}</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background-color: #fef3c7;">
                    <svg class="w-6 h-6" style="color: #d97706;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-secondary-600">Warning</p>
                    <p class="text-2xl font-bold" style="color: #d97706;">{{ number_format($stats['warning_24h']) }}</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background-color: #e2e8f0;">
                    <svg class="w-6 h-6" style="color: #475569;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-secondary-600">IP Diblokir</p>
                    <p class="text-2xl font-bold text-secondary-900">{{ number_format($stats['blocked_ips']) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-6">
        <div class="card-body-sm">
            <form method="GET" action="{{ route('superadmin.security.logs.index') }}" class="flex flex-wrap gap-4">
                <div class="w-40">
                    <select name="event_type" class="input w-full">
                        <option value="">Event Type</option>
                        @foreach($eventTypes as $type)
                            <option value="{{ $type }}" {{ request('event_type') == $type ? 'selected' : '' }}>
                                {{ $type }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="w-32">
                    <select name="severity" class="input w-full">
                        <option value="">Severity</option>
                        <option value="critical" {{ request('severity') == 'critical' ? 'selected' : '' }}>Critical</option>
                        <option value="warning" {{ request('severity') == 'warning' ? 'selected' : '' }}>Warning</option>
                        <option value="info" {{ request('severity') == 'info' ? 'selected' : '' }}>Info</option>
                    </select>
                </div>
                <div class="w-36">
                    <input type="text" name="ip" value="{{ request('ip') }}" placeholder="IP Address" class="input w-full">
                </div>
                <div class="w-40">
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="input w-full" title="Dari Tanggal">
                </div>
                <div class="w-40">
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="input w-full" title="Sampai Tanggal">
                </div>
                <button type="submit" class="btn btn-primary">Filter</button>
                @if(request()->hasAny(['event_type', 'severity', 'ip', 'date_from', 'date_to']))
                    <a href="{{ route('superadmin.security.logs.index') }}" class="btn btn-secondary">Reset</a>
                @endif
            </form>
        </div>
    </div>

    {{-- Logs Table --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Security Logs</h3>
        </div>
        <div class="card-body p-0">
            <x-table>
                <x-slot name="header">
                    <th class="px-6 py-3 text-left">Waktu</th>
                    <th class="px-6 py-3 text-left">Event</th>
                    <th class="px-6 py-3 text-left">IP Address</th>
                    <th class="px-6 py-3 text-left">URL</th>
                    <th class="px-6 py-3 text-left">User</th>
                    <th class="px-6 py-3 text-center">Aksi</th>
                </x-slot>

                @forelse($logs as $log)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4">
                            <p class="text-sm text-secondary-900">{{ $log->created_at->format('d M Y') }}</p>
                            <p class="text-xs text-secondary-500">{{ $log->created_at->format('H:i:s') }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <x-badge type="{{ $log->severity_color }}">{{ $log->severity }}</x-badge>
                                <span class="text-sm font-medium text-secondary-900">{{ $log->event_type }}</span>
                            </div>
                            @if($log->description)
                                <p class="text-xs text-secondary-500 mt-1 truncate max-w-xs">{{ $log->description }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <code class="text-sm bg-slate-100 px-2 py-1 rounded">{{ $log->ip_address }}</code>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm text-secondary-900 truncate max-w-xs" title="{{ $log->url }}">
                                {{ $log->method }} {{ Str::limit($log->url, 40) }}
                            </p>
                        </td>
                        <td class="px-6 py-4">
                            @if($log->user)
                                <p class="text-sm text-secondary-900">{{ $log->user->name }}</p>
                            @else
                                <span class="text-sm text-secondary-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('superadmin.security.logs.show', $log) }}" class="btn btn-ghost btn-sm" title="Detail">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                <form method="POST" action="{{ route('superadmin.security.logs.block-ip', $log) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="btn btn-ghost btn-sm text-danger-600" title="Block IP"
                                            onclick="return confirm('Block IP {{ $log->ip_address }}?')">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-secondary-500">
                            Belum ada security log
                        </td>
                    </tr>
                @endforelse
            </x-table>
        </div>

        @if($logs->hasPages())
            <div class="card-footer">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
@endsection
