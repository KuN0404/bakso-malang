<div>
<div x-data="{ printFormat: 'A4' }" x-init="lucide.createIcons();" class="relative">
    <!-- Loading Overlay -->
    <div wire:loading wire:target="search, filterCashier, periodType, selectedWeek, selectedWeekYear, selectedMonth, selectedMonthYear, selectedYear, applyDateRange, resetFilters, gotoPage, previousPage, nextPage" class="absolute inset-0 bg-white/50 backdrop-blur-[1px] z-50 rounded-xl">
        <div class="sticky top-[40vh] flex flex-col items-center justify-center w-full gap-2">
            <svg class="animate-spin h-8 w-8 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-sm font-medium text-primary-600">Memuat Transaksi...</span>
        </div>
    </div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Laporan Penjualan</h1>
            <p class="text-gray-500">Daftar lengkap riwayat transaksi penjualan</p>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
        <div class="bg-white rounded-xl p-4 border border-gray-100">
            <p class="text-xs text-gray-500">Total Transaksi</p>
            <p class="text-xl font-bold text-gray-800">{{ number_format($summary['total_transactions']) }}</p>
        </div>
        <div class="bg-white rounded-xl p-4 border border-gray-100">
            <p class="text-xs text-gray-500">Total Pendapatan</p>
            <p class="text-xl font-bold text-green-600">Rp {{ number_format($summary['total_revenue'], 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-xl p-4 border border-purple-100 bg-purple-50/30">
            <div class="flex items-center gap-1.5 text-xs text-purple-700 font-semibold mb-0.5">
                <i data-lucide="utensils" class="w-3.5 h-3.5"></i>
                Makan di Tempat
            </div>
            <p class="text-lg font-bold text-purple-900">{{ number_format($summary['dine_in_count']) }} <span class="text-xs text-purple-600 font-normal">(Rp {{ number_format($summary['dine_in_revenue'], 0, ',', '.') }})</span></p>
        </div>
        <div class="bg-white rounded-xl p-4 border border-amber-100 bg-amber-50/30">
            <div class="flex items-center gap-1.5 text-xs text-amber-700 font-semibold mb-0.5">
                <i data-lucide="shopping-bag" class="w-3.5 h-3.5"></i>
                Bawa Pulang
            </div>
            <p class="text-lg font-bold text-amber-900">{{ number_format($summary['take_away_count']) }} <span class="text-xs text-amber-600 font-normal">(Rp {{ number_format($summary['take_away_revenue'], 0, ',', '.') }})</span></p>
        </div>
        <div class="bg-white rounded-xl p-4 border border-gray-100">
            <p class="text-xs text-gray-500">Rata-rata / Trx</p>
            <p class="text-xl font-bold text-blue-600">Rp {{ $summary['total_transactions'] > 0 ? number_format($summary['total_revenue'] / $summary['total_transactions'], 0, ',', '.') : 0 }}</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-4">
        <div class="flex flex-col gap-4">
            <!-- Row 1: Period Tabs (Left) + Period-specific Filters (Right) -->
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
                            class="px-4 py-2 rounded-lg text-sm font-medium transition-all {{ $periodType === $key ? 'bg-white text-primary-600 shadow-sm' : 'text-gray-600 hover:text-gray-800' }}"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                <!-- Right: Period-specific Filters -->
                @if($periodType === 'daily')
                    <!-- Date Range Picker -->
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
                        wire:key="date-picker-trx-daily"
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
                        <select wire:model.live="selectedWeek" class="px-4 py-2 border border-gray-200 rounded-xl bg-white text-sm font-medium focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none transition-all">
                            @foreach($this->weeks as $weekNum => $weekLabel)
                                <option value="{{ $weekNum }}">{{ $weekLabel }}</option>
                            @endforeach
                        </select>
                        <select wire:model.live="selectedWeekYear" class="px-4 py-2 border border-gray-200 rounded-xl bg-white text-sm font-medium focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none transition-all">
                            @foreach($this->years as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                @elseif($periodType === 'monthly')
                    <div class="flex items-center gap-3">
                        <select wire:model.live="selectedMonth" class="px-4 py-2 border border-gray-200 rounded-xl bg-white text-sm font-medium focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none transition-all">
                            @foreach($months as $num => $name)
                                <option value="{{ $num }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        <select wire:model.live="selectedMonthYear" class="px-4 py-2 border border-gray-200 rounded-xl bg-white text-sm font-medium focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none transition-all">
                            @foreach($this->years as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                @elseif($periodType === 'yearly')
                    <div class="flex items-center gap-3">
                        <select wire:model.live="selectedYear" class="px-4 py-2 border border-gray-200 rounded-xl bg-white text-sm font-medium focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none transition-all">
                            @foreach($this->years as $year)
                                <option value="{{ $year }}">Tahun {{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>

            <!-- Row 2: Print (Left) + Excel & Reset (Right) -->
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 pt-4">
                <!-- Left: Print Group -->
                <div class="flex items-center gap-1 bg-gray-900 rounded-xl p-1 shadow-sm">
                    <div class="relative group">
                        <select x-model="printFormat" class="bg-transparent text-white text-xs font-medium border-0 focus:ring-0 cursor-pointer pl-3 pr-6 py-1.5 appearance-none hover:bg-gray-800 rounded-lg transition-colors outline-none" title="Pilih Ukuran Kertas">
                            <option value="A4" class="text-gray-900 bg-white">Laporan (A4)</option>
                            <option value="A5" class="text-gray-900 bg-white">Laporan (A5)</option>
                            <option value="58mm" class="text-gray-900 bg-white">Struk (58mm)</option>
                            <option value="76mm" class="text-gray-900 bg-white">Struk (76mm)</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-1 text-white/50 group-hover:text-white transition-colors">
                            <i data-lucide="chevron-down" class="w-3 h-3"></i>
                        </div>
                    </div>
                    <div class="w-px h-4 bg-gray-700"></div>
                    <a :href="'{!! $this->getPrintTableUrl() !!}' + ('{!! $this->getPrintTableUrl() !!}'.includes('?') ? '&' : '?') + 'format=' + printFormat" target="_blank" class="flex items-center justify-center gap-1.5 px-3 py-1.5 text-white hover:bg-gray-800 rounded-lg transition-colors" title="Cetak Tabel">
                        <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                        <span class="text-xs font-medium">Cetak</span>
                    </a>
                    <div class="w-px h-4 bg-gray-700"></div>
                    <a :href="'{!! $this->getPrintDetailUrl() !!}' + ('{!! $this->getPrintDetailUrl() !!}'.includes('?') ? '&' : '?') + 'format=' + printFormat" target="_blank" class="flex items-center justify-center gap-1.5 px-3 py-1.5 text-white hover:bg-gray-800 rounded-lg transition-colors" title="Cetak Detail">
                        <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                        <span class="text-xs font-medium">Detail</span>
                    </a>
                </div>

                <!-- Right: Excel & Reset -->
                <div class="flex items-center gap-2">
                    <!-- Excel Group (merged) -->
                    <div class="flex items-center gap-1 bg-green-50 rounded-xl p-1 border border-green-200">
                        <a href="{{ $this->getExportUrl() }}" class="flex items-center gap-1.5 px-3 py-1.5 text-green-600 hover:bg-green-100 rounded-lg transition-colors" title="Excel Ringkasan">
                            <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5"></i>
                            <span class="text-xs font-medium">Excel</span>
                        </a>
                        <div class="w-px h-4 bg-green-300"></div>
                        <a href="{{ $this->getExportDetailUrl() }}" class="flex items-center gap-1.5 px-3 py-1.5 text-green-600 hover:bg-green-100 rounded-lg transition-colors" title="Excel Detail">
                            <i data-lucide="download" class="w-3.5 h-3.5"></i>
                            <span class="text-xs font-medium">Detail</span>
                        </a>
                    </div>

                    <!-- Reset Button -->
                    <button wire:click="resetFilters" class="flex items-center gap-1.5 px-3 py-2 bg-red-50 hover:bg-red-100 text-red-600 hover:text-red-700 rounded-lg transition-colors border border-red-200" title="Reset Filter">
                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                        <span class="text-xs font-medium">Reset</span>
                    </button>
                </div>
            </div>

            <!-- Row 3: Search & Cashier Filter -->
            <div class="flex flex-wrap items-center gap-4 border-t border-gray-100 pt-4">
                <div class="relative flex-1 min-w-[200px]">
                    <input 
                        type="text" 
                        wire:model.live.debounce.300ms="search" 
                        placeholder="Cari invoice..." 
                        class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none transition-all"
                    >
                    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
                </div>
                <!-- Order Type Filter -->
                <select wire:model.live="filterOrderType" class="px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none transition-all">
                    <option value="">Semua Tipe Order</option>
                    <option value="dine_in">Makan di Tempat</option>
                    <option value="take_away">Bawa Pulang</option>
                </select>
                <!-- Cashier Filter -->
                <select wire:model.live="filterCashier" class="px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none transition-all">
                    <option value="">Semua Kasir</option>
                    @foreach($cashiers as $cashier)
                        <option value="{{ $cashier->id }}">{{ $cashier->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden relative">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Invoice</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Waktu</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Kasir</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tipe Order</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Pembayaran</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Total</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Cetak</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($transactions as $transaction)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                {{ $transaction->invoice_number }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                <div>{{ $transaction->created_at->format('d/m/Y') }}</div>
                                <div class="text-xs text-gray-500">{{ $transaction->created_at->format('H:i') }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                {{ $transaction->user->name ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                @if($transaction->order_type === 'dine_in')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-xs font-bold bg-purple-100 text-purple-800">
                                        <i data-lucide="utensils" class="w-3 h-3"></i>
                                        Makan di Tempat
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-xs font-bold bg-amber-100 text-amber-800">
                                        <i data-lucide="shopping-bag" class="w-3 h-3"></i>
                                        Bawa Pulang
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <span class="px-2 py-1 rounded-lg text-xs font-bold {{ 
                                    $transaction->payment_method === 'cash' ? 'bg-green-100 text-green-700' : 
                                    ($transaction->payment_method === 'qris' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700') 
                                }}">
                                    {{ strtoupper($transaction->paymentSource->name ?? ($transaction->payment_method === 'cash' ? 'Tunai' : $transaction->payment_method)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900 text-right">
                                Rp {{ number_format($transaction->total, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center justify-center min-w-[24px] px-2 py-0.5 text-xs font-medium {{ $transaction->print_count > 0 ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500' }} rounded-full">
                                    {{ $transaction->print_count ?? 0 }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <a :href="'{{ route('print.transaction.single', $transaction->id) }}' + '?format=' + printFormat" 
                                       target="_blank"
                                       @click="setTimeout(() => $wire.$refresh(), 1500)"
                                       class="p-2 text-gray-400 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-colors"
                                       title="Cetak Struk">
                                        <i data-lucide="printer" class="w-4 h-4"></i>
                                    </a>
                                    <button wire:click="showDetail({{ $transaction->id }})" 
                                            class="p-2 text-gray-400 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-colors"
                                            title="Lihat Detail">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                                Tidak ada transaksi ditemukan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-100">
            {{ $transactions->links() }}
        </div>
    </div>

    <!-- Detail Modal -->
    @if($showDetailModal && $selectedTransaction)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" wire:click="closeDetailModal"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                        Detail Transaksi #{{ $selectedTransaction->invoice_number }}
                                    </h3>
                                    <button wire:click="closeDetailModal" class="text-gray-400 hover:text-gray-500">
                                        <i data-lucide="x" class="w-5 h-5"></i>
                                    </button>
                                </div>
                                
                                <div class="mt-4 border-t border-gray-100 pt-4">
                                    <div class="grid grid-cols-2 gap-4 mb-4">
                                        <div>
                                            <p class="text-xs text-gray-500">Waktu</p>
                                            <p class="text-sm font-medium">{{ $selectedTransaction->created_at->format('d/m/Y H:i') }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500">Kasir</p>
                                            <p class="text-sm font-medium">{{ $selectedTransaction->user->name ?? '-' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500">Pembayaran</p>
                                            <p class="text-sm font-medium uppercase">{{ $selectedTransaction->paymentSource->name ?? ($selectedTransaction->payment_method === 'cash' ? 'Tunai' : $selectedTransaction->payment_method) }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500">Total</p>
                                            <p class="text-sm font-bold text-gray-900">Rp {{ number_format($selectedTransaction->total, 0, ',', '.') }}</p>
                                        </div>
                                    </div>

                                    @if($selectedTransaction->selfOrder)
                                        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-4 text-xs space-y-2">
                                            <div class="flex items-center justify-between font-bold text-amber-900 border-b border-amber-200/80 pb-2">
                                                <span class="flex items-center gap-1.5">
                                                    <i data-lucide="smartphone" class="w-4 h-4 text-amber-700"></i>
                                                    Pesanan Mandiri (QR Order) #{{ $selectedTransaction->selfOrder->queue_display }}
                                                </span>
                                                <span class="px-2.5 py-0.5 rounded-lg text-[11px] font-bold {{ $selectedTransaction->selfOrder->order_type === 'dine_in' ? 'bg-purple-100 text-purple-800' : 'bg-amber-100 text-amber-800' }}">
                                                    {{ $selectedTransaction->selfOrder->order_type === 'dine_in' ? 'Makan di Tempat' : 'Bawa Pulang' }}
                                                </span>
                                            </div>
                                            <div class="grid grid-cols-2 gap-2.5 text-gray-800 pt-1">
                                                <div>
                                                    <span class="text-gray-500 block text-[11px]">Nama Pemesan</span>
                                                    <strong class="text-sm text-gray-900">{{ $selectedTransaction->selfOrder->customer_name }}</strong>
                                                </div>
                                                <div>
                                                    <span class="text-gray-500 block text-[11px]">No. HP Pemesan</span>
                                                    <strong class="text-sm font-mono text-gray-900">{{ $selectedTransaction->selfOrder->customer_phone }}</strong>
                                                </div>
                                                @if($selectedTransaction->selfOrder->order_type === 'take_away')
                                                    <div class="col-span-2 pt-2 border-t border-amber-200/60 mt-1">
                                                        <span class="text-amber-800 font-semibold uppercase text-[10px] tracking-wider block mb-0.5">Pengambil Pesanan (Serah Terima)</span>
                                                        <p class="text-sm font-bold text-gray-900">{{ $selectedTransaction->selfOrder->pickup_display_name }}</p>
                                                        <p class="text-xs font-mono text-gray-600">{{ $selectedTransaction->selfOrder->pickup_display_phone }}</p>
                                                        @if($selectedTransaction->selfOrder->pickup_confirmed_at)
                                                            <p class="text-[11px] text-emerald-700 font-medium mt-0.5">✓ Dikonfirmasi: {{ $selectedTransaction->selfOrder->pickup_confirmed_at->format('d/m/Y H:i') }}</p>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endif

                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <p class="text-xs font-semibold text-gray-500 uppercase mb-2">Item Pembelian</p>
                                        <div class="space-y-2">
                                            @foreach($selectedTransaction->details ?? [] as $item)
                                                <div class="flex justify-between text-sm">
                                                    <div>
                                                        <span class="font-medium">{{ $item->product_name }}</span>
                                                        <span class="text-gray-500">x{{ $item->quantity }}</span>
                                                        @if($item->modifiers)
                                                            <div class="text-xs text-gray-500 ml-2">
                                                                @php
                                                                    $mods = is_string($item->modifiers) ? json_decode($item->modifiers, true) : $item->modifiers;
                                                                @endphp
                                                                @foreach($mods ?? [] as $mod)
                                                                    @php
                                                                        if (is_array($mod)) {
                                                                            $modName = $mod['name'] ?? $mod['modifier_name'] ?? '-';
                                                                            $modPrice = $mod['price'] ?? $mod['price_adjustment'] ?? 0;
                                                                        } else {
                                                                            // Eloquent Model with Pivot
                                                                            $modName = $mod->pivot->modifier_name ?? $mod->name;
                                                                            $modPrice = $mod->pivot->price_adjustment ?? $mod->price ?? 0;
                                                                        }
                                                                    @endphp
                                                                    + {{ $modName }}
                                                                    @if($modPrice > 0)
                                                                        (Rp {{ number_format($modPrice, 0, ',', '.') }})
                                                                    @else
                                                                        (Gratis)
                                                                    @endif<br>
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <span class="text-gray-700">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    
                                    <!-- Print Button inside Modal -->
                                     <div class="mt-4 flex justify-end">
                                        <a :href="'{{ route('print.transaction.single', $selectedTransaction->id) }}' + '?format=' + printFormat" 
                                           target="_blank"
                                           @click="setTimeout(() => $wire.$refresh(), 2000)"
                                           class="flex items-center gap-2 px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 transition-colors">
                                            <i data-lucide="printer" class="w-4 h-4"></i>
                                            <span>Cetak Struk</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Receipt Modal -->
    @if($showReceiptModal && $lastTransaction)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    @include('livewire.partials.receipt', ['transaction' => $lastTransaction])
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm" wire:click="printReceipt">
                            Cetak
                        </button>
                        <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" wire:click="closeReceiptModal">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

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
        startInput.value = formatDate(initStart);
        endInput.value = formatDate(initEnd);
        
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
                    
                    const start = selectedDates[0].toISOString().split('T')[0];
                    const end = selectedDates[1].toISOString().split('T')[0];
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

// Print receipt event
$wire.on('print-receipt', () => {
    setTimeout(() => {
        window.print();
    }, 300);
});

</script>
@endscript
</div>
</div>
