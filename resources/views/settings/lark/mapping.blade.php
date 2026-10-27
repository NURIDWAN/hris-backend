@extends('layouts.admin')

@section('title', 'Pemetaan Karyawan Lark')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Pengaturan</span>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <a href="{{ route('settings.lark.index') }}" class="text-slate-700 hover:text-primary-600 font-medium">Integrasi Lark</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Pemetaan Karyawan</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Pemetaan Karyawan ke Lark</h1>
            <p class="text-secondary-500 mt-1">Petakan identitas karyawan JagoGaji dengan akun Lark User ID untuk pengiriman absensi.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('settings.lark.index') }}" class="btn btn-secondary">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali ke Pengaturan Lark
            </a>
        </div>
    </div>
@endsection

@section('content')
    @if(session('success'))
        <div class="mb-6 p-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- Filter & Search Card --}}
    <div class="card p-4 mb-6">
        <form action="{{ route('settings.lark.mapping') }}" method="GET" class="flex flex-col sm:flex-row gap-3 items-center justify-between">
            <div class="flex flex-1 items-center gap-3 w-full sm:w-auto">
                <div class="relative flex-1">
                    <input type="text" name="search" value="{{ $search }}"
                           placeholder="Cari nama, email, NIK, atau Lark ID..."
                           class="w-full pl-9 rounded-lg border-secondary-300 focus:border-primary-500 focus:ring-primary-500 text-sm">
                    <svg class="w-4 h-4 text-secondary-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>

                <select name="filter" class="rounded-lg border-secondary-300 focus:border-primary-500 focus:ring-primary-500 text-sm">
                    <option value="all" {{ $filter === 'all' ? 'selected' : '' }}>Semua Status</option>
                    <option value="mapped" {{ $filter === 'mapped' ? 'selected' : '' }}>Sudah Terhubung</option>
                    <option value="unmapped" {{ $filter === 'unmapped' ? 'selected' : '' }}>Belum Terhubung</option>
                </select>

                <button type="submit" class="btn btn-secondary text-sm">Filter</button>
            </div>
        </form>
    </div>

    {{-- Employees Table Card --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-secondary-600">
                <thead class="bg-secondary-50 text-secondary-700 uppercase text-xs font-semibold border-b border-secondary-200">
                    <tr>
                        <th class="px-6 py-3">Karyawan</th>
                        <th class="px-6 py-3">Email & NIK</th>
                        <th class="px-6 py-3">Departemen</th>
                        <th class="px-6 py-3">Lark User ID</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-secondary-200">
                    @forelse($employees as $employee)
                        <tr class="hover:bg-secondary-50/50">
                            <td class="px-6 py-4">
                                <div class="font-medium text-secondary-900">
                                    {{ $employee->first_name }} {{ $employee->last_name }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div>{{ $employee->email ?: '-' }}</div>
                                <div class="text-xs text-secondary-400">{{ $employee->employee_id }}</div>
                            </td>
                            <td class="px-6 py-4">
                                {{ $employee->department?->name ?: '-' }}
                            </td>
                            <td class="px-6 py-4">
                                <form action="{{ route('settings.lark.mapping.save', $employee) }}" method="POST" class="flex items-center gap-2">
                                    @csrf
                                    <input type="text" name="lark_user_id"
                                           value="{{ old('lark_user_id', $employee->lark_user_id) }}"
                                           placeholder="ou_xxxx atau user_id"
                                           class="w-48 px-2.5 py-1 text-xs rounded border-secondary-300 focus:border-primary-500 focus:ring-primary-500 font-mono">
                                    <button type="submit" class="btn btn-secondary px-2.5 py-1 text-xs" title="Simpan Lark User ID">
                                        Simpan
                                    </button>
                                </form>
                            </td>
                            <td class="px-6 py-4">
                                @if($employee->lark_user_id)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800">
                                        Terhubung
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">
                                        Belum Terhubung
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                @if($employee->lark_user_id)
                                    <form action="{{ route('settings.lark.mapping.destroy', $employee) }}" method="POST" onsubmit="return confirm('Hapus mapping Lark untuk karyawan ini?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs text-rose-600 hover:text-rose-800 font-medium">
                                            Putuskan
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-secondary-300">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-secondary-400">
                                Tidak ada data karyawan ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($employees->hasPages())
            <div class="p-4 border-t border-secondary-200">
                {{ $employees->links() }}
            </div>
        @endif
    </div>
@endsection
