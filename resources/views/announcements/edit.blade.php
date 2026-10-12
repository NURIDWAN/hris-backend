@extends('layouts.admin')

@section('title', 'Edit Pengumuman')

@section('breadcrumb')
    <a href="{{ route('announcements.index') }}" class="text-primary-600 hover:text-primary-700">Pengumuman</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Edit Pengumuman</span>
@endsection

@section('header')
    <div class="flex items-center gap-4">
        <a href="{{ route('announcements.index') }}" class="btn btn-ghost">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Edit Pengumuman</h1>
            <p class="text-secondary-500 mt-1">Perbarui pengumuman yang sudah ada.</p>
        </div>
    </div>
@endsection

@section('content')
    <form action="{{ route('announcements.update', $announcement) }}" method="POST" x-data="{ targetAudience: '{{ old('target_audience', $announcement->target_audience) }}' }">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Konten Pengumuman</h3>
                    </div>
                    <div class="card-body space-y-4">
                        <div>
                            <label for="title" class="block text-sm font-medium text-secondary-700 mb-1">
                                Judul <span class="text-danger-500">*</span>
                            </label>
                            <input type="text" name="title" id="title"
                                   value="{{ old('title', $announcement->title) }}"
                                   class="input w-full @error('title') border-danger-500 @enderror"
                                   placeholder="Masukkan judul pengumuman"
                                   required>
                            @error('title')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="content" class="block text-sm font-medium text-secondary-700 mb-1">
                                Isi Pengumuman <span class="text-danger-500">*</span>
                            </label>
                            <textarea name="content" id="content" rows="8"
                                      class="input w-full @error('content') border-danger-500 @enderror"
                                      placeholder="Tulis isi pengumuman..."
                                      required>{{ old('content', $announcement->content) }}</textarea>
                            @error('content')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Pengaturan</h3>
                    </div>
                    <div class="card-body space-y-4">
                        <div>
                            <label for="priority" class="block text-sm font-medium text-secondary-700 mb-1">
                                Prioritas <span class="text-danger-500">*</span>
                            </label>
                            <select name="priority" id="priority"
                                    class="input w-full @error('priority') border-danger-500 @enderror"
                                    required>
                                <option value="">Pilih prioritas</option>
                                <option value="low" {{ old('priority', $announcement->priority) == 'low' ? 'selected' : '' }}>Rendah</option>
                                <option value="normal" {{ old('priority', $announcement->priority) == 'normal' ? 'selected' : '' }}>Normal</option>
                                <option value="high" {{ old('priority', $announcement->priority) == 'high' ? 'selected' : '' }}>Tinggi</option>
                                <option value="urgent" {{ old('priority', $announcement->priority) == 'urgent' ? 'selected' : '' }}>Mendesak</option>
                            </select>
                            @error('priority')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="target_audience" class="block text-sm font-medium text-secondary-700 mb-1">
                                Target Penerima <span class="text-danger-500">*</span>
                            </label>
                            <select name="target_audience" id="target_audience"
                                    x-model="targetAudience"
                                    class="input w-full @error('target_audience') border-danger-500 @enderror"
                                    required>
                                <option value="">Pilih target</option>
                                <option value="all">Semua Karyawan</option>
                                <option value="department">Departemen Tertentu</option>
                                <option value="position">Jabatan Tertentu</option>
                                <option value="specific">Karyawan Tertentu</option>
                            </select>
                            @error('target_audience')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>

                        @php
                            $targetIds = old('target_ids', $announcement->target_ids ?? []);
                        @endphp

                        <div x-show="targetAudience === 'department'" x-cloak>
                            <label class="block text-sm font-medium text-secondary-700 mb-1">Pilih Departemen</label>
                            <div class="space-y-2 max-h-40 overflow-y-auto border rounded-lg p-2">
                                @foreach($departments as $dept)
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="target_ids[]" value="{{ $dept->id }}"
                                               {{ in_array($dept->id, $targetIds) ? 'checked' : '' }}
                                               class="rounded border-secondary-300 text-primary-600">
                                        <span class="text-sm">{{ $dept->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div x-show="targetAudience === 'position'" x-cloak>
                            <label class="block text-sm font-medium text-secondary-700 mb-1">Pilih Jabatan</label>
                            <div class="space-y-2 max-h-40 overflow-y-auto border rounded-lg p-2">
                                @foreach($positions as $pos)
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="target_ids[]" value="{{ $pos->id }}"
                                               {{ in_array($pos->id, $targetIds) ? 'checked' : '' }}
                                               class="rounded border-secondary-300 text-primary-600">
                                        <span class="text-sm">{{ $pos->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div x-show="targetAudience === 'specific'" x-cloak>
                            <label class="block text-sm font-medium text-secondary-700 mb-1">Pilih Karyawan</label>
                            <div class="space-y-2 max-h-40 overflow-y-auto border rounded-lg p-2">
                                @foreach($employees as $emp)
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="target_ids[]" value="{{ $emp->id }}"
                                               {{ in_array($emp->id, $targetIds) ? 'checked' : '' }}
                                               class="rounded border-secondary-300 text-primary-600">
                                        <span class="text-sm">{{ $emp->full_name }} ({{ $emp->employee_id }})</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <label for="expires_at" class="block text-sm font-medium text-secondary-700 mb-1">
                                Tanggal Kedaluwarsa
                            </label>
                            <input type="date" name="expires_at" id="expires_at"
                                   value="{{ old('expires_at', $announcement->expires_at?->format('Y-m-d')) }}"
                                   class="input w-full @error('expires_at') border-danger-500 @enderror">
                            <p class="mt-1 text-xs text-secondary-500">Kosongkan jika tidak ada batas waktu.</p>
                            @error('expires_at')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="is_pinned" value="1"
                                       {{ old('is_pinned', $announcement->is_pinned) ? 'checked' : '' }}
                                       class="rounded border-secondary-300 text-primary-600">
                                <span class="text-sm font-medium text-secondary-700">Sematkan di atas</span>
                            </label>
                            <p class="mt-1 text-xs text-secondary-500 ml-6">Pengumuman akan ditampilkan di bagian atas.</p>
                        </div>
                    </div>
                </div>

                <div class="flex gap-3">
                    <a href="{{ route('announcements.index') }}" class="btn btn-secondary flex-1">
                        Batal
                    </a>
                    <button type="submit" class="btn btn-primary flex-1">
                        Simpan
                    </button>
                </div>
            </div>
        </div>
    </form>
@endsection
