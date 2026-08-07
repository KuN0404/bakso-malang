<div class="min-h-screen bg-slate-50 text-slate-800 pb-16 font-sans">
    <!-- Navbar Sticky -->
    <nav class="sticky top-0 z-30 bg-white/95 backdrop-blur-md border-b border-slate-100 transition-all">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <!-- Brand Logo & Name from Settings -->
            <a href="{{ route('home') }}" class="flex items-center gap-3.5 group">
                @if($logoType === 'full' && $logoFull)
                    <img src="{{ asset('storage/' . $logoFull) }}" alt="{{ $storeName }}" class="h-11 w-auto max-w-[200px] object-contain group-hover:scale-105 transition-transform">
                @else
                    @if($logoWeb)
                        <img src="{{ asset('storage/' . $logoWeb) }}" alt="{{ $storeName }}" class="w-11 h-11 object-cover rounded-xl shadow-xs group-hover:scale-105 transition-transform border border-slate-100">
                    @else
                        <div class="w-11 h-11 bg-primary-600 rounded-xl flex items-center justify-center text-white shadow-xs group-hover:scale-105 transition-transform">
                            <x-lucide name="soup" class="w-6 h-6" />
                        </div>
                    @endif
                    <div>
                        <span class="text-xl font-extrabold text-slate-900 tracking-tight block leading-none">
                            {{ $storeName }}
                        </span>
                        <span class="text-xs text-primary-600 font-medium tracking-wide">Digital Menu</span>
                    </div>
                @endif
            </a>

            <!-- Nav Links -->
            <div class="flex items-center gap-4">
                <a 
                    href="{{ route('home') }}" 
                    class="px-4 py-2 text-slate-600 hover:text-slate-900 text-sm font-semibold rounded-xl transition-colors hidden sm:flex items-center gap-1.5"
                >
                    <x-lucide name="arrow-left" class="w-4 h-4" />
                    <span>Kembali ke Beranda</span>
                </a>
                <a 
                    href="{{ route('login') }}" 
                    class="px-4 py-2.5 text-slate-600 hover:text-slate-900 hover:bg-slate-100 text-sm font-semibold rounded-xl transition-colors border border-slate-200"
                >
                    Staff POS
                </a>
            </div>
        </div>
    </nav>

    <!-- Header Section -->
    <header class="bg-white border-b border-slate-100 py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                <div>
                    <span class="inline-block px-3.5 py-1 bg-primary-50 border border-primary-100 text-primary-700 text-xs font-bold rounded-full uppercase tracking-wider mb-2">
                        Katalog Lengkap Produk
                    </span>
                    <h1 class="text-3xl md:text-4xl font-extrabold text-slate-900 tracking-tight">
                        Daftar Menu & Harga
                    </h1>
                    <p class="text-slate-500 text-sm md:text-base mt-1">Pilih sajian kesukaan Anda dan nikmati secara langsung</p>
                </div>

                <!-- Search Bar -->
                <div class="w-full md:w-80">
                    <div class="relative">
                        <input 
                            type="text" 
                            wire:model.live.debounce.300ms="search"
                            placeholder="Cari nama atau deskripsi menu..." 
                            class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:bg-white transition-all text-sm"
                        >
                        <x-lucide name="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                        @if($search)
                            <button wire:click="$set('search', '')" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                <x-lucide name="x" class="w-4 h-4" />
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-8">
        <!-- Categories Horizontal Filter Bar -->
        <div class="sticky top-20 z-20 bg-slate-50/95 backdrop-blur-md py-3 mb-8 border-b border-slate-200/80 -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
            <div class="flex items-center gap-2 overflow-x-auto custom-scroll pb-1">
                <button 
                    wire:click="selectCategory(null)"
                    class="px-5 py-2.5 rounded-xl font-bold text-xs whitespace-nowrap transition-all duration-200 shadow-2xs flex items-center gap-2 {{ is_null($selectedCategory) ? 'bg-primary-600 text-white shadow-sm' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200' }}"
                >
                    <x-lucide name="grid-3x3" class="w-4 h-4" />
                    <span>Semua Menu</span>
                </button>

                @foreach($categories as $category)
                    <button 
                        wire:click="selectCategory({{ $category->id }})"
                        class="px-5 py-2.5 rounded-xl font-bold text-xs whitespace-nowrap transition-all duration-200 shadow-2xs flex items-center gap-2 {{ $selectedCategory === $category->id ? 'bg-primary-600 text-white shadow-sm' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200' }}"
                    >
                        <x-lucide name="tag" class="w-4 h-4" />
                        <span>{{ $category->name }}</span>
                        <span class="text-[11px] px-2 py-0.5 rounded-full {{ $selectedCategory === $category->id ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500' }}">
                            {{ $category->products_count }}
                        </span>
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Products Grid -->
        @if($products->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                @foreach($products as $product)
                    <div 
                        wire:click="openProductModal({{ $product->id }})"
                        class="bg-white rounded-2xl shadow-xs hover:shadow-md border border-slate-100 overflow-hidden transition-all duration-300 transform hover:-translate-y-1 cursor-pointer flex flex-col group"
                    >
                        <!-- Image -->
                        <div class="relative h-48 sm:h-52 w-full bg-slate-100 overflow-hidden">
                            @if($product->image)
                                <img 
                                    src="{{ asset('storage/' . $product->image) }}" 
                                    alt="{{ $product->name }}" 
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                >
                            @else
                                <div class="w-full h-full flex flex-col items-center justify-center bg-slate-100 text-slate-300">
                                    <x-lucide name="soup" class="w-16 h-16 stroke-[1.5]" />
                                </div>
                            @endif

                            <div class="absolute top-3 left-3">
                                <span class="bg-slate-900/80 backdrop-blur-md text-white text-xs font-bold px-3 py-1 rounded-full">
                                    {{ $product->category->name ?? 'Umum' }}
                                </span>
                            </div>

                            @if($product->is_featured)
                                <div class="absolute top-3 right-3">
                                    <span class="bg-amber-500 text-white text-xs font-bold px-2.5 py-1 rounded-full shadow-xs">
                                        ★ Favorit
                                    </span>
                                </div>
                            @endif

                            @if(!$product->isAvailable())
                                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-[2px] flex items-center justify-center">
                                    <span class="bg-red-600 text-white text-xs font-bold px-4 py-1.5 rounded-full uppercase tracking-wider">
                                        Habis
                                    </span>
                                </div>
                            @endif
                        </div>

                        <!-- Content -->
                        <div class="p-5 flex-1 flex flex-col justify-between space-y-3">
                            <div>
                                <h3 class="font-bold text-slate-900 text-base leading-snug group-hover:text-primary-600 transition-colors line-clamp-1">
                                    {{ $product->name }}
                                </h3>
                                @if($product->description)
                                    <p class="text-xs text-slate-500 mt-1 line-clamp-2 leading-relaxed">
                                        {{ $product->description }}
                                    </p>
                                @endif
                            </div>

                            <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                                <div>
                                    <span class="text-[11px] text-slate-400 font-medium block">Harga</span>
                                    <span class="text-lg font-extrabold text-primary-600">
                                        Rp {{ number_format($product->price, 0, ',', '.') }}
                                    </span>
                                </div>
                                <span class="p-2.5 bg-slate-100 text-slate-600 group-hover:bg-primary-600 group-hover:text-white rounded-xl transition-colors">
                                    <x-lucide name="eye" class="w-4 h-4" />
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-8">
                {{ $products->links() }}
            </div>
        @else
            <!-- Empty State -->
            <div class="bg-white rounded-2xl p-12 text-center border border-slate-100 max-w-lg mx-auto my-12 shadow-xs">
                <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-400">
                    <x-lucide name="search" class="w-8 h-8" />
                </div>
                <h3 class="text-lg font-bold text-slate-800">Menu Tidak Ditemukan</h3>
                <p class="text-slate-500 text-xs mt-1">Coba sesuaikan pencarian atau pilih kategori lainnya.</p>
                <button 
                    wire:click="selectCategory(null); $set('search', '')" 
                    class="mt-5 px-5 py-2.5 bg-primary-600 text-white rounded-xl text-xs font-bold hover:bg-primary-700 transition-colors"
                >
                    Reset Filter
                </button>
            </div>
        @endif
    </main>

    <!-- Product Detail Modal -->
    @if($showProductModal && $selectedProduct)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" wire:click="closeProductModal"></div>

            <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full z-10 overflow-hidden relative flex flex-col max-h-[90vh]">
                <button 
                    wire:click="closeProductModal" 
                    class="absolute top-4 right-4 z-20 w-9 h-9 bg-slate-900/50 hover:bg-slate-900/70 text-white rounded-full flex items-center justify-center transition-colors"
                >
                    <x-lucide name="x" class="w-5 h-5" />
                </button>

                <div class="relative h-64 w-full bg-slate-100 flex-shrink-0">
                    @if($selectedProduct->image)
                        <img src="{{ asset('storage/' . $selectedProduct->image) }}" class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full flex flex-col items-center justify-center bg-slate-100 text-slate-300">
                            <x-lucide name="soup" class="w-20 h-20 stroke-[1.5]" />
                        </div>
                    @endif
                    <div class="absolute bottom-4 left-4">
                        <span class="bg-primary-600 text-white text-xs font-bold px-3.5 py-1.5 rounded-full shadow-xs">
                            {{ $selectedProduct->category->name ?? 'Kategori' }}
                        </span>
                    </div>
                </div>

                <div class="p-6 overflow-y-auto space-y-4 custom-scroll flex-1">
                    <div>
                        <div class="flex justify-between items-start gap-4">
                            <h2 class="text-2xl font-bold text-slate-900 leading-tight">{{ $selectedProduct->name }}</h2>
                            <span class="text-2xl font-black text-primary-600 shrink-0">
                                Rp {{ number_format($selectedProduct->price, 0, ',', '.') }}
                            </span>
                        </div>
                        @if($selectedProduct->description)
                            <p class="text-sm text-slate-600 mt-2 bg-slate-50 p-3.5 rounded-xl border border-slate-100">
                                {{ $selectedProduct->description }}
                            </p>
                        @endif
                    </div>

                    @if($selectedProduct->modifierGroups && $selectedProduct->modifierGroups->count() > 0)
                        <div class="space-y-3 pt-2">
                            <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                                <x-lucide name="sliders" class="w-4 h-4 text-primary-600" />
                                <span>Pilihan Toping / Variasi</span>
                            </h4>

                            @foreach($selectedProduct->modifierGroups as $group)
                                <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="font-semibold text-xs text-slate-700 uppercase tracking-wider">{{ $group->name }}</span>
                                        <span class="text-[10px] bg-slate-200 text-slate-600 px-2 py-0.5 rounded-full font-medium">
                                            {{ $group->selection_type === 'single' ? 'Pilih 1' : 'Bisa Banyak' }}
                                        </span>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($group->activeModifiers as $mod)
                                            <div class="bg-white border border-slate-200 px-3 py-1.5 rounded-lg text-xs flex items-center gap-1.5">
                                                <span class="font-medium text-slate-800">{{ $mod->name }}</span>
                                                @if($mod->price_adjustment > 0)
                                                    <span class="text-primary-600 font-bold">+Rp {{ number_format($mod->price_adjustment, 0, ',', '.') }}</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="p-4 border-t bg-slate-50 flex-shrink-0 flex justify-end">
                    <button 
                        wire:click="closeProductModal" 
                        class="px-5 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 text-xs font-bold rounded-xl transition-colors"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
