@extends('superadmin.layouts.app')

@section('title', 'Perusahaan Terdaftar')

@section('header')
    <h1 class="text-2xl font-bold text-secondary-900">Perusahaan Terdaftar</h1>
    <p class="text-secondary-600">Daftar semua perusahaan yang telah mendaftar di sistem</p>
@endsection

@section('content')
    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="card">
            <div class="card-body flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-primary-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-secondary-600">Total Perusahaan</p>
                    <p class="text-2xl font-bold text-secondary-900">{{ $stats['total'] }}</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-success-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-secondary-600">Bulan Ini</p>
                    <p class="text-2xl font-bold text-secondary-900">{{ $stats['this_month'] }}</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-info-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-info-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-secondary-600">Dengan Karyawan</p>
                    <p class="text-2xl font-bold text-secondary-900">{{ $stats['with_employees'] }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Search --}}
    <div class="card mb-6">
        <div class="card-body">
            <form method="GET" action="{{ route('superadmin.companies.index') }}" class="flex gap-4">
                <div class="flex-1">
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Cari nama perusahaan atau email..."
                           class="input w-full">
                </div>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    Cari
                </button>
                @if(request('search'))
                    <a href="{{ route('superadmin.companies.index') }}" class="btn btn-secondary">Reset</a>
                @endif
            </form>
        </div>
    </div>

    {{-- Companies Table --}}
    <div class="card">
        <div class="card-body p-0">
            <x-table>
                <x-slot name="header">
                    <th class="px-6 py-3 text-left">Perusahaan</th>
                    <th class="px-6 py-3 text-left">Kontak</th>
                    <th class="px-6 py-3 text-center">Karyawan</th>
                    <th class="px-6 py-3 text-center">Users</th>
                    <th class="px-6 py-3 text-left">Terdaftar</th>
                    <th class="px-6 py-3 text-center">Aksi</th>
                </x-slot>

                @forelse($companies as $company)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center text-white font-bold">
                                    {{ strtoupper(substr($company->name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-semibold text-secondary-900">{{ $company->name }}</p>
                                    @if($company->id === 1)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">
                                            Demo
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm text-secondary-900">{{ $company->email ? Str::mask($company->email, '*', 3, strlen(explode('@', $company->email)[0]) - 3) : '-' }}</p>
                            <p class="text-sm text-secondary-500">{{ $company->phone ? Str::mask($company->phone, '*', 4, -4) : '-' }}</p>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $company->employees_count > 0 ? 'bg-success-100 text-success-800' : 'bg-slate-100 text-slate-600' }}">
                                {{ $company->employees_count }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-info-100 text-info-800">
                                {{ $company->users_count }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm text-secondary-900">{{ $company->created_at->format('d M Y') }}</p>
                            <p class="text-xs text-secondary-500">{{ $company->created_at->diffForHumans() }}</p>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <a href="{{ route('superadmin.companies.show', $company) }}" class="btn btn-ghost btn-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                Detail
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-secondary-500">
                            Belum ada perusahaan terdaftar
                        </td>
                    </tr>
                @endforelse
            </x-table>
        </div>

        @if($companies->hasPages())
            <div class="card-footer">
                {{ $companies->links() }}
            </div>
        @endif
    </div>
@endsection
