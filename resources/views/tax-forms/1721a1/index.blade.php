@extends('layouts.admin')

@section('title', 'Bukti Potong 1721-A1')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Bukti Potong 1721-A1</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Bukti Potong 1721-A1</h1>
            <p class="text-secondary-500 mt-1">Kelola bukti pemotongan pajak PPh 21 tahunan</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('tax-forms.1721a1.create') }}" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Buat Baru
            </a>
        </div>
    </div>
@endsection

@section('content')
    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body-sm">
            <form action="{{ route('tax-forms.1721a1.index') }}" method="GET" class="flex flex-wrap items-end gap-3">
                <div class="w-32">
                    <label class="block text-xs font-medium text-secondary-500 mb-1">Tahun Pajak</label>
                    <select name="year" class="input w-full">
                        <option value="">Semua</option>
                        @foreach($years as $y)
                            <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-48">
                    <label class="block text-xs font-medium text-secondary-500 mb-1">Karyawan</label>
                    <select name="employee_id" class="input w-full">
                        <option value="">Semua Karyawan</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>
                                {{ $emp->full_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-secondary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    Filter
                </button>
                @if(request()->anyFilled(['year', 'employee_id']))
                    <a href="{{ route('tax-forms.1721a1.index') }}" class="btn btn-ghost">Reset</a>
                @endif
            </form>
        </div>
    </div>

    {{-- Bulk Actions --}}
    <div class="card mb-4">
        <div class="card-body-sm">
            <form action="{{ route('tax-forms.1721a1.generate-bulk') }}" method="POST" class="flex flex-wrap items-end gap-3">
                @csrf
                <div class="w-32">
                    <label class="block text-xs font-medium text-secondary-500 mb-1">Tahun Pajak</label>
                    <select name="tax_year" class="input w-full" required>
                        @for($y = now()->year; $y >= now()->year - 3; $y--)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <button type="submit" class="btn btn-secondary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Generate Semua Karyawan
                </button>
                @if(request('year'))
                    <a href="{{ route('tax-forms.1721a1.pdf-bulk', ['year' => request('year')]) }}" class="btn btn-ghost">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Download Semua PDF
                    </a>
                @endif
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card">
        <x-table>
            <x-slot name="header">
                <th>No. Form</th>
                <th>Karyawan</th>
                <th>NPWP</th>
                <th>Tahun</th>
                <th class="text-right">Penghasilan Bruto</th>
                <th class="text-right">PPh 21</th>
                <th>Dibuat</th>
                <th></th>
            </x-slot>

            @forelse($taxForms as $taxForm)
                <tr>
                    <td class="font-mono text-sm">{{ $taxForm->form_number }}</td>
                    <td>
                        <div class="font-medium">{{ $taxForm->employee_name }}</div>
                        <div class="text-xs text-secondary-500">{{ $taxForm->ptkp_status }}</div>
                    </td>
                    <td class="font-mono text-sm">{{ $taxForm->employee_npwp ?? '-' }}</td>
                    <td>{{ $taxForm->tax_year }}</td>
                    <td class="text-right font-mono">Rp {{ number_format($taxForm->gross_salary, 0, ',', '.') }}</td>
                    <td class="text-right font-mono text-danger-600">Rp {{ number_format($taxForm->total_pph21_withheld, 0, ',', '.') }}</td>
                    <td class="text-sm text-secondary-500">
                        {{ $taxForm->generated_at?->format('d M Y') ?? '-' }}
                    </td>
                    <td>
                        <div class="flex items-center gap-1 justify-end">
                            <a href="{{ route('tax-forms.1721a1.show', $taxForm) }}" class="btn btn-ghost btn-sm" title="Lihat">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </a>
                            <a href="{{ route('tax-forms.1721a1.pdf', $taxForm) }}" class="btn btn-ghost btn-sm" title="Download PDF">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </a>
                            <button type="button"
                                    @click="$dispatch('confirm-dialog', {
                                        title: 'Hapus Bukti Potong',
                                        message: 'Apakah Anda yakin ingin menghapus bukti potong 1721-A1 untuk {{ $taxForm->employee_name }}?',
                                        confirmText: 'Ya, Hapus',
                                        type: 'danger',
                                        formAction: '{{ route('tax-forms.1721a1.destroy', $taxForm) }}'
                                    })"
                                    class="btn btn-ghost btn-sm text-danger-600" title="Hapus">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center py-8 text-secondary-500">
                        <svg class="w-12 h-12 mx-auto mb-3 text-secondary-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <p>Belum ada bukti potong 1721-A1</p>
                        <a href="{{ route('tax-forms.1721a1.create') }}" class="btn btn-primary btn-sm mt-3">Buat Baru</a>
                    </td>
                </tr>
            @endforelse
        </x-table>

        @if($taxForms->hasPages())
            <div class="card-body-sm border-t border-secondary-100">
                {{ $taxForms->links() }}
            </div>
        @endif
    </div>
@endsection
