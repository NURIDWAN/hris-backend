@extends('layouts.portal')

@section('title', 'Riwayat Reimbursement')

@section('content')
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">Riwayat Reimbursement</h1>
                <p class="text-secondary-500 mt-1">Lihat dan ajukan penggantian biaya.</p>
            </div>
            <a href="{{ route('portal.reimbursements.create') }}" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Ajukan Reimbursement
            </a>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="card">
                <div class="card-body">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-warning-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-secondary-500">Menunggu</p>
                            <p class="text-xl font-bold text-secondary-900">{{ $reimbursements->where('status', 'pending')->count() }}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-info-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-info-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-secondary-500">Disetujui</p>
                            <p class="text-xl font-bold text-secondary-900">{{ $reimbursements->where('status', 'approved')->count() }}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-success-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-secondary-500">Total Dibayar</p>
                            <p class="text-xl font-bold text-secondary-900">
                                Rp {{ number_format($reimbursements->where('status', 'paid')->sum('amount'), 0, ',', '.') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Reimbursement List --}}
        <div class="card">
            <x-table>
                <x-slot name="header">
                    <th>Tanggal</th>
                    <th>Kategori</th>
                    <th>Jumlah</th>
                    <th>Deskripsi</th>
                    <th>Status</th>
                    <th class="text-right">Aksi</th>
                </x-slot>

                @forelse($reimbursements as $reimbursement)
                    <tr>
                        <td>
                            <p class="font-medium text-secondary-900">{{ $reimbursement->expense_date->format('d M Y') }}</p>
                            <p class="text-sm text-secondary-500">Diajukan: {{ $reimbursement->created_at->format('d M Y') }}</p>
                        </td>
                        <td>{{ $reimbursement->category->name }}</td>
                        <td class="font-medium">{{ $reimbursement->formatted_amount }}</td>
                        <td class="text-secondary-600">{{ Str::limit($reimbursement->description, 30) }}</td>
                        <td>
                            <x-badge type="{{ match($reimbursement->status) { 'approved' => 'info', 'rejected' => 'danger', 'paid' => 'success', default => 'warning' } }}">
                                {{ $reimbursement->status_label }}
                            </x-badge>
                        </td>
                        <td class="text-right">
                            <a href="{{ route('portal.reimbursements.show', $reimbursement) }}" class="btn btn-ghost btn-sm" title="Detail">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-8 text-secondary-500">
                            Belum ada riwayat reimbursement.
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
    </div>
@endsection
