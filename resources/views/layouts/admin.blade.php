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
    
    <!-- Livewire Styles (includes Alpine.js) -->
    @livewireStyles
    
    <style>
        [x-cloak] { display: none !important; }
        
        /* Custom scrollbar */
        .custom-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scroll::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 3px; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        .custom-scroll::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="h-full bg-gray-100 antialiased">
    <div x-data="{ sidebarOpen: false, showLogoutModal: false }" class="flex h-screen bg-gray-100">
        
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

        <!-- Sidebar -->
        <aside 
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-30 w-64 bg-sidebar flex flex-col transform transition-transform duration-300 lg:static lg:translate-x-0"
        >
            <!-- Logo -->
            <div class="p-5 border-b border-sidebar-light flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center">
                        <i data-lucide="soup" class="w-6 h-6 text-sidebar"></i>
                    </div>
                    <div>
                        <h1 class="text-white font-bold text-lg">BAKSO MALANG</h1>
                        <p class="text-blue-200 text-xs">Point of Sales</p>
                    </div>
                </div>
                <!-- Close Button (Mobile Only) -->
                <button @click="sidebarOpen = false" class="lg:hidden text-blue-200 hover:text-white">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 p-4 space-y-1 overflow-y-auto custom-scroll">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('admin.dashboard') ? 'bg-sidebar-light text-white' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                    <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                    <span>Dashboard</span>
                </a>

                <p class="px-4 pt-4 pb-2 text-xs font-semibold text-blue-300 uppercase tracking-wider">Master Data</p>
                
                <a href="{{ route('admin.categories.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('admin.categories.*') ? 'bg-sidebar-light text-white' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                    <i data-lucide="folder" class="w-5 h-5"></i>
                    <span>Kategori</span>
                </a>
                
                <a href="{{ route('admin.products.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('admin.products.*') ? 'bg-sidebar-light text-white' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                    <i data-lucide="package" class="w-5 h-5"></i>
                    <span>Produk</span>
                </a>

                <a href="{{ route('admin.modifiers.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('admin.modifiers.*') ? 'bg-sidebar-light text-white' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                    <i data-lucide="sliders" class="w-5 h-5"></i>
                    <span>Modifier</span>
                </a>

                <a href="{{ route('admin.payment-sources.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('admin.payment-sources.*') ? 'bg-sidebar-light text-white' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                    <i data-lucide="wallet" class="w-5 h-5"></i>
                    <span>Metode Bayar</span>
                </a>

                <p class="px-4 pt-4 pb-2 text-xs font-semibold text-blue-300 uppercase tracking-wider">Transaksi</p>
                
                <a href="{{ route('pos') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg text-blue-200 hover:bg-sidebar-light hover:text-white transition-colors">
                    <i data-lucide="monitor" class="w-5 h-5"></i>
                    <span>POS Kasir</span>
                </a>
                
                <a href="{{ route('admin.transactions.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('admin.transactions.*') ? 'bg-sidebar-light text-white' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                    <i data-lucide="receipt" class="w-5 h-5"></i>
                    <span>Transaksi</span>
                </a>
                
                <a href="{{ route('admin.shifts.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('admin.shifts.*') ? 'bg-sidebar-light text-white' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                    <i data-lucide="clock" class="w-5 h-5"></i>
                    <span>Shift</span>
                </a>

                <p class="px-4 pt-4 pb-2 text-xs font-semibold text-blue-300 uppercase tracking-wider">Laporan</p>
                
                <a href="{{ route('admin.reports.sales') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('admin.reports.sales') ? 'bg-sidebar-light text-white' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                    <i data-lucide="bar-chart-3" class="w-5 h-5"></i>
                    <span>Laporan Penjualan</span>
                </a>

                <a href="{{ route('admin.reports.shifts') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('admin.reports.shifts') ? 'bg-sidebar-light text-white' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                    <i data-lucide="file-clock" class="w-5 h-5"></i>
                    <span>Laporan Shift</span>
                </a>

                <a href="{{ route('admin.returns') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('admin.returns') ? 'bg-sidebar-light text-white' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                    <i data-lucide="rotate-ccw" class="w-5 h-5"></i>
                    <span>Laporan Retur</span>
                </a>

                <p class="px-4 pt-4 pb-2 text-xs font-semibold text-blue-300 uppercase tracking-wider">Pengaturan</p>
                
                <a href="{{ route('admin.users.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('admin.users.*') ? 'bg-sidebar-light text-white' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                    <i data-lucide="users" class="w-5 h-5"></i>
                    <span>Pengguna</span>
                </a>

                <a href="{{ route('admin.roles.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('admin.roles.*') ? 'bg-sidebar-light text-white' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                    <i data-lucide="shield" class="w-5 h-5"></i>
                    <span>Role & Permission</span>
                </a>
                
                <a href="{{ route('admin.settings.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('admin.settings.*') ? 'bg-sidebar-light text-white' : 'text-blue-200 hover:bg-sidebar-light hover:text-white' }} transition-colors">
                    <i data-lucide="settings" class="w-5 h-5"></i>
                    <span>Pengaturan</span>
                </a>
            </nav>

            <!-- Logout Button Trigger -->
            <div class="p-4 border-t border-sidebar-light">
                <button @click="showLogoutModal = true" type="button" class="flex items-center gap-3 w-full px-4 py-3 rounded-lg bg-red-500/20 text-red-300 hover:bg-red-500/30 transition-colors">
                    <i data-lucide="log-out" class="w-5 h-5"></i>
                    <span>Logout</span>
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="bg-white border-b px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <!-- Hamburger Button -->
                        <button @click="sidebarOpen = true" class="lg:hidden text-gray-500 hover:text-gray-700">
                            <i data-lucide="menu" class="w-6 h-6"></i>
                        </button>
                        
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">{{ $title ?? 'Dashboard' }}</h2>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="hidden sm:flex items-center gap-2 text-gray-600" x-data="realtimeClock()" x-init="startClock()">
                            <i data-lucide="calendar" class="w-4 h-4"></i>
                            <span>{{ now()->locale('id')->isoFormat('dddd, D MMMM Y') }}</span>
                            <span class="text-gray-400">•</span>
                            <i data-lucide="clock" class="w-4 h-4"></i>
                            <span x-text="time" class="font-medium tabular-nums"></span>
                        </div>
                        <div class="h-8 w-px bg-gray-200 hidden sm:block"></div>
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 bg-primary-100 rounded-full flex items-center justify-center">
                                <i data-lucide="user" class="w-5 h-5 text-primary-600"></i>
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
                    <i data-lucide="log-out" class="w-8 h-8 text-red-600"></i>
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
                    this.formatted = this.rawValue > 0 ? this.formatNumber(this.rawValue) : '';
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
                    this.formatted = this.rawValue > 0 ? this.formatNumber(this.rawValue) : '';
                    
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
    </script>
</body>
</html>
