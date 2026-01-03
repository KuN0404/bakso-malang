<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Laporan Penjualan per Produk</h1>
            <p class="text-gray-500">Analisis penjualan berdasarkan produk</p>
        </div>
        <a href="{{ $this->getExportUrl() }}" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg flex items-center gap-2">
            <i data-lucide="download" class="w-5 h-5"></i>
            Export Excel
        </a>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl p-4 border border-gray-100">
            <p class="text-sm text-gray-500">Produk Terjual</p>
            <p class="text-2xl font-bold text-gray-800">{{ number_format($summary['total_products']) }} jenis</p>
        </div>
        <div class="bg-white rounded-xl p-4 border border-gray-100">
            <p class="text-sm text-gray-500">Total Qty Terjual</p>
            <p class="text-2xl font-bold text-blue-600">{{ number_format($summary['total_qty']) }} unit</p>
        </div>
        <div class="bg-white rounded-xl p-4 border border-gray-100">
            <p class="text-sm text-gray-500">Total Pendapatan</p>
            <p class="text-2xl font-bold text-green-600">Rp {{ number_format($summary['total_revenue'], 0, ',', '.') }}</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-4">
        <div class="flex flex-wrap items-center gap-4">
            <!-- Period Buttons -->
            <div class="flex flex-wrap gap-2">
                @foreach([
                    'today' => 'Hari Ini',
                    'yesterday' => 'Kemarin',
                    'this_week' => 'Minggu Ini',
                    'this_month' => 'Bulan Ini',
                    'last_month' => 'Bulan Lalu',
                ] as $key => $label)
                    <button 
                        wire:click="setPeriod('{{ $key }}')"
                        class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors {{ $period === $key ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <!-- Date Range -->
            <div class="flex items-center gap-2">
                <input type="date" wire:model="startDate" class="px-3 py-1.5 border border-gray-200 rounded-lg text-sm">
                <span class="text-gray-400">-</span>
                <input type="date" wire:model="endDate" class="px-3 py-1.5 border border-gray-200 rounded-lg text-sm">
                <button wire:click="applyDateRange" class="px-3 py-1.5 bg-gray-800 text-white rounded-lg text-sm">Terapkan</button>
            </div>
        </div>

        <div class="flex items-center gap-4 mt-4">
            <!-- Category Filter -->
            <select wire:model.live="categoryId" class="px-4 py-2 border border-gray-200 rounded-lg">
                <option value="">Semua Kategori</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">#</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Produk</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Kategori</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Qty Terjual</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Harga Rata-rata</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Total Pendapatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($productSales as $index => $product)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-500">{{ $productSales->firstItem() + $index }}</td>
                            <td class="px-4 py-3">
                                <span class="font-medium text-gray-800">{{ $product->product_name }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                                    {{ $product->category_name ?? 'Tanpa Kategori' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-800">
                                {{ number_format($product->total_qty) }}
                            </td>
                            <td class="px-4 py-3 text-right text-gray-600">
                                Rp {{ number_format($product->avg_price, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-green-600">
                                Rp {{ number_format($product->total_revenue, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-gray-500">
                                <i data-lucide="package" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                                <p>Tidak ada data penjualan produk</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($productSales->hasPages())
            <div class="p-4 border-t bg-gray-50">
                {{ $productSales->links(data: ['scrollTo' => false]) }}
            </div>
        @endif
    </div>

    <!-- Print Button -->
    <div class="mt-4 flex justify-end">
        <button onclick="window.print()" class="px-4 py-2 bg-gray-800 hover:bg-gray-900 text-white font-medium rounded-lg flex items-center gap-2">
            <i data-lucide="printer" class="w-5 h-5"></i>
            Print Laporan
        </button>
    </div>
</div>
@script
<script>
lucide.createIcons();
Livewire.hook('morph.updated', () => queueMicrotask(() => lucide.createIcons()));
</script>
@endscript
