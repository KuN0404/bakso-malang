<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Pengaturan</h1>
        <p class="text-gray-500">Konfigurasi toko dan printer</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- General Settings -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="p-5 border-b flex items-center gap-3">
                <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center">
                    <i data-lucide="store" class="w-5 h-5 text-primary-600"></i>
                </div>
                <h3 class="font-semibold text-gray-800">Informasi Toko</h3>
            </div>
            <form wire:submit="saveGeneral" class="p-5 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Toko</label>
                    <input type="text" wire:model="store_name" class="w-full px-4 py-2 border border-gray-200 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Alamat</label>
                    <textarea wire:model="store_address" rows="2" class="w-full px-4 py-2 border border-gray-200 rounded-lg"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Telepon</label>
                    <input type="text" wire:model="store_phone" class="w-full px-4 py-2 border border-gray-200 rounded-lg">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Logo Web</label>
                        <div class="space-y-2">
                            @if ($logo_web)
                                <div class="relative w-24 h-24 border border-gray-200 rounded-lg overflow-hidden bg-gray-50 flex items-center justify-center p-1">
                                    <img src="{{ $logo_web->temporaryUrl() }}" class="max-w-full max-h-full object-contain">
                                </div>
                            @elseif ($existing_logo_web)
                                <div class="relative w-24 h-24 border border-gray-200 rounded-lg overflow-hidden group bg-gray-50 flex items-center justify-center p-1">
                                    <img src="{{ asset('storage/' . $existing_logo_web) }}" class="max-w-full max-h-full object-contain">
                                    <button type="button" wire:click="removeLogoWeb" class="absolute inset-0 bg-black/60 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                        <i data-lucide="trash-2" class="w-5 h-5"></i>
                                    </button>
                                </div>
                            @endif
                            <input type="file" wire:model="logo_web" class="block w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                            <span class="text-[10px] text-gray-400">Rekomendasi format WebP/PNG, max 2MB (Akan diconvert ke WebP)</span>
                            @error('logo_web') <span class="text-xs text-red-500 block">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Site Logo (Favicon)</label>
                        <div class="space-y-2">
                            @if ($site_logo)
                                <div class="relative w-24 h-24 border border-gray-200 rounded-lg overflow-hidden bg-gray-50 flex items-center justify-center p-1">
                                    <img src="{{ $site_logo->temporaryUrl() }}" class="max-w-full max-h-full object-contain">
                                </div>
                            @elseif ($existing_site_logo)
                                <div class="relative w-24 h-24 border border-gray-200 rounded-lg overflow-hidden group bg-gray-50 flex items-center justify-center p-1">
                                    <img src="{{ asset('storage/' . $existing_site_logo) }}" class="max-w-full max-h-full object-contain">
                                    <button type="button" wire:click="removeSiteLogo" class="absolute inset-0 bg-black/60 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                        <i data-lucide="trash-2" class="w-5 h-5"></i>
                                    </button>
                                </div>
                            @endif
                            <input type="file" wire:model="site_logo" class="block w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                            <span class="text-[10px] text-gray-400">Rekomendasi format WebP/PNG/ICO, max 1MB (Akan diconvert ke WebP)</span>
                            @error('site_logo') <span class="text-xs text-red-500 block">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pajak (PPN)</label>
                        <select wire:model="tax_percentage" class="w-full px-4 py-2 border border-gray-200 rounded-lg">
                            <option value="0">Tanpa Pajak (0%)</option>
                            <option value="11">PPN Indonesia (11%)</option>
                            <option value="12">PPN Indonesia (12%)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Simbol Mata Uang</label>
                        <select wire:model="currency_symbol" class="w-full px-4 py-2 border border-gray-200 rounded-lg">
                            <option value="Rp">Rp (Rupiah)</option>
                            <option value="$">$ (Dollar)</option>
                            <option value="RM">RM (Ringgit)</option>
                            <option value="S$">S$ (SGD)</option>
                            <option value="¥">¥ (Yen)</option>
                            <option value="€">€ (Euro)</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Font Family Web</label>
                    <select wire:model="font_family_web" class="w-full px-4 py-2 border border-gray-200 rounded-lg">
                        <option value="Poppins">Poppins (Modern, Sans-Serif)</option>
                        <option value="Inter">Inter (Sleek, Clean)</option>
                        <option value="Roboto">Roboto (Neo-Grotesque)</option>
                        <option value="Outfit">Outfit (Geometric, Elegant)</option>
                        <option value="Montserrat">Montserrat (Stylish, Bold)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Masa Berlaku Kode QRIS</label>
                    <select wire:model="qris_expiry_minutes" class="w-full px-4 py-2 border border-gray-200 rounded-lg">
                        <option value="3">3 Menit (Cepat / Antrean Padat)</option>
                        <option value="5">5 Menit (Rekomendasi Standar POS)</option>
                        <option value="10">10 Menit (Sedang)</option>
                        <option value="15">15 Menit (Maksimum Layanan)</option>
                    </select>
                    <span class="text-[10px] text-gray-400 block mt-1">Batas waktu aktif kode QRIS sebelum otomatis kadaluarsa jika pelanggan belum menyelesaikan pembayaran.</span>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Teks Header Struk</label>
                    <input type="text" wire:model="header_text" class="w-full px-4 py-2 border border-gray-200 rounded-lg" placeholder="Terima Kasih!">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Teks Footer Struk</label>
                    <input type="text" wire:model="footer_text" class="w-full px-4 py-2 border border-gray-200 rounded-lg" placeholder="Selamat Menikmati">
                </div>
                <button type="submit" wire:loading.attr="disabled" wire:target="logo_web, site_logo" class="w-full py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg disabled:opacity-50">
                    <span wire:loading.remove wire:target="logo_web, site_logo">Simpan Pengaturan Toko</span>
                    <span wire:loading wire:target="logo_web, site_logo" class="flex items-center justify-center gap-2">
                        <svg class="animate-spin h-5 w-5 text-white inline" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Mengunggah berkas...
                    </span>
                </button>
            </form>
        </div>

        <!-- Printer Settings -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="p-5 border-b flex items-center gap-3">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                    <i data-lucide="printer" class="w-5 h-5 text-green-600"></i>
                </div>
                <h3 class="font-semibold text-gray-800">Konfigurasi Printer</h3>
            </div>
            <form wire:submit="savePrinter" class="p-5 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ukuran Kertas</label>
                    <select wire:model.live="paper_size" class="w-full px-4 py-2 border border-gray-200 rounded-lg">
                        <option value="58mm">58mm (Thermal Kecil)</option>
                        <option value="80mm">80mm (Thermal Standar)</option>
                        <option value="custom">Custom</option>
                    </select>
                </div>
                @if($paper_size === 'custom')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Lebar Kertas (px)</label>
                        <input type="number" wire:model="paper_width_px" class="w-full px-4 py-2 border border-gray-200 rounded-lg">
                    </div>
                @endif
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Font Family</label>
                        <select wire:model="font_family" class="w-full px-4 py-2 border border-gray-200 rounded-lg">
                            <option value="monospace">Monospace</option>
                            <option value="Arial">Arial</option>
                            <option value="Courier New">Courier New</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Font Size (px)</label>
                        <input type="number" wire:model="font_size_px" class="w-full px-4 py-2 border border-gray-200 rounded-lg">
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" wire:model="auto_print" id="auto_print" class="w-4 h-4 text-primary-600 border-gray-300 rounded">
                    <label for="auto_print" class="text-sm text-gray-700">Auto Print Struk Setelah Transaksi</label>
                </div>
                <button type="submit" class="w-full py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg">Simpan Pengaturan Printer</button>
            </form>
        </div>
    </div>
</div>
@script
<script>
    lucide.createIcons();
    
    // Re-init icons when Livewire updates the DOM
    Livewire.hook('morph.updated', () => {
        lucide.createIcons();
    });
</script>
@endscript
