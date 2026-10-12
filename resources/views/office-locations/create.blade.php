@extends('layouts.admin')

@section('title', 'Tambah Lokasi Kantor')

@section('breadcrumb')
    <a href="{{ route('office-locations.index') }}" class="text-primary-600 hover:text-primary-700">Lokasi Kantor</a>
    <svg class="w-4 h-4 mx-2 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Tambah Baru</span>
@endsection

@section('header')
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Tambah Lokasi Kantor</h1>
            <p class="text-secondary-500 mt-1">Tambahkan lokasi kantor baru untuk validasi GPS absensi.</p>
        </div>
    </div>
@endsection

@section('content')
    <form action="{{ route('office-locations.store') }}" method="POST">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Info --}}
            <div class="lg:col-span-2 space-y-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Informasi Lokasi</h3>
                    </div>
                    <div class="card-body space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="name" class="block text-sm font-medium text-secondary-700 mb-1">
                                    Nama Lokasi <span class="text-danger-500">*</span>
                                </label>
                                <input type="text" name="name" id="name" value="{{ old('name') }}"
                                    class="input w-full @error('name') border-danger-500 @enderror"
                                    placeholder="Contoh: Kantor Pusat Jakarta" required>
                                @error('name')
                                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="code" class="block text-sm font-medium text-secondary-700 mb-1">
                                    Kode Lokasi <span class="text-danger-500">*</span>
                                </label>
                                <input type="text" name="code" id="code" value="{{ old('code') }}"
                                    class="input w-full @error('code') border-danger-500 @enderror"
                                    placeholder="Contoh: JKT-HQ" required>
                                @error('code')
                                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label for="address" class="block text-sm font-medium text-secondary-700 mb-1">
                                Alamat
                            </label>
                            <textarea name="address" id="address" rows="2"
                                class="input w-full @error('address') border-danger-500 @enderror"
                                placeholder="Alamat lengkap lokasi kantor">{{ old('address') }}</textarea>
                            @error('address')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="city" class="block text-sm font-medium text-secondary-700 mb-1">
                                    Kota
                                </label>
                                <input type="text" name="city" id="city" value="{{ old('city') }}"
                                    class="input w-full @error('city') border-danger-500 @enderror"
                                    placeholder="Contoh: Jakarta">
                                @error('city')
                                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="province" class="block text-sm font-medium text-secondary-700 mb-1">
                                    Provinsi
                                </label>
                                <input type="text" name="province" id="province" value="{{ old('province') }}"
                                    class="input w-full @error('province') border-danger-500 @enderror"
                                    placeholder="Contoh: DKI Jakarta">
                                @error('province')
                                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Koordinat GPS</h3>
                        <p class="text-sm text-secondary-500 mt-1">Atur koordinat GPS untuk validasi lokasi absensi karyawan.</p>
                    </div>
                    <div class="card-body space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="latitude" class="block text-sm font-medium text-secondary-700 mb-1">
                                    Latitude
                                </label>
                                <input type="number" name="latitude" id="latitude" value="{{ old('latitude') }}"
                                    class="input w-full @error('latitude') border-danger-500 @enderror"
                                    placeholder="-6.200000" step="0.00000001" min="-90" max="90">
                                @error('latitude')
                                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="longitude" class="block text-sm font-medium text-secondary-700 mb-1">
                                    Longitude
                                </label>
                                <input type="number" name="longitude" id="longitude" value="{{ old('longitude') }}"
                                    class="input w-full @error('longitude') border-danger-500 @enderror"
                                    placeholder="106.816666" step="0.00000001" min="-180" max="180">
                                @error('longitude')
                                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="radius" class="block text-sm font-medium text-secondary-700 mb-1">
                                    Radius (meter)
                                </label>
                                <input type="number" name="radius" id="radius" value="{{ old('radius', 100) }}"
                                    class="input w-full @error('radius') border-danger-500 @enderror"
                                    placeholder="100" min="10" max="10000">
                                @error('radius')
                                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="bg-secondary-50 rounded-lg p-4">
                            <p class="text-sm text-secondary-600">
                                <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Tip: Anda bisa mendapatkan koordinat dari Google Maps dengan klik kanan pada lokasi yang diinginkan.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Pengaturan</h3>
                    </div>
                    <div class="card-body space-y-4">
                        <div class="flex items-center justify-between">
                            <label for="is_active" class="text-sm font-medium text-secondary-700">
                                Aktif
                            </label>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" id="is_active" value="1"
                                    class="sr-only peer" {{ old('is_active', true) ? 'checked' : '' }}>
                                <div class="w-11 h-6 bg-secondary-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-secondary-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                            </label>
                        </div>

                        <div class="flex items-center justify-between">
                            <label for="is_headquarters" class="text-sm font-medium text-secondary-700">
                                Kantor Pusat
                            </label>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="hidden" name="is_headquarters" value="0">
                                <input type="checkbox" name="is_headquarters" id="is_headquarters" value="1"
                                    class="sr-only peer" {{ old('is_headquarters') ? 'checked' : '' }}>
                                <div class="w-11 h-6 bg-secondary-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-secondary-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-full">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Simpan Lokasi
                        </button>
                        <a href="{{ route('office-locations.index') }}" class="btn btn-ghost w-full mt-2">
                            Batal
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
