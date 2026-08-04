<?php

namespace App\Actions\SelfOrder;

use App\Enums\SelfOrderStatus;
use App\Models\SelfOrder;
use App\Services\StockReservationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * CancelSelfOrderAction — pembatalan order oleh kasir/admin.
 * Customer tidak bisa cancel langsung; hanya via kasir.
 */
class CancelSelfOrderAction
{
    public function __construct(
        private readonly StockReservationService $reservationService,
    ) {}

    /**
     * @throws \DomainException
     */
    public function execute(int $selfOrderId, int $cancelledByUserId, string $reason): void
    {
        DB::transaction(function () use ($selfOrderId, $cancelledByUserId, $reason) {
            $selfOrder = SelfOrder::where('id', $selfOrderId)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$selfOrder->isCancellable()) {
                throw new \DomainException(
                    "Order #{$selfOrder->queue_display} tidak bisa dibatalkan. " .
                    "Status saat ini: {$selfOrder->status->label()}."
                );
            }

            $selfOrder->update([
                'status'           => SelfOrderStatus::Cancelled->value,
                'cancelled_by'     => $cancelledByUserId,
                'cancelled_reason' => $reason,
                'cancelled_at'     => now(),
            ]);

            // Release stock reservations
            $this->reservationService->releaseForSelfOrder($selfOrder);

            Log::channel('self_order')->info(
                "SelfOrder [{$selfOrder->id}] dibatalkan oleh User [{$cancelledByUserId}]. Alasan: {$reason}"
            );
        });
    }
}
