@extends('superadmin.layouts.app')

@section('title', 'Email Logs')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Email Logs</span>
@endsection

@section('header')
    <h1 class="text-2xl font-bold text-secondary-900">Email Logs</h1>
    <p class="text-secondary-600">Monitor semua email yang dikirim oleh sistem</p>
@endsection

@section('content')
    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="card">
            <div class="card-body flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background-color: #dbeafe;">
                    <svg class="w-6 h-6" style="color: #2563eb;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-secondary-600">Total Email</p>
                    <p class="text-2xl font-bold text-secondary-900">{{ number_format($stats['total']) }}</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background-color: #d1fae5;">
                    <svg class="w-6 h-6" style="color: #059669;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-secondary-600">Terkirim</p>
                    <p class="text-2xl font-bold" style="color: #059669;">{{ number_format($stats['sent']) }}</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background-color: #fee2e2;">
                    <svg class="w-6 h-6" style="color: #dc2626;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-secondary-600">Gagal</p>
                    <p class="text-2xl font-bold" style="color: #dc2626;">{{ number_format($stats['failed']) }}</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background-color: #fef3c7;">
                    <svg class="w-6 h-6" style="color: #d97706;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-secondary-600">Hari Ini</p>
                    <p class="text-2xl font-bold" style="color: #d97706;">{{ number_format($stats['today']) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-6">
        <div class="card-body-sm">
            <form method="GET" action="{{ route('superadmin.system.email-logs.index') }}" class="flex flex-wrap gap-4">
                <div class="w-48">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari email/subject..." class="input w-full">
                </div>
                <div class="w-32">
                    <select name="status" class="input w-full">
                        <option value="">Status</option>
                        <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Sent</option>
                        <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                        <option value="sending" {{ request('status') == 'sending' ? 'selected' : '' }}>Sending</option>
                    </select>
                </div>
                <div class="w-40">
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="input w-full" title="Dari Tanggal">
                </div>
                <div class="w-40">
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="input w-full" title="Sampai Tanggal">
                </div>
                <button type="submit" class="btn btn-primary">Filter</button>
                @if(request()->hasAny(['search', 'status', 'date_from', 'date_to']))
                    <a href="{{ route('superadmin.system.email-logs.index') }}" class="btn btn-secondary">Reset</a>
                @endif
            </form>
        </div>
    </div>

    {{-- Email Logs Table --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Email Logs</h3>
        </div>
        <div class="card-body p-0">
            <x-table>
                <x-slot name="header">
                    <th class="px-6 py-3 text-left">Waktu</th>
                    <th class="px-6 py-3 text-left">Penerima</th>
                    <th class="px-6 py-3 text-left">Subject</th>
                    <th class="px-6 py-3 text-left">Status</th>
                    <th class="px-6 py-3 text-center">Aksi</th>
                </x-slot>

                @forelse($emailLogs as $log)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4">
                            <p class="text-sm text-secondary-900">{{ $log->created_at->translatedFormat('d M Y') }}</p>
                            <p class="text-xs text-secondary-500">{{ $log->created_at->format('H:i:s') }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm text-secondary-900">{{ $log->to }}</p>
                            @if($log->mailer)
                                <p class="text-xs text-secondary-500">via {{ $log->mailer }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm text-secondary-900 truncate max-w-xs">{{ $log->subject }}</p>
                        </td>
                        <td class="px-6 py-4">
                            @if($log->status === 'sent')
                                <x-badge type="success">Sent</x-badge>
                            @elseif($log->status === 'failed')
                                <x-badge type="danger">Failed</x-badge>
                            @else
                                <x-badge type="warning">Sending</x-badge>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            <a href="{{ route('superadmin.system.email-logs.show', $log) }}" class="btn btn-ghost btn-sm" title="Detail">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-secondary-500">
                            Belum ada email log
                        </td>
                    </tr>
                @endforelse
            </x-table>
        </div>

        @if($emailLogs->hasPages())
            <div class="card-footer">
                {{ $emailLogs->links() }}
            </div>
        @endif
    </div>
@endsection
