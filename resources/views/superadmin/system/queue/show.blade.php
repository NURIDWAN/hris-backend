@extends('superadmin.layouts.app')

@section('title', 'Failed Job Detail')

@section('breadcrumb')
    <a href="{{ route('superadmin.system.queue.index') }}" class="text-slate-500 hover:text-primary-600">Queue Monitor</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Job #{{ $job->id }}</span>
@endsection

@section('header')
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Failed Job #{{ $job->id }}</h1>
            <p class="text-secondary-600">Detail informasi failed job</p>
        </div>
        <div class="flex gap-2">
            <form method="POST" action="{{ route('superadmin.system.queue.retry', $job->id) }}">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Retry
                </button>
            </form>
            <form method="POST" action="{{ route('superadmin.system.queue.destroy', $job->id) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Hapus job ini?')">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Hapus
                </button>
            </form>
            <a href="{{ route('superadmin.system.queue.index') }}" class="btn btn-secondary btn-sm">Kembali</a>
        </div>
    </div>
@endsection

@php
    $payload = json_decode($job->payload, true);
    $jobName = $payload['displayName'] ?? 'Unknown';
@endphp

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Job Info --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Informasi Job</h3>
            </div>
            <div class="card-body p-0">
                <table class="w-full text-sm">
                    <tr class="border-b border-slate-100">
                        <td class="px-6 py-3 font-medium text-secondary-600 w-1/3">ID</td>
                        <td class="px-6 py-3 text-secondary-900 font-mono">{{ $job->id }}</td>
                    </tr>
                    <tr class="border-b border-slate-100">
                        <td class="px-6 py-3 font-medium text-secondary-600">UUID</td>
                        <td class="px-6 py-3 text-secondary-900 font-mono text-xs">{{ $job->uuid }}</td>
                    </tr>
                    <tr class="border-b border-slate-100">
                        <td class="px-6 py-3 font-medium text-secondary-600">Job Name</td>
                        <td class="px-6 py-3 text-secondary-900">{{ $jobName }}</td>
                    </tr>
                    <tr class="border-b border-slate-100">
                        <td class="px-6 py-3 font-medium text-secondary-600">Connection</td>
                        <td class="px-6 py-3 text-secondary-900">{{ $job->connection }}</td>
                    </tr>
                    <tr class="border-b border-slate-100">
                        <td class="px-6 py-3 font-medium text-secondary-600">Queue</td>
                        <td class="px-6 py-3"><x-badge type="secondary">{{ $job->queue }}</x-badge></td>
                    </tr>
                    <tr>
                        <td class="px-6 py-3 font-medium text-secondary-600">Failed At</td>
                        <td class="px-6 py-3 text-secondary-900">{{ \Carbon\Carbon::parse($job->failed_at)->translatedFormat('d F Y H:i:s') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- Payload --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Payload</h3>
            </div>
            <div class="card-body">
                <pre class="text-xs bg-slate-900 text-green-400 p-4 rounded-lg overflow-x-auto max-h-80 overflow-y-auto"><code>{{ json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
            </div>
        </div>
    </div>

    {{-- Exception --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Exception</h3>
        </div>
        <div class="card-body">
            <pre class="text-xs bg-slate-900 text-red-400 p-4 rounded-lg overflow-x-auto max-h-96 overflow-y-auto whitespace-pre-wrap"><code>{{ $job->exception }}</code></pre>
        </div>
    </div>
@endsection
