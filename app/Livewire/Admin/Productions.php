<?php

namespace App\Livewire\Admin;

use App\Models\Ingredient;
use App\Models\IngredientStockLog;
use App\Models\Product;
use App\Models\Production;
use App\Models\ProductionInput;
use App\Models\ProductionOutput;
use App\Models\StockLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class Productions extends Component
{
    use WithPagination;

    public bool $showModal = false;
    public bool $showDetailModal = false;
    public ?Production $selectedProduction = null;

    // Form fields
    public string $production_code = '';
    public string $production_date = '';
    public ?string $note = '';

    // Dynamic Input (Ingredients used)
    public array $inputItems = [];

    // Dynamic Output (Products yielded)
    public array $outputItems = [];

    #[Url(except: '')]
    public string $search = '';

    public int $perPage = 10;

    public function generateCode(): string
    {
        $prefix = 'PROD';
        $timestamp = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -4));
        return "{$prefix}-{$timestamp}-{$random}";
    }

    public function create(): void
    {
        $this->reset(['note']);
        $this->production_code = $this->generateCode();
        $this->production_date = now()->format('Y-m-d');
        
        $this->inputItems = [
            ['ingredient_id' => '', 'quantity' => 1, 'unit_cost' => 0, 'subtotal' => 0]
        ];

        $this->outputItems = [
            ['product_id' => '', 'quantity' => 1, 'unit_cost' => 0, 'subtotal' => 0]
        ];

        $this->showModal = true;
    }

    public function addInputItem(): void
    {
        $this->inputItems[] = ['ingredient_id' => '', 'quantity' => 1, 'unit_cost' => 0, 'subtotal' => 0];
    }

    public function removeInputItem(int $index): void
    {
        if (count($this->inputItems) > 1) {
            unset($this->inputItems[$index]);
            $this->inputItems = array_values($this->inputItems);
        }
    }

    public function addOutputItem(): void
    {
        $this->outputItems[] = ['product_id' => '', 'quantity' => 1, 'unit_cost' => 0, 'subtotal' => 0];
    }

    public function removeOutputItem(int $index): void
    {
        if (count($this->outputItems) > 1) {
            unset($this->outputItems[$index]);
            $this->outputItems = array_values($this->outputItems);
        }
    }

    public function updatedInputItems($value, $key): void
    {
        $parts = explode('.', $key);
        if (count($parts) === 2) {
            $index = $parts[0];
            $field = $parts[1];

            if ($field === 'ingredient_id' && !empty($value)) {
                $ing = Ingredient::find($value);
                if ($ing) {
                    $this->inputItems[$index]['unit_cost'] = $ing->cost_price;
                }
            }

            $qty = (float) ($this->inputItems[$index]['quantity'] ?? 0);
            $cost = (float) ($this->inputItems[$index]['unit_cost'] ?? 0);
            $this->inputItems[$index]['subtotal'] = $qty * $cost;

            $this->recalculateOutputs();
        }
    }

    public function updatedOutputItems($value, $key): void
    {
        $this->recalculateOutputs();
    }

    public function getTotalInputCostProperty(): float
    {
        return array_reduce($this->inputItems, function ($carry, $item) {
            $qty = (float) ($item['quantity'] ?? 0);
            $cost = (float) ($item['unit_cost'] ?? 0);
            return $carry + ($qty * $cost);
        }, 0.0);
    }

    public function recalculateOutputs(): void
    {
        $totalInputCost = $this->totalInputCost;
        $totalOutputQty = array_reduce($this->outputItems, function ($carry, $item) {
            return $carry + (float) ($item['quantity'] ?? 0);
        }, 0.0);

        if ($totalOutputQty > 0 && $totalInputCost > 0) {
            $costPerUnit = $totalInputCost / $totalOutputQty;
            foreach ($this->outputItems as $i => $item) {
                $qty = (float) ($item['quantity'] ?? 0);
                $this->outputItems[$i]['unit_cost'] = round($costPerUnit, 2);
                $this->outputItems[$i]['subtotal'] = round($qty * $costPerUnit, 2);
            }
        }
    }

    public function save(): void
    {
        $this->validate([
            'production_code' => 'required|string|max:50|unique:productions,production_code',
            'production_date' => 'required|date',
            'note' => 'nullable|string|max:500',
            'inputItems' => 'required|array|min:1',
            'inputItems.*.ingredient_id' => 'required|exists:ingredients,id',
            'inputItems.*.quantity' => 'required|numeric|gt:0',
            'outputItems' => 'required|array|min:1',
            'outputItems.*.product_id' => 'required|exists:products,id',
            'outputItems.*.quantity' => 'required|numeric|gt:0',
        ], [
            'production_code.unique' => 'Kode produksi ini sudah terdaftar.',
            'inputItems.*.ingredient_id.required' => 'Pilih bahan baku yang digunakan.',
            'inputItems.*.quantity.gt' => 'Jumlah bahan terpakai harus > 0.',
            'outputItems.*.product_id.required' => 'Pilih produk jadi yang dihasilkan.',
            'outputItems.*.quantity.gt' => 'Jumlah hasil produk harus > 0.',
        ]);

        // Check stock availability for input ingredients
        foreach ($this->inputItems as $item) {
            $ing = Ingredient::find($item['ingredient_id']);
            $qty = (float) $item['quantity'];
            if ($ing->stock < $qty) {
                $this->dispatch('notify', type: 'error', message: "Stok bahan baku '{$ing->name}' tidak cukup (Stok: {$ing->stock} {$ing->unit}, Dibutuhkan: {$qty} {$ing->unit}).");
                return;
            }
        }

        DB::transaction(function () {
            $this->recalculateOutputs();
            $totalCost = $this->totalInputCost;

            $production = Production::create([
                'production_code' => $this->production_code,
                'production_date' => $this->production_date,
                'total_cost' => $totalCost,
                'note' => $this->note,
                'status' => 'completed',
                'user_id' => Auth::id(),
            ]);

            // Save Inputs & deduct Ingredient stock
            foreach ($this->inputItems as $item) {
                $qty = (float) $item['quantity'];
                $unitCost = (float) $item['unit_cost'];
                $subtotal = $qty * $unitCost;

                ProductionInput::create([
                    'production_id' => $production->id,
                    'ingredient_id' => $item['ingredient_id'],
                    'quantity' => $qty,
                    'unit_cost' => $unitCost,
                    'subtotal' => $subtotal,
                ]);

                $ing = Ingredient::where('id', $item['ingredient_id'])->lockForUpdate()->first();
                if ($ing) {
                    $newIngStock = $ing->stock - $qty;
                    if ($newIngStock < 0) {
                        throw new \Exception("Stok bahan baku '{$ing->name}' tidak cukup.");
                    }
                    $ing->update(['stock' => $newIngStock]);

                    IngredientStockLog::record(
                        ingredientId: $ing->id,
                        userId: Auth::id(),
                        type: 'production_use',
                        amount: $qty,
                        finalStock: $newIngStock,
                        note: "Dipakai Produksi Batch: {$production->production_code}",
                        referenceId: $production->id
                    );
                }
            }

            // Save Outputs & increase Product stock + update HPP
            foreach ($this->outputItems as $item) {
                $qty = (float) $item['quantity'];
                $unitCost = (float) $item['unit_cost'];
                $subtotal = $qty * $unitCost;

                ProductionOutput::create([
                    'production_id' => $production->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $qty,
                    'unit_cost' => $unitCost,
                    'subtotal' => $subtotal,
                ]);

                $product = Product::where('id', $item['product_id'])->lockForUpdate()->first();
                if ($product) {
                    $oldStock = $product->stock;
                    $newStock = $oldStock + (int) $qty;

                    // Weighted Average HPP update
                    $oldCost = $product->cost_price;
                    $newCost = ($newStock > 0)
                        ? (($oldStock * $oldCost) + ($qty * $unitCost)) / $newStock
                        : $unitCost;

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
                        note: "Hasil Produksi Dapur: {$production->production_code}",
                        referenceId: $production->id
                    );
                }
            }
        });

        $this->showModal = false;
        $this->dispatch('notify', type: 'success', message: 'Batch Produksi Dapur berhasil diproses. Stok produk jadi telah ditambahkan.');
    }

    public function viewDetail(int $id): void
    {
        $this->selectedProduction = Production::with(['inputs.ingredient', 'outputs.product', 'user'])->findOrFail($id);
        $this->showDetailModal = true;
    }

    public function render()
    {
        $productions = Production::query()
            ->with(['inputs.ingredient', 'outputs.product', 'user'])
            ->when($this->search, fn($q) => $q->where('production_code', 'like', "%{$this->search}%"))
            ->orderByDesc('production_date')
            ->orderByDesc('id')
            ->paginate($this->perPage);

        $ingredients = Ingredient::where('is_active', true)->where('stock', '>', 0)->orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();

        return view('livewire.admin.productions', compact('productions', 'ingredients', 'products'))
            ->title('Repacking / Produksi Dapur');
    }
}
