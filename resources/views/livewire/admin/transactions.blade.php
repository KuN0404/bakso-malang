<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Transaksi</h1>
            <p class="text-gray-500">Riwayat transaksi penjualan</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6 p-4">
        <div class="flex gap-4">
            <div class="flex-1 relative">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari invoice..." class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg">
                <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
            </div>
            <select wire:model.live="filterStatus" class="px-4 py-2 border border-gray-200 rounded-lg">
                <option value="">Semua Status</option>
                <option value="completed">Selesai</option>
                <option value="pending">Pending</option>
                <option value="cancelled">Dibatalkan</option>
            </select>
            <input type="date" wire:model.live="filterDate" class="px-4 py-2 border border-gray-200 rounded-lg">
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Invoice</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Waktu</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Pelanggan</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Pembayaran</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Total</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($transactions as $trx)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium text-gray-800">{{ $trx->invoice_number }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $trx->created_at->format('d M Y, H:i') }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $trx->customer_name ?: '-' }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $trx->paymentSource?->name ?? $trx->payment_method }}</td>
                        <td class="px-6 py-4 font-medium text-gray-800">Rp {{ number_format($trx->total, 0, ',', '.') }}</td>
                        <td class="px-6 py-4">
                            @if($trx->status === 'completed')
                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">Selesai</span>
                            @elseif($trx->status === 'pending')
                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">Pending</span>
                            @else
                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">Dibatalkan</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button wire:click="view({{ $trx->id }})" class="p-2 text-gray-400 hover:text-primary-600 rounded-lg hover:bg-gray-100">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                            @if($trx->status !== 'cancelled')
                                <button 
                                    @click="$dispatch('confirm-action', {
                                        title: 'Batalkan Transaksi',
                                        message: 'Apakah Anda yakin ingin membatalkan transaksi {{ $trx->invoice_number }}?',
                                        confirmText: 'Ya, Batalkan',
                                        type: 'danger',
                                        action: { componentId: $wire.__instance.id, method: 'cancel' },
                                        params: {{ $trx->id }}
                                    })"
                                    class="p-2 text-gray-400 hover:text-red-600 rounded-lg hover:bg-gray-100"
                                >
                                    <i data-lucide="x-circle" class="w-4 h-4"></i>
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">Belum ada transaksi</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($transactions->hasPages())
            <div class="px-6 py-4 border-t">{{ $transactions->links() }}</div>
        @endif
    </div>

    @if($showModal && $selectedTransaction)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center overflow-y-auto py-8">
            <div class="bg-white rounded-2xl w-full max-w-2xl mx-4 shadow-2xl">
                <div class="p-6 border-b flex justify-between items-center">
                    <h3 class="text-xl font-bold text-gray-800">Detail Transaksi</h3>
                    <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-6 h-6"></i></button>
                </div>
                <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                    <div class="grid grid-cols-2 gap-4 bg-gray-50 rounded-lg p-4">
                        <div><p class="text-sm text-gray-500">Invoice</p><p class="font-medium">{{ $selectedTransaction->invoice_number }}</p></div>
                        <div><p class="text-sm text-gray-500">Antrian</p><p class="font-bold text-xl">{{ $selectedTransaction->queue_display }}</p></div>
                        <div><p class="text-sm text-gray-500">Kasir</p><p class="font-medium">{{ $selectedTransaction->user->name }}</p></div>
                        <div><p class="text-sm text-gray-500">Waktu</p><p class="font-medium">{{ $selectedTransaction->created_at->format('d M Y, H:i') }}</p></div>
                    </div>
                    <div class="border rounded-lg divide-y">
                        @foreach($selectedTransaction->details as $detail)
                            <div class="p-4">
                                <div class="flex justify-between">
                                    <span class="font-medium">{{ $detail->product_name }}</span>
                                    <span>Rp {{ number_format($detail->subtotal, 0, ',', '.') }}</span>
                                </div>
                                <p class="text-sm text-gray-500">{{ $detail->quantity }} x Rp {{ number_format($detail->unit_price, 0, ',', '.') }}</p>
                                @if($detail->modifiers->isNotEmpty())
                                    <p class="text-xs text-gray-400 mt-1">
                                        + {{ $detail->modifiers->pluck('pivot.modifier_name')->join(', ') }}
                                    </p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <div class="bg-primary-50 rounded-lg p-4">
                        <div class="flex justify-between text-lg font-bold">
                            <span>Total</span>
                            <span>Rp {{ number_format($selectedTransaction->total, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between text-sm text-gray-600 mt-2">
                            <span>Bayar ({{ $selectedTransaction->paymentSource?->name ?? $selectedTransaction->payment_method }})</span>
                            <span>Rp {{ number_format($selectedTransaction->paid_amount, 0, ',', '.') }}</span>
                        </div>
                        @if($selectedTransaction->change_amount > 0)
                            <div class="flex justify-between text-sm text-gray-600">
                                <span>Kembali</span>
                                <span>Rp {{ number_format($selectedTransaction->change_amount, 0, ',', '.') }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@script
<script>lucide.createIcons();Livewire.hook('morph.updated',()=>lucide.createIcons());</script>
@endscript
