<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') - {{ brand_name() }}</title>

    <link rel="icon" type="image/svg+xml" href="{{ brand_asset('favicon_path', 'favicon.svg') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-50">
    <div x-data="{ sidebarOpen: false, sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true' }"
         x-init="$watch('sidebarCollapsed', val => localStorage.setItem('sidebarCollapsed', val))"
         class="min-h-screen flex">

        {{-- Sidebar Overlay (Mobile) --}}
        <div x-show="sidebarOpen"
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="sidebarOpen = false"
             class="fixed inset-0 bg-secondary-900/50 z-40 lg:hidden"
             x-cloak>
        </div>

        {{-- Sidebar - Light Theme with subtle gradient --}}
        <aside :class="[sidebarCollapsed ? 'lg:w-20' : 'lg:w-64', sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0']"
               class="fixed inset-y-0 left-0 z-50 w-64 bg-gradient-to-b from-slate-50 via-white to-slate-50 border-r border-slate-200/80 transform transition-all duration-300 ease-in-out lg:static lg:inset-auto lg:translate-x-0 flex flex-col shadow-lg shadow-slate-200/50">

            {{-- Logo --}}
            <div class="h-16 flex items-center justify-between px-4 border-b border-slate-100">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-primary-500 to-primary-600 rounded-xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <span x-show="!sidebarCollapsed" x-transition class="text-lg font-bold bg-gradient-to-r from-primary-600 to-primary-500 bg-clip-text text-transparent">{{ brand_name() }}</span>
                </a>
                <button @click="sidebarOpen = false" class="lg:hidden text-slate-400 hover:text-slate-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
                {{-- Dashboard --}}
                <a href="{{ route('dashboard') }}" class="sidebar-link-light {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    <span x-show="!sidebarCollapsed" x-transition>Dashboard</span>
                </a>

                {{-- Section Label --}}
                <div x-show="!sidebarCollapsed" class="!mt-6 !mb-3 px-3">
                    <span class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">Manajemen</span>
                </div>

                {{-- Karyawan --}}
                <div x-data="{ open: {{ request()->routeIs('employees.*') || request()->routeIs('departments.*') || request()->routeIs('positions.*') || request()->routeIs('office-locations.*') || request()->routeIs('employee-exits.*') || request()->routeIs('organization-chart.*') ? 'true' : 'false' }} }">
                    <button @click="open = !open" class="sidebar-link-light w-full justify-between">
                        <span class="flex items-center gap-3">
                            <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            <span x-show="!sidebarCollapsed" x-transition>Karyawan</span>
                        </span>
                        <svg x-show="!sidebarCollapsed" :class="open ? 'rotate-180' : ''" class="w-4 h-4 text-slate-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open && !sidebarCollapsed" x-collapse class="mt-2 sidebar-submenu-light">
                        <a href="{{ route('employees.index') }}" class="sidebar-sublink-light {{ request()->routeIs('employees.*') ? 'active' : '' }}">Daftar Karyawan</a>
                        <a href="{{ route('departments.index') }}" class="sidebar-sublink-light {{ request()->routeIs('departments.*') ? 'active' : '' }}">Departemen</a>
                        <a href="{{ route('positions.index') }}" class="sidebar-sublink-light {{ request()->routeIs('positions.*') ? 'active' : '' }}">Jabatan</a>
                        <a href="{{ route('office-locations.index') }}" class="sidebar-sublink-light {{ request()->routeIs('office-locations.*') ? 'active' : '' }}">Lokasi Kantor</a>
                        <a href="{{ route('organization-chart.index') }}" class="sidebar-sublink-light {{ request()->routeIs('organization-chart.*') ? 'active' : '' }}">Struktur Organisasi</a>
                        <a href="{{ route('employee-exits.index') }}" class="sidebar-sublink-light {{ request()->routeIs('employee-exits.*') ? 'active' : '' }}">Exit Management</a>
                    </div>
                </div>

                {{-- Kehadiran --}}
                <div x-data="{ open: {{ request()->routeIs('attendances.*') || request()->routeIs('work-schedules.*') || request()->routeIs('holidays.*') || request()->routeIs('overtime-requests.*') || request()->routeIs('overtime-settings.*') ? 'true' : 'false' }} }">
                    <button @click="open = !open" class="sidebar-link-light w-full justify-between">
                        <span class="flex items-center gap-3">
                            <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span x-show="!sidebarCollapsed" x-transition>Kehadiran</span>
                        </span>
                        <svg x-show="!sidebarCollapsed" :class="open ? 'rotate-180' : ''" class="w-4 h-4 text-slate-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open && !sidebarCollapsed" x-collapse class="mt-2 sidebar-submenu-light">
                        <a href="{{ route('attendances.index') }}" class="sidebar-sublink-light {{ request()->routeIs('attendances.index') || request()->routeIs('attendances.show') || request()->routeIs('attendances.create') || request()->routeIs('attendances.edit') ? 'active' : '' }}">Daftar Kehadiran</a>
                        <a href="{{ route('attendances.report') }}" class="sidebar-sublink-light {{ request()->routeIs('attendances.report') ? 'active' : '' }}">Laporan Kehadiran</a>
                        <a href="{{ route('work-schedules.index') }}" class="sidebar-sublink-light {{ request()->routeIs('work-schedules.*') ? 'active' : '' }}">Jadwal Kerja</a>
                        <a href="{{ route('holidays.index') }}" class="sidebar-sublink-light {{ request()->routeIs('holidays.*') ? 'active' : '' }}">Hari Libur</a>
                        <a href="{{ route('overtime-requests.index') }}" class="sidebar-sublink-light {{ request()->routeIs('overtime-requests.*') ? 'active' : '' }}">Pengajuan Lembur</a>
                        <a href="{{ route('overtime-settings.index') }}" class="sidebar-sublink-light {{ request()->routeIs('overtime-settings.*') ? 'active' : '' }}">Pengaturan Lembur</a>
                    </div>
                </div>

                {{-- Cuti & Izin --}}
                <div x-data="{ open: {{ request()->routeIs('leave-requests.*') || request()->routeIs('leave-types.*') || request()->routeIs('leave-balances.*') ? 'true' : 'false' }} }">
                    <button @click="open = !open" class="sidebar-link-light w-full justify-between">
                        <span class="flex items-center gap-3">
                            <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span x-show="!sidebarCollapsed" x-transition>Cuti & Izin</span>
                        </span>
                        <svg x-show="!sidebarCollapsed" :class="open ? 'rotate-180' : ''" class="w-4 h-4 text-slate-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open && !sidebarCollapsed" x-collapse class="mt-2 sidebar-submenu-light">
                        <a href="{{ route('leave-requests.index') }}" class="sidebar-sublink-light {{ request()->routeIs('leave-requests.*') ? 'active' : '' }}">Pengajuan Cuti</a>
                        <a href="{{ route('leave-balances.index') }}" class="sidebar-sublink-light {{ request()->routeIs('leave-balances.*') ? 'active' : '' }}">Saldo Cuti</a>
                        <a href="{{ route('leave-types.index') }}" class="sidebar-sublink-light {{ request()->routeIs('leave-types.*') ? 'active' : '' }}">Jenis Cuti</a>
                    </div>
                </div>

                {{-- Section Label --}}
                <div x-show="!sidebarCollapsed" class="!mt-6 !mb-3 px-3">
                    <span class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">Keuangan</span>
                </div>

                {{-- Payroll --}}
                <div x-data="{ open: {{ request()->routeIs('payrolls.*') || request()->routeIs('payroll-items.*') || request()->routeIs('payroll-settings.*') || request()->routeIs('salary-components.*') || request()->routeIs('employee-salaries.*') ? 'true' : 'false' }} }">
                    <button @click="open = !open" class="sidebar-link-light w-full justify-between">
                        <span class="flex items-center gap-3">
                            <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span x-show="!sidebarCollapsed" x-transition>Payroll</span>
                        </span>
                        <svg x-show="!sidebarCollapsed" :class="open ? 'rotate-180' : ''" class="w-4 h-4 text-slate-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open && !sidebarCollapsed" x-collapse class="mt-2 sidebar-submenu-light">
                        <a href="{{ route('payrolls.index') }}" class="sidebar-sublink-light {{ request()->routeIs('payrolls.*') ? 'active' : '' }}">Proses Gaji</a>
                        <a href="{{ route('payroll-items.index') }}" class="sidebar-sublink-light {{ request()->routeIs('payroll-items.*') ? 'active' : '' }}">Riwayat Slip Gaji</a>
                        <a href="{{ route('salary-components.index') }}" class="sidebar-sublink-light {{ request()->routeIs('salary-components.*') ? 'active' : '' }}">Komponen Gaji</a>
                        <a href="{{ route('employee-salaries.index') }}" class="sidebar-sublink-light {{ request()->routeIs('employee-salaries.*') ? 'active' : '' }}">Pengaturan Gaji</a>
                        <a href="{{ route('payroll-settings.edit') }}" class="sidebar-sublink-light {{ request()->routeIs('payroll-settings.*') ? 'active' : '' }}">Siklus Payroll</a>
                    </div>
                </div>

                {{-- Reimbursement --}}
                <div x-data="{ open: {{ request()->routeIs('reimbursements.*') || request()->routeIs('reimbursement-categories.*') ? 'true' : 'false' }} }">
                    <button @click="open = !open" class="sidebar-link-light w-full justify-between">
                        <span class="flex items-center gap-3">
                            <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            <span x-show="!sidebarCollapsed" x-transition>Reimbursement</span>
                        </span>
                        <svg x-show="!sidebarCollapsed" :class="open ? 'rotate-180' : ''" class="w-4 h-4 text-slate-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open && !sidebarCollapsed" x-collapse class="mt-2 sidebar-submenu-light">
                        <a href="{{ route('reimbursements.index') }}" class="sidebar-sublink-light {{ request()->routeIs('reimbursements.*') ? 'active' : '' }}">Pengajuan</a>
                        <a href="{{ route('reimbursement-categories.index') }}" class="sidebar-sublink-light {{ request()->routeIs('reimbursement-categories.*') ? 'active' : '' }}">Kategori</a>
                    </div>
                </div>

                {{-- Pajak & BPJS --}}
                <div x-data="{ open: {{ request()->routeIs('pph21-settings.*') || request()->routeIs('bpjs-tk-settings.*') || request()->routeIs('bpjs-kes-settings.*') || request()->routeIs('thr.*') || request()->routeIs('thr-settings.*') || request()->routeIs('tax-forms.*') || request()->routeIs('spt-1721.*') ? 'true' : 'false' }} }">
                    <button @click="open = !open" class="sidebar-link-light w-full justify-between">
                        <span class="flex items-center gap-3">
                            <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"/></svg>
                            <span x-show="!sidebarCollapsed" x-transition>Pajak & BPJS</span>
                        </span>
                        <svg x-show="!sidebarCollapsed" :class="open ? 'rotate-180' : ''" class="w-4 h-4 text-slate-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open && !sidebarCollapsed" x-collapse class="mt-2 sidebar-submenu-light">
                        <a href="{{ route('tax-forms.1721a1.index') }}" class="sidebar-sublink-light {{ request()->routeIs('tax-forms.1721a1.*') ? 'active' : '' }}">Bukti Potong 1721-A1</a>
                        <a href="{{ route('spt-1721.index') }}" class="sidebar-sublink-light {{ request()->routeIs('spt-1721.*') ? 'active' : '' }}">SPT Tahunan 1721</a>
                        <a href="{{ route('pph21-settings.index') }}" class="sidebar-sublink-light {{ request()->routeIs('pph21-settings.*') ? 'active' : '' }}">PPh 21</a>
                        <a href="{{ route('bpjs-tk-settings.index') }}" class="sidebar-sublink-light {{ request()->routeIs('bpjs-tk-settings.*') ? 'active' : '' }}">BPJS Ketenagakerjaan</a>
                        <a href="{{ route('bpjs-kes-settings.index') }}" class="sidebar-sublink-light {{ request()->routeIs('bpjs-kes-settings.*') ? 'active' : '' }}">BPJS Kesehatan</a>
                        <a href="{{ route('thr.index') }}" class="sidebar-sublink-light {{ request()->routeIs('thr.*') ? 'active' : '' }}">THR</a>
                        <a href="{{ route('thr-settings.index') }}" class="sidebar-sublink-light {{ request()->routeIs('thr-settings.*') ? 'active' : '' }}">Pengaturan THR</a>
                    </div>
                </div>

                {{-- Laporan --}}
                <div x-data="{ open: {{ request()->routeIs('reports.*') ? 'true' : 'false' }} }">
                    <button @click="open = !open" class="sidebar-link-light w-full justify-between">
                        <span class="flex items-center gap-3">
                            <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            <span x-show="!sidebarCollapsed" x-transition>Laporan</span>
                        </span>
                        <svg x-show="!sidebarCollapsed" :class="open ? 'rotate-180' : ''" class="w-4 h-4 text-slate-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open && !sidebarCollapsed" x-collapse class="mt-2 sidebar-submenu-light">
                        <a href="{{ route('reports.employees') }}" class="sidebar-sublink-light {{ request()->routeIs('reports.employees*') ? 'active' : '' }}">Laporan Karyawan</a>
                        <a href="{{ route('reports.attendance') }}" class="sidebar-sublink-light {{ request()->routeIs('reports.attendance*') ? 'active' : '' }}">Laporan Kehadiran</a>
                        <a href="{{ route('reports.leave') }}" class="sidebar-sublink-light {{ request()->routeIs('reports.leave*') ? 'active' : '' }}">Laporan Cuti</a>
                        <a href="{{ route('reports.payroll') }}" class="sidebar-sublink-light {{ request()->routeIs('reports.payroll*') ? 'active' : '' }}">Laporan Payroll</a>
                    </div>
                </div>

                {{-- Section Label --}}
                <div x-show="!sidebarCollapsed" class="!mt-6 !mb-3 px-3">
                    <span class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">Sistem</span>
                </div>

                {{-- Pengumuman --}}
                <a href="{{ route('announcements.index') }}" class="sidebar-link-light {{ request()->routeIs('announcements.*') ? 'active' : '' }}">
                    <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                    <span x-show="!sidebarCollapsed" x-transition>Pengumuman</span>
                </a>

                {{-- Pengaturan --}}
                <div x-data="{ open: {{ request()->routeIs('settings.*') ? 'true' : 'false' }} }">
                    <button @click="open = !open" class="sidebar-link-light w-full justify-between">
                        <span class="flex items-center gap-3">
                            <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span x-show="!sidebarCollapsed" x-transition>Pengaturan</span>
                        </span>
                        <svg x-show="!sidebarCollapsed" :class="open ? 'rotate-180' : ''" class="w-4 h-4 text-slate-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open && !sidebarCollapsed" x-collapse class="mt-2 sidebar-submenu-light">
                        <a href="{{ route('settings.company-profile.index') }}" class="sidebar-sublink-light {{ request()->routeIs('settings.company-profile.*') ? 'active' : '' }}">Profil Perusahaan</a>
                        <a href="{{ route('settings.attendance.index') }}" class="sidebar-sublink-light {{ request()->routeIs('settings.attendance.*') ? 'active' : '' }}">Pengaturan Absensi</a>
                        <a href="{{ route('settings.lark.index') }}" class="sidebar-sublink-light {{ request()->routeIs('settings.lark.*') ? 'active' : '' }}">Integrasi Lark</a>
                        <a href="{{ route('settings.users.index') }}" class="sidebar-sublink-light {{ request()->routeIs('settings.users.*') ? 'active' : '' }}">Pengguna</a>
                        <a href="{{ route('settings.roles.index') }}" class="sidebar-sublink-light {{ request()->routeIs('settings.roles.*') ? 'active' : '' }}">Role & Hak Akses</a>
                        <a href="{{ route('settings.approval-workflows.index') }}" class="sidebar-sublink-light {{ request()->routeIs('settings.approval-workflows.*') ? 'active' : '' }}">Alur Persetujuan</a>
                        @unless(single_tenant_mode())
                            <a href="{{ route('settings.billing.index') }}" class="sidebar-sublink-light {{ request()->routeIs('settings.billing.*') ? 'active' : '' }}">Billing</a>
                        @endunless
                    </div>
                </div>
            </nav>

            {{-- Collapse Button (Desktop) --}}
            <div class="hidden lg:block p-3 border-t border-slate-100">
                <button @click="sidebarCollapsed = !sidebarCollapsed" class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-slate-400 hover:text-primary-600 hover:bg-primary-50 transition-colors">
                    <svg :class="sidebarCollapsed ? 'rotate-180' : ''" class="w-5 h-5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
                    <span x-show="!sidebarCollapsed" x-transition class="text-sm">Collapse</span>
                </button>
            </div>
        </aside>

        {{-- Main Content --}}
        <div class="flex-1 flex flex-col min-h-screen bg-slate-100/70">
            {{-- Top Header --}}
            <header class="h-16 bg-white/80 backdrop-blur-sm border-b border-slate-200/60 flex items-center justify-between px-4 lg:px-6 sticky top-0 z-30 shadow-sm">
                {{-- Left: Mobile menu + Company Badge + Breadcrumb --}}
                <div class="flex items-center gap-4">
                    <button @click="sidebarOpen = true" class="lg:hidden text-slate-500 hover:text-slate-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>

                    {{-- Breadcrumb --}}
                    <nav class="hidden sm:flex items-center gap-2 text-sm">
                        <a href="{{ route('dashboard') }}" class="text-slate-500 hover:text-primary-600">Dashboard</a>
                        @hasSection('breadcrumb')
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            @yield('breadcrumb')
                        @endif
                    </nav>
                </div>

                {{-- Right: Company + Notifications + Profile --}}
                <div class="flex items-center gap-3">
                    {{-- Company Name --}}
                    @if(auth()->user()->company)
                        <div class="hidden md:flex items-center gap-2 px-3 py-1.5 bg-slate-100 rounded-lg">
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            <span class="text-sm font-medium text-slate-700">{{ auth()->user()->company->name }}</span>
                        </div>
                    @endif

                    {{-- Notifications --}}
                    <div x-data="notificationDropdown()" x-init="fetchNotifications()" class="relative">
                        <button @click="open = !open; if(open) fetchNotifications()" class="relative p-2 text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-xl transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                            <span x-show="unreadCount > 0" class="absolute top-1.5 right-1.5 w-2 h-2 bg-rose-500 rounded-full"></span>
                        </button>
                        <div x-show="open" @click.outside="open = false" x-transition x-cloak class="dropdown-menu w-80">
                            <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                                <h3 class="font-semibold text-slate-900">Notifikasi</h3>
                                <span x-show="unreadCount > 0" class="text-xs bg-primary-100 text-primary-700 px-2 py-0.5 rounded-full" x-text="unreadCount + ' baru'"></span>
                            </div>
                            <div class="max-h-80 overflow-y-auto">
                                <template x-if="loading">
                                    <div class="p-4 text-center text-slate-500">
                                        <svg class="animate-spin h-5 w-5 mx-auto" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                </template>
                                <template x-if="!loading && notifications.length === 0">
                                    <div class="p-4 text-center text-slate-500">
                                        <p class="text-sm">Tidak ada notifikasi</p>
                                    </div>
                                </template>
                                <template x-for="notification in notifications" :key="notification.id">
                                    <a :href="notification.link || '{{ route('notifications.index') }}'" class="dropdown-item py-3" :class="{ 'bg-primary-50/50': !notification.read_at }">
                                        <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"
                                             :class="getIconClass(notification.type)">
                                            <template x-if="notification.icon === 'calendar'">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            </template>
                                            <template x-if="notification.icon === 'banknotes'">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                            </template>
                                            <template x-if="notification.icon === 'clock'">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            </template>
                                            <template x-if="!['calendar', 'banknotes', 'clock'].includes(notification.icon)">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            </template>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-sm text-slate-900" :class="{ 'font-semibold': !notification.read_at }" x-text="notification.title"></p>
                                            <p class="text-xs text-slate-500" x-text="notification.message"></p>
                                            <p class="text-xs text-slate-400 mt-1" x-text="notification.time_ago"></p>
                                        </div>
                                    </a>
                                </template>
                            </div>
                            <div class="p-3 border-t border-slate-100">
                                <a href="{{ route('notifications.index') }}" class="text-sm text-primary-600 hover:text-primary-700 font-medium">Lihat semua notifikasi</a>
                            </div>
                        </div>
                    </div>
                    <script>
                        function notificationDropdown() {
                            return {
                                open: false,
                                loading: false,
                                notifications: [],
                                unreadCount: 0,
                                async fetchNotifications() {
                                    this.loading = true;
                                    try {
                                        const response = await fetch('{{ route('notifications.recent') }}');
                                        const data = await response.json();
                                        this.notifications = data.notifications;
                                        this.unreadCount = data.unread_count;
                                    } catch (error) {
                                        console.error('Failed to fetch notifications:', error);
                                    } finally {
                                        this.loading = false;
                                    }
                                },
                                getIconClass(type) {
                                    const classes = {
                                        'leave_request': 'bg-blue-100 text-blue-600',
                                        'leave_requested': 'bg-blue-100 text-blue-600',
                                        'payroll': 'bg-green-100 text-green-600',
                                        'attendance': 'bg-purple-100 text-purple-600',
                                        'employee': 'bg-indigo-100 text-indigo-600',
                                        'approval': 'bg-yellow-100 text-yellow-600',
                                        'warning': 'bg-orange-100 text-orange-600',
                                        'error': 'bg-red-100 text-red-600',
                                    };
                                    return classes[type] || 'bg-slate-100 text-slate-600';
                                }
                            }
                        }
                    </script>

                    {{-- Profile Dropdown --}}
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" class="flex items-center gap-3 p-1.5 rounded-xl hover:bg-slate-100 transition-colors">
                            <div class="w-9 h-9 bg-primary-500 rounded-lg flex items-center justify-center text-white text-sm font-semibold">
                                {{ substr(auth()->user()->name ?? 'A', 0, 1) }}
                            </div>
                            <div class="hidden md:block text-left">
                                <p class="text-sm font-medium text-slate-900">{{ auth()->user()->name ?? 'Admin' }}</p>
                                <p class="text-xs text-slate-500">
                                    @if(auth()->user()->company)
                                        {{ auth()->user()->company->name }}
                                    @else
                                        {{ ucfirst(str_replace('-', ' ', auth()->user()->roles->first()?->name ?? 'User')) }}
                                    @endif
                                </p>
                            </div>
                            <svg class="w-4 h-4 text-slate-400 hidden md:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" @click.outside="open = false" x-transition x-cloak class="dropdown-menu w-72">
                            {{-- User Info Header --}}
                            <div class="px-4 py-3 border-b border-slate-100">
                                <p class="text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-slate-500">{{ auth()->user()->email }}</p>
                                @if(auth()->user()->company)
                                    <div class="mt-2 flex items-center gap-2">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-primary-50 text-primary-700 text-xs font-medium rounded">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                            {{ auth()->user()->company->name }}
                                        </span>
                                        <span class="inline-flex items-center px-2 py-0.5 bg-slate-100 text-slate-600 text-xs font-medium rounded">
                                            {{ ucfirst(str_replace('-', ' ', auth()->user()->roles->first()?->name ?? 'User')) }}
                                        </span>
                                    </div>
                                @else
                                    <div class="mt-2">
                                        <span class="inline-flex items-center px-2 py-0.5 bg-amber-50 text-amber-700 text-xs font-medium rounded">
                                            {{ ucfirst(str_replace('-', ' ', auth()->user()->roles->first()?->name ?? 'User')) }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                            <div class="py-1">
                                <a href="{{ route('profile.index') }}" class="dropdown-item">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    Profil Saya
                                </a>
                                <a href="{{ route('settings.company-profile.index') }}" class="dropdown-item">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    Pengaturan
                                </a>
                            </div>
                            <div class="dropdown-divider"></div>
                            <div class="py-1">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item dropdown-item-danger w-full text-left">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                        Keluar
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            {{-- Page Content --}}
            <main class="flex-1 p-4 lg:p-6 bg-gradient-to-br from-slate-100/50 via-white/30 to-blue-50/30">
                {{-- Page Header --}}
                @hasSection('header')
                    <div class="mb-6">
                        @yield('header')
                    </div>
                @endif

                {{-- Flash Messages --}}
                @if(session('success'))
                    <x-alert type="success" class="mb-6" dismissible>
                        {{ session('success') }}
                    </x-alert>
                @endif

                @if(session('error'))
                    <x-alert type="danger" class="mb-6" dismissible>
                        {{ session('error') }}
                    </x-alert>
                @endif

                {{-- Main Content --}}
                @yield('content')
            </main>

            {{-- Footer --}}
            <footer class="py-4 px-6 text-center text-sm text-slate-500 border-t border-slate-200 bg-white">
                &copy; {{ date('Y') }} GajiPro. All rights reserved.
            </footer>
        </div>
    </div>

    {{-- Toast Container --}}
    <x-toast />

    {{-- Confirm Dialog (Global) --}}
    <x-confirm-dialog />

    @stack('scripts')
</body>
</html>
