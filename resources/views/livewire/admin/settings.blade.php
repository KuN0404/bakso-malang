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
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pajak (%)</label>
                        <input type="number" step="0.01" wire:model="tax_percentage" class="w-full px-4 py-2 border border-gray-200 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Simbol Mata Uang</label>
                        <input type="text" wire:model="currency_symbol" class="w-full px-4 py-2 border border-gray-200 rounded-lg">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Teks Header Struk</label>
                    <input type="text" wire:model="header_text" class="w-full px-4 py-2 border border-gray-200 rounded-lg" placeholder="Terima Kasih!">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Teks Footer Struk</label>
                    <input type="text" wire:model="footer_text" class="w-full px-4 py-2 border border-gray-200 rounded-lg" placeholder="Selamat Menikmati">
                </div>
                <button type="submit" class="w-full py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg">Simpan Pengaturan Toko</button>
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
                    <select wire:model="paper_size" class="w-full px-4 py-2 border border-gray-200 rounded-lg">
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
<script>lucide.createIcons();</script>
@endscript
