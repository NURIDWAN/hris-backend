@extends('superadmin.layouts.app')

@section('title', $company->name)

@section('header')
    <div class="flex items-center gap-4">
        <a href="{{ route('superadmin.companies.index') }}" class="btn btn-ghost btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">{{ $company->name }}</h1>
            <p class="text-secondary-600">Detail informasi perusahaan</p>
        </div>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Company Info --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Basic Info Card --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Informasi Perusahaan</h3>
                </div>
                <div class="card-body">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Nama Perusahaan</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $company->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Email</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $company->email ? Str::mask($company->email, '*', 3, strlen(explode('@', $company->email)[0]) - 3) : '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Telepon</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $company->phone ? Str::mask($company->phone, '*', 4, -4) : '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Alamat</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $company->address ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">NPWP</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $company->npwp ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Tanggal Daftar</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $company->created_at->format('d M Y H:i') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Users --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Users ({{ $company->users_count }})</h3>
                </div>
                <div class="card-body p-0">
                    <x-table>
                        <x-slot name="header">
                            <th class="px-4 py-3">Nama</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Status</th>
                        </x-slot>
                        @forelse($company->users as $user)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <span>{{ $user->name }}</span>
                                        @if($user->isDemoAccount())
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700">Demo</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-secondary-600">{{ Str::mask($user->email, '*', 3, strlen(explode('@', $user->email)[0]) - 3) }}</td>
                                <td class="px-4 py-3">
                                    @if($user->is_active)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-success-100 text-success-800">Aktif</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">Nonaktif</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-secondary-500">Belum ada user</td>
                            </tr>
                        @endforelse
                    </x-table>
                </div>
            </div>

            {{-- Employees --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Karyawan ({{ $company->employees_count }})</h3>
                </div>
                <div class="card-body p-0">
                    <x-table>
                        <x-slot name="header">
                            <th class="px-4 py-3">ID</th>
                            <th class="px-4 py-3">Nama</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Status</th>
                        </x-slot>
                        @forelse($company->employees as $employee)
                            <tr>
                                <td class="px-4 py-3 text-sm font-mono text-secondary-600">{{ $employee->employee_id }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <span>{{ $employee->first_name }} {{ $employee->last_name }}</span>
                                        @if($employee->isDemoEmployee())
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700">Demo</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-secondary-600">{{ $employee->email ? Str::mask($employee->email, '*', 3, strlen(explode('@', $employee->email)[0]) - 3) : '-' }}</td>
                                <td class="px-4 py-3">
                                    @if($employee->is_active)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-success-100 text-success-800">Aktif</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">Nonaktif</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-secondary-500">Belum ada karyawan</td>
                            </tr>
                        @endforelse
                    </x-table>
                </div>
            </div>
        </div>

        {{-- Stats Sidebar --}}
        <div class="space-y-6">
            {{-- Stats Card --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Statistik</h3>
                </div>
                <div class="card-body space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-secondary-600">Total Users</span>
                        <span class="font-semibold text-secondary-900">{{ $company->users_count }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-secondary-600">Total Karyawan</span>
                        <span class="font-semibold text-secondary-900">{{ $company->employees_count }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-secondary-600">Departemen</span>
                        <span class="font-semibold text-secondary-900">{{ $company->departments_count }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-secondary-600">Jabatan</span>
                        <span class="font-semibold text-secondary-900">{{ $company->positions_count }}</span>
                    </div>
                </div>
            </div>

            {{-- Company Type Badge --}}
            @if($company->id === 1)
                <div class="card border-amber-200 bg-amber-50">
                    <div class="card-body">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-semibold text-amber-800">Perusahaan Demo</p>
                                <p class="text-sm text-amber-600">Data tidak dapat dihapus</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
