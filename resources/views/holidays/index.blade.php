@extends('layouts.admin')

@section('title', 'Hari Libur')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Hari Libur</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Kalender Hari Libur</h1>
            <p class="text-secondary-500 mt-1">Kelola hari libur nasional dan perusahaan.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('holidays.calendar') }}" class="btn btn-ghost">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Kalender
            </a>
            <a href="{{ route('imports.holidays.index') }}" class="btn btn-ghost">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Import
            </a>
            <button type="button" class="btn btn-secondary"
                @click="$dispatch('confirm-dialog', {
                    title: 'Generate Hari Libur Nasional',
                    message: 'Sistem akan menambahkan hari libur nasional Indonesia untuk tahun {{ request('year', $currentYear) }}. Tanggal yang sudah ada akan dilewati.',
                    confirmText: 'Ya, Generate',
                    type: 'primary',
                    method: 'POST',
                    formAction: '{{ route('holidays.generate') }}',
                    formData: { year: {{ request('year', $currentYear) }} }
                })">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Generate Nasional
            </button>
            <a href="{{ route('holidays.create') }}" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Tambah Libur
            </a>
        </div>
    </div>
@endsection

@section('content')
    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body-sm">
            <form action="{{ route('holidays.index') }}" method="GET" class="flex flex-wrap items-end gap-3">
                {{-- Search --}}
                <div class="flex-1 min-w-[180px]">
                    <label for="search" class="block text-xs font-medium text-secondary-500 mb-1">Cari</label>
                    <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Nama hari libur..." class="input w-full">
                </div>

                {{-- Year --}}
                <div class="w-32">
                    <label for="year" class="block text-xs font-medium text-secondary-500 mb-1">Tahun</label>
                    <select name="year" id="year" class="input w-full">
                        @foreach($years as $year)
                            <option value="{{ $year }}" {{ request('year', $currentYear) == $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Type --}}
                <div class="w-40">
                    <label for="type" class="block text-xs font-medium text-secondary-500 mb-1">Jenis</label>
                    <select name="type" id="type" class="input w-full">
                        <option value="">Semua Jenis</option>
                        <option value="national" {{ request('type') === 'national' ? 'selected' : '' }}>Libur Nasional</option>
                        <option value="company" {{ request('type') === 'company' ? 'selected' : '' }}>Libur Perusahaan</option>
                        <option value="religious" {{ request('type') === 'religious' ? 'selected' : '' }}>Libur Keagamaan</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    @if(request()->hasAny(['search', 'type']) || request('year') != $currentYear)
                        <a href="{{ route('holidays.index') }}" class="btn btn-ghost btn-sm">Reset</a>
                    @endif
                    <button type="submit" class="btn btn-primary btn-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card">
        <div class="overflow-x-auto">
            <x-table>
                <x-slot name="header">
                    <th class="w-16">No</th>
                    <th>Tanggal</th>
                    <th>Nama Hari Libur</th>
                    <th>Jenis</th>
                    <th class="text-center">Berulang</th>
                    <th class="w-32 text-right">Aksi</th>
                </x-slot>

                @forelse($holidays as $index => $holiday)
                    <tr>
                        <td class="text-secondary-500">{{ $holidays->firstItem() + $index }}</td>
                        <td>
                            <div class="font-medium text-secondary-900">{{ $holiday->date->format('d M Y') }}</div>
                            <div class="text-xs text-secondary-500">{{ $holiday->date->translatedFormat('l') }}</div>
                        </td>
                        <td>
                            <div class="font-medium text-secondary-900">{{ $holiday->name }}</div>
                            @if($holiday->description)
                                <div class="text-xs text-secondary-500">{{ Str::limit($holiday->description, 50) }}</div>
                            @endif
                        </td>
                        <td>
                            <x-badge :type="$holiday->type_color">{{ $holiday->type_label }}</x-badge>
                        </td>
                        <td class="text-center">
                            @if($holiday->is_recurring)
                                <span class="text-success-600">
                                    <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </span>
                            @else
                                <span class="text-secondary-400">-</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('holidays.edit', $holiday) }}" class="btn btn-ghost btn-sm" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <button type="button" class="btn btn-ghost btn-sm text-danger-600"
                                    @click="$dispatch('confirm-dialog', {
                                        title: 'Hapus Hari Libur',
                                        message: 'Apakah Anda yakin ingin menghapus hari libur {{ $holiday->name }}?',
                                        confirmText: 'Ya, Hapus',
                                        type: 'danger',
                                        formAction: '{{ route('holidays.destroy', $holiday) }}'
                                    })">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-8 text-secondary-500">
                            <svg class="w-12 h-12 mx-auto mb-3 text-secondary-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <p>Belum ada hari libur untuk tahun ini.</p>
                            <a href="{{ route('holidays.create') }}" class="btn btn-primary btn-sm mt-3">Tambah Hari Libur</a>
                        </td>
                    </tr>
                @endforelse
            </x-table>
        </div>

        @if($holidays->hasPages())
            <div class="card-footer">
                {{ $holidays->links() }}
            </div>
        @endif
    </div>

    {{-- Hidden form for generate --}}
    <form id="generate-form" action="{{ route('holidays.generate') }}" method="POST" class="hidden">
        @csrf
        <input type="hidden" name="year" value="{{ request('year', $currentYear) }}">
    </form>
@endsection
