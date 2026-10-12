@extends('layouts.admin')

@section('title', 'Payroll')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Payroll</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Payroll</h1>
            <p class="text-secondary-500 mt-1">Kelola penggajian bulanan karyawan</p>
        </div>
        <a href="{{ route('payrolls.create') }}" class="btn btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Buat Payroll
        </a>
    </div>
@endsection

@section('content')
    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body-sm">
            <form action="{{ route('payrolls.index') }}" method="GET" class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-[180px]">
                    <label class="block text-xs font-medium text-secondary-500 mb-1">Cari</label>
                    <input type="text" name="search" placeholder="Nama atau nomor payroll..." value="{{ request('search') }}" class="input w-full">
                </div>
                <div class="w-28">
                    <label class="block text-xs font-medium text-secondary-500 mb-1">Tahun</label>
                    <select name="year" class="input w-full">
                        <option value="">Semua</option>
                        @foreach($years as $year)
                            <option value="{{ $year }}" {{ request('year') == $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-32">
                    <label class="block text-xs font-medium text-secondary-500 mb-1">Status</label>
                    <select name="status" class="input w-full">
                        <option value="">Semua</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Diproses</option>
                        <option value="calculated" {{ request('status') === 'calculated' ? 'selected' : '' }}>Terhitung</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Disetujui</option>
                        <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Dibayar</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    @if(request()->hasAny(['search', 'year', 'status']))
                        <a href="{{ route('payrolls.index') }}" class="btn btn-ghost btn-sm">Reset</a>
                    @endif
                    <button type="submit" class="btn btn-primary btn-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        Cari
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Payroll List --}}
    <div class="card">
        <x-table>
            <x-slot name="header">
                <th>Payroll</th>
                <th>Periode</th>
                <th>Karyawan</th>
                <th>Total Gaji</th>
                <th>Status</th>
                <th>Dibuat</th>
                <th class="text-right">Aksi</th>
            </x-slot>

            @forelse($payrolls as $payroll)
                <tr>
                    <td>
                        <a href="{{ route('payrolls.show', $payroll) }}" class="font-medium text-secondary-900 hover:text-primary-600">{{ $payroll->name }}</a>
                        <p class="text-xs text-secondary-400">{{ $payroll->payroll_number }}</p>
                    </td>
                    <td class="text-secondary-700">{{ $payroll->period_label }}</td>
                    <td>
                        <span class="text-secondary-700">{{ $payroll->total_employees }}</span>
                        <span class="text-secondary-400 text-xs">orang</span>
                    </td>
                    <td class="font-medium text-secondary-900">{{ $payroll->formatted_total_net_salary }}</td>
                    <td>
                        @switch($payroll->status)
                            @case('draft')
                                <x-badge type="secondary">{{ $payroll->status_label }}</x-badge>
                                @break
                            @case('processing')
                                <x-badge type="info">{{ $payroll->status_label }}</x-badge>
                                @break
                            @case('calculated')
                                <x-badge type="warning">{{ $payroll->status_label }}</x-badge>
                                @break
                            @case('approved')
                                <x-badge type="primary">{{ $payroll->status_label }}</x-badge>
                                @break
                            @case('paid')
                                <x-badge type="success">{{ $payroll->status_label }}</x-badge>
                                @break
                            @case('cancelled')
                                <x-badge type="danger">{{ $payroll->status_label }}</x-badge>
                                @break
                        @endswitch
                    </td>
                    <td>
                        <span class="text-secondary-700">{{ $payroll->created_at->format('d M Y') }}</span>
                        @if($payroll->createdBy)
                            <p class="text-xs text-secondary-400">{{ $payroll->createdBy->name }}</p>
                        @endif
                    </td>
                    <td>
                        <div class="flex items-center justify-end gap-1">
                            <a href="{{ route('payrolls.show', $payroll) }}" class="p-1.5 text-secondary-400 hover:text-primary-600 hover:bg-primary-50 rounded-md transition-colors" title="Lihat Detail">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </a>
                            @if($payroll->canBeEdited())
                                <a href="{{ route('payrolls.edit', $payroll) }}" class="p-1.5 text-secondary-400 hover:text-primary-600 hover:bg-primary-50 rounded-md transition-colors" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-12">
                        <div class="flex flex-col items-center">
                            <svg class="w-12 h-12 text-secondary-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            <p class="text-secondary-500 mb-4">Belum ada data payroll</p>
                            <a href="{{ route('payrolls.create') }}" class="btn btn-primary">Buat Payroll Pertama</a>
                        </div>
                    </td>
                </tr>
            @endforelse
        </x-table>

        @if($payrolls->hasPages())
            <div class="card-footer">
                {{ $payrolls->links() }}
            </div>
        @endif
    </div>
@endsection
