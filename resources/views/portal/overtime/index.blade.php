@extends('layouts.portal')

@section('title', 'Riwayat Lembur')

@section('content')
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">Riwayat Lembur</h1>
                <p class="text-secondary-500 mt-1">Lihat dan ajukan permohonan lembur.</p>
            </div>
            <a href="{{ route('portal.overtime.create') }}" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Ajukan Lembur
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
                            <p class="text-xl font-bold text-secondary-900">{{ $requests->where('status', 'pending')->count() }}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-success-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-secondary-500">Disetujui</p>
                            <p class="text-xl font-bold text-secondary-900">{{ $requests->where('status', 'approved')->count() }}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-secondary-500">Total Nilai</p>
                            <p class="text-xl font-bold text-secondary-900">
                                Rp {{ number_format($requests->where('status', 'approved')->sum('overtime_amount'), 0, ',', '.') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Request List --}}
        <div class="card">
            <x-table>
                <x-slot name="header">
                    <th>Tanggal</th>
                    <th>Waktu</th>
                    <th>Durasi</th>
                    <th>Tipe</th>
                    <th>Nilai</th>
                    <th>Status</th>
                    <th class="text-right">Aksi</th>
                </x-slot>

                @forelse($requests as $request)
                    <tr>
                        <td>
                            <p class="font-medium text-secondary-900">{{ $request->date->format('d M Y') }}</p>
                            <p class="text-sm text-secondary-500">{{ $request->date->translatedFormat('l') }}</p>
                        </td>
                        <td>{{ \Carbon\Carbon::parse($request->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($request->end_time)->format('H:i') }}</td>
                        <td>{{ $request->overtime_hours }} jam</td>
                        <td>
                            <x-badge type="{{ $request->overtime_type == 'holiday' ? 'danger' : ($request->overtime_type == 'weekend' ? 'warning' : 'info') }}">
                                {{ $request->overtime_type_label }}
                            </x-badge>
                        </td>
                        <td>{{ $request->formatted_overtime_amount }}</td>
                        <td>
                            <x-badge type="{{ match($request->status) { 'approved' => 'success', 'rejected' => 'danger', 'cancelled' => 'secondary', default => 'warning' } }}">
                                {{ $request->status_label }}
                            </x-badge>
                        </td>
                        <td class="text-right">
                            @if($request->isPending())
                                <button type="button"
                                        @click="$dispatch('confirm-dialog', {
                                            title: 'Batalkan Pengajuan',
                                            message: 'Apakah Anda yakin ingin membatalkan pengajuan lembur ini?',
                                            confirmText: 'Ya, Batalkan',
                                            type: 'warning',
                                            formAction: '{{ route('portal.overtime.cancel', $request) }}'
                                        })"
                                        class="btn btn-ghost btn-sm text-warning-600" title="Batalkan">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-8 text-secondary-500">
                            Belum ada riwayat lembur.
                        </td>
                    </tr>
                @endforelse
            </x-table>

            @if($requests->hasPages())
                <div class="card-footer">
                    {{ $requests->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
