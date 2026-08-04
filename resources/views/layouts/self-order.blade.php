<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="Pesan makanan & minuman langsung dari meja Anda.">
    <meta name="theme-color" content="#1d4ed8">
    <title>@yield('title', config('app.name', 'Self Order'))</title>

    <!-- Google Fonts.
         display=optional (bukan swap) sengaja dipakai agar TIDAK ada visible font-swap saat font
         custom baru selesai di-download — browser langsung pakai fallback system font secara
         permanen untuk render ini kalau font belum siap dalam waktu singkat, daripada "berkedut"
         berganti dari fallback ke Plus Jakarta Sans di tengah halaman sudah terlihat. -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=optional" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            DEFAULT: '#1d4ed8',
                            dark: '#1e40af',
                            light: '#eff6ff',
                        }
                    },
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    @livewireStyles

    <style>
        [x-cloak] { display: none !important; }
        :root {
            --brand-primary: #1d4ed8;
            --brand-dark: #1e40af;
            --brand-light: #eff6ff;
        }
        body { font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, -apple-system, sans-serif; }
        .bg-brand { background-color: var(--brand-primary); }
        .text-brand { color: var(--brand-primary); }
        .border-brand { border-color: var(--brand-primary); }
        .ring-brand { --tw-ring-color: var(--brand-primary); }
        /* Smooth scroll */
        html { scroll-behavior: smooth; }
        /* Tap highlight */
        * { -webkit-tap-highlight-color: transparent; }
        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: var(--brand-primary); border-radius: 4px; }
        /* Spinner keyframe & overlay */
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        /*
         * PENTING: jangan set `display` di sini. Selector berbasis atribut seperti
         * `[wire\:loading]` selalu match karena atribut itu tertulis statis di HTML,
         * sehingga `display: flex !important` akan menimpa `display:none` inline yang
         * di-toggle Livewire — akibatnya overlay ini tampil TERUS menumpuk di atas
         * konten tombol, bukan cuma saat request sedang berjalan.
         * Gunakan modifier `wire:loading.flex` di Blade agar Livewire sendiri yang
         * mengatur toggle none/flex; CSS ini hanya menangani posisi & layout.
         */
        .btn-loading-overlay {
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 0.75rem !important;
            width: 100% !important;
            height: 100% !important;
        }
    </style>
</head>
<body class="h-full bg-gray-50 antialiased">

    <!-- App Shell (Mobile-First 448px container) -->
    <div class="min-h-full max-w-md mx-auto bg-white shadow-xl relative">
        {{ $slot }}
    </div>

    @livewireScripts
</body>
</html>
