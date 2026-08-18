<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris snapshot komponen yang benar-benar dipakai pada sebuah
 * transaction_detail. Bersifat historis — jangan pernah di-update setelah dibuat,
 * dan jangan di-backfill dari BOM yang hidup (BOM bisa sudah berubah).
 *
 * Dipakai restoreForReturn() supaya retur mengembalikan komposisi yang benar,
 * termasuk untuk penjualan bersubstitusi.
 */
class TransactionDetailComponent extends Model
{
    protected $fillable = [
        'transaction_detail_id',
        'component_id',
        'component_name',
        'quantity_per_unit',
        'quantity_total',
        'source',
        'replaced_component_id',
        'replaced_quantity',
        'product_bom_id',
        'substitution_rule_id',
    ];

    protected $casts = [
        'quantity_per_unit' => 'float',
        'quantity_total'    => 'float',
        'replaced_quantity' => 'float',
    ];

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    public function transactionDetail(): BelongsTo
    {
        return $this->belongsTo(TransactionDetail::class);
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }

    public function replacedComponent(): BelongsTo
    {
        return $this->belongsTo(Component::class, 'replaced_component_id');
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    public function isSubstitute(): bool
    {
        return $this->source === 'substitute';
    }

    /**
     * True jika yang benar-benar terpotong lebih sedikit dari yang dimaksud —
     * penanda oversold pada jalur best-effort settlement QRIS.
     */
    public function wasUnderDeducted(int $detailQuantity): bool
    {
        return $this->quantity_total < ($this->quantity_per_unit * $detailQuantity);
    }
}
