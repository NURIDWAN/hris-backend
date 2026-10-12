@extends('layouts.admin')

@section('title', 'Dashboard')

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Dashboard</h1>
            <p class="text-secondary-500 mt-1">Selamat datang kembali! Berikut ringkasan data perusahaan Anda.</p>
        </div>
    </div>
@endsection

@section('content')
    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        {{-- Total Karyawan --}}
        <div class="stat-card">
            <div class="flex items-start justify-between">
                <div>
                    <p class="stat-card-label">Total Karyawan</p>
                    <p class="stat-card-value">{{ $stats['total_employees'] }}</p>
                </div>
                <div class="stat-card-icon bg-primary-100">
                    <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
            </div>
            @if($stats['new_employees_this_month'] > 0)
                <div class="mt-4">
                    <span class="stat-card-change stat-card-change-up">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                        +{{ $stats['new_employees_this_month'] }} bulan ini
                    </span>
                </div>
            @endif
        </div>

        {{-- Hadir Hari Ini --}}
        <div class="stat-card">
            <div class="flex items-start justify-between">
                <div>
                    <p class="stat-card-label">Hadir Hari Ini</p>
                    <p class="stat-card-value">{{ $stats['present_today'] }}</p>
                </div>
                <div class="stat-card-icon bg-success-50">
                    <svg class="w-6 h-6 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="mt-4">
                <span class="text-sm text-secondary-500">{{ $stats['attendance_percentage'] }}% kehadiran</span>
            </div>
        </div>

        {{-- Cuti Pending --}}
        <div class="stat-card">
            <div class="flex items-start justify-between">
                <div>
                    <p class="stat-card-label">Pengajuan Cuti</p>
                    <p class="stat-card-value">{{ $stats['pending_leaves'] }}</p>
                </div>
                <div class="stat-card-icon bg-warning-50">
                    <svg class="w-6 h-6 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            @if($stats['pending_leaves'] > 0)
                <div class="mt-4">
                    <span class="text-sm text-warning-600 font-medium">Perlu approval</span>
                </div>
            @endif
        </div>

        {{-- Total Gaji --}}
        <div class="stat-card">
            <div class="flex items-start justify-between">
                <div>
                    <p class="stat-card-label">Total Gaji Bulan Ini</p>
                    <p class="stat-card-value text-2xl">Rp {{ number_format($stats['total_payroll_this_month'] / 1000000, 0, ',', '.') }}jt</p>
                </div>
                <div class="stat-card-icon bg-accent-50">
                    <svg class="w-6 h-6 text-accent-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Attendance Chart --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Trend Kehadiran (14 Hari)</h3>
            </div>
            <div class="card-body">
                <div style="height: 280px;">
                    <canvas id="attendanceChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Payroll Trend Chart --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Trend Payroll (6 Bulan)</h3>
            </div>
            <div class="card-body">
                <div style="height: 280px;">
                    <canvas id="payrollChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Pie Charts Row --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        {{-- Employee by Department --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Karyawan per Departemen</h3>
            </div>
            <div class="card-body">
                @if(count($analytics['employee_by_department']['labels']) > 0)
                    <div style="height: 220px;">
                        <canvas id="departmentChart"></canvas>
                    </div>
                @else
                    <div class="text-center py-8 text-secondary-500">
                        <p class="text-sm">Belum ada data departemen</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Employee Status --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Status Karyawan</h3>
            </div>
            <div class="card-body">
                @if(count($analytics['employee_status']['labels']) > 0)
                    <div style="height: 220px;">
                        <canvas id="statusChart"></canvas>
                    </div>
                @else
                    <div class="text-center py-8 text-secondary-500">
                        <p class="text-sm">Belum ada data karyawan</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Leave Statistics --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Statistik Cuti Tahun Ini</h3>
            </div>
            <div class="card-body">
                @if(count($analytics['leave_statistics']['labels']) > 0)
                    <div style="height: 220px;">
                        <canvas id="leaveChart"></canvas>
                    </div>
                @else
                    <div class="text-center py-8 text-secondary-500">
                        <p class="text-sm">Belum ada data cuti</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Main Content Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Column (2/3) --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Attendance Overview --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Kehadiran Hari Ini</h3>
                    <a href="{{ route('attendances.index') }}" class="text-sm text-primary-600 hover:text-primary-700 font-medium">Lihat semua</a>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-4 gap-4 mb-6">
                        <div class="text-center p-4 bg-success-50 rounded-xl">
                            <p class="text-2xl font-bold text-success-600">{{ $attendanceToday['present'] }}</p>
                            <p class="text-sm text-secondary-600">Hadir</p>
                        </div>
                        <div class="text-center p-4 bg-warning-50 rounded-xl">
                            <p class="text-2xl font-bold text-warning-600">{{ $attendanceToday['late'] }}</p>
                            <p class="text-sm text-secondary-600">Terlambat</p>
                        </div>
                        <div class="text-center p-4 bg-info-50 rounded-xl">
                            <p class="text-2xl font-bold text-info-600">{{ $attendanceToday['on_leave'] }}</p>
                            <p class="text-sm text-secondary-600">Cuti</p>
                        </div>
                        <div class="text-center p-4 bg-danger-50 rounded-xl">
                            <p class="text-2xl font-bold text-danger-600">{{ $attendanceToday['absent'] }}</p>
                            <p class="text-sm text-secondary-600">Alpha</p>
                        </div>
                    </div>

                    {{-- Recent Clock-ins --}}
                    @if($recentClockIns->count() > 0)
                        <h4 class="font-semibold text-secondary-900 mb-3">Clock-in Terbaru</h4>
                        <div class="space-y-3">
                            @foreach($recentClockIns as $attendance)
                            <div class="flex items-center justify-between p-3 bg-secondary-50 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center text-primary-600 font-semibold">
                                        {{ substr($attendance->employee->first_name ?? 'U', 0, 1) }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-secondary-900">{{ $attendance->employee->full_name ?? 'Unknown' }}</p>
                                        <p class="text-sm text-secondary-500">{{ $attendance->employee->department->name ?? '-' }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="font-medium text-secondary-900">{{ $attendance->clock_in?->format('H:i') }}</p>
                                    @if($attendance->clock_in_status === 'on_time')
                                        <x-badge type="success">Tepat Waktu</x-badge>
                                    @elseif($attendance->clock_in_status === 'late')
                                        <x-badge type="warning">Terlambat</x-badge>
                                    @else
                                        <x-badge type="danger">Sangat Terlambat</x-badge>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8 text-secondary-500">
                            <svg class="w-12 h-12 mx-auto text-secondary-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <p>Belum ada clock-in hari ini</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Recent Employees --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Karyawan Terbaru</h3>
                    <a href="{{ route('employees.index') }}" class="text-sm text-primary-600 hover:text-primary-700 font-medium">Lihat semua</a>
                </div>
                @if($recentEmployees->count() > 0)
                    <x-table>
                        <x-slot name="header">
                            <th>Nama</th>
                            <th>Departemen</th>
                            <th>Jabatan</th>
                            <th>Tanggal Bergabung</th>
                            <th>Status</th>
                        </x-slot>

                        @foreach($recentEmployees as $employee)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center text-primary-600 text-sm font-semibold">
                                        {{ substr($employee->first_name, 0, 1) }}
                                    </div>
                                    <span class="font-medium text-secondary-900">{{ $employee->full_name }}</span>
                                </div>
                            </td>
                            <td>{{ $employee->department->name ?? '-' }}</td>
                            <td>{{ $employee->position->name ?? '-' }}</td>
                            <td>{{ $employee->hire_date?->format('d M Y') ?? '-' }}</td>
                            <td>
                                @if($employee->employment_status === 'probation')
                                    <x-badge type="warning">Probation</x-badge>
                                @elseif($employee->employment_status === 'permanent')
                                    <x-badge type="success">Permanen</x-badge>
                                @else
                                    <x-badge type="info">Kontrak</x-badge>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </x-table>
                @else
                    <div class="card-body text-center py-8 text-secondary-500">
                        <p>Belum ada data karyawan</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Right Column (1/3) --}}
        <div class="space-y-6">
            {{-- Monthly Summary --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Ringkasan Bulan Ini</h3>
                </div>
                <div class="card-body space-y-4">
                    <div class="flex items-center justify-between p-3 bg-secondary-50 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </div>
                            <span class="text-secondary-600">Total Karyawan</span>
                        </div>
                        <span class="font-bold text-secondary-900">{{ $analytics['monthly_summary']['total_employees'] }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-success-50 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-success-100 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                            </div>
                            <span class="text-secondary-600">Karyawan Baru</span>
                        </div>
                        <span class="font-bold text-success-600">+{{ $analytics['monthly_summary']['new_employees'] }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-danger-50 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-danger-100 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-danger-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6"/></svg>
                            </div>
                            <span class="text-secondary-600">Resign</span>
                        </div>
                        <span class="font-bold text-danger-600">{{ $analytics['monthly_summary']['resigned_employees'] }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-warning-50 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-warning-100 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            </div>
                            <span class="text-secondary-600">Turnover Rate</span>
                        </div>
                        <span class="font-bold text-warning-600">{{ $analytics['monthly_summary']['turnover_rate'] }}%</span>
                    </div>
                </div>
            </div>

            {{-- Pending Approvals --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Perlu Approval</h3>
                    @if($pendingApprovals->count() > 0)
                        <span class="badge badge-danger">{{ $stats['pending_leaves'] }}</span>
                    @endif
                </div>
                <div class="card-body space-y-4">
                    @forelse($pendingApprovals as $request)
                    <div class="flex items-start gap-3 p-3 bg-secondary-50 rounded-lg">
                        <div class="w-10 h-10 bg-warning-100 rounded-full flex items-center justify-center text-warning-600 flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-secondary-900 truncate">{{ $request->employee->full_name ?? 'Unknown' }}</p>
                            <p class="text-sm text-secondary-500">{{ $request->leaveType->name ?? '-' }}</p>
                            <p class="text-xs text-secondary-400 mt-1">{{ $request->created_at?->diffForHumans() }}</p>
                        </div>
                        <div class="flex items-center gap-1">
                            <form action="{{ route('leave-requests.approve', $request) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="w-8 h-8 flex items-center justify-center rounded-lg bg-success-100 text-success-600 hover:bg-success-200">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            </form>
                            <form action="{{ route('leave-requests.reject', $request) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="w-8 h-8 flex items-center justify-center rounded-lg bg-danger-100 text-danger-600 hover:bg-danger-200">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-4 text-secondary-500">
                        <p class="text-sm">Tidak ada pengajuan yang perlu di-approve</p>
                    </div>
                    @endforelse
                </div>
                @if($pendingApprovals->count() > 0)
                    <div class="card-footer">
                        <a href="{{ route('leave-requests.index', ['status' => 'pending']) }}" class="text-sm text-primary-600 hover:text-primary-700 font-medium">Lihat semua pengajuan</a>
                    </div>
                @endif
            </div>

            {{-- Birthday This Month --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Ulang Tahun Bulan Ini</h3>
                </div>
                <div class="card-body space-y-3">
                    @forelse($birthdaysThisMonth->take(5) as $employee)
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-accent-100 rounded-full flex items-center justify-center text-accent-600 font-semibold">
                            {{ substr($employee->first_name, 0, 1) }}
                        </div>
                        <div class="flex-1">
                            <p class="font-medium text-secondary-900">{{ $employee->full_name }}</p>
                            <p class="text-sm text-secondary-500">{{ $employee->date_of_birth?->format('d M') }}</p>
                        </div>
                        @if($employee->date_of_birth?->format('d') == now()->format('d'))
                            <span class="text-2xl">🎂</span>
                        @else
                            <span class="text-2xl">🎉</span>
                        @endif
                    </div>
                    @empty
                    <div class="text-center py-4 text-secondary-500">
                        <p class="text-sm">Tidak ada ulang tahun bulan ini</p>
                    </div>
                    @endforelse
                </div>
            </div>

            {{-- Contract Expiring --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Kontrak Akan Berakhir</h3>
                    @if($expiringContracts->count() > 0)
                        <span class="badge badge-warning">{{ $expiringContracts->count() }}</span>
                    @endif
                </div>
                <div class="card-body space-y-3">
                    @forelse($expiringContracts->take(5) as $employee)
                    <div class="flex items-center gap-3 p-3 bg-warning-50 rounded-lg">
                        <div class="w-10 h-10 bg-warning-100 rounded-full flex items-center justify-center text-warning-600 font-semibold">
                            {{ substr($employee->first_name, 0, 1) }}
                        </div>
                        <div class="flex-1">
                            <p class="font-medium text-secondary-900">{{ $employee->full_name }}</p>
                            <p class="text-sm text-warning-600">Berakhir {{ $employee->contract_end_date?->diffForHumans() }}</p>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-4 text-secondary-500">
                        <p class="text-sm">Tidak ada kontrak yang akan berakhir</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chart.js default settings
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.color = '#64748b';

    // Attendance Chart (Line)
    const attendanceCtx = document.getElementById('attendanceChart');
    if (attendanceCtx) {
        new Chart(attendanceCtx, {
            type: 'line',
            data: {
                labels: @json($analytics['attendance_chart']['labels']),
                datasets: [
                    {
                        label: 'Hadir',
                        data: @json($analytics['attendance_chart']['datasets']['present']),
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Terlambat',
                        data: @json($analytics['attendance_chart']['datasets']['late']),
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    // Payroll Chart (Bar)
    const payrollCtx = document.getElementById('payrollChart');
    if (payrollCtx) {
        new Chart(payrollCtx, {
            type: 'bar',
            data: {
                labels: @json($analytics['payroll_trend']['labels']),
                datasets: [{
                    label: 'Total Payroll',
                    data: @json($analytics['payroll_trend']['data']),
                    backgroundColor: '#3b82f6',
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + (value / 1000000).toFixed(0) + 'jt';
                            }
                        }
                    }
                }
            }
        });
    }

    // Department Chart (Doughnut)
    const departmentCtx = document.getElementById('departmentChart');
    if (departmentCtx && @json(count($analytics['employee_by_department']['labels'])) > 0) {
        new Chart(departmentCtx, {
            type: 'doughnut',
            data: {
                labels: @json($analytics['employee_by_department']['labels']),
                datasets: [{
                    data: @json($analytics['employee_by_department']['data']),
                    backgroundColor: @json($analytics['employee_by_department']['colors']),
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            padding: 8
                        }
                    }
                },
                cutout: '60%'
            }
        });
    }

    // Status Chart (Doughnut)
    const statusCtx = document.getElementById('statusChart');
    if (statusCtx && @json(count($analytics['employee_status']['labels'])) > 0) {
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: @json($analytics['employee_status']['labels']),
                datasets: [{
                    data: @json($analytics['employee_status']['data']),
                    backgroundColor: @json($analytics['employee_status']['colors']),
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            padding: 8
                        }
                    }
                },
                cutout: '60%'
            }
        });
    }

    // Leave Chart (Doughnut)
    const leaveCtx = document.getElementById('leaveChart');
    if (leaveCtx && @json(count($analytics['leave_statistics']['labels'])) > 0) {
        new Chart(leaveCtx, {
            type: 'doughnut',
            data: {
                labels: @json($analytics['leave_statistics']['labels']),
                datasets: [{
                    data: @json($analytics['leave_statistics']['data']),
                    backgroundColor: @json($analytics['leave_statistics']['colors']),
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            padding: 8
                        }
                    }
                },
                cutout: '60%'
            }
        });
    }
});
</script>
@endpush
