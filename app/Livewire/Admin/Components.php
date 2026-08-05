<?php

namespace App\Livewire\Admin;

use App\Exceptions\InsufficientStockException;
use App\Models\Component as ComponentModel;
use App\Models\ComponentStockLog;
use App\Services\ComponentStockService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class Components extends Component
{
    use WithPagination;

    public bool $showModal = false;
    public ?int $editingId = null;

    // Form fields
    public string $code     = '';
    public string $name     = '';
    public string $unit     = 'pcs';
    public float  $stock    = 0;
    public float  $minimumStock = 0;
    public float  $costPrice = 0;
    public string $note     = '';
    public bool   $isActive = true;

    #[Url(except: '')]
    public string $search = '';

    public int $perPage = 15;

    // -----------------------------------------------------------------
    // Form Helpers
    // -----------------------------------------------------------------

    protected function generateCode(): string
    {
        $last = ComponentModel::withTrashed()->orderByDesc('id')->value('code') ?? 'CMP-000';
        preg_match('/(\d+)$/', $last, $m);
        $next = str_pad((int) ($m[1] ?? 0) + 1, 3, '0', STR_PAD_LEFT);
        return "CMP-{$next}";
    }

    // -----------------------------------------------------------------
    // CRUD
    // -----------------------------------------------------------------

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'unit', 'stock', 'minimumStock', 'costPrice', 'note', 'isActive']);
        $this->isActive = true;
        $this->unit     = 'pcs';
        $this->code     = $this->generateCode();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $component          = ComponentModel::findOrFail($id);
        $this->editingId    = $component->id;
        $this->code         = $component->code;
        $this->name         = $component->name;
        $this->unit         = $component->unit;
        $this->stock        = $component->stock;
        $this->minimumStock = $component->minimum_stock;
        $this->costPrice    = $component->cost_price;
        $this->note         = $component->note ?? '';
        $this->isActive     = $component->is_active;
        $this->showModal    = true;
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'edit_components' : 'create_components');

        $this->validate([
            'code'         => 'required|max:50|unique:components,code' . ($this->editingId ? ",{$this->editingId}" : ''),
            'name'         => 'required|min:2|max:150',
            'unit'         => 'required|max:30',
            'minimumStock' => 'numeric|min:0',
            'costPrice'    => 'numeric|min:0',
        ], [
            'code.unique' => 'Kode komponen ini sudah digunakan.',
            'name.required' => 'Nama komponen wajib diisi.',
        ]);

        $data = [
            'code'          => $this->code,
            'name'          => $this->name,
            'unit'          => $this->unit,
            'minimum_stock' => $this->minimumStock,
            'cost_price'    => $this->costPrice,
            'note'          => $this->note ?: null,
            'is_active'     => $this->isActive,
        ];

        if ($this->editingId) {
            $comp = ComponentModel::find($this->editingId);
            $comp->update($data);
            app(\App\Services\ReportSyncService::class)->syncComponent($comp);
            $this->dispatch('notify', type: 'success', message: 'Komponen berhasil diperbarui.');
        } else {
            $comp = ComponentModel::create($data);
            app(\App\Services\ReportSyncService::class)->syncComponent($comp);
            $this->dispatch('notify', type: 'success', message: 'Komponen berhasil ditambahkan.');
        }

        $this->showModal = false;
    }

    public function delete(int $id): void
    {
        $this->authorize('delete_components');

        $component = ComponentModel::findOrFail($id);

        // Cek apakah komponen masih dipakai BOM
        if ($component->boms()->exists()) {
            $this->dispatch('notify', type: 'error', message: "Komponen '{$component->name}' tidak bisa dihapus karena masih dipakai dalam BOM produk.");
            return;
        }

        $component->delete(); // SoftDelete
        $this->dispatch('notify', type: 'success', message: 'Komponen berhasil dihapus.');
    }

    // -----------------------------------------------------------------
    // Filters & Render
    // -----------------------------------------------------------------

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $components = ComponentModel::when(
            $this->search,
            fn($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('code', 'like', "%{$this->search}%")
        )
        ->orderBy('name')
        ->paginate($this->perPage);

        // Stats
        $lowStockCount   = ComponentModel::where('stock', '>', 0)->whereColumn('stock', '<=', 'minimum_stock')->count();
        $outOfStockCount = ComponentModel::where('stock', '<=', 0)->count();

        return view('livewire.admin.components', compact('components', 'lowStockCount', 'outOfStockCount'))
            ->title('Komponen / Setengah Jadi');
    }
}
