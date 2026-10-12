@extends('layouts.admin')

@section('title', 'Edit Permission')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Pengaturan</span>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <a href="{{ route('settings.permissions.index') }}" class="text-slate-500 hover:text-primary-600">Permission</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Edit</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Edit Permission</h1>
            <p class="text-secondary-500 mt-1">Ubah nama permission.</p>
        </div>
        <a href="{{ route('settings.permissions.index') }}" class="btn btn-ghost">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali
        </a>
    </div>
@endsection

@section('content')
    <form action="{{ route('settings.permissions.update', $permission) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="card max-w-xl">
            <div class="card-header">
                <h3 class="card-title">Informasi Permission</h3>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-1 gap-6">
                    {{-- Name --}}
                    <div>
                        <label for="name" class="block text-sm font-medium text-secondary-700 mb-1">
                            Nama Permission <span class="text-danger-500">*</span>
                        </label>
                        <input type="text" name="name" id="name"
                               value="{{ old('name', $permission->name) }}"
                               class="input w-full @error('name') border-danger-500 @enderror"
                               placeholder="contoh: view reports"
                               required>
                        <p class="mt-1 text-xs text-secondary-500">Gunakan format: action resource (contoh: view employees, create payroll).</p>
                        @error('name')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
            <div class="card-footer flex justify-end gap-3">
                <a href="{{ route('settings.permissions.index') }}" class="btn btn-ghost">Batal</a>
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
