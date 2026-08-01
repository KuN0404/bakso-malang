<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DailyQueueNumber extends Model
{
    protected $fillable = [
        'date',
        'last_number',
    ];

    protected $casts = [
        'date' => 'date',
        'last_number' => 'integer',
    ];

    /**
     * Get the next queue number for today.
     * Automatically resets to 1 on new day.
     * Uses database transaction for thread safety.
     */
    public static function getNextNumber(): int
    {
        return DB::transaction(function () {
            $today = now()->toDateString();
            
            $record = static::lockForUpdate()
                ->whereDate('date', $today)
                ->first();
 
            if (!$record) {
                $record = static::create([
                    'date' => $today,
                    'last_number' => 1,
                ]);
                
                return 1;
            }
 
            $nextNumber = $record->last_number + 1;
            $record->update(['last_number' => $nextNumber]);
            
            return $nextNumber;
        });
    }
 
    /**
     * Get the current queue number without incrementing.
     */
    public static function getCurrentNumber(): int
    {
        $today = now()->toDateString();
        
        $record = static::whereDate('date', $today)->first();
        
        return $record ? $record->last_number : 0;
    }

    /**
     * Reset queue number for today (admin use only).
     */
    public static function resetToday(): void
    {
        $today = now()->toDateString();
        
        static::updateOrCreate(
            ['date' => $today],
            ['last_number' => 0]
        );
    }
}
