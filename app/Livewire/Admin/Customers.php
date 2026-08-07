<?php

namespace App\Livewire\Admin;

use App\Models\BlockedPhoneNumber;
use App\Services\CustomerDirectoryService;
use App\Services\PhoneBlacklistService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class Customers extends Component
{
    use WithPagination;

    #[Url(except: 'customers')]
    public string $tab = 'customers';

    #[Url(except: '')]
    public string $search = '';

    public bool $showBlockModal = false;
    public string $blockPhone = '';
    public string $blockReason = '';

    public bool $showUnblockModal = false;
    public ?int $unblockingId = null;
    public string $unblockingPhone = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('view_customers'), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function switchTab(string $tab): void
    {
        if ($tab === 'blacklist') {
            abort_unless(auth()->user()->can('view_phone_blacklist'), 403);
        }

        $this->tab = $tab;
        $this->search = '';
        $this->resetPage();
    }

    public function openBlockModal(string $phone = ''): void
    {
        abort_unless(auth()->user()->can('manage_phone_blacklist'), 403);

        $this->blockPhone = $phone;
        $this->blockReason = '';
        $this->resetValidation();
        $this->showBlockModal = true;
    }

    public function closeBlockModal(): void
    {
        $this->showBlockModal = false;
        $this->reset(['blockPhone', 'blockReason']);
        $this->resetValidation();
    }

    public function confirmBlock(PhoneBlacklistService $blacklistService): void
    {
        abort_unless(auth()->user()->can('manage_phone_blacklist'), 403);

        $this->validate([
            'blockPhone'  => ['required', 'string', 'min:9', 'max:20'],
            'blockReason' => ['nullable', 'string', 'max:255'],
        ]);

        $blacklistService->block($this->blockPhone, $this->blockReason ?: null, auth()->id());

        $this->dispatch('notify', type: 'success', message: "Nomor {$this->blockPhone} berhasil diblokir.");
        $this->closeBlockModal();
    }

    public function confirmUnblock(int $id): void
    {
        abort_unless(auth()->user()->can('manage_phone_blacklist'), 403);

        $entry = BlockedPhoneNumber::findOrFail($id);
        $this->unblockingId = $entry->id;
        $this->unblockingPhone = $entry->phone;
        $this->showUnblockModal = true;
    }

    public function cancelUnblock(): void
    {
        $this->showUnblockModal = false;
        $this->reset(['unblockingId', 'unblockingPhone']);
    }

    public function unblock(PhoneBlacklistService $blacklistService): void
    {
        abort_unless(auth()->user()->can('manage_phone_blacklist'), 403);

        if (!$this->unblockingId) {
            return;
        }

        $entry = BlockedPhoneNumber::findOrFail($this->unblockingId);
        $blacklistService->unblock($entry->phone, auth()->id());

        $this->dispatch('notify', type: 'success', message: "Blokir nomor {$entry->phone} sudah dibuka.");
        $this->cancelUnblock();
    }

    public function render(CustomerDirectoryService $directory)
    {
        $customers  = null;
        $blacklist  = null;
        $blockedMap = collect();

        if ($this->tab === 'blacklist') {
            $blacklist = BlockedPhoneNumber::getPaginated($this->search, 15);
        } else {
            $customers  = $directory->paginate($this->search, 15);
            $blockedMap = $directory->getBlockedMap(
                Collection::make($customers->items())->pluck('phone')
            );
        }

        return view('livewire.admin.customers', compact('customers', 'blacklist', 'blockedMap'))
            ->title('Pelanggan');
    }
}
