@extends('layouts.admin')

@section('title', 'Pengaturan BPJS Kesehatan')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Pengaturan BPJS Kesehatan</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Pengaturan BPJS Kesehatan</h1>
            <p class="text-secondary-500 mt-1">Konfigurasi iuran BPJS Kesehatan perusahaan.</p>
        </div>
    </div>
@endsection

@section('content')
    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-4">
        <div class="stat-card">
            <p class="stat-card-label">Iuran Perusahaan</p>
            <p class="text-lg font-bold text-primary-600">{{ number_format($setting->company_rate, 2) }}%</p>
        </div>
        <div class="stat-card">
            <p class="stat-card-label">Iuran Karyawan</p>
            <p class="text-lg font-bold text-warning-600">{{ number_format($setting->employee_rate, 2) }}%</p>
        </div>
        <div class="stat-card">
            <p class="stat-card-label">Total Iuran</p>
            <p class="text-lg font-bold text-success-600">{{ number_format($setting->total_rate, 2) }}%</p>
        </div>
        <div class="stat-card">
            <p class="stat-card-label">Batas Upah Maks.</p>
            <p class="text-lg font-bold text-secondary-900">{{ $setting->formatted_max_salary_basis }}</p>
        </div>
    </div>

    <form action="{{ route('bpjs-kes-settings.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            {{-- Tarif Iuran --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Tarif Iuran</h3>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="company_rate" class="block text-sm font-medium text-secondary-700 mb-1">
                                Iuran Perusahaan (%) <span class="text-danger-500">*</span>
                            </label>
                            <input type="number" name="company_rate" id="company_rate"
                                   value="{{ old('company_rate', $setting->company_rate) }}"
                                   step="0.01" min="0" max="100"
                                   class="input w-full @error('company_rate') border-danger-500 @enderror">
                            @error('company_rate')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                            <p class="text-xs text-secondary-400 mt-1">Standar: 4%</p>
                        </div>
                        <div>
                            <label for="employee_rate" class="block text-sm font-medium text-secondary-700 mb-1">
                                Iuran Karyawan (%) <span class="text-danger-500">*</span>
                            </label>
                            <input type="number" name="employee_rate" id="employee_rate"
                                   value="{{ old('employee_rate', $setting->employee_rate) }}"
                                   step="0.01" min="0" max="100"
                                   class="input w-full @error('employee_rate') border-danger-500 @enderror">
                            @error('employee_rate')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                            <p class="text-xs text-secondary-400 mt-1">Standar: 1%</p>
                        </div>
                    </div>
                    <div class="mt-4 p-3 bg-info-50 rounded-lg">
                        <p class="text-sm text-info-700">
                            <strong>Info:</strong> Sesuai aturan BPJS Kesehatan, total iuran adalah 5% dari gaji
                            dengan pembagian 4% ditanggung perusahaan dan 1% ditanggung karyawan.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Batas Upah --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Batas Dasar Upah</h3>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-2 gap-4">
                        <div x-data="currencyInput({{ old('min_salary_basis', $setting->min_salary_basis ?? 0) }})">
                            <label for="min_salary_basis_display" class="block text-sm font-medium text-secondary-700 mb-1">
                                Upah Minimum <span class="text-danger-500">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" id="min_salary_basis_display" x-model="display" @input="updateValue($event)"
                                       class="input @error('min_salary_basis') border-danger-500 @enderror"
                                       placeholder="0" inputmode="numeric">
                                <input type="hidden" name="min_salary_basis" :value="value">
                            </div>
                            @error('min_salary_basis')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                            <p class="text-xs text-secondary-400 mt-1">UMR sebagai dasar minimum</p>
                        </div>
                        <div x-data="currencyInput({{ old('max_salary_basis', $setting->max_salary_basis ?? 0) }})">
                            <label for="max_salary_basis_display" class="block text-sm font-medium text-secondary-700 mb-1">
                                Upah Maksimum <span class="text-danger-500">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" id="max_salary_basis_display" x-model="display" @input="updateValue($event)"
                                       class="input @error('max_salary_basis') border-danger-500 @enderror"
                                       placeholder="0" inputmode="numeric">
                                <input type="hidden" name="max_salary_basis" :value="value">
                            </div>
                            @error('max_salary_basis')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                            <p class="text-xs text-secondary-400 mt-1">Standar: Rp 12.000.000</p>
                        </div>
                    </div>
                    <div class="mt-4 p-3 bg-warning-50 rounded-lg">
                        <p class="text-sm text-warning-700">
                            <strong>Catatan:</strong> Upah di bawah minimum akan dihitung berdasarkan batas minimum,
                            dan upah di atas maksimum akan dihitung berdasarkan batas maksimum.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Pengaturan Lainnya --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Pengaturan Lainnya</h3>
                </div>
                <div class="card-body">
                    <div>
                        <label for="effective_year" class="block text-sm font-medium text-secondary-700 mb-1">
                            Tahun Efektif <span class="text-danger-500">*</span>
                        </label>
                        <input type="number" name="effective_year" id="effective_year"
                               value="{{ old('effective_year', $setting->effective_year) }}"
                               min="2020" max="2030"
                               class="input w-full @error('effective_year') border-danger-500 @enderror">
                        @error('effective_year')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="mt-4">
                        <label for="notes" class="block text-sm font-medium text-secondary-700 mb-1">Catatan</label>
                        <textarea name="notes" id="notes" rows="3"
                                  class="input w-full @error('notes') border-danger-500 @enderror">{{ old('notes', $setting->notes) }}</textarea>
                        @error('notes')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end mt-4">
            <button type="submit" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Simpan Pengaturan
            </button>
        </div>
    </form>

    {{-- Calculator --}}
    <div class="card mt-4">
        <div class="card-header">
            <h3 class="card-title">Kalkulator BPJS Kesehatan</h3>
        </div>
        <div class="card-body">
            <div class="flex flex-wrap items-end gap-3">
                <div class="w-48">
                    <label class="block text-sm font-medium text-secondary-700 mb-1">Gaji Pokok (Rp)</label>
                    <input type="number" id="calcSalary" value="5000000" class="input w-full">
                </div>
                <button type="button" onclick="calculateBpjsKes()" class="btn btn-primary btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    Hitung
                </button>
            </div>
            <div id="calcResult" class="mt-4 hidden">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <div class="p-3 bg-secondary-50 rounded-lg">
                        <p class="text-xs text-secondary-600 font-medium">Dasar Upah</p>
                        <p class="text-lg font-bold text-secondary-700" id="resSalaryBasis">-</p>
                    </div>
                    <div class="p-3 bg-primary-50 rounded-lg">
                        <p class="text-xs text-primary-600 font-medium">Iuran Perusahaan</p>
                        <p class="text-lg font-bold text-primary-700" id="resCompany">-</p>
                    </div>
                    <div class="p-3 bg-warning-50 rounded-lg">
                        <p class="text-xs text-warning-600 font-medium">Iuran Karyawan</p>
                        <p class="text-lg font-bold text-warning-700" id="resEmployee">-</p>
                    </div>
                    <div class="p-3 bg-success-50 rounded-lg">
                        <p class="text-xs text-success-600 font-medium">Total Iuran</p>
                        <p class="text-lg font-bold text-success-700" id="resTotal">-</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function formatRupiah(num) {
            return 'Rp ' + Math.round(num).toLocaleString('id-ID');
        }

        function calculateBpjsKes() {
            const salary = parseFloat(document.getElementById('calcSalary').value) || 0;

            fetch('{{ route("bpjs-kes-settings.calculate") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    salary: salary
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    return;
                }

                document.getElementById('calcResult').classList.remove('hidden');
                document.getElementById('resSalaryBasis').textContent = formatRupiah(data.contribution.salary_basis);
                document.getElementById('resCompany').textContent = formatRupiah(data.contribution.company);
                document.getElementById('resEmployee').textContent = formatRupiah(data.contribution.employee);
                document.getElementById('resTotal').textContent = formatRupiah(data.contribution.total);
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
    </script>
@endsection
