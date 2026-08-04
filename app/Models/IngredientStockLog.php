<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IngredientStockLog extends Model
{
    protected static function booted(): void
    {
        static::created(function ($log) {
            app(\App\Services\ReportSyncService::class)->syncIngredientStockLog($log);
        });
    }

    protected $fillable = [
        'ingredient_id',
        'user_id',
        'type',
        'amount',
        'final_stock',
        'note',
        'reference_id',
    ];

    protected $casts = [
        'amount' => 'float',
        'final_stock' => 'float',
    ];

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function record(
        int $ingredientId,
        ?int $userId,
        string $type,
        float $amount,
        float $finalStock,
        ?string $note = null,
        ?int $referenceId = null
    ): self {
        return static::create([
            'ingredient_id' => $ingredientId,
            'user_id' => $userId,
            'type' => $type,
            'amount' => $amount,
            'final_stock' => $finalStock,
            'note' => $note,
            'reference_id' => $referenceId,
        ]);
    }
}
