@extends('layouts.admin')

@section('title', 'Kategori Reimbursement')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Keuangan</span>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Kategori Reimbursement</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Kategori Reimbursement</h1>
            <p class="text-secondary-500 mt-1">Kelola kategori penggantian biaya karyawan.</p>
        </div>
        <a href="{{ route('reimbursement-categories.create') }}" class="btn btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Tambah Kategori
        </a>
    </div>
@endsection

@section('content')
    <div class="card">
        <x-table>
            <x-slot name="header">
                <th>Nama Kategori</th>
                <th>Deskripsi</th>
                <th>Maks. Amount</th>
                <th>Bukti Wajib</th>
                <th>Total Pengajuan</th>
                <th>Status</th>
                <th class="text-right">Aksi</th>
            </x-slot>

            @forelse($categories as $category)
                <tr>
                    <td class="font-medium text-secondary-900">{{ $category->name }}</td>
                    <td class="text-secondary-600">{{ Str::limit($category->description, 40) ?? '-' }}</td>
                    <td>{{ $category->max_amount ? 'Rp '.number_format($category->max_amount, 0, ',', '.') : 'Tidak Terbatas' }}</td>
                    <td>
                        @if($category->requires_receipt)
                            <x-badge type="warning">Ya</x-badge>
                        @else
                            <x-badge type="secondary">Tidak</x-badge>
                        @endif
                    </td>
                    <td>{{ $category->reimbursements_count }}</td>
                    <td>
                        @if($category->is_active)
                            <x-badge type="success">Aktif</x-badge>
                        @else
                            <x-badge type="secondary">Nonaktif</x-badge>
                        @endif
                    </td>
                    <td class="text-right">
                        <div class="flex items-center justify-end gap-1">
                            <a href="{{ route('reimbursement-categories.edit', $category) }}" class="btn btn-ghost btn-sm" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>
                            <button type="button"
                                    @click="$dispatch('confirm-dialog', {
                                        title: 'Hapus Kategori',
                                        message: 'Apakah Anda yakin ingin menghapus kategori {{ $category->name }}?',
                                        confirmText: 'Ya, Hapus',
                                        type: 'danger',
                                        formAction: '{{ route('reimbursement-categories.destroy', $category) }}'
                                    })"
                                    class="btn btn-ghost btn-sm text-danger-600" title="Hapus">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-8 text-secondary-500">
                        Belum ada kategori reimbursement.
                    </td>
                </tr>
            @endforelse
        </x-table>

        @if($categories->hasPages())
            <div class="card-footer">
                {{ $categories->links() }}
            </div>
        @endif
    </div>
@endsection
