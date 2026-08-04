<?php

namespace App\Actions\SelfOrder;

use App\Enums\SelfOrderStatus;
use App\Exceptions\NoOpenShiftException;
use App\Models\SelfOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * UpdateSelfOrderStatusAction — kasir update status order (processing → ready → completed).
 */
class UpdateSelfOrderStatusAction
{
    /**
     * @throws \DomainException
     */
    public function execute(int $selfOrderId, SelfOrderStatus $newStatus, int $cashierId): SelfOrder
    {
        return DB::transaction(function () use ($selfOrderId, $newStatus, $cashierId) {
            $selfOrder = SelfOrder::where('id', $selfOrderId)
                ->lockForUpdate()
                ->firstOrFail();

            // Menandai kasir pertama yang memproses pesanan (QRIS / Cash) sebagai owner.
            // Wajib punya shift terbuka sendiri — jangan fallback ke shift kasir lain (salah atribusi
            // kas/laporan) atau ke 0 (bukan ID shift valid, melanggar foreign key).
            if (!$selfOrder->isClaimed()) {
                $shift = \App\Models\Shift::where('user_id', $cashierId)
                    ->where('status', 'open')
                    ->latest()
                    ->first();

                if (!$shift) {
                    throw new NoOpenShiftException('Anda belum membuka shift. Buka shift terlebih dahulu sebelum memproses pesanan.');
                }

                $selfOrder->claimBy($cashierId, $shift->id);
            } elseif ($selfOrder->processed_by !== $cashierId) {
                $ownerName = $selfOrder->processedBy?->name ?? "kasir lain";
                throw new \DomainException("Order #{$selfOrder->queue_display} sudah diambil/diproses oleh {$ownerName}.");
            }

            $selfOrder->transitionTo($newStatus, 'cashier', $cashierId);

            // Update user_id & shift_id di transaksi POS jika transaksi sudah dibuat (seperti QRIS)
            if ($selfOrder->transaction_id) {
                \App\Models\Transaction::where('id', $selfOrder->transaction_id)->update([
                    'user_id'  => $cashierId,
                    'shift_id' => $selfOrder->shift_id,
                ]);
            }

            Log::channel('self_order')->info(
                "SelfOrder [{$selfOrder->id}] status diubah ke [{$newStatus->label()}] oleh Kasir [{$cashierId}]."
            );

            return $selfOrder->fresh();
        });
    }
}
