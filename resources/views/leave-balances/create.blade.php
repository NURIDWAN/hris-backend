@extends('layouts.admin')

@section('title', 'Tambah Saldo Cuti')

@section('breadcrumb')
    <a href="{{ route('leave-balances.index') }}" class="text-primary-600 hover:text-primary-700">Saldo Cuti</a>
    <svg class="w-4 h-4 text-slate-400 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Tambah Saldo</span>
@endsection

@section('header')
    <div>
        <h1 class="text-2xl font-bold text-secondary-900">Tambah Saldo Cuti</h1>
        <p class="text-secondary-500 mt-1">Tambahkan jatah cuti untuk karyawan</p>
    </div>
@endsection

@section('content')
    <div class="max-w-2xl">
        <form action="{{ route('leave-balances.store') }}" method="POST">
            @csrf

            <div class="card">
                <div class="card-body space-y-6">
                    {{-- Employee --}}
                    <div>
                        <label for="employee_id" class="block text-sm font-medium text-secondary-700 mb-1">Karyawan *</label>
                        <select id="employee_id" name="employee_id" required class="form-select w-full @error('employee_id') border-danger-500 @enderror">
                            <option value="">Pilih Karyawan</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" {{ old('employee_id') == $employee->id ? 'selected' : '' }}>
                                    {{ $employee->full_name }} ({{ $employee->employee_id }})
                                </option>
                            @endforeach
                        </select>
                        @error('employee_id')
                            <p class="mt-1 text-sm text-danger-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Leave Type --}}
                    <div>
                        <label for="leave_type_id" class="block text-sm font-medium text-secondary-700 mb-1">Jenis Cuti *</label>
                        <select id="leave_type_id" name="leave_type_id" required class="form-select w-full @error('leave_type_id') border-danger-500 @enderror">
                            <option value="">Pilih Jenis Cuti</option>
                            @foreach($leaveTypes as $leaveType)
                                <option value="{{ $leaveType->id }}" data-default="{{ $leaveType->default_days }}" {{ old('leave_type_id') == $leaveType->id ? 'selected' : '' }}>
                                    {{ $leaveType->name }} (default: {{ $leaveType->default_days }} hari)
                                </option>
                            @endforeach
                        </select>
                        @error('leave_type_id')
                            <p class="mt-1 text-sm text-danger-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Year --}}
                    <div>
                        <label for="year" class="block text-sm font-medium text-secondary-700 mb-1">Tahun *</label>
                        <select id="year" name="year" required class="form-select w-full @error('year') border-danger-500 @enderror">
                            @for($y = now()->year + 1; $y >= now()->year - 1; $y--)
                                <option value="{{ $y }}" {{ old('year', now()->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                        @error('year')
                            <p class="mt-1 text-sm text-danger-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Entitled Days --}}
                    <div>
                        <label for="entitled_days" class="block text-sm font-medium text-secondary-700 mb-1">Jatah Cuti (Hari) *</label>
                        <input type="number" id="entitled_days" name="entitled_days" value="{{ old('entitled_days', 12) }}" step="0.5" min="0" max="365" required
                               class="form-input w-full @error('entitled_days') border-danger-500 @enderror">
                        <p class="mt-1 text-sm text-secondary-500">Jumlah hari cuti yang diberikan untuk tahun ini</p>
                        @error('entitled_days')
                            <p class="mt-1 text-sm text-danger-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Carried Forward Days --}}
                    <div>
                        <label for="carried_forward_days" class="block text-sm font-medium text-secondary-700 mb-1">Carry Forward (Hari)</label>
                        <input type="number" id="carried_forward_days" name="carried_forward_days" value="{{ old('carried_forward_days', 0) }}" step="0.5" min="0" max="365"
                               class="form-input w-full @error('carried_forward_days') border-danger-500 @enderror">
                        <p class="mt-1 text-sm text-secondary-500">Sisa cuti dari tahun sebelumnya yang dibawa ke tahun ini</p>
                        @error('carried_forward_days')
                            <p class="mt-1 text-sm text-danger-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Adjustment Days --}}
                    <div>
                        <label for="adjustment_days" class="block text-sm font-medium text-secondary-700 mb-1">Adjustment (Hari)</label>
                        <input type="number" id="adjustment_days" name="adjustment_days" value="{{ old('adjustment_days', 0) }}" step="0.5" min="-365" max="365"
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
                                  placeholder="Alasan adjustment (jika ada)">{{ old('adjustment_notes') }}</textarea>
                        @error('adjustment_notes')
                            <p class="mt-1 text-sm text-danger-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="card-footer flex items-center justify-end gap-3">
                    <a href="{{ route('leave-balances.index') }}" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Simpan
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('leave_type_id').addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            const defaultDays = selected.getAttribute('data-default');
            if (defaultDays) {
                document.getElementById('entitled_days').value = defaultDays;
            }
        });
    </script>
@endsection
