<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Login ke Bakso Malang POS — sistem kasir restoran Bakso Malang.">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login — Bakso Malang POS</title>

    <!-- Google Fonts: Poppins — sama seperti admin -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS — sama konfigurasi dengan admin -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50:  '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        },
                        sidebar: {
                            DEFAULT: '#1e3a5f',
                            dark:    '#162d4a',
                            light:   '#2a4a73',
                        },
                    },
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif'],
                    },
                    borderRadius: {
                        '4xl': '2rem',
                        '5xl': '3rem',
                    },
                    keyframes: {
                        floatUp: {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%':      { transform: 'translateY(-10px)' },
                        },
                        blobMorph: {
                            '0%, 100%': { borderRadius: '60% 40% 55% 45% / 45% 55% 40% 60%' },
                            '33%':      { borderRadius: '50% 50% 40% 60% / 60% 40% 55% 45%' },
                            '66%':      { borderRadius: '40% 60% 50% 50% / 45% 60% 40% 55%' },
                        },
                        cardIn: {
                            from: { opacity: '0', transform: 'translateY(24px) scale(0.97)' },
                            to:   { opacity: '1', transform: 'translateY(0) scale(1)' },
                        },
                        fadeIn: {
                            from: { opacity: '0', transform: 'translateX(-6px)' },
                            to:   { opacity: '1', transform: 'translateX(0)' },
                        },
                    },
                    animation: {
                        'float-up':   'floatUp 5s ease-in-out infinite',
                        'blob-morph': 'blobMorph 10s ease-in-out infinite',
                        'card-in':    'cardIn 0.45s cubic-bezier(0.34,1.56,0.64,1) both',
                        'fade-in':    'fadeIn 0.35s ease both',
                    },
                }
            }
        }
    </script>

    @php
        $siteKey = config('services.turnstile.site_key');
    @endphp

    <!-- Cloudflare Turnstile -->
    @if($siteKey)
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif

    <style>
        /* Desain Asimetris Halus Sesuai Referensi Gambar: 
           Kiri-Atas (Rounded Lebih Lebar), Kanan-Atas (Rounded Halus), Kiri-Bawah (Rounded Halus), Kanan-Bawah (Rounded Lebih Lebar) */
        .card-asymmetric {
            border-top-left-radius: 2.5rem;
            border-top-right-radius: 1.25rem;
            border-bottom-left-radius: 1.25rem;
            border-bottom-right-radius: 2.5rem;
        }
        /* Radius panel kiri mengikuti card-asymmetric */
        .panel-left-radius {
            border-top-left-radius: 2.5rem;
            border-top-right-radius: 0px;
            border-bottom-left-radius: 1.25rem;
            border-bottom-right-radius: 0px;
        }
        /* Radius panel kanan mengikuti card-asymmetric */
        .panel-right-radius {
            border-top-left-radius: 0px;
            border-top-right-radius: 1.25rem;
            border-bottom-left-radius: 0px;
            border-bottom-right-radius: 2.5rem;
        }
        /* Spinner animasi */
        @keyframes spin { to { transform: rotate(360deg); } }
        .spinner { animation: spin 0.7s linear infinite; }
        /* Tombol loading */
        .btn-loading .btn-icon,
        .btn-loading .btn-label { display: none !important; }
        .btn-loading .spinner-wrap { display: flex !important; }
        .spinner-wrap { display: none; }
    </style>
</head>

<body class="min-h-screen bg-sidebar-dark flex items-center justify-center p-6 font-sans relative">

    {{-- Latar belakang dekoratif berpola aksen modern --}}
    <div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
        {{-- Pola Grid Halus --}}
        <div class="absolute inset-0 opacity-20 bg-[linear-gradient(to_right,#8080801a_1px,transparent_1px),linear-gradient(to_bottom,#8080801a_1px,transparent_1px)] bg-[size:32px_32px]"></div>
        
        {{-- Glow Orbs berwarna biru admin --}}
        <div class="absolute -top-40 -left-40 w-[600px] h-[600px] bg-primary-600/40 rounded-full blur-[120px]"></div>
        <div class="absolute -bottom-40 -right-40 w-[600px] h-[600px] bg-sidebar-light/50 rounded-full blur-[120px]"></div>
        <div class="absolute top-1/4 right-1/4 w-[500px] h-[500px] bg-primary-400/25 rounded-full blur-[100px]"></div>
        
        {{-- Aksen Geometris Lingkaran Besar --}}
        <div class="absolute top-[10%] left-[15%] w-96 h-96 border-2 border-white/[0.04] rounded-full"></div>
        <div class="absolute bottom-[10%] right-[15%] w-[500px] h-[500px] border-2 border-white/[0.03] rounded-full"></div>
    </div>

    {{-- ═══ CARD UTAMA (Ukuran diperbesar ke max-w-5xl) ═══ --}}
    <div class="card-asymmetric relative z-10 w-full max-w-5xl min-h-[580px] flex shadow-[0_25px_60px_-15px_rgba(0,0,0,0.7)] overflow-hidden bg-sidebar animate-card-in">

        {{-- ─── PANEL KIRI — Ilustrasi & Brand (Lebar disesuaikan) ─── --}}
        <div class="panel-left-radius hidden md:flex md:w-[48%] bg-primary-50 flex-col relative overflow-hidden flex-shrink-0">

            {{-- Dekorasi circles --}}
            <div class="absolute -top-16 -right-16 w-64 h-64 bg-primary-200 rounded-full opacity-50"></div>
            <div class="absolute -bottom-10 -left-10 w-48 h-48 bg-primary-200 rounded-full opacity-40"></div>
            <div class="absolute top-[30%] right-[6%] w-16 h-16 bg-primary-300 rounded-full opacity-60"></div>
            <div class="absolute bottom-[28%] right-[18%] w-10 h-10 bg-primary-400 rounded-full opacity-50"></div>
            <div class="absolute top-[18%] left-[10%] w-8 h-8 bg-primary-300 rounded-full opacity-60"></div>

            {{-- Brand badge —— pojok kiri atas --}}
            <div class="relative z-10 flex items-center gap-3.5 p-8">
                <div class="w-12 h-12 bg-sidebar rounded-xl flex items-center justify-center shadow-lg shadow-sidebar/30 flex-shrink-0">
                    <x-lucide name="soup" class="w-6 h-6 text-white" />
                </div>
                <div class="leading-tight">
                    <div class="text-base font-bold text-sidebar tracking-wide">Bakso Malang POS</div>
                    <div class="text-xs text-primary-600 font-medium">Sistem Kasir Restoran</div>
                </div>
            </div>

            {{-- Blob + Ilustrasi -- tengah --}}
            <div class="flex-1 flex items-center justify-center px-10 pb-8">
                <div class="relative flex items-center justify-center">
                    {{-- Blob background --}}
                    <div class="absolute w-80 h-72 bg-white animate-blob-morph shadow-lg shadow-primary-200/50"></div>
                    {{-- Ilustrasi --}}
                    <img
                        src="{{ asset('images/login-illustration.png') }}"
                        alt="Ilustrasi Bakso Malang POS"
                        class="relative z-10 w-64 h-64 object-contain animate-float-up drop-shadow-xl"
                    >
                </div>
            </div>

            {{-- Copyright --}}
            <div class="relative z-10 px-8 pb-6 text-xs text-primary-500 opacity-70">
                &copy; {{ date('Y') }} Bakso Malang POS
            </div>
        </div>

        {{-- ─── PANEL KANAN — Form Login ─── --}}
        <div class="panel-right-radius flex-1 bg-sidebar flex items-center justify-center px-10 py-14 relative overflow-hidden">

            {{-- Dekorasi glow --}}
            <div class="absolute -top-24 -right-24 w-72 h-72 bg-primary-600 opacity-10 rounded-full blur-2xl pointer-events-none"></div>
            <div class="absolute -bottom-20 -left-16 w-64 h-64 bg-primary-800 opacity-20 rounded-full blur-2xl pointer-events-none"></div>

            <div class="relative z-10 w-full max-w-md">

                {{-- Heading --}}
                <h1 class="text-4xl font-bold text-white tracking-tight mb-8">Login</h1>

                {{-- Error alert --}}
                @if($errors->any())
                <div class="flex items-start gap-3 bg-red-500/10 border border-red-500/30 rounded-xl px-4 py-3 mb-6 animate-fade-in" role="alert">
                    <x-lucide name="alert-circle" class="w-4 h-4 text-red-400 flex-shrink-0 mt-0.5" />
                    <span class="text-sm text-red-300 leading-relaxed">{{ $errors->first() }}</span>
                </div>
                @elseif(request()->query('expired') == '1')
                <div class="flex items-start gap-3 bg-yellow-500/10 border border-yellow-500/30 rounded-xl px-4 py-3 mb-6 animate-fade-in" role="alert">
                    <x-lucide name="alert-circle" class="w-4 h-4 text-yellow-400 flex-shrink-0 mt-0.5" />
                    <span class="text-sm text-yellow-300 leading-relaxed">Sesi Anda telah berakhir karena tidak ada aktivitas. Silakan login kembali.</span>
                </div>
                @endif

                {{-- Form --}}
                <form id="loginForm" action="{{ route('login.post') }}" method="POST" novalidate>
                    @csrf

                    {{-- Username --}}
                    <div class="mb-1">
                        <label for="username" class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-2">
                            Username
                        </label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 pointer-events-none">
                                <x-lucide name="user" class="w-[17px] h-[17px]" />
                            </span>
                            <input
                                id="username"
                                type="text"
                                name="username"
                                value="{{ old('username') }}"
                                placeholder="Masukkan username"
                                autocomplete="username"
                                autofocus
                                required
                                class="w-full h-12 bg-white/[.07] border border-white/10 rounded-xl text-slate-100 text-sm placeholder-slate-500 pl-10 pr-4 outline-none transition focus:bg-white/[.12] focus:border-primary-400 focus:ring-2 focus:ring-primary-400/20"
                            >
                        </div>
                    </div>

                    {{-- Password --}}
                    <div class="mt-5 mb-1">
                        <label for="password" class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-2">
                            Password
                        </label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 pointer-events-none">
                                <x-lucide name="lock" class="w-[17px] h-[17px]" />
                            </span>
                            <input
                                id="password"
                                type="password"
                                name="password"
                                placeholder="Masukkan password"
                                autocomplete="current-password"
                                required
                                class="w-full h-12 bg-white/[.07] border border-white/10 rounded-xl text-slate-100 text-sm placeholder-slate-500 pl-10 pr-11 outline-none transition focus:bg-white/[.12] focus:border-primary-400 focus:ring-2 focus:ring-primary-400/20"
                            >
                            <button
                                type="button"
                                id="togglePw"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-primary-400 transition rounded-md p-1"
                                aria-label="Tampilkan password"
                            >
                                <span id="iconEye">
                                    <x-lucide name="eye" class="w-[17px] h-[17px]" />
                                </span>
                                <span id="iconEyeOff" class="hidden">
                                    <x-lucide name="eye-off" class="w-[17px] h-[17px]" />
                                </span>
                            </button>
                        </div>
                    </div>

                    {{-- Cloudflare Turnstile --}}
                    @if($siteKey)
                    <div class="mt-5">
                        <div class="cf-turnstile"
                             data-sitekey="{{ $siteKey }}"
                             data-theme="dark"
                             data-size="normal">
                        </div>
                    </div>
                    @endif

                    {{-- Submit --}}
                    <button
                        id="btnSubmit"
                        type="submit"
                        class="mt-6 w-full h-12 flex items-center justify-center gap-2 bg-gradient-to-r from-primary-500 to-primary-600 hover:from-primary-400 hover:to-primary-500 text-white font-semibold text-sm rounded-xl shadow-lg shadow-primary-600/30 transition hover:-translate-y-0.5 hover:shadow-primary-500/40 active:translate-y-0 disabled:opacity-60 disabled:cursor-not-allowed disabled:transform-none"
                    >
                        <span class="spinner-wrap items-center justify-center">
                            <svg class="spinner w-5 h-5 text-white" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                        </span>
                        <span class="btn-icon">
                            <x-lucide name="log-in" class="w-[18px] h-[18px]" />
                        </span>
                        <span class="btn-label">Masuk ke Sistem</span>
                    </button>

                    {{-- Security note --}}
                    <div class="mt-5 flex items-center justify-center gap-1.5 text-xs text-slate-500">
                        <x-lucide name="shield" class="w-3.5 h-3.5 text-emerald-500" />
                        Koneksi aman &amp; dilindungi Cloudflare
                    </div>
                </form>

            </div>
        </div>

    </div>{{-- /card --}}


    <script>
        // ── Password visibility toggle
        const togglePw  = document.getElementById('togglePw');
        const pwInput   = document.getElementById('password');
        const iconEye    = document.getElementById('iconEye');
        const iconEyeOff = document.getElementById('iconEyeOff');

        togglePw.addEventListener('click', () => {
            const hidden = pwInput.type === 'password';
            pwInput.type = hidden ? 'text' : 'password';
            iconEye.classList.toggle('hidden', hidden);
            iconEyeOff.classList.toggle('hidden', !hidden);
        });

        // ── Loading state on submit
        document.getElementById('loginForm').addEventListener('submit', function () {
            const btn = document.getElementById('btnSubmit');
            btn.disabled = true;
            btn.classList.add('btn-loading');
        });
    </script>
</body>
</html>
