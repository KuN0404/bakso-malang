<div>
    {{-- Page Header --}}
    <div class="mb-8">
        <div class="flex items-center gap-3 mb-1">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center shadow-sm">
                <i data-lucide="settings-2" class="w-5 h-5 text-white"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Pengaturan</h1>
        </div>
        <p class="text-sm text-gray-500 ml-12">Kelola konfigurasi toko, printer, dan notifikasi email</p>
    </div>

    <div class="space-y-6">

        {{-- ── Row 1: Informasi Toko + Printer ─────────────────────────────── --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- General Settings --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-50 flex items-center gap-3">
                    <div class="w-8 h-8 bg-primary-50 rounded-lg flex items-center justify-center">
                        <i data-lucide="store" class="w-4 h-4 text-primary-600"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800 text-sm">Informasi Toko</h3>
                        <p class="text-xs text-gray-400">Nama, alamat, dan identitas toko</p>
                    </div>
                </div>
                <form wire:submit="saveGeneral" class="p-6 space-y-5">

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Nama Toko</label>
                        <input type="text" wire:model="store_name"
                            class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:border-primary-400 focus:ring-2 focus:ring-primary-100 outline-none transition"
                            placeholder="Bakso Malang...">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Alamat</label>
                        <textarea wire:model="store_address" rows="2"
                            class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:border-primary-400 focus:ring-2 focus:ring-primary-100 outline-none transition resize-none"
                            placeholder="Jl. Contoh No. 1..."></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Telepon</label>
                        <div class="relative">
                            <i data-lucide="phone" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                            <input type="text" wire:model="store_phone"
                                class="w-full pl-9 pr-3.5 py-2.5 text-sm border border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:border-primary-400 focus:ring-2 focus:ring-primary-100 outline-none transition"
                                placeholder="0812-xxxx-xxxx">
                        </div>
                    </div>

                    {{-- Logo Aktif --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-2">Logo Aktif</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="cursor-pointer">
                                <input type="radio" wire:model="logo_type" value="single" class="sr-only peer">
                                <div class="text-center py-2.5 rounded-xl border-2 text-sm font-semibold transition
                                    peer-checked:border-primary-500 peer-checked:bg-primary-50 peer-checked:text-primary-700
                                    border-gray-200 text-gray-500 hover:border-gray-300 bg-gray-50">
                                    Single
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" wire:model="logo_type" value="full" class="sr-only peer">
                                <div class="text-center py-2.5 rounded-xl border-2 text-sm font-semibold transition
                                    peer-checked:border-primary-500 peer-checked:bg-primary-50 peer-checked:text-primary-700
                                    border-gray-200 text-gray-500 hover:border-gray-300 bg-gray-50">
                                    Panjang
                                </div>
                            </label>
                        </div>
                        <p class="text-xs text-gray-400 mt-1.5">Single = ikon + nama toko dari sistem. Panjang = banner logo saja (nama toko sudah ada di gambarnya).</p>
                    </div>

                    {{-- Logo Upload --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Logo Single (Ikon + Nama Toko)</label>
                            <div class="space-y-2">
                                @if ($logo_web)
                                    <div class="w-20 h-20 border-2 border-primary-200 border-dashed rounded-xl bg-primary-50 flex items-center justify-center p-1">
                                        <img src="{{ $logo_web->temporaryUrl() }}" class="max-w-full max-h-full object-contain">
                                    </div>
                                @elseif ($existing_logo_web)
                                    <div class="relative w-20 h-20 border border-gray-200 rounded-xl overflow-hidden group bg-gray-50 flex items-center justify-center p-1">
                                        <img src="{{ asset('storage/' . $existing_logo_web) }}" class="max-w-full max-h-full object-contain">
                                        <button type="button" wire:click="removeLogoWeb"
                                            class="absolute inset-0 bg-red-500/80 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity rounded-xl">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                @else
                                    <div class="w-20 h-20 border-2 border-dashed border-gray-200 rounded-xl bg-gray-50 flex items-center justify-center">
                                        <i data-lucide="image" class="w-6 h-6 text-gray-300"></i>
                                    </div>
                                @endif
                                <input type="file" wire:model="logo_web"
                                    class="block w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                                <p class="text-[10px] text-gray-400">Kotak/persegi, WebP/PNG, maks 2MB</p>
                                @error('logo_web') <span class="text-xs text-red-500 block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Logo Panjang (Logo Saja)</label>
                            <div class="space-y-2">
                                @if ($logo_full)
                                    <div class="w-full h-20 border-2 border-primary-200 border-dashed rounded-xl bg-primary-50 flex items-center justify-center p-1">
                                        <img src="{{ $logo_full->temporaryUrl() }}" class="max-w-full max-h-full object-contain">
                                    </div>
                                @elseif ($existing_logo_full)
                                    <div class="relative w-full h-20 border border-gray-200 rounded-xl overflow-hidden group bg-gray-50 flex items-center justify-center p-1">
                                        <img src="{{ asset('storage/' . $existing_logo_full) }}" class="max-w-full max-h-full object-contain">
                                        <button type="button" wire:click="removeLogoFull"
                                            class="absolute inset-0 bg-red-500/80 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity rounded-xl">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                @else
                                    <div class="w-full h-20 border-2 border-dashed border-gray-200 rounded-xl bg-gray-50 flex items-center justify-center">
                                        <i data-lucide="image" class="w-6 h-6 text-gray-300"></i>
                                    </div>
                                @endif
                                <input type="file" wire:model="logo_full"
                                    class="block w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                                <p class="text-[10px] text-gray-400">Banner lebar, WebP/PNG, maks 2MB</p>
                                @error('logo_full') <span class="text-xs text-red-500 block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Favicon</label>
                        <div class="space-y-2">
                            @if ($site_logo)
                                <div class="w-20 h-20 border-2 border-primary-200 border-dashed rounded-xl bg-primary-50 flex items-center justify-center p-1">
                                    <img src="{{ $site_logo->temporaryUrl() }}" class="max-w-full max-h-full object-contain">
                                </div>
                            @elseif ($existing_site_logo)
                                <div class="relative w-20 h-20 border border-gray-200 rounded-xl overflow-hidden group bg-gray-50 flex items-center justify-center p-1">
                                    <img src="{{ asset('storage/' . $existing_site_logo) }}" class="max-w-full max-h-full object-contain">
                                    <button type="button" wire:click="removeSiteLogo"
                                        class="absolute inset-0 bg-red-500/80 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity rounded-xl">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            @else
                                <div class="w-20 h-20 border-2 border-dashed border-gray-200 rounded-xl bg-gray-50 flex items-center justify-center">
                                    <i data-lucide="image" class="w-6 h-6 text-gray-300"></i>
                                </div>
                            @endif
                            <input type="file" wire:model="site_logo"
                                class="block w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                            <p class="text-[10px] text-gray-400">PNG/ICO, maks 1MB — ikon tab browser</p>
                            @error('site_logo') <span class="text-xs text-red-500 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Pajak (PPN)</label>
                            <select wire:model="tax_percentage"
                                class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:border-primary-400 focus:ring-2 focus:ring-primary-100 outline-none transition">
                                <option value="0">Tanpa Pajak (0%)</option>
                                <option value="11">PPN 11%</option>
                                <option value="12">PPN 12%</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Mata Uang</label>
                            <select wire:model="currency_symbol"
                                class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:border-primary-400 focus:ring-2 focus:ring-primary-100 outline-none transition">
                                <option value="Rp">Rp — Rupiah</option>
                                <option value="$">$ — Dollar</option>
                                <option value="RM">RM — Ringgit</option>
                                <option value="S$">S$ — SGD</option>
                                <option value="¥">¥ — Yen</option>
                                <option value="€">€ — Euro</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Font Web</label>
                            <select wire:model="font_family_web"
                                class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:border-primary-400 focus:ring-2 focus:ring-primary-100 outline-none transition">
                                <option value="Poppins">Poppins</option>
                                <option value="Inter">Inter</option>
                                <option value="Roboto">Roboto</option>
                                <option value="Outfit">Outfit</option>
                                <option value="Montserrat">Montserrat</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Masa QRIS</label>
                            <select wire:model="qris_expiry_minutes"
                                class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:border-primary-400 focus:ring-2 focus:ring-primary-100 outline-none transition">
                                <option value="3">3 Menit (Padat)</option>
                                <option value="5">5 Menit (Standar)</option>
                                <option value="10">10 Menit (Sedang)</option>
                                <option value="15">15 Menit (Maks)</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Header Struk</label>
                            <input type="text" wire:model="header_text"
                                class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:border-primary-400 focus:ring-2 focus:ring-primary-100 outline-none transition"
                                placeholder="Terima Kasih!">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Footer Struk</label>
                            <input type="text" wire:model="footer_text"
                                class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:border-primary-400 focus:ring-2 focus:ring-primary-100 outline-none transition"
                                placeholder="Selamat Menikmati">
                        </div>
                    </div>

                    <button type="submit" wire:loading.attr="disabled" wire:target="logo_web, site_logo, saveGeneral"
                        class="w-full py-2.5 bg-primary-600 hover:bg-primary-700 active:scale-[0.99] text-white text-sm font-semibold rounded-xl disabled:opacity-60 transition flex items-center justify-center gap-2">
                        <span wire:loading.remove wire:target="saveGeneral">
                            <i data-lucide="save" class="w-4 h-4 inline -mt-0.5 mr-1"></i>Simpan Informasi Toko
                        </span>
                        <span wire:loading wire:target="saveGeneral" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Menyimpan...
                        </span>
                    </button>
                </form>
            </div>

            {{-- Printer Settings --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-50 flex items-center gap-3">
                    <div class="w-8 h-8 bg-emerald-50 rounded-lg flex items-center justify-center">
                        <i data-lucide="printer" class="w-4 h-4 text-emerald-600"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800 text-sm">Konfigurasi Printer</h3>
                        <p class="text-xs text-gray-400">Ukuran kertas dan tampilan struk</p>
                    </div>
                </div>
                <form wire:submit="savePrinter" class="p-6 space-y-5">

                    @php
                        // Status tombol dihitung dari nilai lebar+satuan YANG SEKARANG
                        // terisi (bukan dari tombol terakhir diklik) — supaya kalau
                        // satuan/lebar diubah manual sesudah klik preset, tombol tidak
                        // "nyangkut" terlihat aktif padahal nilainya sudah beda.
                        $isPreset58 = (float) $paper_width === 58.0 && $paper_unit === 'mm';
                        $isPreset80 = (float) $paper_width === 80.0 && $paper_unit === 'mm';
                    @endphp
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-2">Ukuran Kertas (isi cepat)</label>
                        <div class="grid grid-cols-3 gap-2">
                            @foreach(['58mm' => '58 mm', '80mm' => '80 mm', 'custom' => 'Custom'] as $val => $lbl)
                                @php
                                    $active = match($val) {
                                        '58mm' => $isPreset58,
                                        '80mm' => $isPreset80,
                                        'custom' => !$isPreset58 && !$isPreset80,
                                    };
                                @endphp
                                <button type="button" wire:click="applyPaperPreset('{{ $val }}')"
                                    class="text-center py-2.5 rounded-xl border-2 text-sm font-semibold transition
                                        {{ $active ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-gray-200 text-gray-500 hover:border-gray-300 bg-gray-50' }}">
                                    {{ $lbl }}
                                </button>
                            @endforeach
                        </div>
                        <p class="text-xs text-gray-400 mt-1.5">Tombol ini cuma isi cepat — lebar, satuan, dan margin di bawah tetap bisa diubah bebas setelahnya. Status tombol di atas mengikuti nilai Lebar Kertas & Satuan yang sedang terisi.</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Lebar Kertas</label>
                            <input type="number" step="0.01" wire:model.live.debounce.500ms="paper_width"
                                class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 outline-none transition">
                            @error('paper_width') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Satuan</label>
                            <select wire:model.live="paper_unit"
                                class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 outline-none transition">
                                <option value="mm">Milimeter (mm)</option>
                                <option value="cm">Sentimeter (cm)</option>
                                <option value="px">Piksel (px)</option>
                            </select>
                            @error('paper_unit') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-2">Margin Halaman ({{ $paper_unit }})</label>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[11px] text-gray-500 mb-1">Atas</label>
                                <input type="number" step="0.01" wire:model="margin_top"
                                    class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 outline-none transition">
                                @error('margin_top') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-[11px] text-gray-500 mb-1">Kanan</label>
                                <input type="number" step="0.01" wire:model="margin_right"
                                    class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 outline-none transition">
                                @error('margin_right') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-[11px] text-gray-500 mb-1">Bawah</label>
                                <input type="number" step="0.01" wire:model="margin_bottom"
                                    class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 outline-none transition">
                                @error('margin_bottom') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-[11px] text-gray-500 mb-1">Kiri</label>
                                <input type="number" step="0.01" wire:model="margin_left"
                                    class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 outline-none transition">
                                @error('margin_left') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Font Family</label>
                            <select wire:model="font_family"
                                class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 outline-none transition">
                                <option value="monospace">Monospace</option>
                                <option value="Arial">Arial</option>
                                <option value="Courier New">Courier New</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Font Size (px)</label>
                            <input type="number" wire:model="font_size_px"
                                class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 outline-none transition">
                        </div>
                    </div>

                    {{-- Auto Print Toggle --}}
                    <label for="auto_print_toggle"
                        class="flex items-center gap-3 p-3.5 rounded-xl border border-gray-200 bg-gray-50 cursor-pointer hover:border-emerald-300 hover:bg-emerald-50/50 transition group">
                        <div class="relative shrink-0">
                            <input type="checkbox" wire:model="auto_print" id="auto_print_toggle" class="sr-only peer">
                            <div class="w-10 h-6 bg-gray-300 rounded-full peer-checked:bg-emerald-500 transition-colors"></div>
                            <div class="absolute top-1 left-1 w-4 h-4 bg-white rounded-full shadow transition-transform peer-checked:translate-x-4 pointer-events-none"></div>
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-gray-700">Auto Print Struk</div>
                            <div class="text-xs text-gray-400">Cetak otomatis setelah transaksi selesai</div>
                        </div>
                    </label>

                    <button type="submit"
                        class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 active:scale-[0.99] text-white text-sm font-semibold rounded-xl transition flex items-center justify-center gap-2">
                        <span wire:loading.remove wire:target="savePrinter">
                            <i data-lucide="save" class="w-4 h-4 inline -mt-0.5 mr-1"></i>Simpan Pengaturan Printer
                        </span>
                        <span wire:loading wire:target="savePrinter" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Menyimpan...
                        </span>
                    </button>
                </form>
            </div>
        </div>

        {{-- ── Row 2: Email ───────────────────────────────── --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-50 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center">
                        <i data-lucide="mail" class="w-4 h-4 text-blue-600"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800 text-sm">Email</h3>
                        <p class="text-xs text-gray-400">Uji coba pengiriman email struk</p>
                    </div>
                </div>
                {{-- Badge status, dibuat tanpa istilah teknis --}}
                @if(config('mail.default') === 'smtp')
                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-full">
                        <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span>Siap Mengirim Email
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-amber-700 bg-amber-50 border border-amber-200 px-2.5 py-1 rounded-full">
                        <span class="w-1.5 h-1.5 bg-amber-400 rounded-full"></span>Belum Aktif
                    </span>
                @endif
            </div>

            <div class="p-6 space-y-4">
                {{-- Test Email --}}
                <div class="border border-gray-100 rounded-xl p-4 bg-gray-50">
                    <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-3">Kirim Email Uji Coba</p>
                    <form wire:submit="sendTestEmail" class="flex gap-2">
                        <div class="relative flex-1">
                            <i data-lucide="at-sign" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                            <input type="email" wire:model="test_email_address"
                                class="w-full pl-9 pr-3.5 py-2.5 text-sm border border-gray-200 rounded-xl bg-white focus:bg-white focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none transition"
                                placeholder="email@gmail.com">
                        </div>
                        <button type="submit"
                            class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 active:scale-[0.99] text-white text-sm font-semibold rounded-xl transition flex items-center gap-1.5 whitespace-nowrap">
                            <span wire:loading.remove wire:target="sendTestEmail">
                                <i data-lucide="send" class="w-4 h-4 inline"></i>
                            </span>
                            <span wire:loading wire:target="sendTestEmail">
                                <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </span>
                            Kirim Test
                        </button>
                    </form>
                    @error('test_email_address') <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p> @enderror
                    <p class="mt-2 text-xs text-gray-400">Masukkan email Anda sendiri untuk memastikan email dari sistem sampai dengan baik.</p>
                </div>

                {{-- Info auto-send --}}
                <div class="flex gap-2.5 p-3.5 bg-amber-50 border border-amber-100 rounded-xl">
                    <i data-lucide="zap" class="w-4 h-4 text-amber-500 shrink-0 mt-0.5"></i>
                    <p class="text-xs text-amber-700">
                        Struk email dikirim otomatis ke pelanggan setelah pembayaran QRIS Self Order berhasil,
                        jika pelanggan mengisi alamat email saat checkout.
                    </p>
                </div>

            </div>
        </div>

        {{-- ── Row 3: WhatsApp (Fonnte) ───────────────────────────────── --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-50 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-emerald-50 rounded-lg flex items-center justify-center">
                        <i data-lucide="message-circle" class="w-4 h-4 text-emerald-600"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800 text-sm">WhatsApp (Fonnte)</h3>
                        <p class="text-xs text-gray-400">Sambungkan nomor WhatsApp toko untuk kirim struk</p>
                    </div>
                </div>
                @if(!$fonnteConfigured)
                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-600 bg-gray-100 border border-gray-200 px-2.5 py-1 rounded-full">
                        <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span>Token Belum Diset
                    </span>
                @elseif($waDeviceStatus === 'connect')
                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-full">
                        <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span>Terhubung
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-amber-700 bg-amber-50 border border-amber-200 px-2.5 py-1 rounded-full">
                        <span class="w-1.5 h-1.5 bg-amber-400 rounded-full"></span>Tidak Terhubung
                    </span>
                @endif
            </div>

            <div class="p-6 space-y-4">
                @if(!$fonnteConfigured)
                    <div class="flex gap-2.5 p-3.5 bg-gray-50 border border-gray-200 rounded-xl">
                        <i data-lucide="info" class="w-4 h-4 text-gray-400 shrink-0 mt-0.5"></i>
                        <p class="text-xs text-gray-600">
                            Token Fonnte belum diset di server (<code class="bg-gray-200 px-1 rounded">FONNTE_TOKEN</code> di <code class="bg-gray-200 px-1 rounded">.env</code>).
                            Hubungi yang mengelola server untuk mengisinya, baru fitur WhatsApp bisa dipakai.
                        </p>
                    </div>
                @else
                    <div class="border border-gray-100 rounded-xl p-4 bg-gray-50 text-center">
                        @if($waDeviceStatus === 'connect')
                            <div class="w-14 h-14 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                <i data-lucide="check-circle" class="w-7 h-7 text-emerald-600"></i>
                            </div>
                            <p class="text-sm font-semibold text-gray-700">WhatsApp sudah tersambung</p>
                            <p class="text-xs text-gray-400 mt-1">Struk WhatsApp siap dikirim ke pelanggan.</p>
                        @else
                            @if($waQrImage)
                                <img src="data:image/png;base64,{{ $waQrImage }}" class="w-48 h-48 mx-auto rounded-xl border border-gray-200 bg-white p-2" alt="QR WhatsApp">
                                <p class="text-xs text-gray-500 mt-3">Buka WhatsApp di HP → Perangkat Tertaut → Tautkan Perangkat, lalu scan QR di atas.</p>
                            @else
                                <p class="text-sm text-gray-500 mb-3">Nomor WhatsApp toko belum tersambung ke Fonnte.</p>
                            @endif
                            <button type="button" wire:click="connectWhatsapp"
                                class="mt-3 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 active:scale-[0.99] text-white text-sm font-semibold rounded-xl transition inline-flex items-center gap-1.5">
                                <span wire:loading.remove wire:target="connectWhatsapp">
                                    <i data-lucide="qr-code" class="w-4 h-4 inline -mt-0.5 mr-1"></i>{{ $waQrImage ? 'Muat Ulang QR' : 'Tampilkan QR untuk Hubungkan' }}
                                </span>
                                <span wire:loading wire:target="connectWhatsapp">Memuat...</span>
                            </button>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- ── Row 4: Notifikasi Struk ───────────────────────────────── --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-50 flex items-center gap-3">
                <div class="w-8 h-8 bg-purple-50 rounded-lg flex items-center justify-center">
                    <i data-lucide="send" class="w-4 h-4 text-purple-600"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-800 text-sm">Notifikasi Struk</h3>
                    <p class="text-xs text-gray-400">Kanal pengiriman struk digital ke pelanggan</p>
                </div>
            </div>
            <form wire:submit="saveNotification" class="p-6 space-y-5">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-2">Kirim Struk Lewat</label>
                    <select wire:model="receipt_channel"
                        class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:border-purple-400 focus:ring-2 focus:ring-purple-100 outline-none transition">
                        <option value="email_only">Email saja</option>
                        <option value="whatsapp_only">WhatsApp saja</option>
                        <option value="both">Email dan WhatsApp (keduanya)</option>
                        <option value="prefer_email">Utamakan Email (WA kalau email kosong)</option>
                        <option value="prefer_whatsapp">Utamakan WhatsApp (email kalau WA kosong)</option>
                        <option value="none">Tidak mengirim struk online</option>
                    </select>
                    @error('receipt_channel') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Template Pesan WhatsApp</label>
                    <textarea wire:model="whatsapp_template" rows="4"
                        class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:border-purple-400 focus:ring-2 focus:ring-purple-100 outline-none transition resize-none font-mono"></textarea>
                    <p class="text-[10px] text-gray-400 mt-1.5">Placeholder yang bisa dipakai: <code class="bg-gray-100 px-1 rounded">{nama}</code> <code class="bg-gray-100 px-1 rounded">{invoice}</code> <code class="bg-gray-100 px-1 rounded">{toko}</code> <code class="bg-gray-100 px-1 rounded">{link}</code></p>
                    @error('whatsapp_template') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <button type="submit"
                    class="w-full py-2.5 bg-purple-600 hover:bg-purple-700 active:scale-[0.99] text-white text-sm font-semibold rounded-xl transition flex items-center justify-center gap-2">
                    <span wire:loading.remove wire:target="saveNotification">
                        <i data-lucide="save" class="w-4 h-4 inline -mt-0.5 mr-1"></i>Simpan Pengaturan Notifikasi
                    </span>
                    <span wire:loading wire:target="saveNotification">Menyimpan...</span>
                </button>
            </form>
        </div>

    </div>
</div>

@script
<script>
    lucide.createIcons();
    Livewire.hook('morph.updated', () => {
        lucide.createIcons();
    });
</script>
@endscript


