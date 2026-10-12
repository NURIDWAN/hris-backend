@extends('layouts.admin')

@section('title', 'Pengajuan Reimbursement')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Keuangan</span>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Reimbursement</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Pengajuan Reimbursement</h1>
            <p class="text-secondary-500 mt-1">Kelola pengajuan penggantian biaya karyawan.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('reimbursement-categories.index') }}" class="btn btn-secondary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
                Kategori
            </a>
            <a href="{{ route('reimbursements.create') }}" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Pengajuan
            </a>
        </div>
    </div>
@endsection

@section('content')
    {{-- Filter --}}
    <div class="card mb-6">
        <div class="card-body">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <input type="text" name="search" value="{{ request('search') }}"
                           class="input w-full" placeholder="Cari karyawan...">
                </div>
                <div>
                    <select name="status" class="input w-full">
                        <option value="">Semua Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Menunggu</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Disetujui</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Ditolak</option>
                        <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Dibayar</option>
                    </select>
                </div>
                <div>
                    <select name="category_id" class="input w-full">
                        <option value="">Semua Kategori</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <input type="date" name="start_date" value="{{ request('start_date') }}"
                           class="input w-full" placeholder="Dari Tanggal">
                </div>
                <div class="flex gap-2">
                    <input type="date" name="end_date" value="{{ request('end_date') }}"
                           class="input w-full" placeholder="Sampai Tanggal">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card">
        <x-table>
            <x-slot name="header">
                <th>Karyawan</th>
                <th>Kategori</th>
                <th>Tanggal Pengeluaran</th>
                <th>Jumlah</th>
                <th>Deskripsi</th>
                <th>Status</th>
                <th class="text-right">Aksi</th>
            </x-slot>

            @forelse($reimbursements as $reimbursement)
                <tr>
                    <td>
                        <p class="font-medium text-secondary-900">{{ $reimbursement->employee->full_name }}</p>
                        <p class="text-sm text-secondary-500">{{ $reimbursement->employee->department?->name ?? '-' }}</p>
                    </td>
                    <td>{{ $reimbursement->category->name }}</td>
                    <td>{{ $reimbursement->expense_date->format('d M Y') }}</td>
                    <td class="font-medium">{{ $reimbursement->formatted_amount }}</td>
                    <td class="text-secondary-600">{{ Str::limit($reimbursement->description, 30) }}</td>
                    <td>
                        <x-badge type="{{ match($reimbursement->status) { 'approved' => 'info', 'rejected' => 'danger', 'paid' => 'success', default => 'warning' } }}">
                            {{ $reimbursement->status_label }}
                        </x-badge>
                    </td>
                    <td class="text-right">
                        <div class="flex items-center justify-end gap-1">
                            <a href="{{ route('reimbursements.show', $reimbursement) }}" class="btn btn-ghost btn-sm" title="Detail">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-8 text-secondary-500">
                        Belum ada pengajuan reimbursement.
                    </td>
                </tr>
            @endforelse
        </x-table>

        @if($reimbursements->hasPages())
            <div class="card-footer">
                {{ $reimbursements->links() }}
            </div>
        @endif
    </div>
@endsection
