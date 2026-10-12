@extends('layouts.admin')

@section('title', 'Kelola Pengguna')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Pengaturan</span>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Pengguna</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Kelola Pengguna</h1>
            <p class="text-secondary-500 mt-1">Kelola akses pengguna dan hak akses sistem.</p>
        </div>
        <a href="{{ route('settings.users.create') }}" class="btn btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Tambah Pengguna
        </a>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                <h3 class="card-title">Daftar Pengguna</h3>
                <form action="{{ route('settings.users.index') }}" method="GET" class="flex flex-1 gap-3 sm:justify-end">
                    <div class="flex-1 sm:flex-none sm:w-64">
                        <input type="text" name="search" value="{{ request('search') }}"
                               class="input w-full" placeholder="Cari nama atau email...">
                    </div>
                    <select name="role" class="input w-auto" onchange="this.form.submit()">
                        <option value="">Semua Role</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}" {{ request('role') == $role->name ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('-', ' ', $role->name)) }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-secondary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
        <div class="overflow-x-auto">
            <x-table>
                <x-slot name="header">
                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 uppercase tracking-wider">Pengguna</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 uppercase tracking-wider">Role</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 uppercase tracking-wider">Terakhir Login</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500 uppercase tracking-wider">Aksi</th>
                </x-slot>

                @forelse($users as $user)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center">
                                    @if($user->avatar)
                                        <img src="{{ Storage::url($user->avatar) }}" alt="{{ $user->name }}" class="w-10 h-10 rounded-full object-cover">
                                    @else
                                        <span class="text-primary-600 font-medium text-sm">
                                            {{ strtoupper(substr($user->name, 0, 2)) }}
                                        </span>
                                    @endif
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium text-secondary-900">{{ $user->name }}</span>
                                        @if($user->isDemoAccount())
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700">Demo</span>
                                        @endif
                                    </div>
                                    <div class="text-sm text-secondary-500">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @foreach($user->roles as $role)
                                @php
                                    $roleColors = [
                                        'admin' => 'primary',
                                        'hr-manager' => 'info',
                                        'employee' => 'secondary',
                                    ];
                                    $color = $roleColors[$role->name] ?? 'secondary';
                                @endphp
                                <x-badge type="{{ $color }}">
                                    {{ ucfirst(str_replace('-', ' ', $role->name)) }}
                                </x-badge>
                            @endforeach
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($user->is_active)
                                <x-badge type="success">Aktif</x-badge>
                            @else
                                <x-badge type="danger">Nonaktif</x-badge>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-secondary-500">
                            {{ $user->updated_at->diffForHumans() }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('settings.users.edit', $user) }}" class="btn btn-ghost btn-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                @if($user->id !== auth()->id() && !$user->isDemoAccount())
                                    <form action="{{ route('settings.users.toggle-status', $user) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-ghost btn-sm" title="{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                            @if($user->is_active)
                                                <svg class="w-4 h-4 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                                </svg>
                                            @else
                                                <svg class="w-4 h-4 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                            @endif
                                        </button>
                                    </form>
                                    <button type="button"
                                        @click="$dispatch('confirm-dialog', {
                                            title: 'Hapus Pengguna',
                                            message: 'Apakah Anda yakin ingin menghapus pengguna {{ $user->name }}?',
                                            confirmText: 'Ya, Hapus',
                                            type: 'danger',
                                            formAction: '{{ route('settings.users.destroy', $user) }}'
                                        })"
                                        class="btn btn-ghost btn-sm text-danger-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-secondary-500">
                            <svg class="w-12 h-12 mx-auto text-secondary-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                            </svg>
                            <p>Belum ada pengguna.</p>
                            <a href="{{ route('settings.users.create') }}" class="btn btn-primary btn-sm mt-4">
                                Tambah Pengguna Pertama
                            </a>
                        </td>
                    </tr>
                @endforelse
            </x-table>
        </div>
        @if($users->hasPages())
            <div class="card-footer">
                {{ $users->links() }}
            </div>
        @endif
    </div>
@endsection
