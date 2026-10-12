@extends('layouts.admin')

@section('title', 'Tambah Alur Persetujuan')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Pengaturan</span>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <a href="{{ route('settings.approval-workflows.index') }}" class="text-slate-500 hover:text-primary-600">Alur Persetujuan</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Tambah</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Tambah Alur Persetujuan</h1>
            <p class="text-secondary-500 mt-1">Buat alur persetujuan baru.</p>
        </div>
        <a href="{{ route('settings.approval-workflows.index') }}" class="btn btn-ghost">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali
        </a>
    </div>
@endsection

@section('content')
    <form action="{{ route('settings.approval-workflows.store') }}" method="POST">
        @csrf

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Informasi Alur Persetujuan</h3>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Type --}}
                    <div>
                        <label for="type" class="block text-sm font-medium text-secondary-700 mb-1">
                            Tipe Persetujuan <span class="text-danger-500">*</span>
                        </label>
                        <select name="type" id="type"
                                class="input w-full @error('type') border-danger-500 @enderror"
                                required>
                            <option value="">Pilih Tipe</option>
                            @foreach($availableTypes as $value => $label)
                                <option value="{{ $value }}" {{ old('type', request('type')) == $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('type')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Name --}}
                    <div>
                        <label for="name" class="block text-sm font-medium text-secondary-700 mb-1">
                            Nama Alur <span class="text-danger-500">*</span>
                        </label>
                        <input type="text" name="name" id="name"
                               value="{{ old('name') }}"
                               class="input w-full @error('name') border-danger-500 @enderror"
                               placeholder="Alur Persetujuan Cuti"
                               required>
                        @error('name')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Description --}}
                    <div class="md:col-span-2">
                        <label for="description" class="block text-sm font-medium text-secondary-700 mb-1">
                            Deskripsi
                        </label>
                        <textarea name="description" id="description" rows="3"
                                  class="input w-full @error('description') border-danger-500 @enderror"
                                  placeholder="Deskripsi alur persetujuan...">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Is Active --}}
                    <div>
                        <label class="block text-sm font-medium text-secondary-700 mb-1">Status</label>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1"
                                   {{ old('is_active', true) ? 'checked' : '' }}
                                   class="rounded border-secondary-300 text-primary-600 focus:ring-primary-500">
                            <span class="text-secondary-700">Aktifkan alur persetujuan</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="card-footer flex justify-end gap-3">
                <a href="{{ route('settings.approval-workflows.index') }}" class="btn btn-ghost">Batal</a>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Buat Alur
                </button>
            </div>
        </div>
    </form>
@endsection
