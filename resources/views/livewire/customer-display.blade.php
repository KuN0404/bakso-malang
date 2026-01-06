<div class="h-screen bg-gray-50 flex flex-col overflow-hidden" wire:poll.5s="fetchState">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b px-6 py-4 flex justify-between items-center transition-all duration-300 z-10 flex-shrink-0">
        <div class="flex items-center gap-3">
            <div class="bg-primary-600 p-2 rounded-lg">
                <i data-lucide="monitor" class="w-6 h-6 text-white"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-gray-800">Layar Pelanggan</h1>
                @if($cashierName)
                    <p class="text-sm text-gray-500">Kasir: {{ $cashierName }}</p>
                @endif
            </div>
        </div>
        <div>
            @if($customerName)
                <span class="bg-blue-100 text-blue-800 px-4 py-2 rounded-full font-bold text-lg">
                    Pelanggan: {{ $customerName }}
                </span>
            @endif
        </div>
    </div>

    <!-- Content -->
    @if(empty($cart))
        <!-- EMPTY STATE: Full Screen -->
        <div class="flex-1 flex flex-col items-center justify-center bg-white text-center p-8 animate-fade-in overflow-hidden">
            <div class="w-64 h-64 bg-gray-50 rounded-full flex items-center justify-center mb-8 shadow-inner">
                <i data-lucide="shopping-bag" class="w-32 h-32 text-gray-300"></i>
            </div>
            <h2 class="text-4xl font-bold text-gray-800 mb-4 font-sans tracking-tight">Selamat Datang</h2>
            <p class="text-2xl text-gray-400 font-light">Silakan pesan menu favorit Anda</p>
            
            <div class="mt-12 flex gap-2">
                <div class="w-3 h-3 rounded-full bg-gray-200 animate-bounce"></div>
                <div class="w-3 h-3 rounded-full bg-gray-200 animate-bounce delay-100"></div>
                <div class="w-3 h-3 rounded-full bg-gray-200 animate-bounce delay-200"></div>
            </div>
        </div>
    @else
        <!-- ACTIVE STATE: Split View -->
        <div class="flex-1 flex overflow-hidden">
            <!-- LEFT: Items List (60%) -->
            <div id="items-container" data-item-count="{{ count($cart) }}" class="w-3/5 p-4 overflow-y-auto border-r border-gray-200 bg-white custom-scroll scroll-smooth relative">
                <div class="space-y-3 pb-4">
                    @foreach($cart as $item)
                        <div class="flex items-start gap-3 p-3 rounded-lg border border-gray-100 bg-gray-50 hover:bg-white hover:shadow-sm transition-all group">
                            <!-- Quantity Badge (Compact) -->
                            <div class="flex-shrink-0 w-9 h-9 bg-primary-600 text-white rounded-md flex items-center justify-center font-bold text-sm shadow-sm">
                                {{ $item['quantity'] }}x
                            </div>
                            
                            <!-- Detail (Compact) -->
                            <div class="flex-1 min-w-0">
                                <h3 class="text-base font-bold text-gray-800 leading-tight truncate">{{ $item['product_name'] }}</h3>
                                <div class="text-xs text-gray-500 font-medium mt-0.5">@ Rp {{ number_format($item['unit_price'] + ($item['modifier_total'] ?? 0), 0, ',', '.') }}</div>
                                @if(!empty($item['modifiers']))
                                    <div class="mt-1.5 flex flex-wrap gap-1">
                                        @foreach($item['modifiers'] as $mod)
                                            <span class="text-[10px] bg-white border border-gray-200 px-1.5 py-0.5 rounded text-gray-600 flex items-center gap-1">
                                                <span>+ {{ $mod['name'] }}</span>
                                                @if(isset($mod['price']) && $mod['price'] > 0)
                                                    <span class="font-medium text-gray-500">(Rp {{ number_format($mod['price'], 0, ',', '.') }})</span>
                                                @endif
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            
                            <!-- Subtotal (Compact) -->
                            <div class="text-right">
                                <span class="text-base font-bold text-gray-800">Rp {{ number_format($item['subtotal'], 0, ',', '.') }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- RIGHT: Allocations & Totals (40%) -->
            <div class="w-2/5 bg-gray-50 flex flex-col shadow-inner overflow-hidden">
                <!-- Totals Section -->
                <div class="flex-1 p-8 flex flex-col justify-center space-y-6 overflow-y-auto">
                    <!-- Subtotal & Tax -->
                    <div class="space-y-3 border-b border-gray-200 pb-6">
                        <div class="flex justify-between items-center text-gray-600 text-lg">
                            <span>Subtotal</span>
                            <span class="font-medium">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                        </div>
                        @if($taxAmount > 0)
                            <div class="flex justify-between items-center text-gray-600 text-lg">
                                <span>Pajak</span>
                                <span class="font-medium">Rp {{ number_format($taxAmount, 0, ',', '.') }}</span>
                            </div>
                        @endif
                    </div>

                    <!-- Grand Total -->
                    <div class="flex justify-between items-center">
                        <span class="text-2xl font-bold text-gray-800">Total Tagihan</span>
                        <span class="text-5xl font-extrabold text-primary-600">
                            Rp {{ number_format($total, 0, ',', '.') }}
                        </span>
                    </div>
                </div>

                <!-- Payment Status (Bottom) -->
                <div class="bg-white p-8 border-t border-gray-200 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)] flex-shrink-0">
                    @if($paidAmount > 0)
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-xl font-medium text-gray-600">Uang Diterima</span>
                                <span class="text-xl font-bold text-gray-800">Rp {{ number_format($paidAmount, 0, ',', '.') }}</span>
                            </div>
                            
                            <div class="flex justify-between items-center p-4 rounded-xl {{ $changeAmount >= 0 ? 'bg-green-100 text-green-800' : 'bg-red-50 text-red-800' }}">
                                <span class="text-2xl font-bold uppercase">Kembalian</span>
                                <span class="text-4xl font-extrabold">Rp {{ number_format(max(0, $changeAmount), 0, ',', '.') }}</span>
                            </div>
                        </div>
                    @else
                        <div class="text-center text-gray-400 py-4 flex flex-col items-center gap-2">
                             <div class="animate-pulse">
                                <i data-lucide="credit-card" class="w-8 h-8 opacity-50"></i>
                             </div>
                            <p class="text-lg font-medium">Menunggu Pembayaran...</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
    
    <script>
        document.addEventListener('livewire:initialized', () => {
             lucide.createIcons();
             
             // Event Driven: Listen for POS updates (Instant)
             const channel = new BroadcastChannel('pos_channel');
             channel.onmessage = () => {
                 @this.$refresh();
             };
             
             Livewire.hook('morph.updated', ({ component, el }) => {
                 lucide.createIcons();
                 
                 // Robust Auto-Scroll
                 const container = document.getElementById('items-container');
                 
                 if (container) {
                     const currentCount = parseInt(container.getAttribute('data-item-count') || '0');
                     
                     // Initialize global tracker if needed
                     if (typeof window.lastItemCount === 'undefined') {
                        // First load, don't auto scroll unless we want to start at bottom?
                        // Actually first load we usually want to confirm items.
                        window.lastItemCount = currentCount;
                     }

                     // Only scroll if count INCREASED (New Item Added)
                     if (currentCount > window.lastItemCount) {
                         setTimeout(() => {
                             container.scrollTo({
                                 top: container.scrollHeight,
                                 behavior: 'smooth'
                             });
                         }, 100);
                     }
                     
                     window.lastItemCount = currentCount;
                 }
             });
        });
    </script>
    
    <style>
        .animate-fade-in { animation: fadeIn 0.5s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        .custom-scroll::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scroll::-webkit-scrollbar-thumb {
            background-color: #e5e7eb;
            border-radius: 20px;
        }
    </style>
</div>
