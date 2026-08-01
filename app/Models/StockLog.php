<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockLog extends Model
{
    protected $fillable = [
        'product_id',
        'user_id',
        'type',
        'amount',
        'final_stock',
        'note',
        'reference_id',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Catat perubahan stok secara standar (factory method).
     * Gunakan ini dari model/service — bukan dari Livewire/Controller langsung.
     */
    public static function record(
        int $productId,
        int $userId,
        string $type,
        int $amount,
        int $finalStock,
        ?string $note = null,
        ?int $referenceId = null
    ): self {
        return static::create([
            'product_id'   => $productId,
            'user_id'      => $userId,
            'type'         => $type,
            'amount'       => $amount,
            'final_stock'  => $finalStock,
            'note'         => $note,
            'reference_id' => $referenceId,
        ]);
    }
}
