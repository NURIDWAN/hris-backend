@extends('layouts.portal')

@section('title', 'Absensi')

@section('breadcrumb')
    <a href="{{ route('portal.dashboard') }}" class="text-slate-500 hover:text-primary-600">Dashboard</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Absensi</span>
@endsection

@section('header')
    <div>
        <h1 class="text-2xl font-bold text-secondary-900">Absensi</h1>
        <p class="text-secondary-500 mt-1">Kelola absensi harian Anda.</p>
    </div>
@endsection

@section('content')
    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Clock In/Out Card --}}
        <div class="card lg:col-span-1">
            <div class="card-header">
                <h3 class="card-title">Absensi Hari Ini</h3>
            </div>
            <div class="card-body">
                <div class="text-center mb-6">
                    <div class="text-4xl font-bold text-secondary-900" x-data x-init="
                        setInterval(() => {
                            $el.textContent = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                        }, 1000);
                    ">{{ now()->format('H:i:s') }}</div>
                    <div class="text-secondary-500 mt-1">{{ now()->translatedFormat('l, d F Y') }}</div>
                </div>

                @if($todayAttendance)
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-3 bg-success-50 rounded-lg">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-success-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                                </div>
                                <div>
                                    <div class="text-sm text-secondary-500">Clock In</div>
                                    <div class="font-semibold text-secondary-900">{{ $todayAttendance->formatted_clock_in }}</div>
                                </div>
                            </div>
                            @if($todayAttendance->status === 'late')
                                <x-badge type="warning">Terlambat</x-badge>
                            @else
                                <x-badge type="success">Tepat Waktu</x-badge>
                            @endif
                        </div>

                        @if($todayAttendance->clock_out)
                            <div class="flex items-center justify-between p-3 bg-danger-50 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-danger-100 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-danger-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                    </div>
                                    <div>
                                        <div class="text-sm text-secondary-500">Clock Out</div>
                                        <div class="font-semibold text-secondary-900">{{ $todayAttendance->formatted_clock_out }}</div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <form action="{{ route('portal.attendance.clock-out') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-danger w-full">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                    Clock Out
                                </button>
                            </form>
                        @endif
                    </div>
                @else
                    <form action="{{ route('portal.attendance.clock-in') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-lg w-full">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                            Clock In
                        </button>
                    </form>
                @endif
            </div>
        </div>

        {{-- Face Verification Card --}}
        @if($faceRecognitionEnabled)
            <div class="card lg:col-span-1">
                <div class="card-header">
                    <h3 class="card-title">Verifikasi Wajah</h3>
                </div>
                <div class="card-body">
                    @if($hasFaceEnrolled)
                        <div class="text-center">
                            <div class="w-16 h-16 bg-success-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h4 class="font-medium text-secondary-900 mb-1">Wajah Terdaftar</h4>
                            <p class="text-sm text-secondary-500 mb-4">
                                Wajah Anda sudah terdaftar untuk verifikasi absensi.
                            </p>
                            <p class="text-xs text-secondary-400">
                                Terdaftar: {{ $employee->faceEmbedding->enrolled_at->format('d M Y H:i') }}
                            </p>
                        </div>
                    @else
                        <div class="text-center">
                            <div class="w-16 h-16 bg-warning-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                            <h4 class="font-medium text-warning-600 mb-1">Wajah Belum Terdaftar</h4>
                            <p class="text-sm text-secondary-500 mb-4">
                                Wajah Anda belum terdaftar. Hubungi admin untuk mendaftarkan wajah Anda.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Attendance History --}}
        <div class="card {{ $faceRecognitionEnabled ? 'lg:col-span-1' : 'lg:col-span-2' }}">
            <div class="card-header">
                <h3 class="card-title">Riwayat Absensi</h3>
            </div>
            <div class="card-body p-0">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-secondary-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-medium text-secondary-500">Tanggal</th>
                                <th class="px-4 py-3 text-center text-sm font-medium text-secondary-500">Clock In</th>
                                <th class="px-4 py-3 text-center text-sm font-medium text-secondary-500">Clock Out</th>
                                <th class="px-4 py-3 text-center text-sm font-medium text-secondary-500">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-secondary-100">
                            @forelse($attendanceHistory as $attendance)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-secondary-900">{{ $attendance->date->format('d M Y') }}</div>
                                        <div class="text-sm text-secondary-500">{{ $attendance->date->translatedFormat('l') }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        {{ $attendance->clock_in?->format('H:i') ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        {{ $attendance->clock_out?->format('H:i') ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @switch($attendance->status)
                                            @case('present')
                                                <x-badge type="success">Hadir</x-badge>
                                                @break
                                            @case('late')
                                                <x-badge type="warning">Terlambat</x-badge>
                                                @break
                                            @case('absent')
                                                <x-badge type="danger">Tidak Hadir</x-badge>
                                                @break
                                            @default
                                                <x-badge type="secondary">{{ ucfirst($attendance->status) }}</x-badge>
                                        @endswitch
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-secondary-500">
                                        Belum ada riwayat absensi
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($attendanceHistory->hasPages())
                <div class="card-footer">
                    {{ $attendanceHistory->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
