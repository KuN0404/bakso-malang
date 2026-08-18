<div class="p-6 space-y-6">
    <!-- Header Page -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-extrabold text-gray-800 tracking-tight">Kalkulator HPP & Margin Profit</h1>
                <span class="bg-indigo-50 text-indigo-700 text-xs font-bold px-2.5 py-1 rounded-full border border-indigo-200">
                    Modul Analisis
                </span>
            </div>
            <p class="text-sm text-gray-500 mt-1">Hitung presisi Harga Pokok Penjualan (HPP) per porsi dan dapatkan rekomendasi harga jual terbaik</p>
        </div>

        <div class="flex items-center gap-3">
            <a 
                href="{{ route('admin.products.index') }}" 
                class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-bold rounded-xl transition-all flex items-center gap-2 border border-gray-200"
            >
                <x-lucide name="package" class="w-4 h-4" />
                <span>Master Produk</span>
            </a>
            <a 
                href="{{ route('admin.ingredients.index') }}" 
                class="px-4 py-2.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-sm font-bold rounded-xl transition-all flex items-center gap-2 border border-indigo-200"
            >
                <x-lucide name="boxes" class="w-4 h-4" />
                <span>Master Bahan Baku</span>
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- LEFT COLUMN: INPUT FORMS (7 COLS) -->
        <div class="lg:col-span-7 space-y-6">
            <!-- Card 1: Target Product & Recipe Setup -->
            <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm space-y-4">
                <h3 class="text-base font-bold text-gray-800 flex items-center gap-2 pb-3 border-b border-gray-100">
                    <x-lucide name="sliders" class="w-5 h-5 text-primary-600" />
                    <span>1. Pilih Produk & Target Hasil Batch</span>
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                            Pilih Produk (Opsional)
                        </label>
                        <select 
                            wire:model.live="selectedProductId" 
                            class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none bg-gray-50/50"
                        >
                            <option value="">-- Simulasi Resep Baru --</option>
                            @foreach($this->availableProducts as $prod)
                                <option value="{{ $prod->id }}">
                                    {{ $prod->name }} (Harga Jual: Rp {{ number_format($prod->price, 0, ',', '.') }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                            Nama Resep / Modul
                        </label>
                        <input 
                            type="text" 
                            wire:model="recipeName" 
                            placeholder="Contoh: Batch Bakso Halus 5kg" 
                            class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none"
                        >
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                        Hasil Produksi Per Batch (Jumlah Porsi / Pcs)
                    </label>
                    <div class="relative">
                        <input 
                            type="number" 
                            wire:model.live.debounce.300ms="portionsPerBatch" 
                            min="1"
                            class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none font-extrabold text-primary-700"
                        >
                        <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-bold text-gray-400">Porsi</span>
                    </div>
                    <p class="text-[11px] text-gray-400 mt-1">Berapa porsi yang dihasilkan dari seluruh racikan bahan baku dalam 1 kali masak/produksi.</p>
                </div>
            </div>

            <!-- Card 2: Ingredients Recipe BOM Table -->
            <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                    <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
                        <x-lucide name="boxes" class="w-5 h-5 text-indigo-600" />
                        <span>2. Komposisi Bahan Baku (Recipe BOM)</span>
                    </h3>

                    <button 
                        wire:click="addIngredientRow"
                        class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold rounded-lg transition-colors flex items-center gap-1 border border-indigo-200"
                    >
                        <x-lucide name="plus" class="w-4 h-4" />
                        <span>Tambah Bahan</span>
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-gray-50 text-gray-600 font-bold uppercase tracking-wider">
                                <th class="p-3 rounded-l-lg">Bahan Baku</th>
                                <th class="p-3">Satuan</th>
                                <th class="p-3 text-right">Harga Modal/Satuan</th>
                                <th class="p-3 text-center">Jumlah Pemakaian</th>
                                <th class="p-3 text-right">Subtotal Biaya</th>
                                <th class="p-3 text-center rounded-r-lg">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($ingredientsList as $index => $row)
                                <tr>
                                    <td class="p-2 min-w-[180px]">
                                        <select 
                                            wire:change="onIngredientSelected({{ $index }}, $event.target.value)"
                                            class="w-full px-2.5 py-1.5 border border-gray-200 rounded-lg text-xs focus:ring-1 focus:ring-indigo-500"
                                        >
                                            <option value="">-- Pilih Bahan Baku --</option>
                                            @foreach($this->availableIngredients as $ing)
                                                <option value="{{ $ing->id }}" {{ ($row['ingredient_id'] ?? null) == $ing->id ? 'selected' : '' }}>
                                                    {{ $ing->name }} (Rp {{ number_format($ing->cost_price, 0, ',', '.') }}/{{ $ing->unit?->symbol }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="p-2 font-semibold text-gray-600 min-w-[70px]">
                                        <input 
                                            type="text" 
                                            wire:model.live="ingredientsList.{{ $index }}.unit" 
                                            class="w-16 px-2 py-1 border border-gray-200 rounded text-center text-xs"
                                        >
                                    </td>
                                    <td class="p-2 text-right min-w-[110px]">
                                        <input 
                                            type="number" 
                                            wire:model.live.debounce.300ms="ingredientsList.{{ $index }}.cost_price" 
                                            class="w-24 px-2 py-1 border border-gray-200 rounded text-right text-xs font-mono"
                                        >
                                    </td>
                                    <td class="p-2 text-center min-w-[90px]">
                                        <input 
                                            type="number" 
                                            step="any"
                                            wire:model.live.debounce.300ms="ingredientsList.{{ $index }}.quantity" 
                                            class="w-20 px-2 py-1 border border-gray-200 rounded text-center text-xs font-bold text-indigo-700"
                                        >
                                    </td>
                                    <td class="p-2 text-right font-extrabold text-gray-800 min-w-[110px]">
                                        Rp {{ number_format(((float)($row['quantity'] ?? 0)) * ((float)($row['cost_price'] ?? 0)), 0, ',', '.') }}
                                    </td>
                                    <td class="p-2 text-center">
                                        <button 
                                            wire:click="removeIngredientRow({{ $index }})"
                                            class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Hapus Bahan"
                                        >
                                            <x-lucide name="trash-2" class="w-4 h-4" />
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="pt-3 border-t border-gray-100 flex justify-between items-center text-xs">
                    <span class="text-gray-500">Total Biaya Bahan Baku:</span>
                    <span class="font-extrabold text-indigo-600 text-sm">
                        Rp {{ number_format($this->totalIngredientsCost, 0, ',', '.') }}
                    </span>
                </div>
            </div>

            <!-- Card 3: Overhead & Packaging Costs -->
            <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm space-y-4">
                <h3 class="text-base font-bold text-gray-800 flex items-center gap-2 pb-3 border-b border-gray-100">
                    <x-lucide name="wallet" class="w-5 h-5 text-amber-600" />
                    <span>3. Biaya Kemasan & Operasional Overhead</span>
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                            Biaya Kemasan (Per Porsi)
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-gray-400">Rp</span>
                            <input 
                                type="number" 
                                wire:model.live.debounce.300ms="packagingCostPerPortion" 
                                class="w-full pl-9 pr-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 focus:outline-none font-bold"
                            >
                        </div>
                        <p class="text-[11px] text-gray-400 mt-1">Mangkok, plastik, sendok, kertas minyak per porsi.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                            Biaya Operasional (Per Batch)
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-gray-400">Rp</span>
                            <input 
                                type="number" 
                                wire:model.live.debounce.300ms="overheadCost" 
                                class="w-full pl-9 pr-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 focus:outline-none font-bold"
                            >
                        </div>
                        <p class="text-[11px] text-gray-400 mt-1">Gas LPG, listrik, bumbu dapur pendukung, & SDM per batch.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: CALCULATION & PROFIT ANALYSIS (5 COLS) -->
        <div class="lg:col-span-5 space-y-6">
            <!-- HPP Summary Card -->
            <div class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white rounded-3xl p-6 shadow-xl space-y-6 relative overflow-hidden">
                <div class="flex justify-between items-start">
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-widest text-indigo-400 bg-indigo-950/60 px-3 py-1 rounded-full border border-indigo-800/50">
                            Hasil Kalkulasi HPP
                        </span>
                        <h2 class="text-xl font-bold mt-2 text-white">{{ $recipeName ?: 'Simulasi Resep' }}</h2>
                    </div>
                    <div class="p-3 bg-white/10 rounded-2xl">
                        <x-lucide name="bar-chart-3" class="w-6 h-6 text-indigo-300" />
                    </div>
                </div>

                <!-- Big HPP Result -->
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-5 border border-white/10 text-center space-y-1">
                    <p class="text-xs text-indigo-200 font-medium uppercase tracking-wider">Harga Pokok Penjualan (HPP) / Porsi</p>
                    <p class="text-4xl font-black text-white tracking-tight">
                        Rp {{ number_format($this->hppPerPortion, 0, ',', '.') }}
                    </p>
                    <p class="text-[11px] text-slate-300 pt-1">
                        Total Biaya Batch: <span class="font-bold text-white">Rp {{ number_format($this->totalBatchCost, 0, ',', '.') }}</span> ({{ $portionsPerBatch }} porsi)
                    </p>
                </div>

                <!-- Cost Structure Breakdown -->
                <div class="space-y-2 text-xs border-t border-slate-700/60 pt-4">
                    <div class="flex justify-between text-slate-300">
                        <span>Bahan Baku Batch:</span>
                        <span class="font-bold text-white">Rp {{ number_format($this->totalIngredientsCost, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-slate-300">
                        <span>Biaya Operasional Batch:</span>
                        <span class="font-bold text-white">Rp {{ number_format($overheadCost, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-slate-300">
                        <span>Kemasan Batch ({{ $portionsPerBatch }}x):</span>
                        <span class="font-bold text-white">Rp {{ number_format($this->totalPackagingCost, 0, ',', '.') }}</span>
                    </div>
                </div>

                @if($this->currentProduct)
                    <div class="bg-indigo-950/70 rounded-xl p-4 border border-indigo-800/60 text-xs space-y-2">
                        <p class="text-indigo-200 font-bold flex items-center gap-1.5 border-b border-indigo-900/60 pb-1.5">
                            <x-lucide name="info" class="w-4 h-4 text-indigo-400" />
                            <span>Perbandingan Data Produk Saat Ini:</span>
                        </p>
                        <div class="flex justify-between items-center text-slate-200">
                            <span class="text-slate-300">Modal (HPP) Saat Ini:</span>
                            <span class="font-bold text-amber-300 bg-amber-950/60 border border-amber-800/60 px-2 py-0.5 rounded">
                                Rp {{ number_format($this->currentProduct->cost_price ?? 0, 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center text-slate-200">
                            <span class="text-slate-300">Harga Jual Saat Ini di Kasir:</span>
                            <span class="font-bold text-emerald-400 bg-emerald-950/60 border border-emerald-800/60 px-2 py-0.5 rounded">
                                Rp {{ number_format($this->currentProduct->price ?? 0, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Profit Simulator Card -->
            <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm space-y-5">
                <h3 class="text-base font-bold text-gray-800 flex items-center gap-2 pb-3 border-b border-gray-100">
                    <x-lucide name="sliders" class="w-5 h-5 text-emerald-600" />
                    <span>Target Margin & Simulasi Harga Jual</span>
                </h3>

                <!-- Target Margin Slider / Buttons -->
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <label class="text-xs font-bold text-gray-700 uppercase tracking-wider">Target Profit Margin</label>
                        <span class="text-lg font-black text-emerald-600 bg-emerald-50 px-3 py-0.5 rounded-full border border-emerald-200">
                            {{ $targetMarginPercent }}%
                        </span>
                    </div>

                    <div class="flex gap-2">
                        @foreach([30, 40, 50, 60, 70] as $m)
                            <button 
                                wire:click="$set('targetMarginPercent', {{ $m }})"
                                class="flex-1 py-1.5 rounded-lg text-xs font-bold transition-all {{ $targetMarginPercent == $m ? 'bg-emerald-600 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
                            >
                                {{ $m }}%
                            </button>
                        @endforeach
                    </div>

                    <input 
                        type="range" 
                        wire:model.live="targetMarginPercent" 
                        min="10" 
                        max="85" 
                        step="5"
                        class="w-full accent-emerald-600 cursor-pointer"
                    >
                </div>

                <!-- Pricing Results -->
                <div class="space-y-3 pt-2">
                    <div class="bg-emerald-50 rounded-2xl p-4 border border-emerald-100 space-y-1 text-center">
                        <span class="text-xs text-emerald-700 font-bold uppercase tracking-wider">Rekomendasi Harga Jual Ideal</span>
                        <p class="text-3xl font-black text-emerald-800">
                            Rp {{ number_format(round($this->recommendedSellingPrice, -2), 0, ',', '.') }}
                        </p>
                        <p class="text-[11px] text-emerald-600">Dibulatkan ke ratusan terdekat</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div class="bg-gray-50 p-3.5 rounded-xl border border-gray-100 text-center">
                            <span class="text-gray-500 block font-medium">Profit / Porsi</span>
                            <span class="text-base font-extrabold text-emerald-600">
                                Rp {{ number_format($this->profitPerPortion, 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="bg-gray-50 p-3.5 rounded-xl border border-gray-100 text-center">
                            <span class="text-gray-500 block font-medium">Total Profit / Batch</span>
                            <span class="text-base font-extrabold text-emerald-600">
                                Rp {{ number_format($this->totalBatchProfit, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Apply to Product Button -->
                <div class="pt-3 border-t border-gray-100 space-y-3">
                    <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-gray-700 select-none">
                        <input type="checkbox" wire:model="applyToSellingPrice" class="w-4 h-4 text-primary-600 rounded focus:ring-primary-500">
                        <span>Juga perbarui **Harga Jual Produk** ke rekomendasi di atas</span>
                    </label>

                    <button 
                        wire:click="confirmApplyHpp"
                        class="w-full py-3.5 bg-primary-600 hover:bg-primary-700 text-white font-extrabold rounded-xl transition-all shadow-md shadow-primary-500/25 flex items-center justify-center gap-2 cursor-pointer"
                    >
                        <x-lucide name="check" class="w-5 h-5" />
                        <span>Terapkan HPP ke Produk</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom Apply HPP Confirmation Modal (Teleported to body like Logout Modal) -->
    @teleport('body')
        <div 
            x-data="{ open: @entangle('showConfirmModal') }" 
            x-show="open" 
            x-cloak
            style="display: none;"
            class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-[9999] flex items-center justify-center p-4 overflow-y-auto"
        >
            <div 
                x-show="open"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-90"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-90"
                class="bg-white rounded-3xl max-w-md w-full p-6 text-center shadow-2xl space-y-5 relative border border-gray-100"
            >
                <!-- Close Button -->
                <button 
                    @click="$wire.cancelApplyHpp()"
                    class="absolute top-4 right-4 p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full transition-colors cursor-pointer"
                    title="Tutup Modal"
                >
                    <x-lucide name="x" class="w-5 h-5" />
                </button>

                <!-- Icon Badge -->
                <div class="w-16 h-16 bg-gradient-to-br from-indigo-50 to-indigo-100 text-indigo-600 rounded-2xl flex items-center justify-center mx-auto shadow-inner border border-indigo-200">
                    <x-lucide name="calculator" class="w-8 h-8" />
                </div>
                
                <div>
                    <h3 class="text-xl font-extrabold text-gray-800">Terapkan HPP ke Produk?</h3>
                    <p class="text-xs text-gray-500 mt-1.5 leading-relaxed">
                        Anda akan memperbarui nilai harga pokok modal (HPP) untuk produk <span class="font-bold text-gray-900 underline decoration-indigo-300 decoration-2">{{ $this->currentProduct?->name }}</span> di database.
                    </p>
                </div>

                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/80 text-left text-xs space-y-2.5">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 font-semibold">Modal HPP Baru (per Porsi):</span>
                        <span class="font-black text-indigo-700 text-sm bg-indigo-50 border border-indigo-200 px-2.5 py-0.5 rounded-lg">
                            Rp {{ number_format(round($this->hppPerPortion, 2), 0, ',', '.') }}
                        </span>
                    </div>
                    @if($applyToSellingPrice)
                        <div class="flex justify-between items-center border-t border-slate-200/80 pt-2">
                            <span class="text-gray-600 font-semibold">Harga Jual Baru di Kasir:</span>
                            <span class="font-black text-emerald-700 text-sm bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 rounded-lg">
                                Rp {{ number_format(round($this->recommendedSellingPrice, -2), 0, ',', '.') }}
                            </span>
                        </div>
                    @endif
                </div>

                <div class="flex gap-3 pt-2">
                    <button 
                        @click="$wire.cancelApplyHpp()"
                        class="flex-1 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition-colors text-xs cursor-pointer"
                    >
                        Batal
                    </button>
                    <button 
                        @click="$wire.applyHppToProduct()"
                        class="flex-1 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold rounded-xl shadow-lg shadow-indigo-600/30 transition-all text-xs flex items-center justify-center gap-1.5 cursor-pointer"
                    >
                        <x-lucide name="check" class="w-4 h-4" />
                        <span>Ya, Terapkan Sekarang</span>
                    </button>
                </div>
            </div>
        </div>
    @endteleport
</div>
