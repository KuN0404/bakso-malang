<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Riwayat setiap percobaan kirim WhatsApp (struk digital), termasuk yang
 * DIBATALKAN karena nomor tujuan sedang diblokir — supaya admin punya bukti
 * nyata di UI bahwa nomor blacklist memang tidak pernah dikirimi WA.
 */
class WhatsappMessageLog extends Model
{
    protected $fillable = [
        'transaction_id',
        'phone',
        'message',
        'status',
        'reason',
        'fonnte_message_id',
        'fonnte_status',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public static function logSent(?int $transactionId, string $phone, string $message, ?string $fonnteMessageId): self
    {
        return static::create([
            'transaction_id'     => $transactionId,
            'phone'              => $phone,
            'message'            => $message,
            'status'             => 'sent',
            'fonnte_message_id'  => $fonnteMessageId,
        ]);
    }

    public static function logFailed(?int $transactionId, string $phone, string $message, ?string $reason): self
    {
        return static::create([
            'transaction_id' => $transactionId,
            'phone'          => $phone,
            'message'        => $message,
            'status'         => 'failed',
            'reason'         => $reason,
        ]);
    }

    public static function logBlocked(?int $transactionId, string $phone, string $message): self
    {
        return static::create([
            'transaction_id' => $transactionId,
            'phone'          => $phone,
            'message'        => $message,
            'status'         => 'blocked',
            'reason'         => 'Nomor sedang diblokir di modul blacklist pelanggan',
        ]);
    }

    /**
     * Dipanggil dari webhook Fonnte message-status untuk mengorelasikan status
     * pengiriman (delivered/read/dst) ke log yang sudah tercatat saat dikirim.
     */
    public static function updateDeliveryStatusByFonnteId(?string $fonnteMessageId, ?string $status): void
    {
        if (!$fonnteMessageId || !$status) {
            return;
        }

        static::where('fonnte_message_id', $fonnteMessageId)->update(['fonnte_status' => $status]);
    }

    public static function getPaginated(string $search = '', string $status = '', int $perPage = 20): LengthAwarePaginator
    {
        return static::query()
            ->when($search, fn ($q) => $q->where('phone', 'like', "%{$search}%"))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate($perPage);
    }
}
