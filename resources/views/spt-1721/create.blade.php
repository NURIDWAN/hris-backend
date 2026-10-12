@extends('layouts.admin')

@section('title', 'Buat SPT Tahunan 1721')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    {{-- Header --}}
    <div class="flex items-center gap-4">
        <a href="{{ route('spt-1721.index') }}" class="btn btn-ghost">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Buat SPT Tahunan 1721</h1>
            <p class="text-secondary-600 mt-1">Formulir rekapitulasi PPh 21 tahunan</p>
        </div>
    </div>

    {{-- Form --}}
    <div class="card">
        <div class="card-body">
            <form action="{{ route('spt-1721.store') }}" method="POST" class="space-y-6">
                @csrf

                <div>
                    <label for="tax_year" class="block text-sm font-medium text-secondary-700 mb-1">
                        Tahun Pajak <span class="text-danger-500">*</span>
                    </label>
                    @if(count($availableYears) > 0)
                        <select name="tax_year" id="tax_year" class="input w-full @error('tax_year') border-danger-500 @enderror" required>
                            <option value="">Pilih Tahun Pajak</option>
                            @foreach($availableYears as $year)
                                <option value="{{ $year }}" {{ old('tax_year') == $year ? 'selected' : '' }}>
                                    {{ $year }}
                                </option>
                            @endforeach
                        </select>
                        @error('tax_year')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    @else
                        <div class="bg-warning-50 border border-warning-200 rounded-lg p-4">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-warning-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <div>
                                    <p class="font-medium text-warning-800">SPT sudah ada untuk semua tahun</p>
                                    <p class="text-sm text-warning-700 mt-1">
                                        SPT sudah dibuat untuk tahun: {{ implode(', ', $existingYears) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="bg-secondary-50 rounded-lg p-4">
                    <h3 class="font-medium text-secondary-900 mb-2">Informasi</h3>
                    <ul class="text-sm text-secondary-600 space-y-1">
                        <li class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-primary-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            SPT akan dibuat dengan status <strong>Draft</strong>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-primary-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Data akan dihitung dari Payroll dan Bukti Potong 1721-A1
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-primary-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Anda dapat mengekspor data untuk e-SPT DJP Online
                        </li>
                    </ul>
                </div>

                @if(count($availableYears) > 0)
                    <div class="flex justify-end gap-3">
                        <a href="{{ route('spt-1721.index') }}" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Buat SPT
                        </button>
                    </div>
                @endif
            </form>
        </div>
    </div>
</div>
@endsection
