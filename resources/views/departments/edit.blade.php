@extends('layouts.admin')

@section('title', 'Edit Departemen')

@section('breadcrumb')
    <a href="{{ route('departments.index') }}" class="text-slate-500 hover:text-primary-600">Departemen</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Edit {{ $department->name }}</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Edit Departemen</h1>
            <p class="text-secondary-500 mt-1">Perbarui informasi departemen {{ $department->name }}.</p>
        </div>
        <a href="{{ route('departments.index') }}" class="btn btn-ghost">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali
        </a>
    </div>
@endsection

@section('content')
    <div class="max-w-2xl">
        <form action="{{ route('departments.update', $department) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Informasi Departemen</h3>
                </div>
                <div class="card-body space-y-4">
                    {{-- Name --}}
                    <div>
                        <label for="name" class="block text-sm font-medium text-secondary-700 mb-1">
                            Nama Departemen <span class="text-danger-500">*</span>
                        </label>
                        <input type="text" name="name" id="name" value="{{ old('name', $department->name) }}"
                               class="input w-full @error('name') border-danger-500 @enderror"
                               placeholder="Contoh: Engineering" required>
                        @error('name')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Code --}}
                    <div>
                        <label for="code" class="block text-sm font-medium text-secondary-700 mb-1">
                            Kode Departemen
                        </label>
                        <input type="text" name="code" id="code" value="{{ old('code', $department->code) }}"
                               class="input w-full @error('code') border-danger-500 @enderror"
                               placeholder="Contoh: ENG">
                        @error('code')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-sm text-secondary-500">Kode unik untuk identifikasi departemen.</p>
                    </div>

                    {{-- Parent Department --}}
                    <div>
                        <label for="parent_id" class="block text-sm font-medium text-secondary-700 mb-1">
                            Departemen Induk
                        </label>
                        <select name="parent_id" id="parent_id" class="input w-full @error('parent_id') border-danger-500 @enderror">
                            <option value="">Tidak ada (Top Level)</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ old('parent_id', $department->parent_id) == $dept->id ? 'selected' : '' }}>
                                    {{ $dept->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('parent_id')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Description --}}
                    <div>
                        <label for="description" class="block text-sm font-medium text-secondary-700 mb-1">
                            Deskripsi
                        </label>
                        <textarea name="description" id="description" rows="3"
                                  class="input w-full @error('description') border-danger-500 @enderror"
                                  placeholder="Deskripsi singkat tentang departemen ini...">{{ old('description', $department->description) }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Status --}}
                    <div class="flex items-center gap-3">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" id="is_active" value="1"
                               class="w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500"
                               {{ old('is_active', $department->is_active) ? 'checked' : '' }}>
                        <label for="is_active" class="text-sm font-medium text-secondary-700">
                            Departemen aktif
                        </label>
                    </div>

                    {{-- Info Stats --}}
                    @if($department->employees_count > 0 || $department->positions->count() > 0)
                        <div class="bg-info-50 border border-info-200 rounded-lg p-4">
                            <div class="flex gap-4">
                                <div class="text-center">
                                    <p class="text-2xl font-bold text-info-700">{{ $department->employees_count }}</p>
                                    <p class="text-sm text-info-600">Karyawan</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-2xl font-bold text-info-700">{{ $department->positions->count() }}</p>
                                    <p class="text-sm text-info-600">Jabatan</p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="card-footer flex justify-end gap-3">
                    <a href="{{ route('departments.index') }}" class="btn btn-ghost">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Perbarui
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
