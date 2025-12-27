<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportAggregation extends Model
{
    protected $fillable = [
        'type',
        'report_date',
        'hour',
        'total_sales',
        'transaction_count',
        'total_discount',
        'payment_breakdown',
        'category_breakdown',
        'cancelled_count',
        'cancelled_amount',
    ];

    protected $casts = [
        'report_date' => 'date',
        'hour' => 'integer',
        'total_sales' => 'decimal:2',
        'transaction_count' => 'integer',
        'total_discount' => 'decimal:2',
        'payment_breakdown' => 'array',
        'category_breakdown' => 'array',
        'cancelled_count' => 'integer',
        'cancelled_amount' => 'decimal:2',
    ];

    public function scopeHourly($query)
    {
        return $query->where('type', 'hourly');
    }

    public function scopeDaily($query)
    {
        return $query->where('type', 'daily');
    }

    public function scopeWeekly($query)
    {
        return $query->where('type', 'weekly');
    }

    public function scopeMonthly($query)
    {
        return $query->where('type', 'monthly');
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('report_date', $date);
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('report_date', [$startDate, $endDate]);
    }
}
