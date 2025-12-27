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
    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-sidebar flex flex-col">
            <!-- Logo -->
            <div class="p-5 border-b border-sidebar-light">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center">
                        <i data-lucide="soup" class="w-6 h-6 text-sidebar"></i>
                    </div>
                    <div>
                        <h1 class="text-white font-bold text-lg">BAKSO MALANG</h1>
                        <p class="text-blue-200 text-xs">Point of Sales</p>
                    </div>
                </div>
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

            <!-- Logout -->
            <div class="p-4 border-t border-sidebar-light">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="flex items-center gap-3 w-full px-4 py-3 rounded-lg bg-red-500/20 text-red-300 hover:bg-red-500/30 transition-colors">
                        <i data-lucide="log-out" class="w-5 h-5"></i>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="bg-white border-b px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800">{{ $header ?? 'Dashboard' }}</h2>
                        @if(isset($subtitle))
                            <p class="text-gray-500 text-sm">{{ $subtitle }}</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2 text-gray-600">
                            <i data-lucide="calendar" class="w-4 h-4"></i>
                            <span>{{ now()->locale('id')->isoFormat('dddd, D MMMM Y') }}</span>
                        </div>
                        <div class="h-8 w-px bg-gray-200"></div>
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 bg-primary-100 rounded-full flex items-center justify-center">
                                <i data-lucide="user" class="w-5 h-5 text-primary-600"></i>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-gray-800">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-gray-500">{{ auth()->user()->roles->first()?->name ?? 'User' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto p-6 custom-scroll">
                {{ $slot }}
            </div>
        </main>
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
    
    <!-- Livewire Scripts -->
    @livewireScripts
    
    <script>
        // Initialize Lucide icons
        lucide.createIcons();
        
        // Re-initialize on Livewire navigation
        document.addEventListener('livewire:navigated', () => {
            lucide.createIcons();
        });
    </script>
</body>
</html>
