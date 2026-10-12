@extends('layouts.admin')

@section('title', 'Laporan Saldo Cuti')

@section('breadcrumb')
    <a href="{{ route('reports.leave') }}" class="text-primary-600 hover:text-primary-700">Laporan Cuti</a>
    <span class="mx-2 text-secondary-400">/</span>
    <span class="text-slate-700 font-medium">Saldo Cuti</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Laporan Saldo Cuti</h1>
            <p class="text-secondary-500 mt-1">Saldo cuti karyawan tahun {{ now()->year }}</p>
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
            <form action="{{ route('reports.leave.balance') }}" method="GET" class="flex flex-wrap items-end gap-3">
                <div class="w-40">
                    <label class="block text-xs font-medium text-secondary-500 mb-1">Departemen</label>
                    <select name="department_id" class="input w-full">
                        <option value="">Semua Departemen</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" {{ request('department_id') == $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    @if(request('department_id'))
                        <a href="{{ route('reports.leave.balance') }}" class="btn btn-ghost btn-sm">Reset</a>
                    @endif
                    <button type="submit" class="btn btn-primary btn-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Leave Balance Table --}}
    <div class="card">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-secondary-50 border-b border-secondary-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-secondary-500 uppercase tracking-wider sticky left-0 bg-secondary-50">Karyawan</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-secondary-500 uppercase tracking-wider">Departemen</th>
                        @foreach($leaveTypes as $leaveType)
                            <th class="px-4 py-3 text-center text-xs font-medium text-secondary-500 uppercase tracking-wider" colspan="3">
                                {{ $leaveType->name }}
                            </th>
                        @endforeach
                    </tr>
                    <tr class="bg-secondary-100">
                        <th class="px-4 py-2 sticky left-0 bg-secondary-100"></th>
                        <th class="px-4 py-2"></th>
                        @foreach($leaveTypes as $leaveType)
                            <th class="px-4 py-2 text-center text-xs text-secondary-500">Jatah</th>
                            <th class="px-4 py-2 text-center text-xs text-secondary-500">Terpakai</th>
                            <th class="px-4 py-2 text-center text-xs text-secondary-500">Sisa</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-secondary-200">
                    @forelse($employees as $employee)
                        <tr class="hover:bg-secondary-50 transition-colors">
                            <td class="px-4 py-3 sticky left-0 bg-white">
                                <p class="font-medium text-secondary-900">{{ $employee->full_name }}</p>
                                <p class="text-sm text-secondary-500">{{ $employee->employee_id }}</p>
                            </td>
                            <td class="px-4 py-3 text-secondary-600">{{ $employee->department?->name ?? '-' }}</td>
                            @foreach($leaveTypes as $leaveType)
                                @php
                                    $balance = $employee->balances[$leaveType->id] ?? ['allocated' => 0, 'used' => 0, 'remaining' => 0];
                                @endphp
                                <td class="px-4 py-3 text-center text-secondary-600">{{ $balance['allocated'] }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if($balance['used'] > 0)
                                        <span class="text-warning-600 font-medium">{{ $balance['used'] }}</span>
                                    @else
                                        <span class="text-secondary-400">0</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($balance['remaining'] > 0)
                                        <span class="text-success-600 font-medium">{{ $balance['remaining'] }}</span>
                                    @elseif($balance['remaining'] < 0)
                                        <span class="text-danger-600 font-medium">{{ $balance['remaining'] }}</span>
                                    @else
                                        <span class="text-secondary-400">0</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 2 + (count($leaveTypes) * 3) }}" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 text-secondary-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                    <p class="text-secondary-500">Tidak ada data karyawan aktif</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer flex items-center justify-between">
            <p class="text-sm text-secondary-500">Menampilkan {{ $employees->count() }} karyawan aktif</p>
            <div class="flex items-center gap-4 text-xs">
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-success-500"></span>
                    <span class="text-secondary-500">Sisa tersedia</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-warning-500"></span>
                    <span class="text-secondary-500">Terpakai</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-danger-500"></span>
                    <span class="text-secondary-500">Minus</span>
                </div>
            </div>
        </div>
    </div>
@endsection
