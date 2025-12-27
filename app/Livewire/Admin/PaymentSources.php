<?php

namespace App\Livewire\Admin;

use App\Models\PaymentSource;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Component;

#[Layout('layouts.admin')]
class PaymentSources extends Component
{
    public bool $showModal = false;
    public ?int $editingId = null;

    #[Rule('required|min:2|max:100')]
    public string $name = '';

    #[Rule('required|in:cash,card,transfer,ewallet,qris')]
    public string $type = 'cash';

    public ?string $description = '';
    public bool $is_active = true;
    public int $sort_order = 0;

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'type', 'description', 'is_active', 'sort_order']);
        $this->is_active = true;
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
        $this->validate();

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
        $sources = PaymentSource::orderBy('sort_order')->get();

        return view('livewire.admin.payment-sources', compact('sources'))
            ->title('Metode Pembayaran');
    }
}
