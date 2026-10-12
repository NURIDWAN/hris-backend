@extends('layouts.admin')

@section('title', 'Kalender Hari Libur')

@section('breadcrumb')
    <a href="{{ route('holidays.index') }}" class="text-slate-500 hover:text-primary-600">Hari Libur</a>
    <svg class="w-4 h-4 mx-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Kalender</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('holidays.index') }}" class="btn btn-ghost btn-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">Kalender Hari Libur</h1>
                <p class="text-secondary-500 mt-1">Tampilan kalender tahunan hari libur.</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('holidays.index') }}" class="btn btn-ghost">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                Daftar
            </a>
            <a href="{{ route('holidays.create') }}" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Tambah Libur
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div x-data="holidayCalendar()" x-init="loadHolidays()">
        {{-- Year Navigation --}}
        <div class="card mb-6">
            <div class="card-body-sm flex items-center justify-between">
                <button @click="prevYear()" class="btn btn-ghost btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Sebelumnya
                </button>
                <h2 class="text-xl font-bold text-secondary-900" x-text="year"></h2>
                <button @click="nextYear()" class="btn btn-ghost btn-sm">
                    Selanjutnya
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
        </div>

        {{-- Legend --}}
        <div class="flex flex-wrap items-center gap-4 mb-6">
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-danger-500"></span>
                <span class="text-sm text-secondary-600">Libur Nasional</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-primary-500"></span>
                <span class="text-sm text-secondary-600">Libur Perusahaan</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-warning-500"></span>
                <span class="text-sm text-secondary-600">Libur Keagamaan</span>
            </div>
        </div>

        {{-- Calendar Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <template x-for="month in 12" :key="month">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title" x-text="getMonthName(month)"></h3>
                    </div>
                    <div class="card-body p-3">
                        {{-- Month calendar grid --}}
                        <div class="grid grid-cols-7 gap-1 text-center text-xs">
                            {{-- Day headers --}}
                            <template x-for="day in ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab']" :key="day">
                                <div class="font-medium text-secondary-500 py-1" x-text="day"></div>
                            </template>
                            {{-- Calendar days --}}
                            <template x-for="day in getMonthDays(month)" :key="day.key">
                                <div
                                    class="py-1 rounded cursor-default"
                                    :class="{
                                        'text-secondary-300': !day.current,
                                        'text-secondary-900': day.current && !day.holiday,
                                        'bg-danger-100 text-danger-700 font-medium': day.holiday && day.holiday.type === 'national',
                                        'bg-primary-100 text-primary-700 font-medium': day.holiday && day.holiday.type === 'company',
                                        'bg-warning-100 text-warning-700 font-medium': day.holiday && day.holiday.type === 'religious',
                                    }"
                                    :title="day.holiday ? day.holiday.name : ''"
                                    x-text="day.day">
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- Upcoming Holidays List --}}
        <div class="card mt-6">
            <div class="card-header">
                <h3 class="card-title">Daftar Hari Libur <span x-text="year"></span></h3>
            </div>
            <div class="card-body">
                <div class="space-y-3" x-show="holidays.length > 0">
                    <template x-for="holiday in holidays" :key="holiday.id">
                        <div class="flex items-center gap-4 p-3 bg-secondary-50 rounded-lg">
                            <div class="flex-shrink-0">
                                <span
                                    class="inline-flex items-center justify-center w-10 h-10 rounded-lg text-white font-bold text-sm"
                                    :class="{
                                        'bg-danger-500': holiday.type === 'national',
                                        'bg-primary-500': holiday.type === 'company',
                                        'bg-warning-500': holiday.type === 'religious',
                                    }"
                                    x-text="new Date(holiday.date).getDate()">
                                </span>
                            </div>
                            <div class="flex-1">
                                <p class="font-medium text-secondary-900" x-text="holiday.name"></p>
                                <p class="text-sm text-secondary-500" x-text="formatDate(holiday.date)"></p>
                            </div>
                            <div>
                                <span
                                    class="inline-flex items-center px-2 py-1 rounded text-xs font-medium"
                                    :class="{
                                        'bg-danger-100 text-danger-700': holiday.type === 'national',
                                        'bg-primary-100 text-primary-700': holiday.type === 'company',
                                        'bg-warning-100 text-warning-700': holiday.type === 'religious',
                                    }"
                                    x-text="holiday.type_label">
                                </span>
                            </div>
                        </div>
                    </template>
                </div>
                <div x-show="holidays.length === 0" class="text-center py-8 text-secondary-500">
                    <p>Belum ada hari libur untuk tahun ini.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function holidayCalendar() {
            return {
                year: new Date().getFullYear(),
                holidays: [],

                async loadHolidays() {
                    try {
                        const response = await fetch(`{{ route('holidays.events') }}?year=${this.year}`);
                        this.holidays = await response.json();
                    } catch (error) {
                        console.error('Error loading holidays:', error);
                    }
                },

                prevYear() {
                    this.year--;
                    this.loadHolidays();
                },

                nextYear() {
                    this.year++;
                    this.loadHolidays();
                },

                getMonthName(month) {
                    const months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                    return months[month];
                },

                getMonthDays(month) {
                    const days = [];
                    const firstDay = new Date(this.year, month - 1, 1);
                    const lastDay = new Date(this.year, month, 0);
                    const startDay = firstDay.getDay();

                    // Previous month days
                    const prevMonthLastDay = new Date(this.year, month - 1, 0).getDate();
                    for (let i = startDay - 1; i >= 0; i--) {
                        days.push({
                            key: `prev-${month}-${i}`,
                            day: prevMonthLastDay - i,
                            current: false,
                            holiday: null
                        });
                    }

                    // Current month days
                    for (let i = 1; i <= lastDay.getDate(); i++) {
                        const dateStr = `${this.year}-${String(month).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
                        const holiday = this.holidays.find(h => h.date === dateStr);
                        days.push({
                            key: `curr-${month}-${i}`,
                            day: i,
                            current: true,
                            holiday: holiday || null
                        });
                    }

                    // Next month days
                    const remaining = 42 - days.length;
                    for (let i = 1; i <= remaining; i++) {
                        days.push({
                            key: `next-${month}-${i}`,
                            day: i,
                            current: false,
                            holiday: null
                        });
                    }

                    return days;
                },

                formatDate(dateStr) {
                    const date = new Date(dateStr);
                    const options = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
                    return date.toLocaleDateString('id-ID', options);
                }
            }
        }
    </script>
@endsection
