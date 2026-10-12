@extends('superadmin.layouts.app')

@section('title', 'Detail Audit Log')

@section('breadcrumb')
    <a href="{{ route('superadmin.system.audit-logs.index') }}" class="text-slate-500 hover:text-primary-600">Audit Logs</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Detail</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Detail Audit Log</h1>
            <p class="text-secondary-500 mt-1">Informasi lengkap perubahan data.</p>
        </div>
        <a href="{{ route('superadmin.system.audit-logs.index') }}" class="btn btn-secondary">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali
        </a>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Info --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Activity Summary --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Ringkasan Aktivitas</h3>
                </div>
                <div class="card-body">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            @switch($activity->event)
                                @case('created')
                                    <div class="w-12 h-12 rounded-full bg-success-100 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                    </div>
                                    @break
                                @case('updated')
                                    <div class="w-12 h-12 rounded-full bg-info-100 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-info-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </div>
                                    @break
                                @case('deleted')
                                    <div class="w-12 h-12 rounded-full bg-danger-100 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-danger-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </div>
                                    @break
                                @default
                                    <div class="w-12 h-12 rounded-full bg-secondary-100 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-secondary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </div>
                            @endswitch
                        </div>
                        <div class="flex-1">
                            <p class="text-lg text-secondary-900">
                                <span class="font-semibold">{{ $activity->causer?->name ?? 'System' }}</span>
                                <span class="text-secondary-500">{{ $activity->event_label }}</span>
                                <span class="font-semibold">{{ $activity->subject_type_label }}</span>
                            </p>
                            @if($activity->description)
                                <p class="text-secondary-600 mt-1">{{ $activity->description }}</p>
                            @endif
                            <p class="text-sm text-secondary-400 mt-2">
                                {{ $activity->created_at->format('d F Y, H:i:s') }}
                                <span class="mx-1">&bull;</span>
                                {{ $activity->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Changes Detail --}}
            @if($activity->event === 'updated' && $activity->properties->has('attributes') && $activity->properties->has('old'))
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Detail Perubahan</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="w-full">
                            <thead class="bg-secondary-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-secondary-500 uppercase tracking-wider">Field</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-secondary-500 uppercase tracking-wider">Nilai Lama</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-secondary-500 uppercase tracking-wider">Nilai Baru</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-secondary-100">
                                @foreach($activity->properties['attributes'] as $field => $newValue)
                                    <tr>
                                        <td class="px-4 py-3 text-sm font-medium text-secondary-900">{{ ucfirst(str_replace('_', ' ', $field)) }}</td>
                                        <td class="px-4 py-3 text-sm text-secondary-500">
                                            @if(isset($activity->properties['old'][$field]))
                                                <span class="line-through text-danger-500">{{ is_array($activity->properties['old'][$field]) ? json_encode($activity->properties['old'][$field]) : $activity->properties['old'][$field] }}</span>
                                            @else
                                                <span class="text-secondary-400 italic">-</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-success-600">
                                            {{ is_array($newValue) ? json_encode($newValue) : $newValue }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- Created Attributes --}}
            @if($activity->event === 'created' && $activity->properties->has('attributes'))
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Data yang Dibuat</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="w-full">
                            <thead class="bg-secondary-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-secondary-500 uppercase tracking-wider">Field</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-secondary-500 uppercase tracking-wider">Nilai</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-secondary-100">
                                @foreach($activity->properties['attributes'] as $field => $value)
                                    <tr>
                                        <td class="px-4 py-3 text-sm font-medium text-secondary-900">{{ ucfirst(str_replace('_', ' ', $field)) }}</td>
                                        <td class="px-4 py-3 text-sm text-secondary-900">{{ is_array($value) ? json_encode($value) : $value }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- Raw JSON --}}
            @if($activity->properties && $activity->properties->isNotEmpty())
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Data Mentah (JSON)</h3>
                    </div>
                    <div class="card-body">
                        <pre class="text-xs bg-secondary-50 p-4 rounded-lg overflow-x-auto"><code>{{ json_encode($activity->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                    </div>
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Informasi</h3>
                </div>
                <div class="card-body space-y-4">
                    <div>
                        <label class="text-xs font-medium text-secondary-500 uppercase">ID</label>
                        <p class="text-sm text-secondary-900 mt-1">#{{ $activity->id }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-secondary-500 uppercase">Event</label>
                        <p class="mt-1">
                            @switch($activity->event)
                                @case('created')
                                    <span class="px-2 py-1 bg-success-100 text-success-700 rounded text-sm">Dibuat</span>
                                    @break
                                @case('updated')
                                    <span class="px-2 py-1 bg-info-100 text-info-700 rounded text-sm">Diperbarui</span>
                                    @break
                                @case('deleted')
                                    <span class="px-2 py-1 bg-danger-100 text-danger-700 rounded text-sm">Dihapus</span>
                                    @break
                                @default
                                    <span class="px-2 py-1 bg-secondary-100 text-secondary-700 rounded text-sm">{{ $activity->event }}</span>
                            @endswitch
                        </p>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-secondary-500 uppercase">Modul</label>
                        <p class="text-sm text-secondary-900 mt-1">
                            <span class="px-2 py-1 bg-secondary-100 rounded">{{ ucfirst(str_replace('_', ' ', $activity->log_name ?? 'default')) }}</span>
                        </p>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-secondary-500 uppercase">Tipe Subject</label>
                        <p class="text-sm text-secondary-900 mt-1">{{ $activity->subject_type_label }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-secondary-500 uppercase">Subject ID</label>
                        <p class="text-sm text-secondary-900 mt-1">{{ $activity->subject_id ?? '-' }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-secondary-500 uppercase">Perusahaan</label>
                        <p class="text-sm text-secondary-900 mt-1">{{ $activity->company?->name ?? '-' }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-secondary-500 uppercase">Waktu</label>
                        <p class="text-sm text-secondary-900 mt-1">{{ $activity->created_at->format('d M Y, H:i:s') }}</p>
                    </div>
                </div>
            </div>

            {{-- Causer Info --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Pelaku</h3>
                </div>
                <div class="card-body">
                    @if($activity->causer)
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center">
                                <span class="text-primary-700 font-semibold text-sm">{{ strtoupper(substr($activity->causer->name, 0, 2)) }}</span>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-secondary-900">{{ $activity->causer->name }}</p>
                                <p class="text-xs text-secondary-500">{{ $activity->causer->email }}</p>
                            </div>
                        </div>
                    @else
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-secondary-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-secondary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-secondary-900">System</p>
                                <p class="text-xs text-secondary-500">Otomatis oleh sistem</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
