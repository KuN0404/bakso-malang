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

                    {{-- Logo Upload --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Logo Web</label>
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
                                <p class="text-[10px] text-gray-400">WebP/PNG, maks 2MB</p>
                                @error('logo_web') <span class="text-xs text-red-500 block">{{ $message }}</span> @enderror
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
                                <p class="text-[10px] text-gray-400">PNG/ICO, maks 1MB</p>
                                @error('site_logo') <span class="text-xs text-red-500 block">{{ $message }}</span> @enderror
                            </div>
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

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-2">Ukuran Kertas</label>
                        <div class="grid grid-cols-3 gap-2">
                            @foreach(['58mm' => '58 mm', '80mm' => '80 mm', 'custom' => 'Custom'] as $val => $lbl)
                                <label class="cursor-pointer">
                                    <input type="radio" wire:model.live="paper_size" value="{{ $val }}" class="sr-only peer">
                                    <div class="text-center py-2.5 rounded-xl border-2 text-sm font-semibold transition
                                        peer-checked:border-emerald-500 peer-checked:bg-emerald-50 peer-checked:text-emerald-700
                                        border-gray-200 text-gray-500 hover:border-gray-300 bg-gray-50">
                                        {{ $lbl }}
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    @if($paper_size === 'custom')
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Lebar Kertas (px)</label>
                            <input type="number" wire:model="paper_width_px"
                                class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 outline-none transition">
                        </div>
                    @endif

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

        {{-- ── Row 2: Konfigurasi Email Gmail ───────────────────────────────── --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-50 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center">
                        <i data-lucide="mail" class="w-4 h-4 text-blue-600"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800 text-sm">Email Struk Otomatis</h3>
                        <p class="text-xs text-gray-400">Gmail SMTP — dikirim ke pelanggan Self Order setelah pembayaran</p>
                    </div>
                </div>
                {{-- Badge status mail driver --}}
                @if(config('mail.default') === 'smtp')
                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-full">
                        <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span>SMTP Aktif
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-amber-700 bg-amber-50 border border-amber-200 px-2.5 py-1 rounded-full">
                        <span class="w-1.5 h-1.5 bg-amber-400 rounded-full"></span>Log Mode
                    </span>
                @endif
            </div>

            <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-6">

                {{-- Kiri: Panduan setup --}}
                <div class="space-y-4">
                    <div class="p-4 bg-blue-50 border border-blue-100 rounded-xl">
                        <div class="flex gap-2.5">
                            <i data-lucide="info" class="w-4 h-4 text-blue-500 mt-0.5 shrink-0"></i>
                            <div class="text-sm text-blue-800">
                                <p class="font-semibold mb-2">Cara setup Gmail App Password:</p>
                                <ol class="list-decimal list-inside space-y-1 text-blue-700 text-xs">
                                    <li>Aktifkan <strong>2-Step Verification</strong> di akun Google</li>
                                    <li>Buka <strong>myaccount.google.com</strong> → Security</li>
                                    <li>Cari <strong>"App passwords"</strong> → klik</li>
                                    <li>Buat app password baru untuk "Mail"</li>
                                    <li>Salin 16-karakter password yang muncul</li>
                                    <li>Isi ke <code class="bg-blue-100 px-1 rounded font-mono">MAIL_PASSWORD</code> di .env</li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    {{-- .env snippet --}}
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Konfigurasi .env VPS</p>
                        <div class="bg-gray-900 rounded-xl overflow-hidden text-xs font-mono">
                            <div class="flex items-center px-4 py-2 border-b border-gray-800">
                                <div class="flex gap-1.5 mr-3">
                                    <div class="w-2.5 h-2.5 rounded-full bg-red-500"></div>
                                    <div class="w-2.5 h-2.5 rounded-full bg-yellow-500"></div>
                                    <div class="w-2.5 h-2.5 rounded-full bg-green-500"></div>
                                </div>
                                <span class="text-gray-400">.env</span>
                            </div>
                            <pre class="p-4 text-gray-300 leading-6 overflow-x-auto"><span class="text-gray-500"># SMTP Gmail</span>
<span class="text-emerald-400">MAIL_MAILER</span>=<span class="text-amber-300">smtp</span>
<span class="text-emerald-400">MAIL_HOST</span>=<span class="text-amber-300">smtp.gmail.com</span>
<span class="text-emerald-400">MAIL_PORT</span>=<span class="text-amber-300">587</span>
<span class="text-emerald-400">MAIL_ENCRYPTION</span>=<span class="text-amber-300">tls</span>
<span class="text-emerald-400">MAIL_USERNAME</span>=<span class="text-sky-300">nama@gmail.com</span>
<span class="text-gray-500"># App Password, BUKAN password Gmail!</span>
<span class="text-emerald-400">MAIL_PASSWORD</span>=<span class="text-sky-300">xxxx xxxx xxxx xxxx</span>
<span class="text-emerald-400">MAIL_FROM_ADDRESS</span>=<span class="text-sky-300">nama@gmail.com</span>
<span class="text-emerald-400">MAIL_FROM_NAME</span>=<span class="text-amber-300">"Nama Toko Anda"</span></pre>
                        </div>
                    </div>
                </div>

                {{-- Kanan: Status & Test --}}
                <div class="space-y-4">
                    {{-- Status Cards --}}
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Status Konfigurasi Saat Ini</p>
                        <div class="grid grid-cols-2 gap-2">
                            @php
                                $smtpHost = config('mail.mailers.smtp.host', '');
                                $mailCfg = [
                                    ['label' => 'Driver',    'value' => strtoupper(config('mail.default', '-')),             'ok' => config('mail.default') === 'smtp'],
                                    ['label' => 'Host',      'value' => $smtpHost ?: 'Belum diset',                          'ok' => str_contains($smtpHost, 'gmail')],
                                    ['label' => 'Port',      'value' => config('mail.mailers.smtp.port', '-'),               'ok' => config('mail.mailers.smtp.port') == 587],
                                    ['label' => 'Username',  'value' => config('mail.mailers.smtp.username') ? '✓ Set' : '✗ Kosong', 'ok' => !empty(config('mail.mailers.smtp.username'))],
                                    ['label' => 'Password',  'value' => config('mail.mailers.smtp.password') ? '✓ Set' : '✗ Kosong', 'ok' => !empty(config('mail.mailers.smtp.password'))],
                                    ['label' => 'From Addr', 'value' => config('mail.from.address') ?: 'Belum diset',        'ok' => !empty(config('mail.from.address')) && config('mail.from.address') !== 'hello@example.com'],
                                ];
                            @endphp
                            @foreach($mailCfg as $cfg)
                                <div class="p-3 rounded-xl border {{ $cfg['ok'] ? 'border-emerald-200 bg-emerald-50' : 'border-gray-200 bg-gray-50' }}">
                                    <div class="text-[10px] font-semibold {{ $cfg['ok'] ? 'text-emerald-600' : 'text-gray-400' }} uppercase tracking-wide mb-0.5">{{ $cfg['label'] }}</div>
                                    <div class="text-xs font-bold {{ $cfg['ok'] ? 'text-emerald-800' : 'text-gray-500' }} truncate">{{ $cfg['value'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Test Email --}}
                    <div class="border border-gray-100 rounded-xl p-4 bg-gray-50">
                        <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-3">Kirim Email Test</p>
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
                        <p class="mt-2 text-xs text-gray-400">Pastikan konfigurasi .env sudah benar sebelum test</p>
                    </div>

                    {{-- Info auto-send --}}
                    <div class="flex gap-2.5 p-3.5 bg-amber-50 border border-amber-100 rounded-xl">
                        <i data-lucide="zap" class="w-4 h-4 text-amber-500 shrink-0 mt-0.5"></i>
                        <p class="text-xs text-amber-700">
                            Struk email dikirim <strong>otomatis via queue</strong> setelah pembayaran QRIS Self Order berhasil,
                            jika pelanggan memasukkan alamat email saat checkout.
                        </p>
                    </div>
                </div>
            </div>
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


