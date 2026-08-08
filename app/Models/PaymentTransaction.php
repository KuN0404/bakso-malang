<?php

namespace App\Models;

use App\Enums\PaymentTransactionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentTransaction extends Model
{
    protected $fillable = [
        'transaction_id',
        'self_order_id',
        'invoice_number',
        'midtrans_order_id',
        'qr_code_url',
        'payment_method',
        'amount',
        'status',
        'source',
        'midtrans_response',
        'webhook_received_at',
        'paid_at',
        'expired_at',
        'idempotency_key',
        'created_by',
    ];

    protected $casts = [
        'amount'               => 'decimal:2',
        'midtrans_response'    => 'array',
        'webhook_received_at'  => 'datetime',
        'paid_at'              => 'datetime',
        'expired_at'           => 'datetime',
        'status'               => PaymentTransactionStatus::class,
    ];

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(PaymentStatusLog::class)->orderBy('created_at');
    }

    public function selfOrder(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SelfOrder::class);
    }

    // -----------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------

    public function scopePending($query)
    {
        return $query->where('status', PaymentTransactionStatus::Pending->value);
    }

    public function scopeExpiredQris($query)
    {
        return $query
            ->where('status', PaymentTransactionStatus::Pending->value)
            ->where('expired_at', '<', now());
    }

    // -----------------------------------------------------------------
    // State Machine Helpers
    // -----------------------------------------------------------------

    /**
     * Transisi status dengan validasi state machine dan catat di log.
     *
     * @param  PaymentTransactionStatus  $newStatus
     * @param  string  $triggeredBy  cashier|webhook|system|job
     * @param  int|null  $actorId
     * @param  string|null  $note
     * @param  array|null  $metadata
     * @throws \DomainException
     */
    public function transitionTo(
        PaymentTransactionStatus $newStatus,
        string $triggeredBy,
        ?int $actorId = null,
        ?string $note = null,
        ?array $metadata = null
    ): void {
        $fromStatus = $this->status;

        if (!$fromStatus->canTransitionTo($newStatus)) {
            throw new \DomainException(
                "Tidak bisa mengubah status pembayaran dari [{$fromStatus->label()}] ke [{$newStatus->label()}]."
            );
        }

        $this->status = $newStatus;

        if ($newStatus === PaymentTransactionStatus::Paid) {
            $this->paid_at = now();
        }

        $this->save();

        // Catat ke audit log
        $this->statusLogs()->create([
            'from_status'  => $fromStatus->value,
            'to_status'    => $newStatus->value,
            'triggered_by' => $triggeredBy,
            'actor_id'     => $actorId,
            'note'         => $note,
            'metadata'     => $metadata,
        ]);
    }

    // -----------------------------------------------------------------
    // Status helpers
    // -----------------------------------------------------------------

    public function isPaid(): bool
    {
        return $this->status === PaymentTransactionStatus::Paid;
    }

    public function isPending(): bool
    {
        return $this->status === PaymentTransactionStatus::Pending;
    }

    public function isExpired(): bool
    {
        return $this->status === PaymentTransactionStatus::Expired;
    }

    public function isCancelled(): bool
    {
        return $this->status === PaymentTransactionStatus::Cancelled;
    }

    public function isFinal(): bool
    {
        return $this->status->isFinal();
    }

    public function isQrisExpiredByTime(): bool
    {
        return $this->expired_at && $this->expired_at->isPast();
    }

    // -----------------------------------------------------------------
    // Static Query Methods
    // -----------------------------------------------------------------

    /**
     * Cari PaymentTransaction pending berdasarkan midtrans_order_id.
     * Digunakan oleh webhook handler.
     */
    public static function findPendingByOrderId(string $orderId): ?self
    {
        return static::where('midtrans_order_id', $orderId)
            ->where('status', PaymentTransactionStatus::Pending->value)
            ->lockForUpdate()
            ->first();
    }

    /**
     * Cari semua QRIS yang sudah melewati waktu expired (untuk Job).
     */
    public static function getExpiredPendingQris(): \Illuminate\Database\Eloquent\Collection
    {
        return static::expiredQris()->get();
    }

    /**
     * Cari PaymentTransaction terbaru (pending/initiated) untuk suatu invoice.
     */
    public static function getActiveForInvoice(string $invoiceNumber): ?self
    {
        return static::where('invoice_number', $invoiceNumber)
            ->whereIn('status', [
                PaymentTransactionStatus::Initiated->value,
                PaymentTransactionStatus::Pending->value,
            ])
            ->latest()
            ->first();
    }
}
