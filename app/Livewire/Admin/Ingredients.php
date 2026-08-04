<?php

namespace App\Livewire\Admin;

use App\Models\Ingredient;
use App\Models\IngredientStockLog;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class Ingredients extends Component
{
    use WithPagination;

    public bool $showModal = false;
    public bool $showStockModal = false;
    public ?int $editingId = null;
    public ?Ingredient $selectedIngredient = null;

    // Form fields
    public string $code = '';
    public string $name = '';
    public string $unit = 'kg';
    public float $stock = 0;
    public float $minimum_stock = 0;
    public float $cost_price = 0;
    public ?string $note = null;
    public bool $is_active = true;

    // Stock management
    public string $stockAdjustmentType = 'add'; // add, sub, set
    public float $stockAdjustmentAmount = 0;
    public ?string $stockNote = '';

    #[Url(except: '')]
    public string $search = '';

    public int $perPage = 10;

    public function generateCode(): string
    {
        $prefix = 'ING';
        $timestamp = now()->format('ymd');
        $random = strtoupper(substr(uniqid(), -4));
        return "{$prefix}{$timestamp}{$random}";
    }

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'unit', 'stock', 'minimum_stock', 'cost_price', 'note', 'is_active']);
        $this->code = $this->generateCode();
        $this->unit = 'kg';
        $this->is_active = true;
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $ingredient = Ingredient::findOrFail($id);
        $this->editingId = $ingredient->id;
        $this->code = $ingredient->code;
        $this->name = $ingredient->name;
        $this->unit = $ingredient->unit;
        $this->stock = $ingredient->stock;
        $this->minimum_stock = $ingredient->minimum_stock;
        $this->cost_price = $ingredient->cost_price;
        $this->note = $ingredient->note;
        $this->is_active = $ingredient->is_active;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'edit_ingredients' : 'create_ingredients');

        $rules = [
            'code' => 'required|max:50|unique:ingredients,code' . ($this->editingId ? ",{$this->editingId}" : ''),
            'name' => 'required|min:2|max:150',
            'unit' => 'required|string|max:20',
            'minimum_stock' => 'required|numeric|min:0',
            'cost_price' => 'required|numeric|min:0',
            'note' => 'nullable|string|max:500',
        ];

        $messages = [
            'code.unique' => 'Kode bahan baku sudah digunakan.',
            'name.required' => 'Nama bahan baku wajib diisi.',
            'unit.required' => 'Satuan wajib dipilih/diisi.',
        ];

        $this->validate($rules, $messages);

        if ($this->editingId) {
            $ingredient = Ingredient::findOrFail($this->editingId);
            $ingredient->update([
                'code' => $this->code,
                'name' => $this->name,
                'unit' => $this->unit,
                'minimum_stock' => $this->minimum_stock,
                'cost_price' => $this->cost_price,
                'note' => $this->note,
                'is_active' => $this->is_active,
            ]);
            app(\App\Services\ReportSyncService::class)->syncIngredient($ingredient);
            $this->dispatch('notify', type: 'success', message: 'Bahan baku berhasil diperbarui.');
        } else {
            $ingredient = Ingredient::create([
                'code' => $this->code,
                'name' => $this->name,
                'unit' => $this->unit,
                'stock' => $this->stock,
                'minimum_stock' => $this->minimum_stock,
                'cost_price' => $this->cost_price,
                'note' => $this->note,
                'is_active' => $this->is_active,
            ]);

            if ($this->stock > 0) {
                IngredientStockLog::record(
                    ingredientId: $ingredient->id,
                    userId: Auth::id(),
                    type: 'initial',
                    amount: $this->stock,
                    finalStock: $this->stock,
                    note: 'Stok awal penambahan bahan baku'
                );
            }

            app(\App\Services\ReportSyncService::class)->syncIngredient($ingredient);
            $this->dispatch('notify', type: 'success', message: 'Bahan baku baru berhasil ditambahkan.');
        }

        $this->showModal = false;
    }

    public function openStockModal(int $id): void
    {
        $this->selectedIngredient = Ingredient::findOrFail($id);
        $this->stockAdjustmentType = 'add';
        $this->stockAdjustmentAmount = 0;
        $this->stockNote = '';
        $this->showStockModal = true;
    }

    public function saveStock(): void
    {
        // Tidak ada permission 'adjust_ingredient_stock' terpisah di seeder — pakai edit_ingredients
        // sebagai yang paling mendekati (setara dengan hak edit data bahan baku).
        $this->authorize('edit_ingredients');

        $rules = [
            'stockAdjustmentAmount' => 'required|numeric|gt:0',
        ];

        if ($this->stockAdjustmentType !== 'add') {
            $rules['stockNote'] = 'required|string|max:255';
        }

        $this->validate($rules, [
            'stockAdjustmentAmount.gt' => 'Jumlah penyesuaian harus lebih besar dari 0.',
            'stockNote.required' => 'Catatan wajib diisi untuk pengurangan/penyesuaian stok.',
        ]);

        $ingredientId = $this->selectedIngredient->id;
        $type = $this->stockAdjustmentType;
        $amount = $this->stockAdjustmentAmount;
        $note = $this->stockNote;

        $success = \Illuminate\Support\Facades\DB::transaction(function () use ($ingredientId, $type, $amount, &$note) {
            $ingredient = Ingredient::where('id', $ingredientId)->lockForUpdate()->first();
            if (!$ingredient) return false;

            $oldStock = $ingredient->stock;
            $newStock = $oldStock;

            if ($type === 'add') {
                $newStock += $amount;
                $logType = 'purchase';
            } elseif ($type === 'sub') {
                $newStock -= $amount;
                $logType = 'sub';
                if ($newStock < 0) {
                    return "Stok tidak mencukupi. Stok saat ini: {$oldStock} {$ingredient->unit}";
                }
            } else {
                $newStock = $amount;
                $logType = 'adjustment';
            }

            $ingredient->update(['stock' => $newStock]);

            IngredientStockLog::record(
                ingredientId: $ingredient->id,
                userId: Auth::id(),
                type: $logType,
                amount: $amount,
                finalStock: $newStock,
                note: $note ?: 'Penyesuaian manual'
            );

            return true;
        });

        if ($success !== true) {
            if (is_string($success)) {
                $this->dispatch('notify', type: 'error', message: $success);
            }
            return;
        }

        $this->showStockModal = false;
        $this->dispatch('notify', type: 'success', message: "Stok bahan baku berhasil diperbarui.");
    }

    public function delete(int $id): void
    {
        $this->authorize('delete_ingredients');

        $ingredient = Ingredient::findOrFail($id);
        $ingredient->delete();
        $this->dispatch('notify', type: 'success', message: 'Bahan baku berhasil dihapus.');
    }

    public function render()
    {
        $ingredients = Ingredient::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('code', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate($this->perPage);

        return view('livewire.admin.ingredients', compact('ingredients'))
            ->title('Bahan Baku');
    }
}
