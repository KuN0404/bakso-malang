<div class="h-screen bg-gray-50 flex flex-col overflow-hidden" 
     x-data="{
         cart: @js($cart),
         subtotal: @js($subtotal),
         taxAmount: @js($taxAmount),
         total: @js($total),
         paidAmount: @js($paidAmount),
         changeAmount: @js($changeAmount),
         customerName: @js($customerName),
         cashierName: @js($cashierName),
         formatNumber(num) {
             return new Intl.NumberFormat('id-ID').format(num || 0);
         },
         clearDisplay() {
             this.cart = [];
             this.subtotal = 0;
             this.taxAmount = 0;
             this.total = 0;
             this.paidAmount = 0;
             this.changeAmount = 0;
             this.customerName = '';
             this.cashierName = '';
         }
     }"
     @cart-data-broadcast.window="
         cart = $event.detail.cart || [];
         subtotal = $event.detail.subtotal || 0;
         taxAmount = $event.detail.tax_amount || 0;
         total = $event.detail.total || 0;
         paidAmount = $event.detail.paid_amount || 0;
         changeAmount = $event.detail.change_amount || 0;
         customerName = $event.detail.customer_name || '';
         cashierName = $event.detail.cashier_name || '';
         $nextTick(() => {
             // Handle auto-scroll on change
             const container = document.getElementById('items-container');
             if (container) {
                 container.scrollTo({
                     top: container.scrollHeight,
                     behavior: 'smooth'
                 });
             }
         });
     "
>
    <!-- Header -->
    <div class="bg-white shadow-sm border-b px-6 py-4 flex justify-between items-center transition-all duration-300 z-10 flex-shrink-0">
        <div class="flex items-center gap-3">
            <div class="bg-primary-600 p-2 rounded-lg">
                <x-lucide name="monitor" class="w-6 h-6 text-white" />
            </div>
            <div>
                <h1 class="text-xl font-bold text-gray-800">Layar Pelanggan</h1>
                <p class="text-sm text-gray-500" x-show="cashierName" x-text="'Kasir: ' + cashierName"></p>
            </div>
        </div>
        <div>
            <span class="bg-blue-100 text-blue-800 px-4 py-2 rounded-full font-bold text-lg" 
                  x-show="customerName" 
                  x-text="'Pelanggan: ' + customerName">
            </span>
        </div>
    </div>

    <!-- Content -->
    <!-- EMPTY STATE: Full Screen -->
    <div x-show="Object.keys(cart).length === 0" class="flex-1 flex flex-col items-center justify-center bg-white text-center p-8 animate-fade-in overflow-hidden">
        <div class="w-64 h-64 bg-gray-50 rounded-full flex items-center justify-center mb-8 shadow-inner">
            <x-lucide name="shopping-bag" class="w-32 h-32 text-gray-300" />
        </div>
        <h2 class="text-4xl font-bold text-gray-800 mb-4 font-sans tracking-tight">Selamat Datang</h2>
        <p class="text-2xl text-gray-400 font-light">Silakan pesan menu favorit Anda</p>
        
        <div class="mt-12 flex gap-2">
            <div class="w-3 h-3 rounded-full bg-gray-200 animate-bounce"></div>
            <div class="w-3 h-3 rounded-full bg-gray-200 animate-bounce delay-100"></div>
            <div class="w-3 h-3 rounded-full bg-gray-200 animate-bounce delay-200"></div>
        </div>
    </div>

    <!-- ACTIVE STATE: Split View -->
    <div x-show="Object.keys(cart).length > 0" class="flex-1 flex overflow-hidden" style="display: none;">
        <!-- LEFT: Items List (60%) -->
        <div id="items-container" class="w-3/5 p-4 overflow-y-auto border-r border-gray-200 bg-white custom-scroll scroll-smooth relative">
            <div class="space-y-3 pb-4">
                <template x-for="(item, cartKey) in cart" :key="cartKey">
                    <div class="flex items-start gap-3 p-3 rounded-lg border border-gray-100 bg-gray-50 hover:bg-white hover:shadow-sm transition-all group">
                        <!-- Quantity Badge (Compact) -->
                        <div class="flex-shrink-0 w-9 h-9 bg-primary-600 text-white rounded-md flex items-center justify-center font-bold text-sm shadow-sm"
                             x-text="item.quantity + 'x'">
                        </div>
                        
                        <!-- Detail (Compact) -->
                        <div class="flex-1 min-w-0">
                            <h3 class="text-base font-bold text-gray-800 leading-tight truncate" x-text="item.product_name"></h3>
                            <div class="text-xs text-gray-500 font-medium mt-0.5" 
                                 x-text="'@ Rp ' + formatNumber(parseFloat(item.unit_price) + parseFloat(item.modifier_total || 0))">
                            </div>
                            <div class="mt-1.5 flex flex-wrap gap-1" x-show="item.modifiers && Object.keys(item.modifiers).length > 0">
                                <template x-for="(mod, modKey) in item.modifiers" :key="modKey">
                                    <span class="text-[10px] bg-white border border-gray-200 px-1.5 py-0.5 rounded text-gray-600 flex items-center gap-1">
                                        <span x-text="'+ ' + mod.name"></span>
                                        <span class="font-medium text-gray-500" 
                                              x-show="mod.price > 0" 
                                              x-text="'(Rp ' + formatNumber(mod.price) + ')'">
                                        </span>
                                    </span>
                                </template>
                            </div>
                        </div>
                        
                        <!-- Subtotal (Compact) -->
                        <div class="text-right">
                            <span class="text-base font-bold text-gray-800" x-text="'Rp ' + formatNumber(item.subtotal)"></span>
                        </div>
                    </div>
                </template>
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
                        <span class="font-medium" x-text="'Rp ' + formatNumber(subtotal)"></span>
                    </div>
                    <div class="flex justify-between items-center text-gray-600 text-lg" x-show="taxAmount > 0">
                        <span>Pajak</span>
                        <span class="font-medium" x-text="'Rp ' + formatNumber(taxAmount)"></span>
                    </div>
                </div>

                <!-- Grand Total -->
                <div class="flex justify-between items-center">
                    <span class="text-2xl font-bold text-gray-800">Total Tagihan</span>
                    <span class="text-5xl font-extrabold text-primary-600" x-text="'Rp ' + formatNumber(total)"></span>
                </div>
            </div>

            <!-- Payment Status (Bottom) -->
            <div class="bg-white p-8 border-t border-gray-200 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)] flex-shrink-0">
                <div x-show="paidAmount > 0" class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-xl font-medium text-gray-600">Uang Diterima</span>
                        <span class="text-xl font-bold text-gray-800" x-text="'Rp ' + formatNumber(paidAmount)"></span>
                    </div>
                    
                    <div class="flex justify-between items-center p-4 rounded-xl text-green-800 bg-green-100">
                        <span class="text-2xl font-bold uppercase">Kembalian</span>
                        <span class="text-4xl font-extrabold" x-text="'Rp ' + formatNumber(Math.max(0, changeAmount))"></span>
                    </div>
                </div>
                <div x-show="!paidAmount || paidAmount <= 0" class="text-center text-gray-400 py-4 flex flex-col items-center gap-2">
                     <div class="animate-pulse">
                        <x-lucide name="credit-card" class="w-8 h-8 opacity-50" />
                     </div>
                    <p class="text-lg font-medium">Menunggu Pembayaran...</p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('livewire:initialized', () => {
             // Event Driven: Listen for POS updates (Instant)
             const channel = new BroadcastChannel('pos_channel_' + {{ auth()->id() }});
             channel.onmessage = (e) => {
                 console.log('Broadcast received', e.data);
                  if (e.data && e.data.type === 'heartbeat') {
                      return; // Skip heartbeat processing
                  }
                  
                  if (e.data && typeof e.data === 'object' && e.data.cart) {
                      // Dispatch custom browser event for Alpine store update
                      window.dispatchEvent(new CustomEvent('cart-data-broadcast', { detail: e.data }));
                  } else {
                      // Fallback to refresh component if no payload
                      @this.$refresh().then(() => {
                          // Update Alpine state from refreshed Livewire properties
                          const root = document.querySelector('[x-data]');
                          if (root && root.__x) {
                              root.__x.$data.cart = @this.cart;
                              root.__x.$data.subtotal = @this.subtotal;
                              root.__x.$data.taxAmount = @this.taxAmount;
                              root.__x.$data.total = @this.total;
                              root.__x.$data.paidAmount = @this.paidAmount;
                              root.__x.$data.changeAmount = @this.changeAmount;
                              root.__x.$data.customerName = @this.customerName;
                              root.__x.$data.cashierName = @this.cashierName;
                          }
                      });
                  }
             };
        });
    </script>
    
    <style>
        .animate-fade-in { animation: fadeIn 0.5s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        .custom-scroll::-webkit-scrollbar { width: 6px; }
        .custom-scroll::-webkit-scrollbar-track { background: transparent; }
        .custom-scroll::-webkit-scrollbar-thumb { background-color: #e5e7eb; border-radius: 20px; }
    </style>
</div>
