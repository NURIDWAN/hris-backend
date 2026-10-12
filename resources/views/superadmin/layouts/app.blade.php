<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') - GajiPro Superadmin</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .superadmin-logo-text {
            font-size: 18px !important;
            font-weight: 700 !important;
            color: #ffffff !important;
        }
    </style>
</head>
<body class="font-sans antialiased bg-slate-50">
    <div x-data="{ sidebarOpen: false }" class="min-h-screen flex">

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

        {{-- Sidebar --}}
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
               class="fixed inset-y-0 left-0 z-50 w-64 bg-gradient-to-b from-slate-900 via-slate-800 to-slate-900 transform transition-all duration-300 ease-in-out lg:static lg:inset-auto flex flex-col">

            {{-- Logo --}}
            <div style="height: 64px; display: flex; align-items: center; justify-content: space-between; padding: 0 16px; border-bottom: 1px solid #334155;">
                <a href="{{ route('superadmin.dashboard') }}" style="display: flex; align-items: center; gap: 12px; text-decoration: none;">
                    <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <svg style="width: 24px; height: 24px;" fill="none" stroke="white" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <span class="superadmin-logo-text">Superadmin</span>
                </a>
                <button @click="sidebarOpen = false" class="lg:hidden" style="color: #94a3b8;">
                    <svg style="width: 24px; height: 24px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
                {{-- Dashboard --}}
                <a href="{{ route('superadmin.dashboard') }}" class="sidebar-link {{ request()->routeIs('superadmin.dashboard') ? 'active' : '' }}">
                    <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    <span>Dashboard</span>
                </a>

                {{-- Section Label --}}
                <div class="!mt-6 !mb-3 px-3">
                    <span class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">Manajemen</span>
                </div>

                {{-- Plans --}}
                <a href="{{ route('superadmin.plans.index') }}" class="sidebar-link {{ request()->routeIs('superadmin.plans.*') ? 'active' : '' }}">
                    <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    <span>Plans</span>
                </a>

                {{-- Subscriptions --}}
                <a href="{{ route('superadmin.subscriptions.index') }}" class="sidebar-link {{ request()->routeIs('superadmin.subscriptions.*') ? 'active' : '' }}">
                    <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                    <span>Subscriptions</span>
                </a>

                {{-- Companies --}}
                <a href="{{ route('superadmin.companies.index') }}" class="sidebar-link {{ request()->routeIs('superadmin.companies.*') ? 'active' : '' }}">
                    <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    <span>Perusahaan</span>
                </a>

                {{-- Section Label --}}
                <div class="!mt-6 !mb-3 px-3">
                    <span class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">Pembayaran</span>
                </div>

                {{-- Payment Gateways --}}
                <a href="{{ route('superadmin.payment-gateways.index') }}" class="sidebar-link {{ request()->routeIs('superadmin.payment-gateways.*') ? 'active' : '' }}">
                    <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    <span>Payment Gateways</span>
                </a>

                {{-- Payments --}}
                <a href="{{ route('superadmin.payments.index') }}" class="sidebar-link {{ request()->routeIs('superadmin.payments.*') ? 'active' : '' }}">
                    <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    <span>Riwayat Pembayaran</span>
                </a>

                {{-- Section Label --}}
                <div class="!mt-6 !mb-3 px-3">
                    <span class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">Sistem</span>
                </div>

                {{-- System Health --}}
                <a href="{{ route('superadmin.system.health') }}" class="sidebar-link {{ request()->routeIs('superadmin.system.health') ? 'active' : '' }}">
                    <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    <span>System Health</span>
                </a>

                {{-- Queue Monitor --}}
                <a href="{{ route('superadmin.system.queue.index') }}" class="sidebar-link {{ request()->routeIs('superadmin.system.queue.*') ? 'active' : '' }}">
                    <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span>Queue Monitor</span>
                    @php
                        try {
                            $failedJobCount = \Illuminate\Support\Facades\Schema::hasTable('failed_jobs')
                                ? \Illuminate\Support\Facades\DB::table('failed_jobs')->count()
                                : 0;
                        } catch (\Exception $e) {
                            $failedJobCount = 0;
                        }
                    @endphp
                    @if($failedJobCount > 0)
                        <span class="ml-auto bg-danger-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">{{ $failedJobCount }}</span>
                    @endif
                </a>

                {{-- Email Logs --}}
                <a href="{{ route('superadmin.system.email-logs.index') }}" class="sidebar-link {{ request()->routeIs('superadmin.system.email-logs.*') ? 'active' : '' }}">
                    <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <span>Email Logs</span>
                </a>

                {{-- Notifications --}}
                <a href="{{ route('superadmin.system.notifications.index') }}" class="sidebar-link {{ request()->routeIs('superadmin.system.notifications.*') ? 'active' : '' }}">
                    <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    <span>Notifications</span>
                </a>

                {{-- Audit Logs --}}
                <a href="{{ route('superadmin.system.audit-logs.index') }}" class="sidebar-link {{ request()->routeIs('superadmin.system.audit-logs.*') ? 'active' : '' }}">
                    <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                    <span>Audit Logs</span>
                </a>

                {{-- Sessions --}}
                <a href="{{ route('superadmin.system.sessions.index') }}" class="sidebar-link {{ request()->routeIs('superadmin.system.sessions.*') ? 'active' : '' }}">
                    <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Sessions</span>
                </a>

                {{-- Rate Limits --}}
                <a href="{{ route('superadmin.system.rate-limits.index') }}" class="sidebar-link {{ request()->routeIs('superadmin.system.rate-limits.*') ? 'active' : '' }}">
                    <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Rate Limits</span>
                    @php
                        try {
                            $rateLimitCount = \Illuminate\Support\Facades\Schema::hasTable('rate_limit_logs')
                                ? \App\Models\RateLimitLog::recent(24)->count()
                                : 0;
                        } catch (\Exception $e) {
                            $rateLimitCount = 0;
                        }
                    @endphp
                    @if($rateLimitCount > 0)
                        <span class="ml-auto bg-warning-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">{{ $rateLimitCount }}</span>
                    @endif
                </a>

                {{-- Section Label --}}
                <div class="!mt-6 !mb-3 px-3">
                    <span class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">Security</span>
                </div>

                {{-- Attack Logs --}}
                <a href="{{ route('superadmin.security.logs.index') }}" class="sidebar-link {{ request()->routeIs('superadmin.security.logs.*') ? 'active' : '' }}">
                    <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>Attack Logs</span>
                    @php
                        try {
                            $criticalCount = \Illuminate\Support\Facades\Schema::hasTable('security_logs')
                                ? \App\Models\SecurityLog::recent(24)->where('severity', 'critical')->count()
                                : 0;
                        } catch (\Exception $e) {
                            $criticalCount = 0;
                        }
                    @endphp
                    @if($criticalCount > 0)
                        <span class="ml-auto bg-danger-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">{{ $criticalCount }}</span>
                    @endif
                </a>

                {{-- Blocked IPs --}}
                <a href="{{ route('superadmin.security.blocked-ips.index') }}" class="sidebar-link {{ request()->routeIs('superadmin.security.blocked-ips.*') ? 'active' : '' }}">
                    <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                    <span>Blocked IPs</span>
                    @php
                        try {
                            $blockedCount = \Illuminate\Support\Facades\Schema::hasTable('blocked_ips')
                                ? \App\Models\BlockedIp::active()->count()
                                : 0;
                        } catch (\Exception $e) {
                            $blockedCount = 0;
                        }
                    @endphp
                    @if($blockedCount > 0)
                        <span class="ml-auto bg-secondary-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">{{ $blockedCount }}</span>
                    @endif
                </a>
            </nav>

            {{-- User Info + Logout --}}
            <div class="p-3 border-t border-slate-700">
                <div class="flex items-center gap-3 px-3 py-2">
                    <div class="w-10 h-10 bg-amber-500 rounded-lg flex items-center justify-center text-white text-sm font-semibold">
                        {{ substr(auth()->user()->name ?? 'S', 0, 1) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name ?? 'Superadmin' }}</p>
                        <p class="text-xs text-slate-400 truncate">Superadmin</p>
                    </div>
                    <form method="POST" action="{{ route('superadmin.logout') }}">
                        @csrf
                        <button type="submit" class="p-2 text-slate-400 hover:text-white hover:bg-slate-700 rounded-lg transition-colors" title="Logout">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        {{-- Main Content --}}
        <div class="flex-1 flex flex-col min-h-screen bg-slate-100/70">
            {{-- Top Header --}}
            <header class="h-16 bg-white/80 backdrop-blur-sm border-b border-slate-200/60 flex items-center justify-between px-4 lg:px-6 sticky top-0 z-30 shadow-sm">
                {{-- Left: Mobile menu + Breadcrumb --}}
                <div class="flex items-center gap-4">
                    <button @click="sidebarOpen = true" class="lg:hidden text-slate-500 hover:text-slate-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>

                    {{-- Breadcrumb --}}
                    <nav class="hidden sm:flex items-center gap-2 text-sm">
                        <a href="{{ route('superadmin.dashboard') }}" class="text-slate-500 hover:text-primary-600">Dashboard</a>
                        @hasSection('breadcrumb')
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            @yield('breadcrumb')
                        @endif
                    </nav>
                </div>

                {{-- Right: Logout --}}
                <div class="flex items-center gap-3">
                    <form method="POST" action="{{ route('superadmin.logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-ghost btn-sm text-slate-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            <span class="hidden sm:inline ml-2">Logout</span>
                        </button>
                    </form>
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
                &copy; {{ date('Y') }} GajiPro Superadmin Panel. All rights reserved.
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
