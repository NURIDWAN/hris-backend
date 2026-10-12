@extends('layouts.portal')

@section('title', 'Dashboard')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Dashboard</span>
@endsection

@section('header')
    <div>
        <h1 class="text-2xl font-bold text-secondary-900">Selamat Datang, {{ $employee->first_name }}!</h1>
        <p class="text-secondary-500 mt-1">Berikut ringkasan informasi Anda.</p>
    </div>
@endsection

@section('content')
    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Employee Info Card --}}
        <div class="card lg:col-span-1">
            <div class="card-body text-center">
                <div class="w-20 h-20 mx-auto rounded-full bg-primary-100 flex items-center justify-center mb-4">
                    @if($employee->photo)
                        <img src="{{ asset('storage/' . $employee->photo) }}" alt="{{ $employee->full_name }}" class="w-20 h-20 rounded-full object-cover">
                    @else
                        <span class="text-3xl font-bold text-primary-600">{{ substr($employee->first_name, 0, 1) }}{{ substr($employee->last_name, 0, 1) }}</span>
                    @endif
                </div>
                <h3 class="text-lg font-semibold text-secondary-900">{{ $employee->full_name }}</h3>
                <p class="text-secondary-500">{{ $employee->employee_id }}</p>
                <div class="mt-4 space-y-2 text-sm text-left">
                    <div class="flex justify-between">
                        <span class="text-secondary-500">Departemen</span>
                        <span class="font-medium">{{ $employee->department->name ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-secondary-500">Jabatan</span>
                        <span class="font-medium">{{ $employee->position->name ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-secondary-500">Masa Kerja</span>
                        <span class="font-medium">{{ $employee->years_of_service }} tahun</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="lg:col-span-2 grid gap-6 sm:grid-cols-2">
            {{-- Today's Attendance --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Absensi Hari Ini</h3>
                </div>
                <div class="card-body">
                    @if($todayAttendance)
                        <div class="space-y-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-success-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                                </div>
                                <div>
                                    <div class="text-sm text-secondary-500">Clock In</div>
                                    <div class="font-semibold">{{ $todayAttendance->formatted_clock_in }}</div>
                                </div>
                            </div>
                            @if($todayAttendance->clock_out)
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-danger-100 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-danger-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                    </div>
                                    <div>
                                        <div class="text-sm text-secondary-500">Clock Out</div>
                                        <div class="font-semibold">{{ $todayAttendance->formatted_clock_out }}</div>
                                    </div>
                                </div>
                            @else
                                <form action="{{ route('portal.attendance.clock-out') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-secondary w-full">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                        Clock Out
                                    </button>
                                </form>
                            @endif
                        </div>
                    @else
                        <div class="text-center py-4">
                            <p class="text-secondary-500 mb-4">Anda belum clock in hari ini</p>
                            <form action="{{ route('portal.attendance.clock-in') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                                    Clock In
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Leave Balance --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Sisa Cuti</h3>
                </div>
                <div class="card-body">
                    @if($leaveBalances->count() > 0)
                        <div class="space-y-3">
                            @foreach($leaveBalances as $balance)
                                <div class="flex items-center justify-between">
                                    <span class="text-secondary-600">{{ $balance->leaveType->name ?? 'Cuti' }}</span>
                                    <span class="font-semibold text-primary-600">{{ $balance->remaining_days }} hari</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-secondary-500 text-center py-4">Belum ada data saldo cuti</p>
                    @endif
                    <a href="{{ route('portal.leave.create') }}" class="btn btn-ghost w-full mt-4">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Ajukan Cuti
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Payslips --}}
    <div class="card mt-6">
        <div class="card-header flex items-center justify-between">
            <h3 class="card-title">Slip Gaji Terbaru</h3>
            <a href="{{ route('portal.payslips.index') }}" class="text-sm text-primary-600 hover:text-primary-700">Lihat Semua</a>
        </div>
        <div class="card-body">
            @if($recentPayslips->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-secondary-200">
                                <th class="py-3 text-left text-sm font-medium text-secondary-500">Periode</th>
                                <th class="py-3 text-right text-sm font-medium text-secondary-500">Gaji Bersih</th>
                                <th class="py-3 text-center text-sm font-medium text-secondary-500">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentPayslips as $payslip)
                                <tr class="border-b border-secondary-100">
                                    <td class="py-3">{{ $payslip->payroll->period_start->format('M Y') }}</td>
                                    <td class="py-3 text-right font-medium">Rp {{ number_format($payslip->net_salary, 0, ',', '.') }}</td>
                                    <td class="py-3 text-center">
                                        <a href="{{ route('portal.payslips.show', $payslip) }}" class="text-primary-600 hover:text-primary-700 text-sm">Detail</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-secondary-500 text-center py-4">Belum ada slip gaji</p>
            @endif
        </div>
    </div>
@endsection
