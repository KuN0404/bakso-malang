<?php

namespace App\Livewire\Admin;

use App\Models\Ingredient;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class PurchaseCreate extends Component
{
    // Form fields
    public string $invoice_number = '';
    public string $purchase_date = '';
    public ?int $supplier_id = null;
    public ?string $note = '';

    // Dynamic Purchase Items
    public array $items = [];

    public function mount(): void
    {
        $this->authorize('create_purchases');

        $this->invoice_number = Purchase::generateInvoiceNumber();
        $this->purchase_date = now()->format('Y-m-d');
        $this->items = [$this->emptyItem()];
    }

    protected function emptyItem(): array
    {
        return [
            'item_type' => 'ingredient',
            'ingredient_id' => '',
            'product_id' => '',
            'quantity' => 1,
            'unit_price' => 0,
            'subtotal' => 0,
        ];
    }

    public function addItem(): void
    {
        $this->items[] = $this->emptyItem();
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

    public function save()
    {
        $this->authorize('create_purchases');

        $this->validate([
            'invoice_number' => 'required|string|max:50|unique:purchases,invoice_number',
            'purchase_date' => 'required|date',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'note' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.item_type' => 'required|in:ingredient,product',
            'items.*.ingredient_id' => 'required_if:items.*.item_type,ingredient|nullable|exists:ingredients,id',
            'items.*.product_id' => 'required_if:items.*.item_type,product|nullable|exists:products,id',
            'items.*.quantity' => 'required|numeric|gt:0',
            'items.*.unit_price' => 'required|numeric|min:0',
        ], [
            'invoice_number.unique' => 'Nomor faktur ini sudah terdaftar.',
            'items.min' => 'Pembelian minimal harus berisi 1 barang.',
            'items.*.quantity.gt' => 'Jumlah harus lebih besar dari 0.',
            'items.*.ingredient_id.required_if' => 'Silakan pilih Bahan Baku.',
            'items.*.ingredient_id.exists' => 'Bahan baku pada salah satu item sudah tidak ada / dihapus.',
            'items.*.product_id.required_if' => 'Silakan pilih Produk.',
            'items.*.product_id.exists' => 'Produk pada salah satu item sudah tidak ada / dihapus.',
        ]);

        try {
            $purchase = Purchase::createWithItems(
                data: [
                    'invoice_number' => $this->invoice_number,
                    'purchase_date' => $this->purchase_date,
                    'supplier_id' => $this->supplier_id,
                    'note' => $this->note,
                ],
                items: $this->items,
                userId: Auth::id()
            );
        } catch (\Throwable $e) {
            Log::error('[PurchaseCreate::save] Gagal menyimpan pembelian: ' . $e->getMessage(), ['exception' => $e]);
            $this->dispatch('notify', type: 'error', message: 'Gagal menyimpan pembelian. Silakan coba lagi (nomor faktur mungkin sudah dipakai bersamaan oleh transaksi lain).');
            return;
        }

        app(\App\Services\ReportSyncService::class)->syncPurchase($purchase->load(['items', 'supplier']));

        session()->flash('notify', ['type' => 'success', 'message' => 'Transaksi Pembelian Stok berhasil disimpan.']);

        return $this->redirect(route('admin.purchases.show', $purchase), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.purchase-create', [
            'ingredients' => Ingredient::getAllActiveWithUnit(),
            'products' => Product::getAllActiveSortedByName(),
            'suppliers' => Supplier::getAllActiveSortedByName(),
        ])->title('Tambah Pembelian Stok');
    }
}
