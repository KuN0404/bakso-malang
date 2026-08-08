<?php

namespace App\Livewire\Admin;

use App\Models\Ingredient;
use App\Models\IngredientStockLog;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class Purchases extends Component
{
    use WithPagination;

    public bool $showModal = false;
    public bool $showDetailModal = false;
    public ?Purchase $selectedPurchase = null;

    // Form fields
    public string $invoice_number = '';
    public string $purchase_date = '';
    public ?string $supplier_name = '';
    public ?string $note = '';

    // Dynamic Purchase Items
    public array $items = [];

    #[Url(except: '')]
    public string $search = '';

    public int $perPage = 10;

    public function generateInvoiceNumber(): string
    {
        $prefix = 'INV-PO';
        $timestamp = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -4));
        return "{$prefix}-{$timestamp}-{$random}";
    }

    public function create(): void
    {
        $this->reset(['supplier_name', 'note']);
        $this->invoice_number = $this->generateInvoiceNumber();
        $this->purchase_date = now()->format('Y-m-d');
        $this->items = [
            [
                'item_type' => 'ingredient',
                'ingredient_id' => '',
                'product_id' => '',
                'quantity' => 1,
                'unit_price' => 0,
                'subtotal' => 0,
            ]
        ];
        $this->showModal = true;
    }

    public function addItem(): void
    {
        $this->items[] = [
            'item_type' => 'ingredient',
            'ingredient_id' => '',
            'product_id' => '',
            'quantity' => 1,
            'unit_price' => 0,
            'subtotal' => 0,
        ];
    }

    public function removeItem(int $index): void
    {
        if (count($this->items) > 1) {
            unset($this->items[$index]);
            $this->items = array_values($this->items);
        }
    }

    public function updatedItems($value, $key): void
    {
        // Auto calculate subtotal when quantity or unit_price changes
        $parts = explode('.', $key);
        if (count($parts) === 2) {
            $index = $parts[0];
            $field = $parts[1];

            if ($field === 'quantity' || $field === 'unit_price') {
                $qty = (float) ($this->items[$index]['quantity'] ?? 0);
                $price = (float) ($this->items[$index]['unit_price'] ?? 0);
                $this->items[$index]['subtotal'] = $qty * $price;
            }

            if ($field === 'item_type') {
                $this->items[$index]['ingredient_id'] = '';
                $this->items[$index]['product_id'] = '';
            }
        }
    }

    public function getTotalAmountProperty(): float
    {
        return array_reduce($this->items, function ($carry, $item) {
            $qty = (float) ($item['quantity'] ?? 0);
            $price = (float) ($item['unit_price'] ?? 0);
            return $carry + ($qty * $price);
        }, 0.0);
    }

    public function save(): void
    {
        $this->authorize('create_purchases');

        $this->validate([
            'invoice_number' => 'required|string|max:50|unique:purchases,invoice_number',
            'purchase_date' => 'required|date',
            'supplier_name' => 'nullable|string|max:150',
            'note' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.item_type' => 'required|in:ingredient,product',
            'items.*.ingredient_id' => 'nullable|exists:ingredients,id',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.quantity' => 'required|numeric|gt:0',
            'items.*.unit_price' => 'required|numeric|min:0',
        ], [
            'invoice_number.unique' => 'Nomor faktur ini sudah terdaftar.',
            'items.min' => 'Pembelian minimal harus berisi 1 barang.',
            'items.*.quantity.gt' => 'Jumlah harus lebih besar dari 0.',
            'items.*.ingredient_id.exists' => 'Bahan baku pada salah satu item sudah tidak ada / dihapus.',
            'items.*.product_id.exists' => 'Produk pada salah satu item sudah tidak ada / dihapus.',
        ]);

        // Validate selected ids
        foreach ($this->items as $idx => $item) {
            if ($item['item_type'] === 'ingredient' && empty($item['ingredient_id'])) {
                $this->dispatch('notify', type: 'error', message: "Silakan pilih Bahan Baku pada item baris ke-" . ($idx + 1));
                return;
            }
            if ($item['item_type'] === 'product' && empty($item['product_id'])) {
                $this->dispatch('notify', type: 'error', message: "Silakan pilih Produk pada item baris ke-" . ($idx + 1));
                return;
            }
        }

        $purchase = null;
        DB::transaction(function () use (&$purchase) {
            $totalAmount = $this->totalAmount;

            $purchase = Purchase::create([
                'invoice_number' => $this->invoice_number,
                'purchase_date' => $this->purchase_date,
                'supplier_name' => $this->supplier_name ?: null,
                'total_amount' => $totalAmount,
                'note' => $this->note,
                'status' => 'completed',
                'user_id' => Auth::id(),
            ]);

            foreach ($this->items as $item) {
                $qty = (float) $item['quantity'];
                $price = (float) $item['unit_price'];
                $subtotal = $qty * $price;

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'item_type' => $item['item_type'],
                    'ingredient_id' => $item['item_type'] === 'ingredient' ? $item['ingredient_id'] : null,
                    'product_id' => $item['item_type'] === 'product' ? $item['product_id'] : null,
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'subtotal' => $subtotal,
                ]);

                if ($item['item_type'] === 'ingredient') {
                    $ingredient = Ingredient::where('id', $item['ingredient_id'])->lockForUpdate()->first();
                    if ($ingredient) {
                        $oldStock = $ingredient->stock;
                        $oldCost = $ingredient->cost_price;
                        $newStock = $oldStock + $qty;

                        // Weighted Average HPP Calculation
                        $newCost = ($newStock > 0)
                            ? (($oldStock * $oldCost) + ($qty * $price)) / $newStock
                            : $price;

                        $ingredient->update([
                            'stock' => $newStock,
                            'cost_price' => round($newCost, 2),
                        ]);

                        IngredientStockLog::record(
                            ingredientId: $ingredient->id,
                            userId: Auth::id(),
                            type: 'purchase',
                            amount: $qty,
                            finalStock: $newStock,
                            note: "Pembelian Faktur: {$purchase->invoice_number}",
                            referenceId: $purchase->id
                        );
                    }
                } else {
                    $product = Product::where('id', $item['product_id'])->lockForUpdate()->first();
                    if ($product) {
                        $oldStock = $product->stock;
                        $oldCost = $product->cost_price;
                        $newStock = $oldStock + (int) $qty;

                        // Weighted Average HPP Calculation
                        $newCost = ($newStock > 0)
                            ? (($oldStock * $oldCost) + ($qty * $price)) / $newStock
                            : $price;

                        $product->update([
                            'stock' => $newStock,
                            'cost_price' => round($newCost, 2),
                            'track_stock' => true,
                        ]);

                        StockLog::record(
                            productId: $product->id,
                            userId: Auth::id(),
                            type: 'add',
                            amount: (int) $qty,
                            finalStock: $newStock,
                            note: "Pembelian Langsung Faktur: {$purchase->invoice_number}",
                            referenceId: $purchase->id
                        );
                    }
                }
            }
        });

        $this->showModal = false;
        $this->dispatch('notify', type: 'success', message: 'Transaksi Pembelian Stok berhasil disimpan.');
    }

    public function viewDetail(int $id): void
    {
        $this->selectedPurchase = Purchase::with(['items.ingredient', 'items.product', 'user'])->findOrFail($id);
        $this->showDetailModal = true;
    }

    public function render()
    {
        $purchases = Purchase::query()
            ->with(['items', 'user'])
            ->when($this->search, fn($q) => $q->where('invoice_number', 'like', "%{$this->search}%")->orWhere('supplier_name', 'like', "%{$this->search}%"))
            ->orderByDesc('purchase_date')
            ->orderByDesc('id')
            ->paginate($this->perPage);

        $ingredients = Ingredient::where('is_active', true)->orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();

        return view('livewire.admin.purchases', compact('purchases', 'ingredients', 'products'))
            ->title('Pembelian Stok');
    }
}
