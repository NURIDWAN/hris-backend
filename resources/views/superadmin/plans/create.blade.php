@extends('superadmin.layouts.app')

@section('title', 'Tambah Plan')

@section('breadcrumb')
    <a href="{{ route('superadmin.plans.index') }}" class="text-secondary-500 hover:text-primary-600">Plans</a>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-secondary-900 font-medium">Tambah</span>
@endsection

@section('header')
    <h1 class="text-2xl font-bold text-secondary-900">Tambah Plan Baru</h1>
    <p class="text-secondary-500 mt-1">Buat paket langganan baru</p>
@endsection

@section('content')
    <form action="{{ route('superadmin.plans.store') }}" method="POST">
        @csrf

        <div class="card">
            <div class="card-body space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Name --}}
                    <div>
                        <label for="name" class="block text-sm font-medium text-secondary-700 mb-1">
                            Nama Plan <span class="text-danger-500">*</span>
                        </label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}"
                               class="input w-full @error('name') border-danger-500 @enderror"
                               placeholder="Enterprise Plan" required>
                        @error('name')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Slug --}}
                    <div>
                        <label for="slug" class="block text-sm font-medium text-secondary-700 mb-1">
                            Slug <span class="text-danger-500">*</span>
                        </label>
                        <input type="text" name="slug" id="slug" value="{{ old('slug') }}"
                               class="input w-full @error('slug') border-danger-500 @enderror"
                               placeholder="enterprise" required>
                        @error('slug')
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
                              placeholder="Deskripsi singkat tentang plan ini...">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Price Monthly --}}
                    <div x-data="currencyInput({{ old('price_monthly', 0) }})">
                        <label for="price_monthly_display" class="block text-sm font-medium text-secondary-700 mb-1">
                            Harga Bulanan <span class="text-danger-500">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" id="price_monthly_display" x-model="display" @input="updateValue($event)"
                                   class="input @error('price_monthly') border-danger-500 @enderror"
                                   placeholder="0" inputmode="numeric" required>
                            <input type="hidden" name="price_monthly" :value="value">
                        </div>
                        @error('price_monthly')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Price Yearly --}}
                    <div x-data="currencyInput({{ old('price_yearly', 0) }})">
                        <label for="price_yearly_display" class="block text-sm font-medium text-secondary-700 mb-1">
                            Harga Tahunan <span class="text-danger-500">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" id="price_yearly_display" x-model="display" @input="updateValue($event)"
                                   class="input @error('price_yearly') border-danger-500 @enderror"
                                   placeholder="0" inputmode="numeric" required>
                            <input type="hidden" name="price_yearly" :value="value">
                        </div>
                        @error('price_yearly')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {{-- Max Employees --}}
                    <div>
                        <label for="max_employees" class="block text-sm font-medium text-secondary-700 mb-1">
                            Max Karyawan
                        </label>
                        <input type="number" name="max_employees" id="max_employees" value="{{ old('max_employees') }}"
                               class="input w-full @error('max_employees') border-danger-500 @enderror"
                               placeholder="100 (kosongkan untuk unlimited)">
                        @error('max_employees')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Max Users --}}
                    <div>
                        <label for="max_users" class="block text-sm font-medium text-secondary-700 mb-1">
                            Max Users
                        </label>
                        <input type="number" name="max_users" id="max_users" value="{{ old('max_users') }}"
                               class="input w-full @error('max_users') border-danger-500 @enderror"
                               placeholder="20 (kosongkan untuk unlimited)">
                        @error('max_users')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Sort Order --}}
                    <div>
                        <label for="sort_order" class="block text-sm font-medium text-secondary-700 mb-1">
                            Urutan
                        </label>
                        <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', 0) }}"
                               class="input w-full @error('sort_order') border-danger-500 @enderror"
                               placeholder="0">
                        @error('sort_order')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Is Active --}}
                <div class="flex items-center gap-3">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" id="is_active" value="1"
                           class="w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500"
                           {{ old('is_active', true) ? 'checked' : '' }}>
                    <label for="is_active" class="text-sm font-medium text-secondary-700">
                        Aktifkan plan ini
                    </label>
                </div>
            </div>

            <div class="card-footer flex items-center justify-end gap-3">
                <a href="{{ route('superadmin.plans.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Plan</button>
            </div>
        </div>
    </form>
@endsection
