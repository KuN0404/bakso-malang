<?php

namespace App\Livewire\Admin;

use App\Models\PaymentSource;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class PaymentSources extends Component
{
    use WithPagination;

    public bool $showModal = false;
    public ?int $editingId = null;

    // Search
    #[Url(except: '')]
    public string $search = '';

    #[Rule('required|min:2|max:100')]
    public string $name = '';

    #[Rule('required|in:cash,card,transfer,ewallet,qris')]
    public string $type = 'cash';

    #[Rule('nullable|max:255')]
    public ?string $description = '';

    public bool $is_active = true;

    #[Rule('required|integer|min:0')]
    public int $sort_order = 0;

    public function mount()
    {
        // URL Validation: Page must be numeric and >= 1
        $page = request()->query('page');
        if ($page && (!is_numeric($page) || $page < 1)) {
            $this->setPage(1);
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'type', 'description', 'is_active', 'sort_order']);
        $this->is_active = true;
        // Auto-fill sort_order with next available (max + 1)
        $this->sort_order = (PaymentSource::max('sort_order') ?? 0) + 1;
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $source = PaymentSource::findOrFail($id);
        $this->editingId = $source->id;
        $this->name = $source->name;
        $this->type = $source->type;
        $this->description = $source->description ?? '';
        $this->is_active = $source->is_active;
        $this->sort_order = $source->sort_order;
        $this->showModal = true;
    }

    public function save(): void
    {
        // Custom validation: sort_order must be unique (except when editing same record)
        $this->validate([
            'name' => 'required|min:2|max:100',
            'type' => 'required|in:cash,card,transfer,ewallet,qris',
            'description' => 'nullable|max:255',
            'sort_order' => [
                'required',
                'integer',
                'min:0',
                \Illuminate\Validation\Rule::unique('payment_sources', 'sort_order')
                    ->ignore($this->editingId),
            ],
        ], [
            'sort_order.unique' => 'Urutan ini sudah digunakan. Pilih urutan lain.',
        ]);

        $data = [
            'name' => $this->name,
            'type' => $this->type,
            'description' => $this->description ?: null,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];

        if ($this->editingId) {
            PaymentSource::find($this->editingId)->update($data);
            $this->dispatch('notify', type: 'success', message: 'Metode pembayaran berhasil diperbarui');
        } else {
            PaymentSource::create($data);
            $this->dispatch('notify', type: 'success', message: 'Metode pembayaran berhasil ditambahkan');
        }

        $this->showModal = false;
    }

    public function delete(int $id): void
    {
        PaymentSource::findOrFail($id)->delete();
        $this->dispatch('notify', type: 'success', message: 'Metode pembayaran berhasil dihapus');
    }

    public function render()
    {
        $sources = PaymentSource::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('sort_order')
            ->paginate(10);

        // Smart Redirect: If page > lastPage, redirect to lastPage
        if ($sources->lastPage() < $this->getPage() && $sources->lastPage() > 0) {
            $this->setPage($sources->lastPage());
            $sources = PaymentSource::query()
                ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->orderBy('sort_order')
                ->paginate(10);
        }

        return view('livewire.admin.payment-sources', compact('sources'))
            ->title('Metode Pembayaran');
    }
}
