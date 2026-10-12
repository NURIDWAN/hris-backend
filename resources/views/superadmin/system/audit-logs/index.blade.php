@extends('superadmin.layouts.app')

@section('title', 'Audit Logs')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Audit Logs</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Audit Logs</h1>
            <p class="text-secondary-500 mt-1">Riwayat aktivitas seluruh perusahaan.</p>
        </div>
    </div>
@endsection

@section('content')
    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="stat-card">
            <div class="stat-card-icon" style="background-color: #dbeafe;">
                <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
            <div>
                <div class="stat-card-value">{{ number_format($stats['total']) }}</div>
                <div class="stat-card-label">Total Log</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-icon" style="background-color: #f0fdf4;">
                <svg class="w-6 h-6 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <div class="stat-card-value">{{ number_format($stats['today']) }}</div>
                <div class="stat-card-label">Hari Ini</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-icon" style="background-color: #ecfdf5;">
                <svg class="w-6 h-6 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            </div>
            <div>
                <div class="stat-card-value">{{ number_format($stats['created']) }}</div>
                <div class="stat-card-label">Dibuat</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-icon" style="background-color: #eff6ff;">
                <svg class="w-6 h-6 text-info-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            </div>
            <div>
                <div class="stat-card-value">{{ number_format($stats['updated']) }}</div>
                <div class="stat-card-label">Diperbarui</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-icon" style="background-color: #fef2f2;">
                <svg class="w-6 h-6 text-danger-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </div>
            <div>
                <div class="stat-card-value">{{ number_format($stats['deleted']) }}</div>
                <div class="stat-card-label">Dihapus</div>
            </div>
        </div>
    </div>

    <div class="card">
        {{-- Filters --}}
        <div class="card-body border-b">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-3">
                <div>
                    <select name="company_id" class="input w-full text-sm">
                        <option value="">Semua Perusahaan</option>
                        @foreach($companies as $id => $name)
                            <option value="{{ $id }}" {{ request('company_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select name="event" class="input w-full text-sm">
                        <option value="">Semua Event</option>
                        <option value="created" {{ request('event') === 'created' ? 'selected' : '' }}>Dibuat</option>
                        <option value="updated" {{ request('event') === 'updated' ? 'selected' : '' }}>Diperbarui</option>
                        <option value="deleted" {{ request('event') === 'deleted' ? 'selected' : '' }}>Dihapus</option>
                    </select>
                </div>
                <div>
                    <select name="log_name" class="input w-full text-sm">
                        <option value="">Semua Modul</option>
                        @foreach($logNames as $logName)
                            <option value="{{ $logName }}" {{ request('log_name') === $logName ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $logName)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="input w-full text-sm" placeholder="Dari tanggal">
                </div>
                <div>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="input w-full text-sm" placeholder="Sampai tanggal">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-1">Filter</button>
                    <a href="{{ route('superadmin.system.audit-logs.index') }}" class="btn btn-secondary btn-sm">Reset</a>
                </div>
            </form>
        </div>

        {{-- Table --}}
        <x-table>
            <x-slot name="header">
                <th>Waktu</th>
                <th>Pelaku</th>
                <th>Event</th>
                <th>Modul</th>
                <th>Perusahaan</th>
                <th>Deskripsi</th>
                <th></th>
            </x-slot>
            @forelse($activities as $activity)
                <tr>
                    <td class="text-xs text-secondary-500 whitespace-nowrap">
                        {{ $activity->created_at->format('d M Y') }}<br>
                        {{ $activity->created_at->format('H:i:s') }}
                    </td>
                    <td class="text-sm">
                        {{ $activity->causer?->name ?? 'System' }}
                    </td>
                    <td>
                        @switch($activity->event)
                            @case('created')
                                <span class="px-2 py-0.5 bg-success-100 text-success-700 rounded text-xs font-medium">Dibuat</span>
                                @break
                            @case('updated')
                                <span class="px-2 py-0.5 bg-info-100 text-info-700 rounded text-xs font-medium">Diperbarui</span>
                                @break
                            @case('deleted')
                                <span class="px-2 py-0.5 bg-danger-100 text-danger-700 rounded text-xs font-medium">Dihapus</span>
                                @break
                            @default
                                <span class="px-2 py-0.5 bg-secondary-100 text-secondary-700 rounded text-xs font-medium">{{ ucfirst($activity->event) }}</span>
                        @endswitch
                    </td>
                    <td class="text-sm text-secondary-600">
                        {{ $activity->subject_type_label }}
                    </td>
                    <td class="text-sm text-secondary-600">
                        @if($activity->company_id)
                            {{ $activity->company?->name ?? '#' . $activity->company_id }}
                        @else
                            <span class="text-secondary-400">-</span>
                        @endif
                    </td>
                    <td class="text-sm text-secondary-600 max-w-xs truncate">
                        {{ $activity->description }}
                    </td>
                    <td>
                        <a href="{{ route('superadmin.system.audit-logs.show', $activity->id) }}" class="btn btn-ghost btn-sm text-primary-600">
                            Detail
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-8 text-secondary-500">Tidak ada data.</td>
                </tr>
            @endforelse
        </x-table>

        {{-- Pagination --}}
        @if($activities->hasPages())
            <div class="card-footer">
                {{ $activities->links() }}
            </div>
        @endif
    </div>
@endsection
