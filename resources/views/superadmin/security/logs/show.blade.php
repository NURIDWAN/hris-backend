@extends('superadmin.layouts.app')

@section('title', 'Detail Log #' . $securityLog->id)

@section('header')
    <div class="flex items-center gap-4">
        <a href="{{ route('superadmin.security.logs.index') }}" class="btn btn-ghost">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Detail Security Log</h1>
            <p class="text-secondary-600">Log #{{ $securityLog->id }}</p>
        </div>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Info --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Informasi Event</h3>
                </div>
                <div class="card-body">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Event Type</dt>
                            <dd class="mt-1 text-sm text-secondary-900 font-semibold">{{ $securityLog->event_type }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Severity</dt>
                            <dd class="mt-1">
                                <x-badge type="{{ $securityLog->severity_color }}">{{ $securityLog->severity }}</x-badge>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Waktu</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $securityLog->created_at->format('d M Y H:i:s') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">User</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $securityLog->user?->name ?? '-' }}</dd>
                        </div>
                        <div class="md:col-span-2">
                            <dt class="text-sm font-medium text-secondary-500">Description</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $securityLog->description ?? '-' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Request Details</h3>
                </div>
                <div class="card-body">
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">URL</dt>
                            <dd class="mt-1 text-sm text-secondary-900 break-all">
                                <code class="bg-slate-100 px-2 py-1 rounded">{{ $securityLog->method }} {{ $securityLog->url }}</code>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">User Agent</dt>
                            <dd class="mt-1 text-sm text-secondary-900 break-all">{{ $securityLog->user_agent ?? '-' }}</dd>
                        </div>
                        @if($securityLog->payload)
                            <div>
                                <dt class="text-sm font-medium text-secondary-500">Payload</dt>
                                <dd class="mt-1">
                                    <pre class="bg-slate-900 text-slate-100 p-4 rounded-lg text-sm overflow-x-auto">{{ json_encode($securityLog->payload, JSON_PRETTY_PRINT) }}</pre>
                                </dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">IP Information</h3>
                </div>
                <div class="card-body">
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">IP Address</dt>
                            <dd class="mt-1">
                                <code class="text-lg bg-slate-100 px-3 py-2 rounded block">{{ $securityLog->ip_address }}</code>
                            </dd>
                        </div>
                        @if($securityLog->country)
                            <div>
                                <dt class="text-sm font-medium text-secondary-500">Country</dt>
                                <dd class="mt-1 text-sm text-secondary-900">{{ $securityLog->country }}</dd>
                            </div>
                        @endif
                        @if($securityLog->city)
                            <div>
                                <dt class="text-sm font-medium text-secondary-500">City</dt>
                                <dd class="mt-1 text-sm text-secondary-900">{{ $securityLog->city }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Actions</h3>
                </div>
                <div class="card-body space-y-3">
                    <form method="POST" action="{{ route('superadmin.security.logs.block-ip', $securityLog) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="block text-sm font-medium text-secondary-700 mb-1">Reason</label>
                            <input type="text" name="reason" value="Blocked from log #{{ $securityLog->id }}" class="input w-full">
                        </div>
                        <div class="mb-3">
                            <label class="block text-sm font-medium text-secondary-700 mb-1">Duration (menit, kosongkan = permanent)</label>
                            <input type="number" name="duration" placeholder="60" min="1" class="input w-full">
                        </div>
                        <button type="submit" class="btn btn-danger w-full">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                            </svg>
                            Block IP
                        </button>
                    </form>

                    <hr>

                    <form method="POST" action="{{ route('superadmin.security.logs.destroy', $securityLog) }}"
                          onsubmit="return confirm('Hapus log ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-secondary w-full">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Hapus Log
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
