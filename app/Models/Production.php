<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Production extends Model
{
    protected $fillable = [
        'production_code',
        'production_date',
        'total_cost',
        'note',
        'status',
        'user_id',
    ];

    protected $casts = [
        'production_date' => 'date',
        'total_cost' => 'float',
    ];

    public function inputs()
    {
        return $this->hasMany(ProductionInput::class);
    }

    public function outputs()
    {
        return $this->hasMany(ProductionOutput::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
