<?php

namespace App\Livewire;

use App\Actions\Payment\CancelPaymentTransactionAction;
use App\Actions\Payment\InitiateCashPaymentAction;
use App\Actions\Payment\InitiateQrisPaymentAction;
use App\DTOs\Payment\CartPayload;
use App\Models\Category;
use App\Models\DailyQueueNumber;
use App\Models\PaymentSource;
use App\Models\PaymentTransaction;
use App\Models\PrinterConfig;
use App\Models\Product;
use App\Models\ProductReturn;
use App\Models\ReturnItem;
use App\Models\Pager;
use App\Models\ServiceArea;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\ShiftExpense;
use App\Models\StockLog;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Services\ComponentStockService;
use App\Services\MidtransService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.pos')]
class PosCheckout extends Component
{
    // Cart state — dilindungi #[Locked] dari manipulasi request langsung (mis. tampering harga via devtools).
    // Harga tetap divalidasi ulang dari DB di Action pembayaran sebagai lapisan pertahanan utama.
    #[Locked]
    public array $cart = [];

    // Lifecycle hooks
    public function updatedCart($value, $key)
    {
        $parts = explode('.', $key);
        if (count($parts) === 2 && $parts[1] === 'quantity') {
            $cartKey = $parts[0];
            $quantity = (int)$value;

            if ($quantity <= 0) {
                $this->cart[$cartKey]['quantity'] = 1;
                $quantity = 1;
            }

            $product = Product::find($this->cart[$cartKey]['product_id']);
            if ($product && $product->track_stock) {
                $currentTotal = $this->getTotalQuantityForProduct($product->id, $cartKey);
                $newTotal = $currentTotal + $quantity;

                if ($newTotal > $product->stock) {
                    $maxAllowed = max(0, $product->stock - $currentTotal);
                    $this->cart[$cartKey]['quantity'] = $maxAllowed;
                    $this->dispatch('notify', type: 'error', message: "Stok total tidak cukup. Sisa untuk item ini: {$maxAllowed}");
                }
            }

            $this->recalculateCartItem($cartKey);
        } else {
            $this->broadcastCartState();
        }
    }

    public function updatedPaidAmount() { $this->broadcastCartState(); }
    public function updatedCustomerName() { $this->broadcastCartState(); }
    public function updatedPaymentMethod() { $this->broadcastCartState(); }
    public function updatedPaymentSourceId() { $this->broadcastCartState(); }
    public function updatedNotes() { $this->broadcastCartState(); }
    public function updatedOrderType() { $this->broadcastCartState(); }
    public function updatedSelectedServiceAreaId() { $this->broadcastCartState(); }
    public function updatedSelectedPagerId() { $this->broadcastCartState(); }

    // Payment state
    public ?int $paymentSourceId = null;
    public string $paymentMethod = 'cash';
    public float $paidAmount = 0;
    public string $customerName = '';
    // Opsional — beda dari Self Order yang nomor HP-nya wajib. Dipakai untuk
    // kirim struk digital (email/WhatsApp) kalau kasir sempat mengisinya.
    public string $customerPhone = '';
    public string $customerEmail = '';
    public ?string $qrisOrderId = null;
    public ?string $qrisCodeUrl = null;
    public float $qrisAmount = 0;
    public ?string $qrisInvoiceNumber = null;
    public int $qrisExpiresIn = 0;
    public ?int $qrisExpiresAtMs = null;
    public bool $showQrisModal = false;
    public bool $qrisGenerating = false;
    public bool $showCloseShiftModal = false;
    public bool $showShiftReceiptModal = false;
    public ?Shift $printShift = null;

    public string $orderType = 'dine_in';
    public ?int $selectedServiceAreaId = null;
    public ?int $selectedPagerId = null;
    public string $notes = '';

    // UI state
    public ?int $selectedCategoryId = null;
    public string $searchQuery = '';
    public bool $showPaymentModal = false;
    public bool $showReceiptModal = false;

    // Close shift form
    public float $openingCash = 0;
    public float $actualCash = 0;
    public float $actualNonCash = 0;
    public float $expectedNonCash = 0;
    public string $closeNotes = '';
    public array $expenses = [];

    // Idempotency keys — di-set dari frontend via $wire.set() sebelum submit
    public string $paymentIdempotencyKey = '';
    public string $returnIdempotencyKey = '';



    // History & Reprint
    public bool $showHistoryModal = false;
    public ?Transaction $lastTransaction = null;

    // Return & Refund
    public bool $showReturnModal = false;
    public bool $showReturnHistoryModal = false;
    public string $returnInvoiceSearch = '';
    public ?Transaction $returnTransaction = null;
    public array $returnItems = [];
    public string $returnReason = '';
    public string $returnNotes = '';

    // Unclosed Shift Blocking
    public bool $showUnclosedShiftModal = false;
    public ?int $unclosedShiftId = null;

    // Kitchen Pending Warning
    public int $pendingFoodCount = 0;
    public int $pendingDrinkCount = 0;

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
        $this->lastTransaction = Transaction::getForPrinting($transactionId);
        if ($this->lastTransaction) {
            $this->showReceiptModal = true;
            $this->dispatch('print-receipt');
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

        $transaction = Transaction::getCompletedByInvoice($this->returnInvoiceSearch);

        if (!$transaction) {
            $this->dispatch('notify', type: 'error', message: 'Invoice tidak ditemukan atau sudah dibatalkan');
            return;
        }

        $this->returnTransaction = $transaction;
        $this->returnItems = [];

        foreach ($transaction->details as $detail) {
            $alreadyReturned = ReturnItem::getReturnedQuantity($detail->id);
            $remaining = max(0, $detail->quantity - $alreadyReturned);

            if ($remaining > 0) {
                $this->returnItems[$detail->id] = [
                    'selected'     => false,
                    'quantity'     => $remaining,
                    'max_quantity' => $remaining,
                    'product_id'   => $detail->product_id,
                    'product_name' => $detail->product_name,
                    'unit_price'   => $detail->unit_price + $detail->modifier_total,
                    'modifiers'    => $detail->modifiers->map(function ($m) {
                        return [
                            'name'  => $m->pivot->modifier_name,
                            'price' => $m->pivot->price_adjustment,
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
        // Idempotency check — tolak jika key yang sama sudah diproses dalam 60 detik
        if (!empty($this->returnIdempotencyKey)) {
            $cacheKey = 'return_idem_' . auth()->id() . '_' . $this->returnIdempotencyKey;
            if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                $this->dispatch('notify', type: 'warning', message: 'Retur sebelumnya sedang diproses, mohon tunggu.');
                return;
            }
            \Illuminate\Support\Facades\Cache::put($cacheKey, true, now()->addSeconds(60));
        }

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

        foreach ($selectedItems as $detailId => $item) {
            if ($item['quantity'] <= 0) {
                $this->dispatch('notify', type: 'error', message: 'Jumlah retur harus lebih dari 0');
                return;
            }

            $detail = TransactionDetail::find($detailId);
            if (!$detail) {
                $this->dispatch('notify', type: 'error', message: 'Item transaksi data tidak valid (ID: ' . $detailId . ')');
                return;
            }

            $alreadyReturned = ReturnItem::getReturnedQuantity($detailId);
            $maxReturnable = $detail->quantity - $alreadyReturned;

            if ($item['quantity'] > $maxReturnable) {
                $this->dispatch('notify', type: 'error', message: "Retur '{$item['product_name']}' melebihi sisa (Max: {$maxReturnable})");
                return;
            }
        }

        $totalRefund = $this->calculateReturnTotal();

        $return = DB::transaction(function () use ($selectedItems, $totalRefund) {
            $shift = Shift::getOrCreateTodayShift(auth()->id());

            $return = ProductReturn::create([
                'transaction_id' => $this->returnTransaction->id,
                'user_id'        => auth()->id(),
                'shift_id'       => $shift->id,
                'return_number'  => ProductReturn::generateReturnNumber(),
                'total_refund'   => $totalRefund,
                'reason'         => $this->returnReason,
                'notes'          => $this->returnNotes,
            ]);

            foreach ($selectedItems as $detailId => $item) {
                $return->items()->create([
                    'transaction_detail_id' => $detailId,
                    'product_id'            => $item['product_id'] ?? null,
                    'product_name'          => $item['product_name'],
                    'modifiers'             => $item['modifiers'] ?? null,
                    'quantity'              => $item['quantity'],
                    'unit_price'            => $item['unit_price'],
                    'subtotal'              => $item['unit_price'] * $item['quantity'],
                ]);
            }

            ShiftExpense::create([
                'shift_id'    => $shift->id,
                'description' => "Retur: {$return->return_number} (Inv: {$this->returnTransaction->invoice_number})",
                'amount'      => $totalRefund,
                'category'    => 'refund',
            ]);

            foreach ($selectedItems as $detailId => $item) {
                $detail = TransactionDetail::find($detailId);

                if (!$detail) {
                    continue;
                }

                // restoreForReturn() menangani BOM (jika produk punya BOM) DAN modifier
                // ber-component_id (independen dari status BOM produk) dalam satu panggilan.
                app(ComponentStockService::class)->restoreForReturn(
                    $detailId,
                    (float) $item['quantity'],
                    auth()->id(),
                    $return->id
                );

                if ($detail->product && !$detail->product->hasBom() && $detail->product->track_stock) {
                    $detail->product->increment('stock', $item['quantity']);

                    StockLog::record(
                        productId:   $detail->product_id,
                        userId:      auth()->id(),
                        type:        'in',
                        amount:      $item['quantity'],
                        finalStock:  $detail->product->stock,
                        note:        "Retur Penjualan: {$return->return_number}",
                        referenceId: $return->id
                    );
                }
            }

            return $return;
        });

        // Reset idempotency key setelah retur sukses agar modal bisa dipakai ulang
        $this->returnIdempotencyKey = '';
        $this->showReturnModal = false;

        // Sinkronisasi data retur ke DB Laporan
        app(\App\Services\ReportSyncService::class)->syncReturn($return->load('items'));

        $this->dispatch('notify', type: 'success', message: 'Retur berhasil diproses');
        $this->dispatch('open-new-window', url: route('print.return-detail', $return->id));
        $this->reset(['returnTransaction', 'returnInvoiceSearch', 'returnItems', 'returnReason', 'returnNotes']);
    }

    // Baseline count Self Order untuk deteksi pesanan baru masuk (lihat selfOrdersCount()).
    // Diisi via query langsung di mount() (bukan lewat computed) agar TIDAK memicu toast
    // untuk pesanan yang sudah ada sebelum kasir membuka halaman ini.
    public int $notifiedSelfOrdersCount = 0;

    public function mount(): void
    {
        $this->paymentSourceId = PaymentSource::getDefaultCash()?->id;

        $userId = auth()->id();
        if ($userId) {
            // Pakai computed property (bukan panggil static method langsung) supaya
            // hasilnya di-cache Livewire untuk request ini — dipakai ulang saat
            // template membaca $this->selfOrdersCount, tidak query 2x.
            $this->notifiedSelfOrdersCount = $this->selfOrdersCount;

            $cachedCart = Cache::get('pos_active_cart_' . $userId);
            if (!empty($cachedCart) && is_array($cachedCart)) {
                $this->cart                  = $cachedCart['cart'] ?? [];
                $this->paidAmount            = $cachedCart['paid_amount'] ?? 0;
                $this->customerName          = $cachedCart['customer_name'] ?? '';
                $this->customerPhone         = $cachedCart['customer_phone'] ?? '';
                $this->customerEmail         = $cachedCart['customer_email'] ?? '';
                $this->notes                 = $cachedCart['notes'] ?? '';
                $this->orderType             = $cachedCart['order_type'] ?? 'dine_in';
                $this->selectedServiceAreaId = $cachedCart['selected_service_area_id'] ?? null;
                $this->selectedPagerId       = $cachedCart['selected_pager_id'] ?? null;
            }

            // Restorasi transaksi QRIS pending yang masih aktif (jika ada)
            $activeTx = PaymentTransaction::where('created_by', $userId)
                ->where('status', \App\Enums\PaymentTransactionStatus::Pending->value)
                ->where('payment_method', 'qris')
                ->latest()
                ->first();

            if ($activeTx && !$activeTx->isQrisExpiredByTime()) {
                $this->qrisOrderId       = $activeTx->midtrans_order_id;
                $this->qrisCodeUrl       = $activeTx->qr_code_url;
                $this->qrisAmount        = (float) $activeTx->amount;
                $this->qrisInvoiceNumber = $activeTx->invoice_number;
                $this->qrisExpiresIn     = (int) max(0, now()->diffInSeconds($activeTx->expired_at, false));
                $this->qrisExpiresAtMs   = $activeTx->expired_at?->valueOf();

                // Jika di cache show_qris bernilai true, buka kembali modal QRIS di kasir
                if (!empty($cachedCart['show_qris'])) {
                    $this->showQrisModal = true;
                }
            } elseif ($activeTx && $activeTx->isQrisExpiredByTime()) {
                $activeTx->transitionTo(
                    newStatus:   \App\Enums\PaymentTransactionStatus::Expired,
                    triggeredBy: 'system',
                    note:        'Kadaluarsa saat kasir membuka kembali halaman POS'
                );
            }
        }

        $this->broadcastCartState();
    }

    // -----------------------------------------------------------------
    // Computed Properties — semua query melalui Model
    // -----------------------------------------------------------------

    #[Computed]
    public function categories()
    {
        return Category::forPos()->get();
    }

    #[Computed]
    public function products()
    {
        $query = Product::forPosDisplay($this->selectedCategoryId, $this->searchQuery ?: null);

        $this->totalProductsCount = $query->count();

        return $query
            ->take($this->perPage)
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function paymentSources()
    {
        return PaymentSource::getForPos();
    }

    #[Computed]
    public function todayShift(): ?Shift
    {
        return Shift::getTodayShift(auth()->id());
    }

    #[Computed]
    public function shiftsTodayCount(): int
    {
        return Shift::where('user_id', auth()->id())
            ->whereDate('started_at', today())
            ->count();
    }

    #[Computed]
    public function unclosedPreviousShift(): ?Shift
    {
        return Shift::getUnclosedPreviousShift(auth()->id());
    }

    #[Computed]
    public function unclosedShiftModalData(): ?Shift
    {
        if (!$this->unclosedShiftId) {
            return null;
        }
        return Shift::with('completedTransactions')->find($this->unclosedShiftId);
    }

    #[Computed]
    public function logoWeb(): ?string
    {
        return Setting::get('logo_web', null, 'general');
    }

    #[Computed]
    public function logoFull(): ?string
    {
        return Setting::get('logo_full', null, 'general');
    }

    #[Computed]
    public function logoType(): string
    {
        return Setting::get('logo_type', 'single', 'general');
    }

    #[Computed]
    public function generalSettings(): array
    {
        return Setting::getGroup('general');
    }

    #[Computed]
    public function receiptSettings(): array
    {
        return Setting::getGroup('receipt');
    }

    #[Computed]
    public function todayTransactions()
    {
        return Transaction::getTodayUserTransactions(auth()->id());
    }

    #[Computed]
    public function selfOrdersCount(): int
    {
        return \App\Models\SelfOrder::countWaitingToday(auth()->id()) + \App\Models\SelfOrder::countPaidToday(auth()->id());
    }

    /**
     * Dipanggil oleh wire:poll setiap 10 detik pada header POS. Membandingkan jumlah
     * Self Order terhadap polling sebelumnya — kalau bertambah, tampilkan toast (tanpa suara).
     */
    public function checkForNewSelfOrders(): void
    {
        $count = $this->selfOrdersCount;

        if ($count > $this->notifiedSelfOrdersCount) {
            $diff = $count - $this->notifiedSelfOrdersCount;
            $this->dispatch('notify', type: 'info', message: $diff === 1
                ? 'Ada 1 pesanan mandiri baru masuk.'
                : "Ada {$diff} pesanan mandiri baru masuk.");
        }

        $this->notifiedSelfOrdersCount = $count;
    }

    #[On('selfOrderUpdated')]
    public function handleSelfOrderUpdated(): void
    {
        unset($this->selfOrdersCount);
        unset($this->todayTransactions);
    }

    #[Computed]
    public function todayReturns()
    {
        return ProductReturn::getTodayUserReturns(auth()->id());
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
        return PrinterConfig::getDefault();
    }

    #[Computed]
    public function serviceAreas()
    {
        return ServiceArea::active()->orderBy('sort_order')->get();
    }

    #[Computed]
    public function pagers()
    {
        return Pager::active()->orderBy('number')->get();
    }

    // -----------------------------------------------------------------
    // Cart Actions
    // -----------------------------------------------------------------

    public function selectCategory(?int $categoryId): void
    {
        $this->selectedCategoryId = $categoryId;
    }

    public function addToCart(int $productId, array $modifiers = []): void
    {
        $product = Product::getForPos($productId);

        if (!$product) {
            $this->dispatch('notify', type: 'error', message: 'Produk tidak ditemukan');
            return;
        }

        $cartKey    = $this->generateCartKey($productId, $modifiers);
        $currentQty = isset($this->cart[$cartKey]) ? $this->cart[$cartKey]['quantity'] : 0;
        $newQty     = $currentQty + 1;

        if ($product->hasBom()) {
            $totalInCart = $this->getTotalQuantityForProduct($product->id);
            $stockErrors = app(\App\Services\ComponentStockService::class)->checkBomAvailability($product->id, $totalInCart + 1);
            if (!empty($stockErrors)) {
                $err = $stockErrors[0];
                $this->dispatch('notify', type: 'error', message: "Stok komponen '{$err['component']}' tidak cukup (Tersisa: {$err['stock']} {$err['unit']}).");
                return;
            }
        } elseif ($product->track_stock) {
            if ($product->stock <= 0) {
                $this->dispatch('notify', type: 'error', message: "Stok '{$product->name}' habis (0). Produk tidak dapat dimasukkan ke keranjang.");
                return;
            }

            $totalInCart = $this->getTotalQuantityForProduct($product->id);
            if (($totalInCart + 1) > $product->stock) {
                $this->dispatch('notify', type: 'error', message: "Stok '{$product->name}' tidak cukup. Sisa stok tersedia: {$product->stock}");
                return;
            }
        }

        // Cek stok komponen modifier
        foreach ($modifiers as $modId => $modData) {
            if (!empty($modData['component_id'])) {
                $modQty = (float)($modData['qty'] ?? 1);
                $modErrors = app(\App\Services\ComponentStockService::class)->checkModifierAvailability((int)$modId, $modQty);
                if (!empty($modErrors)) {
                    $err = $modErrors[0];
                    $this->dispatch('notify', type: 'error', message: "Stok komponen '{$err['component']}' untuk modifier '{$modData['name']}' tidak cukup.");
                    return;
                }
            }
        }

        if (isset($this->cart[$cartKey])) {
            $this->cart[$cartKey]['quantity'] = $newQty;
            $this->recalculateCartItem($cartKey);
        } else {
            $modifierTotal = $this->calculateModifierTotal($modifiers);
            $subtotal      = $product->price + $modifierTotal;

            $this->cart[$cartKey] = [
                'product_id'    => $product->id,
                'product_name'  => $product->name,
                'unit_price'    => $product->price,
                'quantity'      => 1,
                'modifiers'     => $modifiers,
                'modifier_total' => $modifierTotal,
                'subtotal'      => $subtotal,
            ];
        }

        if ($this->qrisOrderId) {
            $this->cancelQris();
            $this->dispatch('notify', type: 'warning', message: 'Keranjang diubah. QRIS sebelumnya dibatalkan.');
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
            $product = Product::find($this->cart[$cartKey]['product_id']);
            if ($product && $product->hasBom()) {
                $currentTotal = $this->getTotalQuantityForProduct($product->id, $cartKey);
                $stockErrors = app(\App\Services\ComponentStockService::class)->checkBomAvailability($product->id, $currentTotal + $quantity);
                if (!empty($stockErrors)) {
                    $err = $stockErrors[0];
                    $this->dispatch('notify', type: 'error', message: "Stok komponen '{$err['component']}' tidak cukup (Tersisa: {$err['stock']} {$err['unit']}).");
                    return;
                }
            } elseif ($product && $product->track_stock) {
                $currentTotal = $this->getTotalQuantityForProduct($product->id, $cartKey);
                if (($currentTotal + $quantity) > $product->stock) {
                    $this->dispatch('notify', type: 'error', message: "Stok total tidak cukup. Max: {$product->stock}");
                    return;
                }
            }

            $this->cart[$cartKey]['quantity'] = $quantity;
            $this->recalculateCartItem($cartKey);

            if ($this->qrisOrderId) {
                $this->cancelQris();
                $this->dispatch('notify', type: 'warning', message: 'Keranjang diubah. QRIS sebelumnya dibatalkan.');
            }
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
        if ($this->qrisOrderId) {
            $this->cancelQris();
            $this->dispatch('notify', type: 'warning', message: 'Keranjang diubah. QRIS sebelumnya dibatalkan.');
        } else {
            $this->broadcastCartState();
        }
    }

    public function clearCart(): void
    {
        if ($this->qrisOrderId) {
            $this->cancelQris();
        }
        $this->cart                  = [];
        $this->paidAmount            = 0;
        $this->customerName          = '';
        $this->customerPhone         = '';
        $this->customerEmail         = '';
        $this->notes                 = '';
        $this->orderType             = 'dine_in';
        $this->selectedServiceAreaId = null;
        $this->selectedPagerId       = null;
        $this->broadcastCartState();
    }

    // -----------------------------------------------------------------
    // Payment Actions
    // -----------------------------------------------------------------

    public function openPaymentModal(): void
    {
        if (empty($this->cart)) {
            $this->dispatch('notify', type: 'error', message: 'Keranjang kosong');
            return;
        }

        $this->paidAmount            = $this->total;
        $this->paymentIdempotencyKey = (string) \Illuminate\Support\Str::uuid();
        $this->showPaymentModal      = true;
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

        if ($this->orderType === 'dine_in' && !$this->selectedServiceAreaId && !$this->selectedPagerId) {
            $this->dispatch('notify', type: 'error', message: 'Wajib pilih Meja/Ruangan atau isi Nomor Pager untuk Dine In');
            return;
        }

        // Routing: QRIS (Gateway Midtrans) atau Manual (Cash, Transfer, Card, E-Wallet)
        if ($paymentSource->type === 'qris') {
            $this->initiateQrisPayment();
        } else {
            $this->processManualPayment($paymentSource);
        }
    }

    /**
     * Proses pembayaran manual (Cash, Transfer, Card, E-Wallet) menggunakan InitiateCashPaymentAction.
     */
    private function processManualPayment(PaymentSource $paymentSource): void
    {
        if ($paymentSource->type === 'cash' && $this->paidAmount < $this->total) {
            $this->dispatch('notify', type: 'error', message: 'Jumlah pembayaran kurang');
            return;
        }

        if (empty($this->paymentIdempotencyKey)) {
            $this->paymentIdempotencyKey = (string) \Illuminate\Support\Str::uuid();
        }

        $cacheKey = 'payment_idem_' . auth()->id() . '_' . $this->paymentIdempotencyKey;

        if (Cache::has($cacheKey)) {
            $this->dispatch('notify', type: 'warning', message: 'Transaksi sebelumnya sedang diproses, mohon tunggu.');
            return;
        }

        Cache::put($cacheKey, true, now()->addSeconds(60));

        $payload = $this->buildCartPayload($paymentSource->type);

        try {
            $transaction = app(InitiateCashPaymentAction::class)->execute($payload, auth()->id());

            $this->lastTransaction       = $transaction;
            $this->clearCart();
            $this->paymentIdempotencyKey = '';
            $this->showPaymentModal      = false;
            $this->showReceiptModal      = true;
            $this->dispatch('print-receipt');
            $this->lastTransaction->increment('print_count');
            $this->dispatch('notify', type: 'success', message: 'Transaksi berhasil!');

        } catch (\Exception $e) {
            Cache::forget($cacheKey);
            $this->paymentIdempotencyKey = (string) \Illuminate\Support\Str::uuid();
            $this->dispatch('notify', type: 'error', message: 'Gagal memproses transaksi: ' . $e->getMessage());
        }
    }

    /**
     * Generate QR Code QRIS via Midtrans dan tampilkan modal QRIS.
     */
    public function initiateQrisPayment(): void
    {
        if (empty($this->cart)) {
            $this->dispatch('notify', type: 'error', message: 'Keranjang kosong');
            return;
        }

        $this->qrisGenerating = true;

        try {
            // Generate invoice number untuk sesi ini (belum ada di DB transactions)
            if (!$this->qrisInvoiceNumber) {
                $this->qrisInvoiceNumber = Transaction::generateInvoiceNumber();
            }

            // Setiap percobaan QRIS (pertama kali, atau ulang setelah kadaluarsa/dibatalkan via
            // regenerateQris()) membuat BARIS PaymentTransaction baru — idempotency_key harus unik
            // per percobaan. paymentIdempotencyKey sebelumnya hanya di-generate sekali saat modal
            // pembayaran dibuka (openPaymentModal) dan tidak pernah diperbarui untuk percobaan QRIS
            // berikutnya, sehingga insert kedua menabrak unique constraint idempotency_key di DB.
            $this->paymentIdempotencyKey = (string) \Illuminate\Support\Str::uuid();

            $payload = $this->buildCartPayload('qris');

            // Harga dihitung ulang & cart snapshot di-cache di dalam Action (dari data tervalidasi DB)
            $paymentTx = app(InitiateQrisPaymentAction::class)->execute(
                payload:       $payload,
                invoiceNumber: $this->qrisInvoiceNumber,
                cashierId:     auth()->id(),
            );

            $this->qrisOrderId   = $paymentTx->midtrans_order_id;
            $this->qrisCodeUrl   = $paymentTx->qr_code_url;
            $this->qrisAmount    = (float) $paymentTx->amount;
            $this->qrisExpiresIn   = $paymentTx->expired_at ? (int) now()->diffInSeconds($paymentTx->expired_at, false) : 300;
            $this->qrisExpiresAtMs = $paymentTx->expired_at?->valueOf();
            $this->showPaymentModal = false;
            $this->showQrisModal    = true;

            // Instruksikan frontend untuk mulai SSE listener
            $this->dispatch('qris-start-sse', orderId: $this->qrisOrderId);
            $this->broadcastCartState();

        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Gagal membuat QRIS: ' . $e->getMessage());
        } finally {
            $this->qrisGenerating = false;
        }
    }

    /**
     * Dipanggil oleh frontend (Alpine via $wire) saat SSE menerima event 'paid'.
     */
    public function onQrisPaid(int $transactionId): void
    {
        $transaction = Transaction::getForPrinting($transactionId);
        if (!$transaction) {
            $this->dispatch('notify', type: 'error', message: 'Transaksi tidak ditemukan.');
            return;
        }

        $this->lastTransaction  = $transaction;
        $this->showQrisModal    = false;
        $this->showReceiptModal = true;
        $this->qrisOrderId      = null;
        $this->qrisCodeUrl      = null;
        $this->qrisAmount       = 0;
        $this->qrisInvoiceNumber = null;

        $this->clearCart();
        $this->dispatch('print-receipt');
        $this->lastTransaction->increment('print_count');
        $this->dispatch('notify', type: 'success', message: 'Pembayaran QRIS berhasil!');
        $this->broadcastCartState();
    }

    /**
     * Dipanggil oleh frontend saat SSE menerima event 'expired'.
     */
    public function onQrisExpired(): void
    {
        $this->dispatch('notify', type: 'warning', message: 'QRIS kadaluarsa. Silakan buat QRIS baru.');
    }

    /**
     * Kasir membatalkan QRIS dan kembali ke pilihan metode pembayaran.
     */
    public function cancelQris(): void
    {
        if ($this->qrisOrderId) {
            $paymentTx = PaymentTransaction::where('midtrans_order_id', $this->qrisOrderId)->first();

            if ($paymentTx) {
                // Keamanan Tambahan: Cek status ke Midtrans API sebelum membatalkan
                if ($paymentTx->isPending()) {
                    try {
                        $midtransRes = app(MidtransService::class)->getTransactionStatus($this->qrisOrderId);
                        if (isset($midtransRes['transaction_status'])) {
                            $payload = \App\DTOs\Payment\MidtransWebhookPayload::fromArray($midtransRes);
                            app(\App\Actions\Payment\HandleMidtransWebhookAction::class)->execute($payload);
                            $paymentTx->refresh();
                        }
                    } catch (\Throwable $e) {
                        Log::warning("Cek status Midtrans saat cancel gagal: " . $e->getMessage());
                    }
                }

                // Jika ternyata pelanggan SUDAH membayar, BATALKAN proses cancel & proses sebagai transaksi berhasil!
                if ($paymentTx->isPaid() && $paymentTx->transaction_id) {
                    $this->dispatch('notify', type: 'warning', message: 'Tidak dapat membatalkan: Pembayaran Sudah berhasil!');
                    $this->onQrisPaid($paymentTx->transaction_id);
                    return;
                }

                // Jika belum dibayar, jalankan pembatalan secara sah
                if (!$paymentTx->isFinal()) {
                    app(CancelPaymentTransactionAction::class)->execute($paymentTx, auth()->id(), 'Dibatalkan kasir');
                }
            }
        }

        $this->dispatch('qris-stop-sse');
        $this->showQrisModal      = false;
        $this->qrisOrderId        = null;
        $this->qrisCodeUrl        = null;
        $this->qrisAmount         = 0;
        $this->qrisInvoiceNumber  = null;
        $this->showPaymentModal   = true; // Kembali ke modal pilih metode
        $this->broadcastCartState();
    }

    /**
     * Sembunyikan modal QRIS tanpa membatalkannya di Midtrans.
     * QRIS tetap aktif di background dan dapat dibuka kembali.
     */
    public function hideQrisModal(): void
    {
        $this->showQrisModal = false;
        $this->dispatch('notify', type: 'info', message: 'QRIS disembunyikan. Klik "Buka QRIS Aktif" untuk menampilkan kembali.');
        $this->broadcastCartState();
    }

    /**
     * Buka kembali modal QRIS yang sedang dibuat (termasuk jika kadaluarsa agar kasir dapat menekan "Buat QRIS Baru").
     */
    public function reopenQris(): void
    {
        if ($this->qrisOrderId && $this->qrisCodeUrl) {
            $paymentTx = PaymentTransaction::where('midtrans_order_id', $this->qrisOrderId)->first();
            if ($paymentTx && !$paymentTx->isPaid() && !$paymentTx->isCancelled()) {
                $this->qrisAmount       = (float) $paymentTx->amount;
                $this->qrisExpiresIn    = (int) max(0, now()->diffInSeconds($paymentTx->expired_at, false));
                $this->qrisExpiresAtMs  = $paymentTx->expired_at?->valueOf();
                $this->showPaymentModal = false;
                $this->showQrisModal    = true;
                $this->broadcastCartState();
                return;
            }
        }

        $this->dispatch('notify', type: 'error', message: 'Tidak ada QRIS aktif yang dapat dibuka.');
    }

    /**
     * Kasir minta QRIS baru (setelah expired).
     */
    public function regenerateQris(): void
    {
        // Reset order ID agar initiateQrisPayment generate baru
        // Invoice number tetap sama
        $this->qrisOrderId = null;
        $this->qrisCodeUrl = null;
        $this->qrisAmount  = 0;
        $this->showQrisModal = false;
        $this->showPaymentModal = false;

        $this->initiateQrisPayment();
    }

    /**
     * Cek status QRIS ke Midtrans secara aktif — dipanggil manual oleh kasir (klik tombol)
     * maupun otomatis secara berkala oleh frontend sebagai cadangan jika webhook/SSE
     * tidak sampai. $silent=true menekan toast "belum diterima" untuk polling otomatis
     * agar kasir tidak dispam notifikasi setiap beberapa detik.
     */
    public function checkQrisStatus(bool $silent = false): void
    {
        if (!$this->qrisOrderId) {
            return;
        }

        $paymentTx = PaymentTransaction::where('midtrans_order_id', $this->qrisOrderId)->first();
        if (!$paymentTx) {
            return;
        }

        if ($paymentTx->isPending()) {
            try {
                $midtransRes = app(MidtransService::class)->getTransactionStatus($this->qrisOrderId);
                if (isset($midtransRes['transaction_status'])) {
                    $payload = MidtransWebhookPayload::fromArray($midtransRes);
                    app(HandleMidtransWebhookAction::class)->execute($payload);
                    $paymentTx->refresh();
                }
            } catch (\Throwable $e) {
                Log::warning("Cek status Midtrans failed for [{$this->qrisOrderId}]: " . $e->getMessage());
            }
        }

        if ($paymentTx->isPaid() && $paymentTx->transaction_id) {
            $this->onQrisPaid($paymentTx->transaction_id);
        } elseif ($paymentTx->isExpired() || $paymentTx->isQrisExpiredByTime()) {
            $this->onQrisExpired();
        } elseif (!$silent) {
            $this->dispatch('notify', type: 'info', message: 'Pembayaran belum diterima. Silakan selesaikan transaksi.');
        }
    }

    /**
     * Build CartPayload dari state Livewire saat ini.
     */
    private function buildCartPayload(string $method): CartPayload
    {
        return new CartPayload(
            cart:            $this->cart,
            subtotal:        $this->subtotal,
            taxAmount:       $this->taxAmount,
            total:           $this->total,
            paidAmount:      $method === 'cash' ? $this->paidAmount : $this->total,
            changeAmount:    $method === 'cash' ? $this->changeAmount : 0,
            paymentSourceId: (int) $this->paymentSourceId,
            paymentMethod:   $method,
            customerName:    $this->customerName,
            customerPhone:   $this->customerPhone,
            customerEmail:   $this->customerEmail,
            orderType:       $this->orderType,
            serviceAreaId:   $this->orderType === 'dine_in' ? $this->selectedServiceAreaId : null,
            pagerId:         $this->selectedPagerId,
            notes:           $this->notes,
            idempotencyKey:  $this->paymentIdempotencyKey,
        );
    }

    public function closeReceiptModal(): void
    {
        $this->showReceiptModal = false;
        $this->lastTransaction  = null;
    }

    public function printReceipt(): void
    {
        $this->dispatch('print-receipt');
        $this->lastTransaction?->increment('print_count');
    }

    // -----------------------------------------------------------------
    // Shift Actions
    // -----------------------------------------------------------------

    // Modal konfirmasi buka shift manual (klik username) — pengganti window.confirm() bawaan browser.
    public bool $showOpenShiftModal = false;

    /**
     * Dipicu oleh komponen anak (SelfOrderDashboard) saat kasir mencoba klaim/proses/bayar
     * pesanan mandiri tapi belum punya shift terbuka — langsung tawarkan modal buka shift
     * di sini alih-alih kasir harus keluar dari modal Pesanan Mandiri terlebih dahulu.
     */
    #[On('open-shift-requested')]
    public function handleOpenShiftRequested(): void
    {
        $this->openShiftPrompt();
    }

    /**
     * Tampilkan modal konfirmasi buka shift. Dipanggil dari klik username/avatar di header,
     * agar kasir bisa membuka shift tanpa harus menjalankan transaksi terlebih dahulu
     * (sebelumnya shift baru otomatis terbuat diam-diam saat transaksi pertama diproses).
     */
    public function openShiftPrompt(): void
    {
        if ($this->todayShift && $this->todayShift->status === 'open') {
            // Sudah ada shift aktif — tidak perlu modal, cukup info.
            $this->dispatch('notify', type: 'info', message: 'Shift Anda sudah aktif.');
            return;
        }

        $this->showOpenShiftModal = true;
    }

    public function closeOpenShiftPrompt(): void
    {
        $this->showOpenShiftModal = false;
    }

    /**
     * Eksekusi pembukaan shift setelah kasir konfirmasi di modal custom (bukan browser confirm()).
     */
    public function confirmOpenShift(): void
    {
        // getOrCreateTodayShift() sudah idempotent (defense-in-depth kalau modal sempat ter-double-click).
        Shift::getOrCreateTodayShift(auth()->id());

        // Bersihkan cache computed properties supaya header langsung mencerminkan shift baru.
        unset($this->todayShift, $this->shiftsTodayCount);

        $this->showOpenShiftModal = false;
        $this->dispatch('notify', type: 'success', message: 'Shift berhasil dibuka. Selamat bertugas!');
    }

    public function openCloseShiftModal(): void
    {
        $shift = $this->todayShift;
        if (!$shift) {
            $this->dispatch('notify', type: 'error', message: 'Tidak ada transaksi hari ini');
            return;
        }

        // Keamanan 1: Cek Pesanan Pending (Held Carts)
        $pendingCarts = $this->pendingCarts;
        if (!empty($pendingCarts)) {
            $count = count($pendingCarts);
            $this->dispatch('notify', type: 'error', message: "Tidak dapat menutup shift: Masih ada {$count} pesanan pending (ditunda). Harap selesaikan atau batalkan pesanan pending terlebih dahulu.");
            return;
        }

        // Keamanan 2: Cek QRIS Aktif
        if (!empty($this->qrisOrderId)) {
            $this->dispatch('notify', type: 'error', message: "Tidak dapat menutup shift: Masih ada transaksi QRIS yang sedang aktif. Harap batalkan atau selesaikan QRIS terlebih dahulu.");
            return;
        }

        // Keamanan 3: Cek Keranjang Aktif di Layar POS
        if (!empty($this->cart)) {
            $this->dispatch('notify', type: 'error', message: "Tidak dapat menutup shift: Keranjang aktif POS masih terisi item. Kosongkan atau selesaikan transaksi terlebih dahulu.");
            return;
        }

        // Keamanan 4: Cek Self Order aktif yang terikat ke shift ini (belum Completed/Cancelled/Expired)
        $activeSelfOrders = $this->countActiveSelfOrdersForShift($shift->id);
        if ($activeSelfOrders > 0) {
            $this->dispatch('notify', type: 'error', message: "Tidak dapat menutup shift: Masih ada {$activeSelfOrders} pesanan mandiri yang belum selesai diproses. Selesaikan, alihkan ke kasir lain, atau tunggu sampai selesai terlebih dahulu.");
            return;
        }

        $pendingItems = TransactionDetail::getPendingItemsForShift($shift->id);

        $this->pendingFoodCount  = $pendingItems->filter(fn($item) => $item->product->category->target_kitchen?->value !== 'drink')->count();
        $this->pendingDrinkCount = $pendingItems->filter(fn($item) => $item->product->category->target_kitchen?->value === 'drink')->count();

        $this->openingCash    = 0;
        $this->actualCash     = 0;
        $this->expectedNonCash = (float) $shift->calculateExpectedNonCash();
        $this->actualNonCash  = 0;
        $this->closeNotes     = '';
        $this->expenses       = [];
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

        if ($shift->status !== 'open') {
            $this->dispatch('notify', type: 'error', message: 'Shift ini sudah ditutup');
            return;
        }

        // Keamanan: Re-validate pesanan pending & keranjang aktif
        if (!empty($this->pendingCarts)) {
            $this->dispatch('notify', type: 'error', message: 'Gagal menutup shift: Masih ada pesanan pending yang belum diselesaikan.');
            return;
        }
        if (!empty($this->cart)) {
            $this->dispatch('notify', type: 'error', message: 'Gagal menutup shift: Keranjang aktif POS belum kosong.');
            return;
        }
        if (!empty($this->qrisOrderId)) {
            $this->dispatch('notify', type: 'error', message: 'Gagal menutup shift: Transaksi QRIS masih aktif.');
            return;
        }
        $activeSelfOrders = $this->countActiveSelfOrdersForShift($shift->id);
        if ($activeSelfOrders > 0) {
            $this->dispatch('notify', type: 'error', message: "Gagal menutup shift: Masih ada {$activeSelfOrders} pesanan mandiri yang belum selesai diproses.");
            return;
        }

        if (!is_numeric($this->openingCash) || $this->openingCash < 0) {
            $this->dispatch('notify', type: 'error', message: 'Modal Awal harus diisi dengan valid (minimal 0)');
            return;
        }
        if (!is_numeric($this->actualCash) || $this->actualCash < 0) {
            $this->dispatch('notify', type: 'error', message: 'Uang Fisik Tunai harus diisi dengan valid (minimal 0)');
            return;
        }
        if (!is_numeric($this->actualNonCash) || $this->actualNonCash < 0) {
            $this->dispatch('notify', type: 'error', message: 'Uang Non-Tunai harus diisi dengan valid (minimal 0)');
            return;
        }

        DB::transaction(function () use ($shift) {
            $shift->opening_cash = (float) $this->openingCash;
            $shift->save();

            foreach ($this->expenses as $expense) {
                if (!empty($expense['description']) && $expense['amount'] > 0) {
                    $shift->expenses()->create([
                        'description' => $expense['description'],
                        'amount'      => $expense['amount'],
                        'category'    => 'operational',
                    ]);
                }
            }

            $shift->close(
                actualCash:    (float) $this->actualCash,
                actualNonCash: (float) $this->actualNonCash,
                notes:         $this->closeNotes ?: null
            );
        });

        // Sinkronisasi shift ke DB Laporan
        app(\App\Services\ReportSyncService::class)->syncShift($shift->load('expenses'));

        // Bersihkan cache pending & active cart untuk kasir ini setelah shift ditutup
        Cache::forget('pos_pending_carts_' . auth()->id());
        Cache::forget('pos_active_cart_' . auth()->id());

        $this->showCloseShiftModal = false;
        $this->dispatch('notify', type: 'success', message: 'Shift berhasil ditutup');
        $this->printShift             = $shift;
        $this->showShiftReceiptModal  = true;
        $this->dispatch('print-shift-receipt');
    }

    public function openClosePreviousShiftModal(): void
    {
        $shift = $this->unclosedPreviousShift;
        if (!$shift) {
            return;
        }

        $this->unclosedShiftId  = $shift->id;
        $this->openingCash      = 0;
        $this->actualCash       = 0;
        $this->expectedNonCash  = (float) $shift->calculateExpectedNonCash();
        $this->actualNonCash    = 0;
        $this->closeNotes       = '';
        $this->expenses         = [];
        $this->showUnclosedShiftModal = true;
    }

    public function closePreviousShift(): void
    {
        $shift = Shift::find($this->unclosedShiftId);
        if (!$shift || $shift->user_id !== auth()->id()) {
            $this->dispatch('notify', type: 'error', message: 'Shift tidak ditemukan');
            return;
        }

        if ($shift->status !== 'open') {
            $this->dispatch('notify', type: 'error', message: 'Shift ini sudah ditutup');
            return;
        }

        $activeSelfOrders = $this->countActiveSelfOrdersForShift($shift->id);
        if ($activeSelfOrders > 0) {
            $this->dispatch('notify', type: 'error', message: "Tidak dapat menutup shift: Masih ada {$activeSelfOrders} pesanan mandiri yang belum selesai diproses pada shift ini.");
            return;
        }

        if (!is_numeric($this->openingCash) || $this->openingCash < 0) {
            $this->dispatch('notify', type: 'error', message: 'Modal Awal harus diisi dengan valid (minimal 0)');
            return;
        }
        if (!is_numeric($this->actualCash) || $this->actualCash < 0) {
            $this->dispatch('notify', type: 'error', message: 'Uang Fisik Tunai harus diisi dengan valid (minimal 0)');
            return;
        }
        if (!is_numeric($this->actualNonCash) || $this->actualNonCash < 0) {
            $this->dispatch('notify', type: 'error', message: 'Uang Non-Tunai harus diisi dengan valid (minimal 0)');
            return;
        }

        DB::transaction(function () use ($shift) {
            $shift->opening_cash = (float) $this->openingCash;
            $shift->save();

            foreach ($this->expenses as $expense) {
                if (!empty($expense['description']) && $expense['amount'] > 0) {
                    $shift->expenses()->create([
                        'description' => $expense['description'],
                        'amount'      => $expense['amount'],
                        'category'    => 'operational',
                    ]);
                }
            }

            $notes = trim(($this->closeNotes ?? '') . ' [DITUTUP TERLAMBAT]');
            $shift->close(
                actualCash:    (float) $this->actualCash,
                actualNonCash: (float) $this->actualNonCash,
                notes:         $notes
            );
        });

        // Sinkronisasi shift ke DB Laporan
        app(\App\Services\ReportSyncService::class)->syncShift($shift->load('expenses'));

        $this->showUnclosedShiftModal = false;
        $this->unclosedShiftId        = null;
        $this->dispatch('notify', type: 'success', message: 'Shift sebelumnya berhasil ditutup. Anda bisa mulai transaksi.');
        $this->printShift            = $shift;
        $this->showShiftReceiptModal = true;
        $this->dispatch('print-shift-receipt');
    }

    // -----------------------------------------------------------------
    // Helper Methods
    // -----------------------------------------------------------------

    /**
     * Hitung Self Order yang masih aktif (belum Completed/Cancelled/Expired) dan
     * terikat ke shift tertentu. Dipakai untuk mencegah shift ditutup selagi masih
     * ada pesanan mandiri yang belum selesai diproses/diambil pelanggan.
     */
    protected function countActiveSelfOrdersForShift(int $shiftId): int
    {
        return \App\Models\SelfOrder::where('shift_id', $shiftId)->active()->count();
    }

    protected function generateCartKey(int $productId, array $modifiers): string
    {
        $parts = [];
        foreach ($modifiers as $modId => $mod) {
            $qty = (int) ($mod['qty'] ?? 1);
            $parts[] = $modId . 'x' . $qty;
        }
        sort($parts);
        return $productId . '_' . implode('_', $parts);
    }

    protected function calculateModifierTotal(array $modifiers): float
    {
        return collect($modifiers)->sum(function ($mod) {
            $price = (float) ($mod['price'] ?? 0);
            $qty   = (int) ($mod['qty'] ?? 1);
            return $price * $qty;
        });
    }

    protected function recalculateCartItem(string $cartKey): void
    {
        if (!isset($this->cart[$cartKey])) {
            return;
        }

        $item     = $this->cart[$cartKey];
        $basePrice = $item['unit_price'] + $item['modifier_total'];
        $this->cart[$cartKey]['subtotal'] = $basePrice * $item['quantity'];

        $this->broadcastCartState();
    }

    /**
     * Broadcast current state ke cache untuk Customer Display.
     */
    public function broadcastCartState(): void
    {
        $data = [
            'cart'                     => $this->cart,
            'subtotal'                 => $this->subtotal,
            'tax_amount'               => $this->taxAmount,
            'total'                    => $this->total,
            'paid_amount'              => $this->paidAmount,
            'change_amount'            => $this->changeAmount,
            'customer_name'            => $this->customerName,
            'customer_phone'           => $this->customerPhone,
            'customer_email'           => $this->customerEmail,
            'notes'                    => $this->notes,
            'order_type'               => $this->orderType,
            'selected_service_area_id' => $this->selectedServiceAreaId,
            'selected_pager_id'        => $this->selectedPagerId,
            'cashier_name'             => auth()->user()->name,
            'qris_order_id'            => $this->qrisOrderId,
            'qris_code_url'            => $this->qrisCodeUrl,
            'qris_amount'              => $this->qrisAmount,
            'qris_expires_in'          => $this->qrisExpiresIn,
            'qris_expires_at_ms'       => $this->qrisExpiresAtMs,
            'show_qris'                => $this->showQrisModal,
            // Presisi milidetik: dua broadcastCartState() bisa terjadi dalam detik yang sama
            // (mis. saat initiateQrisPayment), jadi timestamp detik saja tidak cukup untuk
            // menentukan mana data yang lebih baru di sisi Customer Display.
            'updated_at'               => (int) round(microtime(true) * 1000),
        ];

        \Illuminate\Support\Facades\Cache::put('pos_active_cart_' . auth()->id(), $data, now()->addHours(12));
        $this->dispatch('cart-updated', data: $data);
    }

    // -----------------------------------------------------------------
    // Pending Cart Methods
    // -----------------------------------------------------------------

    public bool $showPendingModal = false;
    public string $pendingCustomerName = '';

    public function openPendingModal(): void
    {
        $this->showPendingModal = true;
    }

    public function closePendingModal(): void
    {
        $this->showPendingModal = false;
    }

    public function holdCart(): void
    {
        if (empty($this->cart)) {
            $this->dispatch('notify', type: 'error', message: 'Keranjang masih kosong, tidak ada pesanan untuk ditunda.');
            return;
        }

        $pendingCarts = \Illuminate\Support\Facades\Cache::get('pos_pending_carts_' . auth()->id(), []);
        
        $pendingId = 'pending_' . uniqid() . '_' . time();
        $label = !empty(trim($this->customerName)) 
            ? trim($this->customerName) 
            : (!empty(trim($this->pendingCustomerName)) ? trim($this->pendingCustomerName) : 'Pelanggan #' . (count($pendingCarts) + 1));

        $pendingData = [
            'id'                       => $pendingId,
            'label'                    => $label,
            'customer_name'            => $this->customerName,
            'customer_phone'           => $this->customerPhone,
            'customer_email'           => $this->customerEmail,
            'cart'                     => $this->cart,
            'subtotal'                 => $this->subtotal,
            'tax_amount'               => $this->taxAmount,
            'total'                    => $this->total,
            'notes'                    => $this->notes,
            'order_type'               => $this->orderType,
            'selected_service_area_id' => $this->selectedServiceAreaId,
            'selected_pager_id'        => $this->selectedPagerId,
            'item_count'               => array_sum(array_column($this->cart, 'quantity')),
            'held_at'                  => now()->format('H:i'),
            'timestamp'                => now()->timestamp,
        ];

        array_unshift($pendingCarts, $pendingData);
        \Illuminate\Support\Facades\Cache::put('pos_pending_carts_' . auth()->id(), $pendingCarts, now()->addHours(12));

        if ($this->qrisOrderId) {
            $this->cancelQris();
        }

        $this->cart = [];
        $this->customerName = '';
        $this->customerPhone = '';
        $this->customerEmail = '';
        $this->notes = '';
        $this->paidAmount = 0;
        $this->pendingCustomerName = '';

        $this->broadcastCartState();
        $this->dispatch('notify', type: 'success', message: "Pesanan '{$label}' berhasil ditunda (Pending)");
    }

    #[Computed]
    public function pendingCarts(): array
    {
        return \Illuminate\Support\Facades\Cache::get('pos_pending_carts_' . auth()->id(), []);
    }

    public function restorePendingCart(string $pendingId): void
    {
        if ($this->qrisOrderId) {
            $this->cancelQris();
        }

        $pendingCarts = \Illuminate\Support\Facades\Cache::get('pos_pending_carts_' . auth()->id(), []);
        $targetIndex = null;
        $targetCart = null;

        foreach ($pendingCarts as $index => $item) {
            if ($item['id'] === $pendingId) {
                $targetIndex = $index;
                $targetCart = $item;
                break;
            }
        }

        if (!$targetCart) {
            $this->dispatch('notify', type: 'error', message: 'Pesanan pending tidak ditemukan');
            return;
        }

        // Jika keranjang aktif saat ini terisi, otomatis pindahkan ke pending dahulu agar tidak tertimpa
        if (!empty($this->cart)) {
            $currentLabel = !empty(trim($this->customerName)) ? trim($this->customerName) : 'Pelanggan #' . (count($pendingCarts) + 1);
            $currentPendingData = [
                'id'                       => 'pending_' . uniqid() . '_' . time(),
                'label'                    => $currentLabel,
                'customer_name'            => $this->customerName,
                'customer_phone'           => $this->customerPhone,
                'customer_email'           => $this->customerEmail,
                'cart'                     => $this->cart,
                'subtotal'                 => $this->subtotal,
                'tax_amount'               => $this->taxAmount,
                'total'                    => $this->total,
                'notes'                    => $this->notes,
                'order_type'               => $this->orderType,
                'selected_service_area_id' => $this->selectedServiceAreaId,
                'item_count'               => array_sum(array_column($this->cart, 'quantity')),
                'held_at'                  => now()->format('H:i'),
                'timestamp'                => now()->timestamp,
            ];
            array_unshift($pendingCarts, $currentPendingData);
        }

        $this->cart                  = $targetCart['cart'] ?? [];
        $this->customerName          = $targetCart['customer_name'] ?? '';
        $this->customerPhone         = $targetCart['customer_phone'] ?? '';
        $this->customerEmail         = $targetCart['customer_email'] ?? '';
        $this->notes                 = $targetCart['notes'] ?? '';
        $this->orderType             = $targetCart['order_type'] ?? 'dine_in';
        $this->selectedServiceAreaId = $targetCart['selected_service_area_id'] ?? null;
        $this->selectedPagerId       = $targetCart['selected_pager_id'] ?? null;
        $this->paidAmount            = 0;

        if ($targetIndex !== null) {
            unset($pendingCarts[$targetIndex]);
            $pendingCarts = array_values($pendingCarts);
        }

        \Illuminate\Support\Facades\Cache::put('pos_pending_carts_' . auth()->id(), $pendingCarts, now()->addHours(12));

        $this->broadcastCartState();
        $this->showPendingModal = false;
        $this->dispatch('notify', type: 'success', message: 'Pesanan pending berhasil dimuat kembali');
    }

    public ?string $confirmDeletePendingId = null;

    public function confirmDeletePending(string $pendingId): void
    {
        $this->confirmDeletePendingId = $pendingId;
    }

    public function cancelDeletePending(): void
    {
        $this->confirmDeletePendingId = null;
    }

    public function deletePendingCart(?string $pendingId = null): void
    {
        $idToDelete = $pendingId ?? $this->confirmDeletePendingId;
        if (!$idToDelete) {
            return;
        }

        $pendingCarts = \Illuminate\Support\Facades\Cache::get('pos_pending_carts_' . auth()->id(), []);
        $pendingCarts = array_filter($pendingCarts, fn($item) => $item['id'] !== $idToDelete);
        $pendingCarts = array_values($pendingCarts);

        \Illuminate\Support\Facades\Cache::put('pos_pending_carts_' . auth()->id(), $pendingCarts, now()->addHours(12));
        $this->confirmDeletePendingId = null;
        $this->dispatch('notify', type: 'info', message: 'Pesanan pending berhasil dihapus');
    }

    public function render()
    {
        return view('livewire.pos-checkout');
    }
}
