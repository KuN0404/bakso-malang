<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Laporan Penjualan</h1>
            <p class="text-gray-500">Daftar lengkap riwayat transaksi penjualan</p>
        </div>
        <div class="flex items-center gap-2">
            <!-- Print Table -->
            <a href="{{ $this->getPrintTableUrl() }}" target="_blank" class="px-3 py-2 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 font-medium rounded-lg flex items-center gap-2 transition-colors" title="Cetak Tabel">
                <i data-lucide="printer" class="w-4 h-4"></i>
                <span class="hidden sm:inline">Cetak</span>
            </a>
            
            <!-- Print Detail -->
            <a href="{{ $this->getPrintDetailUrl() }}" target="_blank" class="px-3 py-2 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 font-medium rounded-lg flex items-center gap-2 transition-colors" title="Cetak Detail">
                <i data-lucide="file-text" class="w-4 h-4"></i>
                <span class="hidden sm:inline">Cetak Detail</span>
            </a>
            
            <!-- Export Summary -->
            <a href="{{ $this->getExportUrl() }}" class="px-3 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg flex items-center gap-2 transition-colors" title="Download Excel Ringkasan">
                <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                <span class="hidden sm:inline">Excel</span>
            </a>

            <!-- Export Detail -->
            <a href="{{ $this->getExportDetailUrl() }}" class="px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg flex items-center gap-2 transition-colors" title="Download Excel Detail Item">
                <i data-lucide="download" class="w-4 h-4"></i>
                <span class="hidden sm:inline">Excel Detail</span>
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl p-4 border border-gray-100">
            <p class="text-sm text-gray-500">Total Transaksi</p>
            <p class="text-2xl font-bold text-gray-800">{{ number_format($summary['total_transactions']) }}</p>
        </div>
        <div class="bg-white rounded-xl p-4 border border-gray-100">
            <p class="text-sm text-gray-500">Total Pendapatan</p>
            <p class="text-2xl font-bold text-green-600">Rp {{ number_format($summary['total_revenue'], 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-xl p-4 border border-gray-100">
            <p class="text-sm text-gray-500">Rata-rata per Transaksi</p>
            <p class="text-2xl font-bold text-blue-600">Rp {{ $summary['total_transactions'] > 0 ? number_format($summary['total_revenue'] / $summary['total_transactions'], 0, ',', '.') : 0 }}</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-4">
        <div class="flex flex-wrap items-center gap-4">
            <!-- Period Type Tabs -->
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

            <!-- Dynamic Filter Based on Period Type -->
            @if($periodType === 'daily')
                <!-- Date Range Picker -->
                <div class="flex items-center gap-3" 
                     x-data="{ init() { initDatePicker(@js($startDate), @js($endDate)) } }" 
                     x-init="init()"
                     wire:key="date-picker-{{ $periodType }}">
                    <div class="flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-white to-gray-50 border border-gray-200 rounded-xl hover:border-primary-400 hover:shadow-md transition-all cursor-pointer group" id="startDateContainer">
                        <i data-lucide="calendar" class="w-4 h-4 text-primary-500 group-hover:text-primary-600"></i>
                        <div class="flex flex-col">
                            <span class="text-[10px] text-gray-400 uppercase tracking-wide">Dari</span>
                            <input type="text" id="dateRangeStart" class="bg-transparent border-none outline-none text-sm font-semibold text-gray-700 cursor-pointer w-28" placeholder="Pilih" readonly>
                        </div>
                    </div>
                    <div class="flex items-center justify-center w-8 h-8 bg-gray-100 rounded-full">
                        <i data-lucide="arrow-right" class="w-4 h-4 text-gray-400"></i>
                    </div>
                    <div class="flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-white to-gray-50 border border-gray-200 rounded-xl hover:border-primary-400 hover:shadow-md transition-all cursor-pointer group" id="endDateContainer">
                        <i data-lucide="calendar" class="w-4 h-4 text-primary-500 group-hover:text-primary-600"></i>
                        <div class="flex flex-col">
                            <span class="text-[10px] text-gray-400 uppercase tracking-wide">Sampai</span>
                            <input type="text" id="dateRangeEnd" class="bg-transparent border-none outline-none text-sm font-semibold text-gray-700 cursor-pointer w-28" placeholder="Pilih" readonly>
                        </div>
                    </div>
                </div>
            @elseif($periodType === 'weekly')
                <!-- Week Selector -->
                <div class="flex items-center gap-3">
                    <select wire:model.live="selectedWeek" class="px-4 py-2.5 border border-gray-200 rounded-xl bg-white text-sm font-medium">
                        @foreach($this->weeks as $weekNum => $weekLabel)
                            <option value="{{ $weekNum }}">{{ $weekLabel }}</option>
                        @endforeach
                    </select>
                    <select wire:model.live="selectedWeekYear" class="px-4 py-2.5 border border-gray-200 rounded-xl bg-white text-sm font-medium">
                        @foreach($this->years as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
            @elseif($periodType === 'monthly')
                <!-- Month Selector -->
                <div class="flex items-center gap-3">
                    <select wire:model.live="selectedMonth" class="px-4 py-2.5 border border-gray-200 rounded-xl bg-white text-sm font-medium">
                        @foreach($months as $num => $name)
                            <option value="{{ $num }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    <select wire:model.live="selectedMonthYear" class="px-4 py-2.5 border border-gray-200 rounded-xl bg-white text-sm font-medium">
                        @foreach($this->years as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
            @elseif($periodType === 'yearly')
                <!-- Year Selector -->
                <div class="flex items-center gap-3">
                    <select wire:model.live="selectedYear" class="px-4 py-2.5 border border-gray-200 rounded-xl bg-white text-sm font-medium">
                        @foreach($this->years as $year)
                            <option value="{{ $year }}">Tahun {{ $year }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            
            <!-- Reset Button -->
            <button 
                wire:click="resetFilters"
                class="flex items-center gap-1.5 px-3 py-2 text-gray-500 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors"
                title="Reset filter"
            >
                <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                <span class="text-sm">Reset</span>
            </button>
        </div>

        <div class="flex flex-wrap items-center gap-4 mt-4">
            <!-- Search -->
            <div class="relative flex-1 min-w-[200px]">
                <input 
                    type="text" 
                    wire:model.live.debounce.300ms="search" 
                    placeholder="Cari invoice..." 
                    class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg"
                >
                <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
            </div>



            <!-- Cashier Filter -->
            <select wire:model.live="filterCashier" class="px-4 py-2 border border-gray-200 rounded-lg">
                <option value="">Semua Kasir</option>
                @foreach($cashiers as $cashier)
                    <option value="{{ $cashier->id }}">{{ $cashier->name }}</option>
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
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Invoice</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Waktu</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Kasir</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Pembayaran</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Total</th>

                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Cetak</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($transactions as $transaction)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <span class="font-medium text-gray-800">{{ $transaction->invoice_number }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                {{ $transaction->created_at->format('d M Y, H:i') }}
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ $transaction->user?->name ?? '-' }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                                    {{ $transaction->paymentSource?->name ?? 'Cash' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-800">
                                Rp {{ number_format($transaction->total, 0, ',', '.') }}
                            </td>

                            <td class="px-4 py-3 text-center">
                                <span class="text-sm text-gray-500">{{ $transaction->print_count }}x</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button wire:click="view({{ $transaction->id }})" class="p-1.5 text-gray-400 hover:text-primary-600 rounded" title="Lihat Detail">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </button>
                                    <button wire:click="printReceipt({{ $transaction->id }})" class="p-1.5 text-gray-400 hover:text-green-600 rounded" title="Cetak Struk">
                                        <i data-lucide="printer" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-gray-500">
                                <i data-lucide="receipt" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                                <p>Tidak ada transaksi ditemukan</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transactions->hasPages())
            <div class="p-4 border-t bg-gray-50">
                {{ $transactions->links(data: ['scrollTo' => false]) }}
            </div>
        @endif
    </div>

    <!-- Detail Modal with Modifiers -->
    @if($showModal && $selectedTransaction)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center overflow-y-auto py-8">
            <div class="bg-white rounded-2xl w-full max-w-lg mx-4 shadow-2xl max-h-[90vh] flex flex-col">
                <div class="p-6 border-b flex justify-between items-center">
                    <h3 class="text-xl font-bold text-gray-800">{{ $selectedTransaction->invoice_number }}</h3>
                    <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
                <div class="p-6 overflow-y-auto flex-1 space-y-4">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-gray-500">Tanggal</p>
                            <p class="font-medium">{{ $selectedTransaction->created_at->format('d M Y, H:i') }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Kasir</p>
                            <p class="font-medium">{{ $selectedTransaction->user?->name ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Pelanggan</p>
                            <p class="font-medium">{{ $selectedTransaction->customer_name ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Pembayaran</p>
                            <p class="font-medium">{{ $selectedTransaction->paymentSource?->name ?? 'Cash' }}</p>
                        </div>
                    </div>

                    <div class="border-t pt-4">
                        <p class="text-sm font-semibold text-gray-700 mb-2">Item</p>
                        <div class="space-y-3">
                            @foreach($selectedTransaction->details as $detail)
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <div class="flex justify-between text-sm">
                                        <div>
                                            <span class="font-medium">{{ $detail->product_name }}</span>
                                            <span class="text-gray-500">x{{ $detail->quantity }}</span>
                                        </div>
                                        <span class="font-semibold">Rp {{ number_format($detail->subtotal, 0, ',', '.') }}</span>
                                    </div>
                                    @if($detail->modifiers && $detail->modifiers->isNotEmpty())
                                        <div class="mt-1 text-xs text-gray-500">
                                            @foreach($detail->modifiers as $mod)
                                                <span class="inline-block mr-2">
                                                    + {{ $mod->pivot->modifier_name }}
                                                    @if($mod->pivot->price_adjustment > 0)
                                                        <span class="text-green-600">(+Rp {{ number_format($mod->pivot->price_adjustment, 0, ',', '.') }})</span>
                                                    @endif
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="border-t pt-4 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Subtotal</span>
                            <span>Rp {{ number_format($selectedTransaction->subtotal, 0, ',', '.') }}</span>
                        </div>
                        @if($selectedTransaction->tax_amount > 0)
                            <div class="flex justify-between">
                                <span class="text-gray-500">Pajak</span>
                                <span>Rp {{ number_format($selectedTransaction->tax_amount, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between font-bold text-lg pt-2 border-t">
                            <span>Total</span>
                            <span class="text-primary-600">Rp {{ number_format($selectedTransaction->total, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
                <div class="p-4 border-t bg-gray-50 flex gap-3">
                    <button wire:click="$set('showModal', false)" class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg">Tutup</button>
                    <button wire:click="printReceipt({{ $selectedTransaction->id }})" class="flex-1 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg flex items-center justify-center gap-2">
                        <i data-lucide="printer" class="w-4 h-4"></i>
                        Cetak Struk
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Receipt Modal (for Printing) -->
    @if($showReceiptModal && $lastTransaction)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center p-4" style="z-index: 9999;">
            <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl max-h-[90vh] flex flex-col">
                <div class="p-4 border-b flex justify-between items-center flex-none">
                    <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                        <i data-lucide="receipt" class="w-5 h-5"></i>
                        Struk Transaksi
                    </h3>
                    <button wire:click="closeReceiptModal" class="text-gray-400 hover:text-gray-600 p-1" type="button">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>

                <!-- Receipt Content -->
                <div id="receipt-container" class="p-4 overflow-y-auto flex-1 custom-scroll">
                    @include('livewire.partials.receipt', ['transaction' => $lastTransaction])
                </div>

                <div class="p-4 border-t flex gap-3 flex-none">
                    <button 
                        wire:click="reprintOnly"
                        class="flex-1 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-xl flex items-center justify-center gap-2"
                        type="button"
                    >
                        <i data-lucide="printer" class="w-5 h-5"></i>
                        Cetak Ulang
                    </button>
                    <button 
                        wire:click="closeReceiptModal"
                        class="flex-1 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl"
                        type="button"
                    >
                        Tutup
                    </button>
                </div>
            </div>
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
        const openPicker = (e) => { e.stopPropagation(); fp.open(); };
        startInput.addEventListener('click', openPicker);
        endInput.addEventListener('click', openPicker);
        document.getElementById('startDateContainer')?.addEventListener('click', openPicker);
        document.getElementById('endDateContainer')?.addEventListener('click', openPicker);
        
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
