<div>
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
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6 p-4">
        <div class="flex gap-4">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Tanggal</label>
                <input type="date" wire:model.live="filterDate" class="px-4 py-2 border border-gray-200 rounded-lg">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Kasir</label>
                <select wire:model.live="filterUserId" class="px-4 py-2 border border-gray-200 rounded-lg">
                    <option value="">Semua Kasir</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full">
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
                        $shiftSales = $shift->transactions?->where('status', 'completed')->sum('total') ?? 0;
                        $shiftExpenses = $shift->expenses?->sum('amount') ?? 0;
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
                            @if($shift->cash_difference !== null)
                                <span class="{{ $shift->cash_difference >= 0 ? 'text-green-600' : 'text-red-600' }} font-medium">
                                    {{ $shift->cash_difference >= 0 ? '+' : '' }}Rp {{ number_format($shift->cash_difference, 0, ',', '.') }}
                                </span>
                            @else
                                -
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
                            <button wire:click="viewDetail({{ $shift->id }})" class="p-2 text-gray-400 hover:text-primary-600 rounded-lg hover:bg-gray-100">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
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
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center overflow-y-auto py-8">
            <div class="bg-white rounded-2xl w-full max-w-2xl mx-4 shadow-2xl">
                <div class="p-6 border-b flex justify-between items-center">
                    <h3 class="text-xl font-bold text-gray-800">Detail Shift</h3>
                    <button wire:click="$set('showDetailModal', false)" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-6 h-6"></i></button>
                </div>
                <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                    <div class="grid grid-cols-2 gap-4 bg-gray-50 rounded-lg p-4">
                        <div><p class="text-sm text-gray-500">Kasir</p><p class="font-medium">{{ $selectedShift->user->name }}</p></div>
                        <div><p class="text-sm text-gray-500">Tanggal</p><p class="font-medium">{{ $selectedShift->started_at->format('d M Y') }}</p></div>
                        <div><p class="text-sm text-gray-500">Mulai</p><p class="font-medium">{{ $selectedShift->started_at->format('H:i') }}</p></div>
                        <div><p class="text-sm text-gray-500">Selesai</p><p class="font-medium">{{ $selectedShift->ended_at?->format('H:i') ?? '-' }}</p></div>
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <div class="bg-blue-50 p-4 rounded-lg text-center">
                            <p class="text-sm text-blue-600">Modal Awal</p>
                            <p class="text-lg font-bold text-blue-700">Rp {{ number_format($selectedShift->opening_cash, 0, ',', '.') }}</p>
                        </div>
                        <div class="bg-green-50 p-4 rounded-lg text-center">
                            <p class="text-sm text-green-600">Penjualan</p>
                            <p class="text-lg font-bold text-green-700">Rp {{ number_format($selectedShift->transactions->where('status', 'completed')->sum('total'), 0, ',', '.') }}</p>
                        </div>
                        <div class="bg-red-50 p-4 rounded-lg text-center">
                            <p class="text-sm text-red-600">Pengeluaran</p>
                            <p class="text-lg font-bold text-red-700">Rp {{ number_format($selectedShift->expenses->sum('amount'), 0, ',', '.') }}</p>
                        </div>
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

                    @if($selectedShift->close_notes)
                        <div class="bg-yellow-50 p-4 rounded-lg">
                            <p class="text-sm text-yellow-700 font-medium mb-1">Catatan Tutup Shift:</p>
                            <p class="text-yellow-800">{{ $selectedShift->close_notes }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
@script
<script>lucide.createIcons();Livewire.hook('morph.updated',()=>lucide.createIcons());</script>
@endscript
