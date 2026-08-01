<?php

namespace App\Services;

use App\Models\ProductReturn;
use App\Models\Shift;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ReportSyncService
 *
 * Bertanggung jawab untuk menulis (menduplikasi) data penting ke database laporan
 * (koneksi 'mysql_report') secara sinkron setelah operasi utama selesai.
 *
 * Desain ini memisahkan logika sinkronisasi dari Livewire/Controller
 * agar kode tetap bersih dan mudah dikelola.
 */
class ReportSyncService
{
    /**
     * Nama koneksi database laporan (didefinisikan di config/database.php).
     */
    private const REPORT_CONNECTION = 'mysql_report';

    // -----------------------------------------------------------------------
    // Transaction Sync
    // -----------------------------------------------------------------------

    /**
     * Salin satu transaksi beserta detail dan modifier-nya ke DB laporan.
     *
     * @param Transaction $transaction Transaksi yang sudah di-load dengan 'details.modifiers'
     */
    public function syncTransaction(Transaction $transaction): void
    {
        try {
            $rdb = DB::connection(self::REPORT_CONNECTION);

            // Pastikan data master yang dibutuhkan oleh transaksi sudah tersinkronisasi terlebih dahulu
            if ($transaction->user_id) {
                $this->ensureUserSynced($transaction->user_id);
            }
            if ($transaction->payment_source_id) {
                $this->ensurePaymentSourceSynced($transaction->payment_source_id);
            }
            if ($transaction->service_area_id) {
                $this->ensureServiceAreaSynced($transaction->service_area_id);
            }
            if ($transaction->shift_id) {
                $shift = Shift::find($transaction->shift_id);
                if ($shift) {
                    $this->syncShift($shift);
                }
            }

            // Gunakan upsert agar safe jika dijalankan dua kali (idempotent)
            $rdb->table('transactions')->upsert(
                [[
                    'id'                => $transaction->id,
                    'user_id'           => $transaction->user_id,
                    'shift_id'          => $transaction->shift_id,
                    'payment_source_id' => $transaction->payment_source_id,
                    'service_area_id'   => $transaction->service_area_id,
                    'invoice_number'    => $transaction->invoice_number,
                    'queue_number'      => $transaction->queue_number,
                    'subtotal'          => $transaction->subtotal,
                    'discount_amount'   => $transaction->discount_amount,
                    'tax_amount'        => $transaction->tax_amount,
                    'total'             => $transaction->total,
                    'paid_amount'       => $transaction->paid_amount,
                    'change_amount'     => $transaction->change_amount,
                    'payment_method'    => $transaction->payment_method,
                    'status'            => $transaction->status,
                    'customer_name'     => $transaction->customer_name,
                    'order_type'        => $transaction->order_type,
                    'notes'             => $transaction->notes,
                    'print_count'       => $transaction->print_count ?? 0,
                    'cancelled_reason'  => $transaction->cancelled_reason,
                    'cancelled_by'      => $transaction->cancelled_by,
                    'cancelled_at'      => $transaction->cancelled_at,
                    'created_at'        => $transaction->created_at,
                    'updated_at'        => $transaction->updated_at,
                ]],
                ['id'], // unique key
                // Kolom yang diupdate jika sudah ada (semua kecuali id dan created_at)
                [
                    'user_id', 'shift_id', 'payment_source_id', 'service_area_id', 
                    'invoice_number', 'queue_number', 'subtotal', 'discount_amount', 
                    'tax_amount', 'total', 'paid_amount', 'change_amount', 
                    'payment_method', 'status', 'customer_name', 'order_type', 
                    'notes', 'print_count', 'cancelled_reason', 'cancelled_by', 
                    'cancelled_at', 'updated_at'
                ]
            );

            // Sync detail dan modifiers
            foreach ($transaction->details as $detail) {
                if ($detail->product_id) {
                    $this->ensureProductSynced($detail->product_id);
                }

                $rdb->table('transaction_details')->upsert(
                    [[
                        'id'             => $detail->id,
                        'transaction_id' => $detail->transaction_id,
                        'product_id'     => $detail->product_id,
                        'product_name'   => $detail->product_name,
                        'unit_price'     => $detail->unit_price,
                        'quantity'       => $detail->quantity,
                        'modifier_total' => $detail->modifier_total,
                        'subtotal'       => $detail->subtotal,
                        'notes'          => $detail->notes,
                        'status'         => $detail->status,
                        'fulfilled_at'   => $detail->fulfilled_at,
                        'created_at'     => $detail->created_at,
                        'updated_at'     => $detail->updated_at,
                    ]],
                    ['id'],
                    [
                        'product_id', 'product_name', 'unit_price', 'quantity', 
                        'modifier_total', 'subtotal', 'notes', 'status', 
                        'fulfilled_at', 'updated_at'
                    ]
                );

                // Sync pivot modifier per detail
                foreach ($detail->modifiers as $modifier) {
                    // Pastikan modifier dan group modifier sudah ada di DB laporan
                    $this->ensureModifierSynced($modifier->id);

                    $exists = $rdb->table('transaction_detail_modifier')
                        ->where('transaction_detail_id', $detail->id)
                        ->where('modifier_id', $modifier->id)
                        ->exists();

                    if (!$exists) {
                        $rdb->table('transaction_detail_modifier')->insert([
                            'transaction_detail_id' => $detail->id,
                            'modifier_id'           => $modifier->id,
                            'modifier_name'         => $modifier->pivot->modifier_name,
                            'price_adjustment'      => $modifier->pivot->price_adjustment,
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Log error tanpa menghentikan transaksi utama
            Log::error('[ReportSync] Gagal sync transaksi ID ' . $transaction->id . ': ' . $e->getMessage());
        }
    }

    // -----------------------------------------------------------------------
    // Shift Sync
    // -----------------------------------------------------------------------

    /**
     * Salin data shift yang sudah ditutup beserta expense-nya ke DB laporan.
     *
     * @param Shift $shift Shift yang sudah di-close (status = closed), sudah di-load dengan 'expenses'
     */
    public function syncShift(Shift $shift): void
    {
        try {
            $rdb = DB::connection(self::REPORT_CONNECTION);

            // Pastikan user pemilik shift sudah tersinkronisasi
            if ($shift->user_id) {
                $this->ensureUserSynced($shift->user_id);
            }

            $rdb->table('shifts')->upsert(
                [[
                    'id'                  => $shift->id,
                    'user_id'             => $shift->user_id,
                    'started_at'          => $shift->started_at,
                    'ended_at'            => $shift->ended_at,
                    'opening_cash'        => $shift->opening_cash,
                    'expected_cash'       => $shift->expected_cash,
                    'actual_cash'         => $shift->actual_cash,
                    'cash_difference'     => $shift->cash_difference,
                    'expected_non_cash'   => $shift->expected_non_cash,
                    'actual_non_cash'     => $shift->actual_non_cash,
                    'non_cash_difference' => $shift->non_cash_difference,
                    'status'              => $shift->status,
                    'notes'               => $shift->notes,
                    'created_at'          => $shift->created_at,
                    'updated_at'          => $shift->updated_at,
                ]],
                ['id'],
                ['ended_at', 'expected_cash', 'actual_cash', 'cash_difference',
                 'expected_non_cash', 'actual_non_cash', 'non_cash_difference',
                 'status', 'notes', 'opening_cash', 'updated_at']
            );

            // Sync expenses shift
            foreach ($shift->expenses as $expense) {
                // Pastikan transaksi terkait (jika ada seperti refund) tersinkronisasi terlebih dahulu
                if ($expense->order_id) {
                    $transaction = Transaction::find($expense->order_id);
                    if ($transaction) {
                        $this->syncTransaction($transaction);
                    }
                }

                $rdb->table('shift_expenses')->upsert(
                    [[
                        'id'          => $expense->id,
                        'shift_id'    => $expense->shift_id,
                        'order_id'    => $expense->order_id,
                        'description' => $expense->description,
                        'amount'      => $expense->amount,
                        'category'    => $expense->category,
                        'created_at'  => $expense->created_at,
                        'updated_at'  => $expense->updated_at,
                    ]],
                    ['id'],
                    ['shift_id', 'order_id', 'description', 'amount', 'category', 'updated_at']
                );
            }
        } catch (\Throwable $e) {
            Log::error('[ReportSync] Gagal sync shift ID ' . $shift->id . ': ' . $e->getMessage());
        }
    }

    // -----------------------------------------------------------------------
    // Return Sync
    // -----------------------------------------------------------------------

    /**
     * Salin data retur beserta item-itemnya ke DB laporan.
     *
     * @param ProductReturn $return Retur yang sudah di-load dengan 'items'
     */
    public function syncReturn(ProductReturn $return): void
    {
        try {
            $rdb = DB::connection(self::REPORT_CONNECTION);

            // Pastikan entitas master terkait sudah tersinkronisasi
            if ($return->user_id) {
                $this->ensureUserSynced($return->user_id);
            }
            if ($return->transaction_id) {
                $transaction = Transaction::find($return->transaction_id);
                if ($transaction) {
                    $this->syncTransaction($transaction);
                }
            }
            if ($return->shift_id) {
                $shift = Shift::find($return->shift_id);
                if ($shift) {
                    $this->syncShift($shift);
                }
            }

            $rdb->table('returns')->upsert(
                [[
                    'id'             => $return->id,
                    'transaction_id' => $return->transaction_id,
                    'user_id'        => $return->user_id,
                    'shift_id'       => $return->shift_id,
                    'return_number'  => $return->return_number,
                    'total_refund'   => $return->total_refund,
                    'reason'         => $return->reason,
                    'notes'          => $return->notes,
                    'created_at'     => $return->created_at,
                    'updated_at'     => $return->updated_at,
                ]],
                ['id'],
                ['transaction_id', 'user_id', 'shift_id', 'return_number', 'total_refund', 'reason', 'notes', 'updated_at']
            );

            foreach ($return->items as $item) {
                if ($item->product_id) {
                    $this->ensureProductSynced($item->product_id);
                }

                $rdb->table('return_items')->upsert(
                    [[
                        'id'                    => $item->id,
                        'return_id'             => $item->return_id,
                        'transaction_detail_id' => $item->transaction_detail_id,
                        'product_id'            => $item->product_id,
                        'product_name'          => $item->product_name,
                        'modifiers'             => is_array($item->modifiers)
                            ? json_encode($item->modifiers)
                            : $item->modifiers,
                        'quantity'              => $item->quantity,
                        'unit_price'            => $item->unit_price,
                        'subtotal'              => $item->subtotal,
                        'created_at'            => $item->created_at,
                        'updated_at'            => $item->updated_at,
                    ]],
                    ['id'],
                    [
                        'return_id', 'transaction_detail_id', 'product_id', 'product_name', 
                        'modifiers', 'quantity', 'unit_price', 'subtotal', 'updated_at'
                    ]
                );
            }
        } catch (\Throwable $e) {
            Log::error('[ReportSync] Gagal sync retur ID ' . $return->id . ': ' . $e->getMessage());
        }
    }

    // -----------------------------------------------------------------------
    // Master Data Helpers
    // -----------------------------------------------------------------------

    private function ensureUserSynced(int $userId): void
    {
        $user = \App\Models\User::find($userId);
        if ($user) {
            DB::connection(self::REPORT_CONNECTION)->table('users')->upsert(
                [[
                    'id'         => $user->id,
                    'name'       => $user->name,
                    'username'   => $user->username,
                    'email'      => $user->email,
                    'password'   => $user->password,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ]],
                ['id'],
                ['name', 'username', 'email', 'password', 'updated_at']
            );
        }
    }

    private function ensurePaymentSourceSynced(int $paymentSourceId): void
    {
        $ps = \App\Models\PaymentSource::find($paymentSourceId);
        if ($ps) {
            DB::connection(self::REPORT_CONNECTION)->table('payment_sources')->upsert(
                [[
                    'id'             => $ps->id,
                    'name'           => $ps->name,
                    'type'           => $ps->type,
                    'account_number' => $ps->account_number,
                    'account_name'   => $ps->account_name,
                    'icon'           => $ps->icon,
                    'is_active'      => $ps->is_active,
                    'sort_order'     => $ps->sort_order,
                    'created_at'     => $ps->created_at,
                    'updated_at'     => $ps->updated_at,
                ]],
                ['id'],
                ['name', 'type', 'account_number', 'account_name', 'icon', 'is_active', 'sort_order', 'updated_at']
            );
        }
    }

    private function ensureServiceAreaSynced(int $serviceAreaId): void
    {
        $sa = \App\Models\ServiceArea::find($serviceAreaId);
        if ($sa) {
            DB::connection(self::REPORT_CONNECTION)->table('service_areas')->upsert(
                [[
                    'id'          => $sa->id,
                    'name'        => $sa->name,
                    'code'        => $sa->code,
                    'description' => $sa->description,
                    'type'        => $sa->type,
                    'capacity'    => $sa->capacity,
                    'sort_order'  => $sa->sort_order,
                    'is_active'   => $sa->is_active,
                    'created_at'  => $sa->created_at,
                    'updated_at'  => $sa->updated_at,
                ]],
                ['id'],
                ['name', 'code', 'description', 'type', 'capacity', 'sort_order', 'is_active', 'updated_at']
            );
        }
    }

    private function ensureCategorySynced(int $categoryId): void
    {
        $cat = \App\Models\Category::find($categoryId);
        if ($cat) {
            DB::connection(self::REPORT_CONNECTION)->table('categories')->upsert(
                [[
                    'id'          => $cat->id,
                    'name'        => $cat->name,
                    'slug'        => $cat->slug,
                    'description' => $cat->description,
                    'icon'        => $cat->icon,
                    'sort_order'  => $cat->sort_order,
                    'is_active'   => $cat->is_active,
                    'created_at'  => $cat->created_at,
                    'updated_at'  => $cat->updated_at,
                ]],
                ['id'],
                ['name', 'slug', 'description', 'icon', 'sort_order', 'is_active', 'updated_at']
            );
        }
    }

    private function ensureProductSynced(int $productId): void
    {
        $prod = \App\Models\Product::find($productId);
        if ($prod) {
            if ($prod->category_id) {
                $this->ensureCategorySynced($prod->category_id);
            }

            DB::connection(self::REPORT_CONNECTION)->table('products')->upsert(
                [[
                    'id'          => $prod->id,
                    'category_id' => $prod->category_id,
                    'name'        => $prod->name,
                    'slug'        => $prod->slug,
                    'sku'         => $prod->sku,
                    'description' => $prod->description,
                    'price'       => $prod->price,
                    'cost_price'  => $prod->cost_price,
                    'image'       => $prod->image,
                    'is_active'   => $prod->is_active,
                    'is_featured' => $prod->is_featured,
                    'stock'       => $prod->stock,
                    'track_stock' => $prod->track_stock,
                    'created_at'  => $prod->created_at,
                    'updated_at'  => $prod->updated_at,
                    'deleted_at'  => $prod->deleted_at,
                ]],
                ['id'],
                ['category_id', 'name', 'slug', 'sku', 'description', 'price', 'cost_price', 'image', 'is_active', 'is_featured', 'stock', 'track_stock', 'updated_at', 'deleted_at']
            );
        }
    }

    private function ensureModifierGroupSynced(int $modifierGroupId): void
    {
        $group = \App\Models\ModifierGroup::find($modifierGroupId);
        if ($group) {
            DB::connection(self::REPORT_CONNECTION)->table('modifier_groups')->upsert(
                [[
                    'id'             => $group->id,
                    'name'           => $group->name,
                    'selection_type' => $group->selection_type,
                    'is_required'    => $group->is_required,
                    'min_selections' => $group->min_selections,
                    'max_selections' => $group->max_selections,
                    'is_active'      => $group->is_active,
                    'created_at'     => $group->created_at,
                    'updated_at'     => $group->updated_at,
                ]],
                ['id'],
                ['name', 'selection_type', 'is_required', 'min_selections', 'max_selections', 'is_active', 'updated_at']
            );
        }
    }

    private function ensureModifierSynced(int $modifierId): void
    {
        $mod = \App\Models\Modifier::find($modifierId);
        if ($mod) {
            if ($mod->modifier_group_id) {
                $this->ensureModifierGroupSynced($mod->modifier_group_id);
            }

            DB::connection(self::REPORT_CONNECTION)->table('modifiers')->upsert(
                [[
                    'id'                => $mod->id,
                    'modifier_group_id' => $mod->modifier_group_id,
                    'name'              => $mod->name,
                    'price_adjustment'  => $mod->price_adjustment,
                    'is_active'         => $mod->is_active,
                    'sort_order'        => $mod->sort_order,
                    'created_at'        => $mod->created_at,
                    'updated_at'        => $mod->updated_at,
                ]],
                ['id'],
                ['modifier_group_id', 'name', 'price_adjustment', 'is_active', 'sort_order', 'updated_at']
            );
        }
    }
}
