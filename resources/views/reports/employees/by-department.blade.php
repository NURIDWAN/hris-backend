@extends('layouts.admin')

@section('title', 'Laporan Karyawan per Departemen')

@section('breadcrumb')
    <a href="{{ route('reports.employees') }}" class="text-slate-500 hover:text-primary-600">Laporan Karyawan</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Per Departemen</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Karyawan per Departemen</h1>
            <p class="text-secondary-500 mt-1">Distribusi karyawan berdasarkan departemen</p>
        </div>
        <a href="{{ route('reports.employees') }}" class="btn btn-ghost">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali
        </a>
    </div>
@endsection

@section('content')
    {{-- Summary Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-4">
        <div class="stat-card">
            <p class="stat-card-label">Total Karyawan</p>
            <p class="text-lg font-bold text-secondary-900">{{ $summary['total'] }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-card-label">Total Departemen</p>
            <p class="text-lg font-bold text-secondary-900">{{ $departments->count() }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-card-label">Rata-rata/Dept</p>
            <p class="text-lg font-bold text-secondary-900">{{ $departments->count() > 0 ? round($summary['total'] / $departments->count(), 1) : 0 }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-card-label">Karyawan Aktif</p>
            <p class="text-lg font-bold text-success-600">{{ $summary['active'] }}</p>
        </div>
    </div>

    {{-- Department Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($departments as $department)
            <div class="card">
                <div class="card-body-sm">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                        <span class="text-2xl font-bold text-primary-600">{{ $department->employees_count }}</span>
                    </div>
                    <h3 class="font-semibold text-secondary-900">{{ $department->name }}</h3>
                    <p class="text-xs text-secondary-500 mt-1 line-clamp-1">{{ $department->description ?? 'Tidak ada deskripsi' }}</p>

                    @if($summary['total'] > 0)
                        <div class="mt-3">
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="text-secondary-500">Persentase</span>
                                <span class="font-medium text-secondary-900">{{ round(($department->employees_count / $summary['total']) * 100, 1) }}%</span>
                            </div>
                            <div class="w-full bg-secondary-200 rounded-full h-1.5">
                                <div class="bg-primary-500 h-1.5 rounded-full" style="width: {{ ($department->employees_count / $summary['total']) * 100 }}%"></div>
                            </div>
                        </div>
                    @endif

                    <a href="{{ route('reports.employees', ['department_id' => $department->id]) }}" class="btn btn-ghost btn-sm w-full mt-3">
                        Lihat Karyawan
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full">
                <div class="card">
                    <div class="card-body text-center py-12">
                        <svg class="w-12 h-12 text-secondary-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        <p class="text-secondary-500">Belum ada departemen</p>
                    </div>
                </div>
            </div>
        @endforelse
    </div>
@endsection
