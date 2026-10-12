@extends('layouts.portal')

@section('title', 'Ajukan Cuti')

@section('breadcrumb')
    <a href="{{ route('portal.dashboard') }}" class="text-slate-500 hover:text-primary-600">Dashboard</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <a href="{{ route('portal.leave.index') }}" class="text-slate-500 hover:text-primary-600">Pengajuan Cuti</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Ajukan Cuti</span>
@endsection

@section('header')
    <div>
        <h1 class="text-2xl font-bold text-secondary-900">Ajukan Cuti</h1>
        <p class="text-secondary-500 mt-1">Isi form berikut untuk mengajukan cuti.</p>
    </div>
@endsection

@section('content')
    <div class="max-w-2xl">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('portal.leave.store') }}" method="POST">
                    @csrf

                    {{-- Leave Type --}}
                    <div class="mb-4">
                        <label for="leave_type_id" class="block text-sm font-medium text-secondary-700 mb-1">
                            Jenis Cuti <span class="text-danger-500">*</span>
                        </label>
                        <select name="leave_type_id" id="leave_type_id"
                                class="input w-full @error('leave_type_id') border-danger-500 @enderror"
                                required>
                            <option value="">Pilih jenis cuti...</option>
                            @foreach($leaveTypes as $type)
                                @php
                                    $balance = $leaveBalances[$type->id] ?? null;
                                    $remaining = $balance ? $balance->remaining_days : 0;
                                @endphp
                                <option value="{{ $type->id }}" {{ old('leave_type_id') == $type->id ? 'selected' : '' }}>
                                    {{ $type->name }} (Sisa: {{ $remaining }} hari)
                                </option>
                            @endforeach
                        </select>
                        @error('leave_type_id')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Date Range --}}
                    <div class="grid gap-4 sm:grid-cols-2 mb-4">
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-secondary-700 mb-1">
                                Tanggal Mulai <span class="text-danger-500">*</span>
                            </label>
                            <input type="date" name="start_date" id="start_date"
                                   value="{{ old('start_date') }}"
                                   min="{{ today()->toDateString() }}"
                                   class="input w-full @error('start_date') border-danger-500 @enderror"
                                   required>
                            @error('start_date')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-secondary-700 mb-1">
                                Tanggal Selesai <span class="text-danger-500">*</span>
                            </label>
                            <input type="date" name="end_date" id="end_date"
                                   value="{{ old('end_date') }}"
                                   min="{{ today()->toDateString() }}"
                                   class="input w-full @error('end_date') border-danger-500 @enderror"
                                   required>
                            @error('end_date')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Reason --}}
                    <div class="mb-6">
                        <label for="reason" class="block text-sm font-medium text-secondary-700 mb-1">
                            Alasan <span class="text-danger-500">*</span>
                        </label>
                        <textarea name="reason" id="reason" rows="4"
                                  class="input w-full @error('reason') border-danger-500 @enderror"
                                  placeholder="Jelaskan alasan pengajuan cuti..."
                                  required>{{ old('reason') }}</textarea>
                        @error('reason')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('portal.leave.index') }}" class="btn btn-ghost">Batal</a>
                        <button type="submit" class="btn btn-primary">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Ajukan Cuti
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
