<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnItem extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'modifiers' => 'array',
    ];

    /**
     * Get already returned quantity for a transaction detail.
     */
    public static function getReturnedQuantity(int $detailId): int
    {
        return (int) static::where('transaction_detail_id', $detailId)->sum('quantity');
    }

    public function return()
    {
        return $this->belongsTo(ProductReturn::class, 'return_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
