@extends('layouts.admin')

@section('title', 'Buat Payroll')

@section('breadcrumb')
    <a href="{{ route('payrolls.index') }}" class="text-slate-500 hover:text-primary-600">Payroll</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Buat Payroll</span>
@endsection

@section('header')
    <div class="flex items-center gap-4">
        <a href="{{ route('payrolls.index') }}" class="btn btn-ghost btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Buat Payroll Baru</h1>
            <p class="text-secondary-500 mt-1">Buat periode penggajian baru untuk karyawan</p>
        </div>
    </div>
@endsection

@section('content')
    <form action="{{ route('payrolls.store') }}" method="POST" class="max-w-2xl">
        @csrf

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Informasi Payroll</h3>
            </div>
            <div class="card-body space-y-4">
                {{-- Name --}}
                <div>
                    <label for="name" class="form-label">Nama Payroll <span class="text-danger-500">*</span></label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name') }}"
                        class="form-input @error('name') border-danger-500 @enderror"
                        placeholder="Contoh: Gaji Januari 2026"
                        required
                    >
                    @error('name')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Period --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="period_month" class="form-label">Bulan <span class="text-danger-500">*</span></label>
                        <select
                            id="period_month"
                            name="period_month"
                            class="form-select @error('period_month') border-danger-500 @enderror"
                            required
                        >
                            <option value="">Pilih Bulan</option>
                            @foreach(range(1, 12) as $month)
                                <option value="{{ $month }}" {{ old('period_month', now()->month) == $month ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create()->month($month)->translatedFormat('F') }}
                                </option>
                            @endforeach
                        </select>
                        @error('period_month')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="period_year" class="form-label">Tahun <span class="text-danger-500">*</span></label>
                        <select
                            id="period_year"
                            name="period_year"
                            class="form-select @error('period_year') border-danger-500 @enderror"
                            required
                        >
                            <option value="">Pilih Tahun</option>
                            @foreach(range(now()->year - 1, now()->year + 1) as $year)
                                <option value="{{ $year }}" {{ old('period_year', now()->year) == $year ? 'selected' : '' }}>
                                    {{ $year }}
                                </option>
                            @endforeach
                        </select>
                        @error('period_year')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Period Range --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="period_start" class="form-label">Tanggal Mulai <span class="text-danger-500">*</span></label>
                        <input
                            type="date"
                            id="period_start"
                            name="period_start"
                            value="{{ old('period_start', now()->startOfMonth()->format('Y-m-d')) }}"
                            class="form-input @error('period_start') border-danger-500 @enderror"
                            required
                        >
                        @error('period_start')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="period_end" class="form-label">Tanggal Akhir <span class="text-danger-500">*</span></label>
                        <input
                            type="date"
                            id="period_end"
                            name="period_end"
                            value="{{ old('period_end', now()->endOfMonth()->format('Y-m-d')) }}"
                            class="form-input @error('period_end') border-danger-500 @enderror"
                            required
                        >
                        @error('period_end')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Notes --}}
                <div>
                    <label for="notes" class="form-label">Catatan</label>
                    <textarea
                        id="notes"
                        name="notes"
                        rows="3"
                        class="form-input @error('notes') border-danger-500 @enderror"
                        placeholder="Catatan tambahan (opsional)"
                    >{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div class="card-footer flex justify-end gap-2">
                <a href="{{ route('payrolls.index') }}" class="btn btn-ghost">Batal</a>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Simpan
                </button>
            </div>
        </div>
    </form>
@endsection
