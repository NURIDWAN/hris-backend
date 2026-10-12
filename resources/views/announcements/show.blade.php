@extends('layouts.admin')

@section('title', 'Detail Pengumuman')

@section('breadcrumb')
    <a href="{{ route('announcements.index') }}" class="text-primary-600 hover:text-primary-700">Pengumuman</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Detail</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('announcements.index') }}" class="btn btn-ghost">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">Detail Pengumuman</h1>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('announcements.edit', $announcement) }}" class="btn btn-secondary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="card">
                <div class="card-header">
                    <div class="flex items-center gap-2">
                        @if($announcement->is_pinned)
                            <svg class="w-5 h-5 text-primary-500" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 2a1 1 0 011 1v1.323l3.954 1.582 1.599-.8a1 1 0 01.894 1.79l-1.233.616 1.738 5.42a1 1 0 01-.285 1.05A3.989 3.989 0 0115 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.715-5.349L10 6.477 6.237 7.582l1.715 5.349a1 1 0 01-.285 1.05A3.989 3.989 0 015 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.738-5.42-1.233-.617a1 1 0 01.894-1.788l1.599.799L9 4.323V3a1 1 0 011-1z"/>
                            </svg>
                        @endif
                        <h3 class="card-title">{{ $announcement->title }}</h3>
                    </div>
                </div>
                <div class="card-body">
                    <div class="prose max-w-none">
                        {!! nl2br(e($announcement->content)) !!}
                    </div>
                </div>
            </div>

            @if($announcement->readers->count() > 0)
                <div class="card mt-6">
                    <div class="card-header">
                        <h3 class="card-title">Dibaca oleh ({{ $announcement->readers->count() }})</h3>
                    </div>
                    <div class="card-body">
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            @foreach($announcement->readers as $reader)
                                <div class="flex items-center gap-2 p-2 bg-secondary-50 rounded-lg">
                                    <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center">
                                        <span class="text-xs font-medium text-primary-600">{{ substr($reader->name, 0, 2) }}</span>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-secondary-900 truncate">{{ $reader->name }}</p>
                                        <p class="text-xs text-secondary-500">{{ $reader->pivot->read_at ? \Carbon\Carbon::parse($reader->pivot->read_at)->format('d M Y H:i') : '-' }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Informasi</h3>
                </div>
                <div class="card-body space-y-4">
                    <div>
                        <p class="text-sm text-secondary-500">Status</p>
                        @if($announcement->is_published)
                            <x-badge type="success">Dipublikasikan</x-badge>
                        @else
                            <x-badge type="secondary">Draft</x-badge>
                        @endif
                    </div>

                    <div>
                        <p class="text-sm text-secondary-500">Prioritas</p>
                        <x-badge type="{{ match($announcement->priority) { 'urgent' => 'danger', 'high' => 'warning', 'normal' => 'info', default => 'secondary' } }}">
                            {{ $announcement->priority_label }}
                        </x-badge>
                    </div>

                    <div>
                        <p class="text-sm text-secondary-500">Target</p>
                        <p class="font-medium text-secondary-900">{{ $announcement->target_label }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-secondary-500">Dibuat oleh</p>
                        <p class="font-medium text-secondary-900">{{ $announcement->creator->name ?? '-' }}</p>
                        <p class="text-xs text-secondary-500">{{ $announcement->created_at->format('d M Y H:i') }}</p>
                    </div>

                    @if($announcement->published_at)
                        <div>
                            <p class="text-sm text-secondary-500">Dipublikasikan pada</p>
                            <p class="font-medium text-secondary-900">{{ $announcement->published_at->format('d M Y H:i') }}</p>
                        </div>
                    @endif

                    @if($announcement->expires_at)
                        <div>
                            <p class="text-sm text-secondary-500">Kedaluwarsa pada</p>
                            <p class="font-medium text-secondary-900 {{ $announcement->is_expired ? 'text-danger-600' : '' }}">
                                {{ $announcement->expires_at->format('d M Y H:i') }}
                                @if($announcement->is_expired)
                                    <span class="text-xs">(Sudah kedaluwarsa)</span>
                                @endif
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-body space-y-2">
                    @if(!$announcement->is_published)
                        <form action="{{ route('announcements.publish', $announcement) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-success w-full">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Publikasikan
                            </button>
                        </form>
                    @else
                        <form action="{{ route('announcements.unpublish', $announcement) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-warning w-full">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                </svg>
                                Unpublish
                            </button>
                        </form>
                    @endif

                    <button type="button"
                            @click="$dispatch('confirm-dialog', {
                                title: 'Hapus Pengumuman',
                                message: 'Apakah Anda yakin ingin menghapus pengumuman ini?',
                                confirmText: 'Ya, Hapus',
                                type: 'danger',
                                formAction: '{{ route('announcements.destroy', $announcement) }}'
                            })"
                            class="btn btn-danger w-full">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection
