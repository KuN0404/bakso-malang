<div>
    <style>
        @media print {
            aside, nav, .sidebar, .no-print, button, .lucide, .items-center.gap-4, .items-center.gap-2 {
                display: none !important;
            }
            body, main, div, .main-content {
                background: white !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                height: auto !important;
                overflow: visible !important;
            }
            .p-4, .p-6, .py-8 { padding: 0 !important; }
            .grid { display: block !important; }
            table {
                width: 100% !important;
                border-collapse: collapse !important;
                font-size: 10pt !important;
            }
            th, td {
                border: 1px solid #eee !important;
                padding: 4px 8px !important;
            }
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
        <h1 class="text-2xl font-bold text-gray-800">Laporan Retur - Bakso Malang</h1>
        <p class="text-gray-600">{{ Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ Carbon\Carbon::parse($endDate)->format('d M Y') }}</p>
    </div>

    <!-- Screen Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8 no-print">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Laporan Retur</h1>
            <p class="text-gray-500">History pengembalian produk</p>
        </div>

    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6 print:hidden no-print">
        <!-- Card 1: Total Nilai Retur -->
        <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm relative overflow-hidden">
             <div class="absolute top-0 right-0 p-3 opacity-5 font-bold text-6xl text-red-600 pointer-events-none">
                <i data-lucide="wallet" class="w-24 h-24"></i>
            </div>
            <div class="relative z-10">
                <p class="text-sm font-medium text-gray-500 mb-2">Total Nilai Retur</p>
                <h3 class="text-3xl font-bold text-red-600">Rp {{ number_format($todayTotal, 0, ',', '.') }}</h3>
                <div class="mt-2 flex items-center text-xs text-red-600 bg-red-50 w-fit px-2 py-1 rounded-md">
                   <i data-lucide="alert-circle" class="w-3 h-3 mr-1"></i>
                   Loss revenue
                </div>
            </div>
        </div>

        <!-- Card 2: Total Item Retur -->
        <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm relative overflow-hidden">
             <div class="absolute top-0 right-0 p-3 opacity-5 font-bold text-6xl text-blue-600 pointer-events-none">
                <i data-lucide="package" class="w-24 h-24"></i>
            </div>
            <div class="relative z-10">
                <p class="text-sm font-medium text-gray-500 mb-2">Total Qty Retur</p>
                <h3 class="text-3xl font-bold text-gray-800">{{ $returnsQty }}</h3>
                 <div class="mt-2 text-xs text-gray-400">
                   Total item produk dikembalikan
                </div>
            </div>
        </div>

        <!-- Card 3: Total Transaksi Retur -->
        <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm relative overflow-hidden">
             <div class="absolute top-0 right-0 p-3 opacity-5 font-bold text-6xl text-orange-600 pointer-events-none">
                <i data-lucide="receipt" class="w-24 h-24"></i>
            </div>
            <div class="relative z-10">
                <p class="text-sm font-medium text-gray-500 mb-2">Total Transaksi Retur</p>
                <h3 class="text-3xl font-bold text-gray-800">{{ $returnsCount }}</h3>
                 <div class="mt-2 text-xs text-gray-400">
                   Nota transaksi yang diretur
                </div>
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
                        wire:click="$set('periodType', '{{ $key }}')"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-all whitespace-nowrap flex-1 md:flex-none text-center {{ $periodType === $key ? 'bg-white text-primary-600 shadow-sm' : 'text-gray-600 hover:text-gray-800' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <!-- Date Inputs & Reset -->
            <div class="flex flex-wrap items-center gap-4 w-full md:w-auto">
                <!-- Date Range Picker -->
                @if($periodType === 'daily')
                    <div class="flex items-center gap-3" id="datePickerContainer">
                        <div class="flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-white to-gray-50 border border-gray-200 rounded-xl hover:border-primary-400 hover:shadow-md transition-all cursor-pointer group w-full sm:w-auto" id="startDateContainer">
                            <i data-lucide="calendar" class="w-4 h-4 text-primary-500 group-hover:text-primary-600"></i>
                            <div class="flex flex-col">
                                <span class="text-[10px] text-gray-400 uppercase tracking-wide">Dari</span>
                                <input type="text" id="dateRangeStart" value="{{ $startDate }}" class="bg-transparent border-none outline-none text-sm font-semibold text-gray-700 cursor-pointer w-full sm:w-28" placeholder="Pilih" readonly>
                            </div>
                        </div>
                        <div class="hidden sm:flex items-center justify-center w-8 h-8 bg-gray-100 rounded-full">
                            <i data-lucide="arrow-right" class="w-4 h-4 text-gray-400"></i>
                        </div>
                        <div class="flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-white to-gray-50 border border-gray-200 rounded-xl hover:border-primary-400 hover:shadow-md transition-all cursor-pointer group w-full sm:w-auto" id="endDateContainer">
                            <i data-lucide="calendar" class="w-4 h-4 text-primary-500 group-hover:text-primary-600"></i>
                            <div class="flex flex-col">
                                <span class="text-[10px] text-gray-400 uppercase tracking-wide">Sampai</span>
                                <input type="text" id="dateRangeEnd" value="{{ $endDate }}" class="bg-transparent border-none outline-none text-sm font-semibold text-gray-700 cursor-pointer w-full sm:w-28" placeholder="Pilih" readonly>
                            </div>
                        </div>
                        <!-- Hidden true input for Flatpickr range mode hacking if needed, but we will try to bind to container -->
                        <input type="hidden" id="dateRange">
                    </div>
                @elseif($periodType === 'weekly')
                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <select wire:model.live="selectedWeekYear" class="px-4 py-2.5 border border-gray-200 rounded-xl bg-white text-sm font-medium w-full sm:w-auto">
                             @foreach(range(date('Y'), 2024) as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </select>
                        <select wire:model.live="selectedWeek" class="px-4 py-2.5 border border-gray-200 rounded-xl bg-white text-sm font-medium w-full sm:w-auto">
                            @for($i = 1; $i <= 53; $i++)
                                <option value="{{ $i }}">Minggu ke-{{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                @elseif($periodType === 'monthly')
                    <input type="month" wire:model.live="selectedMonth" class="px-4 py-2.5 border border-gray-200 rounded-xl bg-white text-sm font-medium w-full sm:w-auto">
                @elseif($periodType === 'yearly')
                    <select wire:model.live="selectedYear" class="px-4 py-2.5 border border-gray-200 rounded-xl bg-white text-sm font-medium w-full sm:w-auto">
                        @foreach(range(date('Y'), 2024) as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                @endif
                
                <button wire:click="resetFilters" wire:loading.attr="disabled" class="flex items-center justify-center gap-2 px-4 py-2.5 text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-xl transition-colors w-full sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed">
                    <i wire:loading.remove wire:target="resetFilters" data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    <i wire:loading wire:target="resetFilters" data-lucide="loader" class="w-4 h-4 animate-spin text-primary-600"></i>
                    <span class="text-sm font-medium">Reset</span>
                </button>

                <div class="h-8 w-px bg-gray-200 hidden md:block"></div>

                <a href="{{ route('export.product-returns', ['start' => $startDate, 'end' => $endDate]) }}" class="flex items-center justify-center gap-2 px-4 py-2.5 bg-green-50 text-green-600 hover:bg-green-100 rounded-xl transition-colors w-full sm:w-auto" title="Export Excel">
                    <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                    <span class="text-sm font-medium">Excel</span>
                </a>

                <a href="{{ route('print.returns-report', ['start' => $startDate, 'end' => $endDate, 'search' => $search]) }}" target="_blank" class="flex items-center justify-center gap-2 px-4 py-2.5 bg-gray-800 text-white hover:bg-gray-700 rounded-xl transition-colors w-full sm:w-auto" title="Cetak Laporan">
                    <i data-lucide="printer" class="w-4 h-4"></i>
                    <span class="text-sm font-medium">Cetak</span>
                </a>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 border-b border-gray-100 flex justify-between items-center no-print">
            <h2 class="font-bold text-gray-800">Daftar Retur</h2>
            <div class="w-64">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nomor retur..." class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
            </div>
        </div>
        
        <!-- Screen Table -->
        <div class="overflow-x-auto no-print">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">No. Retur</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Invoice</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Kasir</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Total Refund</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Alasan</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Waktu</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($returns as $return)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-800">{{ $return->return_number }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ $return->transaction->invoice_number }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ $return->user->name }}</td>
                            <td class="px-6 py-4 text-red-600 font-medium">Rp {{ number_format($return->total_refund, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ Str::limit($return->reason, 20) }}</td>
                            <td class="px-6 py-4 text-gray-500">{{ $return->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-6 py-4 text-right">
                                <button wire:click="viewDetail({{ $return->id }})" class="p-2 text-gray-400 hover:text-primary-600 rounded-lg hover:bg-gray-100">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">Tidak ada data retur</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>



        @if($returns->hasPages())
            <div class="px-6 py-4 border-t no-print">{{ $returns->links() }}</div>
        @endif
    </div>

    <!-- Detail Modal -->
    @if($showDetailModal && $selectedReturn)
        <div 
            wire:click.self="$set('showDetailModal', false)"
            class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center overflow-y-auto py-8 transition-opacity"
        >
            <div class="bg-white rounded-2xl w-full max-w-md mx-4 shadow-2xl relative animate-in fade-in zoom-in duration-200">
                <div class="p-6 border-b flex justify-between items-center bg-gray-50/50 rounded-t-2xl">
                    <h3 class="text-xl font-bold text-gray-800">Detail Retur</h3>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('returns.print', $selectedReturn->id) }}" target="_blank" class="p-2 text-gray-500 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-colors" title="Cetak Struk">
                             <i data-lucide="printer" class="w-5 h-5"></i>
                        </a>
                        <button wire:click="$set('showDetailModal', false)" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                            <i data-lucide="x" class="w-6 h-6"></i>
                        </button>
                    </div>
                </div>
                <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                    <!-- Info Grid -->
                    <div class="bg-gray-50 rounded-xl p-4 grid grid-cols-2 gap-4 text-sm">
                        <div><p class="text-xs text-gray-500 mb-1">No. Retur</p><p class="font-bold text-gray-800">{{ $selectedReturn->return_number }}</p></div>
                        <div><p class="text-xs text-gray-500 mb-1">Invoice</p><p class="font-medium text-gray-800">{{ $selectedReturn->transaction->invoice_number }}</p></div>
                        <div><p class="text-xs text-gray-500 mb-1">Kasir</p><p class="font-medium text-gray-800">{{ $selectedReturn->user->name }}</p></div>
                        <div><p class="text-xs text-gray-500 mb-1">Waktu</p><p class="font-medium text-gray-800">{{ $selectedReturn->created_at->format('d/m/Y H:i') }}</p></div>
                    </div>

                    <!-- Items List -->
                    <div>
                        <p class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
                            <i data-lucide="package" class="w-4 h-4 text-gray-400"></i>
                            Item Diretur
                        </p>
                        <div class="border border-gray-100 rounded-xl divide-y divide-gray-100 overflow-hidden">
                            @foreach($selectedReturn->items as $item)
                                <div class="p-4 bg-white hover:bg-gray-50 transition-colors flex justify-between items-start">
                                    <div>
                                        <p class="font-medium text-gray-800">{{ $item->product ? $item->product->name : ($item->product_name ?? 'Item Terhapus') }}</p>
                                        @if(is_array($item->modifiers) && count($item->modifiers) > 0)
                                            <p class="text-xs text-gray-500 italic mt-0.5">
                                                + {{ collect($item->modifiers)->pluck('name')->implode(', ') }}
                                            </p>
                                        @endif
                                        <p class="text-xs text-gray-500 mt-1">{{ $item->quantity }} x Rp {{ number_format($item->unit_price, 0, ',', '.') }}</p>
                                    </div>
                                    <p class="font-bold text-red-600 text-sm">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Refund Total -->
                    <div class="bg-red-50 border border-red-100 rounded-xl p-4 flex justify-between items-center">
                        <span class="text-sm font-medium text-red-800">Total Refund</span>
                        <span class="text-2xl font-bold text-red-600">Rp {{ number_format($selectedReturn->total_refund, 0, ',', '.') }}</span>
                    </div>

                    <!-- Reason -->
                    <div class="bg-yellow-50 border border-yellow-100 rounded-xl p-4">
                        <p class="text-xs font-bold text-yellow-700 uppercase tracking-wide mb-1">Alasan Pengembalian</p>
                        <p class="text-sm text-yellow-800 italic">"{{ $selectedReturn->reason }}"</p>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        let datePicker;

        function initDatePicker() {
            const startEl = document.getElementById('dateRangeStart');
            if (!startEl) return;

            if (datePicker) {
                datePicker.destroy();
            }

            datePicker = flatpickr(startEl, {
                mode: 'range',
                dateFormat: 'Y-m-d',
                defaultDate: [@js($startDate), @js($endDate)],
                locale: 'id',
                onChange: function(selectedDates, dateStr, instance) {
                    if (selectedDates.length === 2) {
                        const startDisp = instance.formatDate(selectedDates[0], 'd M Y');
                        const endDisp = instance.formatDate(selectedDates[1], 'd M Y');
                        
                        if(document.getElementById('dateRangeStart')) document.getElementById('dateRangeStart').value = startDisp;
                        if(document.getElementById('dateRangeEnd')) document.getElementById('dateRangeEnd').value = endDisp;

                        const startYMD = instance.formatDate(selectedDates[0], 'Y-m-d');
                        const endYMD = instance.formatDate(selectedDates[1], 'Y-m-d');
                        
                        @this.set('startDate', startYMD);
                        @this.set('endDate', endYMD);
                        @this.applyDateRange();
                    }
                },
                onReady: function(selectedDates, dateStr, instance) {
                     if (selectedDates.length === 2) {
                        const start = instance.formatDate(selectedDates[0], 'd M Y');
                        const end = instance.formatDate(selectedDates[1], 'd M Y');
                         if(document.getElementById('dateRangeStart')) document.getElementById('dateRangeStart').value = start;
                         if(document.getElementById('dateRangeEnd')) document.getElementById('dateRangeEnd').value = end;
                     }
                }
            });

            // Trigger calendar on container click
            ['startDateContainer', 'endDateContainer'].forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.addEventListener('click', (e) => {
                        e.stopPropagation();
                        datePicker.open();
                    });
                }
            });
        }

        initDatePicker();
        lucide.createIcons();

        Livewire.on('reset-date-picker', (data) => {
            if (datePicker) {
                 datePicker.setDate([data.start, data.end], true);
            }
        });

        Livewire.hook('morph.updated', ({ el, component }) => {
             if (document.getElementById('dateRangeStart')) {
                 if (!document.getElementById('dateRangeStart')._flatpickr) {
                     initDatePicker();
                 }
             }
             lucide.createIcons();
        });
    });
</script>
