@extends('layouts.portal')

@section('title', $announcement->title)

@section('breadcrumb')
    <a href="{{ route('portal.announcements.index') }}" class="text-primary-600 hover:text-primary-700">Pengumuman</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Detail</span>
@endsection

@section('header')
    <div class="flex items-center gap-4">
        <a href="{{ route('portal.announcements.index') }}" class="btn btn-ghost">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Detail Pengumuman</h1>
        </div>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="flex items-center gap-2">
                @if($announcement->is_pinned)
                    <svg class="w-5 h-5 text-primary-500" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 2a1 1 0 011 1v1.323l3.954 1.582 1.599-.8a1 1 0 01.894 1.79l-1.233.616 1.738 5.42a1 1 0 01-.285 1.05A3.989 3.989 0 0115 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.715-5.349L10 6.477 6.237 7.582l1.715 5.349a1 1 0 01-.285 1.05A3.989 3.989 0 015 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.738-5.42-1.233-.617a1 1 0 01.894-1.788l1.599.799L9 4.323V3a1 1 0 011-1z"/>
                    </svg>
                @endif
                <h3 class="card-title text-xl">{{ $announcement->title }}</h3>
            </div>
            <div class="flex items-center gap-4 mt-2">
                <x-badge type="{{ match($announcement->priority) { 'urgent' => 'danger', 'high' => 'warning', 'normal' => 'info', default => 'secondary' } }}">
                    {{ $announcement->priority_label }}
                </x-badge>
                <span class="text-sm text-secondary-500">
                    Dipublikasikan {{ $announcement->published_at?->format('d M Y H:i') }}
                </span>
            </div>
        </div>
        <div class="card-body">
            <div class="prose max-w-none">
                {!! nl2br(e($announcement->content)) !!}
            </div>
        </div>
        <div class="card-footer bg-secondary-50">
            <div class="flex items-center gap-2 text-sm text-secondary-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <span>Diposting oleh {{ $announcement->creator->name ?? 'Admin' }}</span>
            </div>
        </div>
    </div>
@endsection
