<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Purchase extends Model
{
    protected $fillable = [
        'invoice_number',
        'purchase_date',
        'supplier_id',
        'total_amount',
        'note',
        'status',
        'user_id',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'total_amount' => 'float',
    ];

    /**
     * Route model binding pakai invoice_number, bukan id — supaya URL detail
     * pembelian informatif (mis. /admin/purchases/INV-PO-20260810-F838).
     */
    public function getRouteKeyName(): string
    {
        return 'invoice_number';
    }

    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    // -----------------------------------------------------------------
    // Queries
    // -----------------------------------------------------------------

    /**
     * Daftar pembelian untuk halaman index, dengan eager load lengkap
     * (items, user, supplier) supaya tabel tidak N+1 query.
     */
    public static function getPaginated(string $search = '', int $perPage = 10)
    {
        return static::query()
            ->with(['items', 'user', 'supplier'])
            ->when($search, fn ($q) => $q->where('invoice_number', 'like', "%{$search}%")
                ->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'like', "%{$search}%")))
            ->orderByDesc('purchase_date')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * Detail satu pembelian untuk halaman detail, eager load sampai ke
     * unit bahan baku (items.ingredient.unit) supaya baris item tidak N+1.
     */
    public static function findDetailOrFail(int|string $routeKey): self
    {
        return static::query()
            ->with(['items.ingredient.unit', 'items.product', 'user', 'supplier'])
            ->where('invoice_number', $routeKey)
            ->firstOrFail();
    }

    public static function generateInvoiceNumber(): string
    {
        $prefix = 'INV-PO';
        $timestamp = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -4));
        return "{$prefix}-{$timestamp}-{$random}";
    }

    // -----------------------------------------------------------------
    // Business Logic
    // -----------------------------------------------------------------

    /**
     * Simpan transaksi pembelian beserta semua item-nya dalam satu transaksi
     * DB — termasuk update stok & HPP (weighted average) tiap bahan
     * baku/produk dan pencatatan stock log. Melempar \Throwable jika gagal
     * (mis. race condition pada invoice_number unik) — pemanggil (Livewire
     * component) yang menangani pesan error untuk user.
     *
     * @param array $data  ['invoice_number','purchase_date','supplier_id','note']
     * @param array $items daftar item: item_type, ingredient_id/product_id, quantity, unit_price
     */
    public static function createWithItems(array $data, array $items, int $userId): self
    {
        return DB::transaction(function () use ($data, $items, $userId) {
            $totalAmount = collect($items)->sum(
                fn ($item) => (float) $item['quantity'] * (float) $item['unit_price']
            );

            $purchase = static::create([
                'invoice_number' => $data['invoice_number'],
                'purchase_date' => $data['purchase_date'],
                'supplier_id' => $data['supplier_id'] ?: null,
                'total_amount' => $totalAmount,
                'note' => $data['note'] ?? null,
                'status' => 'completed',
                'user_id' => $userId,
            ]);

            foreach ($items as $item) {
                $purchase->receiveItem($item, $userId);
            }

            return $purchase;
        });
    }

    /**
     * Catat satu baris item pembelian dan terapkan efeknya ke stok +
     * HPP (rata-rata tertimbang) bahan baku/produk terkait.
     */
    protected function receiveItem(array $item, int $userId): void
    {
        $qty = (float) $item['quantity'];
        $price = (float) $item['unit_price'];

        $this->items()->create([
            'item_type' => $item['item_type'],
            'ingredient_id' => $item['item_type'] === 'ingredient' ? $item['ingredient_id'] : null,
            'product_id' => $item['item_type'] === 'product' ? $item['product_id'] : null,
            'quantity' => $qty,
            'unit_price' => $price,
            'subtotal' => $qty * $price,
        ]);

        if ($item['item_type'] === 'ingredient') {
            $this->receiveIngredientStock((int) $item['ingredient_id'], $qty, $price, $userId);
        } else {
            $this->receiveProductStock((int) $item['product_id'], $qty, $price, $userId);
        }
    }

    protected function receiveIngredientStock(int $ingredientId, float $qty, float $price, int $userId): void
    {
        $ingredient = Ingredient::where('id', $ingredientId)->lockForUpdate()->first();
        if (!$ingredient) {
            return;
        }

        $oldStock = $ingredient->stock;
        $oldCost = $ingredient->cost_price;
        $newStock = $oldStock + $qty;

        // Weighted Average HPP Calculation
        $newCost = $newStock > 0
            ? (($oldStock * $oldCost) + ($qty * $price)) / $newStock
            : $price;

        $ingredient->update([
            'stock' => $newStock,
            'cost_price' => round($newCost, 2),
        ]);

        IngredientStockLog::record(
            ingredientId: $ingredient->id,
            userId: $userId,
            type: 'purchase',
            amount: $qty,
            finalStock: $newStock,
            note: "Pembelian Faktur: {$this->invoice_number}",
            referenceId: $this->id
        );
    }

    protected function receiveProductStock(int $productId, float $qty, float $price, int $userId): void
    {
        $product = Product::where('id', $productId)->lockForUpdate()->first();
        if (!$product) {
            return;
        }

        $oldStock = $product->stock;
        $oldCost = $product->cost_price;
        $newStock = $oldStock + (int) $qty;

        // Weighted Average HPP Calculation
        $newCost = $newStock > 0
            ? (($oldStock * $oldCost) + ($qty * $price)) / $newStock
            : $price;

        $product->update([
            'stock' => $newStock,
            'cost_price' => round($newCost, 2),
            'track_stock' => true,
        ]);

        StockLog::record(
            productId: $product->id,
            userId: $userId,
            type: 'add',
            amount: (int) $qty,
            finalStock: $newStock,
            note: "Pembelian Langsung Faktur: {$this->invoice_number}",
            referenceId: $this->id
        );
    }
}
