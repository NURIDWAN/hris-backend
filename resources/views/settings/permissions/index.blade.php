@extends('layouts.admin')

@section('title', 'Kelola Permission')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Pengaturan</span>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Permission</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Kelola Permission</h1>
            <p class="text-secondary-500 mt-1">Atur hak akses yang tersedia dalam sistem.</p>
        </div>
        <a href="{{ route('settings.permissions.create') }}" class="btn btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Tambah Permission
        </a>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($groupedPermissions as $group => $perms)
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title capitalize">{{ $group }}</h3>
                    <span class="text-sm text-secondary-500">{{ $perms->count() }} permission</span>
                </div>
                <div class="card-body p-0">
                    <ul class="divide-y divide-secondary-100">
                        @foreach($perms as $permission)
                            <li class="px-4 py-3 flex items-center justify-between hover:bg-secondary-50">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                        </svg>
                                    </div>
                                    <span class="text-sm text-secondary-700">{{ $permission->name }}</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <a href="{{ route('settings.permissions.edit', $permission) }}" class="btn btn-ghost btn-sm">
                                        <svg class="w-4 h-4 text-secondary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <button type="button"
                                            @click="$dispatch('confirm-dialog', {
                                                title: 'Hapus Permission',
                                                message: 'Apakah Anda yakin ingin menghapus permission {{ $permission->name }}?',
                                                confirmText: 'Ya, Hapus',
                                                type: 'danger',
                                                formAction: '{{ route('settings.permissions.destroy', $permission) }}'
                                            })"
                                            class="btn btn-ghost btn-sm text-danger-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @empty
            <div class="col-span-full">
                <div class="card">
                    <div class="card-body text-center py-12">
                        <svg class="w-12 h-12 mx-auto text-secondary-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                        </svg>
                        <h3 class="text-secondary-900 font-medium mb-1">Belum ada permission</h3>
                        <p class="text-secondary-500 mb-4">Buat permission baru untuk mengatur hak akses.</p>
                        <a href="{{ route('settings.permissions.create') }}" class="btn btn-primary">
                            Tambah Permission
                        </a>
                    </div>
                </div>
            </div>
        @endforelse
    </div>
@endsection
