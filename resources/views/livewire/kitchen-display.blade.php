<div class="min-h-screen bg-gray-50 flex flex-col">
    @if(!$selectedShiftId)
        <!-- Shift Selection Screen -->
        <div class="flex-1 flex flex-col items-center justify-center p-6 bg-gradient-to-br from-blue-600 to-blue-800 text-white">
            <div class="max-w-md w-full">
                <div class="text-center mb-10">
                    <div class="w-20 h-20 bg-white/20 backdrop-blur rounded-2xl flex items-center justify-center mx-auto mb-6">
                        <x-lucide name="chef-hat" class="w-10 h-10" />
                    </div>
                    <h1 class="text-3xl font-bold mb-2">Dapur / Pelayanan</h1>
                    <p class="text-blue-100">Pilih Shift Kasir yang Aktif</p>
                </div>

                <div class="bg-white rounded-2xl shadow-xl overflow-hidden text-gray-800">
                    <div class="p-4 bg-gray-50 border-b border-gray-100 font-semibold text-gray-500 uppercase text-xs tracking-wider">
                        Shift Aktif ({{ $shifts->count() }})
                    </div>
                    @if($shifts->count() > 0)
                        <div class="divide-y divide-gray-100">
                            @foreach($shifts as $shift)
                                <button 
                                    wire:click="selectShift({{ $shift->id }})"
                                    class="w-full text-left p-4 hover:bg-blue-50 transition-colors flex items-center justify-between group"
                                >
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold">
                                            {{ substr($shift->user->name, 0, 1) }}
                                        </div>
                                        <div>
                                            <h3 class="font-bold text-gray-800 group-hover:text-blue-700">{{ $shift->user->name }}</h3>
                                            <div class="flex items-center gap-2 text-xs text-gray-500 mt-0.5">
                                                <x-lucide name="clock" class="w-3 h-3" />
                                                {{ $shift->created_at->format('H:i') }}
                                                <span>&bull;</span>
                                                <span>{{ $shift->initial_cash_formatted }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <x-lucide name="chevron-right" class="w-5 h-5 text-gray-300 group-hover:text-blue-500" />
                                </button>
                            @endforeach
                        </div>
                    @else
                        <div class="p-8 text-center text-gray-500">
                            <x-lucide name="coffee" class="w-12 h-12 mx-auto mb-3 text-gray-300" />
                            <p>Tidak ada shift yang aktif.</p>
                            <p class="text-xs mt-1">Pastikan kasir sudah membuka shift.</p>
                        </div>
                    @endif
                </div>
                
                <div class="mt-8 text-center">
                    <a href="{{ route('admin.dashboard') }}" class="text-blue-200 hover:text-white text-sm flex items-center justify-center gap-2">
                        <x-lucide name="arrow-left" class="w-4 h-4" /> Kembali ke Dashboard
                    </a>
                </div>
            </div>
        </div>
    @else
        <!-- Header -->
        <header class="bg-white border-b border-gray-200 px-4 md:px-6 py-4 flex flex-wrap items-center justify-between gap-4 sticky top-0 z-50 shadow-sm">
            <div class="flex flex-wrap items-center gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-primary-600 rounded-lg flex items-center justify-center text-white">
                         <x-lucide name="chef-hat" class="w-6 h-6" />
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-800">Dapur / Pelayanan</h1>
                        <p class="text-xs text-gray-500">{{ now()->format('d M Y, H:i') }}</p>
                    </div>
                </div>
                
                <div class="flex bg-gray-100 p-1 rounded-lg">
                    <button 
                        wire:click="setTab('food')"
                        class="px-4 md:px-6 py-2 rounded-md text-sm font-medium transition-all {{ $activeTab === 'food' ? 'bg-white text-primary-600 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
                    >
                        <x-lucide name="utensils" class="w-4 h-4 inline-block mr-2" />
                        Makanan
                    </button>
                    <button 
                        wire:click="setTab('drink')"
                        class="px-4 md:px-6 py-2 rounded-md text-sm font-medium transition-all {{ $activeTab === 'drink' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
                    >
                        <x-lucide name="cup-soda" class="w-4 h-4 inline-block mr-2" />
                        Minuman
                    </button>
                </div>
            </div>
    
            <div class="flex items-center gap-3">
                 <div wire:loading class="text-sm text-gray-500 flex items-center gap-2">
                    <x-lucide name="loader" class="w-4 h-4 animate-spin" />
                    Memuat...
                </div>
                
                <!-- Change Shift Button -->
                <button 
                    wire:click="changeShift"
                    class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-3 py-2 rounded-lg text-sm flex items-center gap-2 transition-colors"
                >
                    <x-lucide name="users" class="w-4 h-4" />
                    Ganti Shift
                </button>
                
                <a href="{{ route('admin.dashboard') }}" class="text-gray-400 hover:text-gray-600 ml-2">
                    <x-lucide name="log-out" class="w-5 h-5" />
                </a>
            </div>
        </header>
    
        <!-- Content -->
        <main class="flex-1 p-6 overflow-y-auto">
            @if($orders->count() > 0)
                @php
                    // Get the ID of the oldest transaction (first in the collection due to sorting)
                    $activeTransactionId = $orders->keys()->first();
                @endphp
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 pb-6">
                    @foreach($orders as $transactionId => $items)
                        @php
                            $isActive = $transactionId === $activeTransactionId;
                            $isBlocked = !$isActive;
                        @endphp
                        <!-- Order Card -->
                        <div class="flex-shrink-0 bg-white rounded-xl shadow-sm border border-gray-200 flex flex-col h-full animate-in fade-in slide-in-from-bottom-4 duration-300 {{ $isBlocked ? 'opacity-60 grayscale-[0.5]' : 'ring-2 ring-primary-500 shadow-md transform scale-[1.02]' }} transition-all">
                            <!-- Header -->
                            <div class="p-4 border-b border-gray-100 bg-gray-50 rounded-t-xl flex justify-between items-start">
                                <div>
                                    <h3 class="font-bold text-lg text-gray-800">
                                        #{{ $items->first()->transaction->queue_display ?? '---' }}
                                    </h3>
                                    <p class="text-xs text-gray-500 font-mono mt-1">{{ $items->first()->transaction->invoice_number }}</p>
                                    <div class="flex flex-col gap-1.5 mt-2">
                                        <span class="inline-flex w-fit px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide {{ $items->first()->transaction->order_type === 'dine_in' ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700' }}">
                                            {{ $items->first()->transaction->order_type === 'dine_in' ? 'Dine In' : 'Take Away' }}
                                        </span>
                                        @if($items->first()->transaction->order_type === 'dine_in' && $items->first()->transaction->serviceArea)
                                            <span class="inline-flex w-fit px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700 uppercase tracking-wide">
                                                Meja: {{ $items->first()->transaction->serviceArea->name }}
                                            </span>
                                        @endif
                                        @if($items->first()->transaction->customer_name)
                                            <span class="inline-flex w-fit px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-700 uppercase tracking-wide truncate max-w-[150px]" title="{{ $items->first()->transaction->customer_name }}">
                                                Cust: {{ $items->first()->transaction->customer_name }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs font-semibold text-gray-400 block">
                                        {{ $items->first()->transaction->created_at->format('H:i') }}
                                    </span>
                                    <span class="text-xs text-gray-400 block mt-1">
                                        {{ $items->first()->transaction->created_at->diffForHumans() }}
                                    </span>
                                    @if($items->first()->transaction->user)
                                        <div class="mt-4 text-xs text-gray-400 text-right border-t border-gray-100 pt-1">
                                            <x-lucide name="user" class="w-3 h-3 inline" /> {{ $items->first()->transaction->user->name }}
                                        </div>
                                    @endif
                                </div>
                            </div>
    
                            <!-- Items List -->
                            <div class="p-4 flex-1 space-y-3">
                                @foreach($items as $item)
                                    <div class="p-3 border border-gray-100 rounded-lg {{ $item->isDone() ? 'opacity-50 bg-gray-50' : 'bg-white' }}">
                                        <div class="flex justify-between items-start gap-2">
                                            <div class="flex-1">
                                                <div class="flex items-start gap-2">
                                                    <span class="font-bold text-lg text-primary-600 min-w-[24px]">{{ $item->quantity }}x</span>
                                                    <div>
                                                        <h4 class="font-medium text-gray-800 leading-tight">{{ $item->product_name }}</h4>
                                                        @if($item->modifiers->count() > 0)
                                                            <p class="text-xs text-gray-500 mt-1 italic">
                                                                + {{ $item->modifier_names }}
                                                            </p>
                                                        @endif
                                                        @if($item->notes)
                                                            <p class="text-xs text-red-500 mt-1 bg-red-50 p-1 rounded inline-block">
                                                                Catatan: {{ $item->notes }}
                                                            </p>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-3 pt-3 border-t border-gray-50 flex justify-end">
                                            <button 
                                                @if(!$isBlocked) @click="$dispatch('confirm-done', { id: {{ $item->id }} })" @endif
                                                wire:loading.attr="disabled"
                                                @if($isBlocked) disabled @endif
                                                class="w-full py-2 text-sm font-medium rounded-lg transition-colors flex items-center justify-center gap-2 group
                                                {{ $isBlocked 
                                                    ? 'bg-gray-100 text-gray-400 cursor-not-allowed' 
                                                    : 'bg-gray-100 hover:bg-green-600 hover:text-white text-gray-600' 
                                                }}"
                                            >
                                                @if($isBlocked)
                                                    <x-lucide name="lock" class="w-4 h-4" />
                                                    <span>Antri</span>
                                                @else
                                                    <span>Selesai</span>
                                                    <x-lucide name="check" class="w-4 h-4 group-hover:scale-110 transition-transform" />
                                                @endif
                                            </button>
                                        </div>
                                    </div>
                                @endforeach

                                @if($items->first()->transaction->notes)
                                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-2.5 text-xs text-yellow-800 mt-2">
                                        <div class="font-bold flex items-center gap-1 mb-1">
                                            <x-lucide name="sticky-note" class="w-3.5 h-3.5" />
                                            Catatan Transaksi:
                                        </div>
                                        <p class="whitespace-pre-wrap">{{ $items->first()->transaction->notes }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="flex flex-col items-center justify-center h-[calc(100vh-200px)] text-gray-400">
                    <x-lucide name="coffee" class="w-16 h-16 mb-4 text-gray-300" />
                    <h3 class="text-xl font-semibold text-gray-500">Tidak ada pesanan {{ $activeTab === 'food' ? 'makanan' : 'minuman' }}</h3>
                    <p class="text-sm">Semua pesanan sudah diselesaikan.</p>
                </div>
            @endif
</main>

    <!-- Custom Confirmation Modal -->
    <div 
        x-data="{ show: false, itemId: null }"
        @confirm-done.window="show = true; itemId = $event.detail.id"
        x-show="show"
        x-cloak
        class="fixed inset-0 z-[60] overflow-y-auto"
        aria-labelledby="modal-title" 
        role="dialog" 
        aria-modal="true"
    >
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Backdrop -->
            <div 
                x-show="show"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                @click="show = false"
                aria-hidden="true"
            ></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div 
                x-show="show"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full"
            >
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
                            <x-lucide name="check-circle" class="h-6 w-6 text-green-600" />
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Selesaikan Pesanan?
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Pastikan menu sudah dimasak / disiapkan dengan benar dan siap dihidangkan.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button 
                        type="button" 
                        @click="$wire.markAsDone(itemId); show = false"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm"
                    >
                        Ya, Selesai
                    </button>
                    <button 
                        type="button" 
                        @click="show = false"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                    >
                        Batal
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@script
<script>
    let pollTimeout = null;

    const scheduleNextPoll = () => {
        if (pollTimeout) clearTimeout(pollTimeout);

        // If no shift is selected yet, check again in 2 seconds
        if (!$wire.selectedShiftId) {
            pollTimeout = setTimeout(runPoll, 2000);
            return;
        }

        // If the tab is hidden, slow down polling to 30s to conserve server resources
        if (document.hidden) {
            pollTimeout = setTimeout(runPoll, 30000);
            return;
        }

        // Base interval: 6 seconds. Jitter: random offset between -1.5s and +1.5s
        const baseTime = 6000;
        const jitter = (Math.random() * 3000) - 1500;
        const delay = Math.max(3000, baseTime + jitter); // Keep it above 3s

        pollTimeout = setTimeout(runPoll, delay);
    };

    const runPoll = () => {
        // Skip polling if no shift is active/selected
        if (!$wire.selectedShiftId) {
            scheduleNextPoll();
            return;
        }

        console.log('Kitchen polling...');
        try {
            const refreshPromise = $wire.$refresh();
            if (refreshPromise && typeof refreshPromise.then === 'function') {
                refreshPromise.then(() => {
                    scheduleNextPoll();
                }).catch((err) => {
                    console.error('Kitchen poll failed, backing off...', err);
                    pollTimeout = setTimeout(runPoll, 12000);
                });
            } else {
                // Fallback if Livewire $refresh doesn't return a promise
                scheduleNextPoll();
            }
        } catch (err) {
            console.error('Kitchen poll execution failed, backing off...', err);
            pollTimeout = setTimeout(runPoll, 12000);
        }
    };

    // Start polling loop
    scheduleNextPoll();

    // Pause/slow down when tab is hidden, refresh instantly when tab gets focus
    document.addEventListener('visibilitychange', () => {
        if (pollTimeout) clearTimeout(pollTimeout);
        
        if (!document.hidden) {
            console.log('Kitchen tab active - refreshing immediately');
            runPoll();
        } else {
            console.log('Kitchen tab backgrounded - slowing down poll');
            scheduleNextPoll();
        }
    });

    document.addEventListener('livewire:init', () => {
        // Run immediately when Livewire is initialized
        if(window.lucide) window.lucide.createIcons();

        // Run after every commit (polling, actions, etc)
        Livewire.hook('commit', ({ component, commit, respond, succeed, fail }) => {
            succeed(({ snapshot, effect }) => {
                // Wait for DOM to update
                setTimeout(() => {
                    if(window.lucide) window.lucide.createIcons();
                }, 10);
            })
        });

        // Also hook into morphing for granular updates
        Livewire.hook('morph.updated', ({ el, component }) => {
            if(window.lucide) window.lucide.createIcons();
        });
    });

    // Fallback for purely DOM content loaded
    document.addEventListener('DOMContentLoaded', () => {
        if(window.lucide) window.lucide.createIcons();
    });
</script>
@endscript
