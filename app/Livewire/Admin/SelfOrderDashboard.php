<?php

namespace App\Livewire\Admin;

use App\Actions\SelfOrder\AcceptSelfOrderPaymentAction;
use App\Actions\SelfOrder\CancelSelfOrderAction;
use App\Actions\SelfOrder\ClaimSelfOrderAction;
use App\Actions\SelfOrder\UpdateSelfOrderStatusAction;
use App\Enums\SelfOrderStatus;
use App\Exceptions\NoOpenShiftException;
use App\Models\Pager;
use App\Models\SelfOrder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Dashboard Pesan Mandiri')]
class SelfOrderDashboard extends Component
{
    use WithPagination;

    // -----------------------------------------------------------------
    // Tab State
    // -----------------------------------------------------------------
    // 'paid' (Sudah Dibayar, belum diambil) | 'waiting' (Bayar di Tempat, belum diambil) |
    // 'claimed' (Pesanan Diambil — QRIS & Cash sekaligus, sedang dikerjakan kasir)
    public string $activeTab = 'paid';

    // Tab "Diambil": true = hanya punya kasir ini, false = semua kasir (transparansi tim)
    public bool $onlyMyClaimedOrders = false;

    // Sub-filter status di tab "Diambil": '' = semua, atau nilai status enum ('paid','processing','ready')
    public string $claimedStatusFilter = '';

    // -----------------------------------------------------------------
    // Detail Modal
    // -----------------------------------------------------------------
    // -----------------------------------------------------------------
    // Detail & Assign Area Modal
    // -----------------------------------------------------------------
    public bool $showDetailModal     = false;
    public bool $showCancelModal     = false;
    public bool $showPaymentModal    = false;
    public bool $showAssignAreaModal = false;
    public bool $showPickupModal     = false;

    public ?int $selectedOrderId       = null;
    public ?int $assigningOrderId      = null;
    public ?int $selectedServiceAreaId = null;
    public ?int $selectedPagerId       = null;
    public ?string $pendingActionType  = null;
    public ?SelfOrder $selectedOrder   = null;
    public string $cancelReason        = '';
    public mixed  $paidAmount          = 0;

    // Pickup Confirmation State (Take Away)
    public ?int $pickupOrderId       = null;
    public ?SelfOrder $pickupOrder   = null;
    public string $pickupOption      = 'same'; // 'same' | 'other'
    public string $pickupName        = '';
    public string $pickupPhone       = '';
    // 'claim' = konfirmasi saat klaim, atau nilai SelfOrderStatus ('completed', dll)
    public ?string $pickupNextAction = 'completed';

    // -----------------------------------------------------------------
    // Flash
    // -----------------------------------------------------------------
    public string $flashMessage = '';
    public string $flashType    = 'success';

    // -----------------------------------------------------------------
    // Polling (realtime count update)
    // -----------------------------------------------------------------
    protected $listeners = ['echo:self-orders,SelfOrderPlaced' => 'handleNewOrder'];

    public function getListeners()
    {
        return [
            'refreshDashboard' => '$refresh',
        ];
    }

    // -----------------------------------------------------------------
    // Computed
    // -----------------------------------------------------------------

    #[Computed]
    public function waitingOrders()
    {
        return SelfOrder::getPaginatedWaiting(20, auth()->id());
    }

    #[Computed]
    public function paidOrders()
    {
        return SelfOrder::getPaginatedPaid(20, auth()->id());
    }

    #[Computed]
    public function claimedOrders()
    {
        $userId = $this->onlyMyClaimedOrders ? auth()->id() : null;
        return SelfOrder::getPaginatedClaimed(20, $userId, $this->claimedStatusFilter ?: null);
    }

    #[Computed]
    public function activeServiceAreas()
    {
        return \App\Models\ServiceArea::active()->orderBy('sort_order')->get();
    }

    #[Computed]
    public function activePagers()
    {
        return Pager::active()->orderBy('number')->get();
    }

    #[Computed]
    public function waitingCount(): int
    {
        return SelfOrder::countWaitingToday();
    }

    #[Computed]
    public function paidCount(): int
    {
        return SelfOrder::countPaidToday();
    }

    #[Computed]
    public function claimedCount(): int
    {
        return SelfOrder::countClaimedToday($this->onlyMyClaimedOrders ? auth()->id() : null);
    }

    /**
     * Badge count per status di sub-filter tab Diambil.
     * Satu query GROUP BY — bukan per-status terpisah.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function claimedStatusCounts(): array
    {
        $userId = $this->onlyMyClaimedOrders ? auth()->id() : null;
        return SelfOrder::countClaimedByStatusGroup($userId);
    }

    /**
     * Data order untuk modal Konfirmasi Pembayaran — computed property agar tidak ada query di blade.
     */
    #[Computed]
    public function payOrderData(): ?SelfOrder
    {
        if (!$this->selectedOrderId || !$this->showPaymentModal) {
            return null;
        }
        return SelfOrder::find($this->selectedOrderId);
    }

    public function toggleOnlyMine(): void
    {
        $this->onlyMyClaimedOrders = !$this->onlyMyClaimedOrders;
        $this->claimedStatusFilter = ''; // reset sub-filter saat toggle kasir berubah
        unset($this->claimedOrders, $this->claimedCount, $this->claimedStatusCounts);
    }

    public function filterClaimedByStatus(string $status): void
    {
        $this->claimedStatusFilter = ($this->claimedStatusFilter === $status) ? '' : $status;
        $this->resetPage();
        unset($this->claimedOrders);
    }

    // -----------------------------------------------------------------
    // Tab Navigation
    // -----------------------------------------------------------------

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
        // Unset computed cache agar tidak menampilkan data lama saat kembali ke tab.
        unset($this->claimedOrders, $this->paidOrders, $this->waitingOrders);
    }

    // -----------------------------------------------------------------
    // Detail Modal & Assign Area
    // -----------------------------------------------------------------

    public function viewDetail(int $selfOrderId): void
    {
        $this->selectedOrderId = $selfOrderId;
        $this->selectedOrder   = SelfOrder::getForPrint($selfOrderId);
        $this->showDetailModal = true;
    }

    public function closeDetail(): void
    {
        $this->showDetailModal = false;
        $this->selectedOrder   = null;
    }

    public function openAssignAreaModal(int $selfOrderId, string $actionType): void
    {
        $order = SelfOrder::find($selfOrderId);
        if (!$order) return;

        $this->assigningOrderId      = $selfOrderId;
        $this->pendingActionType     = $actionType;
        $this->selectedServiceAreaId = $order->service_area_id;
        $this->selectedPagerId       = $order->pager_id;
        $this->showAssignAreaModal  = true;
    }

    public function closeAssignAreaModal(): void
    {
        $this->showAssignAreaModal   = false;
        $this->assigningOrderId      = null;
        $this->selectedServiceAreaId = null;
        $this->selectedPagerId       = null;
        $this->pendingActionType     = null;
    }

    public function saveAreaAndContinue(ClaimSelfOrderAction $claimAction): void
    {
        if (!$this->selectedServiceAreaId && !$this->selectedPagerId) {
            $this->flash('error', 'Harap pilih Meja/Area atau isi Nomor Pager terlebih dahulu untuk pesanan Makan di Sini.');
            return;
        }

        $order = SelfOrder::find($this->assigningOrderId);
        if ($order) {
            $order->update([
                'service_area_id' => $this->selectedServiceAreaId,
                'pager_id'        => $this->selectedPagerId,
            ]);
        }

        $actionType = $this->pendingActionType;
        $orderId    = $this->assigningOrderId;

        $this->closeAssignAreaModal();

        if ($actionType === 'claim') {
            $this->claimOrder($orderId, $claimAction);
        } elseif ($actionType === 'payment') {
            $this->openPaymentModal($orderId);
        }
    }

    // -----------------------------------------------------------------
    // Claim Order
    // -----------------------------------------------------------------

    public function claimOrder(int $selfOrderId, ClaimSelfOrderAction $action): void
    {
        $this->authorize('manage_self_orders');

        $order = SelfOrder::find($selfOrderId);
        if (!$order) return;

        if ($order->order_type === 'dine_in' && !$order->service_area_id && !$order->pager_id && !$this->selectedServiceAreaId && !$this->selectedPagerId) {
            $this->openAssignAreaModal($selfOrderId, 'claim');
            return;
        }

        try {
            $action->execute(
                $selfOrderId,
                auth()->id(),
                $this->selectedServiceAreaId ?: $order->service_area_id,
                $this->selectedPagerId ?: $order->pager_id
            );
            $this->selectedServiceAreaId = null;
            $this->selectedPagerId       = null;
            $this->flash('success', 'Pesanan berhasil diambil — dipindah ke tab "Diambil".');
            $this->activeTab = 'claimed';
            unset($this->claimedOrders, $this->claimedCount, $this->claimedStatusCounts, $this->paidOrders, $this->paidCount, $this->waitingOrders, $this->waitingCount);
        } catch (NoOpenShiftException $e) {
            $this->promptOpenShift($e->getMessage());
        } catch (\DomainException $e) {
            $this->flash('error', $e->getMessage());
        }
    }

    // -----------------------------------------------------------------
    // Accept Payment (Bayar di Tempat)
    // -----------------------------------------------------------------

    public function openPaymentModal(int $selfOrderId): void
    {
        $this->selectedOrderId = $selfOrderId;
        $order = SelfOrder::find($selfOrderId);
        if (!$order) return;

        if ($order->order_type === 'dine_in' && !$order->service_area_id && !$order->pager_id) {
            $this->openAssignAreaModal($selfOrderId, 'payment');
            return;
        }

        $this->paidAmount       = (float) $order->total;
        $this->showPaymentModal = true;
    }

    public function closePaymentModal(): void
    {
        $this->showPaymentModal = false;
        $this->selectedOrderId  = null;
        $this->paidAmount       = 0;
    }

    public function confirmPayment(AcceptSelfOrderPaymentAction $action): void
    {
        $this->authorize('accept_self_order_payment');

        if (!$this->selectedOrderId) return;

        if ($this->paidAmount <= 0) {
            $this->flash('error', 'Masukkan nominal pembayaran.');
            return;
        }

        try {
            $action->execute($this->selectedOrderId, auth()->id(), $this->paidAmount);
            $this->closePaymentModal();
            $this->flash('success', 'Pembayaran berhasil dikonfirmasi! Pesanan dipindah ke tab "Diambil".');
            $this->activeTab = 'claimed';
            unset($this->claimedOrders, $this->claimedCount, $this->waitingOrders, $this->waitingCount);
            $this->dispatch('selfOrderUpdated');
        } catch (NoOpenShiftException $e) {
            $this->promptOpenShift($e->getMessage());
        } catch (\DomainException $e) {
            $this->flash('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->flash('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error('AcceptSelfOrderPayment error: ' . $e->getMessage());
        }
    }

    // -----------------------------------------------------------------
    // Update Status (processing / ready / completed) & Pickup Confirmation
    // -----------------------------------------------------------------

    public function updateStatus(int $selfOrderId, string $newStatus, UpdateSelfOrderStatusAction $action): void
    {
        $this->authorize('manage_self_orders');

        $order = SelfOrder::find($selfOrderId);
        if (!$order) return;

        // Jika pesanan bawa pulang (take_away) diselesaikan dan belum ada konfirmasi pengambil,
        // tampilkan modal konfirmasi pengambil terlebih dahulu.
        if ($order->order_type === 'take_away' && $newStatus === 'completed' && !$order->pickup_confirmed_at) {
            $this->openPickupModal($selfOrderId, $newStatus);
            return;
        }

        try {
            $status = SelfOrderStatus::from($newStatus);
            $action->execute($selfOrderId, $status, auth()->id());
            $this->flash('success', 'Status pesanan diperbarui.');
            unset($this->claimedOrders, $this->claimedCount, $this->paidOrders, $this->paidCount);
            $this->dispatch('selfOrderUpdated');
        } catch (NoOpenShiftException $e) {
            $this->promptOpenShift($e->getMessage());
        } catch (\DomainException $e) {
            $this->flash('error', $e->getMessage());
        } catch (\ValueError $e) {
            $this->flash('error', 'Status tidak valid.');
        }
    }

    public function openPickupModal(int $selfOrderId, string $nextAction = 'completed'): void
    {
        $order = SelfOrder::find($selfOrderId);
        if (!$order) return;

        $this->pickupOrderId    = $selfOrderId;
        $this->pickupOrder      = $order;
        $this->pickupOption     = 'same';
        $this->pickupName       = $order->customer_name;
        $this->pickupPhone      = $order->customer_phone;
        $this->pickupNextAction = $nextAction;
        $this->showPickupModal  = true;
    }

    public function closePickupModal(): void
    {
        $this->showPickupModal  = false;
        $this->pickupOrderId    = null;
        $this->pickupOrder      = null;
        $this->pickupOption     = 'same';
        $this->pickupName       = '';
        $this->pickupPhone      = '';
        $this->pickupNextAction = 'completed';
    }

    public function confirmPickup(UpdateSelfOrderStatusAction $statusAction, ClaimSelfOrderAction $claimAction): void
    {
        $this->authorize('manage_self_orders');

        if (!$this->pickupOrderId || !$this->pickupOrder) return;

        if ($this->pickupOption === 'other') {
            $this->validate([
                'pickupName'  => 'required|min:2|max:100',
                'pickupPhone' => 'required|min:8|max:20',
            ], [
                'pickupName.required'  => 'Nama pengambil wajib diisi jika wakil/orang lain.',
                'pickupPhone.required' => 'No. HP pengambil wajib diisi.',
            ]);
        }

        try {
            $finalPickupName  = $this->pickupOption === 'same' ? $this->pickupOrder->customer_name : $this->pickupName;
            $finalPickupPhone = $this->pickupOption === 'same' ? $this->pickupOrder->customer_phone : $this->pickupPhone;

            // Simpan konfirmasi pengambil via OOP method model
            $this->pickupOrder->recordPickupConfirmation($finalPickupName, $finalPickupPhone);

            if ($this->pickupNextAction === 'claim') {
                // Klaim order (pindah dari pool ke tab Diambil)
                $claimAction->execute(
                    $this->pickupOrderId,
                    auth()->id(),
                    $this->selectedServiceAreaId ?: $this->pickupOrder->service_area_id,
                    $this->selectedPagerId ?: $this->pickupOrder->pager_id
                );
                $this->selectedServiceAreaId = null;
                $this->selectedPagerId       = null;
                $this->closePickupModal();
                $this->flash('success', 'Data pengambil disimpan & pesanan berhasil diambil.');
                $this->activeTab = 'claimed';
                unset($this->claimedOrders, $this->claimedCount, $this->claimedStatusCounts, $this->paidOrders, $this->paidCount, $this->waitingOrders, $this->waitingCount);
            } else {
                // Transisi status (processing → ready → completed, dll)
                $status = SelfOrderStatus::from($this->pickupNextAction);
                $statusAction->execute($this->pickupOrderId, $status, auth()->id());
                $this->closePickupModal();
                $this->flash('success', 'Konfirmasi pengambil berhasil disimpan & pesanan diselesaikan.');
                unset($this->claimedOrders, $this->claimedCount, $this->claimedStatusCounts, $this->paidOrders, $this->paidCount);
            }

            $this->dispatch('selfOrderUpdated');
        } catch (NoOpenShiftException $e) {
            $this->promptOpenShift($e->getMessage());
        } catch (\DomainException $e) {
            $this->flash('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->flash('error', 'Gagal memproses konfirmasi: ' . $e->getMessage());
        }
    }

    /**
     * Kasir mencoba klaim/proses/bayar pesanan tapi belum punya shift terbuka — daripada cuma
     * menampilkan error, langsung minta parent (PosCheckout) menampilkan modal konfirmasi
     * "Buka Shift" yang sudah ada, supaya kasir tidak perlu keluar dari modal ini untuk tahu
     * harus berbuat apa. Event ini didengarkan lintas komponen (lihat PosCheckout::handleOpenShiftRequested()).
     */
    private function promptOpenShift(string $message): void
    {
        $this->flash('error', $message . ' Konfirmasi di jendela yang baru muncul, lalu coba lagi.');
        $this->dispatch('open-shift-requested');
    }

    // -----------------------------------------------------------------
    // Cancel
    // -----------------------------------------------------------------

    public function openCancelModal(int $selfOrderId): void
    {
        $this->selectedOrderId = $selfOrderId;
        $this->cancelReason    = '';
        $this->showCancelModal = true;
    }

    public function cancelOrderFromDetail(): void
    {
        if ($this->selectedOrder) {
            $orderId = $this->selectedOrder->id;
            $this->closeDetail();
            $this->openCancelModal($orderId);
        }
    }

    public function closeCancelModal(): void
    {
        $this->showCancelModal = false;
        $this->cancelReason    = '';
    }

    public function cancelOrder(CancelSelfOrderAction $action): void
    {
        $this->authorize('cancel_self_order');

        if (empty(trim($this->cancelReason))) {
            $this->flash('error', 'Masukkan alasan pembatalan.');
            return;
        }

        try {
            $action->execute($this->selectedOrderId, auth()->id(), $this->cancelReason);
            $this->closeCancelModal();
            $this->flash('success', 'Pesanan berhasil dibatalkan.');
        } catch (\DomainException $e) {
            $this->flash('error', $e->getMessage());
        }
    }

    // -----------------------------------------------------------------
    // Print
    // -----------------------------------------------------------------

    public function printReceipt(int $selfOrderId): void
    {
        $this->authorize('print_self_order_receipt');
        // Buka di jendela/tab baru (bukan redirect) agar kasir tidak kehilangan konteks POS-nya.
        $this->dispatch('open-new-window', url: route('admin.self-orders.print', $selfOrderId));
    }

    // -----------------------------------------------------------------
    // New Order Handler (untuk notifikasi)
    // -----------------------------------------------------------------

    public function handleNewOrder(): void
    {
        // Re-render akan otomatis mengupdate count
        $this->flash('info', 'Ada pesanan Self Order baru masuk!');
    }

    // -----------------------------------------------------------------
    // Helper
    // -----------------------------------------------------------------

    private function flash(string $type, string $message): void
    {
        $this->flashType    = $type;
        $this->flashMessage = $message;
    }

    // -----------------------------------------------------------------
    // Render
    // -----------------------------------------------------------------

    public function render()
    {
        return view('livewire.admin.self-order-dashboard')
            ->layout('layouts.admin');
    }
}
