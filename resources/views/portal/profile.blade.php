@extends('layouts.portal')

@section('title', 'Profil Saya')

@section('breadcrumb')
    <a href="{{ route('portal.dashboard') }}" class="text-slate-500 hover:text-primary-600">Dashboard</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Profil Saya</span>
@endsection

@section('header')
    <div>
        <h1 class="text-2xl font-bold text-secondary-900">Profil Saya</h1>
        <p class="text-secondary-500 mt-1">Lihat dan perbarui informasi pribadi Anda.</p>
    </div>
@endsection

@section('content')
    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Profile Photo --}}
        <div class="card lg:col-span-1">
            <div class="card-body text-center">
                <div class="w-32 h-32 mx-auto rounded-full bg-primary-100 flex items-center justify-center mb-4">
                    @if($employee->photo)
                        <img src="{{ asset('storage/' . $employee->photo) }}" alt="{{ $employee->full_name }}" class="w-32 h-32 rounded-full object-cover">
                    @else
                        <span class="text-5xl font-bold text-primary-600">{{ substr($employee->first_name, 0, 1) }}{{ substr($employee->last_name, 0, 1) }}</span>
                    @endif
                </div>
                <h3 class="text-xl font-semibold text-secondary-900">{{ $employee->full_name }}</h3>
                <p class="text-secondary-500">{{ $employee->employee_id }}</p>
                <div class="mt-4">
                    <x-badge type="success">{{ ucfirst($employee->employment_status) }}</x-badge>
                </div>
            </div>
        </div>

        {{-- Info Cards --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Employment Info --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Informasi Kepegawaian</h3>
                </div>
                <div class="card-body">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <div class="text-sm text-secondary-500">Departemen</div>
                            <div class="font-medium">{{ $employee->department->name ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-secondary-500">Jabatan</div>
                            <div class="font-medium">{{ $employee->position->name ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-secondary-500">Tanggal Bergabung</div>
                            <div class="font-medium">{{ $employee->hire_date?->format('d M Y') ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-secondary-500">Masa Kerja</div>
                            <div class="font-medium">{{ $employee->years_of_service }} tahun</div>
                        </div>
                        <div>
                            <div class="text-sm text-secondary-500">Status Karyawan</div>
                            <div class="font-medium">{{ ucfirst($employee->employment_status) }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-secondary-500">Jadwal Kerja</div>
                            <div class="font-medium">{{ $employee->workSchedule->name ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Personal Info (Editable) --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Informasi Pribadi</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('portal.profile.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="grid gap-4 sm:grid-cols-2">
                            {{-- Read-only fields --}}
                            <div>
                                <label class="block text-sm font-medium text-secondary-700 mb-1">Nama Depan</label>
                                <input type="text" value="{{ $employee->first_name }}" class="input w-full bg-secondary-50" disabled>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-secondary-700 mb-1">Nama Belakang</label>
                                <input type="text" value="{{ $employee->last_name }}" class="input w-full bg-secondary-50" disabled>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-secondary-700 mb-1">Email</label>
                                <input type="email" value="{{ $employee->email }}" class="input w-full bg-secondary-50" disabled>
                            </div>

                            {{-- Editable fields --}}
                            <div>
                                <label for="phone" class="block text-sm font-medium text-secondary-700 mb-1">
                                    No. Telepon <span class="text-danger-500">*</span>
                                </label>
                                <input type="text" name="phone" id="phone"
                                       value="{{ old('phone', $employee->phone) }}"
                                       class="input w-full @error('phone') border-danger-500 @enderror"
                                       required>
                                @error('phone')
                                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label for="address" class="block text-sm font-medium text-secondary-700 mb-1">Alamat</label>
                                <textarea name="address" id="address" rows="2"
                                          class="input w-full @error('address') border-danger-500 @enderror">{{ old('address', $employee->address) }}</textarea>
                                @error('address')
                                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="city" class="block text-sm font-medium text-secondary-700 mb-1">Kota</label>
                                <input type="text" name="city" id="city"
                                       value="{{ old('city', $employee->city) }}"
                                       class="input w-full @error('city') border-danger-500 @enderror">
                                @error('city')
                                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="postal_code" class="block text-sm font-medium text-secondary-700 mb-1">Kode Pos</label>
                                <input type="text" name="postal_code" id="postal_code"
                                       value="{{ old('postal_code', $employee->postal_code) }}"
                                       class="input w-full @error('postal_code') border-danger-500 @enderror">
                                @error('postal_code')
                                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Emergency Contact --}}
                        <h4 class="text-lg font-medium text-secondary-900 mt-6 mb-4">Kontak Darurat</h4>
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div>
                                <label for="emergency_contact_name" class="block text-sm font-medium text-secondary-700 mb-1">Nama</label>
                                <input type="text" name="emergency_contact_name" id="emergency_contact_name"
                                       value="{{ old('emergency_contact_name', $employee->emergency_contact_name) }}"
                                       class="input w-full">
                            </div>
                            <div>
                                <label for="emergency_contact_phone" class="block text-sm font-medium text-secondary-700 mb-1">No. Telepon</label>
                                <input type="text" name="emergency_contact_phone" id="emergency_contact_phone"
                                       value="{{ old('emergency_contact_phone', $employee->emergency_contact_phone) }}"
                                       class="input w-full">
                            </div>
                            <div>
                                <label for="emergency_contact_relationship" class="block text-sm font-medium text-secondary-700 mb-1">Hubungan</label>
                                <input type="text" name="emergency_contact_relationship" id="emergency_contact_relationship"
                                       value="{{ old('emergency_contact_relationship', $employee->emergency_contact_relationship) }}"
                                       class="input w-full"
                                       placeholder="Contoh: Suami, Istri, Orang Tua">
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <button type="submit" class="btn btn-primary">
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
