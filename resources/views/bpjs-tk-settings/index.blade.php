@extends('layouts.admin')

@section('title', 'Pengaturan BPJS Ketenagakerjaan')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Pengaturan BPJS Ketenagakerjaan</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Pengaturan BPJS Ketenagakerjaan</h1>
            <p class="text-secondary-500 mt-1">Konfigurasi JHT, JKK, JKM, dan JP.</p>
        </div>
    </div>
@endsection

@section('content')
    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-4">
        <div class="stat-card">
            <p class="stat-card-label">Total Perusahaan</p>
            <p class="text-lg font-bold text-primary-600">{{ number_format($setting->total_company_rate, 2) }}%</p>
        </div>
        <div class="stat-card">
            <p class="stat-card-label">Total Karyawan</p>
            <p class="text-lg font-bold text-warning-600">{{ number_format($setting->total_employee_rate, 2) }}%</p>
        </div>
        <div class="stat-card">
            <p class="stat-card-label">Tingkat Risiko JKK</p>
            <p class="text-lg font-bold text-secondary-900">{{ $setting->jkk_risk_level_label }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-card-label">Batas Upah JP</p>
            <p class="text-lg font-bold text-secondary-900">{{ $setting->formatted_jp_max_salary }}</p>
        </div>
    </div>

    <form action="{{ route('bpjs-tk-settings.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            {{-- JHT (Jaminan Hari Tua) --}}
            <div class="card">
                <div class="card-header flex items-center justify-between">
                    <h3 class="card-title">JHT (Jaminan Hari Tua)</h3>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="jht_enabled" value="1" {{ $setting->jht_enabled ? 'checked' : '' }} class="rounded border-secondary-300 text-primary-600 focus:ring-primary-500">
                        <span class="text-sm text-secondary-600">Aktif</span>
                    </label>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-secondary-700 mb-1">Perusahaan (%)</label>
                            <input type="number" name="jht_company_rate" value="{{ $setting->jht_company_rate }}" step="0.01" class="input w-full">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-secondary-700 mb-1">Karyawan (%)</label>
                            <input type="number" name="jht_employee_rate" value="{{ $setting->jht_employee_rate }}" step="0.01" class="input w-full">
                        </div>
                    </div>
                    <p class="text-xs text-secondary-400 mt-2">Total: {{ number_format($setting->jht_total_rate, 2) }}% dari gaji</p>
                </div>
            </div>

            {{-- JKK (Jaminan Kecelakaan Kerja) --}}
            <div class="card">
                <div class="card-header flex items-center justify-between">
                    <h3 class="card-title">JKK (Jaminan Kecelakaan Kerja)</h3>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="jkk_enabled" value="1" {{ $setting->jkk_enabled ? 'checked' : '' }} class="rounded border-secondary-300 text-primary-600 focus:ring-primary-500">
                        <span class="text-sm text-secondary-600">Aktif</span>
                    </label>
                </div>
                <div class="card-body">
                    <div>
                        <label class="block text-sm font-medium text-secondary-700 mb-1">Tingkat Risiko</label>
                        <select name="jkk_risk_level" class="input w-full">
                            @foreach($jkkRiskOptions as $level => $option)
                                <option value="{{ $level }}" {{ $setting->jkk_risk_level === $level ? 'selected' : '' }}>
                                    {{ $option['label'] }} - {{ $option['rate'] }}%
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mt-3 p-3 bg-secondary-50 rounded-lg">
                        <p class="text-xs text-secondary-500 font-medium mb-1">Kategori Risiko:</p>
                        @foreach($jkkRiskOptions as $level => $option)
                            <p class="text-xs text-secondary-600">{{ $option['label'] }}: {{ $option['description'] }}</p>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- JKM (Jaminan Kematian) --}}
            <div class="card">
                <div class="card-header flex items-center justify-between">
                    <h3 class="card-title">JKM (Jaminan Kematian)</h3>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="jkm_enabled" value="1" {{ $setting->jkm_enabled ? 'checked' : '' }} class="rounded border-secondary-300 text-primary-600 focus:ring-primary-500">
                        <span class="text-sm text-secondary-600">Aktif</span>
                    </label>
                </div>
                <div class="card-body">
                    <div>
                        <label class="block text-sm font-medium text-secondary-700 mb-1">Tarif Perusahaan (%)</label>
                        <input type="number" name="jkm_rate" value="{{ $setting->jkm_rate }}" step="0.01" class="input w-full">
                    </div>
                    <p class="text-xs text-secondary-400 mt-2">Seluruhnya ditanggung perusahaan</p>
                </div>
            </div>

            {{-- JP (Jaminan Pensiun) --}}
            <div class="card">
                <div class="card-header flex items-center justify-between">
                    <h3 class="card-title">JP (Jaminan Pensiun)</h3>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="jp_enabled" value="1" {{ $setting->jp_enabled ? 'checked' : '' }} class="rounded border-secondary-300 text-primary-600 focus:ring-primary-500">
                        <span class="text-sm text-secondary-600">Aktif</span>
                    </label>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-secondary-700 mb-1">Perusahaan (%)</label>
                            <input type="number" name="jp_company_rate" value="{{ $setting->jp_company_rate }}" step="0.01" class="input w-full">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-secondary-700 mb-1">Karyawan (%)</label>
                            <input type="number" name="jp_employee_rate" value="{{ $setting->jp_employee_rate }}" step="0.01" class="input w-full">
                        </div>
                    </div>
                    <div class="mt-3" x-data="currencyInput({{ $setting->jp_max_salary ?? 0 }})">
                        <label class="block text-sm font-medium text-secondary-700 mb-1">Batas Maksimum Upah</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" x-model="display" @input="updateValue($event)"
                                   class="input" placeholder="0" inputmode="numeric">
                            <input type="hidden" name="jp_max_salary" :value="value">
                        </div>
                        <p class="text-xs text-secondary-400 mt-1">Upah di atas batas ini tetap dihitung berdasarkan batas maksimum</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Additional Settings --}}
        <div class="card mt-4">
            <div class="card-header">
                <h3 class="card-title">Pengaturan Tambahan</h3>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-secondary-700 mb-1">Tahun Efektif</label>
                        <input type="number" name="effective_year" value="{{ $setting->effective_year }}" min="2020" max="2030" class="input w-full">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-secondary-700 mb-1">Catatan</label>
                        <textarea name="notes" rows="2" class="input w-full">{{ $setting->notes }}</textarea>
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
            <h3 class="card-title">Kalkulator BPJS TK</h3>
        </div>
        <div class="card-body">
            <div class="flex flex-wrap items-end gap-3">
                <div class="w-48">
                    <label class="block text-sm font-medium text-secondary-700 mb-1">Gaji Pokok (Rp)</label>
                    <input type="number" id="calcSalary" value="5000000" class="input w-full">
                </div>
                <button type="button" onclick="calculateBpjsTk()" class="btn btn-primary btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    Hitung
                </button>
            </div>
            <div id="calcResult" class="mt-4 hidden">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <div class="p-3 bg-primary-50 rounded-lg">
                        <p class="text-xs text-primary-600 font-medium">JHT Perusahaan</p>
                        <p class="text-lg font-bold text-primary-700" id="resJhtCompany">-</p>
                    </div>
                    <div class="p-3 bg-warning-50 rounded-lg">
                        <p class="text-xs text-warning-600 font-medium">JHT Karyawan</p>
                        <p class="text-lg font-bold text-warning-700" id="resJhtEmployee">-</p>
                    </div>
                    <div class="p-3 bg-primary-50 rounded-lg">
                        <p class="text-xs text-primary-600 font-medium">JKK</p>
                        <p class="text-lg font-bold text-primary-700" id="resJkk">-</p>
                    </div>
                    <div class="p-3 bg-primary-50 rounded-lg">
                        <p class="text-xs text-primary-600 font-medium">JKM</p>
                        <p class="text-lg font-bold text-primary-700" id="resJkm">-</p>
                    </div>
                    <div class="p-3 bg-primary-50 rounded-lg">
                        <p class="text-xs text-primary-600 font-medium">JP Perusahaan</p>
                        <p class="text-lg font-bold text-primary-700" id="resJpCompany">-</p>
                    </div>
                    <div class="p-3 bg-warning-50 rounded-lg">
                        <p class="text-xs text-warning-600 font-medium">JP Karyawan</p>
                        <p class="text-lg font-bold text-warning-700" id="resJpEmployee">-</p>
                    </div>
                    <div class="p-3 bg-success-50 rounded-lg">
                        <p class="text-xs text-success-600 font-medium">Total Perusahaan</p>
                        <p class="text-lg font-bold text-success-700" id="resTotalCompany">-</p>
                    </div>
                    <div class="p-3 bg-danger-50 rounded-lg">
                        <p class="text-xs text-danger-600 font-medium">Total Karyawan</p>
                        <p class="text-lg font-bold text-danger-700" id="resTotalEmployee">-</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function formatRupiah(num) {
            return 'Rp ' + num.toLocaleString('id-ID');
        }

        function calculateBpjsTk() {
            const salary = parseFloat(document.getElementById('calcSalary').value) || 0;

            fetch('{{ route("bpjs-tk-settings.calculate") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ salary: salary })
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('calcResult').classList.remove('hidden');
                document.getElementById('resJhtCompany').textContent = formatRupiah(data.company.jht);
                document.getElementById('resJhtEmployee').textContent = formatRupiah(data.employee.jht);
                document.getElementById('resJkk').textContent = formatRupiah(data.company.jkk);
                document.getElementById('resJkm').textContent = formatRupiah(data.company.jkm);
                document.getElementById('resJpCompany').textContent = formatRupiah(data.company.jp);
                document.getElementById('resJpEmployee').textContent = formatRupiah(data.employee.jp);
                document.getElementById('resTotalCompany').textContent = formatRupiah(data.total.company);
                document.getElementById('resTotalEmployee').textContent = formatRupiah(data.total.employee);
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
    </script>
@endsection
