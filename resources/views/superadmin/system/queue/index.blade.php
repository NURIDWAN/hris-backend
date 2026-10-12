@extends('superadmin.layouts.app')

@section('title', 'Queue Monitor')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Queue Monitor</span>
@endsection

@section('header')
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Queue Monitor</h1>
            <p class="text-secondary-600">Monitor dan kelola background jobs</p>
        </div>
        @if($stats['failed'] > 0)
            <div class="flex gap-2">
                <form method="POST" action="{{ route('superadmin.system.queue.retry-all') }}">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Retry semua failed jobs?')">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Retry All
                    </button>
                </form>
                <form method="POST" action="{{ route('superadmin.system.queue.flush') }}">
                    @csrf
                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Hapus semua failed jobs? Aksi ini tidak dapat dibatalkan.')">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Flush All
                    </button>
                </form>
            </div>
        @endif
    </div>
@endsection

@section('content')
    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="card">
            <div class="card-body flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background-color: #dbeafe;">
                    <svg class="w-6 h-6" style="color: #2563eb;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-secondary-600">Pending Jobs</p>
                    <p class="text-2xl font-bold text-secondary-900">{{ number_format($stats['pending']) }}</p>
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
                    <p class="text-sm text-secondary-600">Failed Jobs</p>
                    <p class="text-2xl font-bold" style="color: #dc2626;">{{ number_format($stats['failed']) }}</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background-color: #fef3c7;">
                    <svg class="w-6 h-6" style="color: #d97706;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-secondary-600">Gagal Hari Ini</p>
                    <p class="text-2xl font-bold" style="color: #d97706;">{{ number_format($stats['failed_today']) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Failed Jobs Table --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Failed Jobs</h3>
        </div>
        <div class="card-body p-0">
            <x-table>
                <x-slot name="header">
                    <th class="px-6 py-3 text-left">ID</th>
                    <th class="px-6 py-3 text-left">Job</th>
                    <th class="px-6 py-3 text-left">Queue</th>
                    <th class="px-6 py-3 text-left">Gagal Pada</th>
                    <th class="px-6 py-3 text-center">Aksi</th>
                </x-slot>

                @forelse($failedJobs as $job)
                    @php
                        $payload = json_decode($job->payload, true);
                        $jobName = $payload['displayName'] ?? 'Unknown';
                    @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4">
                            <span class="font-mono text-sm text-secondary-600">{{ $job->id }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm font-medium text-secondary-900">{{ class_basename($jobName) }}</p>
                            <p class="text-xs text-secondary-500 truncate max-w-xs">{{ $jobName }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <x-badge type="secondary">{{ $job->queue }}</x-badge>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm text-secondary-900">{{ \Carbon\Carbon::parse($job->failed_at)->translatedFormat('d M Y') }}</p>
                            <p class="text-xs text-secondary-500">{{ \Carbon\Carbon::parse($job->failed_at)->format('H:i:s') }}</p>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('superadmin.system.queue.show', $job->id) }}" class="btn btn-ghost btn-sm" title="Detail">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                <form method="POST" action="{{ route('superadmin.system.queue.retry', $job->id) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="btn btn-ghost btn-sm text-primary-600" title="Retry">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('superadmin.system.queue.destroy', $job->id) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-ghost btn-sm text-danger-600" title="Hapus" onclick="return confirm('Hapus job ini?')">
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
                        <td colspan="5" class="px-6 py-12 text-center text-secondary-500">
                            Tidak ada failed jobs
                        </td>
                    </tr>
                @endforelse
            </x-table>
        </div>

        @if($failedJobs->hasPages())
            <div class="card-footer">
                {{ $failedJobs->links() }}
            </div>
        @endif
    </div>
@endsection
