@extends('layouts.admin')

@section('title', 'SPT Tahunan 1721')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">SPT Tahunan PPh 21</h1>
            <p class="text-secondary-600 mt-1">Formulir 1721 - Rekapitulasi tahunan pajak penghasilan</p>
        </div>
        <a href="{{ route('spt-1721.create') }}" class="btn btn-primary">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Buat SPT Baru
        </a>
    </div>

    {{-- Filter --}}
    <div class="card">
        <div class="card-body">
            <form method="GET" class="flex flex-wrap gap-4">
                <div class="w-40">
                    <label class="block text-sm font-medium text-secondary-700 mb-1">Tahun Pajak</label>
                    <select name="year" class="input w-full" onchange="this.form.submit()">
                        <option value="">Semua Tahun</option>
                        @foreach($years as $year)
                            <option value="{{ $year }}" {{ request('year') == $year ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="w-40">
                    <label class="block text-sm font-medium text-secondary-700 mb-1">Status</label>
                    <select name="status" class="input w-full" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="calculated" {{ request('status') == 'calculated' ? 'selected' : '' }}>Sudah Dihitung</option>
                        <option value="submitted" {{ request('status') == 'submitted' ? 'selected' : '' }}>Sudah Disetor</option>
                        <option value="reported" {{ request('status') == 'reported' ? 'selected' : '' }}>Sudah Dilaporkan</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-secondary-50 border-b border-secondary-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-secondary-600 uppercase">No. SPT</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-secondary-600 uppercase">Tahun Pajak</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-secondary-600 uppercase">Jenis</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-secondary-600 uppercase">Jml Karyawan</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-secondary-600 uppercase">Total PPh 21</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-secondary-600 uppercase">Status</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-secondary-600 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-secondary-200">
                    @forelse($spts as $spt)
                        <tr class="hover:bg-secondary-50">
                            <td class="px-4 py-3">
                                <span class="font-mono text-sm">{{ $spt->spt_number }}</span>
                            </td>
                            <td class="px-4 py-3 font-semibold">{{ $spt->tax_year }}</td>
                            <td class="px-4 py-3">
                                @if($spt->spt_type === 'pembetulan')
                                    <span class="text-warning-600">Pembetulan ke-{{ $spt->pembetulan_ke }}</span>
                                @else
                                    <span class="text-secondary-600">Normal</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">{{ number_format($spt->total_employees) }}</td>
                            <td class="px-4 py-3 text-right font-mono">{{ $spt->formatted_total_pph21_terutang }}</td>
                            <td class="px-4 py-3 text-center">
                                <x-badge :type="$spt->status_color">{{ $spt->status_label }}</x-badge>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <a href="{{ route('spt-1721.show', $spt) }}" class="btn btn-sm btn-ghost text-primary-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-secondary-500">
                                <svg class="w-12 h-12 mx-auto mb-4 text-secondary-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <p>Belum ada data SPT Tahunan.</p>
                                <a href="{{ route('spt-1721.create') }}" class="btn btn-primary mt-4">Buat SPT Baru</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($spts->hasPages())
            <div class="px-4 py-3 border-t border-secondary-200">
                {{ $spts->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
