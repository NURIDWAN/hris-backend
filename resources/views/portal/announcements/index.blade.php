@extends('layouts.portal')

@section('title', 'Pengumuman')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Pengumuman</span>
@endsection

@section('header')
    <div>
        <h1 class="text-2xl font-bold text-secondary-900">Pengumuman</h1>
        <p class="text-secondary-500 mt-1">Informasi dan pengumuman terbaru dari perusahaan.</p>
    </div>
@endsection

@section('content')
    <div class="space-y-4">
        @forelse($announcements as $announcement)
            <a href="{{ route('portal.announcements.show', $announcement) }}" class="card block hover:shadow-md transition-shadow">
                <div class="card-body">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            @if($announcement->priority == 'urgent')
                                <div class="w-10 h-10 rounded-full bg-danger-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-danger-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                </div>
                            @elseif($announcement->priority == 'high')
                                <div class="w-10 h-10 rounded-full bg-warning-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                            @else
                                <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                                    </svg>
                                </div>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                @if($announcement->is_pinned)
                                    <svg class="w-4 h-4 text-primary-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 2a1 1 0 011 1v1.323l3.954 1.582 1.599-.8a1 1 0 01.894 1.79l-1.233.616 1.738 5.42a1 1 0 01-.285 1.05A3.989 3.989 0 0115 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.715-5.349L10 6.477 6.237 7.582l1.715 5.349a1 1 0 01-.285 1.05A3.989 3.989 0 015 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.738-5.42-1.233-.617a1 1 0 01.894-1.788l1.599.799L9 4.323V3a1 1 0 011-1z"/>
                                    </svg>
                                @endif
                                <h3 class="text-lg font-semibold text-secondary-900">{{ $announcement->title }}</h3>
                                <x-badge type="{{ match($announcement->priority) { 'urgent' => 'danger', 'high' => 'warning', 'normal' => 'info', default => 'secondary' } }}">
                                    {{ $announcement->priority_label }}
                                </x-badge>
                            </div>
                            <p class="text-secondary-600 line-clamp-2">{{ Str::limit($announcement->content, 150) }}</p>
                            <div class="mt-2 flex items-center gap-4 text-sm text-secondary-500">
                                <span>{{ $announcement->published_at?->format('d M Y') }}</span>
                                @if($announcement->isReadBy(auth()->id()))
                                    <span class="flex items-center gap-1 text-success-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Sudah dibaca
                                    </span>
                                @else
                                    <span class="flex items-center gap-1 text-primary-600">
                                        <span class="w-2 h-2 bg-primary-500 rounded-full"></span>
                                        Baru
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="flex-shrink-0">
                            <svg class="w-5 h-5 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </a>
        @empty
            <div class="card">
                <div class="card-body text-center py-12">
                    <svg class="w-16 h-16 mx-auto text-secondary-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                    </svg>
                    <p class="text-secondary-500">Belum ada pengumuman untuk Anda.</p>
                </div>
            </div>
        @endforelse
    </div>

    @if($announcements->hasPages())
        <div class="mt-6">
            {{ $announcements->links() }}
        </div>
    @endif
@endsection
