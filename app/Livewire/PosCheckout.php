<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\DailyQueueNumber;
use App\Models\PaymentSource;
use App\Models\PrinterConfig;
use App\Models\Product;
use App\Models\ServiceArea;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use App\Models\ProductReturn;
use App\Models\ShiftExpense;

use App\Models\StockLog;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class PosCheckout extends Component
{
    // Cart state
    public array $cart = [];
    
    // Lifecycle hooks
    public function updatedCart($value, $key)
    {
        // Check if the update is on quantity
        // $key format: key.field (e.g., 10_3.quantity)
        $parts = explode('.', $key);
        if (count($parts) === 2 && $parts[1] === 'quantity') {
            $cartKey = $parts[0];
            $quantity = (int)$value;
            
            // Validate quantity
            if ($quantity <= 0) {
                $this->cart[$cartKey]['quantity'] = 1;
                $quantity = 1;
            }

            // Check stock (including other variants of the same product)
            $product = Product::find($this->cart[$cartKey]['product_id']);
            if ($product && $product->track_stock) {
                $currentTotal = $this->getTotalQuantityForProduct($product->id, $cartKey);
                $newTotal = $currentTotal + $quantity;
                
                if ($newTotal > $product->stock) {
                    // Calculate max allowed for this item
                    $maxAllowed = max(0, $product->stock - $currentTotal);
                    $this->cart[$cartKey]['quantity'] = $maxAllowed ?: 1; // Fallback to 1 if calculation is weird, but usually validation logic runs before strict enforcement
                     
                    // Better approach: set to remaining stock
                    $this->cart[$cartKey]['quantity'] = $maxAllowed;
                    
                    // If maxAllowed is 0 (because other variants took all stock), we might have an issue.
                    // But usually we are editing an existing item that has at least 1.
                    // Let's rely on the previous valid value or force it to fit.
                    
                    $this->dispatch('notify', type: 'error', message: "Stok total tidak cukup. Sisa untuk item ini: {$maxAllowed}");
                }
            }
            
            $this->recalculateCartItem($cartKey);
        } else {
            // Broadcast any other cart changes (e.g. quantity via other means if any)
            $this->broadcastCartState();
        }
    }

    public function updatedPaidAmount() { $this->broadcastCartState(); }
    public function updatedCustomerName() { $this->broadcastCartState(); }
    public function updatedPaymentMethod() { $this->broadcastCartState(); }
    public function updatedPaymentSourceId() { $this->broadcastCartState(); }
    
    // Payment state
    public ?int $paymentSourceId = null;
    public string $paymentMethod = 'cash';
    public float $paidAmount = 0;
    public string $customerName = '';
    public string $orderType = 'dine_in';
    public ?int $selectedServiceAreaId = null;
    public string $notes = '';
    
    // UI state
    public ?int $selectedCategoryId = null;
    public string $searchQuery = '';
    public bool $showPaymentModal = false;
    public bool $showReceiptModal = false;
    public bool $showCloseShiftModal = false;
    
    // Close shift form
    public float $openingCash = 0;
    public float $actualCash = 0;
    public string $closeNotes = '';
    public array $expenses = [];
    
    // History & Reprint
    public bool $showHistoryModal = false;
    public ?Transaction $lastTransaction = null;
    
    // Return & Refund
    public bool $showReturnModal = false;
    public bool $showReturnHistoryModal = false; // Added property
    public string $returnInvoiceSearch = '';
    public ?Transaction $returnTransaction = null;
    public array $returnItems = [];
    public string $returnReason = '';
    public string $returnNotes = '';
    
    // Unclosed Shift Blocking
    public bool $showUnclosedShiftModal = false;
    public ?int $unclosedShiftId = null;

    // Pagination
    public int $perPage = 40;
    public int $totalProductsCount = 0;

    public function loadMore(): void
    {
        $this->perPage += 40;
    }
    
    public function openHistoryModal()
    {
        $this->showHistoryModal = true;
    }
    
    public function reprintReceipt(int $transactionId)
    {
        $this->lastTransaction = Transaction::with(['details.modifiers', 'paymentSource', 'user', 'serviceArea'])->find($transactionId);
        if ($this->lastTransaction) {
            $this->showReceiptModal = true;
            $this->dispatch('print-receipt');
            // Increment print count for reprint from history
            $this->lastTransaction->increment('print_count');
        }
    }
    
    public function closeHistoryModal()
    {
        $this->showHistoryModal = false;
    }

    // Return Logic
    public function openReturnHistoryModal()
    {
        $this->showReturnHistoryModal = true;
    }

    public function closeReturnHistoryModal()
    {
        $this->showReturnHistoryModal = false;
    }

    public function printReturnReceipt(int $returnId)
    {
        $this->dispatch('open-new-window', url: route('print.return-detail', $returnId));
    }

    public function openReturnModal()
    {
        $this->reset(['returnTransaction', 'returnInvoiceSearch', 'returnItems', 'returnReason', 'returnNotes']);
        $this->showReturnModal = true;
    }

    public function searchReturnInvoice()
    {
        if (empty($this->returnInvoiceSearch)) {
            $this->dispatch('notify', type: 'error', message: 'Masukkan nomor invoice');
            return;
        }

        $transaction = Transaction::with(['details.modifiers'])
            ->where('invoice_number', $this->returnInvoiceSearch)
            ->where('status', 'completed')
            ->first();

        if (!$transaction) {
            $this->dispatch('notify', type: 'error', message: 'Invoice tidak ditemukan atau sudah dibatalkan');
            return;
        }

        $this->returnTransaction = $transaction;
        $this->returnItems = [];
        
        foreach ($transaction->details as $detail) {
            $alreadyReturned = \App\Models\ReturnItem::where('transaction_detail_id', $detail->id)->sum('quantity');
            $remaining = max(0, $detail->quantity - $alreadyReturned);

            if ($remaining > 0) {
                $this->returnItems[$detail->id] = [
                    'selected' => false,
                    'quantity' => $remaining, // Default to max available
                    'max_quantity' => $remaining,
                    'product_id' => $detail->product_id,
                    'product_name' => $detail->product_name,
                    'unit_price' => $detail->unit_price + $detail->modifier_total,
                    'modifiers' => $detail->modifiers->map(function($m) {
                        return [
                            'name' => $m->pivot->modifier_name,
                            'price' => $m->pivot->price_adjustment
                        ];
                    })->values()->toArray(),
                ];
            }
        }
        
        if (empty($this->returnItems)) {
            $this->dispatch('notify', type: 'error', message: 'Semua item dalam invoice ini sudah diretur sepenuhnya');
            $this->returnTransaction = null;
        }
    }

    public function calculateReturnTotal(): float
    {
        $total = 0;
        foreach ($this->returnItems as $item) {
            if ($item['selected']) {
                $total += $item['unit_price'] * (int)$item['quantity'];
            }
        }
        return $total;
    }

    public function processReturn()
    {
        if (!$this->returnTransaction) {
            $this->dispatch('notify', type: 'error', message: 'Pilih transaksi terlebih dahulu');
            return;
        }

        $selectedItems = array_filter($this->returnItems, fn($item) => $item['selected']);
        if (empty($selectedItems)) {
            $this->dispatch('notify', type: 'error', message: 'Pilih minimal satu item untuk diretur');
            return;
        }

        if (empty($this->returnReason)) {
            $this->dispatch('notify', type: 'error', message: 'Isi alasan retur');
            return;
        }

        // VALIDATION: Ensure return quantity logic is sound
        foreach ($selectedItems as $detailId => $item) {
            if ($item['quantity'] <= 0) {
                $this->dispatch('notify', type: 'error', message: 'Jumlah retur harus lebih dari 0');
                return;
            }
            
            $detail = \App\Models\TransactionDetail::find($detailId);
            if (!$detail) {
                $this->dispatch('notify', type: 'error', message: 'Item transaksi data tidak valid (ID: ' . $detailId . ')');
                return;
            }

            // Backend Check: Qty cannot exceed original purchase MINUS already returned
            $alreadyReturned = \App\Models\ReturnItem::where('transaction_detail_id', $detailId)->sum('quantity');
            $maxReturnable = $detail->quantity - $alreadyReturned;

            if ($item['quantity'] > $maxReturnable) {
                $this->dispatch('notify', type: 'error', message: "Retur '{$item['product_name']}' melebihi sisa (Max: {$maxReturnable})");
                return;
            }
        }

        $totalRefund = $this->calculateReturnTotal();

        $return = DB::transaction(function () use ($selectedItems, $totalRefund) {
            $shift = $this->getOrCreateTodayShift();

            // Create return record
            $return = ProductReturn::create([
                'transaction_id' => $this->returnTransaction->id,
                'user_id' => auth()->id(),
                'shift_id' => $shift->id,
                'return_number' => ProductReturn::generateReturnNumber(),
                'total_refund' => $totalRefund,
                'reason' => $this->returnReason,
                'notes' => $this->returnNotes,
            ]);

            // Create return items
            foreach ($selectedItems as $detailId => $item) {
                $return->items()->create([
                    'transaction_detail_id' => $detailId,
                    'product_id' => $item['product_id'] ?? null,
                    'product_name' => $item['product_name'],
                    'modifiers' => $item['modifiers'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['unit_price'] * $item['quantity'],
                ]);
            }

            // Add expense for refund (always linked to current shift)
            ShiftExpense::create([
                'shift_id' => $shift->id,
                'description' => "Retur: {$return->return_number} (Inv: {$this->returnTransaction->invoice_number})",
                'amount' => $totalRefund,
                'category' => 'refund',
            ]);

            // Restore Stock & Log
            foreach ($selectedItems as $detailId => $item) {
                $detail = \App\Models\TransactionDetail::find($detailId);
                if ($detail && $detail->product && $detail->product->track_stock) {
                    $detail->product->increment('stock', $item['quantity']);
                    
                    StockLog::create([
                        'product_id' => $detail->product_id,
                        'user_id' => auth()->id(),
                        'type' => 'in', // Returned stock is 'in'
                        'amount' => $item['quantity'],
                        'final_stock' => $detail->product->stock, // Fresh stock after increment
                        'reason' => "Retur Penjualan: {$return->return_number}",
                        'reference_id' => $return->id,
                    ]);
                }
            }
            
            return $return;
        });

        $this->showReturnModal = false;
        $this->dispatch('notify', type: 'success', message: 'Retur berhasil diproses');
        $this->dispatch('open-new-window', url: route('print.return-detail', $return->id));
        $this->reset(['returnTransaction', 'returnInvoiceSearch', 'returnItems', 'returnReason', 'returnNotes']);
    }

    public function mount(): void
    {
        $this->paymentSourceId = PaymentSource::active()->where('type', 'cash')->first()?->id;
    }

    #[Computed]
    public function categories()
    {
        return Category::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    #[Computed]
    public function products()
    {
        $query = Product::with(['category', 'modifierGroups.modifiers'])
            ->where('is_active', true);

        if ($this->selectedCategoryId) {
            $query->where('category_id', $this->selectedCategoryId);
        }

        if ($this->searchQuery) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->searchQuery}%")
                  ->orWhere('sku', 'like', "%{$this->searchQuery}%");
            });
        }
        
        // Count total matching (cached for pagination check)
        $this->totalProductsCount = $query->count();

        // Apply limit
        return $query->take($this->perPage)
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function paymentSources()
    {
        return PaymentSource::where('is_active', true)->orderBy('sort_order')->get();
    }

    #[Computed]
    public function todayShift(): ?Shift
    {
        return Shift::where('user_id', auth()->id())
            ->whereDate('started_at', today())
            ->first();
    }

    #[Computed]
    public function unclosedPreviousShift(): ?Shift
    {
        return Shift::where('user_id', auth()->id())
            ->where('status', 'open')
            ->whereDate('started_at', '<', today())
            ->with('transactions')
            ->first();
    }

    #[Computed]
    public function todayTransactions()
    {
        return Transaction::where('user_id', auth()->id())
            ->whereDate('created_at', today())
            ->where('status', 'completed')
            ->get();
    }

    #[Computed]
    public function todayReturns()
    {
        return ProductReturn::with(['user', 'transaction'])
            ->where('user_id', auth()->id())
            ->whereDate('created_at', today())
            ->latest()
            ->get();
    }

    #[Computed]
    public function subtotal(): float
    {
        return collect($this->cart)->sum('subtotal');
    }

    #[Computed]
    public function taxAmount(): float
    {
        $taxPercentage = Setting::get('tax_percentage', 0, 'general');
        return $this->subtotal * ($taxPercentage / 100);
    }

    #[Computed]
    public function total(): float
    {
        return $this->subtotal + $this->taxAmount;
    }

    #[Computed]
    public function changeAmount(): float
    {
        return max(0, $this->paidAmount - $this->total);
    }

    #[Computed]
    public function printerConfig(): ?PrinterConfig
    {
        return PrinterConfig::where('is_default', true)->first();
    }

    #[Computed]
    public function serviceAreas()
    {
        return ServiceArea::active()->orderBy('sort_order')->get();
    }

    public function selectCategory(?int $categoryId): void
    {
        $this->selectedCategoryId = $categoryId;
    }

    public function addToCart(int $productId, array $modifiers = []): void
    {
        $product = Product::with('modifierGroups.modifiers')->find($productId);
        
        if (!$product) {
            $this->dispatch('notify', type: 'error', message: 'Produk tidak ditemukan');
            return;
        }

        $cartKey = $this->generateCartKey($productId, $modifiers);
        
        $currentQty = isset($this->cart[$cartKey]) ? $this->cart[$cartKey]['quantity'] : 0;
        $newQty = $currentQty + 1;

        if ($product->track_stock) {
            if ($product->stock <= 0) {
                 $this->dispatch('notify', type: 'error', message: "Stok {$product->name} habis.");
                 return;
            }
            if ($newQty > $product->stock) {
                 $this->dispatch('notify', type: 'error', message: "Stok tidak cukup. Sisa: {$product->stock}");
                 return;
            }
        }

        if (isset($this->cart[$cartKey])) {
            $this->cart[$cartKey]['quantity'] = $newQty;
            $this->recalculateCartItem($cartKey);
        } else {
            $modifierTotal = $this->calculateModifierTotal($modifiers);
            $subtotal = $product->price + $modifierTotal;
            
            $this->cart[$cartKey] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'unit_price' => $product->price,
                'quantity' => 1,
                'modifiers' => $modifiers,
                'modifier_total' => $modifierTotal,
                'subtotal' => $subtotal,
            ];
        }
        
        $this->dispatch('notify', type: 'success', message: "{$product->name} ditambahkan");
        $this->broadcastCartState();
    }

    public function updateQuantity(string $cartKey, int $quantity): void
    {
        if ($quantity <= 0) {
            $this->removeFromCart($cartKey);
            return;
        }

        if (isset($this->cart[$cartKey])) {
            // Check stock
            $product = Product::find($this->cart[$cartKey]['product_id']);
            if ($product && $product->track_stock) {
                $currentTotal = $this->getTotalQuantityForProduct($product->id, $cartKey);
                if (($currentTotal + $quantity) > $product->stock) {
                    $this->dispatch('notify', type: 'error', message: "Stok total tidak cukup. Max: {$product->stock}");
                    return;
                }
            }

            $this->cart[$cartKey]['quantity'] = $quantity;
            $this->recalculateCartItem($cartKey);
        }
    }

    protected function getTotalQuantityForProduct(int $productId, ?string $excludeCartKey = null): int
    {
        $total = 0;
        foreach ($this->cart as $key => $item) {
            if ($item['product_id'] === $productId && $key !== $excludeCartKey) {
                $total += (int)$item['quantity'];
            }
        }
        return $total;
    }

    public function removeFromCart(string $cartKey): void
    {
        unset($this->cart[$cartKey]);
        $this->broadcastCartState();
    }

    public function clearCart(): void
    {
        $this->cart = [];
        $this->paidAmount = 0;
        $this->customerName = '';
        $this->notes = '';
        $this->orderType = 'dine_in';
        $this->selectedServiceAreaId = null;
        $this->broadcastCartState();
    }

    public function openPaymentModal(): void
    {
        if (empty($this->cart)) {
            $this->dispatch('notify', type: 'error', message: 'Keranjang kosong');
            return;
        }

        $this->paidAmount = $this->total;
        $this->showPaymentModal = true;
    }

    public function closePaymentModal(): void
    {
        $this->showPaymentModal = false;
    }

    public function setPaidAmount(float $amount): void
    {
        $this->paidAmount = $amount;
    }

    public function addToPaidAmount(float $amount): void
    {
        $this->paidAmount += $amount;
    }

    protected function getOrCreateTodayShift(): Shift
    {
        $shift = Shift::where('user_id', auth()->id())
            ->whereDate('started_at', today())
            ->first();

        if (!$shift) {
            $shift = Shift::create([
                'user_id' => auth()->id(),
                'started_at' => now(),
                'opening_cash' => 0,
                'status' => 'open',
            ]);
        }

        return $shift;
    }

    public function processPayment(): void
    {
        if (empty($this->cart)) {
            $this->dispatch('notify', type: 'error', message: 'Keranjang kosong');
            return;
        }

        // Pre-calculate total quantities per product
        $productQuantities = [];
        foreach ($this->cart as $item) {
            $pid = $item['product_id'];
            if (!isset($productQuantities[$pid])) {
                $productQuantities[$pid] = 0;
            }
            $productQuantities[$pid] += $item['quantity'];
        }

        $paymentSource = PaymentSource::find($this->paymentSourceId);
        if (!$paymentSource) {
            $this->dispatch('notify', type: 'error', message: 'Pilih metode pembayaran');
            return;
        }

        // Validate Service Area for Dine In
        if ($this->orderType === 'dine_in' && !$this->selectedServiceAreaId) {
            $this->dispatch('notify', type: 'error', message: 'Wajib pilih Meja / Ruangan untuk Dine In');
            return;
        }

        // For cash, validate paid amount
        if ($paymentSource->type === 'cash' && $this->paidAmount < $this->total) {
            $this->dispatch('notify', type: 'error', message: 'Jumlah pembayaran kurang');
            return;
        }

        try {
            DB::transaction(function () use ($paymentSource, $productQuantities) {
                // LOCKING: Get all products involved in one go, locking rows for update
                $productIds = array_keys($productQuantities);
                $products = Product::whereIn('id', $productIds)->lockForUpdate()->get()->keyBy('id');

                // STRICT STOCK CHECK with locked rows
                foreach ($productQuantities as $productId => $totalQty) {
                    $product = $products->get($productId);
                    if ($product && $product->track_stock && $totalQty > $product->stock) {
                         throw new \Exception("Stok {$product->name} tidak cukup (Total: $totalQty, Sisa: {$product->stock})");
                    }
                }

                // Auto-create or get today's shift
                $shift = $this->getOrCreateTodayShift();
                
                // Get queue number (thread-safe)
                $queueNumber = DailyQueueNumber::getNextNumber();
                
                // Create transaction
                $transaction = Transaction::create([
                    'user_id' => auth()->id(),
                    'shift_id' => $shift->id,
                    'payment_source_id' => $paymentSource->id,
                    'service_area_id' => $this->orderType === 'dine_in' ? $this->selectedServiceAreaId : null,
                    'invoice_number' => Transaction::generateInvoiceNumber(),
                    'queue_number' => $queueNumber,
                    'subtotal' => $this->subtotal,
                    'discount_amount' => 0,
                    'tax_amount' => $this->taxAmount,
                    'total' => $this->total,
                    'paid_amount' => $paymentSource->type === 'cash' ? $this->paidAmount : $this->total,
                    'change_amount' => $paymentSource->type === 'cash' ? $this->changeAmount : 0,
                    'payment_method' => $paymentSource->type,
                    'status' => 'completed',
                    'customer_name' => $this->customerName ?: null,
                    'order_type' => $this->orderType,
                    'notes' => $this->notes ?: null,
                ]);

                // Create transaction details
                foreach ($this->cart as $item) {
                    $detail = $transaction->details()->create([
                        'product_id' => $item['product_id'],
                        'product_name' => $item['product_name'],
                        'unit_price' => $item['unit_price'],
                        'quantity' => $item['quantity'],
                        'modifier_total' => $item['modifier_total'],
                        'subtotal' => $item['subtotal'],
                    ]);

                    // Attach modifiers with snapshot data
                    if (!empty($item['modifiers'])) {
                        foreach ($item['modifiers'] as $modifierId => $modifierData) {
                            $detail->modifiers()->attach($modifierId, [
                                'modifier_name' => $modifierData['name'],
                                'price_adjustment' => $modifierData['price'],
                            ]);
                        }
                    }

                    // Decrement stock using the PRE-LOADED LOCKED MODEL
                    $product = $products->get($item['product_id']);
                    if ($product && $product->track_stock) {
                        $product->decrement('stock', $item['quantity']);
                        
                        // Log usage
                        StockLog::create([
                            'product_id' => $product->id,
                            'user_id' => auth()->id(),
                            'type' => 'sale',
                            'amount' => -$item['quantity'], // Negative for usage
                            'final_stock' => $product->fresh()->stock, // Refresh to get exact current value
                            'note' => "Inv: {$transaction->invoice_number}",
                        ]);
                    }
                }

                // Load relations for receipt
                $this->lastTransaction = $transaction->load([
                    'details.modifiers',
                    'user',
                    'paymentSource',
                    'serviceArea',
                ]);
            });

            // Clear cart and close payment modal
            $this->clearCart();
            $this->showPaymentModal = false;
            $this->showReceiptModal = true;

            // Dispatch event for auto-print
            $printerConfig = $this->printerConfig;
            if ($printerConfig?->auto_print) {
                $this->dispatch('print-receipt');
                // Increment print count for auto-print
                $this->lastTransaction?->increment('print_count');
            }

            $this->dispatch('notify', type: 'success', message: 'Transaksi berhasil!');

        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Gagal memproses transaksi: ' . $e->getMessage());
        }
    }

    public function closeReceiptModal(): void
    {
        $this->showReceiptModal = false;
        $this->lastTransaction = null;
    }

    public function printReceipt(): void
    {
        $this->dispatch('print-receipt');
        // Increment print count for manual reprint
        $this->lastTransaction?->increment('print_count');
    }

    // Close Shift Methods
    public function openCloseShiftModal(): void
    {
        $shift = $this->todayShift;
        if (!$shift) {
            $this->dispatch('notify', type: 'error', message: 'Tidak ada transaksi hari ini');
            return;
        }

        $this->openingCash = 0;
        $this->actualCash = 0;
        $this->closeNotes = '';
        $this->expenses = [];
        $this->showCloseShiftModal = true;
    }

    public function addExpense(): void
    {
        $this->expenses[] = ['description' => '', 'amount' => 0];
    }

    public function removeExpense(int $index): void
    {
        unset($this->expenses[$index]);
        $this->expenses = array_values($this->expenses);
    }

    public function closeShift(): void
    {
        $shift = $this->todayShift;
        if (!$shift) {
            return;
        }

        DB::transaction(function () use ($shift) {
            // Update opening cash
            $shift->opening_cash = $this->openingCash;

            // Add expenses
            foreach ($this->expenses as $expense) {
                if (!empty($expense['description']) && $expense['amount'] > 0) {
                    $shift->expenses()->create([
                        'description' => $expense['description'],
                        'amount' => $expense['amount'],
                        'category' => 'operational',
                    ]);
                }
            }
            
            // Re-calculate TOTAL expenses (Manual Operational + Automated Refunds)
            // We need to fetch from DB because refunds are already saved there
            $totalExpenses = $shift->expenses()->sum('amount');

            // Calculate expected cash
            $cashSales = $shift->transactions()
                ->where('status', 'completed')
                ->where('payment_method', 'cash')
                ->sum('total');

            $expectedCash = $this->openingCash + $cashSales - $totalExpenses;

            // Close shift
            $shift->update([
                'ended_at' => now(),
                'actual_cash' => $this->actualCash,
                'expected_cash' => $expectedCash,
                'cash_difference' => $this->actualCash - $expectedCash,
                'close_notes' => $this->closeNotes,
                'status' => 'closed',
            ]);
        });

        $this->showCloseShiftModal = false;
        $this->dispatch('notify', type: 'success', message: 'Shift berhasil ditutup');
        $this->dispatch('open-new-window', url: route('print.shift.detail', $shift->id)); // Send URL directly
    }

    public function openClosePreviousShiftModal(): void
    {
        $shift = $this->unclosedPreviousShift;
        if (!$shift) {
            return;
        }
        
        $this->unclosedShiftId = $shift->id;
        $this->openingCash = 0;
        $this->actualCash = 0;
        $this->closeNotes = '';
        $this->expenses = [];
        $this->showUnclosedShiftModal = true;
    }

    public function closePreviousShift(): void
    {
        $shift = Shift::find($this->unclosedShiftId);
        if (!$shift || $shift->user_id !== auth()->id()) {
            $this->dispatch('notify', type: 'error', message: 'Shift tidak ditemukan');
            return;
        }

        DB::transaction(function () use ($shift) {
            // Update opening cash
            $shift->opening_cash = $this->openingCash;

            // Add expenses
            foreach ($this->expenses as $expense) {
                if (!empty($expense['description']) && $expense['amount'] > 0) {
                    $shift->expenses()->create([
                        'description' => $expense['description'],
                        'amount' => $expense['amount'],
                        'category' => 'operational',
                    ]);
                }
            }
            
            // Re-calculate TOTAL expenses (to include Refunds)
            $totalExpenses = $shift->expenses()->sum('amount');

            // Calculate expected cash
            $cashSales = $shift->transactions()
                ->where('status', 'completed')
                ->where('payment_method', 'cash')
                ->sum('total');

            $expectedCash = $this->openingCash + $cashSales - $totalExpenses;

            // Close shift with late closure flag
            $shift->update([
                'ended_at' => now(),
                'actual_cash' => $this->actualCash,
                'expected_cash' => $expectedCash,
                'cash_difference' => $this->actualCash - $expectedCash,
                'close_notes' => $this->closeNotes . ' [DITUTUP TERLAMBAT]',
                'status' => 'closed',
            ]);
        });

        $this->showUnclosedShiftModal = false;
        $this->unclosedShiftId = null;
        $this->unclosedShiftId = null;
        $this->dispatch('notify', type: 'success', message: 'Shift sebelumnya berhasil ditutup. Anda bisa mulai transaksi.');
        $this->dispatch('open-new-window', url: route('print.shift.detail', $shift->id));
    }

    protected function generateCartKey(int $productId, array $modifiers): string
    {
        $modifierIds = array_keys($modifiers);
        sort($modifierIds);
        return $productId . '_' . implode('_', $modifierIds);
    }

    protected function calculateModifierTotal(array $modifiers): float
    {
        return collect($modifiers)->sum('price');
    }

    protected function recalculateCartItem(string $cartKey): void
    {
        if (!isset($this->cart[$cartKey])) {
            return;
        }

        $item = $this->cart[$cartKey];
        $basePrice = $item['unit_price'] + $item['modifier_total'];
        $this->cart[$cartKey]['subtotal'] = $basePrice * $item['quantity'];
        
        $this->broadcastCartState();
    }

    /**
     * Broadcast current state to cache for Customer Display
     * Saved for 12 hours
     */
    public function broadcastCartState(): void
    {
        // Calculate totals dynamically if needed, or rely on computed properties
        // For cache, we need concrete values.
        
        $data = [
            'cart' => $this->cart,
            'subtotal' => $this->subtotal, // Computed property access
            'tax_amount' => $this->taxAmount,
            'total' => $this->total,
            'paid_amount' => $this->paidAmount,
            'change_amount' => $this->changeAmount,
            'customer_name' => $this->customerName,
            'cashier_name' => auth()->user()->name,
            'updated_at' => now()->timestamp,
        ];

        \Illuminate\Support\Facades\Cache::put('pos_active_cart_' . auth()->id(), $data, now()->addHours(12));
    }

    public function render()
    {
        return view('livewire.pos-checkout')
            ->layout('layouts.pos');
    }
}
