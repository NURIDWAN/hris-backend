@extends('superadmin.layouts.app')

@section('title', 'System Health')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">System Health</span>
@endsection

@section('header')
    <h1 class="text-2xl font-bold text-secondary-900">System Health</h1>
    <p class="text-secondary-600">Monitor kesehatan sistem dan status infrastruktur</p>
@endsection

@section('content')
    {{-- Overview Stat Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        {{-- PHP Version --}}
        <div class="card">
            <div class="card-body flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background-color: #ede9fe;">
                    <svg class="w-6 h-6" style="color: #7c3aed;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-secondary-600">PHP Version</p>
                    <p class="text-2xl font-bold text-secondary-900">{{ $metrics['php']['version'] }}</p>
                </div>
            </div>
        </div>

        {{-- Laravel Version --}}
        <div class="card">
            <div class="card-body flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background-color: #fee2e2;">
                    <svg class="w-6 h-6" style="color: #dc2626;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-secondary-600">Laravel Version</p>
                    <p class="text-2xl font-bold text-secondary-900">{{ $metrics['laravel']['version'] }}</p>
                </div>
            </div>
        </div>

        {{-- Disk Usage --}}
        <div class="card">
            <div class="card-body flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background-color: {{ $metrics['disk']['percentage'] > 80 ? '#fee2e2' : '#d1fae5' }};">
                    <svg class="w-6 h-6" style="color: {{ $metrics['disk']['percentage'] > 80 ? '#dc2626' : '#059669' }};" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-secondary-600">Disk</p>
                    <p class="text-2xl font-bold text-secondary-900">{{ $metrics['disk']['percentage'] }}%</p>
                </div>
            </div>
        </div>

        {{-- Database --}}
        <div class="card">
            <div class="card-body flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background-color: {{ $metrics['database']['connected'] ? '#dbeafe' : '#fee2e2' }};">
                    <svg class="w-6 h-6" style="color: {{ $metrics['database']['connected'] ? '#2563eb' : '#dc2626' }};" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-secondary-600">Database</p>
                    <p class="text-2xl font-bold text-secondary-900">
                        @if($metrics['database']['connected'])
                            {{ $metrics['database']['size_formatted'] }}
                        @else
                            <span style="color: #dc2626;">Offline</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- PHP Info --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">PHP Information</h3>
            </div>
            <div class="card-body p-0">
                <table class="w-full text-sm">
                    <tr class="border-b border-slate-100">
                        <td class="px-6 py-3 font-medium text-secondary-600 w-1/3">Version</td>
                        <td class="px-6 py-3 text-secondary-900">{{ $metrics['php']['version'] }}</td>
                    </tr>
                    <tr class="border-b border-slate-100">
                        <td class="px-6 py-3 font-medium text-secondary-600">Memory Limit</td>
                        <td class="px-6 py-3 text-secondary-900">{{ $metrics['php']['memory_limit'] }}</td>
                    </tr>
                    <tr class="border-b border-slate-100">
                        <td class="px-6 py-3 font-medium text-secondary-600">Max Execution</td>
                        <td class="px-6 py-3 text-secondary-900">{{ $metrics['php']['max_execution_time'] }}s</td>
                    </tr>
                    <tr class="border-b border-slate-100">
                        <td class="px-6 py-3 font-medium text-secondary-600">Upload Max</td>
                        <td class="px-6 py-3 text-secondary-900">{{ $metrics['php']['upload_max_filesize'] }}</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-3 font-medium text-secondary-600">Post Max Size</td>
                        <td class="px-6 py-3 text-secondary-900">{{ $metrics['php']['post_max_size'] }}</td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- Laravel Info --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Laravel Information</h3>
            </div>
            <div class="card-body p-0">
                <table class="w-full text-sm">
                    <tr class="border-b border-slate-100">
                        <td class="px-6 py-3 font-medium text-secondary-600 w-1/3">Version</td>
                        <td class="px-6 py-3 text-secondary-900">{{ $metrics['laravel']['version'] }}</td>
                    </tr>
                    <tr class="border-b border-slate-100">
                        <td class="px-6 py-3 font-medium text-secondary-600">Environment</td>
                        <td class="px-6 py-3">
                            <x-badge type="{{ $metrics['laravel']['environment'] === 'production' ? 'success' : 'warning' }}">
                                {{ $metrics['laravel']['environment'] }}
                            </x-badge>
                        </td>
                    </tr>
                    <tr class="border-b border-slate-100">
                        <td class="px-6 py-3 font-medium text-secondary-600">Debug Mode</td>
                        <td class="px-6 py-3">
                            <x-badge type="{{ $metrics['laravel']['debug_mode'] ? 'danger' : 'success' }}">
                                {{ $metrics['laravel']['debug_mode'] ? 'ON' : 'OFF' }}
                            </x-badge>
                        </td>
                    </tr>
                    <tr class="border-b border-slate-100">
                        <td class="px-6 py-3 font-medium text-secondary-600">Timezone</td>
                        <td class="px-6 py-3 text-secondary-900">{{ $metrics['laravel']['timezone'] }}</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-3 font-medium text-secondary-600">Maintenance</td>
                        <td class="px-6 py-3">
                            <x-badge type="{{ $metrics['laravel']['maintenance_mode'] ? 'danger' : 'success' }}">
                                {{ $metrics['laravel']['maintenance_mode'] ? 'ON' : 'OFF' }}
                            </x-badge>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- Disk Usage --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Disk Usage</h3>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <div class="flex justify-between text-sm mb-2">
                        <span class="text-secondary-600">{{ $metrics['disk']['used_formatted'] }} terpakai dari {{ $metrics['disk']['total_formatted'] }}</span>
                        <span class="font-semibold {{ $metrics['disk']['percentage'] > 80 ? 'text-danger-600' : 'text-secondary-900' }}">{{ $metrics['disk']['percentage'] }}%</span>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-3">
                        <div class="h-3 rounded-full {{ $metrics['disk']['percentage'] > 80 ? 'bg-danger-500' : ($metrics['disk']['percentage'] > 60 ? 'bg-warning-500' : 'bg-success-500') }}"
                             style="width: {{ min($metrics['disk']['percentage'], 100) }}%"></div>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-4 text-center text-sm">
                    <div>
                        <p class="text-secondary-500">Total</p>
                        <p class="font-semibold text-secondary-900">{{ $metrics['disk']['total_formatted'] }}</p>
                    </div>
                    <div>
                        <p class="text-secondary-500">Terpakai</p>
                        <p class="font-semibold text-secondary-900">{{ $metrics['disk']['used_formatted'] }}</p>
                    </div>
                    <div>
                        <p class="text-secondary-500">Tersedia</p>
                        <p class="font-semibold text-secondary-900">{{ $metrics['disk']['free_formatted'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Database --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Database</h3>
            </div>
            <div class="card-body p-0">
                <table class="w-full text-sm">
                    <tr class="border-b border-slate-100">
                        <td class="px-6 py-3 font-medium text-secondary-600 w-1/3">Status</td>
                        <td class="px-6 py-3">
                            <x-badge type="{{ $metrics['database']['connected'] ? 'success' : 'danger' }}">
                                {{ $metrics['database']['connected'] ? 'Connected' : 'Disconnected' }}
                            </x-badge>
                        </td>
                    </tr>
                    <tr class="border-b border-slate-100">
                        <td class="px-6 py-3 font-medium text-secondary-600">Driver</td>
                        <td class="px-6 py-3 text-secondary-900">{{ $metrics['database']['driver'] }}</td>
                    </tr>
                    <tr class="border-b border-slate-100">
                        <td class="px-6 py-3 font-medium text-secondary-600">Database</td>
                        <td class="px-6 py-3 text-secondary-900 font-mono">{{ $metrics['database']['database'] }}</td>
                    </tr>
                    <tr class="border-b border-slate-100">
                        <td class="px-6 py-3 font-medium text-secondary-600">Size</td>
                        <td class="px-6 py-3 text-secondary-900">{{ $metrics['database']['size_formatted'] }}</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-3 font-medium text-secondary-600">Tables</td>
                        <td class="px-6 py-3 text-secondary-900">{{ $metrics['database']['table_count'] }}</td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- Cache --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Cache</h3>
            </div>
            <div class="card-body p-0">
                <table class="w-full text-sm">
                    <tr class="border-b border-slate-100">
                        <td class="px-6 py-3 font-medium text-secondary-600 w-1/3">Driver</td>
                        <td class="px-6 py-3 text-secondary-900">{{ $metrics['cache']['driver'] }}</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-3 font-medium text-secondary-600">Status</td>
                        <td class="px-6 py-3">
                            <x-badge type="{{ $metrics['cache']['working'] ? 'success' : 'danger' }}">
                                {{ $metrics['cache']['working'] ? 'Working' : 'Error' }}
                            </x-badge>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- Queue --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Queue</h3>
            </div>
            <div class="card-body p-0">
                <table class="w-full text-sm">
                    <tr class="border-b border-slate-100">
                        <td class="px-6 py-3 font-medium text-secondary-600 w-1/3">Driver</td>
                        <td class="px-6 py-3 text-secondary-900">{{ $metrics['queue']['driver'] }}</td>
                    </tr>
                    <tr class="border-b border-slate-100">
                        <td class="px-6 py-3 font-medium text-secondary-600">Pending Jobs</td>
                        <td class="px-6 py-3 text-secondary-900">{{ number_format($metrics['queue']['pending']) }}</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-3 font-medium text-secondary-600">Failed Jobs</td>
                        <td class="px-6 py-3">
                            @if($metrics['queue']['failed'] > 0)
                                <span class="font-semibold" style="color: #dc2626;">{{ number_format($metrics['queue']['failed']) }}</span>
                            @else
                                <span class="text-secondary-900">0</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- Server Info --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Server</h3>
            </div>
            <div class="card-body p-0">
                <table class="w-full text-sm">
                    <tr class="border-b border-slate-100">
                        <td class="px-6 py-3 font-medium text-secondary-600 w-1/3">OS</td>
                        <td class="px-6 py-3 text-secondary-900">{{ $metrics['server']['os'] }}</td>
                    </tr>
                    <tr class="border-b border-slate-100">
                        <td class="px-6 py-3 font-medium text-secondary-600">Hostname</td>
                        <td class="px-6 py-3 text-secondary-900 font-mono">{{ $metrics['server']['hostname'] }}</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-3 font-medium text-secondary-600">PHP SAPI</td>
                        <td class="px-6 py-3 text-secondary-900">{{ $metrics['server']['php_sapi'] }}</td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- Config Summary --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Configuration</h3>
            </div>
            <div class="card-body p-0">
                <table class="w-full text-sm">
                    <tr class="border-b border-slate-100">
                        <td class="px-6 py-3 font-medium text-secondary-600 w-1/3">Cache Driver</td>
                        <td class="px-6 py-3 text-secondary-900">{{ $metrics['config']['cache_driver'] }}</td>
                    </tr>
                    <tr class="border-b border-slate-100">
                        <td class="px-6 py-3 font-medium text-secondary-600">Queue Driver</td>
                        <td class="px-6 py-3 text-secondary-900">{{ $metrics['config']['queue_driver'] }}</td>
                    </tr>
                    <tr class="border-b border-slate-100">
                        <td class="px-6 py-3 font-medium text-secondary-600">Session Driver</td>
                        <td class="px-6 py-3 text-secondary-900">{{ $metrics['config']['session_driver'] }}</td>
                    </tr>
                    <tr class="border-b border-slate-100">
                        <td class="px-6 py-3 font-medium text-secondary-600">Mail Driver</td>
                        <td class="px-6 py-3 text-secondary-900">{{ $metrics['config']['mail_driver'] }}</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-3 font-medium text-secondary-600">Filesystem</td>
                        <td class="px-6 py-3 text-secondary-900">{{ $metrics['config']['filesystem_driver'] }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
@endsection
