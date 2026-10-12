@extends('layouts.admin')

@section('title', 'Edit Hari Libur')

@section('breadcrumb')
    <a href="{{ route('holidays.index') }}" class="text-slate-500 hover:text-primary-600">Hari Libur</a>
    <svg class="w-4 h-4 mx-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Edit</span>
@endsection

@section('header')
    <div class="flex items-center gap-4">
        <a href="{{ route('holidays.index') }}" class="btn btn-ghost btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Edit Hari Libur</h1>
            <p class="text-secondary-500 mt-1">Perbarui informasi hari libur.</p>
        </div>
    </div>
@endsection

@section('content')
    <div class="max-w-2xl">
        <div class="card">
            <form action="{{ route('holidays.update', $holiday) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="card-body space-y-6">
                    {{-- Name --}}
                    <div>
                        <label for="name" class="block text-sm font-medium text-secondary-700 mb-1">
                            Nama Hari Libur <span class="text-danger-500">*</span>
                        </label>
                        <input type="text" name="name" id="name" value="{{ old('name', $holiday->name) }}"
                            class="input w-full @error('name') border-danger-500 @enderror"
                            placeholder="Contoh: Hari Kemerdekaan"
                            required>
                        @error('name')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Date --}}
                    <div>
                        <label for="date" class="block text-sm font-medium text-secondary-700 mb-1">
                            Tanggal <span class="text-danger-500">*</span>
                        </label>
                        <input type="date" name="date" id="date" value="{{ old('date', $holiday->date->format('Y-m-d')) }}"
                            class="input w-full @error('date') border-danger-500 @enderror"
                            required>
                        @error('date')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Type --}}
                    <div>
                        <label for="type" class="block text-sm font-medium text-secondary-700 mb-1">
                            Jenis Libur <span class="text-danger-500">*</span>
                        </label>
                        <select name="type" id="type"
                            class="input w-full @error('type') border-danger-500 @enderror"
                            required>
                            <option value="">Pilih jenis libur...</option>
                            <option value="national" {{ old('type', $holiday->type) === 'national' ? 'selected' : '' }}>Libur Nasional</option>
                            <option value="company" {{ old('type', $holiday->type) === 'company' ? 'selected' : '' }}>Libur Perusahaan</option>
                            <option value="religious" {{ old('type', $holiday->type) === 'religious' ? 'selected' : '' }}>Libur Keagamaan</option>
                        </select>
                        @error('type')
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
                            placeholder="Keterangan tambahan (opsional)">{{ old('description', $holiday->description) }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Is Recurring --}}
                    <div>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="is_recurring" value="1"
                                class="w-5 h-5 rounded border-secondary-300 text-primary-600 focus:ring-primary-500"
                                {{ old('is_recurring', $holiday->is_recurring) ? 'checked' : '' }}>
                            <div>
                                <span class="text-sm font-medium text-secondary-700">Berulang Setiap Tahun</span>
                                <p class="text-xs text-secondary-500">Centang jika hari libur ini jatuh pada tanggal yang sama setiap tahun.</p>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="card-footer flex items-center justify-end gap-3">
                    <a href="{{ route('holidays.index') }}" class="btn btn-ghost">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
