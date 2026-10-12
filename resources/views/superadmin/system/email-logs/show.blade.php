@extends('superadmin.layouts.app')

@section('title', 'Email Detail')

@section('breadcrumb')
    <a href="{{ route('superadmin.system.email-logs.index') }}" class="text-slate-500 hover:text-primary-600">Email Logs</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Detail #{{ $emailLog->id }}</span>
@endsection

@section('header')
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Email Detail</h1>
            <p class="text-secondary-600">{{ $emailLog->subject }}</p>
        </div>
        <div class="flex gap-2">
            <form method="POST" action="{{ route('superadmin.system.email-logs.resend', $emailLog) }}">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Kirim ulang email ini?')">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    Kirim Ulang
                </button>
            </form>
            <a href="{{ route('superadmin.system.email-logs.index') }}" class="btn btn-secondary btn-sm">Kembali</a>
        </div>
    </div>
@endsection

@section('content')
    {{-- Email Info --}}
    <div class="card mb-6">
        <div class="card-header">
            <h3 class="card-title">Informasi Email</h3>
        </div>
        <div class="card-body p-0">
            <table class="w-full text-sm">
                <tr class="border-b border-slate-100">
                    <td class="px-6 py-3 font-medium text-secondary-600 w-1/4">Status</td>
                    <td class="px-6 py-3">
                        @if($emailLog->status === 'sent')
                            <x-badge type="success">Sent</x-badge>
                        @elseif($emailLog->status === 'failed')
                            <x-badge type="danger">Failed</x-badge>
                        @else
                            <x-badge type="warning">Sending</x-badge>
                        @endif
                    </td>
                </tr>
                <tr class="border-b border-slate-100">
                    <td class="px-6 py-3 font-medium text-secondary-600">Dari</td>
                    <td class="px-6 py-3 text-secondary-900">{{ $emailLog->from ?? '-' }}</td>
                </tr>
                <tr class="border-b border-slate-100">
                    <td class="px-6 py-3 font-medium text-secondary-600">Kepada</td>
                    <td class="px-6 py-3 text-secondary-900">{{ $emailLog->to }}</td>
                </tr>
                @if($emailLog->cc)
                    <tr class="border-b border-slate-100">
                        <td class="px-6 py-3 font-medium text-secondary-600">CC</td>
                        <td class="px-6 py-3 text-secondary-900">{{ $emailLog->cc }}</td>
                    </tr>
                @endif
                @if($emailLog->bcc)
                    <tr class="border-b border-slate-100">
                        <td class="px-6 py-3 font-medium text-secondary-600">BCC</td>
                        <td class="px-6 py-3 text-secondary-900">{{ $emailLog->bcc }}</td>
                    </tr>
                @endif
                <tr class="border-b border-slate-100">
                    <td class="px-6 py-3 font-medium text-secondary-600">Subject</td>
                    <td class="px-6 py-3 text-secondary-900 font-medium">{{ $emailLog->subject }}</td>
                </tr>
                <tr class="border-b border-slate-100">
                    <td class="px-6 py-3 font-medium text-secondary-600">Mailer</td>
                    <td class="px-6 py-3 text-secondary-900">{{ $emailLog->mailer ?? '-' }}</td>
                </tr>
                @if($emailLog->mailable_class)
                    <tr class="border-b border-slate-100">
                        <td class="px-6 py-3 font-medium text-secondary-600">Mailable Class</td>
                        <td class="px-6 py-3 text-secondary-900 font-mono text-xs">{{ $emailLog->mailable_class }}</td>
                    </tr>
                @endif
                @if($emailLog->provider_message_id)
                    <tr class="border-b border-slate-100">
                        <td class="px-6 py-3 font-medium text-secondary-600">Message ID</td>
                        <td class="px-6 py-3 text-secondary-900 font-mono text-xs">{{ $emailLog->provider_message_id }}</td>
                    </tr>
                @endif
                <tr class="border-b border-slate-100">
                    <td class="px-6 py-3 font-medium text-secondary-600">Dikirim Pada</td>
                    <td class="px-6 py-3 text-secondary-900">{{ $emailLog->sent_at ? $emailLog->sent_at->translatedFormat('d F Y H:i:s') : '-' }}</td>
                </tr>
                <tr>
                    <td class="px-6 py-3 font-medium text-secondary-600">Dibuat Pada</td>
                    <td class="px-6 py-3 text-secondary-900">{{ $emailLog->created_at->translatedFormat('d F Y H:i:s') }}</td>
                </tr>
            </table>
        </div>
    </div>

    @if($emailLog->error_message)
        <div class="card mb-6">
            <div class="card-header">
                <h3 class="card-title" style="color: #dc2626;">Error</h3>
            </div>
            <div class="card-body">
                <pre class="text-sm bg-red-50 text-red-800 p-4 rounded-lg overflow-x-auto whitespace-pre-wrap">{{ $emailLog->error_message }}</pre>
            </div>
        </div>
    @endif

    {{-- Email Body Preview --}}
    @if($emailLog->body)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Preview Email</h3>
            </div>
            <div class="card-body">
                <div class="bg-white border border-slate-200 rounded-lg p-6 max-h-96 overflow-y-auto">
                    {!! $emailLog->body !!}
                </div>
            </div>
        </div>
    @endif
@endsection
