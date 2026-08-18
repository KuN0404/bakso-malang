<div>
    <!-- Breadcrumb & Header -->
    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
            <a href="{{ route('admin.purchases.index') }}" wire:navigate class="hover:text-primary-600">Pembelian Stok</a>
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
            <span class="text-gray-900">Detail</span>
        </div>
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-800 font-mono">{{ $purchase->invoice_number }}</h1>
            <a href="{{ route('admin.purchases.index') }}" wire:navigate class="px-4 py-2 bg-white border border-gray-200 text-gray-700 font-medium rounded-lg hover:bg-gray-50 flex items-center gap-2">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                Kembali
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Info -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                    <h3 class="font-semibold text-gray-800">Daftar Barang Dibeli</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b text-gray-600 font-semibold">
                                <th class="py-2 px-6">Tipe</th>
                                <th class="py-2 px-3">Nama Barang</th>
                                <th class="py-2 px-3 text-center">Jumlah</th>
                                <th class="py-2 px-3 text-right">Harga Satuan</th>
                                <th class="py-2 px-6 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($purchase->items as $item)
                                <tr>
                                    <td class="py-2.5 px-6">
                                        <span class="text-xs font-semibold px-2 py-0.5 rounded {{ $item->item_type === 'ingredient' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800' }}">
                                            {{ $item->item_type === 'ingredient' ? 'Bahan Baku' : 'Produk Jadi' }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 font-medium">
                                        {{ $item->item_type === 'ingredient' ? $item->ingredient?->name : $item->product?->name }}
                                    </td>
                                    <td class="py-2.5 px-3 text-center">
                                        {{ number_format($item->quantity, 2, ',', '.') }} {{ $item->item_type === 'ingredient' ? $item->ingredient?->unit?->symbol : 'Pcs' }}
                                    </td>
                                    <td class="py-2.5 px-3 text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                    <td class="py-2.5 px-6 text-right font-bold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t font-bold bg-gray-50">
                                <td colspan="4" class="py-3 px-3 text-right">Total Transaksi:</td>
                                <td class="py-3 px-6 text-right text-primary-700">Rp {{ number_format($purchase->total_amount, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                @if($purchase->note)
                    <div class="m-6 mt-0 text-xs text-gray-500 bg-gray-50 p-3 rounded-lg border border-gray-100">
                        <strong>Catatan:</strong> {{ $purchase->note }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                <h3 class="font-semibold text-gray-800">Informasi Faktur</h3>
                <div>
                    <p class="text-xs text-gray-500 uppercase mb-1">No. Faktur</p>
                    <p class="font-mono text-gray-700">{{ $purchase->invoice_number }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase mb-1">Tanggal Pembelian</p>
                    <p class="text-gray-700">{{ $purchase->purchase_date->format('d M Y') }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase mb-1">Supplier</p>
                    <p class="text-gray-700">{{ $purchase->supplier?->name ?: '-' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase mb-1">Petugas</p>
                    <p class="text-gray-700">{{ $purchase->user?->name ?? 'System' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase mb-1">Total Biaya</p>
                    <p class="font-bold text-lg text-primary-600">Rp {{ number_format($purchase->total_amount, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

@script
<script>
    lucide.createIcons();
</script>
@endscript
