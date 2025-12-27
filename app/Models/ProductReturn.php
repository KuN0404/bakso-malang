<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductReturn extends Model
{
    protected $table = 'returns';
    protected $guarded = ['id'];

    public static function generateReturnNumber()
    {
        $prefix = 'RTN-' . date('Ymd') . '-';
        $last = self::where('return_number', 'like', $prefix . '%')->orderBy('id', 'desc')->first();
        if ($last) {
            $num = (int) Str::afterLast($last->return_number, '-') + 1;
        } else {
            $num = 1;
        }
        return $prefix . str_pad($num, 4, '0', STR_PAD_LEFT);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function items()
    {
        return $this->hasMany(ReturnItem::class, 'return_id');
    }
}
