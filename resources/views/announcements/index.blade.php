@extends('layouts.admin')

@section('title', 'Pengumuman')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Pengumuman</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Pengumuman</h1>
            <p class="text-secondary-500 mt-1">Kelola pengumuman untuk karyawan.</p>
        </div>
        <div>
            <a href="{{ route('announcements.create') }}" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Buat Pengumuman
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="card">
        <x-table>
            <x-slot name="header">
                <th>Judul</th>
                <th>Prioritas</th>
                <th>Target</th>
                <th>Status</th>
                <th>Dibuat</th>
                <th class="text-right">Aksi</th>
            </x-slot>

            @forelse($announcements as $announcement)
                <tr>
                    <td>
                        <div class="flex items-center gap-2">
                            @if($announcement->is_pinned)
                                <svg class="w-4 h-4 text-primary-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 2a1 1 0 011 1v1.323l3.954 1.582 1.599-.8a1 1 0 01.894 1.79l-1.233.616 1.738 5.42a1 1 0 01-.285 1.05A3.989 3.989 0 0115 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.715-5.349L10 6.477 6.237 7.582l1.715 5.349a1 1 0 01-.285 1.05A3.989 3.989 0 015 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.738-5.42-1.233-.617a1 1 0 01.894-1.788l1.599.799L9 4.323V3a1 1 0 011-1zm-5 8.274l-.818 2.552c.25.112.526.174.818.174.292 0 .569-.062.818-.174L5 10.274zm10 0l-.818 2.552c.25.112.526.174.818.174.292 0 .569-.062.818-.174L15 10.274zM10 18a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1z"/>
                                </svg>
                            @endif
                            <div>
                                <p class="font-medium text-secondary-900">{{ $announcement->title }}</p>
                                <p class="text-sm text-secondary-500">{{ Str::limit($announcement->content, 50) }}</p>
                            </div>
                        </div>
                    </td>
                    <td>
                        <x-badge type="{{ match($announcement->priority) { 'urgent' => 'danger', 'high' => 'warning', 'normal' => 'info', default => 'secondary' } }}">
                            {{ $announcement->priority_label }}
                        </x-badge>
                    </td>
                    <td>
                        <span class="text-sm text-secondary-600">{{ $announcement->target_label }}</span>
                    </td>
                    <td>
                        @if($announcement->is_published)
                            <x-badge type="success">Dipublikasikan</x-badge>
                        @else
                            <x-badge type="secondary">Draft</x-badge>
                        @endif
                    </td>
                    <td>
                        <div>
                            <p class="text-sm text-secondary-900">{{ $announcement->created_at->format('d M Y') }}</p>
                            <p class="text-xs text-secondary-500">{{ $announcement->creator->name ?? '-' }}</p>
                        </div>
                    </td>
                    <td class="text-right">
                        <div class="flex items-center justify-end gap-2">
                            @if(!$announcement->is_published)
                                <form action="{{ route('announcements.publish', $announcement) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="btn btn-ghost btn-sm text-success-600" title="Publikasikan">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </button>
                                </form>
                            @else
                                <form action="{{ route('announcements.unpublish', $announcement) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="btn btn-ghost btn-sm text-warning-600" title="Unpublish">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                        </svg>
                                    </button>
                                </form>
                            @endif
                            <a href="{{ route('announcements.edit', $announcement) }}" class="btn btn-ghost btn-sm" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>
                            <button type="button"
                                    @click="$dispatch('confirm-dialog', {
                                        title: 'Hapus Pengumuman',
                                        message: 'Apakah Anda yakin ingin menghapus pengumuman ini?',
                                        confirmText: 'Ya, Hapus',
                                        type: 'danger',
                                        formAction: '{{ route('announcements.destroy', $announcement) }}'
                                    })"
                                    class="btn btn-ghost btn-sm text-danger-600" title="Hapus">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center py-8 text-secondary-500">
                        Belum ada pengumuman.
                    </td>
                </tr>
            @endforelse
        </x-table>

        @if($announcements->hasPages())
            <div class="card-footer">
                {{ $announcements->links() }}
            </div>
        @endif
    </div>
@endsection
