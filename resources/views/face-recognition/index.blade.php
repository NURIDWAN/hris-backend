@extends('layouts.admin')

@section('title', 'Pendaftaran Wajah')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Pendaftaran Wajah</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Pendaftaran Wajah</h1>
            <p class="text-secondary-500 mt-1">Kelola pendaftaran wajah karyawan untuk presensi.</p>
        </div>
    </div>
@endsection

@section('content')
    {{-- Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="card">
            <div class="card-body-sm flex items-center gap-3">
                <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-secondary-500">Total Karyawan</p>
                    <p class="text-xl font-bold text-secondary-900">{{ $employees->total() }}</p>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body-sm flex items-center gap-3">
                <div class="w-10 h-10 bg-success-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-secondary-500">Sudah Terdaftar</p>
                    <p class="text-xl font-bold text-success-600">{{ $employees->filter(fn($e) => $e->faceEmbedding && $e->faceEmbedding->is_active)->count() }}</p>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body-sm flex items-center gap-3">
                <div class="w-10 h-10 bg-warning-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-secondary-500">Belum Terdaftar</p>
                    <p class="text-xl font-bold text-warning-600">{{ $employees->filter(fn($e) => !$e->faceEmbedding || !$e->faceEmbedding->is_active)->count() }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Employee List --}}
    <div class="card">
        <x-table>
            <x-slot name="header">
                <th>Karyawan</th>
                <th>ID Karyawan</th>
                <th>Departemen</th>
                <th>Status Wajah</th>
                <th>Tanggal Daftar</th>
                <th class="text-right">Aksi</th>
            </x-slot>

            @forelse($employees as $employee)
                @php
                    $isEnrolled = $employee->faceEmbedding && $employee->faceEmbedding->is_active;
                @endphp
                <tr>
                    <td>
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center text-primary-600 font-medium text-xs flex-shrink-0">
                                {{ strtoupper(substr($employee->first_name, 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <span class="font-medium text-secondary-900 block truncate">
                                    {{ $employee->full_name }}
                                </span>
                                @if($employee->email)
                                    <p class="text-xs text-secondary-400 truncate">{{ $employee->email }}</p>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="font-mono text-xs text-secondary-600">{{ $employee->employee_id }}</span>
                    </td>
                    <td class="text-secondary-600">{{ $employee->department?->name ?? '-' }}</td>
                    <td>
                        @if($isEnrolled)
                            <x-badge type="success">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Terdaftar
                            </x-badge>
                        @else
                            <x-badge type="secondary">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Belum Terdaftar
                            </x-badge>
                        @endif
                    </td>
                    <td class="text-secondary-600">
                        @if($isEnrolled)
                            {{ $employee->faceEmbedding->enrolled_at->format('d M Y H:i') }}
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        <div class="flex items-center justify-end gap-1">
                            <a href="{{ route('face-recognition.show', $employee) }}" class="btn btn-ghost btn-sm">
                                @if($isEnrolled)
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    Lihat
                                @else
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                    Daftarkan
                                @endif
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center py-12">
                        <div class="flex flex-col items-center">
                            <svg class="w-12 h-12 text-secondary-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <p class="text-secondary-500">Belum ada data karyawan.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </x-table>

        @if($employees->hasPages())
            <div class="card-footer">
                {{ $employees->links() }}
            </div>
        @endif
    </div>
@endsection
