<div x-data="{ printFormat: 'A4' }" class="relative">
    <!-- Loading Overlay -->
    <div wire:loading wire:target="periodType, activeTab, startDate, endDate, selectedMonth, selectedYear, selectedWeek, selectedWeekYear, categoryId, resetFilters, setTab, setPeriodType, applyDateRange" class="absolute inset-0 bg-white/50 backdrop-blur-[1px] z-50 rounded-xl">
        <div class="sticky top-[40vh] flex flex-col items-center justify-center w-full gap-2">
            <svg class="animate-spin h-8 w-8 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-sm font-medium text-primary-600">Memuat Laporan...</span>
        </div>
    </div>
    <style>
        @media print {
            /* Hide non-printable elements */
            aside, nav, .sidebar, .no-print, button, .lucide, .items-center.gap-4, .items-center.gap-2 {
                display: none !important;
            }
            
            /* Reset layout for print */
            body, main, div, .main-content {
                background: white !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                height: auto !important;
                overflow: visible !important;
            }

            /* Adjust container width */
            .p-4, .p-6, .py-8 {
                padding: 0 !important;
            }

            /* Ensure summary cards grid fits */
            .grid {
                display: flex !important;
                flex-wrap: wrap !important;
                gap: 1rem !important;
            }
            .grid > div {
                flex: 1 !important;
                border: 1px solid #ddd !important;
            }
            
            /* Table optimizations */
            table {
                width: 100% !important;
                border-collapse: collapse !important;
                font-size: 10pt !important;
            }
            th, td {
                border: 1px solid #eee !important;
                padding: 4px 8px !important;
            }
            
            /* Header Info */
            .print-header {
                display: block !important;
                margin-bottom: 20px;
                text-align: center;
                border-bottom: 2px solid #333;
                padding-bottom: 10px;
            }
        }
        .print-header { display: none; }
    </style>

    <!-- Print Header -->
    <div class="print-header">
        <h1 class="text-2xl font-bold text-gray-800">Laporan Penjualan - Bakso Malang</h1>
        <p class="text-gray-600">{{ Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ Carbon\Carbon::parse($endDate)->format('d M Y') }}</p>
    </div>

    <div class="flex items-center justify-between mb-6 no-print">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Analisa & Performa</h1>
            <p class="text-gray-500">Analisis penjualan dan performa produk</p>
        </div>
        <div class="flex items-center gap-3">
            @if(in_array($activeTab, ['products', 'categories', 'payments', 'service_areas']))
                <a href="{{ $this->getExportUrl() }}" class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors shadow-sm">
                    <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                    <span class="text-sm font-medium">Excel</span>
                </a>
            @endif

            <div class="flex items-center gap-1 bg-white border border-gray-200 rounded-lg p-1 shadow-sm">
                <div class="relative group">
                    <select x-model="printFormat" class="bg-transparent text-gray-700 text-sm font-medium border-0 focus:ring-0 cursor-pointer pl-3 pr-7 py-1.5 appearance-none hover:bg-gray-50 rounded-md transition-colors outline-none" title="Pilih Ukuran Kertas">
                        <option value="A4">Laporan (A4)</option>
                        <option value="A5">Laporan (A5)</option>
                        <option value="58mm">Struk (58mm)</option>
                        <option value="76mm">Struk (76mm)</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-400 group-hover:text-gray-600 transition-colors">
                        <i data-lucide="chevron-down" class="w-3 h-3"></i>
                    </div>
                </div>
                <div class="w-px h-5 bg-gray-200"></div>
                <a :href="'{!! route('print.sales-report', ['start' => $startDate, 'end' => $endDate]) !!}' + ('{!! route('print.sales-report', ['start' => $startDate, 'end' => $endDate]) !!}'.includes('?') ? '&' : '?') + 'format=' + printFormat" target="_blank" class="flex items-center justify-center gap-2 px-3 py-1.5 text-gray-700 hover:bg-gray-50 rounded-md transition-colors" title="Cetak Sekarang">
                    <i data-lucide="printer" class="w-4 h-4"></i>
                    <span class="text-sm font-medium">Cetak</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="mb-6 no-print">
        <div class="flex overflow-x-auto pb-2 md:pb-0 hide-scrollbar">
            <div class="flex p-1 bg-white rounded-xl shadow-sm border border-gray-100 w-fit min-w-max">
                <button wire:click="setTab('analysis')" 
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-all whitespace-nowrap {{ $activeTab === 'analysis' ? 'bg-primary-50 text-primary-600 shadow-sm' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}">
                    Ringkasan
                </button>
                <button wire:click="setTab('products')" 
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-all whitespace-nowrap {{ $activeTab === 'products' ? 'bg-primary-50 text-primary-600 shadow-sm' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}">
                    Detail Produk
                </button>
                <button wire:click="setTab('categories')" 
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-all whitespace-nowrap {{ $activeTab === 'categories' ? 'bg-primary-50 text-primary-600 shadow-sm' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}">
                    Kategori
                </button>
                <button wire:click="setTab('payments')" 
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-all whitespace-nowrap {{ $activeTab === 'payments' ? 'bg-primary-50 text-primary-600 shadow-sm' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}">
                    Metode Bayar
                </button>
                <button wire:click="setTab('service_areas')" 
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-all whitespace-nowrap {{ $activeTab === 'service_areas' ? 'bg-primary-50 text-primary-600 shadow-sm' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}">
                    Area Pelayanan
                </button>
            </div>
        </div>
    </div>
    <!-- Period Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6 p-4 print:hidden no-print">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            
            <!-- Period Types -->
            <div class="flex bg-gray-100 rounded-xl p-1 overflow-x-auto hide-scrollbar w-full md:w-auto">
                @foreach([
                    'daily' => 'Per Hari',
                    'weekly' => 'Per Minggu',
                    'monthly' => 'Per Bulan',
                    'yearly' => 'Per Tahun',
                ] as $key => $label)
                    <button 
                        wire:click="setPeriodType('{{ $key }}')"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-all whitespace-nowrap flex-1 md:flex-none text-center {{ $periodType === $key ? 'bg-white text-primary-600 shadow-sm' : 'text-gray-600 hover:text-gray-800' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <!-- Date Inputs & Reset -->
            <div class="flex flex-wrap items-center gap-4 w-full md:w-auto">
                @if($periodType == 'daily')
                    <!-- Alpine Flatpickr Date Range Picker Component -->
                    <div 
                        x-data="{
                            fp: null,
                            formatDate(d) {
                                return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
                            },
                            init() {
                                this.$nextTick(() => {
                                    if (typeof flatpickr === 'undefined') return;
                                    if (this.fp) { try { this.fp.destroy(); } catch(e){} }
                                    this.fp = flatpickr(this.$refs.hiddenInput, {
                                        mode: 'range',
                                        locale: 'id',
                                        dateFormat: 'Y-m-d',
                                        defaultDate: [@js($startDate), @js($endDate)],
                                        showMonths: 2,
                                        animate: true,
                                        positionElement: this.$refs.startCard,
                                        onReady: (selectedDates) => {
                                            if (selectedDates.length >= 1) this.$refs.startInput.value = this.formatDate(selectedDates[0]);
                                            if (selectedDates.length >= 2) this.$refs.endInput.value = this.formatDate(selectedDates[1]);
                                        },
                                        onChange: (selectedDates) => {
                                            if (selectedDates.length >= 1) this.$refs.startInput.value = this.formatDate(selectedDates[0]);
                                            if (selectedDates.length >= 2) {
                                                this.$refs.endInput.value = this.formatDate(selectedDates[1]);
                                                const startStr = new Date(selectedDates[0].getTime() - (selectedDates[0].getTimezoneOffset() * 60000)).toISOString().split('T')[0];
                                                const endStr = new Date(selectedDates[1].getTime() - (selectedDates[1].getTimezoneOffset() * 60000)).toISOString().split('T')[0];
                                                $wire.set('startDate', startStr);
                                                $wire.set('endDate', endStr);
                                                $wire.applyDateRange();
                                            }
                                        }
                                    });
                                });
                                $wire.on('reset-date-picker', (data) => {
                                    if (this.fp) {
                                        const start = data?.start ?? data?.[0]?.start;
                                        const end = data?.end ?? data?.[0]?.end;
                                        if (start && end) {
                                            this.fp.setDate([start, end], false);
                                            const startD = new Date(start + 'T00:00:00');
                                            const endD = new Date(end + 'T00:00:00');
                                            this.$refs.startInput.value = this.formatDate(startD);
                                            this.$refs.endInput.value = this.formatDate(endD);
                                        }
                                    }
                                });
                            }
                        }"
                        x-init="init()"
                        wire:key="date-picker-sales-daily"
                        wire:ignore
                        class="flex items-center gap-3"
                    >
                        <!-- Hidden flatpickr anchor -->
                        <input x-ref="hiddenInput" type="text" class="hidden">

                        <!-- Dari Card -->
                        <div x-ref="startCard" @click="fp && fp.open()" class="flex items-center gap-2 px-3 py-2 bg-gradient-to-r from-white to-gray-50 border border-gray-200 rounded-xl hover:border-primary-400 hover:shadow-md transition-all cursor-pointer group">
                            <i data-lucide="calendar" class="w-4 h-4 text-primary-600"></i>
                            <div class="flex flex-col">
                                <span class="text-[9px] text-gray-400 uppercase tracking-wide leading-none mb-0.5">Dari</span>
                                <input x-ref="startInput" type="text" class="bg-transparent border-none outline-none text-xs font-bold text-gray-700 cursor-pointer w-24 p-0" placeholder="Pilih" readonly>
                            </div>
                        </div>

                        <div class="text-gray-300">
                            <i data-lucide="arrow-right" class="w-3 h-3"></i>
                        </div>

                        <!-- Sampai Card -->
                        <div @click="fp && fp.open()" class="flex items-center gap-2 px-3 py-2 bg-gradient-to-r from-white to-gray-50 border border-gray-200 rounded-xl hover:border-primary-400 hover:shadow-md transition-all cursor-pointer group">
                            <i data-lucide="calendar" class="w-4 h-4 text-primary-600"></i>
                            <div class="flex flex-col">
                                <span class="text-[9px] text-gray-400 uppercase tracking-wide leading-none mb-0.5">Sampai</span>
                                <input x-ref="endInput" type="text" class="bg-transparent border-none outline-none text-xs font-bold text-gray-700 cursor-pointer w-24 p-0" placeholder="Pilih" readonly>
                            </div>
                        </div>
                    </div>
                @elseif($periodType === 'weekly')
                    <div class="flex items-center gap-2">
                        <select wire:model.live="selectedWeekYear" class="px-3 py-2 border border-gray-200 rounded-lg bg-white text-sm focus:ring-2 focus:ring-primary-100 focus:border-primary-400 outline-none">
                            @foreach($this->years as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </select>
                        <select wire:model.live="selectedWeek" class="px-3 py-2 border border-gray-200 rounded-lg bg-white text-sm focus:ring-2 focus:ring-primary-100 focus:border-primary-400 outline-none">
                            @foreach($this->weeks as $weekNum => $weekLabel)
                                <option value="{{ $weekNum }}">{{ $weekLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                @elseif($periodType === 'monthly')
                    <input type="month" wire:model.live="selectedMonth" class="px-3 py-2 border border-gray-200 rounded-lg bg-white text-sm focus:ring-2 focus:ring-primary-100 focus:border-primary-400 outline-none">
                @elseif($periodType === 'yearly')
                    <select wire:model.live="selectedYear" class="px-3 py-2 border border-gray-200 rounded-lg bg-white text-sm focus:ring-2 focus:ring-primary-100 focus:border-primary-400 outline-none">
                        @foreach($this->years as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                @endif

                <button 
                    wire:click="resetFilters" 
                    wire:loading.attr="disabled"
                    class="flex items-center gap-2 px-3 py-2 text-gray-500 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors"
                >
                    <i wire:loading.remove wire:target="resetFilters" data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    <i wire:loading wire:target="resetFilters" data-lucide="loader" class="w-4 h-4 animate-spin"></i>
                    <span class="text-sm font-medium">Reset</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Global Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-{{ $activeTab === 'analysis' ? '3' : '2' }} gap-6 mb-6">
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Total Penjualan</p>
                    <p class="text-2xl font-bold text-gray-800">
                        Rp {{ number_format($activeTab === 'products' ? ($productSummary['total_revenue'] ?? 0) : ($dailySummary['total_sales'] ?? 0), 0, ',', '.') }}
                    </p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                    <i data-lucide="banknote" class="w-6 h-6 text-green-600"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">
                        {{ $activeTab === 'products' ? 'Total Qty Terjual' : 'Jumlah Transaksi' }}
                    </p>
                    <p class="text-2xl font-bold text-gray-800">
                        {{ number_format($activeTab === 'products' ? ($productSummary['total_qty'] ?? 0) : ($dailySummary['completed_count'] ?? 0)) }}
                    </p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                    <i data-lucide="{{ $activeTab === 'products' ? 'package' : 'check-circle' }}" class="w-6 h-6 text-blue-600"></i>
                </div>
            </div>
        </div>
        
        <!-- Cards 3 only for Analysis -->
        @if($activeTab === 'analysis')
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
        @endif
    </div>

    @if($activeTab === 'analysis')
        <!-- ANALYSIS CONTENT: DASHBOARD 4 PANELS -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
             <!-- Top 10 Products -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="p-5 border-b"><h3 class="font-semibold text-gray-800">10 Produk Terlaris</h3></div>
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

            <!-- Top Categories -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="p-5 border-b"><h3 class="font-semibold text-gray-800">Kategori Terpopuler</h3></div>
                <div class="p-5 space-y-3">
                    @forelse($categoryReport as $index => $cat)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg {{ $index === 0 ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-600' }} flex items-center justify-center font-bold text-sm">
                                    {{ $index + 1 }}
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

            <!-- Top Payment Methods -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="p-5 border-b"><h3 class="font-semibold text-gray-800">Metode Bayar Terbanyak</h3></div>
                <div class="p-5 space-y-3">
                    @forelse($paymentReport as $index => $pay)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg {{ $index === 0 ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-600' }} flex items-center justify-center font-bold text-sm">
                                    {{ $index + 1 }}
                                </div>
                                <span class="font-medium text-gray-800">{{ ucfirst($pay->payment_name) }}</span>
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

            <!-- Peak Hours -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="p-5 border-b"><h3 class="font-semibold text-gray-800">Jam Ter-Sibuk</h3></div>
                <div class="p-5 space-y-3">
                    @forelse($peakHours as $index => $peak)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg {{ $index === 0 ? 'bg-orange-100 text-orange-600' : 'bg-gray-100 text-gray-600' }} flex items-center justify-center font-bold text-sm">
                                    {{ $index + 1 }}
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

    @elseif($activeTab === 'categories')
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="p-5 border-b flex justify-between items-center">
                <h3 class="font-semibold text-gray-800">Detail Penjualan per Kategori</h3>
            </div>
            <div class="p-6">
            <!-- Screen View (List) -->
            <div class="print:hidden">
                <div class="space-y-4">
                     @forelse($categoryReport as $index => $cat)
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-100 hover:border-blue-200 transition-colors">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center shadow-sm font-semibold text-gray-500">
                                    {{ $categoryReport->firstItem() + $index }}
                                </div>
                                <div>
                                    <span class="font-semibold text-gray-800 block">{{ $cat->category_name }}</span>
                                    <span class="text-sm text-gray-500">{{ $cat->total_quantity }} item terjual</span>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-bold text-gray-800">Rp {{ number_format($cat->total_sales, 0, ',', '.') }}</p>
                                <p class="text-xs text-gray-500">{{ $cat->transaction_count }} transaksi</p>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12">
                            <i data-lucide="folder-open" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
                            <p class="text-gray-500">Tidak ada data kategori untuk periode ini</p>
                        </div>
                    @endforelse
                </div>

                @if($categoryReport->hasPages())
                    <div class="mt-6 pt-4 border-t border-gray-100">
                        {{ $categoryReport->links(data: ['scrollTo' => false]) }}
                    </div>
                @endif
            </div>

            <!-- Print View (Table) -->
            <div class="hidden print:block">
                <table class="w-full">
                    <thead>
                        <tr>
                            <th class="text-left">No</th>
                            <th class="text-left">Kategori</th>
                            <th class="text-right">Qty Terjual</th>
                            <th class="text-right">Jumlah Transaksi</th>
                            <th class="text-right">Total Penjualan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categoryReport as $index => $cat)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $cat->category_name }}</td>
                                <td class="text-right">{{ $cat->total_quantity }}</td>
                                <td class="text-right">{{ $cat->transaction_count }}</td>
                                <td class="text-right">Rp {{ number_format($cat->total_sales, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            </div>
        </div>

    @elseif($activeTab === 'payments')
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
             <div class="p-5 border-b flex justify-between items-center">
                <h3 class="font-semibold text-gray-800">Detail Metode Pembayaran</h3>
            </div>
            <div class="p-6">
            <!-- Screen View (Cards) -->
            <div class="print:hidden">
                 <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                    @forelse($paymentReport as $index => $pay)
                        <div class="bg-white p-6 rounded-xl border border-gray-200 hover:shadow-md transition-shadow relative overflow-hidden">
                            <div class="absolute top-0 right-0 p-3 opacity-10 font-bold text-6xl text-gray-900 pointer-events-none">
                                {{ $paymentReport->firstItem() + $index }}
                            </div>
                            <div class="flex items-center gap-4 mb-4 relative z-10">
                                <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center">
                                    <i data-lucide="wallet" class="w-6 h-6 text-green-600"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-800">{{ ucfirst($pay->payment_name) }}</h4>
                                    <p class="text-sm text-gray-500">{{ $pay->transaction_count }} transaksi</p>
                                </div>
                            </div>
                            <div class="pt-4 border-t border-gray-100 relative z-10">
                                <p class="text-sm text-gray-500 mb-1">Total Masuk</p>
                                <p class="text-2xl font-bold text-gray-800">Rp {{ number_format($pay->total_sales, 0, ',', '.') }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full text-center py-12">
                            <i data-lucide="wallet" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
                            <p class="text-gray-500">Tidak ada data pembayaran</p>
                        </div>
                    @endforelse
                 </div>

                 @if($paymentReport->hasPages())
                    <div class="pt-4 border-t border-gray-100">
                        {{ $paymentReport->links(data: ['scrollTo' => false]) }}
                    </div>
                @endif
            </div>

            <!-- Print View (Table) -->
            <div class="hidden print:block">
                <table class="w-full">
                    <thead>
                        <tr>
                            <th class="text-left">No</th>
                            <th class="text-left">Metode Pembayaran</th>
                            <th class="text-right">Jumlah Transaksi</th>
                            <th class="text-right">Total Pendapatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($paymentReport as $index => $pay)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ ucfirst($pay->payment_name) }}</td>
                                <td class="text-right">{{ $pay->transaction_count }}</td>
                                <td class="text-right">Rp {{ number_format($pay->total_sales, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            </div>
        </div>

    @elseif($activeTab === 'service_areas')
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
             <div class="p-5 border-b flex justify-between items-center">
                <h3 class="font-semibold text-gray-800">Detail Area Pelayanan</h3>
            </div>
            <div class="p-6">
            <!-- Screen View (Cards) -->
            <div class="print:hidden">
                 <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                    @forelse($serviceAreaReport as $index => $area)
                        <div class="bg-white p-6 rounded-xl border border-gray-200 hover:shadow-md transition-shadow relative overflow-hidden">
                            <div class="absolute top-0 right-0 p-3 opacity-10 font-bold text-6xl text-gray-900 pointer-events-none">
                                {{ $serviceAreaReport->firstItem() + $index }}
                            </div>
                            <div class="flex items-center gap-4 mb-4 relative z-10">
                                <div class="w-12 h-12 bg-purple-50 rounded-xl flex items-center justify-center">
                                    <i data-lucide="map-pin" class="w-6 h-6 text-purple-600"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-800">{{ $area->area_name }}</h4>
                                    <p class="text-sm text-gray-500">{{ $area->transaction_count }} transaksi</p>
                                </div>
                            </div>
                            <div class="pt-4 border-t border-gray-100 relative z-10">
                                <p class="text-sm text-gray-500 mb-1">Total Pendapatan</p>
                                <p class="text-2xl font-bold text-gray-800">Rp {{ number_format($area->total_sales, 0, ',', '.') }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full text-center py-12">
                            <i data-lucide="map-pin" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
                            <p class="text-gray-500">Tidak ada data area pelayanan</p>
                        </div>
                    @endforelse
                 </div>

                 @if($serviceAreaReport->hasPages())
                    <div class="pt-4 border-t border-gray-100">
                        {{ $serviceAreaReport->links(data: ['scrollTo' => false]) }}
                    </div>
                @endif
            </div>

            <!-- Print View (Table) -->
            <div class="hidden print:block">
                <table class="w-full">
                    <thead>
                        <tr>
                            <th class="text-left">No</th>
                            <th class="text-left">Area Pelayanan</th>
                            <th class="text-right">Jumlah Transaksi</th>
                            <th class="text-right">Total Pendapatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($serviceAreaReport as $index => $area)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $area->area_name }}</td>
                                <td class="text-right">{{ $area->transaction_count }}</td>
                                <td class="text-right">Rp {{ number_format($area->total_sales, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            </div>
        </div>

    @elseif($activeTab === 'products')
        <!-- PRODUCTS CONTENT -->
        <div class="flex items-center gap-4 mb-4">
            <!-- Category Filter -->
            <select wire:model.live="categoryId" class="px-4 py-2 border border-gray-200 rounded-lg bg-white text-sm">
                <option value="">Semua Kategori</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </div>

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

            @if($productSales && $productSales->hasPages())
                <div class="p-4 border-t bg-gray-50">
                    {{ $productSales->links(data: ['scrollTo' => false]) }}
                </div>
            @endif
        </div>
    @endif
</div>
@script
<script>
    lucide.createIcons();
    Livewire.hook('morph.updated', () => queueMicrotask(() => lucide.createIcons()));

    // Format date helper
    function formatDate(date) {
        return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    // Global function for Alpine to call
    window.initDatePicker = function(startDateStr, endDateStr) {
        setTimeout(() => {
            const startInput = document.getElementById('dateRangeStart');
            const endInput = document.getElementById('dateRangeEnd');
            
            if (!startInput || !endInput) return;
            
            // Set initial values
            const initStart = new Date(startDateStr);
            const initEnd = new Date(endDateStr);
            // Check if valid dates
            if (!isNaN(initStart.getTime())) startInput.value = formatDate(initStart);
            if (!isNaN(initEnd.getTime())) endInput.value = formatDate(initEnd);
            
            // Remove any existing hidden picker
            const existingPicker = document.getElementById('hiddenDatePicker');
            if (existingPicker) {
                existingPicker._flatpickr?.destroy();
                existingPicker.remove();
            }
            
            // Create a completely hidden element for Flatpickr
            const hiddenPicker = document.createElement('input');
            hiddenPicker.id = 'hiddenDatePicker';
            hiddenPicker.style.cssText = 'position:absolute;visibility:hidden;width:0;height:0;overflow:hidden;';
            document.body.appendChild(hiddenPicker);
            
            // Create Flatpickr on hidden element
            const fp = flatpickr(hiddenPicker, {
                mode: 'range',
                locale: 'id',
                dateFormat: 'Y-m-d',
                defaultDate: [startDateStr, endDateStr],
                showMonths: 2,
                animate: true,
                positionElement: startInput,
                onChange: function(selectedDates, dateStr) {
                    if (selectedDates.length === 2) {
                        startInput.value = formatDate(selectedDates[0]);
                        endInput.value = formatDate(selectedDates[1]);
                        
                        // Use local timezone adjustment to prevent off-by-one errors when converting to string
                        const start = new Date(selectedDates[0].getTime() - (selectedDates[0].getTimezoneOffset() * 60000)).toISOString().split('T')[0];
                        const end = new Date(selectedDates[1].getTime() - (selectedDates[1].getTimezoneOffset() * 60000)).toISOString().split('T')[0];
                        
                        console.log('Date picked:', start, end);
                        
                        $wire.set('startDate', start);
                        $wire.set('endDate', end);
                        $wire.applyDateRange();
                    }
                }
            });
            
            // Make both inputs and containers open the picker
            const openPicker = (e) => { if (e) e.stopPropagation(); fp.open(); };
            startInput.onclick = openPicker;
            endInput.onclick = openPicker;
            const sCont = document.getElementById('startDateContainer');
            const eCont = document.getElementById('endDateContainer');
            if (sCont) sCont.onclick = openPicker;
            if (eCont) eCont.onclick = openPicker;
            
            // Store reference for reset
            window._datePicker = fp;
        }, 50);
    };

    // Listen for reset event
    $wire.on('reset-date-picker', (data) => {
        if (window._datePicker) {
            const newStart = new Date(data.start);
            const newEnd = new Date(data.end);
            const startInput = document.getElementById('dateRangeStart');
            const endInput = document.getElementById('dateRangeEnd');
            if (startInput) startInput.value = formatDate(newStart);
            if (endInput) endInput.value = formatDate(newEnd);
            window._datePicker.setDate([newStart, newEnd], false);
        }
    });
</script>
@endscript
