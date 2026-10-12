@extends('layouts.admin')

@section('title', 'Edit Pengguna')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Pengaturan</span>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <a href="{{ route('settings.users.index') }}" class="text-slate-500 hover:text-primary-600">Pengguna</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Edit</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Edit Pengguna</h1>
            <p class="text-secondary-500 mt-1">Edit data pengguna {{ $user->name }}.</p>
        </div>
        <a href="{{ route('settings.users.index') }}" class="btn btn-ghost">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali
        </a>
    </div>
@endsection

@section('content')
    <form action="{{ route('settings.users.update', $user) }}" method="POST">
        @csrf
        @method('PUT')

        @if($user->isDemoAccount())
        <div class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-xl">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    <p class="font-semibold text-amber-800">Akun Demo</p>
                    <p class="text-sm text-amber-600">Password dan data sensitif akun demo tidak dapat diubah.</p>
                </div>
            </div>
        </div>
        @endif

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Informasi Pengguna</h3>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Name --}}
                    <div class="md:col-span-2">
                        <label for="name" class="block text-sm font-medium text-secondary-700 mb-1">
                            Nama Lengkap <span class="text-danger-500">*</span>
                        </label>
                        <input type="text" name="name" id="name"
                               value="{{ old('name', $user->name) }}"
                               class="input w-full @error('name') border-danger-500 @enderror"
                               required>
                        @error('name')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div>
                        <label for="email" class="block text-sm font-medium text-secondary-700 mb-1">
                            Email <span class="text-danger-500">*</span>
                        </label>
                        <input type="email" name="email" id="email"
                               value="{{ old('email', $user->email) }}"
                               class="input w-full @error('email') border-danger-500 @enderror"
                               required>
                        @error('email')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Phone --}}
                    <div>
                        <label for="phone" class="block text-sm font-medium text-secondary-700 mb-1">
                            No. Telepon
                        </label>
                        <input type="text" name="phone" id="phone"
                               value="{{ old('phone', $user->phone) }}"
                               class="input w-full @error('phone') border-danger-500 @enderror"
                               placeholder="08123456789">
                        @error('phone')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Password --}}
                    <div>
                        <label for="password" class="block text-sm font-medium text-secondary-700 mb-1">
                            Password Baru
                        </label>
                        <input type="password" name="password" id="password"
                               class="input w-full @error('password') border-danger-500 @enderror"
                               {{ $user->isDemoAccount() ? 'disabled' : '' }}>
                        @if($user->isDemoAccount())
                            <p class="mt-1 text-xs text-amber-600">Password akun demo tidak dapat diubah.</p>
                        @else
                            <p class="mt-1 text-xs text-secondary-500">Kosongkan jika tidak ingin mengubah password.</p>
                        @endif
                        @error('password')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Password Confirmation --}}
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-secondary-700 mb-1">
                            Konfirmasi Password Baru
                        </label>
                        <input type="password" name="password_confirmation" id="password_confirmation"
                               class="input w-full"
                               {{ $user->isDemoAccount() ? 'disabled' : '' }}>
                    </div>

                    {{-- Roles --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-secondary-700 mb-2">
                            Role <span class="text-danger-500">*</span>
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                            @foreach($roles as $role)
                                <label class="inline-flex items-center gap-2 p-3 border border-slate-200 rounded-xl hover:bg-slate-50 cursor-pointer transition-colors">
                                    <input type="checkbox" name="roles[]" value="{{ $role->name }}"
                                           {{ in_array($role->name, old('roles', $user->roles->pluck('name')->toArray())) ? 'checked' : '' }}
                                           class="rounded border-secondary-300 text-primary-600 focus:ring-primary-500">
                                    <span class="text-sm text-secondary-700">{{ ucfirst(str_replace('-', ' ', $role->name)) }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('roles')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Is Active --}}
                    <div>
                        <label class="block text-sm font-medium text-secondary-700 mb-1">Status</label>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1"
                                   {{ old('is_active', $user->is_active) ? 'checked' : '' }}
                                   class="rounded border-secondary-300 text-primary-600 focus:ring-primary-500"
                                   {{ $user->id === auth()->id() ? 'disabled' : '' }}>
                            <span class="text-secondary-700">Pengguna aktif</span>
                        </label>
                        @if($user->id === auth()->id())
                            <p class="mt-1 text-xs text-secondary-500">Anda tidak dapat menonaktifkan akun sendiri.</p>
                        @endif
                    </div>
                </div>
            </div>
            <div class="card-footer flex justify-end gap-3">
                <a href="{{ route('settings.users.index') }}" class="btn btn-ghost">Batal</a>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Simpan Perubahan
                </button>
            </div>
        </div>
    </form>
@endsection
