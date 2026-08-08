<!DOCTYPE html>
<html lang="id" class="h-full scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ $title ?? config('app.name', 'Bakso Malang') }} - Cita Rasa Otentik Bakso Malang Spesial Daging Pilihan.">
    <meta name="theme-color" content="#2563eb">
    <title>{{ $title ?? config('app.name', 'Bakso Malang') }}</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,400&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
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
                    },
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'ui-sans-serif', 'system-ui', '-apple-system', 'sans-serif'],
                    },
                }
            }
        }
    </script>

    {{-- Alpine.js sudah dibundel otomatis oleh @livewireScripts (Livewire 3). Memuat Alpine
         CDN terpisah di sini akan membuat DUA instance Alpine berjalan bersamaan, yang
         menyebabkan x-data/wire:click di halaman ini gagal/berperilaku acak (mis. modal
         detail produk tidak muncul saat ikon mata diklik). Jangan tambahkan lagi. --}}

    @livewireStyles

    <style>
        [x-cloak] { display: none !important; }

        :root {
            --primary-600: #2563eb;
            --primary-700: #1d4ed8;
        }

        * { -webkit-tap-highlight-color: transparent; }

        body {
            font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, -apple-system, sans-serif;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: var(--primary-600); border-radius: 99px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--primary-700); }

        /* Custom scroll for modals */
        .custom-scroll::-webkit-scrollbar { width: 4px; }
        .custom-scroll::-webkit-scrollbar-track { background: transparent; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 99px; }

        /* Scroll-reveal: fade + slide-up yang halus, bukan animasi mencolok */
        [data-reveal] {
            opacity: 0;
            transform: translateY(22px);
            transition: opacity .7s cubic-bezier(.16,.84,.44,1), transform .7s cubic-bezier(.16,.84,.44,1);
            transition-delay: var(--reveal-delay, 0ms);
        }
        [data-reveal].is-revealed {
            opacity: 1;
            transform: translateY(0);
        }
        @media (prefers-reduced-motion: reduce) {
            [data-reveal] { opacity: 1; transform: none; transition: none; }
        }
    </style>
</head>
<body class="h-full bg-slate-50 antialiased text-slate-800">

    {{ $slot }}

    @livewireScripts

    <script>
        function initScrollReveal() {
            const targets = document.querySelectorAll('[data-reveal]:not(.is-revealed)');
            if (!targets.length) return;

            if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                targets.forEach((el) => el.classList.add('is-revealed'));
                return;
            }

            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-revealed');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.15, rootMargin: '0px 0px -60px 0px' });

            targets.forEach((el) => observer.observe(el));
        }

        document.addEventListener('DOMContentLoaded', initScrollReveal);
        document.addEventListener('livewire:navigated', initScrollReveal);
        document.addEventListener('livewire:init', () => {
            Livewire.hook('morph.updated', () => initScrollReveal());
        });
    </script>
</body>
</html>
