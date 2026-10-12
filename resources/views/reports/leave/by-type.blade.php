@extends('layouts.admin')

@section('title', 'Laporan Cuti Per Jenis')

@section('breadcrumb')
    <a href="{{ route('reports.leave') }}" class="text-primary-600 hover:text-primary-700">Laporan Cuti</a>
    <span class="mx-2 text-secondary-400">/</span>
    <span class="text-slate-700 font-medium">Per Jenis Cuti</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Laporan Cuti Per Jenis</h1>
            <p class="text-secondary-500 mt-1">{{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('reports.leave') }}" class="btn btn-ghost">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/></svg>
                Kembali
            </a>
        </div>
    </div>
@endsection

@section('content')
    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body-sm">
            <form action="{{ route('reports.leave.by-type') }}" method="GET" class="flex flex-wrap items-end gap-3">
                <div class="w-36">
                    <label class="block text-xs font-medium text-secondary-500 mb-1">Dari Tanggal</label>
                    <input type="date" name="start_date" value="{{ $startDate }}" class="input w-full">
                </div>
                <div class="w-36">
                    <label class="block text-xs font-medium text-secondary-500 mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" value="{{ $endDate }}" class="input w-full">
                </div>
                <div class="flex items-center gap-2">
                    @if(request()->hasAny(['start_date', 'end_date']))
                        <a href="{{ route('reports.leave.by-type') }}" class="btn btn-ghost btn-sm">Reset</a>
                    @endif
                    <button type="submit" class="btn btn-primary btn-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Leave Type Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
        @forelse($leaveTypes as $leaveType)
            <div class="card">
                <div class="card-body">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="font-semibold text-secondary-900">{{ $leaveType->name }}</h3>
                            <p class="text-sm text-secondary-500 mt-1">{{ $leaveType->description ?? 'Tidak ada deskripsi' }}</p>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $leaveType->color ?? 'secondary' }}-100 text-{{ $leaveType->color ?? 'secondary' }}-800">
                            {{ $leaveType->code ?? '-' }}
                        </span>
                    </div>
                    <div class="mt-4 pt-4 border-t border-secondary-100">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-secondary-500">Jumlah Pengajuan</p>
                                <p class="text-2xl font-bold text-secondary-900">{{ $leaveType->leave_requests_count }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-secondary-500">Total Hari Digunakan</p>
                                <p class="text-2xl font-bold text-primary-600">{{ $leaveType->total_days_used }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-secondary-500">Jatah Default</span>
                            <span class="font-medium text-secondary-900">{{ $leaveType->default_days }} hari</span>
                        </div>
                        @if($leaveType->is_paid)
                            <div class="flex items-center gap-1 mt-2">
                                <svg class="w-4 h-4 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span class="text-sm text-success-600">Cuti berbayar</span>
                            </div>
                        @else
                            <div class="flex items-center gap-1 mt-2">
                                <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                <span class="text-sm text-secondary-500">Cuti tidak berbayar</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full">
                <div class="card">
                    <div class="card-body text-center py-12">
                        <svg class="w-12 h-12 text-secondary-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                        <p class="text-secondary-500">Tidak ada jenis cuti aktif</p>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    {{-- Summary Table --}}
    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold text-secondary-900">Ringkasan Penggunaan Cuti</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-secondary-50 border-b border-secondary-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-secondary-500 uppercase tracking-wider">Jenis Cuti</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-secondary-500 uppercase tracking-wider">Kode</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-secondary-500 uppercase tracking-wider">Jatah Default</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-secondary-500 uppercase tracking-wider">Jumlah Pengajuan</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-secondary-500 uppercase tracking-wider">Total Hari</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-secondary-500 uppercase tracking-wider">Berbayar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-secondary-200">
                    @php $grandTotalRequests = 0; $grandTotalDays = 0; @endphp
                    @forelse($leaveTypes as $leaveType)
                        @php
                            $grandTotalRequests += $leaveType->leave_requests_count;
                            $grandTotalDays += $leaveType->total_days_used;
                        @endphp
                        <tr class="hover:bg-secondary-50 transition-colors">
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full bg-{{ $leaveType->color ?? 'secondary' }}-500"></span>
                                    <span class="font-medium text-secondary-900">{{ $leaveType->name }}</span>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-secondary-600">{{ $leaveType->code ?? '-' }}</td>
                            <td class="px-4 py-3 text-center text-secondary-600">{{ $leaveType->default_days }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="font-medium text-secondary-900">{{ $leaveType->leave_requests_count }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="font-medium text-primary-600">{{ $leaveType->total_days_used }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($leaveType->is_paid)
                                    <x-badge type="success">Ya</x-badge>
                                @else
                                    <x-badge type="secondary">Tidak</x-badge>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-secondary-500">Tidak ada data</td>
                        </tr>
                    @endforelse
                </tbody>
                @if($leaveTypes->count() > 0)
                    <tfoot class="bg-secondary-50 border-t border-secondary-200">
                        <tr>
                            <td colspan="3" class="px-4 py-3 font-semibold text-secondary-900">Total</td>
                            <td class="px-4 py-3 text-center font-semibold text-secondary-900">{{ $grandTotalRequests }}</td>
                            <td class="px-4 py-3 text-center font-semibold text-primary-600">{{ $grandTotalDays }}</td>
                            <td class="px-4 py-3"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
@endsection
