<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'shift_id',
        'payment_source_id',
        'invoice_number',
        'queue_number',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total',
        'paid_amount',
        'change_amount',
        'payment_method',
        'status',
        'cancelled_reason',
        'cancelled_by',
        'cancelled_at',
        'customer_name',
        'order_type',
        'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'queue_number' => 'integer',
        'cancelled_at' => 'datetime',
    ];

    // Eager load these relations by default for performance
    protected $with = ['details'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function paymentSource(): BelongsTo
    {
        return $this->belongsTo(PaymentSource::class);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function details(): HasMany
    {
        return $this->hasMany(TransactionDetail::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isCash(): bool
    {
        return $this->payment_method === 'cash';
    }

    public static function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $date = now()->format('Ymd');
        
        $lastInvoice = static::whereDate('created_at', today())
            ->orderByDesc('id')
            ->first();

        $sequence = 1;
        if ($lastInvoice) {
            $lastSequence = (int) substr($lastInvoice->invoice_number, -4);
            $sequence = $lastSequence + 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }

    public function complete(float $paidAmount): bool
    {
        $change = $paidAmount - $this->total;
        
        return $this->update([
            'status' => 'completed',
            'paid_amount' => $paidAmount,
            'change_amount' => max(0, $change),
        ]);
    }

    public function cancel(int $cancelledBy, string $reason): bool
    {
        return DB::transaction(function () use ($cancelledBy, $reason) {
            $this->update([
                'status' => 'cancelled',
                'cancelled_by' => $cancelledBy,
                'cancelled_reason' => $reason,
                'cancelled_at' => now(),
            ]);

            // Create refund expense if transaction was completed with cash
            if ($this->isCompleted() && $this->isCash() && $this->shift_id) {
                ShiftExpense::create([
                    'shift_id' => $this->shift_id,
                    'order_id' => $this->id,
                    'description' => "Refund: {$this->invoice_number}",
                    'amount' => $this->total,
                    'category' => 'refund',
                ]);
            }

            return true;
        });
    }

    public function getFormattedTotalAttribute(): string
    {
        return 'Rp ' . number_format($this->total, 0, ',', '.');
    }

    public function getQueueDisplayAttribute(): string
    {
        return str_pad($this->queue_number, 3, '0', STR_PAD_LEFT);
    }
}
