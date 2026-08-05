<?php

namespace App\Livewire\SelfOrder;

use App\Actions\SelfOrder\PlaceSelfOrderAction;
use App\Enums\SelfOrderStatus;
use App\Exceptions\InsufficientStockException;
use App\Models\Category;
use App\Models\PaymentSource;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Self Order')]
class SelfOrderPage extends Component
{
    // -----------------------------------------------------------------
    // Wizard State
    // -----------------------------------------------------------------
    public string $step = 'menu'; // 'menu' | 'checkout'

    // -----------------------------------------------------------------
    // Cart (server-side, dilindungi #[Locked] dari manipulasi BurpSuite)
    // -----------------------------------------------------------------
    #[Locked]
    public array $cart = []; // ['productId_modKey' => ['product_id', 'quantity', 'unit_price', 'modifiers', ...]]

    // -----------------------------------------------------------------
    // Checkout Form
    // -----------------------------------------------------------------
    #[Rule(['required', 'string', 'min:2', 'max:100', 'regex:/^[\pL\s\-\'\.]+$/u'])]
    public string $customerName = '';

    #[Rule(['required', 'string', 'min:9', 'max:15', 'regex:/^(?:\+62|62|0)[0-9]{8,13}$/'])]
    public string $customerPhone = '';

    #[Rule(['nullable', 'email:rfc', 'max:150'])]
    public string $customerEmail = '';

    #[Rule(['required', 'in:qris,cash_on_counter'])]
    public string $paymentMethod = 'qris';

    #[Rule(['required', 'in:dine_in,take_away'])]
    public string $orderType = 'dine_in';

    #[Rule(['nullable', 'string', 'max:500'])]
    public string $notes = '';

    // -----------------------------------------------------------------
    // UI State (dilindungi #[Locked] dari client tampering)
    // -----------------------------------------------------------------
    #[Locked]
    public bool   $isSubmitting = false;

    public string $flashMessage = '';
    public string $flashType    = 'success'; // 'success' | 'error' | 'warning'

    // Filter menu
    public ?int   $selectedCategoryId = null;
    public string $searchQuery        = '';

    // Modifier modal (dilindungi #[Locked] dari IDOR/parameter injection)
    public bool   $showModifierModal  = false;

    #[Locked]
    public ?int   $modifierProductId  = null;

    public array  $selectedModifiers  = []; // Multiple choice (checkbox)
    public array  $singleModifiers    = []; // Single choice (radio)
    public array  $modifierQuantities = []; // Quantity per-modifier ID ['modifierId' => qty]
    public int    $modalQuantity      = 1;

    // Delete item confirmation modal
    public bool   $showDeleteConfirmModal = false;

    #[Locked]
    public ?string $deletingCartKey       = null;

    public string  $deletingProductName   = '';

    // -----------------------------------------------------------------
    // Lifecycle
    // -----------------------------------------------------------------

    public function mount(): void
    {
        // Idempotency key baru untuk setiap mount
        session()->put('self_order_idempotency_key', (string) Str::uuid());

        // Default payment method berdasarkan metode yang aktif di Self Order
        if ($this->isQrisAvailable) {
            $this->paymentMethod = 'qris';
        } elseif ($this->isCashAvailable) {
            $this->paymentMethod = 'cash_on_counter';
        } else {
            $this->paymentMethod = '';
        }
    }

    // -----------------------------------------------------------------
    // Computed
    // -----------------------------------------------------------------

    #[Computed(seconds: 300)]
    public function categories()
    {
        return Category::active()->ordered()->get();
    }

    #[Computed(seconds: 5)]
    public function products()
    {
        return Product::getForSelfOrderDisplay($this->selectedCategoryId, $this->searchQuery ?: null);
    }

    #[Computed]
    public function cartItems(): array
    {
        return array_values($this->cart);
    }

    #[Computed]
    public function cartCount(): int
    {
        return array_sum(array_column($this->cart, 'quantity'));
    }

    #[Computed]
    public function cartSubtotal(): float
    {
        return array_sum(array_map(
            fn($item) => $item['subtotal'],
            $this->cart
        ));
    }

    #[Computed]
    public function taxRate(): float
    {
        // Key 'tax_percentage' — sama dengan yang dipakai POS dan disimpan halaman Pengaturan.
        // ('tax_rate' yang lama tidak pernah di-set di mana pun, jadi pajak Self Order selalu 0%.)
        return (float) Cache::remember('setting_tax_percentage', 300, fn() => Setting::get('tax_percentage', 0, 'general'));
    }

    #[Computed]
    public function taxAmount(): float
    {
        return round($this->cartSubtotal * ($this->taxRate / 100), 2);
    }

    #[Computed]
    public function cartTotal(): float
    {
        return $this->cartSubtotal + $this->taxAmount;
    }

    #[Computed]
    public function storeName(): string
    {
        return Cache::remember('setting_store_name', 600, fn() => Setting::get('store_name', 'Resto', 'general'));
    }

    #[Computed]
    public function isQrisAvailable(): bool
    {
        return PaymentSource::isQrisActiveForSelfOrder();
    }

    #[Computed]
    public function isCashAvailable(): bool
    {
        return PaymentSource::isCashActiveForSelfOrder();
    }

    #[Computed]
    public function modalTotalPrice(): float
    {
        if (!$this->modifierProductId) return 0;

        $product = Product::find($this->modifierProductId);
        if (!$product) return 0;

        $basePrice = (float) $product->price;
        $modifierTotal = 0;

        // Hitung dari singleModifiers (radio)
        foreach ($this->singleModifiers as $groupId => $modifierId) {
            if ($modifierId) {
                $modifier = \App\Models\Modifier::find($modifierId);
                if ($modifier) {
                    $modifierTotal += (float) $modifier->price_adjustment;
                }
            }
        }

        // Hitung dari modifierQuantities (stepper per topping)
        foreach ($this->modifierQuantities as $modifierId => $qty) {
            if ($qty > 0) {
                $modifier = \App\Models\Modifier::find($modifierId);
                if ($modifier) {
                    $modifierTotal += (float) $modifier->price_adjustment * $qty;
                }
            }
        }

        return ($basePrice + $modifierTotal) * max(1, $this->modalQuantity);
    }

    // -----------------------------------------------------------------
    // Cart Methods
    // -----------------------------------------------------------------

    public function addToCart(int $productId, int $quantity = 1, array $modifiers = [], ?string $notes = null): void
    {
        $product = Product::find($productId);

        if (!$product || !$product->is_active) {
            $this->flashError('Produk tidak tersedia.');
            return;
        }

        // Generate cart key unik berdasarkan product + modifier combination
        $modKey    = empty($modifiers) ? '' : '_' . md5(serialize($modifiers));
        $cartKey   = "p{$productId}{$modKey}";
        $unitPrice = (float) $product->price;

        // Hitung modifier total
        $modifierTotal = 0;
        $modifiersData = [];
        if (!empty($modifiers)) {
            foreach ($modifiers as $modifierId => $modQty) {
                $modifier = \App\Models\Modifier::find($modifierId);
                if ($modifier) {
                    $modifierTotal  += $modifier->price_adjustment * $modQty;
                    $modifiersData[$modifierId] = [
                        'modifier_id'      => $modifier->id,
                        'modifier_name'    => $modifier->name,
                        'price_adjustment' => $modifier->price_adjustment,
                        'quantity'         => $modQty,
                    ];
                }
            }
        }

        if (isset($this->cart[$cartKey])) {
            $this->cart[$cartKey]['quantity'] += $quantity;
        } else {
            if (count($this->cart) >= config('self_order.max_cart_items', 15)) {
                $this->flashError('Maksimum ' . config('self_order.max_cart_items', 15) . ' jenis item dalam 1 pesanan.');
                return;
            }
            $this->cart[$cartKey] = [
                'cart_key'       => $cartKey,
                'product_id'     => $productId,
                'product_name'   => $product->name,
                'unit_price'     => $unitPrice,
                'quantity'       => $quantity,
                'modifier_total' => $modifierTotal,
                'modifiers'      => $modifiersData,
                'notes'          => $notes,
                'image'          => $product->image_url ?? null,
            ];
        }

        // Update subtotal
        $this->cart[$cartKey]['subtotal'] = ($unitPrice + $this->cart[$cartKey]['modifier_total']) * $this->cart[$cartKey]['quantity'];

        $this->closeModifierModal();
        $this->flashSuccess("'{$product->name}' ditambahkan ke pesanan.");
    }

    public function updateQuantity(string $cartKey, int $delta): void
    {
        if (!isset($this->cart[$cartKey])) return;

        $newQty = $this->cart[$cartKey]['quantity'] + $delta;
        $maxQty = config('self_order.max_item_quantity', 20);

        if ($newQty <= 0) {
            $this->confirmRemoveFromCart($cartKey);
            return;
        }

        if ($newQty > $maxQty) {
            $this->flashError("Maksimum {$maxQty} per item.");
            return;
        }

        $this->cart[$cartKey]['quantity'] = $newQty;
        $unitPrice     = $this->cart[$cartKey]['unit_price'];
        $modifierTotal = $this->cart[$cartKey]['modifier_total'];
        $this->cart[$cartKey]['subtotal'] = ($unitPrice + $modifierTotal) * $newQty;
    }

    public function confirmRemoveFromCart(string $cartKey): void
    {
        if (!isset($this->cart[$cartKey])) return;

        $this->deletingCartKey     = $cartKey;
        $this->deletingProductName = $this->cart[$cartKey]['product_name'];
        $this->showDeleteConfirmModal = true;
    }

    public function cancelRemoveFromCart(): void
    {
        $this->showDeleteConfirmModal = false;
        $this->deletingCartKey       = null;
        $this->deletingProductName   = '';
    }

    public function executeRemoveFromCart(): void
    {
        if ($this->deletingCartKey && isset($this->cart[$this->deletingCartKey])) {
            $productName = $this->cart[$this->deletingCartKey]['product_name'];
            unset($this->cart[$this->deletingCartKey]);
            $this->flashSuccess("'{$productName}' telah dihapus dari keranjang.");
        }

        $this->cancelRemoveFromCart();
    }

    public function removeFromCart(string $cartKey): void
    {
        $this->confirmRemoveFromCart($cartKey);
    }

    public function clearCart(): void
    {
        $this->cart = [];
    }

    // -----------------------------------------------------------------
    // Modifier Modal
    // -----------------------------------------------------------------

    public function openModifierModal(int $productId): void
    {
        $this->modifierProductId  = $productId;
        $this->selectedModifiers  = [];
        $this->singleModifiers    = [];
        $this->modifierQuantities = [];
        $this->modalQuantity      = 1;

        $product = Product::with('modifierGroups.activeModifiers')->find($productId);
        if ($product) {
            foreach ($product->modifierGroups as $group) {
                // Untuk grup pilihan tunggal (single), otomatis pilih opsi pertama sebagai default (persis seperti di POS)
                if ($group->selection_type === 'single' && $group->activeModifiers->isNotEmpty()) {
                    $this->singleModifiers[$group->id] = $group->activeModifiers->first()->id;
                }
            }
        }

        $this->showModifierModal = true;
    }

    public function closeModifierModal(): void
    {
        $this->showModifierModal  = false;
        $this->modifierProductId  = null;
        $this->selectedModifiers  = [];
        $this->singleModifiers    = [];
        $this->modifierQuantities = [];
        $this->modalQuantity      = 1;
    }

    public function updateModifierQty(int $modifierId, int $delta): void
    {
        $current = $this->modifierQuantities[$modifierId] ?? 0;
        $newQty  = max(0, min(10, $current + $delta));

        if ($newQty > 0) {
            $this->modifierQuantities[$modifierId] = $newQty;
            $this->selectedModifiers[$modifierId]  = 1;
        } else {
            unset($this->modifierQuantities[$modifierId]);
            unset($this->selectedModifiers[$modifierId]);
        }
    }

    public function addToCartFromModal(): void
    {
        if (!$this->modifierProductId) return;

        $product = Product::with('modifierGroups.activeModifiers')->find($this->modifierProductId);
        if (!$product) return;

        // Validasi grup wajib (is_required)
        foreach ($product->modifierGroups as $group) {
            if ($group->is_required) {
                if ($group->selection_type === 'single') {
                    if (empty($this->singleModifiers[$group->id])) {
                        $this->flashError("Pilih salah satu opsi wajib untuk '{$group->name}'.");
                        return;
                    }
                } else {
                    // Pilihan ganda yang wajib
                    $hasSelected = false;
                    foreach ($group->activeModifiers as $mod) {
                        if (!empty($this->modifierQuantities[$mod->id]) || !empty($this->selectedModifiers[$mod->id])) {
                            $hasSelected = true;
                            break;
                        }
                    }
                    if (!$hasSelected) {
                        $this->flashError("Pilih minimal 1 opsi untuk '{$group->name}'.");
                        return;
                    }
                }
            }
        }

        // Kombinasikan singleModifiers (radio) dan selectedModifiers (checkbox dengan Qty)
        $combinedModifiers = [];

        foreach ($this->singleModifiers as $groupId => $modifierId) {
            if ($modifierId) {
                $combinedModifiers[$modifierId] = 1;
            }
        }

        foreach ($this->selectedModifiers as $modifierId => $isSelected) {
            if ($isSelected) {
                $qty = $this->modifierQuantities[$modifierId] ?? 1;
                $combinedModifiers[$modifierId] = max(1, (int)$qty);
            }
        }

        $this->addToCart($this->modifierProductId, $this->modalQuantity, $combinedModifiers);
    }

    // -----------------------------------------------------------------
    // Wizard Navigation
    // -----------------------------------------------------------------

    public function goToCheckout(): void
    {
        $this->clearFlash();
        if (empty($this->cart)) {
            $this->flashError('Pilih menu terlebih dahulu.');
            return;
        }

        $this->step = 'checkout';
    }

    public function backToMenu(): void
    {
        $this->clearFlash();
        $this->step = 'menu';
    }

    public function updated($propertyName): void
    {
        // Bersihkan flash message global jika ada interaksi pengguna
        $this->flashMessage = '';

        // Validasi real-time per field yang sedang diubah jika pada step checkout
        if (in_array($propertyName, ['customerName', 'customerPhone', 'customerEmail', 'paymentMethod', 'orderType'])) {
            $this->validateOnly($propertyName);
        }
    }

    protected function messages(): array
    {
        return [
            'customerName.required'  => 'Nama pemesan wajib diisi.',
            'customerName.min'       => 'Nama pemesan minimal 2 karakter.',
            'customerName.regex'     => 'Nama pemesan hanya boleh berisi huruf dan spasi.',
            'customerPhone.required' => 'Nomor HP wajib diisi.',
            'customerPhone.min'      => 'Nomor HP minimal 9 digit.',
            'customerPhone.regex'    => 'Nomor HP harus berupa nomor Indonesia yang valid (contoh: 08123456789).',
            'customerEmail.email'    => 'Format alamat email tidak valid.',
            'paymentMethod.required' => 'Pilih metode pembayaran.',
            'orderType.required'     => 'Pilih tipe order.',
        ];
    }

    // -----------------------------------------------------------------
    // Order Submission
    // -----------------------------------------------------------------

    public function placeOrder(PlaceSelfOrderAction $action): void
    {
        if ($this->isSubmitting) return;

        // Reset pesan flash lama sebelum validasi
        $this->flashMessage = '';

        // Validasi form (jika ada error, pesan error spesifik per field akan tampil di bawah masing-masing kolom)
        $this->validate();

        if (empty($this->cart)) {
            $this->flashError('Keranjang kosong.');
            return;
        }

        $this->isSubmitting = true;
        $idempotencyKey     = session('self_order_idempotency_key');

        try {
            $items = array_map(function ($cartItem) {
                return [
                    'product_id' => $cartItem['product_id'],
                    'quantity'   => $cartItem['quantity'],
                    'modifiers'  => array_map(fn($m) => [
                        'modifier_id' => $m['modifier_id'],
                        'quantity'    => $m['quantity'],
                    ], array_values($cartItem['modifiers'] ?? [])),
                    'notes'      => $cartItem['notes'] ?? null,
                ];
            }, array_values($this->cart));

            $selfOrder = $action->execute([
                'customer_name'    => trim(strip_tags($this->customerName)),
                'customer_phone'   => trim(strip_tags($this->customerPhone)),
                'customer_email'   => $this->customerEmail ? trim(strip_tags($this->customerEmail)) : null,
                'payment_method'   => $this->paymentMethod,
                'order_type'       => $this->orderType,
                'notes'            => $this->notes ? trim(strip_tags($this->notes)) : null,
                'items'            => $items,
                'idempotency_key'  => $idempotencyKey,
            ], request()->ip());

            // Reset idempotency key setelah sukses
            session()->put('self_order_idempotency_key', (string) Str::uuid());
            $this->cart = [];

            if ($selfOrder->payment_method === 'qris') {
                $this->redirect(route('self-order.payment', ['token' => $selfOrder->order_token]), navigate: true);
            } else {
                $this->redirect(route('self-order.tracking', ['token' => $selfOrder->order_token]), navigate: true);
            }

        } catch (InsufficientStockException $e) {
            $this->flashError('Stok tidak cukup: ' . $e->getMessage());
        } catch (\RuntimeException $e) {
            $this->flashError($e->getMessage());
        } catch (\Illuminate\Database\QueryException $e) {
            // Kemungkinan double-submit yang lolos guard UI (mis. dua tab / request nyaris bersamaan)
            // menabrak unique constraint idempotency_key di DB. Ini BUKAN error nyata — order pertama
            // kemungkinan besar sudah berhasil dibuat. Ambil order tersebut dan redirect ke sana,
            // jangan tampilkan error generik ke customer.
            $existing = $idempotencyKey ? \App\Models\SelfOrder::existsByIdempotencyKey($idempotencyKey) : null;

            if ($existing) {
                session()->put('self_order_idempotency_key', (string) Str::uuid());
                $this->cart = [];

                if ($existing->payment_method === 'qris') {
                    $this->redirect(route('self-order.payment', ['token' => $existing->order_token]), navigate: true);
                } else {
                    $this->redirect(route('self-order.tracking', ['token' => $existing->order_token]), navigate: true);
                }
            } else {
                $this->flashError('Terjadi kesalahan sistem. Silakan coba lagi.');
                \Illuminate\Support\Facades\Log::error('Self Order placeOrder duplicate-key error: ' . $e->getMessage());
            }
        } catch (\Exception $e) {
            $this->flashError('Terjadi kesalahan sistem. Silakan coba lagi.');
            \Illuminate\Support\Facades\Log::error('Self Order placeOrder error: ' . $e->getMessage());
        } finally {
            $this->isSubmitting = false;
        }
    }

    // -----------------------------------------------------------------
    // Flash Messages
    // -----------------------------------------------------------------

    public function clearFlash(): void
    {
        $this->flashMessage = '';
    }

    private function flashSuccess(string $message): void
    {
        $this->flashMessage = $message;
        $this->flashType    = 'success';
    }

    private function flashError(string $message): void
    {
        $this->flashMessage = $message;
        $this->flashType    = 'error';
    }

    // -----------------------------------------------------------------
    // Render
    // -----------------------------------------------------------------

    public function render()
    {
        return view('livewire.self-order.self-order-page')
            ->layout('layouts.self-order');
    }
}
