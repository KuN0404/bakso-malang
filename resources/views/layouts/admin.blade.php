<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard - Bakso Malang POS' }}</title>
    
    <!-- Google Fonts: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
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
                        sans: ['Poppins', 'sans-serif'],
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
    
    <!-- Livewire Styles (includes Alpine.js) -->
    @livewireStyles
    
    <style>
        [x-cloak] { display: none !important; }
        
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
        
        /* Flatpickr Custom Styling */
        .flatpickr-calendar {
            border-radius: 12px !important;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15) !important;
            border: 1px solid #e5e7eb !important;
        }
        .flatpickr-day.selected, 
        .flatpickr-day.startRange, 
        .flatpickr-day.endRange {
            background: #2563eb !important;
            border-color: #2563eb !important;
        }
        .flatpickr-day.inRange {
            background: #dbeafe !important;
            border-color: #dbeafe !important;
            box-shadow: none !important;
        }
        .flatpickr-day:hover {
            background: #eff6ff !important;
            border-color: #eff6ff !important;
        }
        .flatpickr-months .flatpickr-month {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%) !important;
            color: white !important;
            border-radius: 10px 10px 0 0 !important;
        }
        .flatpickr-current-month .flatpickr-monthDropdown-months,
        .flatpickr-current-month input.cur-year {
            color: white !important;
        }
        .flatpickr-weekday {
            color: #6b7280 !important;
            font-weight: 600 !important;
        }
    </style>
</head>
<body class="h-full bg-gray-100 antialiased">
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
                <div class="flex-shrink-0 w-10 h-10 bg-white rounded-lg flex items-center justify-center">
                    <x-lucide name="soup" class="w-6 h-6 text-sidebar" />
                </div>
                <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-40 opacity-100'">
                    <h1 class="text-white font-bold text-lg leading-tight">BAKSO MALANG</h1>
                    <p class="text-blue-200 text-xs">Point of Sales</p>
                </div>
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

                <!-- Group: Master Data -->
                @php
                    $isMasterActive = request()->routeIs('admin.categories.*') || 
                                      request()->routeIs('admin.products.*') || 
                                      request()->routeIs('admin.modifiers.*') || 
                                      request()->routeIs('admin.payment-sources.*') ||
                                      request()->routeIs('admin.service-areas.*');
                @endphp
                @canany(['view_categories', 'view_products', 'view_modifiers', 'manage_payment_sources', 'manage_settings'])
                <div x-data="{ open: {{ $isMasterActive ? 'true' : 'false' }} }" class="mt-4">
                    <button @click="open = !open" class="w-full flex items-center justify-between px-3 py-2 text-xs font-semibold text-blue-300 uppercase tracking-wider hover:text-white transition-colors group whitespace-nowrap text-left">
                        <div class="flex items-center gap-3">
                            <div class="flex-shrink-0 w-6 flex justify-center"><x-lucide name="database" class="w-5 h-5 text-blue-300 group-hover:text-white" /></div>
                            <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-32 opacity-100'">
                                <span>Master Data</span>
                            </div>
                        </div>
                        <x-lucide name="chevron-down" class="w-4 h-4 transition-transform duration-200 flex-shrink-0" ::class="[open ? 'rotate-180' : '', isCompact ? 'hidden' : 'block']" />
                    </button>
                    <!-- Force hidden if compact to avoid popup artifacts, rely on hover to expand first -->
                    <div x-show="open && !isCompact" 
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="space-y-1 mt-1">
                        @can('view_categories')
                        <a href="{{ route('admin.categories.index') }}" wire:navigate class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.categories.*') ? 'bg-sidebar-light text-white' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                            <div class="flex-shrink-0 w-6 flex justify-center"><x-lucide name="folder" class="w-4 h-4" /></div>
                            <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-32 opacity-100'"><span>Kategori</span></div>
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
                    </div>
                </div>
                @endcanany

                <!-- Group: Transaksi -->
                @php
                    $isTransActive = request()->routeIs('pos') || request()->routeIs('admin.shifts.*');
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
                        <x-lucide name="chevron-down" class="w-4 h-4 transition-transform duration-200 flex-shrink-0" ::class="[open ? 'rotate-180' : '', isCompact ? 'hidden' : 'block']" />
                    </button>
                    <div x-show="open && !isCompact" 
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="space-y-1 mt-1">
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
                @canany(['view_transactions', 'view_sales_reports', 'view_all_shifts', 'view_cancellation_reports'])
                <div x-data="{ open: {{ $isReportActive ? 'true' : 'false' }} }" class="mt-2">
                    <button @click="open = !open" class="w-full flex items-center justify-between px-3 py-2 text-xs font-semibold text-blue-300 uppercase tracking-wider hover:text-white transition-colors group whitespace-nowrap text-left">
                        <div class="flex items-center gap-3">
                            <div class="flex-shrink-0 w-6 flex justify-center"><x-lucide name="clipboard-list" class="w-5 h-5 text-blue-300 group-hover:text-white" /></div>
                            <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-32 opacity-100'">
                                <span>Laporan</span>
                            </div>
                        </div>
                        <x-lucide name="chevron-down" class="w-4 h-4 transition-transform duration-200 flex-shrink-0" ::class="[open ? 'rotate-180' : '', isCompact ? 'hidden' : 'block']" />
                    </button>
                    <div x-show="open && !isCompact" 
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="space-y-1 mt-1">
                        @can('view_transactions')
                        <a href="{{ route('admin.reports.transactions') }}" wire:navigate class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.reports.transactions') ? 'bg-sidebar-light text-white' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                            <div class="flex-shrink-0 w-6 flex justify-center"><x-lucide name="receipt" class="w-4 h-4" /></div>
                            <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-32 opacity-100'"><span>Penjualan</span></div>
                        </a>
                        @endcan

                        @can('view_sales_reports')
                        <a href="{{ route('admin.reports.sales') }}" wire:navigate class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.reports.sales') ? 'bg-sidebar-light text-white' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                            <div class="flex-shrink-0 w-6 flex justify-center"><x-lucide name="bar-chart-3" class="w-4 h-4" /></div>
                            <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-32 opacity-100'"><span>Analisa & Performa</span></div>
                        </a>
                        @endcan

                        @can('view_all_shifts')
                        <a href="{{ route('admin.reports.shifts') }}" wire:navigate class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.reports.shifts') ? 'bg-sidebar-light text-white' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                            <div class="flex-shrink-0 w-6 flex justify-center"><x-lucide name="file-clock" class="w-4 h-4" /></div>
                            <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-32 opacity-100'"><span>Laporan Shift</span></div>
                        </a>
                        @endcan

                        @can('view_cancellation_reports')
                        <a href="{{ route('admin.returns') }}" wire:navigate class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.returns') ? 'bg-sidebar-light text-white' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                            <div class="flex-shrink-0 w-6 flex justify-center"><x-lucide name="rotate-ccw" class="w-4 h-4" /></div>
                            <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-32 opacity-100'"><span>Laporan Retur</span></div>
                        </a>
                        @endcan
                    </div>
                </div>
                @endcanany

                <!-- Group: Pengaturan -->
                @php
                    $isSettingsActive = request()->routeIs('admin.users.*') || 
                                        request()->routeIs('admin.roles.*') || 
                                        request()->routeIs('admin.settings.*');
                @endphp
                @canany(['view_users', 'manage_roles', 'manage_settings'])
                <div x-data="{ open: {{ $isSettingsActive ? 'true' : 'false' }} }" class="mt-2">
                    <button @click="open = !open" class="w-full flex items-center justify-between px-3 py-2 text-xs font-semibold text-blue-300 uppercase tracking-wider hover:text-white transition-colors group whitespace-nowrap text-left">
                         <div class="flex items-center gap-3">
                            <div class="flex-shrink-0 w-6 flex justify-center"><x-lucide name="settings-2" class="w-5 h-5 text-blue-300 group-hover:text-white" /></div>
                            <div class="transition-all duration-300 overflow-hidden" :class="isCompact ? 'w-0 opacity-0' : 'w-32 opacity-100'">
                                <span>Pengaturan</span>
                            </div>
                        </div>
                        <x-lucide name="chevron-down" class="w-4 h-4 transition-transform duration-200 flex-shrink-0" ::class="[open ? 'rotate-180' : '', isCompact ? 'hidden' : 'block']" />
                    </button>
                    <div x-show="open && !isCompact" 
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="space-y-1 mt-1">
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
        <main class="flex-1 flex flex-col overflow-hidden transition-all duration-300 lg:transition-none lg:ml-64" :class="isSidebarCollapsed ? 'lg:ml-20' : 'lg:ml-64'">
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
    
    <!-- Toast Notifications -->
    <div 
        x-data="notificationHandler"
        class="fixed top-4 right-4 z-50 space-y-2 pointer-events-none"
    >
        <template x-for="notification in notifications" :key="notification.id">
            <div 
                x-show="notification.show"
                x-transition:enter="transition transform ease-out duration-300"
                x-transition:enter-start="translate-x-full opacity-0"
                x-transition:enter-end="translate-x-0 opacity-100"
                x-transition:leave="transition transform ease-in duration-300"
                x-transition:leave-start="translate-x-0 opacity-100"
                x-transition:leave-end="translate-x-full opacity-0"
                class="bg-white rounded-lg shadow-lg border-l-4 p-4 pointer-events-auto min-w-[300px] flex items-start gap-3"
                :class="{
                    'border-green-500': notification.type === 'success',
                    'border-red-500': notification.type === 'error',
                    'border-blue-500': notification.type === 'info'
                }"
            >
                <!-- Icon -->
                <div class="flex-shrink-0">
                    <svg x-show="notification.type === 'success'" class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <svg x-show="notification.type === 'error'" class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <svg x-show="notification.type === 'info'" class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                
                <!-- Content -->
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-900" x-text="notification.message"></p>
                </div>
                
                <!-- Close -->
                <button @click="remove(notification.id)" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </template>
    </div>

    <!-- Livewire Scripts -->
    @livewireScripts
    
    <script>
        // Initialize Lucide icons
        lucide.createIcons();
        
        // Re-initialize on Livewire navigation
        document.addEventListener('livewire:navigated', () => {
            lucide.createIcons();
        });
        
        // Global Alpine.js Money Input Component
        // Usage: x-data="moneyInput(initialValue, wireModel)"
        document.addEventListener('alpine:init', () => {
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
                    const oldLen = value.length; // Use current value length as reference or approximation? No, we need old length of FORMATTED before edit? 
                    // Actually, cursor logic depends on old formatted length.
                    // But accessing this.formatted is safe for "old length" (previous state).
                    // accessing input.value is safe for "new characters".
                    
                    const oldFormattedLen = this.formatted.length;
                    
                    // Get raw digits only from CURRENT INPUT
                    const digits = value.replace(/\D/g, '');
                    if (digits === '') {
                        this.rawValue = 0;
                        this.formatted = '';
                    } else {
                        this.rawValue = parseInt(digits) || 0;
                        this.formatted = this.formatNumber(this.rawValue);
                    }
                    
                    // Adjust cursor position
                    // We need to compare old formatted length vs new formatted length
                    // And adjust cursor based on that?
                    // The standard cursor logic:
                    // newPos = oldPos + (newLen - oldLen)
                    // But here oldPos is cursorPos (post-edit position).
                    // This logic is tricky. 
                    // Let's stick to the existing logic but FIX the source of 'digits'.
                    
                    // Re-calculate cursor position
                    const newLen = this.formatted.length;
                    
                    this.$nextTick(() => {
                        const diff = newLen - oldFormattedLen; 
                        // If new length is bigger, cursor usually moves forward effectively by diff? 
                        // Or if we just added a digit.
                        // Simple heuristic: 
                        // If appending digit, move forward. 
                        // If deleting digit, move back (diff is negative).
                        // Be careful about formatting chars (dots) appearing/disappearing.
                        
                        // Heuristic: Maintain relative distance from end? No.
                        // Maintain relative distance from start.
                        let newPos = cursorPos;
                        
                        // If formatting changed length (e.g. 1000 -> 1.000), diff is 1. 
                        // If cursor was at 2 (after 0), newPos should be 3 (after 0).
                        // So newPos = cursorPos + diff?
                        // If diff is due to formatting occurring BEFORE cursor?
                        // For money input, thousands separator usually appears to the left.
                        // So usually we just add diff.
                        
                        // But what if we deleted? diff is negative. 
                        // Cursor moves back.
                        
                        newPos = Math.max(0, cursorPos + diff);
                        
                        // Special case: if value is empty/0, ensure we are inside.
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
                        // Dispatch Livewire action
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
        });
            // Notification Handler
            Alpine.data('notificationHandler', () => ({
                notifications: [],
                
                init() {
                    window.addEventListener('notify', (event) => {
                        this.add(event.detail);
                    });
                },
                
                add(notification) {
                    const id = Date.now();
                    this.notifications.push({
                        id: id,
                        type: notification.type || 'info', // success, error, info
                        message: notification.message,
                        show: true,
                    });
                    
                    setTimeout(() => {
                        this.remove(id);
                    }, 3000);
                },
                
                remove(id) {
                    const index = this.notifications.findIndex(n => n.id === id);
                    if (index > -1) {
                        this.notifications[index].show = false;
                        setTimeout(() => {
                            this.notifications = this.notifications.filter(n => n.id !== id);
                        }, 300); // Wait for transition
                    }
                }
            }));
        });

        // Global Lucide Icon re-creation for Livewire SPA navigation and updates
        document.addEventListener('livewire:init', () => {
            Livewire.hook('request', ({ respond }) => {
                respond(() => {
                    queueMicrotask(() => {
                        if (window.lucide) {
                            lucide.createIcons();
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>
