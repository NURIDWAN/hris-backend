@extends('layouts.admin')

@section('title', 'Tambah Role')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Pengaturan</span>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <a href="{{ route('settings.roles.index') }}" class="text-slate-500 hover:text-primary-600">Role</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Tambah</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Tambah Role</h1>
            <p class="text-secondary-500 mt-1">Buat role baru dengan hak akses tertentu.</p>
        </div>
        <a href="{{ route('settings.roles.index') }}" class="btn btn-ghost">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali
        </a>
    </div>
@endsection

@section('content')
    <form action="{{ route('settings.roles.store') }}" method="POST">
        @csrf

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Informasi Role</h3>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-1 gap-6">
                    {{-- Name --}}
                    <div>
                        <label for="name" class="block text-sm font-medium text-secondary-700 mb-1">
                            Nama Role <span class="text-danger-500">*</span>
                        </label>
                        <input type="text" name="name" id="name"
                               value="{{ old('name') }}"
                               class="input w-full @error('name') border-danger-500 @enderror"
                               placeholder="contoh: hr-manager"
                               required>
                        <p class="mt-1 text-xs text-secondary-500">Gunakan huruf kecil dan strip untuk pemisah.</p>
                        @error('name')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Permissions --}}
                    <div>
                        <label class="block text-sm font-medium text-secondary-700 mb-3">
                            Hak Akses
                        </label>
                        @php
                            $groupedPermissions = $permissions->groupBy(function($permission) {
                                return explode(' ', $permission->name)[1] ?? 'other';
                            });
                        @endphp

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach($groupedPermissions as $group => $perms)
                                <div class="bg-secondary-50 rounded-lg p-4">
                                    <h4 class="font-medium text-secondary-900 mb-3 capitalize">{{ $group }}</h4>
                                    <div class="space-y-2">
                                        @foreach($perms as $permission)
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                                                       {{ in_array($permission->name, old('permissions', [])) ? 'checked' : '' }}
                                                       class="rounded border-secondary-300 text-primary-600 focus:ring-primary-500">
                                                <span class="text-sm text-secondary-700">{{ $permission->name }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @error('permissions')
                            <p class="mt-2 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
            <div class="card-footer flex justify-end gap-3">
                <a href="{{ route('settings.roles.index') }}" class="btn btn-ghost">Batal</a>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Tambah Role
                </button>
            </div>
        </div>
    </form>
@endsection
