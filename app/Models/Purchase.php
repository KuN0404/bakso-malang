<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $fillable = [
        'invoice_number',
        'purchase_date',
        'supplier_name',
        'total_amount',
        'note',
        'status',
        'user_id',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'total_amount' => 'float',
    ];

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
