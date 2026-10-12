@extends('layouts.admin')

@section('title', 'Profil Saya')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Profil Saya</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Profil Saya</h1>
            <p class="text-secondary-500 mt-1">Kelola informasi profil dan keamanan akun Anda.</p>
        </div>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Profile Info Card --}}
        <div class="lg:col-span-2">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Informasi Profil</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        {{-- Avatar --}}
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-secondary-700 mb-3">
                                Foto Profil
                            </label>
                            <div class="flex items-center gap-4">
                                <div class="w-20 h-20 rounded-full bg-primary-500 flex items-center justify-center text-white text-2xl font-bold overflow-hidden">
                                    @if($user->avatar)
                                        <img src="{{ Storage::url($user->avatar) }}" alt="{{ $user->name }}" class="w-full h-full object-cover">
                                    @else
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    @endif
                                </div>
                                <div>
                                    <input type="file" name="avatar" id="avatar" accept="image/*" class="hidden"
                                           onchange="document.getElementById('avatar-name').textContent = this.files[0]?.name || 'Pilih file...'">
                                    <label for="avatar" class="btn btn-secondary cursor-pointer">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        Ganti Foto
                                    </label>
                                    <p class="text-xs text-secondary-500 mt-1" id="avatar-name">JPG, PNG, GIF. Maksimal 2MB.</p>
                                    @error('avatar')
                                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Name --}}
                        <div class="mb-4">
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
                        <div class="mb-4">
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

                        {{-- Submit --}}
                        <div class="flex justify-end">
                            <button type="submit" class="btn btn-primary">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Side Info --}}
        <div class="space-y-6">
            {{-- Account Info --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Informasi Akun</h3>
                </div>
                <div class="card-body">
                    <div class="space-y-4">
                        <div>
                            <span class="text-sm text-secondary-500">Perusahaan</span>
                            <p class="font-medium text-secondary-900">{{ $user->company->name }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-secondary-500">Role</span>
                            <p class="font-medium text-secondary-900">{{ ucfirst($user->roles->first()?->name ?? '-') }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-secondary-500">Bergabung</span>
                            <p class="font-medium text-secondary-900">{{ $user->created_at->format('d M Y') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Change Password --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Ubah Password</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('profile.password') }}" method="POST">
                        @csrf
                        @method('PUT')

                        {{-- Current Password --}}
                        <div class="mb-4">
                            <label for="current_password" class="block text-sm font-medium text-secondary-700 mb-1">
                                Password Saat Ini <span class="text-danger-500">*</span>
                            </label>
                            <input type="password" name="current_password" id="current_password"
                                   class="input w-full @error('current_password') border-danger-500 @enderror"
                                   required>
                            @error('current_password')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- New Password --}}
                        <div class="mb-4">
                            <label for="password" class="block text-sm font-medium text-secondary-700 mb-1">
                                Password Baru <span class="text-danger-500">*</span>
                            </label>
                            <input type="password" name="password" id="password"
                                   class="input w-full @error('password') border-danger-500 @enderror"
                                   required>
                            @error('password')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Confirm Password --}}
                        <div class="mb-4">
                            <label for="password_confirmation" class="block text-sm font-medium text-secondary-700 mb-1">
                                Konfirmasi Password <span class="text-danger-500">*</span>
                            </label>
                            <input type="password" name="password_confirmation" id="password_confirmation"
                                   class="input w-full"
                                   required>
                        </div>

                        {{-- Submit --}}
                        <button type="submit" class="btn btn-secondary w-full">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                            </svg>
                            Ubah Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
