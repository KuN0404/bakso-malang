<div x-init="lucide.createIcons()">
    {{-- Page Header --}}
    <div class="mb-8">
        <div class="flex items-center gap-3 mb-1">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-700 flex items-center justify-center shadow-sm">
                <i data-lucide="message-circle" class="w-5 h-5 text-white"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">WhatsApp</h1>
        </div>
        <p class="text-sm text-gray-500 ml-12">Sambungkan nomor WhatsApp toko (Fonnte) dan pantau riwayat pengiriman struk</p>
    </div>

    <div class="space-y-6">
        {{-- ── Kartu Koneksi Device ───────────────────────────────── --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-50 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-emerald-50 rounded-lg flex items-center justify-center">
                        <i data-lucide="smartphone" class="w-4 h-4 text-emerald-600"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800 text-sm">Koneksi Device</h3>
                        <p class="text-xs text-gray-400">Nomor WhatsApp toko untuk kirim struk digital</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @if($fonnteConfigured)
                        <button type="button" wire:click="refreshStatus" wire:loading.attr="disabled" wire:target="refreshStatus"
                            class="px-3 py-1.5 text-xs font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition inline-flex items-center gap-1.5 disabled:opacity-50">
                            <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                            <span wire:loading.remove wire:target="refreshStatus">Cek Status</span>
                            <span wire:loading wire:target="refreshStatus">Mengecek...</span>
                        </button>
                    @endif
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

        {{-- ── Log Pengiriman ───────────────────────────────── --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-purple-50 rounded-lg flex items-center justify-center">
                        <i data-lucide="list" class="w-4 h-4 text-purple-600"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800 text-sm">Log Pengiriman</h3>
                        <p class="text-xs text-gray-400">Riwayat setiap percobaan kirim struk WhatsApp</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nomor..."
                        class="px-3 py-1.5 text-sm border border-gray-200 rounded-lg w-40 sm:w-48">
                    <select wire:model.live="statusFilter" class="px-3 py-1.5 text-sm border border-gray-200 rounded-lg">
                        <option value="">Semua Status</option>
                        <option value="sent">Terkirim</option>
                        <option value="failed">Gagal</option>
                        <option value="blocked">Diblokir</option>
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Waktu</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Nomor</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($logs as $log)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 text-xs text-gray-500 whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-6 py-3 text-sm text-gray-700">{{ $log->phone }}</td>
                                <td class="px-6 py-3">
                                    @if($log->status === 'sent')
                                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">Terkirim</span>
                                    @elseif($log->status === 'blocked')
                                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium bg-gray-200 text-gray-600">Diblokir</span>
                                    @else
                                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">Gagal</span>
                                    @endif
                                    @if($log->fonnte_status)
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-medium bg-blue-50 text-blue-600 ml-1">{{ $log->fonnte_status }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-xs text-gray-500">{{ $log->reason ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-gray-500">Belum ada riwayat pengiriman WhatsApp</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                @if($logs->hasPages())
                    <div class="px-6 py-4 border-t">{{ $logs->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@script
<script>lucide.createIcons();Livewire.hook('morph.updated',()=>lucide.createIcons());</script>
@endscript
