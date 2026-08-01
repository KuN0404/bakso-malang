<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Layar Pelanggan - Bakso Malang</title>
    
    @php
        $siteLogo = \App\Models\Setting::get('site_logo', null, 'general');
        $fontFamilyWeb = \App\Models\Setting::get('font_family_web', 'Poppins', 'general');
    @endphp

    <!-- Google Fonts: Dynamic -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family={{ str_replace(' ', '+', $fontFamilyWeb) }}:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style id="base-font-style">
        :root {
            --font-sans: '{{ $fontFamilyWeb }}';
        }
        body {
            font-family: var(--font-sans), sans-serif !important;
        }
    </style>

    @if ($siteLogo)
        <link rel="icon" type="image/webp" href="{{ asset('storage/' . $siteLogo) }}">
    @endif
    
    <!-- Tailwind CSS -->
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
                        sans: ['var(--font-sans)', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    
    <!-- Livewire Styles -->
    @livewireStyles
    
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="min-h-screen bg-gray-50 antialiased" data-font-web="{{ $fontFamilyWeb }}">
    {{ $slot }}
    
    <!-- Livewire Scripts -->
    @livewireScripts
    
    <script>
        document.addEventListener('livewire:init', () => {
            if (typeof Livewire.onPageExpired === 'function') {
                Livewire.onPageExpired((response, message) => {
                    window.location.href = "{{ route('login') }}?expired=1";
                    return false;
                });
            } else if (Livewire.hook) {
                Livewire.hook('request', ({ fail }) => {
                    fail(({ status, preventDefault }) => {
                        if (status === 419) {
                            preventDefault();
                            window.location.href = "{{ route('login') }}?expired=1";
                        }
                    });
                });
            }
        });

        // Dynamic Font Applier
        function applyDynamicFont(fontFamily) {
            if (!fontFamily) return;
            try {
                let fontLink = document.getElementById('dynamic-font-link');
                const href = `https://fonts.googleapis.com/css2?family=${fontFamily.replace(/ /g, '+')}:wght@300;400;500;600;700;800&display=swap`;
                
                if (!fontLink) {
                    fontLink = document.createElement('link');
                    fontLink.id = 'dynamic-font-link';
                    fontLink.rel = 'stylesheet';
                    document.head.appendChild(fontLink);
                }
                if (fontLink.href !== href) {
                    fontLink.href = href;
                }

                // Instantly update the CSS variable on the document root
                document.documentElement.style.setProperty('--font-sans', `'${fontFamily}'`);
            } catch (e) {
                console.error("Error applying font:", e);
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();
            const initialFont = document.body.getAttribute('data-font-web');
            if (initialFont) applyDynamicFont(initialFont);
        });

        // Re-initialize on Livewire navigation
        document.addEventListener('livewire:navigated', () => {
            lucide.createIcons();
            const currentFont = document.body.getAttribute('data-font-web');
            if (currentFont) applyDynamicFont(currentFont);
        });

        // Global MutationObserver to automatically rebuild Lucide icons on any DOM change
        let lucideDebounce;
        const lucideObserver = new MutationObserver((mutations) => {
            let needsRebuild = false;
            for (const mutation of mutations) {
                if (mutation.addedNodes.length) {
                    for (const node of mutation.addedNodes) {
                        if (node.nodeType === 1) {
                            if (node.hasAttribute('data-lucide') || node.querySelector('[data-lucide]')) {
                                needsRebuild = true;
                                break;
                            }
                        }
                    }
                }
                if (needsRebuild) break;
            }
            if (needsRebuild) {
                clearTimeout(lucideDebounce);
                lucideDebounce = setTimeout(() => {
                    if (window.lucide) lucide.createIcons();
                }, 10);
            }
        });
        lucideObserver.observe(document.body, { childList: true, subtree: true });

        // Tangani update in-place Livewire (seperti pengetikan pada input pencarian)
        document.addEventListener('livewire:init', () => {
            Livewire.hook('morph.updated', () => {
                clearTimeout(lucideDebounce);
                lucideDebounce = setTimeout(() => {
                    if (window.lucide) lucide.createIcons();
                }, 15);
            });
        });

        // Handle Real-Time Settings Updates for SPA/wire:navigate
        window.addEventListener('settings-updated', event => {
            const data = event.detail;
            const siteLogo = data.site_logo;
            const fontFamilyWeb = data.font_family_web;

            // Update Favicon (site logo)
            let favicon = document.querySelector('link[rel="icon"]');
            if (siteLogo) {
                if (!favicon) {
                    favicon = document.createElement('link');
                    favicon.rel = 'icon';
                    favicon.type = 'image/webp';
                    document.head.appendChild(favicon);
                }
                favicon.href = siteLogo;
            } else {
                if (favicon) {
                    favicon.remove();
                }
            }

            // Update Font Family in real-time
            if (fontFamilyWeb) {
                document.body.setAttribute('data-font-web', fontFamilyWeb);
                applyDynamicFont(fontFamilyWeb);
            }
        });
    </script>
</body>
</html>
