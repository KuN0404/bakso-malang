<?php

namespace App\Livewire\Admin;

use App\Models\Ingredient;
use App\Models\Product;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class HppCalculator extends Component
{
    public ?int $selectedProductId = null;
    public string $recipeName = '';
    public int $portionsPerBatch = 100;
    public float $overheadCost = 50000; // Biaya gas, listrik, tenaga kerja per batch
    public float $packagingCostPerPortion = 1500; // Biaya mangkok, plastik, sendok per porsi
    public float $targetMarginPercent = 50; // Target Profit Margin (%)
    public bool $applyToSellingPrice = false;

    public array $ingredientsList = [];

    public function mount(): void
    {
        // Sample initial row for convenience
        $firstIngredient = Ingredient::where('is_active', true)->first();
        if ($firstIngredient) {
            $this->ingredientsList[] = [
                'ingredient_id' => $firstIngredient->id,
                'name'          => $firstIngredient->name,
                'unit'          => $firstIngredient->unit,
                'cost_price'    => (float) $firstIngredient->cost_price,
                'quantity'      => 5,
            ];
        } else {
            $this->ingredientsList[] = [
                'ingredient_id' => null,
                'name'          => 'Bahan Baku 1',
                'unit'          => 'kg',
                'cost_price'    => 100000,
                'quantity'      => 1,
            ];
        }
    }

    public function updatedSelectedProductId($value): void
    {
        if ($value) {
            $product = Product::find($value);
            if ($product) {
                $this->recipeName = $product->name;
            }
        }
    }

    public function selectProduct(int $productId): void
    {
        $this->selectedProductId = $productId;
        $product = Product::find($productId);
        if ($product) {
            $this->recipeName = $product->name;
        }
    }

    public function addIngredientRow(): void
    {
        $firstIngredient = Ingredient::where('is_active', true)->first();
        if ($firstIngredient) {
            $this->ingredientsList[] = [
                'ingredient_id' => $firstIngredient->id,
                'name'          => $firstIngredient->name,
                'unit'          => $firstIngredient->unit,
                'cost_price'    => (float) $firstIngredient->cost_price,
                'quantity'      => 1,
            ];
        } else {
            $this->ingredientsList[] = [
                'ingredient_id' => null,
                'name'          => '',
                'unit'          => 'kg',
                'cost_price'    => 0,
                'quantity'      => 1,
            ];
        }
    }

    public function removeIngredientRow(int $index): void
    {
        if (isset($this->ingredientsList[$index])) {
            unset($this->ingredientsList[$index]);
            $this->ingredientsList = array_values($this->ingredientsList);
        }
    }

    public function onIngredientSelected(int $index, $ingredientId): void
    {
        if (!$ingredientId) return;

        $ingredient = Ingredient::find($ingredientId);
        if ($ingredient && isset($this->ingredientsList[$index])) {
            $this->ingredientsList[$index]['ingredient_id'] = $ingredient->id;
            $this->ingredientsList[$index]['name']          = $ingredient->name;
            $this->ingredientsList[$index]['unit']          = $ingredient->unit;
            $this->ingredientsList[$index]['cost_price']    = (float) $ingredient->cost_price;
        }
    }

    #[Computed]
    public function availableProducts()
    {
        return Product::orderBy('name')->get();
    }

    #[Computed]
    public function availableIngredients()
    {
        return Ingredient::where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function totalIngredientsCost(): float
    {
        $total = 0;
        foreach ($this->ingredientsList as $item) {
            $qty  = (float) ($item['quantity'] ?? 0);
            $cost = (float) ($item['cost_price'] ?? 0);
            $total += ($qty * $cost);
        }
        return $total;
    }

    #[Computed]
    public function totalPackagingCost(): float
    {
        return (float) $this->packagingCostPerPortion * max(1, $this->portionsPerBatch);
    }

    #[Computed]
    public function totalBatchCost(): float
    {
        return $this->totalIngredientsCost + (float) $this->overheadCost + $this->totalPackagingCost;
    }

    #[Computed]
    public function hppPerPortion(): float
    {
        $portions = max(1, $this->portionsPerBatch);
        return $this->totalBatchCost / $portions;
    }

    #[Computed]
    public function recommendedSellingPrice(): float
    {
        $marginDecimal = min(0.95, max(0.05, (float) $this->targetMarginPercent / 100));
        return $this->hppPerPortion / (1 - $marginDecimal);
    }

    #[Computed]
    public function profitPerPortion(): float
    {
        return $this->recommendedSellingPrice - $this->hppPerPortion;
    }

    #[Computed]
    public function totalBatchProfit(): float
    {
        return $this->profitPerPortion * max(1, $this->portionsPerBatch);
    }

    #[Computed]
    public function currentProduct(): ?Product
    {
        return $this->selectedProductId ? Product::find($this->selectedProductId) : null;
    }

    public bool $showConfirmModal = false;

    public function confirmApplyHpp(): void
    {
        if (!$this->selectedProductId) {
            $this->dispatch('notify', type: 'error', message: 'Silakan pilih produk terlebih dahulu');
            return;
        }
        $this->showConfirmModal = true;
    }

    public function cancelApplyHpp(): void
    {
        $this->showConfirmModal = false;
    }

    public function applyHppToProduct(): void
    {
        if (!$this->selectedProductId) {
            $this->dispatch('notify', type: 'error', message: 'Silakan pilih produk terlebih dahulu');
            return;
        }

        $product = Product::find($this->selectedProductId);
        if (!$product) {
            $this->dispatch('notify', type: 'error', message: 'Produk tidak ditemukan');
            return;
        }

        $calculatedHpp = round($this->hppPerPortion, 2);
        $product->cost_price = $calculatedHpp;

        if ($this->applyToSellingPrice) {
            $product->price = round($this->recommendedSellingPrice, -2); // Round to nearest hundred
        }

        $product->save();
        $this->showConfirmModal = false;

        $message = "HPP produk '{$product->name}' berhasil diperbarui menjadi Rp " . number_format($calculatedHpp, 0, ',', '.');
        if ($this->applyToSellingPrice) {
            $message .= " dan Harga Jual menjadi Rp " . number_format($product->price, 0, ',', '.');
        }

        $this->dispatch('notify', type: 'success', message: $message);
    }

    public function render()
    {
        return view('livewire.admin.hpp-calculator', [
            'title' => 'Kalkulator HPP & Margin Profit'
        ])->layout('layouts.admin', ['title' => 'Kalkulator HPP & Margin Profit']);
    }
}
