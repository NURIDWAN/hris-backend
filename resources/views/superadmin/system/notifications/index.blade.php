@extends('superadmin.layouts.app')

@section('title', 'Notifications')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Notifications</span>
@endsection

@section('header')
    <h1 class="text-2xl font-bold text-secondary-900">Notifications</h1>
    <p class="text-secondary-600">Monitor semua notifikasi yang dikirim ke pengguna</p>
@endsection

@section('content')
    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="card">
            <div class="card-body flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background-color: #dbeafe;">
                    <svg class="w-6 h-6" style="color: #2563eb;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-secondary-600">Total</p>
                    <p class="text-2xl font-bold text-secondary-900">{{ number_format($stats['total']) }}</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background-color: #fef3c7;">
                    <svg class="w-6 h-6" style="color: #d97706;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-secondary-600">Belum Dibaca</p>
                    <p class="text-2xl font-bold" style="color: #d97706;">{{ number_format($stats['unread']) }}</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background-color: #d1fae5;">
                    <svg class="w-6 h-6" style="color: #059669;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-secondary-600">Hari Ini</p>
                    <p class="text-2xl font-bold" style="color: #059669;">{{ number_format($stats['today']) }}</p>
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
                    <p class="text-sm text-secondary-600">Push Error</p>
                    <p class="text-2xl font-bold" style="color: #dc2626;">{{ number_format($stats['push_errors']) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-6">
        <div class="card-body-sm">
            <form method="GET" action="{{ route('superadmin.system.notifications.index') }}" class="flex flex-wrap gap-4">
                <div class="w-48">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari notifikasi..." class="input w-full">
                </div>
                <div class="w-40">
                    <select name="type" class="input w-full">
                        <option value="">Semua Tipe</option>
                        @foreach($types as $type)
                            <option value="{{ $type }}" {{ request('type') == $type ? 'selected' : '' }}>
                                {{ $type }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="w-40">
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="input w-full" title="Dari Tanggal">
                </div>
                <div class="w-40">
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="input w-full" title="Sampai Tanggal">
                </div>
                <button type="submit" class="btn btn-primary">Filter</button>
                @if(request()->hasAny(['search', 'type', 'date_from', 'date_to']))
                    <a href="{{ route('superadmin.system.notifications.index') }}" class="btn btn-secondary">Reset</a>
                @endif
            </form>
        </div>
    </div>

    {{-- Notifications Table --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Notifications</h3>
        </div>
        <div class="card-body p-0">
            <x-table>
                <x-slot name="header">
                    <th class="px-6 py-3 text-left">Waktu</th>
                    <th class="px-6 py-3 text-left">Perusahaan</th>
                    <th class="px-6 py-3 text-left">User</th>
                    <th class="px-6 py-3 text-left">Notifikasi</th>
                    <th class="px-6 py-3 text-left">Tipe</th>
                    <th class="px-6 py-3 text-left">Status</th>
                </x-slot>

                @forelse($notifications as $notification)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4">
                            <p class="text-sm text-secondary-900">{{ $notification->created_at->translatedFormat('d M Y') }}</p>
                            <p class="text-xs text-secondary-500">{{ $notification->created_at->format('H:i:s') }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm text-secondary-900">{{ $notification->company?->name ?? '-' }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm text-secondary-900">{{ $notification->user?->name ?? '-' }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm font-medium text-secondary-900">{{ $notification->title }}</p>
                            <p class="text-xs text-secondary-500 truncate max-w-xs">{{ $notification->message }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <x-badge type="info">{{ $notification->type }}</x-badge>
                        </td>
                        <td class="px-6 py-4">
                            @if($notification->isRead())
                                <x-badge type="success">Dibaca</x-badge>
                            @else
                                <x-badge type="warning">Belum Dibaca</x-badge>
                            @endif
                            @if($notification->push_error)
                                <x-badge type="danger">Push Error</x-badge>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-secondary-500">
                            Belum ada notifikasi
                        </td>
                    </tr>
                @endforelse
            </x-table>
        </div>

        @if($notifications->hasPages())
            <div class="card-footer">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
@endsection
