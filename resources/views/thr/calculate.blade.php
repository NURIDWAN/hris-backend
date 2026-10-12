@extends('layouts.admin')

@section('title', 'Hitung THR')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Pajak & BPJS</span>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <a href="{{ route('thr.index') }}" class="text-slate-500 hover:text-primary-600">THR</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Hitung THR</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Hitung THR</h1>
            <p class="text-secondary-500 mt-1">Kalkulasi THR untuk karyawan yang memenuhi syarat.</p>
        </div>
        <a href="{{ route('thr.index') }}" class="btn btn-ghost">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali
        </a>
    </div>
@endsection

@section('content')
    @if(!$setting)
        <div class="card">
            <div class="card-body text-center py-8">
                <div class="w-16 h-16 bg-warning-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-secondary-900 mb-2">Pengaturan THR Belum Dikonfigurasi</h3>
                <p class="text-secondary-500 mb-4">Silakan konfigurasi pengaturan THR terlebih dahulu.</p>
                <a href="{{ route('thr-settings.index') }}" class="btn btn-primary">
                    Konfigurasi THR
                </a>
            </div>
        </div>
    @else
        <div x-data="thrCalculator()" x-init="init()">
            {{-- Calculation Form --}}
            <div class="card mb-6">
                <div class="card-header">
                    <h3 class="card-title">Parameter Perhitungan</h3>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="year" class="block text-sm font-medium text-secondary-700 mb-1">
                                Tahun <span class="text-danger-500">*</span>
                            </label>
                            <select x-model="year" id="year" class="input w-full">
                                @foreach($years as $y)
                                    <option value="{{ $y }}">{{ $y }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="religious_holiday" class="block text-sm font-medium text-secondary-700 mb-1">
                                Hari Raya <span class="text-danger-500">*</span>
                            </label>
                            <select x-model="religiousHoliday" id="religious_holiday" class="input w-full">
                                @foreach($religiousHolidays as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="payment_date" class="block text-sm font-medium text-secondary-700 mb-1">
                                Tanggal Pembayaran <span class="text-danger-500">*</span>
                            </label>
                            <input type="date" x-model="paymentDate" id="payment_date" class="input w-full">
                        </div>
                    </div>

                    <div class="mt-4 p-4 bg-secondary-50 rounded-lg">
                        <h4 class="font-medium text-secondary-900 mb-2">Pengaturan Aktif:</h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <span class="text-secondary-500">Metode:</span>
                                <span class="font-medium">{{ $setting->calculation_method_label }}</span>
                            </div>
                            <div>
                                <span class="text-secondary-500">Min. Masa Kerja:</span>
                                <span class="font-medium">{{ $setting->min_service_months }} bulan</span>
                            </div>
                            <div>
                                <span class="text-secondary-500">Tunjangan:</span>
                                <span class="font-medium">{{ $setting->include_allowances ? 'Termasuk' : 'Tidak' }}</span>
                            </div>
                            <div>
                                <span class="text-secondary-500">Formula:</span>
                                <span class="font-medium">{{ $setting->prorata_formula_label }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 flex justify-end">
                        <button @click="calculate()" :disabled="loading" class="btn btn-primary">
                            <template x-if="loading">
                                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </template>
                            <template x-if="!loading">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                            </template>
                            Hitung THR
                        </button>
                    </div>
                </div>
            </div>

            {{-- Results --}}
            <template x-if="calculated && results.length > 0">
                <div class="card">
                    <div class="card-header flex items-center justify-between">
                        <h3 class="card-title">Hasil Perhitungan (<span x-text="results.length"></span> karyawan)</h3>
                        <div class="flex items-center gap-2">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" @change="toggleSelectAll($event)" class="w-4 h-4 rounded border-secondary-300 text-primary-600 focus:ring-primary-500">
                                <span class="text-sm text-secondary-700">Pilih Semua</span>
                            </label>
                        </div>
                    </div>
                    <x-table>
                        <x-slot name="header">
                            <th class="w-10"></th>
                            <th>Karyawan</th>
                            <th>Departemen</th>
                            <th>Masa Kerja</th>
                            <th class="text-right">Gaji Pokok</th>
                            <th class="text-right">Tunjangan</th>
                            <th class="text-right">THR</th>
                        </x-slot>

                        <template x-for="(result, index) in results" :key="result.employee_id">
                            <tr>
                                <td>
                                    <input type="checkbox" x-model="selectedEmployees" :value="result.employee_id"
                                           class="w-4 h-4 rounded border-secondary-300 text-primary-600 focus:ring-primary-500">
                                </td>
                                <td>
                                    <div>
                                        <p class="font-medium text-secondary-900" x-text="result.employee_name"></p>
                                        <p class="text-sm text-secondary-500" x-text="result.employee_number"></p>
                                    </div>
                                </td>
                                <td x-text="result.department || '-'"></td>
                                <td x-text="result.service_months + ' bulan'"></td>
                                <td class="text-right" x-text="formatCurrency(result.base_salary)"></td>
                                <td class="text-right" x-text="formatCurrency(result.allowances)"></td>
                                <td class="text-right font-semibold text-primary-600" x-text="formatCurrency(result.thr_amount)"></td>
                            </tr>
                        </template>
                    </x-table>

                    <div class="card-footer flex items-center justify-between">
                        <div class="text-sm text-secondary-500">
                            <span x-text="selectedEmployees.length"></span> karyawan dipilih |
                            Total THR: <span class="font-semibold text-secondary-900" x-text="formatCurrency(totalSelectedThr)"></span>
                        </div>
                        <form action="{{ route('thr.process') }}" method="POST" @submit="submitForm($event)">
                            @csrf
                            <input type="hidden" name="year" :value="year">
                            <input type="hidden" name="religious_holiday" :value="religiousHoliday">
                            <input type="hidden" name="payment_date" :value="paymentDate">
                            <template x-for="empId in selectedEmployees" :key="empId">
                                <input type="hidden" name="employee_ids[]" :value="empId">
                            </template>
                            <button type="submit" :disabled="selectedEmployees.length === 0" class="btn btn-primary">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Proses THR
                            </button>
                        </form>
                    </div>
                </div>
            </template>

            <template x-if="calculated && results.length === 0">
                <div class="card">
                    <div class="card-body text-center py-8">
                        <div class="w-16 h-16 bg-secondary-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-secondary-900 mb-2">Tidak Ada Karyawan yang Memenuhi Syarat</h3>
                        <p class="text-secondary-500">Tidak ada karyawan aktif yang memenuhi kriteria THR.</p>
                    </div>
                </div>
            </template>
        </div>

        <script>
            function thrCalculator() {
                return {
                    year: {{ $years[0] }},
                    religiousHoliday: '{{ $setting->religious_holiday }}',
                    paymentDate: '{{ now()->addDays(7)->format('Y-m-d') }}',
                    loading: false,
                    calculated: false,
                    results: [],
                    selectedEmployees: [],

                    init() {
                        // Auto-calculate on load can be enabled here
                    },

                    async calculate() {
                        this.loading = true;
                        this.calculated = false;

                        try {
                            const response = await fetch('{{ route('thr.do-calculate') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({
                                    year: this.year,
                                    religious_holiday: this.religiousHoliday,
                                }),
                            });

                            const data = await response.json();

                            if (data.success) {
                                this.results = data.data;
                                this.selectedEmployees = this.results.map(r => r.employee_id);
                            } else {
                                alert(data.message || 'Terjadi kesalahan');
                            }
                        } catch (error) {
                            console.error('Error:', error);
                            alert('Terjadi kesalahan saat menghitung THR');
                        } finally {
                            this.loading = false;
                            this.calculated = true;
                        }
                    },

                    toggleSelectAll(event) {
                        if (event.target.checked) {
                            this.selectedEmployees = this.results.map(r => r.employee_id);
                        } else {
                            this.selectedEmployees = [];
                        }
                    },

                    get totalSelectedThr() {
                        return this.results
                            .filter(r => this.selectedEmployees.includes(r.employee_id))
                            .reduce((sum, r) => sum + r.thr_amount, 0);
                    },

                    formatCurrency(value) {
                        return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
                    },

                    submitForm(event) {
                        if (this.selectedEmployees.length === 0) {
                            event.preventDefault();
                            alert('Pilih minimal satu karyawan');
                        }
                    }
                }
            }
        </script>
    @endif
@endsection
