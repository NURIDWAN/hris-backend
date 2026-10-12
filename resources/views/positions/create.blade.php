@extends('layouts.admin')

@section('title', 'Tambah Jabatan')

@section('breadcrumb')
    <a href="{{ route('positions.index') }}" class="text-slate-500 hover:text-primary-600">Jabatan</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Tambah Jabatan</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Tambah Jabatan</h1>
            <p class="text-secondary-500 mt-1">Buat jabatan baru untuk perusahaan Anda.</p>
        </div>
        <a href="{{ route('positions.index') }}" class="btn btn-ghost">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali
        </a>
    </div>
@endsection

@section('content')
    <div class="max-w-2xl">
        <form action="{{ route('positions.store') }}" method="POST">
            @csrf

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Informasi Jabatan</h3>
                </div>
                <div class="card-body space-y-4">
                    {{-- Name --}}
                    <div>
                        <label for="name" class="block text-sm font-medium text-secondary-700 mb-1">
                            Nama Jabatan <span class="text-danger-500">*</span>
                        </label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}"
                               class="input w-full @error('name') border-danger-500 @enderror"
                               placeholder="Contoh: Software Engineer" required>
                        @error('name')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Code --}}
                    <div>
                        <label for="code" class="block text-sm font-medium text-secondary-700 mb-1">
                            Kode Jabatan
                        </label>
                        <input type="text" name="code" id="code" value="{{ old('code') }}"
                               class="input w-full @error('code') border-danger-500 @enderror"
                               placeholder="Contoh: SE">
                        @error('code')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-sm text-secondary-500">Kode unik untuk identifikasi jabatan.</p>
                    </div>

                    {{-- Department --}}
                    <div>
                        <label for="department_id" class="block text-sm font-medium text-secondary-700 mb-1">
                            Departemen <span class="text-danger-500">*</span>
                        </label>
                        <select name="department_id" id="department_id" class="input w-full @error('department_id') border-danger-500 @enderror" required>
                            <option value="">Pilih Departemen</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}" {{ old('department_id') == $department->id ? 'selected' : '' }}>
                                    {{ $department->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('department_id')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Level --}}
                        <div>
                            <label for="level" class="block text-sm font-medium text-secondary-700 mb-1">
                                Level Jabatan
                            </label>
                            <input type="number" name="level" id="level" value="{{ old('level') }}"
                                   class="input w-full @error('level') border-danger-500 @enderror"
                                   placeholder="1-20" min="1" max="20">
                            @error('level')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-secondary-500">1 = tertinggi, 20 = terendah</p>
                        </div>

                        {{-- Base Salary --}}
                        <div x-data="currencyInput({{ old('base_salary', 0) }})">
                            <label for="base_salary_display" class="block text-sm font-medium text-secondary-700 mb-1">
                                Standar Gaji
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" id="base_salary_display" x-model="display" @input="updateValue($event)"
                                       class="input @error('base_salary') border-danger-500 @enderror"
                                       placeholder="0" inputmode="numeric">
                                <input type="hidden" name="base_salary" :value="value">
                            </div>
                            @error('base_salary')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Description --}}
                    <div>
                        <label for="description" class="block text-sm font-medium text-secondary-700 mb-1">
                            Deskripsi
                        </label>
                        <textarea name="description" id="description" rows="3"
                                  class="input w-full @error('description') border-danger-500 @enderror"
                                  placeholder="Deskripsi tugas dan tanggung jawab jabatan ini...">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Status --}}
                    <div class="flex items-center gap-3">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" id="is_active" value="1"
                               class="w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500"
                               {{ old('is_active', true) ? 'checked' : '' }}>
                        <label for="is_active" class="text-sm font-medium text-secondary-700">
                            Jabatan aktif
                        </label>
                    </div>
                </div>
                <div class="card-footer flex justify-end gap-3">
                    <a href="{{ route('positions.index') }}" class="btn btn-ghost">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Simpan
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
