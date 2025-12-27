<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Laporan Retur</h1>
            <p class="text-gray-500">History pengembalian produk</p>
        </div>
        <!-- Button removed as requested -->
    </div>

    <!-- Summary -->
    <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Total Retur Hari Ini</p>
                <p class="text-2xl font-bold text-red-600">Rp {{ number_format($todayTotal, 0, ',', '.') }}</p>
            </div>
            <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                <i data-lucide="rotate-ccw" class="w-6 h-6 text-red-600"></i>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6 p-4 flex gap-4">
        <input type="date" wire:model.live="filterDate" class="px-4 py-2 border border-gray-200 rounded-lg">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nomor retur..." class="flex-1 px-4 py-2 border border-gray-200 rounded-lg">
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
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
                        <td class="px-6 py-4 text-gray-600">{{ Str::limit($return->reason, 30) }}</td>
                        <td class="px-6 py-4 text-gray-500">{{ $return->created_at->format('H:i') }}</td>
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
        @if($returns->hasPages())
            <div class="px-6 py-4 border-t">{{ $returns->links() }}</div>
        @endif
    </div>



    <!-- Detail Modal -->
    @if($showDetailModal && $selectedReturn)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center overflow-y-auto py-8">
            <div class="bg-white rounded-2xl w-full max-w-md mx-4 shadow-2xl">
                <div class="p-6 border-b flex justify-between items-center">
                    <h3 class="text-xl font-bold text-gray-800">Detail Retur</h3>
                    <button wire:click="$set('showDetailModal', false)" class="text-gray-400 hover:text-gray-600">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <div class="bg-gray-50 rounded-lg p-4 grid grid-cols-2 gap-4">
                        <div><p class="text-xs text-gray-500">No. Retur</p><p class="font-medium">{{ $selectedReturn->return_number }}</p></div>
                        <div><p class="text-xs text-gray-500">Invoice</p><p class="font-medium">{{ $selectedReturn->transaction->invoice_number }}</p></div>
                        <div><p class="text-xs text-gray-500">Kasir</p><p class="font-medium">{{ $selectedReturn->user->name }}</p></div>
                        <div><p class="text-xs text-gray-500">Waktu</p><p class="font-medium">{{ $selectedReturn->created_at->format('d/m/Y H:i') }}</p></div>
                    </div>

                    <div>
                        <p class="font-medium text-gray-800 mb-2">Item Diretur</p>
                        <div class="border rounded-lg divide-y">
                            @foreach($selectedReturn->items as $item)
                                <div class="p-3 flex justify-between">
                                    <div>
                                        <p class="font-medium text-gray-800">{{ $item->product_name }}</p>
                                        <p class="text-sm text-gray-500">{{ $item->quantity }} x Rp {{ number_format($item->unit_price, 0, ',', '.') }}</p>
                                    </div>
                                    <p class="font-medium text-red-600">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="bg-red-50 rounded-lg p-4 text-center">
                        <p class="text-sm text-red-600">Total Refund</p>
                        <p class="text-2xl font-bold text-red-700">Rp {{ number_format($selectedReturn->total_refund, 0, ',', '.') }}</p>
                    </div>

                    <div class="bg-yellow-50 rounded-lg p-4">
                        <p class="text-xs text-yellow-600 font-medium">Alasan:</p>
                        <p class="text-yellow-800">{{ $selectedReturn->reason }}</p>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@script
<script>lucide.createIcons();Livewire.hook('morph.updated',()=>lucide.createIcons());</script>
@endscript
