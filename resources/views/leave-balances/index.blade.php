@extends('layouts.admin')

@section('title', 'Saldo Cuti')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Saldo Cuti</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Saldo Cuti</h1>
            <p class="text-secondary-500 mt-1">Kelola jatah cuti karyawan</p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" onclick="document.getElementById('bulkModal').classList.remove('hidden')" class="btn btn-ghost">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Generate Massal
            </button>
            <a href="{{ route('leave-balances.create') }}" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Saldo
            </a>
        </div>
    </div>
@endsection

@section('content')
    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body-sm">
            <form action="{{ route('leave-balances.index') }}" method="GET" class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-[180px]">
                    <label class="block text-xs font-medium text-secondary-500 mb-1">Cari</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari karyawan..." class="input w-full">
                </div>
                <div class="w-28">
                    <label class="block text-xs font-medium text-secondary-500 mb-1">Tahun</label>
                    <select name="year" class="input w-full">
                        @for($y = now()->year + 1; $y >= now()->year - 3; $y--)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="w-36">
                    <label class="block text-xs font-medium text-secondary-500 mb-1">Jenis Cuti</label>
                    <select name="leave_type_id" class="input w-full">
                        <option value="">Semua</option>
                        @foreach($leaveTypes as $leaveType)
                            <option value="{{ $leaveType->id }}" {{ request('leave_type_id') == $leaveType->id ? 'selected' : '' }}>
                                {{ $leaveType->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    @if(request()->hasAny(['search', 'leave_type_id']))
                        <a href="{{ route('leave-balances.index', ['year' => $year]) }}" class="btn btn-ghost btn-sm">Reset</a>
                    @endif
                    <button type="submit" class="btn btn-primary btn-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card">
        <x-table>
            <x-slot name="header">
                <th>Karyawan</th>
                <th>Jenis Cuti</th>
                <th class="text-center">Tahun</th>
                <th class="text-center">Jatah</th>
                <th class="text-center">Carry</th>
                <th class="text-center">Adj</th>
                <th class="text-center">Pakai</th>
                <th class="text-center">Pending</th>
                <th class="text-center">Sisa</th>
                <th class="text-right">Aksi</th>
            </x-slot>

            @forelse($leaveBalances as $balance)
                <tr>
                    <td>
                        <span class="font-medium text-secondary-900">{{ $balance->employee->full_name }}</span>
                        <p class="text-xs text-secondary-400">{{ $balance->employee->employee_id }}</p>
                    </td>
                    <td class="text-secondary-700">{{ $balance->leaveType->name }}</td>
                    <td class="text-center font-medium text-secondary-900">{{ $balance->year }}</td>
                    <td class="text-center font-medium text-secondary-900">{{ number_format($balance->entitled_days, 1) }}</td>
                    <td class="text-center text-secondary-600">{{ number_format($balance->carried_forward_days, 1) }}</td>
                    <td class="text-center">
                        @if($balance->adjustment_days != 0)
                            <span class="{{ $balance->adjustment_days > 0 ? 'text-success-600' : 'text-danger-600' }}">
                                {{ $balance->adjustment_days > 0 ? '+' : '' }}{{ number_format($balance->adjustment_days, 1) }}
                            </span>
                        @else
                            <span class="text-secondary-400">-</span>
                        @endif
                    </td>
                    <td class="text-center text-danger-600 font-medium">{{ number_format($balance->used_days, 1) }}</td>
                    <td class="text-center">
                        @if($balance->pending_days > 0)
                            <span class="text-warning-600 font-medium">{{ number_format($balance->pending_days, 1) }}</span>
                        @else
                            <span class="text-secondary-400">-</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="font-bold {{ $balance->remaining_days > 0 ? 'text-success-600' : 'text-danger-600' }}">
                            {{ number_format($balance->remaining_days, 1) }}
                        </span>
                    </td>
                    <td>
                        <div class="flex items-center justify-end gap-1">
                            <a href="{{ route('leave-balances.edit', $balance) }}" class="p-1.5 text-secondary-400 hover:text-primary-600 hover:bg-primary-50 rounded-md transition-colors" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            @if($balance->used_days == 0 && $balance->pending_days == 0)
                                <form action="{{ route('leave-balances.destroy', $balance) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Yakin ingin menghapus saldo cuti ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 text-secondary-400 hover:text-danger-600 hover:bg-danger-50 rounded-md transition-colors" title="Hapus">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center py-12">
                        <div class="flex flex-col items-center">
                            <svg class="w-12 h-12 text-secondary-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <p class="text-secondary-500 mb-4">Belum ada data saldo cuti untuk tahun {{ $year }}</p>
                            <button type="button" onclick="document.getElementById('bulkModal').classList.remove('hidden')" class="btn btn-primary btn-sm">
                                Generate Saldo Cuti
                            </button>
                        </div>
                    </td>
                </tr>
            @endforelse
        </x-table>

        @if($leaveBalances->hasPages())
            <div class="card-footer">
                {{ $leaveBalances->links() }}
            </div>
        @endif
    </div>

    {{-- Bulk Generate Modal --}}
    <div id="bulkModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-secondary-900/50 transition-opacity" onclick="document.getElementById('bulkModal').classList.add('hidden')"></div>
            <div class="relative bg-white rounded-2xl shadow-xl transform transition-all sm:max-w-lg sm:w-full p-6">
                <h3 class="text-lg font-bold text-secondary-900 mb-4">Generate Saldo Cuti Massal</h3>
                <p class="text-sm text-secondary-500 mb-6">Generate saldo cuti untuk semua karyawan aktif yang belum memiliki saldo untuk jenis cuti dan tahun yang dipilih.</p>

                <form action="{{ route('leave-balances.generate-bulk') }}" method="POST">
                    @csrf
                    <div class="space-y-4 text-left">
                        <div>
                            <label class="block text-sm font-medium text-secondary-700 mb-1">Jenis Cuti *</label>
                            <select name="leave_type_id" required class="form-select w-full">
                                <option value="">Pilih Jenis Cuti</option>
                                @foreach($leaveTypes as $leaveType)
                                    <option value="{{ $leaveType->id }}">{{ $leaveType->name }} ({{ $leaveType->default_days }} hari)</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-secondary-700 mb-1">Tahun *</label>
                            <select name="year" required class="form-select w-full">
                                @for($y = now()->year + 1; $y >= now()->year - 1; $y--)
                                    <option value="{{ $y }}" {{ $y == now()->year ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    <div class="mt-6 flex gap-3">
                        <button type="button" onclick="document.getElementById('bulkModal').classList.add('hidden')" class="btn btn-secondary flex-1">Batal</button>
                        <button type="submit" class="btn btn-primary flex-1">Generate</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
