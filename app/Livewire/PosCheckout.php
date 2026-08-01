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
use App\Models\ServiceArea;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\ShiftExpense;
use App\Models\StockLog;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.pos')]
class PosCheckout extends Component
{
    // Cart state
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
    public bool $showShiftReceiptModal = false;
    public ?Shift $printShift = null;

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

    // ── QRIS Payment State ───────────────────────────────────────────────
    public bool $showQrisModal = false;
    public ?string $qrisOrderId = null;       // Midtrans order_id untuk SSE
    public ?string $qrisCodeUrl = null;       // URL QR code image dari Midtrans
    public ?string $qrisInvoiceNumber = null; // Invoice number yang sedang menunggu QRIS
    public int $qrisExpiresIn = 0;            // Countdown dalam detik (diupdate oleh SSE)
    public bool $qrisGenerating = false;      // Loading state saat generate QR

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
                if ($detail && $detail->product && $detail->product->track_stock) {
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

    public function mount(): void
    {
        $this->paymentSourceId = PaymentSource::getDefaultCash()?->id;

        $userId = auth()->id();
        if ($userId) {
            $cachedCart = Cache::get('pos_active_cart_' . $userId);
            if (!empty($cachedCart) && is_array($cachedCart)) {
                $this->cart                  = $cachedCart['cart'] ?? [];
                $this->paidAmount            = $cachedCart['paid_amount'] ?? 0;
                $this->customerName          = $cachedCart['customer_name'] ?? '';
                $this->notes                 = $cachedCart['notes'] ?? '';
                $this->orderType             = $cachedCart['order_type'] ?? 'dine_in';
                $this->selectedServiceAreaId = $cachedCart['selected_service_area_id'] ?? null;
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
                $this->qrisInvoiceNumber = $activeTx->invoice_number;
                $this->qrisExpiresIn     = (int) max(0, now()->diffInSeconds($activeTx->expired_at, false));

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
        return PaymentSource::active()->ordered()->get();
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
    public function todayTransactions()
    {
        return Transaction::getTodayUserTransactions(auth()->id());
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

        if ($product->track_stock) {
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
        $this->cart                  = [];
        $this->paidAmount            = 0;
        $this->customerName          = '';
        $this->notes                 = '';
        $this->orderType             = 'dine_in';
        $this->selectedServiceAreaId = null;
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

        if ($this->orderType === 'dine_in' && !$this->selectedServiceAreaId) {
            $this->dispatch('notify', type: 'error', message: 'Wajib pilih Meja / Ruangan untuk Dine In');
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

            $payload = $this->buildCartPayload('qris');

            // Simpan cart ke cache agar HandleMidtransWebhookAction bisa rekonstruksi
            $paymentTx = app(InitiateQrisPaymentAction::class)->execute(
                payload:       $payload,
                invoiceNumber: $this->qrisInvoiceNumber,
                cashierId:     auth()->id(),
            );

            // Cache cart data untuk webhook handler
            Cache::put(
                "qris_cart_{$paymentTx->midtrans_order_id}",
                array_merge($payload->toArray(), ['invoice_number' => $this->qrisInvoiceNumber]),
                now()->addMinutes(30)
            );

            $this->qrisOrderId   = $paymentTx->midtrans_order_id;
            $this->qrisCodeUrl   = $paymentTx->qr_code_url;
            $this->qrisExpiresIn = $paymentTx->expired_at ? (int) now()->diffInSeconds($paymentTx->expired_at, false) : 300;
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
     * Buka kembali modal QRIS yang sedang aktif & pending.
     */
    public function reopenQris(): void
    {
        if ($this->qrisOrderId && $this->qrisCodeUrl) {
            $paymentTx = PaymentTransaction::where('midtrans_order_id', $this->qrisOrderId)->first();
            if ($paymentTx && $paymentTx->isPending() && !$paymentTx->isQrisExpiredByTime()) {
                $this->qrisExpiresIn    = (int) max(0, now()->diffInSeconds($paymentTx->expired_at, false));
                $this->showPaymentModal = false;
                $this->showQrisModal    = true;
                $this->broadcastCartState();
                return;
            }
        }

        $this->dispatch('notify', type: 'error', message: 'Tidak ada QRIS aktif yang valid.');
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
        $this->showQrisModal = false;
        $this->showPaymentModal = false;

        $this->initiateQrisPayment();
    }

    /**
     * Cek status QRIS secara manual (fallback jika SSE terputus).
     */
    public function checkQrisStatus(): void
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
        } else {
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
            orderType:       $this->orderType,
            serviceAreaId:   $this->orderType === 'dine_in' ? $this->selectedServiceAreaId : null,
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

    public function openCloseShiftModal(): void
    {
        $shift = $this->todayShift;
        if (!$shift) {
            $this->dispatch('notify', type: 'error', message: 'Tidak ada transaksi hari ini');
            return;
        }

        $pendingItems = TransactionDetail::getPendingItemsForShift($shift->id);

        $this->pendingFoodCount  = $pendingItems->filter(fn($item) => $item->product->category->slug !== 'minuman')->count();
        $this->pendingDrinkCount = $pendingItems->filter(fn($item) => $item->product->category->slug === 'minuman')->count();

        $this->openingCash    = 0;
        $this->actualCash     = 0;
        $this->expectedNonCash = (float) $shift->calculateExpectedNonCash();
        $this->actualNonCash  = $this->expectedNonCash;
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
        $this->actualNonCash    = $this->expectedNonCash;
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
            'notes'                    => $this->notes,
            'order_type'               => $this->orderType,
            'selected_service_area_id' => $this->selectedServiceAreaId,
            'cashier_name'             => auth()->user()->name,
            'qris_order_id'            => $this->qrisOrderId,
            'qris_code_url'            => $this->qrisCodeUrl,
            'qris_expires_in'          => $this->qrisExpiresIn,
            'show_qris'                => $this->showQrisModal,
            'updated_at'               => now()->timestamp,
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

        array_unshift($pendingCarts, $pendingData);
        \Illuminate\Support\Facades\Cache::put('pos_pending_carts_' . auth()->id(), $pendingCarts, now()->addHours(12));

        $this->cart = [];
        $this->customerName = '';
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
        $this->notes                 = $targetCart['notes'] ?? '';
        $this->orderType             = $targetCart['order_type'] ?? 'dine_in';
        $this->selectedServiceAreaId = $targetCart['selected_service_area_id'] ?? null;
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
