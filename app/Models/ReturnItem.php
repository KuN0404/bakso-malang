<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnItem extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'modifiers' => 'array',
    ];

    public function return()
    {
        return $this->belongsTo(ProductReturn::class, 'return_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
