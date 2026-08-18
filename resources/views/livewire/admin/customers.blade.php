<div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Pelanggan</h1>
            <p class="text-gray-500">Daftar pelanggan unik (berdasarkan nomor HP) dari POS & Self Order</p>
        </div>
        @can('manage_phone_blacklist')
        <button wire:click="openBlockModal" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg flex items-center gap-2">
            <i data-lucide="shield-ban" class="w-5 h-5"></i>
            Blokir Nomor
        </button>
        @endcan
    </div>

    <!-- Tabs -->
    <div class="flex gap-2 mb-6 border-b border-gray-200">
        <button wire:click="switchTab('customers')"
            class="px-4 py-2 text-sm font-medium border-b-2 -mb-px {{ $tab === 'customers' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            Semua Pelanggan
        </button>
        @can('view_phone_blacklist')
        <button wire:click="switchTab('blacklist')"
            class="px-4 py-2 text-sm font-medium border-b-2 -mb-px {{ $tab === 'blacklist' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            Blacklist Nomor
        </button>
        @endcan
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6 p-4 flex gap-4">
        <div class="relative flex-1 min-w-[200px]">
            <input type="text" wire:model.live.debounce.300ms="search"
                placeholder="{{ $tab === 'blacklist' ? 'Cari nomor atau alasan...' : 'Cari nomor HP atau nama...' }}"
                class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
        </div>
        @if($tab === 'customers')
            <select wire:model.live="sourceFilter" class="px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500">
                <option value="">Semua Sumber</option>
                <option value="manual">POS (Manual)</option>
                <option value="self_order">Self Order</option>
            </select>
        @else
            <select wire:model.live="blacklistStatus" class="px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500">
                <option value="blocked">Diblokir</option>
                <option value="unblocked">Dibuka</option>
                <option value="all">Semua Status</option>
            </select>
        @endif
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto relative">
        <div wire:loading.flex wire:target="search, sourceFilter, blacklistStatus, gotoPage, previousPage, nextPage, switchTab" class="absolute inset-0 bg-white/70 z-10 items-center justify-center">
            <div class="flex flex-col items-center gap-2">
                <svg class="animate-spin h-8 w-8 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm text-gray-600 font-medium">Memuat data...</span>
            </div>
        </div>

        @if($tab === 'customers')
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Nomor HP</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Nama</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Email</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Total Order</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Order Terakhir</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Sumber</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Status</th>
                        @can('manage_phone_blacklist')
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                        @endcan
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($customers as $customer)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-mono text-sm text-gray-800">{{ $customer->phone }}</td>
                            <td class="px-6 py-4 text-gray-700">
                                @if(!empty($customer->name))
                                    {{ $customer->name }}
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-500 text-sm">{{ $customer->email ?: '-' }}</td>
                            <td class="px-6 py-4 text-center text-gray-700">{{ $customer->total_orders }}</td>
                            <td class="px-6 py-4 text-gray-500 text-sm">{{ \Carbon\Carbon::parse($customer->last_order_at)->format('d M Y H:i') }}</td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-1 flex-wrap">
                                    @if($customer->has_manual)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">POS</span>
                                    @endif
                                    @if($customer->has_self_order)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700">Self Order</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($blockedMap->has($customer->phone))
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                        <i data-lucide="shield-ban" class="w-3 h-3"></i> Diblokir
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                        <i data-lucide="check-circle" class="w-3 h-3"></i> Normal
                                    </span>
                                @endif
                            </td>
                            @can('manage_phone_blacklist')
                            <td class="px-6 py-4 text-right">
                                @if(!$blockedMap->has($customer->phone))
                                    <button wire:click="openBlockModal('{{ $customer->phone }}')" class="p-2 text-gray-400 hover:text-red-600 rounded-lg hover:bg-gray-100" title="Blokir nomor ini">
                                        <i data-lucide="shield-ban" class="w-4 h-4"></i>
                                    </button>
                                @endif
                            </td>
                            @endcan
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                <i data-lucide="users" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                                <p>Belum ada data pelanggan</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if($customers->hasPages())
                <div class="px-6 py-4 border-t">{{ $customers->links() }}</div>
            @endif
        @else
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Nomor HP</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Alasan</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Sumber</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Diblokir Oleh</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Status</th>
                        @can('manage_phone_blacklist')
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                        @endcan
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($blacklist as $entry)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-mono text-sm text-gray-800">{{ $entry->phone }}</td>
                            <td class="px-6 py-4 text-gray-600 text-sm">{{ $entry->reason ?: '-' }}</td>
                            <td class="px-6 py-4 text-center">
                                @if($entry->auto_blocked)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                                        <i data-lucide="bot" class="w-3 h-3"></i> Otomatis
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                        <i data-lucide="user" class="w-3 h-3"></i> Manual
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-500 text-sm">
                                {{ $entry->is_blocked ? ($entry->blockedBy?->name ?? 'Sistem') : ($entry->unblockedBy?->name ?? '-') }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($entry->is_blocked)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                        <i data-lucide="shield-ban" class="w-3 h-3"></i> Diblokir
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                        <i data-lucide="shield-check" class="w-3 h-3"></i> Dibuka
                                    </span>
                                @endif
                            </td>
                            @can('manage_phone_blacklist')
                            <td class="px-6 py-4 text-right">
                                @if($entry->is_blocked)
                                    <button wire:click="confirmUnblock({{ $entry->id }})" class="p-2 text-gray-400 hover:text-green-600 rounded-lg hover:bg-gray-100" title="Buka blokir">
                                        <i data-lucide="shield-check" class="w-4 h-4"></i>
                                    </button>
                                @else
                                    <button wire:click="openBlockModal('{{ $entry->phone }}')" class="p-2 text-gray-400 hover:text-red-600 rounded-lg hover:bg-gray-100" title="Blokir lagi">
                                        <i data-lucide="shield-ban" class="w-4 h-4"></i>
                                    </button>
                                @endif
                            </td>
                            @endcan
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <i data-lucide="shield" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                                <p>Belum ada nomor yang diblokir</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if($blacklist->hasPages())
                <div class="px-6 py-4 border-t">{{ $blacklist->links() }}</div>
            @endif
        @endif
    </div>

    <!-- Block Modal -->
    @if($showBlockModal)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center" x-data x-on:keydown.escape.window="$wire.closeBlockModal()">
            <div class="bg-white rounded-2xl w-full max-w-lg mx-4 shadow-2xl">
                <div class="p-6 border-b flex justify-between items-center">
                    <h3 class="text-xl font-bold text-gray-800">Blokir Nomor HP</h3>
                    <button wire:click="closeBlockModal" class="text-gray-400 hover:text-gray-600">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
                <form wire:submit="confirmBlock" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nomor HP *</label>
                        <input type="text" wire:model="blockPhone" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500" placeholder="08123456789">
                        @error('blockPhone') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alasan</label>
                        <textarea wire:model="blockReason" rows="3" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500" placeholder="Opsional, mis. spam order / order fiktif"></textarea>
                        @error('blockReason') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex gap-3 pt-4">
                        <button type="button" wire:click="closeBlockModal" class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg">Batal</button>
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            class="flex-1 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg disabled:opacity-50 disabled:cursor-not-allowed"
                        >Blokir</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Unblock Confirmation Modal -->
    @if($showUnblockModal)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center" x-data x-on:keydown.escape.window="$wire.cancelUnblock()">
            <div class="bg-white rounded-2xl w-full max-w-md mx-4 shadow-2xl p-6">
                <div class="text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="shield-check" class="w-8 h-8 text-green-600"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Buka Blokir Nomor?</h3>
                    <p class="text-gray-500 mb-6">Nomor <span class="font-mono font-semibold text-gray-700">{{ $unblockingPhone }}</span> akan bisa order lagi lewat Self Order.</p>
                    <div class="flex gap-3">
                        <button wire:click="cancelUnblock" class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg">Batal</button>
                        <button
                            wire:click="unblock"
                            wire:loading.attr="disabled"
                            class="flex-1 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg disabled:opacity-50 disabled:cursor-not-allowed"
                        >Ya, Buka Blokir</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@script
<script>
    lucide.createIcons();
    Livewire.hook('morph.updated', () => {
        setTimeout(() => lucide.createIcons(), 50);
    });
    Livewire.hook('commit', ({ succeed }) => {
        succeed(() => {
            setTimeout(() => lucide.createIcons(), 100);
        });
    });
</script>
@endscript
