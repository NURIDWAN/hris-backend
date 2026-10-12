@extends('layouts.portal')

@section('title', 'Pengajuan Cuti')

@section('breadcrumb')
    <a href="{{ route('portal.dashboard') }}" class="text-slate-500 hover:text-primary-600">Dashboard</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Pengajuan Cuti</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Pengajuan Cuti</h1>
            <p class="text-secondary-500 mt-1">Kelola pengajuan cuti Anda.</p>
        </div>
        <a href="{{ route('portal.leave.create') }}" class="btn btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Ajukan Cuti
        </a>
    </div>
@endsection

@section('content')
    {{-- Leave Balances --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        @forelse($leaveBalances as $balance)
            <div class="card">
                <div class="card-body text-center">
                    <div class="text-3xl font-bold text-primary-600">{{ $balance->remaining_days }}</div>
                    <div class="text-secondary-500">{{ $balance->leaveType->name ?? 'Cuti' }}</div>
                    <div class="text-xs text-secondary-400 mt-1">dari {{ $balance->total_entitled }} hari</div>
                </div>
            </div>
        @empty
            <div class="card sm:col-span-2 lg:col-span-4">
                <div class="card-body text-center text-secondary-500">
                    Belum ada data saldo cuti
                </div>
            </div>
        @endforelse
    </div>

    {{-- Leave Requests History --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Riwayat Pengajuan</h3>
        </div>
        <div class="card-body p-0">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-secondary-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-medium text-secondary-500">Jenis Cuti</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-secondary-500">Tanggal</th>
                            <th class="px-4 py-3 text-center text-sm font-medium text-secondary-500">Hari</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-secondary-500">Alasan</th>
                            <th class="px-4 py-3 text-center text-sm font-medium text-secondary-500">Status</th>
                            <th class="px-4 py-3 text-center text-sm font-medium text-secondary-500">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-secondary-100">
                        @forelse($leaveRequests as $request)
                            <tr>
                                <td class="px-4 py-3 font-medium text-secondary-900">
                                    {{ $request->leaveType->name ?? '-' }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-sm">
                                        {{ $request->start_date->format('d M Y') }}
                                        @if($request->start_date->ne($request->end_date))
                                            - {{ $request->end_date->format('d M Y') }}
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">{{ $request->total_days }}</td>
                                <td class="px-4 py-3 max-w-xs truncate">{{ $request->reason }}</td>
                                <td class="px-4 py-3 text-center">
                                    @switch($request->status)
                                        @case('pending')
                                            <x-badge type="warning">Menunggu</x-badge>
                                            @break
                                        @case('approved')
                                            <x-badge type="success">Disetujui</x-badge>
                                            @break
                                        @case('rejected')
                                            <x-badge type="danger">Ditolak</x-badge>
                                            @break
                                        @case('cancelled')
                                            <x-badge type="secondary">Dibatalkan</x-badge>
                                            @break
                                    @endswitch
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($request->status === 'pending')
                                        <form action="{{ route('portal.leave.cancel', $request) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="text-danger-600 hover:text-danger-700 text-sm" onclick="return confirm('Yakin ingin membatalkan pengajuan ini?')">
                                                Batalkan
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-secondary-400">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-secondary-500">
                                    Belum ada riwayat pengajuan cuti
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($leaveRequests->hasPages())
            <div class="card-footer">
                {{ $leaveRequests->links() }}
            </div>
        @endif
    </div>
@endsection
