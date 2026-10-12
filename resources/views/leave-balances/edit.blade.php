@extends('layouts.admin')

@section('title', 'Edit Saldo Cuti')

@section('breadcrumb')
    <a href="{{ route('leave-balances.index') }}" class="text-primary-600 hover:text-primary-700">Saldo Cuti</a>
    <svg class="w-4 h-4 text-slate-400 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Edit Saldo</span>
@endsection

@section('header')
    <div>
        <h1 class="text-2xl font-bold text-secondary-900">Edit Saldo Cuti</h1>
        <p class="text-secondary-500 mt-1">{{ $leaveBalance->employee->full_name }} - {{ $leaveBalance->leaveType->name }} ({{ $leaveBalance->year }})</p>
    </div>
@endsection

@section('content')
    <div class="max-w-2xl">
        {{-- Info Card --}}
        <div class="card mb-6">
            <div class="card-body">
                <h3 class="text-sm font-medium text-secondary-500 mb-3">Informasi Karyawan</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-secondary-500">Karyawan</p>
                        <p class="font-medium text-secondary-900">{{ $leaveBalance->employee->full_name }}</p>
                        <p class="text-sm text-secondary-500">{{ $leaveBalance->employee->employee_id }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-secondary-500">Jenis Cuti</p>
                        <p class="font-medium text-secondary-900">{{ $leaveBalance->leaveType->name }}</p>
                        <p class="text-sm text-secondary-500">Tahun {{ $leaveBalance->year }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Usage Summary --}}
        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="card">
                <div class="card-body text-center">
                    <p class="text-sm text-secondary-500 mb-1">Terpakai</p>
                    <p class="text-2xl font-bold text-danger-600">{{ number_format($leaveBalance->used_days, 1) }}</p>
                </div>
            </div>
            <div class="card">
                <div class="card-body text-center">
                    <p class="text-sm text-secondary-500 mb-1">Pending</p>
                    <p class="text-2xl font-bold text-warning-600">{{ number_format($leaveBalance->pending_days, 1) }}</p>
                </div>
            </div>
            <div class="card">
                <div class="card-body text-center">
                    <p class="text-sm text-secondary-500 mb-1">Sisa</p>
                    <p class="text-2xl font-bold {{ $leaveBalance->remaining_days > 0 ? 'text-success-600' : 'text-danger-600' }}">
                        {{ number_format($leaveBalance->remaining_days, 1) }}
                    </p>
                </div>
            </div>
        </div>

        <form action="{{ route('leave-balances.update', $leaveBalance) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="card">
                <div class="card-body space-y-6">
                    {{-- Entitled Days --}}
                    <div>
                        <label for="entitled_days" class="block text-sm font-medium text-secondary-700 mb-1">Jatah Cuti (Hari) *</label>
                        <input type="number" id="entitled_days" name="entitled_days" value="{{ old('entitled_days', $leaveBalance->entitled_days) }}" step="0.5" min="0" max="365" required
                               class="form-input w-full @error('entitled_days') border-danger-500 @enderror">
                        <p class="mt-1 text-sm text-secondary-500">Jumlah hari cuti yang diberikan untuk tahun ini</p>
                        @error('entitled_days')
                            <p class="mt-1 text-sm text-danger-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Carried Forward Days --}}
                    <div>
                        <label for="carried_forward_days" class="block text-sm font-medium text-secondary-700 mb-1">Carry Forward (Hari)</label>
                        <input type="number" id="carried_forward_days" name="carried_forward_days" value="{{ old('carried_forward_days', $leaveBalance->carried_forward_days) }}" step="0.5" min="0" max="365"
                               class="form-input w-full @error('carried_forward_days') border-danger-500 @enderror">
                        <p class="mt-1 text-sm text-secondary-500">Sisa cuti dari tahun sebelumnya yang dibawa ke tahun ini</p>
                        @error('carried_forward_days')
                            <p class="mt-1 text-sm text-danger-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Adjustment Days --}}
                    <div>
                        <label for="adjustment_days" class="block text-sm font-medium text-secondary-700 mb-1">Adjustment (Hari)</label>
                        <input type="number" id="adjustment_days" name="adjustment_days" value="{{ old('adjustment_days', $leaveBalance->adjustment_days) }}" step="0.5" min="-365" max="365"
                               class="form-input w-full @error('adjustment_days') border-danger-500 @enderror">
                        <p class="mt-1 text-sm text-secondary-500">Penyesuaian tambahan (positif untuk tambah, negatif untuk kurang)</p>
                        @error('adjustment_days')
                            <p class="mt-1 text-sm text-danger-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Adjustment Notes --}}
                    <div>
                        <label for="adjustment_notes" class="block text-sm font-medium text-secondary-700 mb-1">Catatan Adjustment</label>
                        <textarea id="adjustment_notes" name="adjustment_notes" rows="2"
                                  class="form-textarea w-full @error('adjustment_notes') border-danger-500 @enderror"
                                  placeholder="Alasan adjustment (jika ada)">{{ old('adjustment_notes', $leaveBalance->adjustment_notes) }}</textarea>
                        @error('adjustment_notes')
                            <p class="mt-1 text-sm text-danger-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Calculated Total --}}
                    <div class="bg-secondary-50 rounded-xl p-4">
                        <p class="text-sm text-secondary-500 mb-2">Total Jatah Cuti</p>
                        <p class="text-lg font-bold text-secondary-900" id="totalDisplay">
                            {{ number_format($leaveBalance->total_entitled, 1) }} hari
                        </p>
                        <p class="text-xs text-secondary-500 mt-1">Jatah + Carry Forward + Adjustment</p>
                    </div>
                </div>

                <div class="card-footer flex items-center justify-end gap-3">
                    <a href="{{ route('leave-balances.index') }}" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Simpan Perubahan
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
        function updateTotal() {
            const entitled = parseFloat(document.getElementById('entitled_days').value) || 0;
            const carried = parseFloat(document.getElementById('carried_forward_days').value) || 0;
            const adjustment = parseFloat(document.getElementById('adjustment_days').value) || 0;
            const total = entitled + carried + adjustment;
            document.getElementById('totalDisplay').textContent = total.toFixed(1) + ' hari';
        }

        document.getElementById('entitled_days').addEventListener('input', updateTotal);
        document.getElementById('carried_forward_days').addEventListener('input', updateTotal);
        document.getElementById('adjustment_days').addEventListener('input', updateTotal);
    </script>
@endsection
