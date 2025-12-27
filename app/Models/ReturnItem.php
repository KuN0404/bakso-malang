<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnItem extends Model
{
    protected $guarded = ['id'];

    public function return()
    {
        return $this->belongsTo(ProductReturn::class, 'return_id');
    }
}
