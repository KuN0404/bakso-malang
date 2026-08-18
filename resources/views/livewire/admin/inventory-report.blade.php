<div>
<div x-data="{ printFormat: 'A4' }" x-init="lucide.createIcons();" class="relative">
    <!-- Loading Overlay -->
    <div wire:loading wire:target="periodType, activeTab, startDate, endDate, search, selectedMonth, selectedMonthYear, selectedYear, selectedWeek, selectedWeekYear, filterToday, filterThisMonth" class="absolute inset-0 bg-white/50 backdrop-blur-[1px] z-50 rounded-xl">
        <div class="sticky top-[40vh] flex flex-col items-center justify-center w-full gap-2">
            <svg class="animate-spin h-8 w-8 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-sm font-medium text-primary-600">Memuat Laporan...</span>
        </div>
    </div>

    <!-- Header & Print Format Dropdown -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Laporan Inventori & Produksi</h1>
            <p class="text-gray-500">Analisa persediaan bahan baku, mutasi stok, pembelian, dan efisiensi produksi dapur</p>
        </div>
        
        <!-- Print Button & Format Selector -->
        <div class="flex items-center gap-2 bg-white border border-gray-200 rounded-xl p-1.5 shadow-sm">
            <div class="relative group">
                <select x-model="printFormat" wire:model.live="printFormat" class="bg-transparent text-gray-700 text-xs font-semibold border-0 focus:ring-0 cursor-pointer pl-3 pr-7 py-1.5 appearance-none hover:bg-gray-50 rounded-lg transition-colors outline-none">
                    <option value="A4">Laporan (A4)</option>
                    <option value="A5">Laporan (A5)</option>
                    <option value="58mm">Struk (58mm)</option>
                    <option value="76mm">Struk (76mm)</option>
                </select>
                <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-gray-400 absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none"></i>
            </div>
            <div class="w-px h-5 bg-gray-200"></div>
            <a :href="'{!! $this->getPrintUrl() !!}'" target="_blank" class="flex items-center gap-2 px-3.5 py-1.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors shadow-sm text-xs font-semibold">
                <i data-lucide="printer" class="w-4 h-4"></i>
                <span>Cetak Laporan</span>
            </a>
        </div>
    </div>

    <!-- Summary Cards (4 Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Card 1: Total Aset Bahan -->
        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center flex-shrink-0">
                <i data-lucide="boxes" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Nilai Aset Bahan Baku</p>
                <h3 class="text-xl font-bold text-gray-900 mt-0.5">Rp {{ number_format($totalAssetValue, 0, ',', '.') }}</h3>
                <p class="text-xs text-gray-500 mt-1">{{ $totalIngredients }} jenis bahan mentah</p>
            </div>
        </div>

        <!-- Card 2: Stok Kritis -->
        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center flex-shrink-0">
                <i data-lucide="alert-triangle" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Stok Kritis / Menipis</p>
                <h3 class="text-xl font-bold text-amber-600 mt-0.5">{{ $criticalStockCount }} Bahan</h3>
                <p class="text-xs text-gray-500 mt-1">Perlu restock segera</p>
            </div>
        </div>

        <!-- Card 3: Total Pembelian -->
        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center flex-shrink-0">
                <i data-lucide="shopping-cart" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Pembelian Stok</p>
                <h3 class="text-xl font-bold text-emerald-700 mt-0.5">Rp {{ number_format($totalPurchaseSum, 0, ',', '.') }}</h3>
                <p class="text-xs text-gray-500 mt-1">{{ $totalPurchaseCount }} transaksi pada periode ini</p>
            </div>
        </div>

        <!-- Card 4: Total Produksi -->
        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 bg-purple-50 text-purple-600 rounded-xl flex items-center justify-center flex-shrink-0">
                <i data-lucide="utensils-crossed" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Produksi Dapur</p>
                <h3 class="text-xl font-bold text-purple-700 mt-0.5">Rp {{ number_format($totalProductionCost, 0, ',', '.') }}</h3>
                <p class="text-xs text-gray-500 mt-1">{{ $totalProductionCount }} batch repacking terproses</p>
            </div>
        </div>
    </div>

    <!-- Filter Controls Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
        <div class="flex flex-col gap-4">
            <!-- Row 1: Period Tabs (Left) + Period-specific Date Picker / Dropdowns (Right) -->
            <div class="flex flex-wrap items-center justify-between gap-4">
                <!-- Left: Period Tabs -->
                <div class="flex bg-gray-100 rounded-xl p-1">
                    @foreach([
                        'daily' => 'Per Hari',
                        'weekly' => 'Per Minggu',
                        'monthly' => 'Per Bulan',
                        'yearly' => 'Per Tahun',
                    ] as $key => $label)
                        <button 
                            wire:click="$set('periodType', '{{ $key }}')"
                            class="px-4 py-2 rounded-lg text-xs font-bold transition-all {{ $periodType === $key ? 'bg-white text-primary-600 shadow-sm' : 'text-gray-600 hover:text-gray-800' }}"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                <!-- Right: Period Specific Controls -->
                @if($periodType === 'daily')
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
                        wire:key="date-picker-inventory-daily"
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
                    <div class="flex items-center gap-3">
                        <select wire:model.live="selectedWeek" class="px-3 py-2 border border-gray-200 rounded-xl bg-white text-xs font-semibold focus:ring-2 focus:ring-primary-500">
                            @foreach($this->weeks as $weekNum => $weekLabel)
                                <option value="{{ $weekNum }}">{{ $weekLabel }}</option>
                            @endforeach
                        </select>
                        <select wire:model.live="selectedWeekYear" class="px-3 py-2 border border-gray-200 rounded-xl bg-white text-xs font-semibold focus:ring-2 focus:ring-primary-500">
                            @foreach($this->years as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                @elseif($periodType === 'monthly')
                    <div class="flex items-center gap-3">
                        <select wire:model.live="selectedMonth" class="px-3 py-2 border border-gray-200 rounded-xl bg-white text-xs font-semibold focus:ring-2 focus:ring-primary-500">
                            @foreach($months as $num => $name)
                                <option value="{{ $num }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        <select wire:model.live="selectedMonthYear" class="px-3 py-2 border border-gray-200 rounded-xl bg-white text-xs font-semibold focus:ring-2 focus:ring-primary-500">
                            @foreach($this->years as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                @elseif($periodType === 'yearly')
                    <div class="flex items-center gap-3">
                        <select wire:model.live="selectedYear" class="px-3 py-2 border border-gray-200 rounded-xl bg-white text-xs font-semibold focus:ring-2 focus:ring-primary-500">
                            @foreach($this->years as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>

            <!-- Row 2: Quick Filters & Search -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 border-t border-gray-100 pt-3">
                <div class="flex items-center gap-2">
                    <button wire:click="filterToday" class="px-3.5 py-1.5 text-xs font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors">Hari Ini</button>
                    <button wire:click="filterThisMonth" class="px-3.5 py-1.5 text-xs font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors">Bulan Ini</button>
                </div>
                <div class="relative w-full sm:w-72">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari data laporan..." class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg text-xs focus:ring-2 focus:ring-primary-500">
                    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="border-b border-gray-200 mb-6">
        <nav class="flex gap-6 overflow-x-auto">
            <button 
                wire:click="$set('activeTab', 'valuation')"
                class="pb-3 text-xs font-bold uppercase tracking-wide whitespace-nowrap border-b-2 transition-colors {{ $activeTab === 'valuation' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}"
            >
                1. Valuasi Bahan Baku
            </button>
            <button 
                wire:click="$set('activeTab', 'components')"
                class="pb-3 text-xs font-bold uppercase tracking-wide whitespace-nowrap border-b-2 transition-colors {{ $activeTab === 'components' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}"
            >
                2. Valuasi Komponen
            </button>
            <button 
                wire:click="$set('activeTab', 'purchases')"
                class="pb-3 text-xs font-bold uppercase tracking-wide whitespace-nowrap border-b-2 transition-colors {{ $activeTab === 'purchases' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}"
            >
                3. Pembelian Stok
            </button>
            <button 
                wire:click="$set('activeTab', 'productions')"
                class="pb-3 text-xs font-bold uppercase tracking-wide whitespace-nowrap border-b-2 transition-colors {{ $activeTab === 'productions' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}"
            >
                4. Repacking / Produksi
            </button>
            <button 
                wire:click="$set('activeTab', 'stock_logs')"
                class="pb-3 text-xs font-bold uppercase tracking-wide whitespace-nowrap border-b-2 transition-colors {{ $activeTab === 'stock_logs' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}"
            >
                5. Mutasi Bahan Baku
            </button>
            <button
                wire:click="$set('activeTab', 'component_stock_logs')"
                class="pb-3 text-xs font-bold uppercase tracking-wide whitespace-nowrap border-b-2 transition-colors {{ $activeTab === 'component_stock_logs' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}"
            >
                6. Mutasi Komponen
            </button>
            <button
                wire:click="$set('activeTab', 'low_stock')"
                class="pb-3 text-xs font-bold uppercase tracking-wide whitespace-nowrap border-b-2 transition-colors {{ $activeTab === 'low_stock' ? 'border-red-600 text-red-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}"
            >
                7. Stok Menipis
            </button>
        </nav>
    </div>

    <!-- TAB 1: VALUASI ASET BAHAN BAKU -->
    @if($activeTab === 'valuation')
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
        <table class="w-full text-left text-sm border-collapse">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 font-semibold text-gray-500 uppercase text-xs">Kode</th>
                    <th class="px-6 py-3 font-semibold text-gray-500 uppercase text-xs">Bahan Baku</th>
                    <th class="px-6 py-3 font-semibold text-gray-500 uppercase text-xs">Stok</th>
                    <th class="px-6 py-3 font-semibold text-gray-500 uppercase text-xs">Stok Min.</th>
                    <th class="px-6 py-3 font-semibold text-gray-500 uppercase text-xs">HPP Per Satuan</th>
                    <th class="px-6 py-3 font-semibold text-gray-500 uppercase text-xs">Total Nilai Aset</th>
                    <th class="px-6 py-3 text-center font-semibold text-gray-500 uppercase text-xs">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($valuationData as $ing)
                    @php $assetVal = $ing->stock * $ing->cost_price; @endphp
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 font-mono text-xs text-gray-500">{{ $ing->code }}</td>
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $ing->name }}</td>
                        <td class="px-6 py-4 font-bold">
                            <span class="{{ $ing->stock <= $ing->minimum_stock ? 'text-red-600 bg-red-50 px-2 py-0.5 rounded' : 'text-gray-800' }}">
                                {{ number_format($ing->stock, 2, ',', '.') }} {{ $ing->unit?->symbol }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-500">{{ number_format($ing->minimum_stock, 2, ',', '.') }} {{ $ing->unit?->symbol }}</td>
                        <td class="px-6 py-4">Rp {{ number_format($ing->cost_price, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 font-extrabold text-primary-700">Rp {{ number_format($assetVal, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-center">
                            @if($ing->stock <= $ing->minimum_stock)
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">Menipis</span>
                            @else
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">Aman</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">Tidak ada data bahan baku.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($valuationData && $valuationData->count() > 0)
            <div class="px-6 py-4 border-t border-gray-100">
                {{ $valuationData->links() }}
            </div>
        @endif
    </div>
    @endif

    <!-- TAB 2: VALUASI KOMPONEN -->
    @if($activeTab === 'components')
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
        <table class="w-full text-left text-sm border-collapse">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 font-semibold text-gray-500 uppercase text-xs">Kode</th>
                    <th class="px-6 py-3 font-semibold text-gray-500 uppercase text-xs">Komponen</th>
                    <th class="px-6 py-3 font-semibold text-gray-500 uppercase text-xs">Stok</th>
                    <th class="px-6 py-3 font-semibold text-gray-500 uppercase text-xs">Stok Min.</th>
                    <th class="px-6 py-3 font-semibold text-gray-500 uppercase text-xs">HPP Per Satuan</th>
                    <th class="px-6 py-3 font-semibold text-gray-500 uppercase text-xs">Total Nilai Aset</th>
                    <th class="px-6 py-3 text-center font-semibold text-gray-500 uppercase text-xs">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($componentsData as $comp)
                    @php
                        $assetVal = $comp->stock * $comp->cost_price;
                        $status = $comp->getStockStatus();
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors {{ $status === 'out' ? 'bg-red-50/40' : ($status === 'low' ? 'bg-yellow-50/40' : '') }}">
                        <td class="px-6 py-4 font-mono text-xs text-blue-700 font-semibold">{{ $comp->code }}</td>
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $comp->name }}</td>
                        <td class="px-6 py-4 font-bold {{ $status === 'out' ? 'text-red-600' : ($status === 'low' ? 'text-yellow-700' : 'text-gray-800') }}">
                            {{ number_format($comp->stock, 2, ',', '.') }} {{ $comp->unit?->symbol }}
                        </td>
                        <td class="px-6 py-4 text-gray-500">{{ number_format($comp->minimum_stock, 2, ',', '.') }} {{ $comp->unit?->symbol }}</td>
                        <td class="px-6 py-4">Rp {{ number_format($comp->cost_price, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 font-extrabold text-blue-700">Rp {{ number_format($assetVal, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-center">
                            @if($status === 'out')
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">Habis</span>
                            @elseif($status === 'low')
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700">Menipis</span>
                            @else
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">Aman</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">Tidak ada data komponen setengah jadi.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($componentsData && $componentsData->count() > 0)
            <div class="px-6 py-4 border-t border-gray-100">
                {{ $componentsData->links() }}
            </div>
        @endif
    </div>
    @endif

    <!-- TAB 2: LAPORAN PEMBELIAN STOK -->
    @if($activeTab === 'purchases')
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
        <table class="w-full text-left text-sm border-collapse">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 font-semibold text-gray-500 uppercase text-xs">No. Faktur</th>
                    <th class="px-6 py-3 font-semibold text-gray-500 uppercase text-xs">Tanggal</th>
                    <th class="px-6 py-3 font-semibold text-gray-500 uppercase text-xs">Supplier</th>
                    <th class="px-6 py-3 font-semibold text-gray-500 uppercase text-xs">Rincian Item</th>
                    <th class="px-6 py-3 font-semibold text-gray-500 uppercase text-xs">Total Nominal</th>
                    <th class="px-6 py-3 font-semibold text-gray-500 uppercase text-xs">Petugas</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($purchasesData as $p)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 font-mono text-xs font-semibold text-primary-600">{{ $p->invoice_number }}</td>
                        <td class="px-6 py-4 text-gray-700">{{ $p->purchase_date->format('d/m/Y') }}</td>
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $p->supplier?->name ?: '-' }}</td>
                        <td class="px-6 py-4 text-xs text-gray-600">
                            @foreach($p->items as $item)
                                <div>- {{ $item->item_type === 'ingredient' ? $item->ingredient?->name : $item->product?->name }} ({{ number_format($item->quantity, 2, ',', '.') }} {{ $item->item_type === 'ingredient' ? $item->ingredient?->unit?->symbol : 'pcs' }}) @ Rp {{ number_format($item->unit_price, 0, ',', '.') }}</div>
                            @endforeach
                        </td>
                        <td class="px-6 py-4 font-bold text-gray-900">Rp {{ number_format($p->total_amount, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-xs text-gray-500">{{ $p->user?->name ?? 'System' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">Tidak ada riwayat pembelian pada periode ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($purchasesData && $purchasesData->count() > 0)
            <div class="px-6 py-4 border-t border-gray-100">
                {{ $purchasesData->links() }}
            </div>
        @endif
    </div>
    @endif

    <!-- TAB 3: LAPORAN REPACKING / PRODUKSI -->
    @if($activeTab === 'productions')
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
        <table class="w-full text-left text-sm border-collapse">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 font-semibold text-gray-500 uppercase text-xs">Kode Batch</th>
                    <th class="px-6 py-3 font-semibold text-gray-500 uppercase text-xs">Tanggal</th>
                    <th class="px-6 py-3 font-semibold text-gray-500 uppercase text-xs">Bahan Baku Terpakai</th>
                    <th class="px-6 py-3 font-semibold text-gray-500 uppercase text-xs">Hasil Produk Jadi</th>
                    <th class="px-6 py-3 font-semibold text-gray-500 uppercase text-xs">Total Biaya Dapur</th>
                    <th class="px-6 py-3 font-semibold text-gray-500 uppercase text-xs">Petugas</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($productionsData as $p)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 font-mono text-xs font-semibold text-purple-700">{{ $p->production_code }}</td>
                        <td class="px-6 py-4 text-gray-700">{{ $p->production_date->format('d/m/Y') }}</td>
                        <td class="px-6 py-4 text-xs text-gray-600">
                            @foreach($p->inputs as $in)
                                <div>- {{ $in->ingredient?->name }}: {{ number_format($in->quantity, 2, ',', '.') }} {{ $in->ingredient?->unit?->symbol }} (Rp {{ number_format($in->subtotal, 0, ',', '.') }})</div>
                            @endforeach
                        </td>
                        <td class="px-6 py-4 text-xs text-gray-900 font-medium">
                            @foreach($p->outputs as $out)
                                <div class="text-emerald-700 font-semibold">+ {{ $out->getOutputName() }}: {{ number_format($out->quantity, 0) }} {{ $out->getOutputUnit() }} (HPP: Rp {{ number_format($out->unit_cost, 0, ',', '.') }})</div>
                            @endforeach
                        </td>
                        <td class="px-6 py-4 font-bold text-gray-900">Rp {{ number_format($p->total_cost, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-xs text-gray-500">{{ $p->user?->name ?? 'System' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">Tidak ada riwayat produksi dapur pada periode ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($productionsData && $productionsData->count() > 0)
            <div class="px-6 py-4 border-t border-gray-100">
                {{ $productionsData->links() }}
            </div>
        @endif
    </div>
    @endif

    <!-- TAB 4: MUTASI & HISTORY STOK -->
    @if($activeTab === 'stock_logs')
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
        <table class="w-full text-left text-sm border-collapse">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 font-semibold text-gray-500 uppercase text-xs">Waktu Log</th>
                    <th class="px-6 py-3 font-semibold text-gray-500 uppercase text-xs">Bahan Baku</th>
                    <th class="px-6 py-3 font-semibold text-gray-500 uppercase text-xs">Aksi Mutasi</th>
                    <th class="px-6 py-3 font-semibold text-gray-500 uppercase text-xs">Perubahan Qty</th>
                    <th class="px-6 py-3 font-semibold text-gray-500 uppercase text-xs">Stok Akhir</th>
                    <th class="px-6 py-3 font-semibold text-gray-500 uppercase text-xs">Catatan</th>
                    <th class="px-6 py-3 font-semibold text-gray-500 uppercase text-xs">Petugas</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($stockLogsData as $log)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 text-xs text-gray-500">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $log->ingredient?->name ?? 'Bahan Telah Dihapus' }}</td>
                        <td class="px-6 py-4">
                            @if($log->type === 'purchase' || $log->type === 'initial')
                                <span class="px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800">Pembelian / Awal</span>
                            @elseif($log->type === 'production_use')
                                <span class="px-2 py-0.5 rounded text-xs font-semibold bg-purple-100 text-purple-800">Dipakai Produksi</span>
                            @else
                                <span class="px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800">Koreksi Stok</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 font-bold {{ $log->type === 'production_use' || $log->type === 'sub' ? 'text-red-600' : 'text-emerald-600' }}">
                            {{ $log->type === 'production_use' || $log->type === 'sub' ? '-' : '+' }}{{ number_format($log->amount, 2, ',', '.') }} {{ $log->ingredient?->unit?->symbol }}
                        </td>
                        <td class="px-6 py-4 font-semibold text-gray-800">{{ number_format($log->final_stock, 2, ',', '.') }} {{ $log->ingredient?->unit?->symbol }}</td>
                        <td class="px-6 py-4 text-xs text-gray-500">{{ $log->note ?: '-' }}</td>
                        <td class="px-6 py-4 text-xs text-gray-500">{{ $log->user?->name ?? 'System' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">Tidak ada log mutasi stok pada periode ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($stockLogsData && $stockLogsData->count() > 0)
            <div class="px-6 py-4 border-t border-gray-100">
                {{ $stockLogsData->links() }}
            </div>
        @endif
    </div>
    @endif

    <!-- TAB 6: MUTASI STOK KOMPONEN -->
    @if($activeTab === 'component_stock_logs')
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
        <table class="w-full text-left text-sm border-collapse">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 font-semibold text-gray-500 uppercase text-xs">Waktu Log</th>
                    <th class="px-6 py-3 font-semibold text-gray-500 uppercase text-xs">Komponen</th>
                    <th class="px-6 py-3 font-semibold text-gray-500 uppercase text-xs">Tipe Mutasi</th>
                    <th class="px-6 py-3 font-semibold text-gray-500 uppercase text-xs">Jumlah Perubahan</th>
                    <th class="px-6 py-3 font-semibold text-gray-500 uppercase text-xs">Stok Akhir</th>
                    <th class="px-6 py-3 font-semibold text-gray-500 uppercase text-xs">Catatan</th>
                    <th class="px-6 py-3 font-semibold text-gray-500 uppercase text-xs">Petugas</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($componentStockLogsData as $cLog)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 text-xs text-gray-500">{{ $cLog->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $cLog->component?->name ?? 'Komponen Dihapus' }}</td>
                        <td class="px-6 py-4">
                            @if($cLog->type === 'production_add')
                                <span class="px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800">Hasil Repacking</span>
                            @elseif($cLog->type === 'bom_deduct')
                                <span class="px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800">Penjualan BOM POS</span>
                            @elseif($cLog->type === 'modifier_deduct')
                                <span class="px-2 py-0.5 rounded text-xs font-semibold bg-purple-100 text-purple-800">Modifier POS</span>
                            @else
                                <span class="px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800">Penyesuaian Manual</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 font-bold {{ $cLog->amount < 0 ? 'text-red-600' : 'text-emerald-600' }}">
                            {{ $cLog->amount > 0 ? '+' : '' }}{{ number_format($cLog->amount, 2, ',', '.') }} {{ $cLog->component?->unit?->symbol }}
                        </td>
                        <td class="px-6 py-4 font-semibold text-gray-800">{{ number_format($cLog->final_stock, 2, ',', '.') }} {{ $cLog->component?->unit?->symbol }}</td>
                        <td class="px-6 py-4 text-xs text-gray-500">{{ $cLog->note ?: '-' }}</td>
                        <td class="px-6 py-4 text-xs text-gray-500">{{ $cLog->user?->name ?? 'System' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">Tidak ada log mutasi stok komponen pada periode ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($componentStockLogsData && $componentStockLogsData->count() > 0)
            <div class="px-6 py-4 border-t border-gray-100">
                {{ $componentStockLogsData->links() }}
            </div>
        @endif
    </div>
    @endif

    <!-- TAB 7: STOK MENIPIS -->
    @if($activeTab === 'low_stock')
    <div class="space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
            <div class="px-6 py-3 border-b border-gray-100 bg-gray-50">
                <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Bahan Baku Menipis / Habis</h3>
            </div>
            <table class="w-full text-left text-sm border-collapse">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 font-semibold text-gray-500 uppercase text-xs">Kode</th>
                        <th class="px-6 py-3 font-semibold text-gray-500 uppercase text-xs">Bahan Baku</th>
                        <th class="px-6 py-3 font-semibold text-gray-500 uppercase text-xs">Stok</th>
                        <th class="px-6 py-3 font-semibold text-gray-500 uppercase text-xs">Stok Min.</th>
                        <th class="px-6 py-3 text-center font-semibold text-gray-500 uppercase text-xs">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($lowStockIngredients as $ing)
                        @php $status = $ing->getStockStatus(); @endphp
                        <tr class="hover:bg-gray-50 transition-colors {{ $status === 'out' ? 'bg-red-50/40' : 'bg-yellow-50/40' }}">
                            <td class="px-6 py-4 font-mono text-xs text-gray-500">{{ $ing->code }}</td>
                            <td class="px-6 py-4 font-medium text-gray-900">{{ $ing->name }}</td>
                            <td class="px-6 py-4 font-bold {{ $status === 'out' ? 'text-red-600' : 'text-yellow-700' }}">
                                {{ number_format($ing->stock, 2, ',', '.') }} {{ $ing->unit?->symbol }}
                            </td>
                            <td class="px-6 py-4 text-gray-500">{{ number_format($ing->minimum_stock, 2, ',', '.') }} {{ $ing->unit?->symbol }}</td>
                            <td class="px-6 py-4 text-center">
                                @if($status === 'out')
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">Habis</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700">Menipis</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">Tidak ada bahan baku yang menipis / habis</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
            <div class="px-6 py-3 border-b border-gray-100 bg-gray-50">
                <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Komponen Menipis / Habis</h3>
            </div>
            <table class="w-full text-left text-sm border-collapse">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 font-semibold text-gray-500 uppercase text-xs">Kode</th>
                        <th class="px-6 py-3 font-semibold text-gray-500 uppercase text-xs">Komponen</th>
                        <th class="px-6 py-3 font-semibold text-gray-500 uppercase text-xs">Stok</th>
                        <th class="px-6 py-3 font-semibold text-gray-500 uppercase text-xs">Stok Min.</th>
                        <th class="px-6 py-3 text-center font-semibold text-gray-500 uppercase text-xs">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($lowStockComponents as $comp)
                        @php $status = $comp->getStockStatus(); @endphp
                        <tr class="hover:bg-gray-50 transition-colors {{ $status === 'out' ? 'bg-red-50/40' : 'bg-yellow-50/40' }}">
                            <td class="px-6 py-4 font-mono text-xs text-blue-700 font-semibold">{{ $comp->code }}</td>
                            <td class="px-6 py-4 font-medium text-gray-900">{{ $comp->name }}</td>
                            <td class="px-6 py-4 font-bold {{ $status === 'out' ? 'text-red-600' : 'text-yellow-700' }}">
                                {{ number_format($comp->stock, 2, ',', '.') }} {{ $comp->unit?->symbol }}
                            </td>
                            <td class="px-6 py-4 text-gray-500">{{ number_format($comp->minimum_stock, 2, ',', '.') }} {{ $comp->unit?->symbol }}</td>
                            <td class="px-6 py-4 text-center">
                                @if($status === 'out')
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">Habis</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700">Menipis</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">Tidak ada komponen yang menipis / habis</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

@script
<script>
    lucide.createIcons();
</script>
@endscript
</div>
