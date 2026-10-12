@extends('layouts.admin')

@section('title', 'Profil Perusahaan')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Pengaturan</span>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Profil Perusahaan</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Profil Perusahaan</h1>
            <p class="text-secondary-500 mt-1">Kelola informasi dan pengaturan perusahaan Anda.</p>
        </div>
    </div>
@endsection

@section('content')
    <div class="space-y-6">
        {{-- Company Profile Form --}}
        <form action="{{ route('settings.company-profile.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Informasi Perusahaan</h3>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {{-- Logo Section --}}
                        <div class="lg:col-span-1">
                            <label class="block text-sm font-medium text-secondary-700 mb-3">Logo Perusahaan</label>
                            <div class="flex flex-col items-center gap-4">
                                <div class="w-32 h-32 rounded-lg border-2 border-dashed border-secondary-300 flex items-center justify-center overflow-hidden bg-secondary-50">
                                    @if($company->logo)
                                        <img src="{{ Storage::url($company->logo) }}" alt="{{ $company->name }}" class="w-full h-full object-contain">
                                    @else
                                        <svg class="w-12 h-12 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                        </svg>
                                    @endif
                                </div>
                                <div class="flex flex-col items-center gap-2">
                                    <label for="logo" class="btn btn-secondary btn-sm cursor-pointer">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        Pilih Logo
                                        <input type="file" name="logo" id="logo" accept="image/jpeg,image/png,image/jpg,image/svg+xml" class="hidden">
                                    </label>
                                    @if($company->logo)
                                        <button type="button"
                                            @click="$dispatch('confirm-dialog', {
                                                title: 'Hapus Logo',
                                                message: 'Apakah Anda yakin ingin menghapus logo perusahaan?',
                                                confirmText: 'Ya, Hapus',
                                                type: 'danger',
                                                formAction: '{{ route('settings.company-profile.delete-logo') }}'
                                            })"
                                            class="text-sm text-danger-600 hover:text-danger-700">
                                            Hapus Logo
                                        </button>
                                    @endif
                                    <p class="text-xs text-secondary-500 text-center">Format: JPG, PNG, SVG. Maks 2MB</p>
                                </div>
                                @error('logo')
                                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Company Info --}}
                        <div class="lg:col-span-2">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {{-- Company Name --}}
                                <div class="md:col-span-2">
                                    <label for="name" class="block text-sm font-medium text-secondary-700 mb-1">
                                        Nama Perusahaan <span class="text-danger-500">*</span>
                                    </label>
                                    <input type="text" name="name" id="name"
                                           value="{{ old('name', $company->name) }}"
                                           class="input w-full @error('name') border-danger-500 @enderror"
                                           required>
                                    @error('name')
                                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Email --}}
                                <div>
                                    <label for="email" class="block text-sm font-medium text-secondary-700 mb-1">
                                        Email Perusahaan <span class="text-danger-500">*</span>
                                    </label>
                                    <input type="email" name="email" id="email"
                                           value="{{ old('email', $company->email) }}"
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
                                           value="{{ old('phone', $company->phone) }}"
                                           class="input w-full @error('phone') border-danger-500 @enderror"
                                           placeholder="021-12345678">
                                    @error('phone')
                                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Website --}}
                                <div>
                                    <label for="website" class="block text-sm font-medium text-secondary-700 mb-1">
                                        Website
                                    </label>
                                    <input type="url" name="website" id="website"
                                           value="{{ old('website', $company->website) }}"
                                           class="input w-full @error('website') border-danger-500 @enderror"
                                           placeholder="https://example.com">
                                    @error('website')
                                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- NPWP --}}
                                <div>
                                    <label for="npwp" class="block text-sm font-medium text-secondary-700 mb-1">
                                        NPWP Perusahaan
                                    </label>
                                    <input type="text" name="npwp" id="npwp"
                                           value="{{ old('npwp', $company->npwp) }}"
                                           class="input w-full @error('npwp') border-danger-500 @enderror"
                                           placeholder="12.345.678.9-012.345">
                                    @error('npwp')
                                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Address --}}
                                <div class="md:col-span-2">
                                    <label for="address" class="block text-sm font-medium text-secondary-700 mb-1">
                                        Alamat
                                    </label>
                                    <textarea name="address" id="address" rows="3"
                                              class="input w-full @error('address') border-danger-500 @enderror"
                                              placeholder="Alamat lengkap perusahaan">{{ old('address', $company->address) }}</textarea>
                                    @error('address')
                                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer flex justify-end">
                    <button type="submit" class="btn btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Simpan Profil
                    </button>
                </div>
            </div>
        </form>

        {{-- Company Settings Form --}}
        <form action="{{ route('settings.company-profile.update-settings') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Pengaturan Sistem</h3>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Timezone --}}
                        <div>
                            <label for="timezone" class="block text-sm font-medium text-secondary-700 mb-1">
                                Zona Waktu <span class="text-danger-500">*</span>
                            </label>
                            <select name="timezone" id="timezone"
                                    class="input w-full @error('timezone') border-danger-500 @enderror">
                                @foreach(\App\Models\Company::getAllTimezones() as $tz => $label)
                                    <option value="{{ $tz }}" {{ old('timezone', $company->timezone) == $tz ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-secondary-500">Zona waktu yang digunakan untuk absensi dan perhitungan jam kerja.</p>
                            @error('timezone')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Date Format --}}
                        <div>
                            <label for="date_format" class="block text-sm font-medium text-secondary-700 mb-1">
                                Format Tanggal
                            </label>
                            <select name="date_format" id="date_format"
                                    class="input w-full @error('date_format') border-danger-500 @enderror">
                                <option value="d/m/Y" {{ old('date_format', $company->settings['date_format'] ?? 'd/m/Y') == 'd/m/Y' ? 'selected' : '' }}>
                                    DD/MM/YYYY (31/12/2024)
                                </option>
                                <option value="d-m-Y" {{ old('date_format', $company->settings['date_format'] ?? '') == 'd-m-Y' ? 'selected' : '' }}>
                                    DD-MM-YYYY (31-12-2024)
                                </option>
                                <option value="Y-m-d" {{ old('date_format', $company->settings['date_format'] ?? '') == 'Y-m-d' ? 'selected' : '' }}>
                                    YYYY-MM-DD (2024-12-31)
                                </option>
                                <option value="d M Y" {{ old('date_format', $company->settings['date_format'] ?? '') == 'd M Y' ? 'selected' : '' }}>
                                    DD MMM YYYY (31 Dec 2024)
                                </option>
                                <option value="m/d/Y" {{ old('date_format', $company->settings['date_format'] ?? '') == 'm/d/Y' ? 'selected' : '' }}>
                                    MM/DD/YYYY (12/31/2024)
                                </option>
                            </select>
                            @error('date_format')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Currency --}}
                        <div>
                            <label for="currency" class="block text-sm font-medium text-secondary-700 mb-1">
                                Mata Uang
                            </label>
                            <select name="currency" id="currency"
                                    class="input w-full @error('currency') border-danger-500 @enderror">
                                <option value="IDR" {{ old('currency', $company->settings['currency'] ?? 'IDR') == 'IDR' ? 'selected' : '' }}>
                                    IDR - Rupiah Indonesia
                                </option>
                                <option value="USD" {{ old('currency', $company->settings['currency'] ?? '') == 'USD' ? 'selected' : '' }}>
                                    USD - US Dollar
                                </option>
                            </select>
                            @error('currency')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Working Days per Month --}}
                        <div>
                            <label for="working_days_per_month" class="block text-sm font-medium text-secondary-700 mb-1">
                                Hari Kerja per Bulan
                            </label>
                            <input type="number" name="working_days_per_month" id="working_days_per_month"
                                   value="{{ old('working_days_per_month', $company->settings['working_days_per_month'] ?? 22) }}"
                                   class="input w-full @error('working_days_per_month') border-danger-500 @enderror"
                                   min="1" max="31">
                            <p class="mt-1 text-xs text-secondary-500">Digunakan untuk perhitungan gaji harian.</p>
                            @error('working_days_per_month')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="card-footer flex justify-end">
                    <button type="submit" class="btn btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Simpan Pengaturan
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
