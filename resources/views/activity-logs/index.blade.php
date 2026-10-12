@extends('layouts.admin')

@section('title', 'Log Aktivitas')

@section('breadcrumb')
    <a href="{{ route('settings.company-profile.index') }}" class="text-slate-500 hover:text-primary-600">Settings</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Log Aktivitas</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Log Aktivitas</h1>
            <p class="text-secondary-500 mt-1">Riwayat semua perubahan data di sistem.</p>
        </div>
    </div>
@endsection

@section('content')
    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body-sm">
            <form action="{{ route('settings.activity-logs.index') }}" method="GET" class="flex flex-wrap items-end gap-3">
                {{-- Search --}}
                <div class="flex-1 min-w-[200px]">
                    <label for="search" class="block text-xs font-medium text-secondary-500 mb-1">Cari</label>
                    <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Cari deskripsi..." class="input w-full">
                </div>

                {{-- Log Name --}}
                <div class="w-36">
                    <label for="log_name" class="block text-xs font-medium text-secondary-500 mb-1">Modul</label>
                    <select name="log_name" id="log_name" class="input w-full">
                        <option value="">Semua</option>
                        @foreach($logNames as $logName)
                            <option value="{{ $logName }}" {{ request('log_name') === $logName ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $logName)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Event --}}
                <div class="w-32">
                    <label for="event" class="block text-xs font-medium text-secondary-500 mb-1">Event</label>
                    <select name="event" id="event" class="input w-full">
                        <option value="">Semua</option>
                        <option value="created" {{ request('event') === 'created' ? 'selected' : '' }}>Dibuat</option>
                        <option value="updated" {{ request('event') === 'updated' ? 'selected' : '' }}>Diperbarui</option>
                        <option value="deleted" {{ request('event') === 'deleted' ? 'selected' : '' }}>Dihapus</option>
                    </select>
                </div>

                {{-- User --}}
                <div class="w-40">
                    <label for="causer_id" class="block text-xs font-medium text-secondary-500 mb-1">User</label>
                    <select name="causer_id" id="causer_id" class="input w-full">
                        <option value="">Semua</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('causer_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Date From --}}
                <div class="w-36">
                    <label for="date_from" class="block text-xs font-medium text-secondary-500 mb-1">Dari Tanggal</label>
                    <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" class="input w-full">
                </div>

                {{-- Date To --}}
                <div class="w-36">
                    <label for="date_to" class="block text-xs font-medium text-secondary-500 mb-1">Sampai Tanggal</label>
                    <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" class="input w-full">
                </div>

                <div class="flex items-center gap-2">
                    @if(request()->hasAny(['search', 'log_name', 'event', 'causer_id', 'date_from', 'date_to']))
                        <a href="{{ route('settings.activity-logs.index') }}" class="btn btn-ghost btn-sm">Reset</a>
                    @endif
                    <button type="submit" class="btn btn-primary btn-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Activity List --}}
    <div class="card">
        <div class="divide-y divide-secondary-100">
            @forelse($activities as $activity)
                <div class="p-4 hover:bg-secondary-50 transition-colors">
                    <div class="flex items-start gap-4">
                        {{-- Event Icon --}}
                        <div class="flex-shrink-0">
                            @switch($activity->event)
                                @case('created')
                                    <div class="w-10 h-10 rounded-full bg-success-100 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                    </div>
                                    @break
                                @case('updated')
                                    <div class="w-10 h-10 rounded-full bg-info-100 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-info-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </div>
                                    @break
                                @case('deleted')
                                    <div class="w-10 h-10 rounded-full bg-danger-100 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-danger-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </div>
                                    @break
                                @default
                                    <div class="w-10 h-10 rounded-full bg-secondary-100 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-secondary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </div>
                            @endswitch
                        </div>

                        {{-- Content --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm text-secondary-900">
                                        <span class="font-medium">{{ $activity->causer?->name ?? 'System' }}</span>
                                        <span class="text-secondary-500">{{ $activity->event_label }}</span>
                                        <span class="font-medium">{{ $activity->subject_type_label }}</span>
                                        @if($activity->subject)
                                            <span class="text-secondary-500">-</span>
                                            <a href="{{ route('settings.activity-logs.show', $activity) }}" class="text-primary-600 hover:underline">
                                                {{ $activity->subject->name ?? $activity->subject->full_name ?? $activity->subject->title ?? "#{$activity->subject_id}" }}
                                            </a>
                                        @endif
                                    </p>
                                    <p class="text-xs text-secondary-400 mt-1">
                                        {{ $activity->created_at->diffForHumans() }}
                                        <span class="mx-1">•</span>
                                        {{ $activity->created_at->format('d M Y, H:i') }}
                                        @if($activity->log_name)
                                            <span class="mx-1">•</span>
                                            <span class="px-1.5 py-0.5 bg-secondary-100 rounded text-secondary-600">{{ ucfirst(str_replace('_', ' ', $activity->log_name)) }}</span>
                                        @endif
                                    </p>
                                </div>
                                <a href="{{ route('settings.activity-logs.show', $activity) }}" class="flex-shrink-0 p-1.5 text-secondary-400 hover:text-primary-600 hover:bg-primary-50 rounded-md transition-colors" title="Lihat Detail">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            </div>

                            {{-- Changes Preview --}}
                            @if($activity->changes_summary && count($activity->changes_summary) > 0)
                                <div class="mt-2 text-xs text-secondary-500">
                                    @foreach(array_slice($activity->changes_summary, 0, 2) as $field => $change)
                                        <span class="inline-block mr-2">
                                            <span class="text-secondary-600">{{ ucfirst(str_replace('_', ' ', $field)) }}:</span>
                                            @if($change['old'])
                                                <span class="line-through text-danger-500">{{ is_array($change['old']) ? json_encode($change['old']) : \Illuminate\Support\Str::limit($change['old'], 20) }}</span>
                                                <span class="mx-1">→</span>
                                            @endif
                                            <span class="text-success-600">{{ is_array($change['new']) ? json_encode($change['new']) : \Illuminate\Support\Str::limit($change['new'], 20) }}</span>
                                        </span>
                                    @endforeach
                                    @if(count($activity->changes_summary) > 2)
                                        <span class="text-secondary-400">+{{ count($activity->changes_summary) - 2 }} perubahan lain</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-12 text-center">
                    <svg class="w-12 h-12 text-secondary-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    <p class="text-secondary-500">Belum ada log aktivitas.</p>
                </div>
            @endforelse
        </div>

        @if($activities->hasPages())
            <div class="card-footer">
                {{ $activities->links() }}
            </div>
        @endif
    </div>
@endsection
