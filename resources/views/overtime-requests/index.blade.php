@extends('layouts.admin')

@section('title', 'Pengajuan Lembur')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Lembur</span>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Pengajuan Lembur</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Pengajuan Lembur</h1>
            <p class="text-secondary-500 mt-1">Kelola pengajuan lembur karyawan.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('overtime-settings.index') }}" class="btn btn-secondary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Pengaturan
            </a>
            <a href="{{ route('overtime-requests.create') }}" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Ajukan Lembur
            </a>
        </div>
    </div>
@endsection

@section('content')
    {{-- Filters --}}
    <div class="card mb-6">
        <div class="card-body">
            <form action="{{ route('overtime-requests.index') }}" method="GET" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Cari nama/ID karyawan..."
                           class="input w-full">
                </div>
                <div>
                    <select name="status" class="input">
                        <option value="">Semua Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Menunggu</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Disetujui</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Ditolak</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                    </select>
                </div>
                <div>
                    <select name="overtime_type" class="input">
                        <option value="">Semua Tipe</option>
                        <option value="weekday" {{ request('overtime_type') == 'weekday' ? 'selected' : '' }}>Hari Kerja</option>
                        <option value="weekend" {{ request('overtime_type') == 'weekend' ? 'selected' : '' }}>Akhir Pekan</option>
                        <option value="holiday" {{ request('overtime_type') == 'holiday' ? 'selected' : '' }}>Hari Libur</option>
                    </select>
                </div>
                <div>
                    <input type="date" name="start_date" value="{{ request('start_date') }}"
                           class="input" placeholder="Dari tanggal">
                </div>
                <div>
                    <input type="date" name="end_date" value="{{ request('end_date') }}"
                           class="input" placeholder="Sampai tanggal">
                </div>
                <button type="submit" class="btn btn-primary">Filter</button>
                @if(request()->hasAny(['search', 'status', 'overtime_type', 'start_date', 'end_date']))
                    <a href="{{ route('overtime-requests.index') }}" class="btn btn-ghost">Reset</a>
                @endif
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card">
        <x-table>
            <x-slot name="header">
                <th>Karyawan</th>
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
                        <div>
                            <p class="font-medium text-secondary-900">{{ $request->employee?->full_name ?? 'Karyawan Dihapus' }}</p>
                            <p class="text-sm text-secondary-500">{{ $request->employee?->employee_id ?? '-' }}</p>
                        </div>
                    </td>
                    <td>{{ $request->date->format('d M Y') }}</td>
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
                        <div class="flex items-center justify-end gap-2">
                            @if($request->isPending())
                                <form action="{{ route('overtime-requests.approve', $request) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="btn btn-ghost btn-sm text-success-600" title="Setujui">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </button>
                                </form>
                                <button type="button"
                                        @click="$dispatch('confirm-dialog', {
                                            title: 'Tolak Pengajuan',
                                            message: 'Apakah Anda yakin ingin menolak pengajuan lembur ini?',
                                            confirmText: 'Ya, Tolak',
                                            type: 'danger',
                                            formAction: '{{ route('overtime-requests.reject', $request) }}',
                                            showReasonField: true
                                        })"
                                        class="btn btn-ghost btn-sm text-danger-600" title="Tolak">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            @endif
                            <a href="{{ route('overtime-requests.show', $request) }}" class="btn btn-ghost btn-sm" title="Detail">
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
                    <td colspan="8" class="text-center py-8 text-secondary-500">
                        Belum ada pengajuan lembur.
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
@endsection
