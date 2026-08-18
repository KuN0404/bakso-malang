<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'POS - Bakso Malang' }}</title>
    
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
                        sidebar: {
                            DEFAULT: '#1e3a5f',
                            dark: '#162d4a',
                            light: '#2a4a73',
                        }
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

        /* Hilangkan spinner bawaan browser di semua input number — spinner ini
           mudah tersenggol drag/scroll mouse dan mengubah angka tanpa sengaja. */
        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type="number"] {
            -moz-appearance: textfield;
        }

        /* Custom scrollbar */
        .custom-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scroll::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 3px; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        .custom-scroll::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        /* Print styles */
        @media print {
            body * { visibility: hidden; }
            #receipt-container, #receipt-container * { visibility: visible; }
            #receipt-container {
                position: absolute;
                left: 0;
                top: 0;
                /* Div ini punya kelas Tailwind "p-4" untuk tampilan di modal
                   (layar) — tanpa di-nolkan, padding itu tetap ikut tercetak
                   dan membuat struk lebih lebar dari ukuran kertas (@page)
                   yang sebenarnya, menyebabkan konten overflow ke kanan. */
                width: fit-content;
                padding: 0 !important;
                margin: 0 !important;
            }
        }
    </style>
</head>
<body class="h-full bg-gray-100 antialiased" data-font-web="{{ $fontFamilyWeb }}">
    {{ $slot }}
    
    <!-- Toast Notifications -->
    <div 
        x-data="{ 
            notifications: [],
            add(data) {
                const id = Date.now();
                this.notifications.push({ id, ...data });
                setTimeout(() => this.remove(id), 3000);
            },
            remove(id) {
                this.notifications = this.notifications.filter(n => n.id !== id);
            }
        }"
        @notify.window="add($event.detail)"
        class="fixed top-4 right-4 z-50 space-y-2"
    >
        <template x-for="notification in notifications" :key="notification.id">
            <div 
                x-show="true"
                x-transition
                :class="{
                    'bg-green-500': notification.type === 'success',
                    'bg-red-500': notification.type === 'error',
                    'bg-yellow-500': notification.type === 'warning',
                    'bg-blue-500': notification.type === 'info'
                }"
                class="px-4 py-3 rounded-lg shadow-lg text-white font-medium"
            >
                <span x-text="notification.message"></span>
            </div>
        </template>
    </div>
    
    <!-- Livewire Scripts (includes Alpine.js) -->
    @livewireScripts
    
    <script>
        document.addEventListener('livewire:init', () => {
            if (typeof Livewire.onPageExpired === 'function') {
                Livewire.onPageExpired((response, message) => {
                    window.location.href = "{{ route('login') }}?expired=1";
                    return false;
                });
            }
            // Livewire.onPageExpired hanya menangani 419 (sesi habis). Tanpa hook di bawah
            // ini, request yang gagal karena sebab lain (500 dari exception tak terduga, dll)
            // sama sekali tidak terlihat oleh user — spinner berhenti tapi tidak ada notifikasi
            // apapun, terlihat seperti tombol tidak berfungsi.
            Livewire.hook('request', ({ fail }) => {
                fail(({ status, preventDefault }) => {
                    if (status === 419) {
                        preventDefault();
                        window.location.href = "{{ route('login') }}?expired=1";
                        return;
                    }
                    if (status && status !== 200) {
                        preventDefault();
                        window.dispatchEvent(new CustomEvent('notify', {
                            detail: { type: 'error', message: 'Terjadi kesalahan saat memproses permintaan (kode ' + status + '). Silakan coba lagi.' }
                        }));
                    }
                });
            });
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

        // Initialize Lucide icons after page load
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();
            const initialFont = document.body.getAttribute('data-font-web');
            if (initialFont) applyDynamicFont(initialFont);
        });
        
        // Re-initialize on Livewire updates
        document.addEventListener('livewire:navigated', () => {
            lucide.createIcons();
            const currentFont = document.body.getAttribute('data-font-web');
            if (currentFont) applyDynamicFont(currentFont);
        });

        // Cegah scroll wheel mouse mengubah nilai input number saat kursor
        // kebetulan berada di atasnya (browser secara default mengubah nilai
        // jika input number sedang fokus lalu di-scroll — risiko salah input
        // tanpa sengaja, mis. saat kasir scroll daftar produk).
        document.addEventListener('wheel', function (e) {
            if (document.activeElement && document.activeElement.tagName === 'INPUT' && document.activeElement.type === 'number') {
                document.activeElement.blur();
            }
        }, { passive: true });

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
        
        // Global Alpine.js Money Input Component
        // Usage: x-data="moneyInput(initialValue, wireModel)"
        document.addEventListener('alpine:init', () => {
            Alpine.data('moneyInput', (initialValue = 0, wireModel = null) => ({
                rawValue: initialValue,
                formatted: '',
                wireModel: wireModel,
                
                init() {
                    // Show '0' when initial value is 0, not empty
                    this.formatted = this.rawValue > 0 ? this.formatNumber(this.rawValue) : '0';
                },
                
                formatNumber(num) {
                    return new Intl.NumberFormat('id-ID').format(num || 0);
                },
                
                parseNumber(str) {
                    return parseInt(String(str).replace(/\./g, '').replace(/,/g, '') || 0);
                },
                
                onInput(e) {
                    const input = e.target;
                    const cursorPos = input.selectionStart;
                    const oldLen = this.formatted.length;
                    
                    // Get raw digits only
                    const digits = this.formatted.replace(/\D/g, '');
                    this.rawValue = parseInt(digits) || 0;
                    // Keep '0' visible instead of blanking the field
                    this.formatted = this.rawValue > 0 ? this.formatNumber(this.rawValue) : (digits.length > 0 ? '0' : '');
                    
                    // Adjust cursor position after formatting
                    const newLen = this.formatted.length;
                    const diff = newLen - oldLen;
                    this.$nextTick(() => {
                        const newPos = Math.max(0, cursorPos + diff);
                        input.setSelectionRange(newPos, newPos);
                    });
                },
                
                syncToWire() {
                    if (this.wireModel) {
                        this.$wire.set(this.wireModel, this.rawValue);
                    }
                },
                
                getValue() {
                    return this.rawValue;
                }
            }));
        });
    </script>
</body>
</html>
