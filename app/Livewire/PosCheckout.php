<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\DailyQueueNumber;
use App\Models\PaymentSource;
use App\Models\PrinterConfig;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use App\Models\ProductReturn;
use App\Models\ShiftExpense;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class PosCheckout extends Component
{
    // Cart state
    public array $cart = [];
    
    // Payment state
    public ?int $paymentSourceId = null;
    public string $paymentMethod = 'cash';
    public float $paidAmount = 0;
    public string $customerName = '';
    public string $orderType = 'dine_in';
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
    public string $returnInvoiceSearch = '';
    public ?Transaction $returnTransaction = null;
    public array $returnItems = [];
    public string $returnReason = '';
    public string $returnNotes = '';
    
    public function openHistoryModal()
    {
        $this->showHistoryModal = true;
    }
    
    public function reprintReceipt(int $transactionId)
    {
        $this->lastTransaction = Transaction::with(['details.product.category', 'paymentSource', 'user'])->find($transactionId);
        if ($this->lastTransaction) {
            $this->showReceiptModal = true;
            $this->dispatch('print-receipt');
        }
    }
    
    public function closeHistoryModal()
    {
        $this->showHistoryModal = false;
    }

    // Return Logic
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

        $transaction = Transaction::with('details')
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
            $this->returnItems[$detail->id] = [
                'selected' => false,
                'quantity' => $detail->quantity,
                'max_quantity' => $detail->quantity,
                'product_name' => $detail->product_name,
                'unit_price' => $detail->unit_price + $detail->modifier_total,
            ];
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

        $totalRefund = $this->calculateReturnTotal();

        DB::transaction(function () use ($selectedItems, $totalRefund) {
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
                    'product_name' => $item['product_name'],
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
        });

        $this->showReturnModal = false;
        $this->dispatch('notify', type: 'success', message: 'Retur berhasil diproses');
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

        return $query->orderBy('name')->get();
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
    public function todayTransactions()
    {
        return Transaction::where('user_id', auth()->id())
            ->whereDate('created_at', today())
            ->where('status', 'completed')
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
        
        if (isset($this->cart[$cartKey])) {
            $this->cart[$cartKey]['quantity']++;
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
    }

    public function updateQuantity(string $cartKey, int $quantity): void
    {
        if ($quantity <= 0) {
            $this->removeFromCart($cartKey);
            return;
        }

        if (isset($this->cart[$cartKey])) {
            $this->cart[$cartKey]['quantity'] = $quantity;
            $this->recalculateCartItem($cartKey);
        }
    }

    public function removeFromCart(string $cartKey): void
    {
        unset($this->cart[$cartKey]);
    }

    public function clearCart(): void
    {
        $this->cart = [];
        $this->paidAmount = 0;
        $this->customerName = '';
        $this->notes = '';
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

        $paymentSource = PaymentSource::find($this->paymentSourceId);
        if (!$paymentSource) {
            $this->dispatch('notify', type: 'error', message: 'Pilih metode pembayaran');
            return;
        }

        // For cash, validate paid amount
        if ($paymentSource->type === 'cash' && $this->paidAmount < $this->total) {
            $this->dispatch('notify', type: 'error', message: 'Jumlah pembayaran kurang');
            return;
        }

        try {
            DB::transaction(function () use ($paymentSource) {
                // Auto-create or get today's shift
                $shift = $this->getOrCreateTodayShift();
                
                // Get queue number (thread-safe)
                $queueNumber = DailyQueueNumber::getNextNumber();
                
                // Create transaction
                $transaction = Transaction::create([
                    'user_id' => auth()->id(),
                    'shift_id' => $shift->id,
                    'payment_source_id' => $paymentSource->id,
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
                        'subtotal' => $item['subtotal'] * $item['quantity'],
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

                    // Decrement stock if tracking
                    $product = Product::find($item['product_id']);
                    if ($product && $product->track_stock) {
                        $product->decrement('stock', $item['quantity']);
                    }
                }

                // Load relations for receipt
                $this->lastTransaction = $transaction->load([
                    'details.modifiers',
                    'user',
                    'paymentSource',
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
            $totalExpenses = 0;
            foreach ($this->expenses as $expense) {
                if (!empty($expense['description']) && $expense['amount'] > 0) {
                    $shift->expenses()->create([
                        'description' => $expense['description'],
                        'amount' => $expense['amount'],
                        'category' => 'operational',
                    ]);
                    $totalExpenses += $expense['amount'];
                }
            }

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
        $this->dispatch('print-shift-receipt');
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
    }

    public function render()
    {
        return view('livewire.pos-checkout')
            ->layout('layouts.pos');
    }
}
