@extends('superadmin.layouts.app')

@section('title', 'Kelola Subscriptions')

@section('breadcrumb')
    <span class="text-secondary-900 font-medium">Subscriptions</span>
@endsection

@section('header')
    <h1 class="text-2xl font-bold text-secondary-900">Kelola Subscriptions</h1>
    <p class="text-secondary-500 mt-1">Daftar langganan perusahaan</p>
@endsection

@section('content')
    {{-- Filter Card --}}
    <div class="card mb-6">
        <div class="card-body-sm">
            <form action="{{ route('superadmin.subscriptions.index') }}" method="GET" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <input type="text" name="search" value="{{ request('search') }}"
                           class="input w-full" placeholder="Cari perusahaan...">
                </div>
                <div class="w-40">
                    <select name="status" class="input w-full">
                        <option value="">Semua Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Filter</button>
                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('superadmin.subscriptions.index') }}" class="btn btn-secondary">Reset</a>
                @endif
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body-sm">
            <x-table>
                <x-slot name="header">
                    <th class="px-6 py-3 text-left">Perusahaan</th>
                    <th class="px-6 py-3 text-left">Plan</th>
                    <th class="px-6 py-3 text-left">Mulai</th>
                    <th class="px-6 py-3 text-left">Berakhir</th>
                    <th class="px-6 py-3 text-center">Status</th>
                    <th class="px-6 py-3 text-center">Aksi</th>
                </x-slot>

                @forelse($subscriptions as $subscription)
                    <tr class="hover:bg-secondary-50">
                        <td class="px-6 py-4">
                            <p class="font-medium text-secondary-900">{{ $subscription->company->name ?? 'N/A' }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-secondary-900">{{ $subscription->plan->name ?? 'N/A' }}</p>
                        </td>
                        <td class="px-6 py-4">
                            {{ $subscription->started_at ? $subscription->started_at->format('d M Y') : '-' }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $subscription->ends_at ? $subscription->ends_at->format('d M Y') : '-' }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <x-badge :type="$subscription->status === 'active' ? 'success' : ($subscription->status === 'pending' ? 'warning' : 'danger')">
                                {{ ucfirst($subscription->status) }}
                            </x-badge>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('superadmin.subscriptions.show', $subscription) }}" class="btn btn-ghost btn-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-secondary-500">
                            Belum ada subscription
                        </td>
                    </tr>
                @endforelse
            </x-table>
        </div>

        @if($subscriptions->hasPages())
            <div class="card-footer">
                {{ $subscriptions->links() }}
            </div>
        @endif
    </div>
@endsection
