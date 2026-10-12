@extends('superadmin.layouts.app')

@section('title', 'Payment Gateways')

@section('breadcrumb')
    <span class="text-secondary-900 font-medium">Payment Gateways</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Payment Gateways</h1>
            <p class="text-secondary-500 mt-1">Konfigurasi gateway pembayaran</p>
        </div>
        <a href="{{ route('superadmin.payment-gateways.create') }}" class="btn btn-primary">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Tambah Gateway
        </a>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-body-sm">
            <x-table>
                <x-slot name="header">
                    <th class="px-6 py-3 text-left">Gateway</th>
                    <th class="px-6 py-3 text-left">Environment</th>
                    <th class="px-6 py-3 text-center">Status</th>
                    <th class="px-6 py-3 text-center">Aksi</th>
                </x-slot>

                @forelse($gateways as $gateway)
                    <tr class="hover:bg-secondary-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-secondary-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-secondary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium text-secondary-900">{{ $gateway->name }}</p>
                                    <p class="text-sm text-secondary-500">{{ $gateway->gateway }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <x-badge :type="$gateway->environment === 'production' ? 'success' : 'warning'">
                                {{ ucfirst($gateway->environment) }}
                            </x-badge>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <x-badge :type="$gateway->is_active ? 'success' : 'secondary'">
                                {{ $gateway->is_active ? 'Aktif' : 'Nonaktif' }}
                            </x-badge>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('superadmin.payment-gateways.edit', $gateway) }}" class="btn btn-ghost btn-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                <form action="{{ route('superadmin.payment-gateways.toggle-status', $gateway) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-ghost btn-sm" title="{{ $gateway->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                        @if($gateway->is_active)
                                            <svg class="w-4 h-4 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                            </svg>
                                        @else
                                            <svg class="w-4 h-4 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        @endif
                                    </button>
                                </form>
                                <button type="button"
                                        @click="$dispatch('confirm-dialog', {
                                            title: 'Hapus Gateway',
                                            message: 'Apakah Anda yakin ingin menghapus gateway {{ $gateway->name }}?',
                                            confirmText: 'Ya, Hapus',
                                            type: 'danger',
                                            formAction: '{{ route('superadmin.payment-gateways.destroy', $gateway) }}'
                                        })"
                                        class="btn btn-ghost btn-sm text-danger-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-secondary-500">
                            Belum ada payment gateway yang dikonfigurasi
                        </td>
                    </tr>
                @endforelse
            </x-table>
        </div>

        @if($gateways->hasPages())
            <div class="card-footer">
                {{ $gateways->links() }}
            </div>
        @endif
    </div>
@endsection
