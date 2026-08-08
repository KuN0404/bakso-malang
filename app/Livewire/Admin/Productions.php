<?php

namespace App\Livewire\Admin;

use App\Models\Component as ComponentModel;
use App\Models\Ingredient;
use App\Models\IngredientStockLog;
use App\Models\Production;
use App\Models\ProductionInput;
use App\Models\ProductionOutput;
use App\Services\ComponentStockService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

    // Dynamic Input (Bahan Baku digunakan)
    public array $inputItems = [];

    // Dynamic Output (Komponen / Item Setengah Jadi yang dihasilkan)
    public array $outputItems = [];

    #[Url(except: '')]
    public string $search = '';

    public int $perPage = 10;

    public function generateCode(): string
    {
        $prefix    = 'PROD';
        $timestamp = now()->format('Ymd');
        $random    = strtoupper(substr(uniqid(), -4));
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
            ['component_id' => '', 'quantity' => 1, 'unit_cost' => 0, 'subtotal' => 0]
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
        $this->outputItems[] = ['component_id' => '', 'quantity' => 1, 'unit_cost' => 0, 'subtotal' => 0];
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

            $qty  = (float) ($this->inputItems[$index]['quantity'] ?? 0);
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
            $qty  = (float) ($item['quantity'] ?? 0);
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
                $this->outputItems[$i]['unit_cost'] = round($costPerUnit, 4);
                $this->outputItems[$i]['subtotal']  = round($qty * $costPerUnit, 2);
            }
        }
    }

    public function save(): void
    {
        $this->authorize('create_productions');

        $this->validate([
            'production_code'                 => 'required|string|max:50|unique:productions,production_code',
            'production_date'                 => 'required|date',
            'note'                            => 'nullable|string|max:500',
            'inputItems'                      => 'required|array|min:1',
            'inputItems.*.ingredient_id'      => 'required|exists:ingredients,id',
            'inputItems.*.quantity'           => 'required|numeric|gt:0',
            'outputItems'                     => 'required|array|min:1',
            'outputItems.*.component_id'      => 'required|exists:components,id',
            'outputItems.*.quantity'          => 'required|numeric|gt:0',
        ], [
            'production_code.unique'               => 'Kode produksi ini sudah terdaftar.',
            'inputItems.*.ingredient_id.required'  => 'Pilih bahan baku yang digunakan.',
            'inputItems.*.quantity.gt'             => 'Jumlah bahan terpakai harus > 0.',
            'outputItems.*.component_id.required'  => 'Pilih komponen yang dihasilkan.',
            'outputItems.*.quantity.gt'            => 'Jumlah hasil komponen harus > 0.',
        ]);

        // Soft check stok bahan baku sebelum masuk DB transaction
        foreach ($this->inputItems as $item) {
            $ing = Ingredient::find($item['ingredient_id']);
            $qty = (float) $item['quantity'];
            if ($ing->stock < $qty) {
                $this->dispatch('notify', type: 'error', message: "Stok bahan baku '{$ing->name}' tidak cukup (Stok: {$ing->stock} {$ing->unit}, Dibutuhkan: {$qty} {$ing->unit}).");
                return;
            }
        }

        // Jaring fat-finger: HPP hasil repacking (dihitung dari total input cost / total qty
        // output) yang berbeda drastis dari HPP komponen saat ini biasanya berarti jumlah
        // output salah ketik (mis. kurang/lebih nol), bukan aturan bisnis ketat.
        $this->recalculateOutputs();
        foreach ($this->outputItems as $item) {
            if (empty($item['component_id'])) {
                continue;
            }
            $component        = ComponentModel::find($item['component_id']);
            $computedUnitCost = (float) ($item['unit_cost'] ?? 0);
            if ($component && $component->cost_price > 0 && $computedUnitCost > 0) {
                $ratio = max($computedUnitCost, $component->cost_price) / min($computedUnitCost, $component->cost_price);
                if ($ratio > 20) {
                    $this->dispatch('notify', type: 'error', message: "HPP hasil repacking komponen '{$component->name}' (Rp " . number_format($computedUnitCost, 0, ',', '.') . ") berbeda drastis dari HPP saat ini (Rp " . number_format($component->cost_price, 0, ',', '.') . ") — cek kembali jumlah output, kemungkinan salah input.");
                    return;
                }
            }
        }

        $production = null;
        try {
            DB::transaction(function () use (&$production) {
                $this->recalculateOutputs();
                $totalCost = $this->totalInputCost;

                $production = Production::create([
                    'production_code' => $this->production_code,
                    'production_date' => $this->production_date,
                    'total_cost'      => $totalCost,
                    'note'            => $this->note,
                    'status'          => 'completed',
                    'user_id'         => Auth::id(),
                ]);

                // Simpan Inputs & Kurangi Stok Bahan Baku
                foreach ($this->inputItems as $item) {
                    $qty      = (float) $item['quantity'];
                    $unitCost = (float) $item['unit_cost'];
                    $subtotal = $qty * $unitCost;

                    ProductionInput::create([
                        'production_id' => $production->id,
                        'ingredient_id' => $item['ingredient_id'],
                        'quantity'      => $qty,
                        'unit_cost'     => $unitCost,
                        'subtotal'      => $subtotal,
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
                            userId:       Auth::id(),
                            type:         'production_use',
                            amount:       -$qty,
                            finalStock:   $newIngStock,
                            note:         "Dipakai Repacking Batch: {$production->production_code}",
                            referenceId:  $production->id
                        );
                    }
                }

                // Simpan Outputs & Tambah Stok Komponen via ComponentStockService
                $stockService = app(ComponentStockService::class);
                foreach ($this->outputItems as $item) {
                    $qty      = (float) $item['quantity'];
                    $unitCost = (float) $item['unit_cost'];
                    $subtotal = $qty * $unitCost;

                    ProductionOutput::create([
                        'production_id' => $production->id,
                        'component_id'  => $item['component_id'],
                        'product_id'    => null, // Output ke komponen, bukan produk
                        'quantity'      => $qty,
                        'unit_cost'     => $unitCost,
                        'subtotal'      => $subtotal,
                    ]);

                    // Tambah stok komponen + update HPP (Weighted Average)
                    $stockService->addFromProduction(
                        componentId:  (int) $item['component_id'],
                        quantity:     $qty,
                        unitCost:     $unitCost,
                        productionId: $production->id,
                        userId:       Auth::id()
                    );
                }
            });

            if ($production) {
                app(\App\Services\ReportSyncService::class)->syncProduction($production->load(['inputs', 'outputs']));
            }

            $this->showModal = false;
            $this->dispatch('notify', type: 'success', message: 'Batch Repacking berhasil diproses. Stok komponen telah ditambahkan.');

        } catch (\Exception $e) {
            Log::error('Production save failed', [
                'production_code' => $this->production_code,
                'error'            => $e->getMessage(),
                'user_id'          => Auth::id(),
            ]);
            $this->dispatch('notify', type: 'error', message: 'Gagal memproses: ' . $e->getMessage());
        }
    }

    public function viewDetail(int $id): void
    {
        $this->selectedProduction = Production::with([
            'inputs.ingredient',
            'outputs.component', // komponen (baru)
            'outputs.product',   // produk (backward compat data lama)
            'user',
        ])->findOrFail($id);
        $this->showDetailModal = true;
    }

    public function render()
    {
        $productions = Production::query()
            ->with([
                'inputs.ingredient',
                'outputs.component',
                'outputs.product',
                'user',
            ])
            ->when($this->search, fn($q) => $q->where('production_code', 'like', "%{$this->search}%"))
            ->orderByDesc('production_date')
            ->orderByDesc('id')
            ->paginate($this->perPage);

        $ingredients = Ingredient::where('is_active', true)
            ->where('stock', '>', 0)
            ->orderBy('name')
            ->get();

        // Komponen aktif untuk dropdown output
        $components = ComponentModel::getForRepacking();

        return view('livewire.admin.productions', compact('productions', 'ingredients', 'components'))
            ->title('Repacking / Produksi Dapur');
    }
}
