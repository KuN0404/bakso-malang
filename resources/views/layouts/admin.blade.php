<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard - Bakso Malang POS' }}</title>
    
    @php
        $siteLogo = \App\Models\Setting::get('site_logo', null, 'general');
        $logoWeb = \App\Models\Setting::get('logo_web', null, 'general');
        $logoFull = \App\Models\Setting::get('logo_full', null, 'general');
        $logoType = \App\Models\Setting::get('logo_type', 'single', 'general');
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
    
    <!-- Flatpickr Date Picker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/airbnb.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/id.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/rangePlugin.js"></script>
    
    <!-- Livewire Styles (includes Alpine.js) -->
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

        /* Static preload widths to prevent sidebar flash/animation on hard refresh */
        .sidebar-collapsed aside {
            width: 5rem !important; /* lg:w-20 */
        }
        .sidebar-collapsed main {
            margin-left: 5rem !important; /* lg:ml-20 */
        }
        
        /* Custom scrollbar */
        .custom-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scroll::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 3px; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        .custom-scroll::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        /* Flatpickr Custom Styling - Modern Aesthetic */
        .flatpickr-calendar {
            border-radius: 16px !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05) !important;
            border: 1px solid #e2e8f0 !important;
            padding: 12px !important;
            background: #ffffff !important;
            font-family: inherit !important;
        }
        .flatpickr-months {
            align-items: center !important;
            margin-bottom: 8px !important;
        }
        .flatpickr-months .flatpickr-month {
            background: transparent !important;
            color: #0f172a !important;
            height: 38px !important;
        }
        .flatpickr-current-month {
            font-size: 0.95rem !important;
            font-weight: 700 !important;
            padding-top: 4px !important;
            color: #0f172a !important;
        }
        .flatpickr-current-month .flatpickr-monthDropdown-months {
            font-weight: 700 !important;
            color: #0f172a !important;
            border-radius: 6px !important;
            padding: 2px 4px !important;
        }
        .flatpickr-current-month input.cur-year {
            font-weight: 700 !important;
            color: #0f172a !important;
        }
        .flatpickr-months .flatpickr-prev-month, 
        .flatpickr-months .flatpickr-next-month {
            fill: #475569 !important;
            color: #475569 !important;
            padding: 6px !important;
            border-radius: 8px !important;
            transition: all 0.2s ease !important;
        }
        .flatpickr-months .flatpickr-prev-month:hover, 
        .flatpickr-months .flatpickr-next-month:hover {
            background: #f1f5f9 !important;
            color: #0f172a !important;
            fill: #0f172a !important;
        }
        .flatpickr-weekdays {
            margin-bottom: 4px !important;
        }
        .flatpickr-weekday {
            color: #94a3b8 !important;
            font-weight: 600 !important;
            font-size: 0.75rem !important;
            text-transform: uppercase !important;
        }
        .dayContainer {
            gap: 2px !important;
        }
        .flatpickr-day {
            border-radius: 8px !important;
            border: none !important;
            color: #334155 !important;
            font-weight: 500 !important;
            font-size: 0.85rem !important;
            height: 36px !important;
            line-height: 36px !important;
            max-width: 36px !important;
            margin: 1px 0 !important;
            transition: all 0.15s ease !important;
        }
        .flatpickr-day.prevMonthDay, 
        .flatpickr-day.nextMonthDay {
            color: #cbd5e1 !important;
        }
        .flatpickr-day:hover, 
        .flatpickr-day:focus {
            background: #f1f5f9 !important;
            color: #0f172a !important;
        }
        .flatpickr-day.today {
            border: 2px solid #2563eb !important;
            color: #2563eb !important;
            font-weight: 700 !important;
            background: transparent !important;
        }
        .flatpickr-day.selected, 
        .flatpickr-day.startRange, 
        .flatpickr-day.endRange {
            background: #2563eb !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            border-radius: 8px !important;
            box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.3) !important;
        }
        .flatpickr-day.startRange {
            border-top-right-radius: 0 !important;
            border-bottom-right-radius: 0 !important;
        }
        .flatpickr-day.endRange {
            border-top-left-radius: 0 !important;
            border-bottom-left-radius: 0 !important;
        }
        .flatpickr-day.inRange {
            background: #eff6ff !important;
            color: #1d4ed8 !important;
            border-radius: 0 !important;
            box-shadow: -5px 0 0 #eff6ff, 5px 0 0 #eff6ff !important;
        }
        .flatpickr-day.startRange.endRange {
            border-radius: 8px !important;
        }
    </style>
</head>
<body class="h-full bg-gray-100 antialiased" data-font-web="{{ $fontFamilyWeb }}">
    <div 
        x-data="{ 
            sidebarOpen: false, 
            showLogoutModal: false,
            isSidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
            sidebarHover: false,
            get isCompact() { return this.isSidebarCollapsed && !this.sidebarHover },
            toggleSidebar() {
                this.isSidebarCollapsed = !this.isSidebarCollapsed;
                localStorage.setItem('sidebarCollapsed', this.isSidebarCollapsed);
            }
        }" 
        x-init="document.documentElement.classList.remove('sidebar-collapsed')"
        class="flex h-screen bg-gray-100"
    >
        
        <!-- Mobile Backdrop -->
        <div 
            x-show="sidebarOpen" 
            x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="sidebarOpen = false"
            class="fixed inset-0 z-20 bg-black/50 lg:hidden"
        ></div>

        <!-- Preload script to prevent sidebar collapse transition flash on hard refresh -->
        <script>
            if (localStorage.getItem('sidebarCollapsed') === 'true') {
                document.documentElement.classList.add('sidebar-collapsed');
            } else {
                document.documentElement.classList.remove('sidebar-collapsed');
            }
        </script>

        <!-- Sidebar -->
        <aside 
            @mouseenter="sidebarHover = true"
            @mouseleave="sidebarHover = false"
            :class="[
                sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
                isCompact ? 'lg:w-20' : 'lg:w-64'
            ]"
            class="fixed inset-y-0 left-0 z-30 bg-sidebar flex flex-col transition-all duration-300 ease-in-out shadow-xl lg:shadow-none overflow-hidden lg:w-64"
        >
            <!-- Logo -->
            <div class="p-5 border-b border-sidebar-light flex items-center gap-3 whitespace-nowrap overflow-hidden h-20">
                {{-- Mode "single": ikon kotak + nama toko --}}
                <div id="sidebar-logo-single-wrap" class="contents" style="{{ $logoType === 'full' && $logoFull ? 'display:none' : '' }}">
                    <div class="flex-shrink-0 w-10 h-10 bg-white rounded-lg flex items-center justify-center overflow-hidden">
                        @if ($logoWeb)
                            <img id="sidebar-logo-img" src="{{ asset('storage/' . $logoWeb) }}" class="w-full h-full object-cover">
                        @else
                            <div id="sidebar-logo-placeholder" class="w-full h-full flex items-center justify-center">
                                <x-lucide name="soup" class="w-6 h-6 text-sidebar" />
                            </div>
                        @endif
                    </div>
                    <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-40 opacity-100'">
                        <h1 id="sidebar-store-name" class="text-white font-bold text-lg leading-tight">{{ \App\Models\Setting::get('store_name', 'BAKSO MALANG', 'general') }}</h1>
                        <p class="text-blue-200 text-xs">Point of Sales</p>
                    </div>
                </div>
                {{-- Mode "full": banner logo saja, tanpa teks nama toko (sudah ada di gambar) --}}
                <img id="sidebar-logo-full-img" src="{{ $logoFull ? asset('storage/' . $logoFull) : '' }}" class="h-10 w-auto max-w-[180px] object-contain"
                    style="{{ $logoType === 'full' && $logoFull ? '' : 'display:none' }}">
                <!-- Close Button (Mobile Only) -->
                <button @click="sidebarOpen = false" class="lg:hidden ml-auto text-blue-200 hover:text-white">
                    <x-lucide name="x" class="w-6 h-6" />
                </button>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 p-3 space-y-1 overflow-y-auto custom-scroll overflow-x-hidden">
                <a href="{{ route('admin.dashboard') }}" wire:navigate class="flex items-center gap-3 px-3 py-3 rounded-lg whitespace-nowrap {{ request()->routeIs('admin.dashboard') ? 'bg-sidebar-light text-white' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                    <div class="flex-shrink-0 w-6 flex justify-center"><x-lucide name="layout-dashboard" class="w-5 h-5" /></div>
                     <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-32 opacity-100'">
                        <span>Dashboard</span>
                    </div>
                </a>

                <a href="{{ route('admin.menu.index') }}" wire:navigate class="flex items-center gap-3 px-3 py-3 rounded-lg whitespace-nowrap {{ request()->routeIs('admin.menu.*') ? 'bg-sidebar-light text-white' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                    <div class="flex-shrink-0 w-6 flex justify-center"><x-lucide name="utensils" class="w-5 h-5" /></div>
                     <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-32 opacity-100'">
                        <span>Katalog Menu</span>
                    </div>
                </a>

                <!-- Group: Master Data -->
                @php
                    $isMasterActive = request()->routeIs('admin.categories.*') ||
                                      request()->routeIs('admin.units.*') ||
                                      request()->routeIs('admin.products.*') ||
                                      request()->routeIs('admin.modifiers.*') ||
                                      request()->routeIs('admin.payment-sources.*') ||
                                      request()->routeIs('admin.service-areas.*') ||
                                      request()->routeIs('admin.pagers.*') ||
                                      request()->routeIs('admin.customers.*');
                @endphp
                @canany(['view_categories', 'view_units', 'view_products', 'view_modifiers', 'manage_payment_sources', 'manage_settings', 'manage_pagers', 'view_customers'])
                <div x-data="{ open: {{ $isMasterActive ? 'true' : 'false' }} }" class="mt-4">
                    <button @click="open = !open" class="w-full flex items-center justify-between px-3 py-2 text-xs font-semibold text-blue-300 uppercase tracking-wider hover:text-white transition-colors group whitespace-nowrap text-left">
                        <div class="flex items-center gap-3">
                            <div class="flex-shrink-0 w-6 flex justify-center"><x-lucide name="database" class="w-5 h-5 text-blue-300 group-hover:text-white" /></div>
                            <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-32 opacity-100'">
                                <span>Master Data</span>
                            </div>
                        </div>
                        <span class="transform transition-transform duration-200 flex-shrink-0" :class="[open ? 'rotate-90' : '', isCompact ? 'hidden' : 'block']">
                            <x-lucide name="chevron-right" class="w-4 h-4" />
                        </span>
                    </button>
                    <!-- Force hidden if compact to avoid popup artifacts, rely on hover to expand first -->
                    <div x-show="open && !isCompact" x-cloak 
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="ml-6 pl-3 border-l border-sidebar-light/40 space-y-1 mt-1">
                        @can('view_categories')
                        <a href="{{ route('admin.categories.index') }}" wire:navigate class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.categories.*') ? 'bg-sidebar-light text-white' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                            <div class="flex-shrink-0 w-6 flex justify-center"><x-lucide name="folder" class="w-4 h-4" /></div>
                            <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-32 opacity-100'"><span>Kategori</span></div>
                        </a>
                        @endcan

                        @can('view_units')
                        <a href="{{ route('admin.units.index') }}" wire:navigate class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.units.*') ? 'bg-sidebar-light text-white' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                            <div class="flex-shrink-0 w-6 flex justify-center"><x-lucide name="ruler" class="w-4 h-4" /></div>
                            <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-32 opacity-100'"><span>Satuan</span></div>
                        </a>
                        @endcan

                        @can('view_products')
                        <a href="{{ route('admin.products.index') }}" wire:navigate class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.products.*') ? 'bg-sidebar-light text-white' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                            <div class="flex-shrink-0 w-6 flex justify-center"><x-lucide name="package" class="w-4 h-4" /></div>
                            <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-32 opacity-100'"><span>Produk</span></div>
                        </a>
                        @endcan

                        @can('view_modifiers')
                        <a href="{{ route('admin.modifiers.index') }}" wire:navigate class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.modifiers.*') ? 'bg-sidebar-light text-white' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                            <div class="flex-shrink-0 w-6 flex justify-center"><x-lucide name="sliders" class="w-4 h-4" /></div>
                            <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-32 opacity-100'"><span>Modifier</span></div>
                        </a>
                        @endcan

                        @can('manage_payment_sources')
                        <a href="{{ route('admin.payment-sources.index') }}" wire:navigate class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.payment-sources.*') ? 'bg-sidebar-light text-white' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                            <div class="flex-shrink-0 w-6 flex justify-center"><x-lucide name="wallet" class="w-4 h-4" /></div>
                            <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-32 opacity-100'"><span>Metode Bayar</span></div>
                        </a>
                        @endcan

                        @can('manage_settings')
                        <a href="{{ route('admin.service-areas.index') }}" wire:navigate class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.service-areas.*') ? 'bg-sidebar-light text-white' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                            <div class="flex-shrink-0 w-6 flex justify-center"><x-lucide name="map-pin" class="w-4 h-4" /></div>
                            <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-32 opacity-100'"><span>Area Pelayanan</span></div>
                        </a>
                        @endcan

                        @can('manage_pagers')
                        <a href="{{ route('admin.pagers.index') }}" wire:navigate class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.pagers.*') ? 'bg-sidebar-light text-white' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                            <div class="flex-shrink-0 w-6 flex justify-center"><x-lucide name="radio" class="w-4 h-4" /></div>
                            <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-32 opacity-100'"><span>Pager</span></div>
                        </a>
                        @endcan

                        @can('view_customers')
                        <a href="{{ route('admin.customers.index') }}" wire:navigate class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.customers.*') ? 'bg-sidebar-light text-white' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                            <div class="flex-shrink-0 w-6 flex justify-center"><x-lucide name="users" class="w-4 h-4" /></div>
                            <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-32 opacity-100'"><span>Pelanggan</span></div>
                        </a>
                        @endcan
                    </div>
                </div>
                @endcanany

                <!-- Group: Inventori & Stok -->
                @php
                    $isInventoryActive = request()->routeIs('admin.ingredients.*') ||
                                         request()->routeIs('admin.components.*') ||
                                         request()->routeIs('admin.purchases.*') ||
                                         request()->routeIs('admin.suppliers.*') ||
                                         request()->routeIs('admin.productions.*') ||
                                         request()->routeIs('admin.hpp-calculator.*');
                @endphp
                @canany(['view_ingredients', 'view_purchases', 'view_suppliers', 'view_productions'])
                <div x-data="{ open: {{ $isInventoryActive ? 'true' : 'false' }} }" class="mt-2">
                    <button @click="open = !open" class="w-full flex items-center justify-between px-3 py-2 text-xs font-semibold text-blue-300 uppercase tracking-wider hover:text-white transition-colors group whitespace-nowrap text-left">
                        <div class="flex items-center gap-3">
                            <div class="flex-shrink-0 w-6 flex justify-center"><x-lucide name="boxes" class="w-5 h-5 text-blue-300 group-hover:text-white" /></div>
                            <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-32 opacity-100'">
                                <span>Inventori & Stok</span>
                            </div>
                        </div>
                        <span class="transform transition-transform duration-200 flex-shrink-0" :class="[open ? 'rotate-90' : '', isCompact ? 'hidden' : 'block']">
                            <x-lucide name="chevron-right" class="w-4 h-4" />
                        </span>
                    </button>
                    <div x-show="open && !isCompact" x-cloak 
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="ml-6 pl-3 border-l border-sidebar-light/40 space-y-1 mt-1">
                        <a href="{{ route('admin.hpp-calculator.index') }}" wire:navigate class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.hpp-calculator.*') ? 'bg-sidebar-light text-white' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                            <div class="flex-shrink-0 w-6 flex justify-center"><x-lucide name="calculator" class="w-4 h-4 text-emerald-400" /></div>
                            <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-32 opacity-100'"><span>Kalkulator HPP</span></div>
                        </a>

                        @can('view_ingredients')
                        <a href="{{ route('admin.ingredients.index') }}" wire:navigate class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.ingredients.*') ? 'bg-sidebar-light text-white' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                            <div class="flex-shrink-0 w-6 flex justify-center"><x-lucide name="leaf" class="w-4 h-4" /></div>
                            <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-32 opacity-100'"><span>Bahan Baku</span></div>
                        </a>
                        @endcan

                        @can('view_components')
                        <a href="{{ route('admin.components.index') }}" wire:navigate class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.components.*') ? 'bg-sidebar-light text-white' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                            <div class="flex-shrink-0 w-6 flex justify-center"><x-lucide name="layers" class="w-4 h-4" /></div>
                            <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-32 opacity-100'"><span>Komponen</span></div>
                        </a>
                        @endcan
                        
                        @can('view_purchases')
                        <a href="{{ route('admin.purchases.index') }}" wire:navigate class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.purchases.*') ? 'bg-sidebar-light text-white' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                            <div class="flex-shrink-0 w-6 flex justify-center"><x-lucide name="shopping-cart" class="w-4 h-4" /></div>
                            <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-32 opacity-100'"><span>Pembelian Stok</span></div>
                        </a>
                        @endcan

                        @can('view_suppliers')
                        <a href="{{ route('admin.suppliers.index') }}" wire:navigate class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.suppliers.*') ? 'bg-sidebar-light text-white' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                            <div class="flex-shrink-0 w-6 flex justify-center"><x-lucide name="truck" class="w-4 h-4" /></div>
                            <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-32 opacity-100'"><span>Supplier</span></div>
                        </a>
                        @endcan

                        @can('view_productions')
                        <a href="{{ route('admin.productions.index') }}" wire:navigate class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.productions.*') ? 'bg-sidebar-light text-white' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                            <div class="flex-shrink-0 w-6 flex justify-center"><x-lucide name="utensils-crossed" class="w-4 h-4" /></div>
                            <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-32 opacity-100'"><span>Repacking / Produksi</span></div>
                        </a>
                        @endcan
                    </div>
                </div>
                @endcanany

                <!-- Group: Transaksi -->
                @php
                    $isTransActive = request()->routeIs('pos') || request()->routeIs('admin.shifts.*') || request()->routeIs('kitchen.display');
                @endphp
                @canany(['access_pos', 'view_all_shifts', 'view_kitchen_display'])
                <div x-data="{ open: {{ $isTransActive ? 'true' : 'false' }} }" class="mt-2">
                    <button @click="open = !open" class="w-full flex items-center justify-between px-3 py-2 text-xs font-semibold text-blue-300 uppercase tracking-wider hover:text-white transition-colors group whitespace-nowrap text-left">
                        <div class="flex items-center gap-3">
                            <div class="flex-shrink-0 w-6 flex justify-center"><x-lucide name="shopping-bag" class="w-5 h-5 text-blue-300 group-hover:text-white" /></div>
                            <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-32 opacity-100'">
                                <span>Transaksi</span>
                            </div>
                        </div>
                        <span class="transform transition-transform duration-200 flex-shrink-0" :class="[open ? 'rotate-90' : '', isCompact ? 'hidden' : 'block']">
                            <x-lucide name="chevron-right" class="w-4 h-4" />
                        </span>
                    </button>
                    <div x-show="open && !isCompact" x-cloak 
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="ml-6 pl-3 border-l border-sidebar-light/40 space-y-1 mt-1">
                        @can('access_pos')
                        <a href="{{ route('pos') }}" wire:navigate class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-blue-200 hover:bg-sidebar-light hover:text-white transition-colors">
                             <div class="flex-shrink-0 w-6 flex justify-center"><x-lucide name="monitor" class="w-4 h-4" /></div>
                            <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-32 opacity-100'"><span>POS Kasir</span></div>
                        </a>
                        @endcan
                        
                        @can('view_all_shifts')
                        <a href="{{ route('admin.shifts.index') }}" wire:navigate class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.shifts.*') ? 'bg-sidebar-light text-white' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                            <div class="flex-shrink-0 w-6 flex justify-center"><x-lucide name="clock" class="w-4 h-4" /></div>
                            <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-32 opacity-100'"><span>Shift</span></div>
                        </a>
                        @endcan

                        @can('view_kitchen_display')
                        <a href="{{ route('kitchen.display') }}" wire:navigate class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('kitchen.display') ? 'bg-sidebar-light text-white' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                            <div class="flex-shrink-0 w-6 flex justify-center"><x-lucide name="chef-hat" class="w-4 h-4" /></div>
                            <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-32 opacity-100'"><span>Dapur / Service</span></div>
                        </a>
                        @endcan
                    </div>
                </div>
                @endcanany

                <!-- Group: Laporan -->
                @php
                    $isReportActive = request()->routeIs('admin.reports.*') || request()->routeIs('admin.returns');
                @endphp
                @canany(['view_transactions', 'view_sales_reports', 'view_all_shifts', 'view_inventory_reports', 'view_cancellation_reports'])
                <div x-data="{ open: {{ $isReportActive ? 'true' : 'false' }} }" class="mt-2">
                    <button @click="open = !open" class="w-full flex items-center justify-between px-3 py-2 text-xs font-semibold text-blue-300 uppercase tracking-wider hover:text-white transition-colors group whitespace-nowrap text-left">
                        <div class="flex items-center gap-3">
                            <div class="flex-shrink-0 w-6 flex justify-center"><x-lucide name="clipboard-list" class="w-5 h-5 text-blue-300 group-hover:text-white" /></div>
                            <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-32 opacity-100'">
                                <span>Laporan</span>
                            </div>
                        </div>
                        <span class="transform transition-transform duration-200 flex-shrink-0" :class="[open ? 'rotate-90' : '', isCompact ? 'hidden' : 'block']">
                            <x-lucide name="chevron-right" class="w-4 h-4" />
                        </span>
                    </button>
                    <div x-show="open && !isCompact" x-cloak 
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="ml-6 pl-3 border-l border-sidebar-light/40 space-y-1 mt-1">
                        @can('view_transactions')
                        <a href="{{ route('admin.reports.transactions') }}" wire:navigate class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.reports.transactions') ? 'bg-sidebar-light text-white font-semibold' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                            <div class="flex-shrink-0 w-6 flex justify-center"><x-lucide name="receipt" class="w-4 h-4" /></div>
                            <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-32 opacity-100'"><span>Laporan Penjualan</span></div>
                        </a>
                        @endcan

                        @can('view_sales_reports')
                        <a href="{{ route('admin.reports.sales') }}" wire:navigate class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.reports.sales') ? 'bg-sidebar-light text-white font-semibold' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                            <div class="flex-shrink-0 w-6 flex justify-center"><x-lucide name="bar-chart-3" class="w-4 h-4" /></div>
                            <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-32 opacity-100'"><span>Analisa & Performa</span></div>
                        </a>
                        @endcan

                        @can('view_all_shifts')
                        <a href="{{ route('admin.reports.shifts') }}" wire:navigate class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.reports.shifts') ? 'bg-sidebar-light text-white font-semibold' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                            <div class="flex-shrink-0 w-6 flex justify-center"><x-lucide name="file-clock" class="w-4 h-4" /></div>
                            <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-32 opacity-100'"><span>Laporan Shift</span></div>
                        </a>
                        @endcan

                        @can('view_cancellation_reports')
                        <a href="{{ route('admin.returns') }}" wire:navigate class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.returns') ? 'bg-sidebar-light text-white font-semibold' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                            <div class="flex-shrink-0 w-6 flex justify-center"><x-lucide name="rotate-ccw" class="w-4 h-4" /></div>
                            <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-32 opacity-100'"><span>Laporan Retur</span></div>
                        </a>
                        @endcan

                        @can('view_inventory_reports')
                        <div class="pt-2 pb-1 px-3 text-[10px] font-bold text-blue-300 uppercase tracking-wider">Inventori & Stok</div>

                        <a href="{{ route('admin.reports.inventory', ['activeTab' => 'valuation']) }}" wire:navigate class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.reports.inventory') && request('activeTab', 'valuation') === 'valuation' ? 'bg-sidebar-light text-white font-semibold' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                            <div class="flex-shrink-0 w-6 flex justify-center"><x-lucide name="boxes" class="w-4 h-4" /></div>
                            <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-32 opacity-100'"><span>Stok Bahan Baku</span></div>
                        </a>

                        <a href="{{ route('admin.reports.inventory', ['activeTab' => 'components']) }}" wire:navigate class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.reports.inventory') && request('activeTab') === 'components' ? 'bg-sidebar-light text-white font-semibold' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                            <div class="flex-shrink-0 w-6 flex justify-center"><x-lucide name="layers" class="w-4 h-4" /></div>
                            <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-32 opacity-100'"><span>Stok Komponen</span></div>
                        </a>

                        <a href="{{ route('admin.reports.inventory', ['activeTab' => 'purchases']) }}" wire:navigate class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.reports.inventory') && request('activeTab') === 'purchases' ? 'bg-sidebar-light text-white font-semibold' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                            <div class="flex-shrink-0 w-6 flex justify-center"><x-lucide name="shopping-cart" class="w-4 h-4" /></div>
                            <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-32 opacity-100'"><span>Laporan Pembelian</span></div>
                        </a>

                        <a href="{{ route('admin.reports.inventory', ['activeTab' => 'productions']) }}" wire:navigate class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.reports.inventory') && request('activeTab') === 'productions' ? 'bg-sidebar-light text-white font-semibold' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                            <div class="flex-shrink-0 w-6 flex justify-center"><x-lucide name="utensils-crossed" class="w-4 h-4" /></div>
                            <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-32 opacity-100'"><span>Laporan Repacking</span></div>
                        </a>

                        <a href="{{ route('admin.reports.inventory', ['activeTab' => 'stock_logs']) }}" wire:navigate class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.reports.inventory') && request('activeTab') === 'stock_logs' ? 'bg-sidebar-light text-white font-semibold' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                            <div class="flex-shrink-0 w-6 flex justify-center"><x-lucide name="history" class="w-4 h-4" /></div>
                            <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-32 opacity-100'"><span>Mutasi Bahan Baku</span></div>
                        </a>

                        <a href="{{ route('admin.reports.inventory', ['activeTab' => 'component_stock_logs']) }}" wire:navigate class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.reports.inventory') && request('activeTab') === 'component_stock_logs' ? 'bg-sidebar-light text-white font-semibold' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                            <div class="flex-shrink-0 w-6 flex justify-center"><x-lucide name="file-clock" class="w-4 h-4" /></div>
                            <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-32 opacity-100'"><span>Mutasi Komponen</span></div>
                        </a>

                        <a href="{{ route('admin.reports.inventory', ['activeTab' => 'low_stock']) }}" wire:navigate class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.reports.inventory') && request('activeTab') === 'low_stock' ? 'bg-sidebar-light text-white font-semibold' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                            <div class="flex-shrink-0 w-6 flex justify-center"><x-lucide name="alert-triangle" class="w-4 h-4" /></div>
                            <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-32 opacity-100'"><span>Stok Menipis</span></div>
                        </a>
                        @endcan
                    </div>
                </div>
                @endcanany

                <!-- Group: Pengaturan -->
                @php
                    $isSettingsActive = request()->routeIs('admin.users.*') ||
                                        request()->routeIs('admin.roles.*') ||
                                        request()->routeIs('admin.settings.*') ||
                                        request()->routeIs('admin.whatsapp.*');
                @endphp
                @canany(['view_users', 'manage_roles', 'manage_settings', 'manage_whatsapp'])
                <div x-data="{ open: {{ $isSettingsActive ? 'true' : 'false' }} }" class="mt-2">
                    <button @click="open = !open" class="w-full flex items-center justify-between px-3 py-2 text-xs font-semibold text-blue-300 uppercase tracking-wider hover:text-white transition-colors group whitespace-nowrap text-left">
                         <div class="flex items-center gap-3">
                            <div class="flex-shrink-0 w-6 flex justify-center"><x-lucide name="settings-2" class="w-5 h-5 text-blue-300 group-hover:text-white" /></div>
                            <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-32 opacity-100'">
                                <span>Pengaturan</span>
                            </div>
                        </div>
                        <span class="transform transition-transform duration-200 flex-shrink-0" :class="[open ? 'rotate-90' : '', isCompact ? 'hidden' : 'block']">
                            <x-lucide name="chevron-right" class="w-4 h-4" />
                        </span>
                    </button>
                    <div x-show="open && !isCompact" x-cloak 
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="ml-6 pl-3 border-l border-sidebar-light/40 space-y-1 mt-1">
                        @can('view_users')
                        <a href="{{ route('admin.users.index') }}" wire:navigate class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.users.*') ? 'bg-sidebar-light text-white' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                            <div class="flex-shrink-0 w-6 flex justify-center"><x-lucide name="users" class="w-4 h-4" /></div>
                            <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-32 opacity-100'"><span>Pengguna</span></div>
                        </a>
                        @endcan

                        @can('manage_roles')
                        <a href="{{ route('admin.roles.index') }}" wire:navigate class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.roles.*') ? 'bg-sidebar-light text-white' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                            <div class="flex-shrink-0 w-6 flex justify-center"><x-lucide name="shield" class="w-4 h-4" /></div>
                            <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-32 opacity-100'"><span>Role & Permission</span></div>
                        </a>
                        @endcan
                        
                        @can('manage_settings')
                        <a href="{{ route('admin.settings.index') }}" wire:navigate class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.settings.*') ? 'bg-sidebar-light text-white' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                            <div class="flex-shrink-0 w-6 flex justify-center"><x-lucide name="settings" class="w-4 h-4" /></div>
                            <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-32 opacity-100'"><span>Pengaturan</span></div>
                        </a>
                        @endcan

                        @can('manage_whatsapp')
                        <a href="{{ route('admin.whatsapp.index') }}" wire:navigate class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.whatsapp.*') ? 'bg-sidebar-light text-white' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                            <div class="flex-shrink-0 w-6 flex justify-center"><x-lucide name="message-circle" class="w-4 h-4" /></div>
                            <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-32 opacity-100'"><span>WhatsApp</span></div>
                        </a>
                        @endcan
                    </div>
                </div>
                @endcanany
            </nav>

            <!-- Logout Button Trigger -->
            <div class="p-4 border-t border-sidebar-light">
                <button @click="showLogoutModal = true" type="button" class="flex items-center gap-3 w-full px-3 py-3 rounded-lg bg-red-500/20 text-red-300 hover:bg-red-500/30 transition-colors whitespace-nowrap">
                    <div class="flex-shrink-0 w-6 flex justify-center"><x-lucide name="log-out" class="w-5 h-5" /></div>
                    <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-32 opacity-100'"><span>Logout</span></div>
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col overflow-hidden transition-[margin] duration-300 lg:transition-none lg:ml-64" :class="isSidebarCollapsed ? 'lg:ml-20' : 'lg:ml-64'">
            <!-- Header -->
            <header class="bg-white border-b px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <!-- Hamburger Button (Mobile) -->
                        <button @click="sidebarOpen = true" class="lg:hidden text-gray-500 hover:text-gray-700">
                            <x-lucide name="menu" class="w-6 h-6" />
                        </button>
                        
                        <!-- Collapse Toggle (Desktop) -->
                        <button @click="toggleSidebar()" class="hidden lg:block text-gray-500 hover:text-blue-600 transition-colors transform active:scale-95">
                            <x-lucide name="align-justify" class="w-6 h-6" />
                        </button>

                        <div>
                            <h2 class="text-xl font-bold text-gray-800">{{ $title ?? 'Dashboard' }}</h2>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="hidden sm:flex items-center gap-2 text-gray-600" x-data="realtimeClock()" x-init="startClock()">
                            <x-lucide name="calendar" class="w-4 h-4" />
                            <span>{{ now()->locale('id')->isoFormat('dddd, D MMMM Y') }}</span>
                            <span class="text-gray-400">•</span>
                            <x-lucide name="clock" class="w-4 h-4" />
                            <span x-text="time" class="font-medium tabular-nums"></span>
                        </div>
                        <div class="h-8 w-px bg-gray-200 hidden sm:block"></div>
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 bg-primary-100 rounded-full flex items-center justify-center">
                                <x-lucide name="user" class="w-5 h-5 text-primary-600" />
                            </div>
                            <div class="text-right hidden sm:block">
                                <p class="text-sm font-medium text-gray-800">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-gray-500">{{ auth()->user()->roles->first()?->name ?? 'User' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto p-4 sm:p-6 custom-scroll">
                {{ $slot }}
            </div>
        </main>

        <!-- Cool Logout Modal (Glassmorphism) -->
        <div 
            x-show="showLogoutModal" 
            style="display: none;"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
        >
            <div 
                x-show="showLogoutModal"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-90"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-90"
                @click.away="showLogoutModal = false"
                class="bg-white/80 backdrop-blur-md rounded-2xl shadow-2xl p-8 max-w-sm w-full mx-4 border border-white/50 text-center"
            >
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <x-lucide name="log-out" class="w-8 h-8 text-red-600" />
                </div>
                
                <h3 class="text-xl font-bold text-gray-800 mb-2">Konfirmasi Logout</h3>
                <p class="text-gray-600 mb-6">Apakah Anda yakin ingin keluar dari aplikasi?</p>
                
                <div class="flex gap-3">
                    <button 
                        @click="showLogoutModal = false" 
                        class="flex-1 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl transition-colors"
                    >
                        Batal
                    </button>
                    
                    <form action="{{ route('logout') }}" method="POST" class="flex-1">
                        @csrf
                        <button 
                            type="submit" 
                            class="w-full py-2.5 bg-red-600 hover:bg-red-700 text-white font-medium rounded-xl shadow-lg shadow-red-600/30 transition-colors"
                        >
                            Ya, Keluar
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>

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
        x-on:notify.window="add($event.detail)"
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
                class="px-4 py-3 rounded-lg shadow-lg text-white font-medium flex items-center gap-2"
            >
            <span x-text="notification.message"></span>
            </div>
        </template>
    </div>
    
    <!-- Global Confirm Modal -->
    <div 
        x-data="confirmModal()"
        x-on:confirm-action.window="open($event.detail)"
        x-show="isOpen"
        x-cloak
        class="fixed inset-0 z-[100] flex items-center justify-center p-4"
    >
        <!-- Backdrop -->
        <div 
            x-show="isOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="cancel()"
            class="absolute inset-0 bg-black/50"
        ></div>
        
        <!-- Modal -->
        <div 
            x-show="isOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden"
        >
            <!-- Header with Icon -->
            <div class="p-6 pb-2 text-center">
                <div 
                    class="w-16 h-16 mx-auto rounded-full flex items-center justify-center mb-4"
                    :class="type === 'danger' ? 'bg-red-100' : 'bg-yellow-100'"
                >
                    <svg 
                        class="w-8 h-8" 
                        :class="type === 'danger' ? 'text-red-600' : 'text-yellow-600'"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800" x-text="title"></h3>
            </div>
            
            <!-- Body -->
            <div class="px-6 pb-4 text-center">
                <p class="text-gray-600" x-text="message"></p>
            </div>
            
            <!-- Footer -->
            <div class="p-4 bg-gray-50 flex gap-3">
                <button 
                    @click="cancel()"
                    class="flex-1 py-2.5 px-4 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium rounded-xl transition-colors"
                >
                    Batal
                </button>
                <button 
                    @click="confirm()"
                    class="flex-1 py-2.5 px-4 font-medium rounded-xl transition-colors"
                    :class="type === 'danger' ? 'bg-red-600 hover:bg-red-700 text-white' : 'bg-primary-600 hover:bg-primary-700 text-white'"
                    x-text="confirmText"
                >
                </button>
            </div>
        </div>
    </div>
    
    <!-- Livewire Scripts -->
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

        function initLayoutTheme() {
            try {
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
                if (document.body) {
                    const currentFont = document.body.getAttribute('data-font-web');
                    if (currentFont) {
                        applyDynamicFont(currentFont);
                    }
                }
            } catch (e) {
                console.error("Error initializing layout theme:", e);
            }
        }

        // Initialize on DOMContentLoaded (hard refresh)
        document.addEventListener('DOMContentLoaded', initLayoutTheme);

        // Re-initialize on Livewire navigation
        document.addEventListener('livewire:navigated', initLayoutTheme);

        // Cegah scroll wheel mouse mengubah nilai input number saat kursor
        // kebetulan berada di atasnya (browser secara default mengubah nilai
        // jika input number sedang fokus lalu di-scroll — risiko salah input
        // tanpa sengaja). Event delegation di document, jadi otomatis berlaku
        // untuk semua input number termasuk yang dirender ulang oleh Livewire.
        document.addEventListener('wheel', function (e) {
            if (document.activeElement && document.activeElement.tagName === 'INPUT' && document.activeElement.type === 'number') {
                document.activeElement.blur();
            }
        }, { passive: true });

        // Handle Real-Time Settings Updates for SPA/wire:navigate
        window.addEventListener('settings-updated', event => {
            const data = event.detail;
            const storeName = data.store_name;
            const logoWeb = data.logo_web;
            const siteLogo = data.site_logo;
            const logoFull = data.logo_full;
            const logoType = data.logo_type || 'single';
            const fontFamilyWeb = data.font_family_web;

            // Update Store Name in sidebar
            const storeNameEl = document.getElementById('sidebar-store-name');
            if (storeNameEl) {
                storeNameEl.textContent = storeName;
            }

            // Update Logo Web in sidebar
            const logoImg = document.getElementById('sidebar-logo-img');
            const logoPlaceholder = document.getElementById('sidebar-logo-placeholder');
            const logoContainer = logoImg ? logoImg.parentElement : (logoPlaceholder ? logoPlaceholder.parentElement : null);

            if (logoContainer) {
                if (logoWeb) {
                    logoContainer.innerHTML = `<img id="sidebar-logo-img" src="${logoWeb}" class="w-full h-full object-cover">`;
                } else {
                    logoContainer.innerHTML = `<div id="sidebar-logo-placeholder" class="w-full h-full flex items-center justify-center"><i data-lucide="soup" class="w-6 h-6 text-sidebar"></i></div>`;
                    if (window.lucide) window.lucide.createIcons({ nodes: logoContainer.querySelectorAll('[data-lucide]') });
                }
            }

            // Toggle mode Single (ikon+teks) vs Panjang (banner saja)
            const singleWrap = document.getElementById('sidebar-logo-single-wrap');
            const fullImg = document.getElementById('sidebar-logo-full-img');
            const showFull = logoType === 'full' && !!logoFull;
            if (singleWrap) singleWrap.style.display = showFull ? 'none' : '';
            if (fullImg) {
                if (logoFull) fullImg.src = logoFull;
                fullImg.style.display = showFull ? '' : 'none';
            }

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
        function initAlpineComponents() {
            // Global Alpine.js Money Input Component
            Alpine.data('moneyInput', (initialValue = 0, wireModel = null) => ({
                rawValue: initialValue,
                formatted: '',
                wireModel: wireModel,
                
                init() {
                    this.formatted = this.formatNumber(this.rawValue);
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
                    const value = input.value;
                    const oldFormattedLen = this.formatted.length;
                    
                    const digits = value.replace(/\D/g, '');
                    if (digits === '') {
                        this.rawValue = 0;
                        this.formatted = '';
                    } else {
                        this.rawValue = parseInt(digits) || 0;
                        this.formatted = this.formatNumber(this.rawValue);
                    }
                    
                    const newLen = this.formatted.length;
                    
                    this.$nextTick(() => {
                        const diff = newLen - oldFormattedLen; 
                        let newPos = Math.max(0, cursorPos + diff);
                        if (newLen === 0) newPos = 0;
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
            
            // Icon Dropdown Component for Categories
            Alpine.data('iconDropdown', (initialName = 'folder', initialLabel = 'Lainnya') => ({
                open: false,
                selectedName: initialName,
                selectedLabel: initialLabel,
                
                init() {
                    this.$nextTick(() => lucide.createIcons());
                },
                
                toggleDropdown() {
                    this.open = !this.open;
                    if (this.open) {
                        this.$nextTick(() => lucide.createIcons());
                    }
                },
                
                selectIcon(name, label) {
                    this.selectedName = name;
                    this.selectedLabel = label;
                    this.open = false;
                    this.$wire.set('icon', name, false);
                    
                    // Update the icon in the button
                    const iconContainer = this.$refs.iconContainer;
                    if (iconContainer) {
                        iconContainer.innerHTML = `<i data-lucide="${name}" class="w-4 h-4 text-primary-600"></i>`;
                        lucide.createIcons({ nodes: iconContainer.querySelectorAll('[data-lucide]') });
                    }
                }
            }));
            
            // Confirm Modal Component
            Alpine.data('confirmModal', () => ({
                isOpen: false,
                title: 'Konfirmasi',
                message: 'Apakah Anda yakin?',
                confirmText: 'Ya, Lanjutkan',
                type: 'danger',
                action: null,
                actionParams: null,
                
                open(detail) {
                    this.title = detail.title || 'Konfirmasi';
                    this.message = detail.message || 'Apakah Anda yakin?';
                    this.confirmText = detail.confirmText || 'Ya, Lanjutkan';
                    this.type = detail.type || 'danger';
                    this.action = detail.action || null;
                    this.actionParams = detail.params || null;
                    this.isOpen = true;
                },
                
                cancel() {
                    this.isOpen = false;
                    this.action = null;
                    this.actionParams = null;
                },
                
                confirm() {
                    if (this.action) {
                        const wire = Livewire.find(this.action.componentId);
                        if (wire) {
                            if (this.actionParams !== null) {
                                wire.call(this.action.method, this.actionParams);
                            } else {
                                wire.call(this.action.method);
                            }
                        }
                    }
                    this.isOpen = false;
                    this.action = null;
                    this.actionParams = null;
                }
            }));
            
            // Realtime Clock Component
            Alpine.data('realtimeClock', () => ({
                time: '',
                interval: null,
                
                startClock() {
                    this.updateTime();
                    this.interval = setInterval(() => this.updateTime(), 1000);
                },
                
                updateTime() {
                    const now = new Date();
                    const hours = String(now.getHours()).padStart(2, '0');
                    const minutes = String(now.getMinutes()).padStart(2, '0');
                    const seconds = String(now.getSeconds()).padStart(2, '0');
                    this.time = `${hours}:${minutes}:${seconds}`;
                },
                
                destroy() {
                    if (this.interval) {
                        clearInterval(this.interval);
                    }
                }
            }));
        }

        if (window.Alpine) {
            initAlpineComponents();
        } else {
            document.addEventListener('alpine:init', initAlpineComponents);
        }

        // Global Lucide Icon re-creation for Livewire SPA navigation and updates.
        // wire:navigate mengganti <body> secara penuh dan mengeksekusi ulang <script>
        // inline ini di setiap navigasi — tanpa guard ini, `let lucideDebounce` akan
        // redeclare pada navigasi kedua dan seterusnya, memicu SyntaxError yang
        // menghentikan sisa eksekusi script (termasuk observer/listener di bawahnya)
        // dan bisa membuat halaman jadi tidak responsif setelah navigasi sidebar.
        if (!window.__lucideNavObserverSetup) {
            window.__lucideNavObserverSetup = true;

            let lucideDebounce;
            const lucideObserver = new MutationObserver((mutations) => {
                let needsRebuild = false;
                for (const mutation of mutations) {
                    if (mutation.addedNodes.length) {
                        for (const node of mutation.addedNodes) {
                            if (node.nodeType === 1) { // ELEMENT_NODE
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

            document.addEventListener('livewire:navigated', () => {
                if (window.lucide) lucide.createIcons();
            });

            // Tangani update in-place Livewire (seperti pengetikan pada input pencarian)
            document.addEventListener('livewire:init', () => {
                Livewire.hook('morph.updated', () => {
                    clearTimeout(lucideDebounce);
                    lucideDebounce = setTimeout(() => {
                        if (window.lucide) lucide.createIcons();
                    }, 15);
                });
            });
        }
    </script>
</body>
</html>
