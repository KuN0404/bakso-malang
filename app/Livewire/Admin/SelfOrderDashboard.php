<?php

namespace App\Livewire\Admin;

use App\Actions\SelfOrder\AcceptSelfOrderPaymentAction;
use App\Actions\SelfOrder\CancelSelfOrderAction;
use App\Actions\SelfOrder\ClaimSelfOrderAction;
use App\Actions\SelfOrder\UpdateSelfOrderStatusAction;
use App\Enums\SelfOrderStatus;
use App\Exceptions\NoOpenShiftException;
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

    // -----------------------------------------------------------------
    // Detail Modal
    // -----------------------------------------------------------------
    public bool $showDetailModal   = false;
    public bool $showCancelModal   = false;
    public bool $showPaymentModal  = false;
    public ?int $selectedOrderId   = null;
    public ?SelfOrder $selectedOrder = null;
    public string $cancelReason    = '';
    public float  $paidAmount      = 0;

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
        return SelfOrder::getPaginatedClaimed(20, $this->onlyMyClaimedOrders ? auth()->id() : null);
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

    public function toggleOnlyMine(): void
    {
        $this->onlyMyClaimedOrders = !$this->onlyMyClaimedOrders;
        unset($this->claimedOrders, $this->claimedCount);
    }

    // -----------------------------------------------------------------
    // Tab Navigation
    // -----------------------------------------------------------------

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    // -----------------------------------------------------------------
    // Detail Modal
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

    // -----------------------------------------------------------------
    // Claim Order
    // -----------------------------------------------------------------

    public function claimOrder(int $selfOrderId, ClaimSelfOrderAction $action): void
    {
        $this->authorize('manage_self_orders');

        try {
            $action->execute($selfOrderId, auth()->id());
            $this->flash('success', 'Pesanan berhasil diambil — dipindah ke tab "Diambil".');
            // Pindah otomatis ke tab "Diambil" agar kasir langsung lihat ke mana order-nya pergi.
            $this->activeTab = 'claimed';
            unset($this->claimedOrders, $this->claimedCount, $this->waitingOrders, $this->waitingCount);
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
        $this->paidAmount      = (float) $order?->total;
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
    // Update Status (processing / ready / completed)
    // -----------------------------------------------------------------

    public function updateStatus(int $selfOrderId, string $newStatus, UpdateSelfOrderStatusAction $action): void
    {
        $this->authorize('manage_self_orders');

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
