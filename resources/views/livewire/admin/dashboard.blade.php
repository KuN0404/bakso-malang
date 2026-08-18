<div>
    <!-- Welcome Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Selamat Datang, {{ auth()->user()->name }}!</h1>
        <p class="text-gray-500">Berikut ringkasan aktivitas hari ini.</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Penjualan -->
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Penjualan Hari Ini</p>
                    <p class="text-2xl font-bold text-gray-800 mt-1">Rp {{ number_format($this->todayStats['total_sales'], 0, ',', '.') }}</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                    <i data-lucide="banknote" class="w-6 h-6 text-green-600"></i>
                </div>
            </div>
        </div>

        <!-- Total Transaksi -->
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Transaksi Hari Ini</p>
                    <p class="text-2xl font-bold text-gray-800 mt-1">{{ $this->todayStats['transaction_count'] }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                    <i data-lucide="shopping-cart" class="w-6 h-6 text-blue-600"></i>
                </div>
            </div>
        </div>

        <!-- Rata-rata Transaksi -->
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Rata-rata Transaksi</p>
                    <p class="text-2xl font-bold text-gray-800 mt-1">Rp {{ number_format($this->todayStats['average_transaction'], 0, ',', '.') }}</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                    <i data-lucide="trending-up" class="w-6 h-6 text-purple-600"></i>
                </div>
            </div>
        </div>

        <!-- Shift Aktif -->
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Shift Aktif</p>
                    <p class="text-2xl font-bold text-gray-800 mt-1">{{ $this->counts['active_shifts'] }}</p>
                </div>
                <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center">
                    <i data-lucide="clock" class="w-6 h-6 text-orange-600"></i>
                </div>
            </div>
        </div>
    </div>

    @php
        $lowStock = $this->lowStockSummary;
        $lowStockTotal = $lowStock['ingredients_low'] + $lowStock['ingredients_out'] + $lowStock['components_low'] + $lowStock['components_out'];
    @endphp
    @if($lowStockTotal > 0)
        <a href="{{ route('admin.reports.inventory', ['activeTab' => 'low_stock']) }}" class="block bg-red-50 border border-red-200 rounded-xl p-5 mb-8 hover:bg-red-100 transition-colors">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center shrink-0">
                        <i data-lucide="alert-triangle" class="w-5 h-5 text-red-600"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-red-800">{{ $lowStockTotal }} item stok menipis / habis</p>
                        <p class="text-sm text-red-600">
                            Bahan baku: {{ $lowStock['ingredients_low'] }} menipis, {{ $lowStock['ingredients_out'] }} habis ·
                            Komponen: {{ $lowStock['components_low'] }} menipis, {{ $lowStock['components_out'] }} habis
                        </p>
                    </div>
                </div>
                <span class="text-red-700 hover:text-red-800 text-sm font-medium flex items-center gap-1 shrink-0">
                    Lihat Detail
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </span>
            </div>
        </a>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Transaksi Terbaru -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-800">Transaksi Terbaru</h3>
                <a href="#" class="text-primary-600 hover:text-primary-700 text-sm font-medium flex items-center gap-1">
                    Lihat Semua
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">No. Invoice</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Waktu</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Total</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($this->recentTransactions as $transaction)
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-4">
                                    <span class="font-medium text-gray-800">{{ $transaction->invoice_number }}</span>
                                </td>
                                <td class="px-5 py-4 text-gray-600">
                                    {{ $transaction->created_at->format('d M Y, H:i') }}
                                </td>
                                <td class="px-5 py-4 font-medium text-gray-800">
                                    Rp {{ number_format($transaction->total, 0, ',', '.') }}
                                </td>
                                <td class="px-5 py-4">
                                    @if($transaction->status === 'completed')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                            <i data-lucide="check-circle" class="w-3 h-3"></i>
                                            Selesai
                                        </span>
                                    @elseif($transaction->status === 'pending')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">
                                            <i data-lucide="clock" class="w-3 h-3"></i>
                                            Pending
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                            <i data-lucide="x-circle" class="w-3 h-3"></i>
                                            Dibatalkan
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <button class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-8 text-center text-gray-500">
                                    Belum ada transaksi
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Produk Terlaris -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="p-5 border-b border-gray-100">
                <h3 class="font-semibold text-gray-800">Produk Terlaris Bulan Ini</h3>
            </div>
            <div class="p-5 space-y-4">
                @forelse($this->topProducts as $index => $product)
                    <div class="flex items-center gap-4">
                        <div class="w-8 h-8 rounded-lg {{ $index === 0 ? 'bg-yellow-100 text-yellow-600' : ($index === 1 ? 'bg-gray-100 text-gray-600' : 'bg-orange-50 text-orange-600') }} flex items-center justify-center font-bold text-sm">
                            {{ $index + 1 }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-800 truncate">{{ $product->product_name }}</p>
                            <p class="text-sm text-gray-500">{{ $product->total_quantity }} terjual</p>
                        </div>
                        <p class="font-medium text-gray-800">Rp {{ number_format($product->total_sales, 0, ',', '.') }}</p>
                    </div>
                @empty
                    <p class="text-center text-gray-500 py-4">Belum ada data</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
        <div class="bg-gradient-to-br from-primary-500 to-primary-700 rounded-xl p-6 text-white">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                    <i data-lucide="package" class="w-6 h-6"></i>
                </div>
                <div>
                    <p class="text-white/80 text-sm">Total Produk</p>
                    <p class="text-2xl font-bold">{{ $this->counts['products'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-700 rounded-xl p-6 text-white">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                    <i data-lucide="folder" class="w-6 h-6"></i>
                </div>
                <div>
                    <p class="text-white/80 text-sm">Total Kategori</p>
                    <p class="text-2xl font-bold">{{ $this->counts['categories'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-orange-500 to-orange-700 rounded-xl p-6 text-white">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                    <i data-lucide="x-circle" class="w-6 h-6"></i>
                </div>
                <div>
                    <p class="text-white/80 text-sm">Dibatalkan Hari Ini</p>
                    <p class="text-2xl font-bold">{{ $this->todayStats['cancelled_count'] }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

@script
<script>
    // Reinitialize icons after Livewire updates
    lucide.createIcons();
</script>
@endscript
