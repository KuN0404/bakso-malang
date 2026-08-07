@php
    $printerConfig = $printerConfig ?? ($this->printerConfig ?? null);
    $settings = $settings ?? ($this->generalSettings ?? []);
    $receiptSettings = $receiptSettings ?? ($this->receiptSettings ?? []);

    $paperWidth = $printerConfig?->css_width ?? '58mm';
    $paperMargin = $printerConfig?->css_margin ?? '0mm 0mm 0mm 0mm';
@endphp

<style>
    #receipt-content, #receipt-content * {
        color: #000 !important;
        border-color: #000 !important;
    }
    /* Pengaman anti-overflow: baris "flex justify-between" (mis. label vs harga)
       secara default TIDAK menyusut di bawah lebar alami tulisannya (min-width:auto
       bawaan flexbox) — kalau teksnya panjang (alamat toko, catatan, nama modifier,
       nomor invoice, dll), baris itu bisa meluber melewati lebar kertas. Rangkaian
       aturan ini memaksa: (1) baris flex selebar 100% wadahnya — tidak menyusut
       mengikuti lebar teks di dalamnya, (2) anak flex boleh menyusut & teksnya
       boleh patah, (3) apa pun yang masih tersisa lebih lebar tetap DIPOTONG
       (overflow:hidden) alih-alih dibiarkan meluber keluar kertas. */
    #receipt-content {
        box-sizing: border-box !important;
        overflow: hidden !important;
    }
    #receipt-content, #receipt-content * {
        max-width: 100%;
        overflow-wrap: break-word;
        word-break: break-word;
    }
    #receipt-content .flex {
        width: 100%;
        overflow: hidden;
    }
    #receipt-content .flex > * {
        min-width: 0;
        flex-shrink: 1;
    }
    @media print {
        @page {
            size: {{ $paperWidth }} auto;
            margin: {{ $paperMargin }};
        }
        #receipt-content {
            width: {{ $paperWidth }} !important;
            font-family: {{ $printerConfig?->font_family ?? 'monospace' }};
            font-size: {{ $printerConfig?->font_size_px ?? 12 }}px;
        }
    }
</style>

<div id="receipt-content" class="font-mono text-xs text-black" style="max-width: {{ $paperWidth }}; width: {{ $paperWidth }}; margin: 0 auto; color: #000 !important;">
    <!-- Header -->
    <div class="text-center border-b border-dashed border-black pb-1.5 mb-1.5">
        <h1 class="text-base font-bold">{{ $settings['store_name'] ?? 'Bakso Malang' }}</h1>
        <p class="text-xs">{{ $settings['store_address'] ?? '' }}</p>
        <p class="text-xs">{{ $settings['store_phone'] ?? '' }}</p>
    </div>

    <!-- Transaction Info -->
    <div class="border-b border-dashed border-black pb-1.5 mb-1.5 text-xs">
        <div class="flex justify-between">
            <span>No:</span>
            <span class="font-bold">{{ $transaction->invoice_number }}</span>
        </div>
        <div class="flex justify-between">
            <span>Tanggal:</span>
            <span>{{ $transaction->created_at->format('d/m/Y H:i') }}</span>
        </div>
        <div class="flex justify-between">
            <span>Kasir:</span>
            <span>{{ $transaction->user->name ?? '-' }}</span>
        </div>
        @if($transaction->customer_name)
        <div class="flex justify-between">
            <span>Customer:</span>
            <span>{{ $transaction->customer_name }}</span>
        </div>
        @endif
        <div class="flex justify-between">
            <span>Tipe:</span>
            <span class="font-bold">{{ $transaction->order_type === 'dine_in' ? 'Makan di Tempat' : 'Bawa Pulang' }}</span>
        </div>
        @if($transaction->service_area_id)
        <div class="flex justify-between">
            <span>Area / Meja:</span>
            <span class="font-bold text-md">{{ $transaction->serviceArea->name ?? '-' }}</span>
        </div>
        @endif
        @if($transaction->pager_id)
        <div class="flex justify-between">
            <span>Nomor Pager:</span>
            <span class="font-bold text-md">{{ $transaction->pager->number ?? '-' }}</span>
        </div>
        @endif
    </div>

    <!-- Items -->
    <div class="border-b border-dashed border-black pb-1.5 mb-1.5">
        @foreach($transaction->details as $detail)
            <div class="mb-1">
                <div class="flex justify-between">
                    <span class="flex-1 font-bold">{{ $detail->product_name }}</span>
                </div>
                <div class="flex justify-between pl-2">
                    <span>{{ $detail->quantity }} x Rp {{ number_format($detail->unit_price, 0, ',', '.') }}</span>
                    <span>Rp {{ number_format($detail->unit_price * $detail->quantity, 0, ',', '.') }}</span>
                </div>
                @if($detail->modifiers->isNotEmpty())
                    @foreach($detail->modifiers as $mod)
                        @php
                            $modQty = $mod->pivot->quantity ?? 1;
                            $modPrice = ($mod->pivot->price_adjustment ?? 0) * $modQty;
                        @endphp
                        <div class="flex justify-between pl-4 text-[11px]">
                            <span>+ {{ $mod->pivot->modifier_name ?? $mod->name }}{{ $modQty > 1 ? " ×{$modQty}" : '' }}</span>
                            @if($modPrice > 0)
                                <span>Rp {{ number_format($modPrice, 0, ',', '.') }}</span>
                            @endif
                        </div>
                    @endforeach
                    <div class="flex justify-between pl-2 font-semibold border-t border-dotted border-black">
                        <span>Jumlah</span>
                        <span>Rp {{ number_format($detail->subtotal, 0, ',', '.') }}</span>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <!-- Totals -->
    <div class="border-b border-dashed border-black pb-1.5 mb-1.5">
        <div class="flex justify-between">
            <span>Subtotal:</span>
            <span>Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</span>
        </div>
        @if($transaction->tax_amount > 0)
            <div class="flex justify-between">
                <span>Pajak:</span>
                <span>Rp {{ number_format($transaction->tax_amount, 0, ',', '.') }}</span>
            </div>
        @endif
        @if($transaction->discount_amount > 0)
            <div class="flex justify-between">
                <span>Diskon:</span>
                <span>-Rp {{ number_format($transaction->discount_amount, 0, ',', '.') }}</span>
            </div>
        @endif
        <div class="flex justify-between font-bold text-sm mt-1">
            <span>TOTAL:</span>
            <span>Rp {{ number_format($transaction->total, 0, ',', '.') }}</span>
        </div>
    </div>

    <!-- Payment -->
    <div class="border-b border-dashed border-black pb-1.5 mb-1.5">
        <div class="flex justify-between">
            <span>Bayar ({{ ucfirst($transaction->payment_method) }}):</span>
            <span>Rp {{ number_format($transaction->paid_amount, 0, ',', '.') }}</span>
        </div>
        @if($transaction->change_amount > 0)
            <div class="flex justify-between font-bold">
                <span>Kembali:</span>
                <span>Rp {{ number_format($transaction->change_amount, 0, ',', '.') }}</span>
            </div>
        @endif
    </div>

    @if($transaction->notes)
    <div class="border-b border-dashed border-black pb-1.5 mb-1.5 text-xs">
        <span class="font-bold">Catatan:</span>
        <p class="italic whitespace-pre-wrap">{{ $transaction->notes }}</p>
    </div>
    @endif

    <!-- Nomor Antrian (kiri) & Footer (kanan) -->
    <div class="flex justify-between items-end mt-2 pt-1.5 border-t border-dashed border-black">
        <div class="text-left" style="white-space: nowrap; flex-shrink: 0;">
            <p class="text-xs">ANTRIAN</p>
            <p class="text-4xl font-bold">{{ $transaction->queue_display }}</p>
        </div>
        <div class="text-right text-xs" style="min-width: 0;">
            <p>{{ $receiptSettings['header_text'] ?? 'Terima Kasih!' }}</p>
            <p class="mt-1">{{ $receiptSettings['footer_text'] ?? '' }}</p>
        </div>
    </div>
</div>
