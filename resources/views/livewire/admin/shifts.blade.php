<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Shift Hari Ini</h1>
            <p class="text-gray-500">{{ now()->locale('id')->isoFormat('dddd, D MMMM Y') }}</p>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Total Penjualan Hari Ini</p>
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
                    <p class="text-gray-500 text-sm">Total Pengeluaran Hari Ini</p>
                    <p class="text-2xl font-bold text-red-600">Rp {{ number_format($totalExpenses, 0, ',', '.') }}</p>
                </div>
                <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                    <i data-lucide="trending-down" class="w-6 h-6 text-red-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Shifts List -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Kasir</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Waktu</th>
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
                        $shiftSales = $shift->transactions->where('status', 'completed')->sum('total');
                        $shiftExpenses = $shift->expenses->sum('amount');
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center">
                                    <i data-lucide="user" class="w-5 h-5 text-primary-600"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800">{{ $shift->user->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $shift->transactions->count() }} transaksi</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-gray-800">{{ $shift->started_at->format('H:i') }}</p>
                            @if($shift->ended_at)
                                <p class="text-sm text-gray-400">s/d {{ $shift->ended_at->format('H:i') }}</p>
                            @else
                                <p class="text-sm text-green-500">Masih aktif</p>
                            @endif
                        </td>
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
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                    <i data-lucide="play" class="w-3 h-3"></i> Aktif
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                    <i data-lucide="check" class="w-3 h-3"></i> Ditutup
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button wire:click="viewDetail({{ $shift->id }})" class="p-2 text-gray-400 hover:text-primary-600 rounded-lg hover:bg-gray-100">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                            <i data-lucide="clock" class="w-16 h-16 mx-auto mb-4 text-gray-300"></i>
                            <p>Belum ada shift hari ini</p>
                            <p class="text-sm mt-2">Buat transaksi di POS untuk memulai shift</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="text-sm text-gray-500 mt-4">
        <i data-lucide="info" class="w-4 h-4 inline"></i>
        Shift kemarin dan sebelumnya bisa dilihat di menu <a href="{{ route('admin.reports.shifts') }}" class="text-primary-600 underline">Laporan Shift</a>
    </p>

    <!-- Detail Modal -->
    @if($showDetailModal && $selectedShift)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center overflow-y-auto py-8" wire:click.self="$set('showDetailModal', false)">
            <div class="bg-white rounded-2xl w-full max-w-2xl mx-4 shadow-2xl overflow-hidden">
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
                        <div><p class="text-sm text-gray-500">Selesai</p><p class="font-medium">{{ $selectedShift->ended_at?->format('H:i') ?? 'Masih aktif' }}</p></div>
                    </div>
                    
                    <div class="grid grid-cols-3 gap-4">
                        <div class="bg-blue-50 p-4 rounded-lg text-center">
                            <p class="text-sm text-blue-600">Modal Awal</p>
                            <p class="text-lg font-bold text-blue-700">Rp {{ number_format($selectedShift->opening_cash, 0, ',', '.') }}</p>
                        </div>
                        <div class="bg-green-50 p-4 rounded-lg text-center">
                            <p class="text-sm text-green-600">Penjualan Cash</p>
                            <p class="text-lg font-bold text-green-700">Rp {{ number_format($selectedShift->transactions->where('status', 'completed')->where('payment_method', 'cash')->sum('total'), 0, ',', '.') }}</p>
                        </div>
                        <div class="bg-red-50 p-4 rounded-lg text-center">
                            <p class="text-sm text-red-600">Pengeluaran</p>
                            <p class="text-lg font-bold text-red-700">Rp {{ number_format($selectedShift->expenses->sum('amount'), 0, ',', '.') }}</p>
                        </div>
                    </div>

                    @if($selectedShift->expenses->isNotEmpty())
                        <div>
                            <p class="font-medium text-gray-800 mb-2">Rincian Pengeluaran</p>
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

                    @if($selectedShift->status === 'closed')
                        <!-- Cash Reconciliation -->
                        <div class="border rounded-xl overflow-hidden">
                            <div class="bg-green-50 px-4 py-2 border-b">
                                <p class="text-xs font-semibold text-green-700 uppercase tracking-wide">💵 Rekonsiliasi Tunai (Cash)</p>
                            </div>
                            <div class="grid grid-cols-2 divide-x border-b">
                                <div class="p-3 text-center">
                                    <p class="text-xs text-gray-500">Ekspektasi Sistem</p>
                                    <p class="font-bold text-gray-800">Rp {{ number_format($selectedShift->expected_cash, 0, ',', '.') }}</p>
                                </div>
                                <div class="p-3 text-center">
                                    <p class="text-xs text-gray-500">Fisik di Laci</p>
                                    <p class="font-bold text-gray-800">Rp {{ number_format($selectedShift->actual_cash, 0, ',', '.') }}</p>
                                </div>
                            </div>
                            <div class="px-4 py-2 bg-{{ $selectedShift->cash_difference >= 0 ? 'green' : 'red' }}-50 text-center">
                                <span class="text-sm font-bold text-{{ $selectedShift->cash_difference >= 0 ? 'green' : 'red' }}-700">
                                    Selisih Tunai: {{ $selectedShift->cash_difference >= 0 ? '+' : '' }}Rp {{ number_format($selectedShift->cash_difference, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>

                        <!-- Non-Cash Reconciliation -->
                        <div class="border rounded-xl overflow-hidden">
                            <div class="bg-purple-50 px-4 py-2 border-b">
                                <p class="text-xs font-semibold text-purple-700 uppercase tracking-wide">📱 Rekonsiliasi Non-Tunai (QRIS / Transfer / EDC)</p>
                            </div>
                            <div class="grid grid-cols-2 divide-x border-b">
                                <div class="p-3 text-center">
                                    <p class="text-xs text-gray-500">Sistem (Catatan)</p>
                                    <p class="font-bold text-purple-700">Rp {{ number_format($selectedShift->expected_non_cash ?? 0, 0, ',', '.') }}</p>
                                </div>
                                <div class="p-3 text-center">
                                    <p class="text-xs text-gray-500">Terverifikasi Kasir</p>
                                    <p class="font-bold text-gray-800">Rp {{ number_format($selectedShift->actual_non_cash ?? 0, 0, ',', '.') }}</p>
                                </div>
                            </div>
                            @if($selectedShift->non_cash_difference !== null)
                            <div class="px-4 py-2 bg-{{ $selectedShift->non_cash_difference >= 0 ? 'purple' : 'orange' }}-50 text-center">
                                <span class="text-sm font-bold text-{{ $selectedShift->non_cash_difference >= 0 ? 'purple' : 'orange' }}-700">
                                    Selisih Non-Tunai: {{ $selectedShift->non_cash_difference >= 0 ? '+' : '' }}Rp {{ number_format($selectedShift->non_cash_difference, 0, ',', '.') }}
                                </span>
                            </div>
                            @endif
                        </div>
                    @endif

                    @if($selectedShift->notes)
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-500 mb-1">Catatan Tutup Shift:</p>
                            <p class="text-gray-700">{{ $selectedShift->notes }}</p>
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
