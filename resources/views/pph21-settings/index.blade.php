@extends('layouts.admin')

@section('title', 'Pengaturan PPh 21')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Pengaturan PPh 21</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Pengaturan PPh 21</h1>
            <p class="text-secondary-500 mt-1">Konfigurasi PTKP dan tarif pajak penghasilan.</p>
        </div>
    </div>
@endsection

@section('content')
    {{-- General Settings --}}
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">Pengaturan Umum PPh 21</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('pph21-settings.update-setting') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-secondary-700 mb-1">Metode Perhitungan</label>
                        <select name="use_ter" class="input w-full">
                            <option value="1" {{ $setting->use_ter ? 'selected' : '' }}>TER (Tarif Efektif Rata-rata) - 2024</option>
                            <option value="0" {{ !$setting->use_ter ? 'selected' : '' }}>Tarif Progresif (Tradisional)</option>
                        </select>
                        <p class="text-xs text-secondary-400 mt-1">TER berlaku mulai Januari 2024</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-secondary-700 mb-1">Beban Pajak</label>
                        <select name="is_gross_up" class="input w-full">
                            <option value="0" {{ !$setting->is_gross_up ? 'selected' : '' }}>Ditanggung Karyawan</option>
                            <option value="1" {{ $setting->is_gross_up ? 'selected' : '' }}>Ditanggung Perusahaan (Gross Up)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-secondary-700 mb-1">Diskon NPWP (%)</label>
                        <input type="number" name="npwp_discount_rate" value="{{ $setting->npwp_discount_rate }}" step="0.01" class="input w-full">
                        <p class="text-xs text-secondary-400 mt-1">Potongan tarif bagi yang memiliki NPWP</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-secondary-700 mb-1">Tahun Efektif</label>
                        <input type="number" name="effective_year" value="{{ $setting->effective_year }}" min="2020" max="2030" class="input w-full">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-secondary-700 mb-1">Catatan</label>
                        <textarea name="notes" rows="2" class="input w-full">{{ $setting->notes }}</textarea>
                    </div>
                </div>

                <div class="flex justify-end mt-4">
                    <button type="submit" class="btn btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Simpan Pengaturan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- PTKP Settings --}}
    <div class="card mb-4">
        <div class="card-header flex items-center justify-between">
            <h3 class="card-title">PTKP (Penghasilan Tidak Kena Pajak) - {{ $currentYear }}</h3>
            @if($ptkpSettings->isEmpty())
                <form action="{{ route('pph21-settings.initialize-ptkp') }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="year" value="{{ $currentYear }}">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        Inisialisasi PTKP {{ $currentYear }}
                    </button>
                </form>
            @endif
        </div>
        <x-table>
            <x-slot name="header">
                <th>Kode Status</th>
                <th>Keterangan</th>
                <th class="text-right">PTKP / Tahun</th>
                <th class="text-right">PTKP / Bulan</th>
                <th class="text-right">Aksi</th>
            </x-slot>

            @forelse($ptkpSettings as $ptkp)
                <tr>
                    <td>
                        <span class="font-medium text-secondary-900">{{ $ptkp->status_code }}</span>
                    </td>
                    <td class="text-secondary-600">{{ $ptkp->description }}</td>
                    <td class="text-right font-medium text-secondary-900">{{ $ptkp->formatted_annual_amount }}</td>
                    <td class="text-right text-secondary-600">{{ $ptkp->formatted_monthly_amount }}</td>
                    <td class="text-right">
                        <button
                            type="button"
                            onclick="openEditPtkpModal({{ $ptkp->id }}, '{{ $ptkp->status_code }}', {{ $ptkp->annual_amount }})"
                            class="p-1.5 text-secondary-400 hover:text-primary-600 hover:bg-primary-50 rounded-md transition-colors"
                            title="Edit"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center py-8">
                        <p class="text-secondary-500">Belum ada data PTKP. Klik tombol "Inisialisasi PTKP" untuk mengisi data default.</p>
                    </td>
                </tr>
            @endforelse
        </x-table>
    </div>

    {{-- TER Rates --}}
    <div class="card mb-4" x-data="{ activeTab: 'A' }">
        <div class="card-header flex items-center justify-between">
            <div>
                <h3 class="card-title">Tarif Efektif Rata-rata (TER) - {{ $currentYear }}</h3>
                <p class="text-xs text-secondary-400 mt-1">Sesuai PP 58 Tahun 2023</p>
            </div>
            @if($terRates->isEmpty())
                <form action="{{ route('pph21-settings.initialize-ter-rates') }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="year" value="{{ $currentYear }}">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        Inisialisasi TER {{ $currentYear }}
                    </button>
                </form>
            @endif
        </div>

        @if($terRates->isNotEmpty())
            {{-- Category Tabs --}}
            <div class="border-b px-4 pt-2 flex gap-1">
                @foreach(['A' => 'Kategori A (TK/0, TK/1, K/0)', 'B' => 'Kategori B (TK/2, TK/3, K/1, K/2)', 'C' => 'Kategori C (K/3, K/I/0-3)'] as $cat => $label)
                    <button
                        type="button"
                        @click="activeTab = '{{ $cat }}'"
                        :class="activeTab === '{{ $cat }}' ? 'border-primary-500 text-primary-600 font-medium' : 'border-transparent text-secondary-500 hover:text-secondary-700'"
                        class="px-4 py-2 text-sm border-b-2 transition-colors whitespace-nowrap"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            {{-- Tables per Category --}}
            @foreach(['A', 'B', 'C'] as $cat)
                <div x-show="activeTab === '{{ $cat }}'" x-cloak>
                    <x-table>
                        <x-slot name="header">
                            <th class="w-12">No</th>
                            <th>Penghasilan Bruto Bulanan</th>
                            <th class="text-right">Tarif (%)</th>
                            <th class="text-right w-16">Aksi</th>
                        </x-slot>

                        @forelse($terRates->get($cat, collect()) as $index => $ter)
                            <tr>
                                <td class="text-secondary-500">{{ $index + 1 }}</td>
                                <td class="font-medium text-secondary-900">{{ $ter->range_label }}</td>
                                <td class="text-right">
                                    <span class="font-bold text-primary-600">{{ number_format($ter->rate, 2) }}%</span>
                                </td>
                                <td class="text-right">
                                    <button
                                        type="button"
                                        onclick="openEditTerModal({{ $ter->id }}, '{{ $ter->range_label }}', {{ $ter->rate }})"
                                        class="p-1.5 text-secondary-400 hover:text-primary-600 hover:bg-primary-50 rounded-md transition-colors"
                                        title="Edit"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-8">
                                    <p class="text-secondary-500">Tidak ada data.</p>
                                </td>
                            </tr>
                        @endforelse
                    </x-table>
                </div>
            @endforeach
        @else
            <div class="card-body text-center py-8">
                <p class="text-secondary-500">Belum ada data TER. Klik tombol "Inisialisasi TER" untuk mengisi data default PP 58/2023.</p>
            </div>
        @endif
    </div>

    {{-- PPh 21 Progressive Rates --}}
    <div class="card">
        <div class="card-header flex items-center justify-between">
            <h3 class="card-title">Tarif Progresif PPh 21 - {{ $currentYear }}</h3>
            @if($pph21Rates->isEmpty())
                <form action="{{ route('pph21-settings.initialize-rates') }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="year" value="{{ $currentYear }}">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        Inisialisasi Tarif {{ $currentYear }}
                    </button>
                </form>
            @endif
        </div>
        <x-table>
            <x-slot name="header">
                <th>Lapisan PKP</th>
                <th class="text-right">Tarif (%)</th>
                <th class="text-right">Aksi</th>
            </x-slot>

            @forelse($pph21Rates as $rate)
                <tr>
                    <td>
                        <span class="font-medium text-secondary-900">{{ $rate->range_label }}</span>
                    </td>
                    <td class="text-right">
                        <span class="font-bold text-primary-600">{{ number_format($rate->rate, 0) }}%</span>
                    </td>
                    <td class="text-right">
                        <button
                            type="button"
                            onclick="openEditRateModal({{ $rate->id }}, '{{ $rate->range_label }}', {{ $rate->rate }})"
                            class="p-1.5 text-secondary-400 hover:text-primary-600 hover:bg-primary-50 rounded-md transition-colors"
                            title="Edit"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center py-8">
                        <p class="text-secondary-500">Belum ada data tarif. Klik tombol "Inisialisasi Tarif" untuk mengisi data default.</p>
                    </td>
                </tr>
            @endforelse
        </x-table>
    </div>

    {{-- Edit PTKP Modal --}}
    <div id="editPtkpModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" onclick="closeEditPtkpModal()"></div>
            <div class="relative bg-white rounded-lg shadow-xl w-full max-w-md">
                <div class="p-4 border-b">
                    <h3 class="text-lg font-semibold text-secondary-900">Edit PTKP</h3>
                </div>
                <form id="editPtkpForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="p-4">
                        <p class="text-sm text-secondary-600 mb-3">Status: <span id="editPtkpStatus" class="font-medium"></span></p>
                        <div>
                            <label class="block text-sm font-medium text-secondary-700 mb-1">PTKP per Tahun (Rp)</label>
                            <input type="number" name="annual_amount" id="editPtkpAmount" class="input w-full" required>
                        </div>
                    </div>
                    <div class="p-4 border-t flex justify-end gap-2">
                        <button type="button" onclick="closeEditPtkpModal()" class="btn btn-ghost">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit Rate Modal --}}
    <div id="editRateModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" onclick="closeEditRateModal()"></div>
            <div class="relative bg-white rounded-lg shadow-xl w-full max-w-md">
                <div class="p-4 border-b">
                    <h3 class="text-lg font-semibold text-secondary-900">Edit Tarif PPh 21</h3>
                </div>
                <form id="editRateForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="p-4">
                        <p class="text-sm text-secondary-600 mb-3">Lapisan: <span id="editRateRange" class="font-medium"></span></p>
                        <div>
                            <label class="block text-sm font-medium text-secondary-700 mb-1">Tarif (%)</label>
                            <input type="number" name="rate" id="editRateValue" step="0.01" class="input w-full" required>
                        </div>
                    </div>
                    <div class="p-4 border-t flex justify-end gap-2">
                        <button type="button" onclick="closeEditRateModal()" class="btn btn-ghost">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit TER Modal --}}
    <div id="editTerModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" onclick="closeEditTerModal()"></div>
            <div class="relative bg-white rounded-lg shadow-xl w-full max-w-md">
                <div class="p-4 border-b">
                    <h3 class="text-lg font-semibold text-secondary-900">Edit Tarif TER</h3>
                </div>
                <form id="editTerForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="p-4">
                        <p class="text-sm text-secondary-600 mb-3">Bracket: <span id="editTerRange" class="font-medium"></span></p>
                        <div>
                            <label class="block text-sm font-medium text-secondary-700 mb-1">Tarif (%)</label>
                            <input type="number" name="rate" id="editTerValue" step="0.01" min="0" max="100" class="input w-full" required>
                        </div>
                    </div>
                    <div class="p-4 border-t flex justify-end gap-2">
                        <button type="button" onclick="closeEditTerModal()" class="btn btn-ghost">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openEditTerModal(id, range, rate) {
            document.getElementById('editTerForm').action = '/pph21-settings/ter-rates/' + id;
            document.getElementById('editTerRange').textContent = range;
            document.getElementById('editTerValue').value = rate;
            document.getElementById('editTerModal').classList.remove('hidden');
        }

        function closeEditTerModal() {
            document.getElementById('editTerModal').classList.add('hidden');
        }

        function openEditPtkpModal(id, status, amount) {
            document.getElementById('editPtkpForm').action = '/pph21-settings/ptkp/' + id;
            document.getElementById('editPtkpStatus').textContent = status;
            document.getElementById('editPtkpAmount').value = amount;
            document.getElementById('editPtkpModal').classList.remove('hidden');
        }

        function closeEditPtkpModal() {
            document.getElementById('editPtkpModal').classList.add('hidden');
        }

        function openEditRateModal(id, range, rate) {
            document.getElementById('editRateForm').action = '/pph21-settings/rates/' + id;
            document.getElementById('editRateRange').textContent = range;
            document.getElementById('editRateValue').value = rate;
            document.getElementById('editRateModal').classList.remove('hidden');
        }

        function closeEditRateModal() {
            document.getElementById('editRateModal').classList.add('hidden');
        }
    </script>
@endsection
