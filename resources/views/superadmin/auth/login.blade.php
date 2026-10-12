<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Superadmin Login - GajiPro</title>
    <meta name="description" content="Masuk ke panel Superadmin GajiPro.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            min-height: 100vh;
        }
    </style>
</head>
<body class="font-sans antialiased" x-data="{ demoEmail: 'superadmin@gajipro.com', demoPassword: 'password' }">
    <div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; position: relative;">
        {{-- Background Decorations --}}
        <div style="position: absolute; inset: 0; overflow: hidden; pointer-events: none;">
            <div style="position: absolute; top: -96px; left: -96px; width: 384px; height: 384px; background: rgba(245, 158, 11, 0.1); border-radius: 50%; filter: blur(64px);"></div>
            <div style="position: absolute; bottom: -96px; right: -96px; width: 384px; height: 384px; background: rgba(249, 115, 22, 0.1); border-radius: 50%; filter: blur(64px);"></div>
        </div>

        <div style="position: relative; z-index: 10; width: 100%; max-width: 448px;">
            {{-- Logo --}}
            <div style="text-align: center; margin-bottom: 32px;">
                <a href="{{ url('/') }}" style="display: inline-flex; align-items: center; gap: 12px; text-decoration: none;">
                    <div style="width: 56px; height: 56px; background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%); border-radius: 16px; display: flex; align-items: center; justify-content: center;">
                        <svg style="width: 32px; height: 32px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <span style="font-size: 24px; font-weight: 700; color: white;">Superadmin</span>
                </a>
            </div>

            {{-- Login Card --}}
            <div style="background: white; border-radius: 16px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); padding: 32px;">
                <div style="text-align: center; margin-bottom: 24px;">
                    <h2 style="font-size: 24px; font-weight: 700; color: #1e293b; margin-bottom: 8px;">Superadmin Login</h2>
                    <p style="color: #64748b;">Masuk ke panel administrasi</p>
                </div>

                <form method="POST" action="{{ route('superadmin.login') }}">
                    @csrf

                    {{-- Email --}}
                    <div style="margin-bottom: 20px;">
                        <label for="email" style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 8px;">Email</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                               class="input w-full @error('email') border-danger-500 @enderror"
                               placeholder="superadmin@gajipro.com">
                        @error('email')
                            <p style="margin-top: 4px; font-size: 14px; color: #ef4444;">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Password --}}
                    <div x-data="{ show: false }" style="margin-bottom: 24px;">
                        <label for="password" style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 8px;">Password</label>
                        <div style="position: relative;">
                            <input :type="show ? 'text' : 'password'" id="password" name="password" required
                                   class="input w-full @error('password') border-danger-500 @enderror"
                                   style="padding-right: 48px;"
                                   placeholder="Masukkan password">
                            <button type="button" @click="show = !show"
                                    style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #9ca3af;"
                                    onmouseover="this.style.color='#4b5563'" onmouseout="this.style.color='#9ca3af'">
                                <svg x-show="!show" style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <svg x-show="show" x-cloak style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                            </button>
                        </div>
                        @error('password')
                            <p style="margin-top: 4px; font-size: 14px; color: #ef4444;">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Submit --}}
                    <button type="submit" style="width: 100%; padding: 14px 24px; background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%); color: white; font-weight: 600; border-radius: 12px; border: none; cursor: pointer; box-shadow: 0 10px 15px -3px rgba(245, 158, 11, 0.25); transition: all 0.2s;"
                            onmouseover="this.style.background='linear-gradient(135deg, #d97706 0%, #c2410c 100%)'"
                            onmouseout="this.style.background='linear-gradient(135deg, #f59e0b 0%, #ea580c 100%)'">
                        <span style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                            <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                            </svg>
                            Masuk
                        </span>
                    </button>
                </form>

                {{-- Demo Account --}}
                @if(config('app.show_demo_accounts', false))
                <div style="margin-top: 24px; padding-top: 24px; border-top: 1px solid #e2e8f0;">
                    <p style="font-size: 12px; color: #94a3b8; text-align: center; margin-bottom: 12px;">Demo Account</p>
                    <button type="button"
                            @click="document.getElementById('email').value = demoEmail; document.getElementById('password').value = demoPassword;"
                            style="width: 100%; text-align: left; padding: 12px 16px; background: linear-gradient(135deg, #fef3c7 0%, #fed7aa 100%); border: 1px solid #f59e0b; border-radius: 12px; cursor: pointer; transition: all 0.2s;"
                            onmouseover="this.style.borderColor='#d97706'; this.style.boxShadow='0 4px 12px rgba(245, 158, 11, 0.15)';"
                            onmouseout="this.style.borderColor='#f59e0b'; this.style.boxShadow='none';">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <span style="font-size: 14px; font-weight: 600; color: #92400e;">Super Admin</span>
                                <p style="font-size: 12px; color: #b45309; margin-top: 2px;">superadmin@gajipro.com</p>
                            </div>
                            <span style="font-size: 11px; background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%); color: white; padding: 4px 10px; border-radius: 9999px; font-weight: 500;">
                                Klik untuk isi
                            </span>
                        </div>
                    </button>
                </div>
                @endif

                {{-- Back to main site --}}
                <div style="margin-top: 24px; text-align: center;">
                    <a href="{{ url('/') }}" style="font-size: 14px; color: #64748b; text-decoration: none;"
                       onmouseover="this.style.color='#374151'" onmouseout="this.style.color='#64748b'">
                        &larr; Kembali ke halaman utama
                    </a>
                </div>
            </div>

            {{-- Security Note --}}
            <div style="margin-top: 24px; display: flex; align-items: center; justify-content: center; gap: 8px; color: #94a3b8; font-size: 14px;">
                <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <span>Akses terbatas untuk administrator</span>
            </div>
        </div>
    </div>
</body>
</html>
