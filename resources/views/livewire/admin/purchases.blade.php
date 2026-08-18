<div class="relative">
    <!-- Loading Overlay -->
    <div wire:loading class="absolute inset-0 bg-white/50 backdrop-blur-[1px] z-50 rounded-xl">
        <div class="sticky top-[40vh] flex flex-col items-center justify-center w-full gap-2">
            <svg class="animate-spin h-8 w-8 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-sm font-medium text-primary-600">Memuat...</span>
        </div>
    </div>

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Pembelian Stok</h1>
            <p class="text-gray-500">Catat transaksi pembelian bahan baku mentah atau produk siap jual dari supplier</p>
        </div>
        @can('create_purchases')
            <a href="{{ route('admin.purchases.create') }}" wire:navigate class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg flex items-center gap-2 shadow-sm transition-colors">
                <i data-lucide="plus" class="w-5 h-5"></i>
                Tambah Pembelian
            </a>
        @endcan
    </div>

    <!-- Search & Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6 p-4">
        <div class="relative">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari no. faktur atau nama supplier..."
                class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
        </div>
    </div>

    <!-- Table Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">No. Faktur</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tanggal Beli</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Supplier</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Jumlah Item</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Total Biaya</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Petugas</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($purchases as $p)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 font-mono text-xs font-semibold text-primary-600">{{ $p->invoice_number }}</td>
                        <td class="px-6 py-4 text-gray-700">{{ $p->purchase_date->format('d/m/Y') }}</td>
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $p->supplier?->name ?: '-' }}</td>
                        <td class="px-6 py-4 text-center font-medium">{{ $p->items->count() }} Item</td>
                        <td class="px-6 py-4 font-bold text-gray-900">Rp {{ number_format($p->total_amount, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-xs text-gray-500">{{ $p->user?->name ?? 'System' }}</td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.purchases.show', $p) }}" wire:navigate title="Lihat Detail" class="p-2 text-gray-400 hover:text-primary-600 rounded-lg hover:bg-gray-100 inline-block">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                            <i data-lucide="shopping-cart" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                            <p>Belum ada riwayat pembelian stok</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($purchases->count() > 0)
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <span>Tampilkan</span>
                    <select wire:model.live="perPage" class="border-gray-200 rounded-lg text-sm focus:ring-primary-500 focus:border-primary-500 py-1.5 px-3">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                    <span>pembelian</span>
                </div>
                <div>
                    {{ $purchases->links() }}
                </div>
            </div>
        @endif
    </div>
</div>

@script
<script>
    lucide.createIcons();
</script>
@endscript
