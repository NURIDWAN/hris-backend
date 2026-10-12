@extends('layouts.portal')

@section('title', 'Slip Gaji')

@section('breadcrumb')
    <a href="{{ route('portal.dashboard') }}" class="text-slate-500 hover:text-primary-600">Dashboard</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Slip Gaji</span>
@endsection

@section('header')
    <div>
        <h1 class="text-2xl font-bold text-secondary-900">Slip Gaji</h1>
        <p class="text-secondary-500 mt-1">Lihat riwayat slip gaji Anda.</p>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-body p-0">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-secondary-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-medium text-secondary-500">Periode</th>
                            <th class="px-4 py-3 text-right text-sm font-medium text-secondary-500">Gaji Kotor</th>
                            <th class="px-4 py-3 text-right text-sm font-medium text-secondary-500">Potongan</th>
                            <th class="px-4 py-3 text-right text-sm font-medium text-secondary-500">Gaji Bersih</th>
                            <th class="px-4 py-3 text-center text-sm font-medium text-secondary-500">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-secondary-100">
                        @forelse($payslips as $payslip)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-secondary-900">
                                        {{ $payslip->payroll->period_start->format('M Y') }}
                                    </div>
                                    <div class="text-sm text-secondary-500">
                                        {{ $payslip->payroll->period_start->format('d M') }} - {{ $payslip->payroll->period_end->format('d M Y') }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    Rp {{ number_format($payslip->gross_salary, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-right text-danger-600">
                                    -Rp {{ number_format($payslip->total_deductions, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-secondary-900">
                                    Rp {{ number_format($payslip->net_salary, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('portal.payslips.show', $payslip) }}" class="text-primary-600 hover:text-primary-700 text-sm">
                                            Detail
                                        </a>
                                        <span class="text-secondary-300">|</span>
                                        <a href="{{ route('portal.payslips.pdf', $payslip) }}" class="text-primary-600 hover:text-primary-700 text-sm">
                                            PDF
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-secondary-500">
                                    Belum ada slip gaji
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($payslips->hasPages())
            <div class="card-footer">
                {{ $payslips->links() }}
            </div>
        @endif
    </div>
@endsection
