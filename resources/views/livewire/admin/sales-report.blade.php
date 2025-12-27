<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Laporan Penjualan</h1>
            <p class="text-gray-500">Analisis penjualan berdasarkan periode</p>
        </div>
    </div>

    <!-- Period Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6 p-4">
        <div class="flex flex-wrap gap-4 items-center">
            <div class="flex gap-2">
                <button wire:click="setPeriod('today')" class="px-4 py-2 rounded-lg font-medium {{ $period === 'today' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">Hari Ini</button>
                <button wire:click="setPeriod('yesterday')" class="px-4 py-2 rounded-lg font-medium {{ $period === 'yesterday' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">Kemarin</button>
                <button wire:click="setPeriod('this_week')" class="px-4 py-2 rounded-lg font-medium {{ $period === 'this_week' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">Minggu Ini</button>
                <button wire:click="setPeriod('this_month')" class="px-4 py-2 rounded-lg font-medium {{ $period === 'this_month' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">Bulan Ini</button>
            </div>
            <div class="flex items-center gap-2">
                <input type="date" wire:model.live="startDate" class="px-4 py-2 border border-gray-200 rounded-lg">
                <span class="text-gray-400">-</span>
                <input type="date" wire:model.live="endDate" class="px-4 py-2 border border-gray-200 rounded-lg">
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Total Penjualan</p>
                    <p class="text-2xl font-bold text-gray-800">Rp {{ number_format($dailySummary['total_sales'] ?? 0, 0, ',', '.') }}</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                    <i data-lucide="banknote" class="w-6 h-6 text-green-600"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Transaksi Selesai</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $dailySummary['completed_count'] ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                    <i data-lucide="check-circle" class="w-6 h-6 text-blue-600"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Dibatalkan</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $dailySummary['cancelled_count'] ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                    <i data-lucide="x-circle" class="w-6 h-6 text-red-600"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Rata-rata</p>
                    <p class="text-2xl font-bold text-gray-800">Rp {{ number_format($dailySummary['average_transaction'] ?? 0, 0, ',', '.') }}</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                    <i data-lucide="trending-up" class="w-6 h-6 text-purple-600"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- By Category -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="p-5 border-b"><h3 class="font-semibold text-gray-800">Penjualan per Kategori</h3></div>
            <div class="p-5 space-y-3">
                @forelse($categoryReport as $cat)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-primary-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="folder" class="w-4 h-4 text-primary-600"></i>
                            </div>
                            <span class="font-medium text-gray-800">{{ $cat->category_name }}</span>
                        </div>
                        <div class="text-right">
                            <p class="font-medium text-gray-800">Rp {{ number_format($cat->total_sales, 0, ',', '.') }}</p>
                            <p class="text-xs text-gray-500">{{ $cat->total_quantity }} item</p>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-gray-500 py-4">Tidak ada data</p>
                @endforelse
            </div>
        </div>

        <!-- By Payment -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="p-5 border-b"><h3 class="font-semibold text-gray-800">Penjualan per Metode Pembayaran</h3></div>
            <div class="p-5 space-y-3">
                @forelse($paymentReport as $pay)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="wallet" class="w-4 h-4 text-green-600"></i>
                            </div>
                            <span class="font-medium text-gray-800">{{ ucfirst($pay->payment_method) }}</span>
                        </div>
                        <div class="text-right">
                            <p class="font-medium text-gray-800">Rp {{ number_format($pay->total_sales, 0, ',', '.') }}</p>
                            <p class="text-xs text-gray-500">{{ $pay->transaction_count }} transaksi</p>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-gray-500 py-4">Tidak ada data</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Products -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="p-5 border-b"><h3 class="font-semibold text-gray-800">Produk Terlaris</h3></div>
            <div class="p-5 space-y-3">
                @forelse($topProducts as $index => $product)
                    <div class="flex items-center gap-4">
                        <div class="w-8 h-8 rounded-lg {{ $index === 0 ? 'bg-yellow-100 text-yellow-600' : 'bg-gray-100 text-gray-600' }} flex items-center justify-center font-bold text-sm">{{ $index + 1 }}</div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-800 truncate">{{ $product->product_name }}</p>
                            <p class="text-sm text-gray-500">{{ $product->total_quantity }} terjual</p>
                        </div>
                        <p class="font-medium text-gray-800">Rp {{ number_format($product->total_sales, 0, ',', '.') }}</p>
                    </div>
                @empty
                    <p class="text-center text-gray-500 py-4">Tidak ada data</p>
                @endforelse
            </div>
        </div>

        <!-- Peak Hours -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="p-5 border-b"><h3 class="font-semibold text-gray-800">Jam Sibuk</h3></div>
            <div class="p-5 space-y-3">
                @forelse($peakHours->take(8) as $peak)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="clock" class="w-4 h-4 text-orange-600"></i>
                            </div>
                            <span class="font-medium text-gray-800">{{ str_pad($peak->hour, 2, '0', STR_PAD_LEFT) }}:00</span>
                        </div>
                        <div class="text-right">
                            <p class="font-medium text-gray-800">{{ $peak->transaction_count }} transaksi</p>
                            <p class="text-xs text-gray-500">Rp {{ number_format($peak->total_sales, 0, ',', '.') }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-gray-500 py-4">Tidak ada data</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@script
<script>lucide.createIcons();Livewire.hook('morph.updated',()=>lucide.createIcons());</script>
@endscript
