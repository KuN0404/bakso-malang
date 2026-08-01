<div class="p-6 space-y-6">
    <!-- Header Page -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-extrabold text-gray-800 tracking-tight">Katalog Menu Produk</h1>
                <span class="bg-primary-50 text-primary-700 text-xs font-bold px-2.5 py-1 rounded-full border border-primary-200">
                    Modul Panel
                </span>
            </div>
            <p class="text-sm text-gray-500 mt-1">Pratinjau visual dan katalog produk makanan & minuman</p>
        </div>

        <div class="flex items-center gap-3">
            <a 
                href="{{ route('menu.public') }}" 
                target="_blank"
                class="px-4 py-2.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-sm font-bold rounded-xl transition-all flex items-center gap-2 border border-indigo-200 shadow-2xs"
            >
                <x-lucide name="eye" class="w-4 h-4" />
                <span>Buka Menu Publik</span>
            </a>

            @can('view_products')
                <a 
                    href="{{ route('admin.products.index') }}" 
                    class="px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white text-sm font-bold rounded-xl transition-all flex items-center gap-2 shadow-md shadow-primary-500/20"
                >
                    <x-lucide name="package" class="w-4 h-4" />
                    <span>Kelola Master Produk</span>
                </a>
            @endcan
        </div>
    </div>

    <!-- Summary Stats & Filters -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center font-bold">
                <x-lucide name="package" class="w-6 h-6" />
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium">Total Produk</p>
                <p class="text-xl font-extrabold text-gray-800">{{ $totalProductsCount }}</p>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 bg-green-50 text-green-600 rounded-xl flex items-center justify-center font-bold">
                <x-lucide name="check-circle" class="w-6 h-6" />
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium">Produk Aktif</p>
                <p class="text-xl font-extrabold text-gray-800">{{ $activeProductsCount }}</p>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 bg-purple-50 text-purple-600 rounded-xl flex items-center justify-center font-bold">
                <x-lucide name="folder" class="w-6 h-6" />
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium">Total Kategori</p>
                <p class="text-xl font-extrabold text-gray-800">{{ $categories->count() }}</p>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="bg-white p-3 rounded-2xl border border-gray-100 shadow-xs flex items-center">
            <div class="relative w-full">
                <input 
                    type="text" 
                    wire:model.live.debounce.300ms="search" 
                    placeholder="Cari produk / SKU..." 
                    class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none"
                >
                <x-lucide name="search" class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
            </div>
        </div>
    </div>

    <!-- Category Pill Filter -->
    <div class="flex items-center gap-2 overflow-x-auto custom-scroll pb-1">
        <button 
            wire:click="selectCategory(null)"
            class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition-colors {{ is_null($selectedCategory) ? 'bg-primary-600 text-white shadow-sm' : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200' }}"
        >
            Semua Kategori ({{ $totalProductsCount }})
        </button>
        @foreach($categories as $cat)
            <button 
                wire:click="selectCategory({{ $cat->id }})"
                class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition-colors {{ $selectedCategory === $cat->id ? 'bg-primary-600 text-white shadow-sm' : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200' }}"
            >
                {{ $cat->name }} ({{ $cat->products_count }})
            </button>
        @endforeach
    </div>

    <!-- Product Grid -->
    @if($products->count() > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            @foreach($products as $product)
                <div 
                    wire:click="showProductDetail({{ $product->id }})"
                    class="bg-white rounded-2xl border border-gray-100 shadow-xs hover:shadow-lg transition-all duration-300 overflow-hidden cursor-pointer flex flex-col group"
                >
                    <!-- Image -->
                    <div class="relative h-44 w-full bg-gray-100 overflow-hidden">
                        @if($product->image)
                            <img src="{{ asset('storage/' . $product->image) }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                        @else
                            <div class="w-full h-full flex flex-col items-center justify-center bg-gray-50 text-gray-300">
                                <x-lucide name="soup" class="w-12 h-12 stroke-[1.5]" />
                            </div>
                        @endif

                        <div class="absolute top-2.5 left-2.5">
                            <span class="bg-black/60 backdrop-blur-md text-white text-[11px] font-bold px-2.5 py-0.5 rounded-full">
                                {{ $product->category->name ?? 'Uncategorized' }}
                            </span>
                        </div>

                        <div class="absolute top-2.5 right-2.5">
                            @if($product->is_active)
                                <span class="bg-green-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider">
                                    Aktif
                                </span>
                            @else
                                <span class="bg-gray-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider">
                                    Nonaktif
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="p-4 flex-1 flex flex-col justify-between space-y-3">
                        <div>
                            <h3 class="font-bold text-gray-800 text-sm line-clamp-1 group-hover:text-primary-600 transition-colors">
                                {{ $product->name }}
                            </h3>
                            <p class="text-xs text-gray-400 font-mono mt-0.5">SKU: {{ $product->sku ?? '-' }}</p>
                        </div>

                        <div class="pt-2 border-t border-gray-100 flex items-center justify-between">
                            <span class="text-base font-extrabold text-primary-600">
                                Rp {{ number_format($product->price, 0, ',', '.') }}
                            </span>
                            @if($product->track_stock)
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-md {{ $product->stock > 0 ? 'bg-blue-50 text-blue-700' : 'bg-red-50 text-red-700' }}">
                                    Stok: {{ $product->stock }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $products->links() }}
        </div>
    @else
        <div class="bg-white rounded-2xl p-12 text-center border border-gray-100">
            <x-lucide name="search" class="w-12 h-12 text-gray-300 mx-auto mb-3" />
            <h3 class="font-bold text-gray-700 text-base">Tidak ada produk ditemukan</h3>
            <p class="text-xs text-gray-400 mt-1">Gunakan kata kunci lain atau reset filter kategori</p>
        </div>
    @endif

    <!-- Product Detail Modal -->
    @if($showDetailModal && $selectedProduct)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" wire:click="closeDetailModal"></div>

            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full z-10 overflow-hidden relative flex flex-col max-h-[85vh]">
                <button wire:click="closeDetailModal" class="absolute top-3 right-3 z-20 p-2 bg-black/40 hover:bg-black/60 text-white rounded-full transition-colors">
                    <x-lucide name="x" class="w-5 h-5" />
                </button>

                <div class="h-48 w-full bg-gray-100 relative shrink-0">
                    @if($selectedProduct->image)
                        <img src="{{ asset('storage/' . $selectedProduct->image) }}" class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full flex items-center justify-center bg-gray-100 text-gray-300">
                            <x-lucide name="soup" class="w-16 h-16" />
                        </div>
                    @endif
                    <div class="absolute bottom-3 left-3">
                        <span class="bg-primary-600 text-white text-xs font-bold px-3 py-1 rounded-full">
                            {{ $selectedProduct->category->name ?? 'Umum' }}
                        </span>
                    </div>
                </div>

                <div class="p-5 overflow-y-auto space-y-4 flex-1">
                    <div>
                        <div class="flex justify-between items-start">
                            <h2 class="text-xl font-bold text-gray-800">{{ $selectedProduct->name }}</h2>
                            <span class="text-xl font-extrabold text-primary-600">
                                Rp {{ number_format($selectedProduct->price, 0, ',', '.') }}
                            </span>
                        </div>
                        @if($selectedProduct->description)
                            <p class="text-xs text-gray-600 mt-2 bg-gray-50 p-3 rounded-xl border border-gray-100">
                                {{ $selectedProduct->description }}
                            </p>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                            <span class="text-gray-400 block">Kode SKU</span>
                            <span class="font-bold text-gray-800">{{ $selectedProduct->sku ?? '-' }}</span>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                            <span class="text-gray-400 block">Lacak Stok</span>
                            <span class="font-bold text-gray-800">
                                {{ $selectedProduct->track_stock ? 'Ya (' . $selectedProduct->stock . ' unit)' : 'Tidak' }}
                            </span>
                        </div>
                    </div>

                    @if($selectedProduct->modifierGroups && $selectedProduct->modifierGroups->count() > 0)
                        <div class="space-y-2">
                            <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider">Variasi / Modifier</h4>
                            @foreach($selectedProduct->modifierGroups as $group)
                                <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                                    <span class="font-semibold text-xs text-gray-800">{{ $group->name }}</span>
                                    <div class="flex flex-wrap gap-1.5 mt-1.5">
                                        @foreach($group->activeModifiers as $m)
                                            <span class="bg-white border border-gray-200 px-2 py-0.5 rounded text-[11px] text-gray-700">
                                                {{ $m->name }} (+Rp {{ number_format($m->price_adjustment, 0, ',', '.') }})
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="p-4 border-t bg-gray-50 flex justify-end gap-2 shrink-0">
                    @can('view_products')
                        <a 
                            href="{{ route('admin.products.show', $selectedProduct->slug) }}" 
                            class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-xs font-bold rounded-xl transition-colors"
                        >
                            Edit Detail Produk
                        </a>
                    @endcan
                    <button wire:click="closeDetailModal" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-bold rounded-xl transition-colors">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
