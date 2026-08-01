<div x-data="{ printFormat: '58mm' }" x-init="lucide.createIcons()" class="relative">
    <!-- Loading Overlay -->
    <div wire:loading wire:target="periodType, filterUserId, startDate, endDate, selectedMonth, selectedMonthYear, selectedYear, selectedWeek, selectedWeekYear, resetFilters, applyDateRange, viewDetail, gotoPage, previousPage, nextPage" class="absolute inset-0 bg-white/50 backdrop-blur-[1px] z-50 rounded-xl">
        <div class="sticky top-[40vh] flex flex-col items-center justify-center w-full gap-2">
            <svg class="animate-spin h-8 w-8 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-sm font-medium text-primary-600">Memuat Laporan Shift...</span>
        </div>
    </div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Laporan Shift</h1>
            <p class="text-gray-500">Rekap shift kasir harian</p>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Total Penjualan</p>
                    <p class="text-2xl font-bold text-green-600">Rp {{ number_format($totalSales, 0, ',', '.') }}</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                    <i data-lucide="trending-up" class="w-6 h-6 text-green-600"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Total Pengeluaran</p>
                    <p class="text-2xl font-bold text-red-600">Rp {{ number_format($totalExpenses, 0, ',', '.') }}</p>
                </div>
                <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                    <i data-lucide="trending-down" class="w-6 h-6 text-red-600"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Selisih Kas</p>
                    <p class="text-2xl font-bold {{ $totalDifference >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $totalDifference >= 0 ? '+' : '' }}Rp {{ number_format($totalDifference, 0, ',', '.') }}
                    </p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                    <i data-lucide="wallet" class="w-6 h-6 text-blue-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-4">
        <div class="flex flex-col gap-4">
            <!-- Top Section: Period Filters & Actions -->
            <div class="flex flex-wrap items-center gap-4">
                <!-- Period Tabs -->
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

                <!-- Active Filters & Actions -->
                @if($periodType === 'daily')
                    <!-- DAILY VIEW -->
                    <!-- Date Range -->
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
                        wire:key="date-picker-shift-daily"
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



                @else
                    <!-- OTHER VIEWS (Weekly/Monthly/Yearly) -->
                    @if($periodType === 'weekly')
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
                    
                @endif

                <!-- Shared Actions & Filters -->
                <div class="h-8 w-px bg-gray-200 hidden sm:block"></div>

                <!-- Print Group (Compact Dark) -->
                <div class="flex items-center gap-1 bg-gray-900 rounded-xl p-1 shadow-lg shadow-gray-200/50">
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
                </div>
                
                <!-- Vertical Stack: Excel & Reset -->
                <div class="flex items-center gap-1">
                     <a href="{{ $this->getExportUrl() }}" class="w-8 h-8 flex items-center justify-center bg-green-50 hover:bg-green-100 text-green-600 hover:text-green-700 rounded-lg transition-colors border border-green-200" title="Excel Ringkasan">
                        <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                    </a>
                    <a href="{{ $this->getExportDetailUrl() }}" class="w-8 h-8 flex items-center justify-center bg-emerald-50 hover:bg-emerald-100 text-emerald-600 hover:text-emerald-700 rounded-lg transition-colors border border-emerald-200" title="Excel Detail">
                        <i data-lucide="download" class="w-4 h-4"></i>
                    </a>
                    <button wire:click="resetFilters" class="w-8 h-8 flex items-center justify-center bg-red-50 hover:bg-red-100 text-red-600 hover:text-red-700 rounded-lg transition-colors border border-red-200" title="Reset Filter">
                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    </button>
                </div>

                <!-- Cashier Filter (Global) -->
                <div class="h-8 w-px bg-gray-200 hidden sm:block"></div>
                <div class="relative min-w-[200px]">
                     <i data-lucide="user" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                    <select wire:model.live="filterUserId" class="w-full pl-9 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none transition-all text-sm appearance-none bg-white">
                        <option value="">Semua Kasir</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Kasir</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Waktu</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Modal Awal</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Penjualan</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Pengeluaran</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Selisih</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($shifts as $shift)
                    @php
                        $shiftSales = $shift->transactions_sum_total ?? 0;
                        $shiftExpenses = $shift->expenses_sum_amount ?? 0;
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium text-gray-800">{{ $shift->user->name }}</td>
                        <td class="px-6 py-4 text-gray-600">
                            <p>{{ $shift->started_at->format('H:i') }}</p>
                            @if($shift->ended_at)
                                <p class="text-sm text-gray-400">s/d {{ $shift->ended_at->format('H:i') }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-gray-600">Rp {{ number_format($shift->opening_cash, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-green-600 font-medium">Rp {{ number_format($shiftSales, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-red-600">Rp {{ number_format($shiftExpenses, 0, ',', '.') }}</td>
                        <td class="px-6 py-4">
                            @if($shift->status === 'closed')
                                @php
                                    $cashDiff = $shift->cash_difference;
                                    $nonCashDiff = $shift->non_cash_difference;
                                @endphp
                                <div class="flex flex-col gap-0.5 text-xs">
                                    <span class="{{ $cashDiff >= 0 ? 'text-green-600' : 'text-red-600' }} font-medium">
                                        Tunai: {{ $cashDiff >= 0 ? '+' : '' }}Rp {{ number_format($cashDiff, 0, ',', '.') }}
                                    </span>
                                    @if($nonCashDiff !== null)
                                    <span class="{{ $nonCashDiff >= 0 ? 'text-purple-600' : 'text-orange-600' }} font-medium">
                                        Non-Tunai: {{ $nonCashDiff >= 0 ? '+' : '' }}Rp {{ number_format($nonCashDiff, 0, ',', '.') }}
                                    </span>
                                    @endif
                                </div>
                            @else
                                <span class="text-gray-400 text-xs">Shift aktif</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($shift->status === 'open')
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">Aktif</span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Ditutup</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <!-- View Detail -->
                                <button wire:click="viewDetail({{ $shift->id }})" class="p-2 text-gray-400 hover:text-primary-600 rounded-lg hover:bg-gray-100" title="Detail">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                                
                                <!-- Print Brief -->
                                <a :href="'{{ route('print.shift.custom', ['shift' => $shift->id, 'type' => 'brief']) }}' + '&format=' + printFormat" target="_blank" class="p-2 text-gray-400 hover:text-blue-600 rounded-lg hover:bg-blue-50" title="Cetak Ringkas">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                                </a>

                                <!-- Print Detail -->
                                <a :href="'{{ route('print.shift.custom', ['shift' => $shift->id, 'type' => 'detail']) }}' + '&format=' + printFormat" target="_blank" class="p-2 text-gray-400 hover:text-purple-600 rounded-lg hover:bg-purple-50" title="Cetak Lengkap">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M16 22h2a2 2 0 0 0 2-2V7.5L14.5 2H6a2 2 0 0 0-2 2v4"/><path d="M18 9l-5 5-2-2a2 2 0 0 0-2 0l-2 2-2-2"/></svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">Tidak ada data shift</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($shifts->hasPages())
            <div class="px-6 py-4 border-t">{{ $shifts->links() }}</div>
        @endif
    </div>

    <!-- Detail Modal -->
    @if($showDetailModal && $selectedShift)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center overflow-y-auto py-8" wire:click.self="$set('showDetailModal', false)">
            <div class="bg-white rounded-2xl w-full max-w-2xl mx-4 shadow-2xl overflow-hidden" x-init="setTimeout(() => lucide.createIcons(), 50)">
                <div class="p-6 border-b flex justify-between items-center bg-gray-50/50">
                    <h3 class="text-xl font-bold text-gray-800">Detail Shift</h3>
                    <button wire:click="$set('showDetailModal', false)" class="bg-gray-100 hover:bg-gray-200 text-gray-500 hover:text-gray-700 rounded-full p-2 transition-colors">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
                <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                    <div class="grid grid-cols-2 gap-4 bg-gray-50 rounded-lg p-4">
                        <div><p class="text-sm text-gray-500">Kasir</p><p class="font-medium">{{ $selectedShift->user->name }}</p></div>
                        <div><p class="text-sm text-gray-500">Tanggal</p><p class="font-medium">{{ $selectedShift->started_at->format('d M Y') }}</p></div>
                        <div><p class="text-sm text-gray-500">Mulai</p><p class="font-medium">{{ $selectedShift->started_at->format('H:i') }}</p></div>
                        <div><p class="text-sm text-gray-500">Selesai</p><p class="font-medium">{{ $selectedShift->ended_at?->format('H:i') ?? '-' }}</p></div>
                    </div>
                    <!-- Cash Verification Row -->
                    <div class="border rounded-xl overflow-hidden">
                        <div class="bg-green-50 px-4 py-2 border-b">
                            <p class="text-xs font-semibold text-green-700 uppercase tracking-wide">💵 Rekonsiliasi Tunai (Cash)</p>
                        </div>
                        <div class="grid grid-cols-3 divide-x">
                            <div class="p-3 text-center">
                                <p class="text-xs text-gray-500">Modal Awal</p>
                                <p class="font-bold text-gray-800">Rp {{ number_format($selectedShift->opening_cash, 0, ',', '.') }}</p>
                            </div>
                            <div class="p-3 text-center">
                                <p class="text-xs text-gray-500">Penjualan Tunai</p>
                                <p class="font-bold text-green-700">Rp {{ number_format($selectedShift->transactions->where('status','completed')->where('payment_method','cash')->sum('total'), 0, ',', '.') }}</p>
                            </div>
                            <div class="p-3 text-center">
                                <p class="text-xs text-gray-500">Pengeluaran</p>
                                <p class="font-bold text-red-700">-Rp {{ number_format($selectedShift->expenses->sum('amount'), 0, ',', '.') }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 divide-x border-t bg-gray-50">
                            <div class="p-3 text-center">
                                <p class="text-xs text-gray-500">Ekspektasi Sistem</p>
                                <p class="font-bold text-gray-800">Rp {{ number_format($selectedShift->expected_cash, 0, ',', '.') }}</p>
                            </div>
                            <div class="p-3 text-center">
                                <p class="text-xs text-gray-500">Fisik di Laci</p>
                                <p class="font-bold text-gray-800">Rp {{ number_format($selectedShift->actual_cash, 0, ',', '.') }}</p>
                            </div>
                        </div>
                        @if($selectedShift->cash_difference !== null)
                        <div class="px-4 py-2 border-t bg-{{ $selectedShift->cash_difference >= 0 ? 'green' : 'red' }}-50 text-center">
                            <span class="text-sm font-bold text-{{ $selectedShift->cash_difference >= 0 ? 'green' : 'red' }}-700">
                                Selisih Tunai: {{ $selectedShift->cash_difference >= 0 ? '+' : '' }}Rp {{ number_format($selectedShift->cash_difference, 0, ',', '.') }}
                            </span>
                        </div>
                        @endif
                    </div>

                    <!-- Non-Cash Verification Row -->
                    <div class="border rounded-xl overflow-hidden">
                        <div class="bg-purple-50 px-4 py-2 border-b">
                            <p class="text-xs font-semibold text-purple-700 uppercase tracking-wide">📱 Rekonsiliasi Non-Tunai (QRIS / Transfer / EDC)</p>
                        </div>
                        <div class="grid grid-cols-2 divide-x">
                            <div class="p-3 text-center">
                                <p class="text-xs text-gray-500">Penjualan Non-Tunai (Sistem)</p>
                                <p class="font-bold text-purple-700">Rp {{ number_format($selectedShift->expected_non_cash ?? $selectedShift->transactions->where('status','completed')->where('payment_method','!=','cash')->sum('total'), 0, ',', '.') }}</p>
                            </div>
                            <div class="p-3 text-center">
                                <p class="text-xs text-gray-500">Non-Tunai Terverifikasi</p>
                                <p class="font-bold text-gray-800">Rp {{ number_format($selectedShift->actual_non_cash ?? 0, 0, ',', '.') }}</p>
                            </div>
                        </div>
                        @if($selectedShift->non_cash_difference !== null)
                        <div class="px-4 py-2 border-t bg-{{ $selectedShift->non_cash_difference >= 0 ? 'purple' : 'orange' }}-50 text-center">
                            <span class="text-sm font-bold text-{{ $selectedShift->non_cash_difference >= 0 ? 'purple' : 'orange' }}-700">
                                Selisih Non-Tunai: {{ $selectedShift->non_cash_difference >= 0 ? '+' : '' }}Rp {{ number_format($selectedShift->non_cash_difference, 0, ',', '.') }}
                            </span>
                        </div>
                        @endif
                    </div>

                    @if($selectedShift->expenses->isNotEmpty())
                        <div>
                            <p class="font-medium text-gray-800 mb-2">Pengeluaran</p>
                            <div class="border rounded-lg divide-y">
                                @foreach($selectedShift->expenses as $exp)
                                    <div class="p-3 flex justify-between">
                                        <span class="text-gray-600">{{ $exp->description }}</span>
                                        <span class="text-red-600 font-medium">-Rp {{ number_format($exp->amount, 0, ',', '.') }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($selectedShift->notes)
                        <div class="bg-yellow-50 p-4 rounded-lg">
                            <p class="text-sm text-yellow-700 font-medium mb-1">Catatan Tutup Shift:</p>
                            <p class="text-yellow-800">{{ $selectedShift->notes }}</p>
                        </div>
                    @endif
                </div>
                <div class="p-4 bg-gray-50 border-t flex justify-end">
                    <button wire:click="$set('showDetailModal', false)" class="px-6 py-2 bg-white border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors shadow-sm">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
@script
<script>lucide.createIcons();Livewire.hook('morph.updated',()=>lucide.createIcons());</script>
@endscript
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
```
