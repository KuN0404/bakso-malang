<?php

namespace App\Actions\SelfOrder;

use App\Enums\SelfOrderStatus;
use App\Exceptions\NoOpenShiftException;
use App\Models\SelfOrder;
use App\Models\Shift;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ClaimSelfOrderAction — kasir "mengambil" pesanan Self Order.
 * Yang klik "Ambil Pesanan" menjadi owner transaksi.
 */
class ClaimSelfOrderAction
{
    /**
     * @throws \DomainException
     */
    public function execute(int $selfOrderId, int $cashierId, ?int $serviceAreaId = null, ?int $pagerId = null): SelfOrder
    {
        return DB::transaction(function () use ($selfOrderId, $cashierId, $serviceAreaId, $pagerId) {
            $selfOrder = SelfOrder::where('id', $selfOrderId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($selfOrder->isClaimed()) {
                throw new \DomainException(
                    "Order #{$selfOrder->queue_display} sudah diambil oleh kasir lain."
                );
            }

            if ($selfOrder->status->isFinal()) {
                throw new \DomainException(
                    "Order #{$selfOrder->queue_display} sudah selesai/dibatalkan."
                );
            }

            // Wajib punya shift terbuka milik sendiri — konsisten dengan AcceptSelfOrderPaymentAction
            // dan UpdateSelfOrderStatusAction. Selain mencegah shift_id=null (order jadi tak terlihat
            // di Kitchen Display), ini juga menjaga atribusi kas/laporan tetap benar.
            $shift = Shift::where('user_id', $cashierId)
                ->where('status', 'open')
                ->first();

            if (!$shift) {
                throw new NoOpenShiftException('Anda belum membuka shift. Buka shift terlebih dahulu sebelum mengambil pesanan.');
            }

            $assignedAreaId = $selfOrder->order_type === 'dine_in' ? ($serviceAreaId ?? $selfOrder->service_area_id) : null;
            $assignedPagerId = $pagerId ?? $selfOrder->pager_id;

            $selfOrder->update([
                'processed_by'    => $cashierId,
                'shift_id'        => $shift->id,
                'service_area_id' => $assignedAreaId,
                'pager_id'        => $assignedPagerId,
                'claimed_at'      => now(),
            ]);

            if ($selfOrder->transaction_id) {
                \App\Models\Transaction::where('id', $selfOrder->transaction_id)->update([
                    'user_id'         => $cashierId,
                    'shift_id'        => $shift->id,
                    'service_area_id' => $assignedAreaId,
                    'pager_id'        => $assignedPagerId,
                ]);
            }

            Log::channel('self_order')->info(
                "SelfOrder [{$selfOrder->id}] diambil oleh Kasir [{$cashierId}], Shift [{$shift?->id}]."
            );

            return $selfOrder->fresh();
        });
    }
}
