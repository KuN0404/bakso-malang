<div x-data="{ mobileMenuOpen: false }" class="min-h-screen bg-slate-50 text-slate-800 font-sans selection:bg-primary-500 selection:text-white">
    <!-- Navbar Sticky -->
    <nav class="sticky top-0 z-40 bg-white/90 backdrop-blur-md border-b border-slate-100 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <!-- Brand Logo & Name from Settings -->
            <a href="{{ route('home') }}" class="flex items-center gap-3.5 group">
                @if($logoWeb)
                    <img src="{{ asset('storage/' . $logoWeb) }}" alt="{{ $storeName }}" class="w-11 h-11 object-contain rounded-xl shadow-xs group-hover:scale-105 transition-transform" style="image-rendering:auto;">
                @elseif($siteLogo)
                    <img src="{{ asset('storage/' . $siteLogo) }}" alt="{{ $storeName }}" class="w-11 h-11 object-contain rounded-xl shadow-xs group-hover:scale-105 transition-transform" style="image-rendering:auto;">
                @else
                    <div class="w-11 h-11 bg-primary-600 rounded-xl flex items-center justify-center text-white shadow-xs group-hover:scale-105 transition-transform">
                        <x-lucide name="soup" class="w-6 h-6" />
                    </div>
                @endif
                <div>
                    <span class="text-xl font-extrabold text-slate-900 tracking-tight block leading-none">
                        {{ $storeName }}
                    </span>
                    <span class="text-xs text-primary-600 font-medium tracking-wide">Kuliner Otentik</span>
                </div>
            </a>

            <!-- Nav Links Desktop -->
            <div class="hidden md:flex items-center gap-8 text-sm font-semibold text-slate-600">
                <a href="#beranda" class="hover:text-primary-600 transition-colors">Beranda</a>
                <a href="#keunggulan" class="hover:text-primary-600 transition-colors">Keunggulan</a>
                <a href="#menu-preview" class="hover:text-primary-600 transition-colors">Menu Populer</a>
                <a href="#lokasi" class="hover:text-primary-600 transition-colors">Lokasi & Kontak</a>
            </div>

            <!-- Action Buttons Desktop & Mobile Hamburger Toggle -->
            <div class="flex items-center gap-3">
                <a 
                    href="{{ route('menu.public') }}" 
                    class="px-4 py-2 text-slate-600 hover:text-slate-900 hover:bg-slate-100 text-xs font-semibold rounded-xl transition-colors hidden sm:flex items-center gap-1.5 border border-slate-200"
                >
                    <x-lucide name="utensils" class="w-4 h-4" />
                    <span>Menu</span>
                </a>
                <a 
                    href="{{ route('self-order.index') }}" 
                    class="px-4 py-2 sm:px-5 sm:py-2.5 bg-primary-600 hover:bg-primary-700 text-white text-xs sm:text-sm font-bold rounded-xl transition-all shadow-sm hover:shadow-md flex items-center gap-2"
                >
                    <x-lucide name="shopping-bag" class="w-4 h-4" />
                    <span>Pesan Sekarang</span>
                </a>

                <!-- Mobile Menu Button -->
                <button 
                    @click="mobileMenuOpen = !mobileMenuOpen" 
                    type="button"
                    class="md:hidden p-2 rounded-xl text-slate-600 hover:bg-slate-100 border border-slate-200 transition-colors"
                    aria-label="Toggle navigation"
                >
                    <x-lucide name="menu" x-show="!mobileMenuOpen" class="w-5 h-5" />
                    <x-lucide name="x" x-show="mobileMenuOpen" class="w-5 h-5" x-cloak />
                </button>
            </div>
        </div>

        <!-- Mobile Nav Menu Dropdown -->
        <div 
            x-show="mobileMenuOpen" 
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2"
            @click.away="mobileMenuOpen = false"
            class="md:hidden bg-white border-b border-slate-100 px-4 pt-2 pb-6 space-y-3 shadow-lg"
            x-cloak
        >
            <a @click="mobileMenuOpen = false" href="#beranda" class="block px-3 py-2 rounded-lg text-sm font-semibold text-slate-700 hover:bg-slate-50 hover:text-primary-600 transition-colors">Beranda</a>
            <a @click="mobileMenuOpen = false" href="#keunggulan" class="block px-3 py-2 rounded-lg text-sm font-semibold text-slate-700 hover:bg-slate-50 hover:text-primary-600 transition-colors">Keunggulan</a>
            <a @click="mobileMenuOpen = false" href="#menu-preview" class="block px-3 py-2 rounded-lg text-sm font-semibold text-slate-700 hover:bg-slate-50 hover:text-primary-600 transition-colors">Menu Populer</a>
            <a @click="mobileMenuOpen = false" href="#lokasi" class="block px-3 py-2 rounded-lg text-sm font-semibold text-slate-700 hover:bg-slate-50 hover:text-primary-600 transition-colors">Lokasi & Kontak</a>
            <div class="pt-2 border-t border-slate-100 flex flex-col gap-2">
                <a href="{{ route('menu.public') }}" class="w-full text-center px-4 py-2.5 bg-slate-100 text-slate-700 font-semibold text-xs rounded-xl flex items-center justify-center gap-2">
                    <x-lucide name="utensils" class="w-4 h-4" />
                    <span>Katalog Menu Publik</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="beranda" class="relative bg-white pt-10 pb-16 md:pt-20 md:pb-28 border-b border-slate-100 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-8 items-center">
                <!-- Left Text Content -->
                <div class="lg:col-span-7 space-y-6 text-center lg:text-left">
                    <p class="text-sm font-semibold text-primary-600 tracking-wide">Cita Rasa Khas Malang</p>

                    <h1 class="text-3xl sm:text-5xl lg:text-6xl font-extrabold text-slate-900 tracking-tight leading-[1.15]">
                        Lezatnya <span class="text-primary-600">Bakso Malang</span> Spesial Daging Pilihan
                    </h1>

                    <p class="text-sm sm:text-lg text-slate-600 leading-relaxed max-w-2xl mx-auto lg:mx-0">
                        Disajikan hangat dengan kuah kaldu sapi asli yang gurih, gorengan renyah, tahu bakso lembut, dan siomay lezat. Resep warisan berkualitas tinggi siap memanjakan lidah Anda.
                    </p>

                    <!-- CTA Buttons -->
                    <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-3.5 pt-2">
                        <a 
                            href="{{ route('menu.public') }}" 
                            class="w-full sm:w-auto px-7 py-3.5 bg-primary-600 hover:bg-primary-700 text-white font-bold rounded-2xl shadow-md hover:shadow-lg transition-all text-center flex items-center justify-center gap-2 text-sm sm:text-base"
                        >
                            <x-lucide name="utensils" class="w-5 h-5" />
                            <span>Lihat Katalog Menu</span>
                        </a>

                        <a 
                            href="{{ route('self-order.index') }}" 
                            class="w-full sm:w-auto px-7 py-3.5 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold rounded-2xl transition-all text-center border border-slate-200 text-sm sm:text-base flex items-center justify-center gap-2"
                        >
                            <x-lucide name="qr-code" class="w-5 h-5 text-slate-600" />
                            <span>Pesan Mandiri (Self Order)</span>
                        </a>
                    </div>

                    <!-- Trust Highlights -->
                    <div class="pt-6 border-t border-slate-100 grid grid-cols-3 gap-4 max-w-lg mx-auto lg:mx-0 text-center lg:text-left">
                        <div>
                            <p class="text-xl sm:text-2xl font-extrabold text-slate-900">100%</p>
                            <p class="text-xs text-slate-500 font-medium">Daging Sapi Halal</p>
                        </div>
                        <div>
                            <p class="text-xl sm:text-2xl font-extrabold text-slate-900">Fresh</p>
                            <p class="text-xs text-slate-500 font-medium">Bahan Setiap Hari</p>
                        </div>
                        <div>
                            <p class="text-xl sm:text-2xl font-extrabold text-slate-900">Otentik</p>
                            <p class="text-xs text-slate-500 font-medium">Resep Khas Malang</p>
                        </div>
                    </div>
                </div>

                <!-- Right Visual Card Showcase -->
                <div class="lg:col-span-5 relative flex justify-center">
                    <div class="relative w-full max-w-md bg-white rounded-3xl p-4 shadow-xl border border-slate-100">
                        @if($featuredProducts->first() && $featuredProducts->first()->image)
                            <div class="h-64 sm:h-72 w-full rounded-2xl overflow-hidden bg-slate-100 relative">
                                <img src="{{ asset('storage/' . $featuredProducts->first()->image) }}" alt="{{ $featuredProducts->first()->name }}" class="w-full h-full object-cover">
                            </div>
                        @else
                            <div class="h-64 sm:h-72 w-full rounded-2xl bg-gradient-to-br from-slate-100 to-slate-200 flex flex-col items-center justify-center text-slate-400">
                                <x-lucide name="soup" class="w-20 h-20 stroke-[1.5]" />
                                <span class="text-sm font-semibold mt-2 text-slate-500">Bakso Malang Special</span>
                            </div>
                        @endif

                        <div class="p-4 space-y-2">
                            <div class="flex justify-between items-center">
                                <h3 class="font-bold text-slate-900 text-base sm:text-lg">
                                    {{ $featuredProducts->first()?->name ?? $storeName }}
                                </h3>
                                <span class="text-base sm:text-lg font-black text-primary-600">
                                    Rp {{ number_format($featuredProducts->first()?->price ?? 25000, 0, ',', '.') }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 leading-relaxed line-clamp-2">
                                {{ $featuredProducts->first()?->description ?? 'Sensasi bakso halus, bakso urat, pangsit goreng renyah, dan tahu bakso dengan kuah gurih hangat.' }}
                            </p>
                        </div>

                        <!-- Floating Status Badge -->
                        <div class="absolute -bottom-4 -left-4 sm:-bottom-5 sm:-left-5 bg-white p-3 sm:p-3.5 rounded-2xl shadow-lg border border-slate-100 flex items-center gap-3">
                            <div class="w-9 h-9 sm:w-10 sm:h-10 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center shrink-0">
                                <x-lucide name="check-circle" class="w-5 h-5 sm:w-6 sm:h-6" />
                            </div>
                            <div>
                                <p class="text-xs font-extrabold text-slate-800">Buka Hari Ini</p>
                                <p class="text-[10px] sm:text-[11px] text-slate-500 font-medium">{{ $storeHours }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Keunggulan Section -->
    <section id="keunggulan" class="py-16 md:py-24 bg-slate-50 border-b border-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-14 space-y-3">
                <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                    Kualitas & Kelezatan yang Selalu Terjaga
                </h2>
                <p class="text-slate-500 text-sm sm:text-base">
                    Kami berkomitmen memberikan hidangan bakso terbaik bagi setiap pelanggan dengan standar kebersihan dan rasa terjamin.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="bg-white p-8 rounded-3xl border border-slate-100 shadow-xs hover:shadow-md transition-all hover:-translate-y-1">
                    <div class="w-14 h-14 bg-primary-50 text-primary-600 rounded-2xl flex items-center justify-center mb-6">
                        <x-lucide name="soup" class="w-7 h-7" />
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-2">Resep Khas Otentik</h3>
                    <p class="text-slate-500 text-sm leading-relaxed">
                        Racikan bumbu rempah pilihan yang diwariskan turun-temurun, menghasilkan cita rasa khas Malang yang gurih dan nikmat.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="bg-white p-8 rounded-3xl border border-slate-100 shadow-xs hover:shadow-md transition-all hover:-translate-y-1">
                    <div class="w-14 h-14 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center mb-6">
                        <x-lucide name="leaf" class="w-7 h-7" />
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-2">100% Bahan Segar</h3>
                    <p class="text-slate-500 text-sm leading-relaxed">
                        Menggunakan olahan daging sapi segar setiap hari tanpa bahan pengawet sintesis untuk kesehatan Anda dan keluarga.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="bg-white p-8 rounded-3xl border border-slate-100 shadow-xs hover:shadow-md transition-all hover:-translate-y-1">
                    <div class="w-14 h-14 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center mb-6">
                        <x-lucide name="shield" class="w-7 h-7" />
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-2">Higienis & Nyaman</h3>
                    <p class="text-slate-500 text-sm leading-relaxed">
                        Proses penyajian bersih, cepat, dan tempat yang nyaman untuk bersantap bersama keluarga maupun rekan kerja.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Menu Preview Section -->
    <section id="menu-preview" class="py-16 md:py-24 bg-white border-b border-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row md:items-end justify-between mb-10 gap-4">
                <div>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                        Hidangan Pilihan Pelanggan
                    </h2>
                </div>

                <a 
                    href="{{ route('menu.public') }}" 
                    class="inline-flex items-center gap-2 text-primary-600 font-bold hover:text-primary-700 text-sm transition-colors"
                >
                    <span>Lihat Seluruh Menu Publik</span>
                    <x-lucide name="chevron-right" class="w-4 h-4" />
                </a>
            </div>

            <!-- Category Pills -->
            <div class="flex items-center gap-2 overflow-x-auto custom-scroll pb-2 mb-8">
                <button 
                    wire:click="selectCategory(null)"
                    class="px-5 py-2.5 rounded-xl font-bold text-xs whitespace-nowrap transition-all {{ is_null($selectedCategory) ? 'bg-primary-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}"
                >
                    Semua Menu
                </button>
                @foreach($categories as $cat)
                    <button 
                        wire:click="selectCategory({{ $cat->id }})"
                        class="px-5 py-2.5 rounded-xl font-bold text-xs whitespace-nowrap transition-all {{ $selectedCategory === $cat->id ? 'bg-primary-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}"
                    >
                        {{ $cat->name }} ({{ $cat->products_count }})
                    </button>
                @endforeach
            </div>

            <!-- Menu Grid -->
            @if(count($menuProducts) > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach($menuProducts as $product)
                        <div 
                            wire:click="openProductModal({{ $product->id }})"
                            class="bg-white rounded-2xl border border-slate-100 shadow-xs hover:shadow-lg transition-all duration-300 overflow-hidden cursor-pointer flex flex-col group"
                        >
                            <div class="relative h-48 w-full bg-slate-100 overflow-hidden">
                                @if($product->image)
                                    <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                @else
                                    <div class="w-full h-full flex flex-col items-center justify-center bg-slate-50 text-slate-300">
                                        <x-lucide name="soup" class="w-14 h-14 stroke-[1.5]" />
                                    </div>
                                @endif
                            </div>

                            <div class="p-5 flex-1 flex flex-col justify-between space-y-3">
                                <div>
                                    <h3 class="font-bold text-slate-900 text-base leading-snug line-clamp-1 group-hover:text-primary-600 transition-colors">
                                        {{ $product->name }}
                                    </h3>
                                    @if($product->description)
                                        <p class="text-xs text-slate-500 mt-1 line-clamp-2">{{ $product->description }}</p>
                                    @endif
                                </div>

                                <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                                    <span class="text-lg font-extrabold text-primary-600">
                                        Rp {{ number_format($product->price, 0, ',', '.') }}
                                    </span>
                                    <span class="p-2 bg-slate-100 text-slate-600 group-hover:bg-primary-600 group-hover:text-white rounded-xl transition-colors">
                                        <x-lucide name="eye" class="w-4 h-4" />
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-12 text-center bg-slate-50 rounded-2xl border border-slate-100">
                    <x-lucide name="soup" class="w-12 h-12 mx-auto text-slate-300 mb-3 stroke-[1.5]" />
                    <p class="text-slate-600 font-semibold text-sm">Belum ada menu di kategori ini.</p>
                    <button wire:click="selectCategory(null)" class="mt-3 text-xs font-bold text-primary-600 hover:underline">Lihat Semua Menu</button>
                </div>
            @endif

            <!-- Bottom Banner to Full Menu -->
            <div class="mt-12 text-center">
                <a 
                    href="{{ route('menu.public') }}" 
                    class="px-8 py-3.5 bg-slate-900 hover:bg-slate-800 text-white font-bold text-sm rounded-xl transition-all shadow-md inline-flex items-center gap-2"
                >
                    <span>Buka Katalog Lengkap Menu & Harga</span>
                    <x-lucide name="chevron-right" class="w-4 h-4" />
                </a>
            </div>
        </div>
    </section>

    <!-- Location & Contact Section -->
    <section id="lokasi" class="py-16 md:py-24 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-14 space-y-3">
                <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                    Lokasi & Kontak
                </h2>
                <p class="text-slate-500 text-sm sm:text-base">
                    Kami siap melayani Anda dengan hangat setiap hari.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-4xl mx-auto mb-12">
                <!-- Card Address -->
                <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-xs flex flex-col items-center text-center">
                    <div class="w-12 h-12 bg-primary-50 text-primary-600 rounded-xl flex items-center justify-center mb-4">
                        <x-lucide name="map-pin" class="w-6 h-6" />
                    </div>
                    <h4 class="font-bold text-slate-900 text-base mb-1">Alamat Outlets</h4>
                    <p class="text-xs text-slate-500 leading-relaxed">{{ $storeAddress }}</p>
                </div>

                <!-- Card Hours -->
                <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-xs flex flex-col items-center text-center">
                    <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center mb-4">
                        <x-lucide name="clock" class="w-6 h-6" />
                    </div>
                    <h4 class="font-bold text-slate-900 text-base mb-1">Jam Operasional</h4>
                    <p class="text-xs text-slate-500 leading-relaxed">{{ $storeHours }}</p>
                </div>

                <!-- Card Contact -->
                <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-xs flex flex-col items-center text-center">
                    <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center mb-4">
                        <x-lucide name="phone" class="w-6 h-6" />
                    </div>
                    <h4 class="font-bold text-slate-900 text-base mb-1">Hubungi Kami</h4>
                    <p class="text-xs text-slate-500 leading-relaxed">{{ $storePhone }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">{{ $storeEmail }}</p>
                </div>
            </div>

            {{-- Google Maps Embed --}}
            <div class="w-full rounded-2xl overflow-hidden border border-slate-200 shadow-sm" style="height:400px;">
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d126732.48796553!2d112.5678!3d-7.9839!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2dd627a8b5888893%3A0x3fa6b10a4e4f0a57!2sMalang%2C%20Jawa%20Timur!5e0!3m2!1sid!2sid!4v1690000000000"
                    width="100%"
                    height="100%"
                    style="border:0;"
                    allowfullscreen=""
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    title="Lokasi {{ $storeName }}"
                ></iframe>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-slate-900 text-slate-300 pt-16 pb-8 border-t border-slate-800 relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8 mb-12">
                <!-- Kolom 1: Identity & Address (Lebih lebar) -->
                <div class="lg:col-span-2 space-y-4">
                    <div class="flex items-center gap-3">
                        @if($logoWeb)
                            <img src="{{ asset('storage/' . $logoWeb) }}" class="w-10 h-10 object-contain rounded-xl" style="image-rendering:auto;" alt="{{ $storeName }}">
                        @elseif($siteLogo)
                            <img src="{{ asset('storage/' . $siteLogo) }}" class="w-10 h-10 object-contain rounded-xl" style="image-rendering:auto;" alt="{{ $storeName }}">
                        @else
                            <div class="w-10 h-10 bg-primary-600 text-white rounded-xl flex items-center justify-center">
                                <x-lucide name="soup" class="w-5 h-5" />
                            </div>
                        @endif
                        <div>
                            <span class="text-lg font-bold text-white block leading-none tracking-tight">{{ strtoupper($storeName) }}</span>
                            <span class="text-xs text-primary-400 font-medium">Cita Rasa Otentik Bakso Malang</span>
                        </div>
                    </div>

                    <p class="text-xs text-slate-400 leading-relaxed max-w-sm">
                        {{ $storeAddress }}
                    </p>

                    <div class="space-y-1.5 text-xs text-slate-300 pt-1">
                        <div class="flex items-center gap-2">
                            <x-lucide name="mail" class="w-4 h-4 text-primary-500 shrink-0" />
                            <span>{{ $storeEmail }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-lucide name="phone" class="w-4 h-4 text-primary-500 shrink-0" />
                            <span>{{ $storePhone }}</span>
                        </div>
                    </div>

                    <!-- Social Icons -->
                    <div class="flex items-center gap-2 pt-2">
                        <a href="#" class="w-8 h-8 rounded-full border border-slate-700 bg-slate-800/80 hover:bg-primary-600 hover:border-primary-600 text-slate-300 hover:text-white flex items-center justify-center transition-colors">
                            <x-lucide name="instagram" class="w-4 h-4" />
                        </a>
                        <a href="#" class="w-8 h-8 rounded-full border border-slate-700 bg-slate-800/80 hover:bg-primary-600 hover:border-primary-600 text-slate-300 hover:text-white flex items-center justify-center transition-colors">
                            <x-lucide name="facebook" class="w-4 h-4" />
                        </a>
                        <a href="#" class="w-8 h-8 rounded-full border border-slate-700 bg-slate-800/80 hover:bg-primary-600 hover:border-primary-600 text-slate-300 hover:text-white flex items-center justify-center transition-colors">
                            <x-lucide name="youtube" class="w-4 h-4" />
                        </a>
                    </div>
                </div>

                <!-- Kolom 2: Profil / Navigasi -->
                <div>
                    <h4 class="text-xs font-bold text-slate-100 uppercase tracking-widest mb-4">NAVIGASI</h4>
                    <ul class="space-y-2.5 text-xs text-slate-400">
                        <li><a href="#beranda" class="hover:text-primary-400 transition-colors">Beranda</a></li>
                        <li><a href="#keunggulan" class="hover:text-primary-400 transition-colors">Keunggulan Kami</a></li>
                        <li><a href="#menu-preview" class="hover:text-primary-400 transition-colors">Menu Populer</a></li>
                        <li><a href="#lokasi" class="hover:text-primary-400 transition-colors">Lokasi Outlets</a></li>
                    </ul>
                </div>

                <!-- Kolom 3: Layanan -->
                <div>
                    <h4 class="text-xs font-bold text-slate-100 uppercase tracking-widest mb-4">LAYANAN</h4>
                    <ul class="space-y-2.5 text-xs text-slate-400">
                        <li><a href="{{ route('self-order.index') }}" class="hover:text-primary-400 transition-colors">Self Order (Pesan Mandiri)</a></li>
                        <li><a href="{{ route('menu.public') }}" class="hover:text-primary-400 transition-colors">Katalog Menu Publik</a></li>
                        <li><a href="#lokasi" class="hover:text-primary-400 transition-colors">Jam Operasional</a></li>
                    </ul>
                </div>

                <!-- Kolom 4: Informasi Toko -->
                <div>
                    <h4 class="text-xs font-bold text-slate-100 uppercase tracking-widest mb-4">INFORMASI TOKO</h4>
                    <ul class="space-y-2.5 text-xs text-slate-400">
                        <li><span class="text-slate-300 font-semibold block">Jam Buka:</span> {{ $storeHours }}</li>
                        <li><span class="text-slate-300 font-semibold block">Status Daging:</span> 100% Sapi Halal</li>
                    </ul>
                </div>
            </div>

            <!-- Bottom Line: Copyright & Credits -->
            <div class="pt-8 border-t border-slate-800 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-4">
                <div>
                    &copy; {{ date('Y') }} <span class="text-slate-400 font-semibold">{{ $storeName }}</span>. All rights reserved.
                </div>
                <div>
                    Dikelola oleh <span class="text-slate-300 font-bold">{{ $storeName }} Management</span>
                </div>
            </div>
        </div>

        <!-- Floating Action Button (Back To Top) -->
        <div class="fixed bottom-6 right-6 z-50">
            <a href="#beranda" class="w-11 h-11 bg-primary-600 hover:bg-primary-700 text-white rounded-full flex items-center justify-center shadow-lg transition-transform hover:scale-105" title="Kembali ke atas">
                <x-lucide name="chevron-up" class="w-6 h-6" />
            </a>
        </div>
    </footer>

    <!-- Product Detail Modal -->
    @if($showProductModal && $selectedProduct)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="closeProductModal"></div>

            <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full z-10 overflow-hidden relative flex flex-col max-h-[90vh]">
                <button 
                    wire:click="closeProductModal" 
                    class="absolute top-4 right-4 z-20 w-10 h-10 bg-black/40 hover:bg-black/60 text-white rounded-full flex items-center justify-center transition-colors"
                >
                    <x-lucide name="x" class="w-5 h-5" />
                </button>

                <div class="relative h-64 w-full bg-slate-100 flex-shrink-0">
                    @if($selectedProduct->image)
                        <img src="{{ asset('storage/' . $selectedProduct->image) }}" alt="{{ $selectedProduct->name }}" class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full flex flex-col items-center justify-center bg-slate-100 text-slate-300">
                            <x-lucide name="soup" class="w-20 h-20 stroke-[1.5]" />
                        </div>
                    @endif
                    <div class="absolute bottom-4 left-4">
                        <span class="bg-primary-600 text-white text-xs font-bold px-3.5 py-1.5 rounded-full shadow-md">
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
                            <p class="text-sm text-slate-600 mt-2 bg-slate-50 p-4 rounded-2xl border border-slate-100 leading-relaxed">
                                {{ $selectedProduct->description }}
                            </p>
                        @endif
                    </div>

                    @if($selectedProduct->modifierGroups && $selectedProduct->modifierGroups->count() > 0)
                        <div class="space-y-3 pt-2">
                            <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Variasi / Toping</h4>
                            @foreach($selectedProduct->modifierGroups as $group)
                                <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                                    <span class="font-semibold text-xs text-slate-700 uppercase tracking-wider block mb-2">{{ $group->name }}</span>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($group->activeModifiers as $mod)
                                            <div class="bg-white border border-slate-200 px-3 py-1.5 rounded-xl text-xs flex items-center gap-1.5">
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

                <div class="p-4 border-t bg-slate-50 flex items-center justify-between gap-3">
                    <button 
                        wire:click="closeProductModal" 
                        class="px-5 py-2.5 bg-slate-200 hover:bg-slate-300 text-slate-800 text-sm font-bold rounded-xl transition-colors"
                    >
                        Tutup
                    </button>
                    <a 
                        href="{{ route('self-order.index') }}" 
                        class="px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white text-sm font-bold rounded-xl transition-colors flex items-center gap-2 shadow-sm"
                    >
                        <x-lucide name="shopping-bag" class="w-4 h-4" />
                        <span>Pesan via Self Order</span>
                    </a>
                </div>
            </div>
        </div>
    @endif
</div>
