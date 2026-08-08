<?php

namespace App\Models;

use App\Enums\SelfOrderStatus;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SelfOrder extends Model
{
    protected $fillable = [
        'order_token',
        'queue_number',
        'invoice_number',
        'customer_name',
        'customer_phone',
        'customer_email',
        'pickup_name',
        'pickup_phone',
        'subtotal',
        'tax_amount',
        'total',
        'order_type',
        'service_area_id',
        'pager_id',
        'notes',
        'payment_method',
        'status',
        'payment_transaction_id',
        'transaction_id',
        'shift_id',
        'processed_by',
        'cancelled_by',
        'cancelled_reason',
        'cancelled_at',
        'paid_at',
        'claimed_at',
        'processing_at',
        'completed_at',
        'pickup_confirmed_at',
        'idempotency_key',
        'customer_ip',
    ];

    protected $casts = [
        'subtotal'            => 'decimal:2',
        'tax_amount'          => 'decimal:2',
        'total'               => 'decimal:2',
        'status'              => SelfOrderStatus::class,
        'cancelled_at'        => 'datetime',
        'paid_at'             => 'datetime',
        'claimed_at'          => 'datetime',
        'processing_at'       => 'datetime',
        'completed_at'        => 'datetime',
        'pickup_confirmed_at' => 'datetime',
        'queue_number'        => 'integer',
        'service_area_id'     => 'integer',
        'pager_id'            => 'integer',
    ];

    // -----------------------------------------------------------------
    // Boot
    // -----------------------------------------------------------------

    protected static function boot(): void
    {
        parent::boot();

        // Auto-generate order_token saat create jika belum ada
        static::creating(function (SelfOrder $order) {
            if (empty($order->order_token)) {
                $order->order_token = (string) Str::uuid();
            }
        });
    }

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    public function items(): HasMany
    {
        return $this->hasMany(SelfOrderItem::class);
    }

    public function serviceArea(): BelongsTo
    {
        return $this->belongsTo(ServiceArea::class);
    }

    public function pager(): BelongsTo
    {
        return $this->belongsTo(Pager::class);
    }

    public function paymentTransaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by')->withTrashed();
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by')->withTrashed();
    }

    public function cartSnapshot(): HasOne
    {
        return $this->hasOne(SelfOrderCartSnapshot::class);
    }

    /**
     * Stock reservations yang dimiliki order ini.
     */
    public function stockReservations(): MorphMany
    {
        return $this->morphMany(StockReservation::class, 'reservable');
    }

    // -----------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------

    public function scopePendingPayment($query)
    {
        return $query->where('status', SelfOrderStatus::PendingPayment->value);
    }

    public function scopeWaitingPayment($query)
    {
        return $query->where('status', SelfOrderStatus::WaitingPayment->value);
    }

    public function scopePaid($query)
    {
        return $query->where('status', SelfOrderStatus::Paid->value);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [
            SelfOrderStatus::Completed->value,
            SelfOrderStatus::Cancelled->value,
            SelfOrderStatus::Expired->value,
        ]);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Filter untuk tab dashboard kasir "Sudah Dibayar" (termasuk diproses & siap).
     *
     * HANYA menampilkan order yang BELUM diambil kasir manapun (processed_by NULL) — "kolam" pool
     * yang bisa diambil siapa saja. Begitu diklik "Ambil Pesanan", order pindah ke scopeForClaimedTab()
     * agar tidak ambigu (tidak nyempil lagi di tab ini sambil juga muncul di tab lain).
     *
     * Sengaja TIDAK dibatasi tanggal (whereDate created_at) — status Paid/Processing/Ready
     * sudah dengan sendirinya berarti "belum selesai", jadi order yang belum sempat diproses
     * kasir sebelum lewat tengah malam tetap harus tampil, bukan hilang begitu saja.
     *
     * Diurutkan FIFO (terlama dulu) agar antrian tidak ada yang terlewat/lupa.
     *
     * @param  int|null  $userId  Tidak lagi memengaruhi hasil query (pool selalu unclaimed untuk semua
     *                            kasir) — parameter dipertahankan demi kompatibilitas pemanggil lama.
     */
    public function scopeForPaidTab($query, ?int $userId = null)
    {
        return $query->whereIn('status', [
            SelfOrderStatus::Paid->value,
            SelfOrderStatus::Processing->value,
            SelfOrderStatus::Ready->value,
        ])
            ->whereNull('processed_by')
            ->oldest();
    }

    /**
     * Filter untuk tab dashboard kasir "Bayar di Tempat".
     *
     * HANYA menampilkan order yang BELUM diambil kasir manapun — lihat catatan di scopeForPaidTab().
     *
     * Sengaja TIDAK dibatasi tanggal — status WaitingPayment sudah otomatis dibersihkan oleh
     * CheckExpiredSelfOrderReservationsJob setelah 30 menit, jadi tidak perlu filter tanggal lagi.
     *
     * Diurutkan FIFO (terlama dulu) agar antrian tidak ada yang terlewat/lupa.
     */
    public function scopeForWaitingTab($query, ?int $userId = null)
    {
        return $query->where('status', SelfOrderStatus::WaitingPayment->value)
            ->where('payment_method', 'cash_on_counter')
            ->whereNull('processed_by')
            ->oldest();
    }

    /**
     * Filter untuk tab dashboard kasir "Pesanan Diambil" — order aktif (belum Completed/Cancelled/
     * Expired) yang SUDAH diambil kasir manapun, baik QRIS maupun Bayar di Tempat, tanpa dipisah lagi
     * berdasarkan metode pembayaran. Tab ini menjawab "sedang dikerjakan siapa", mengurangi ambiguitas
     * dibanding sebelumnya (order ter-claim ikut nyempil di tab Sudah Dibayar/Bayar di Tempat).
     *
     * @param  int|null     $userId        Jika diisi, hanya tampilkan order yang di-claim kasir tsb.
     * @param  string|null  $statusFilter  Jika diisi, filter berdasarkan nilai status enum (misal: 'paid', 'processing', 'ready').
     */
    public function scopeForClaimedTab($query, ?int $userId = null, ?string $statusFilter = null)
    {
        return $query->active()
            ->whereNotNull('processed_by')
            ->when($userId, fn($q) => $q->where('processed_by', $userId))
            ->when($statusFilter, fn($q) => $q->where('status', $statusFilter))
            ->oldest();
    }

    // -----------------------------------------------------------------
    // Status Helpers
    // -----------------------------------------------------------------

    public function isPendingPayment(): bool
    {
        return $this->status === SelfOrderStatus::PendingPayment;
    }

    public function isWaitingPayment(): bool
    {
        return $this->status === SelfOrderStatus::WaitingPayment;
    }

    public function isPaid(): bool
    {
        return $this->status === SelfOrderStatus::Paid;
    }

    public function isProcessing(): bool
    {
        return $this->status === SelfOrderStatus::Processing;
    }

    public function isReady(): bool
    {
        return $this->status === SelfOrderStatus::Ready;
    }

    public function isCompleted(): bool
    {
        return $this->status === SelfOrderStatus::Completed;
    }

    public function isCancelled(): bool
    {
        return $this->status === SelfOrderStatus::Cancelled;
    }

    public function isExpired(): bool
    {
        return $this->status === SelfOrderStatus::Expired;
    }

    public function isCancellable(): bool
    {
        return $this->status->isCancellable();
    }

    public function isClaimed(): bool
    {
        return $this->processed_by !== null;
    }

    /**
     * Transisi status dengan validasi state machine.
     *
     * @throws \DomainException
     */
    public function transitionTo(
        SelfOrderStatus $newStatus,
        ?string $triggeredBy = null,
        ?int $actorId = null
    ): void {
        $from = $this->status;

        if (!$from->canTransitionTo($newStatus)) {
            throw new \DomainException(
                "SelfOrder [{$this->id}] tidak bisa berpindah dari [{$from->label()}] ke [{$newStatus->label()}]."
            );
        }

        $updates = ['status' => $newStatus->value];

        match ($newStatus) {
            SelfOrderStatus::Paid        => $updates['paid_at'] = now(),
            SelfOrderStatus::Processing  => $updates['processing_at'] = now(),
            SelfOrderStatus::Completed   => $updates['completed_at'] = now(),
            default                      => null,
        };

        $this->update($updates);
    }

    // -----------------------------------------------------------------
    // Business Logic
    // -----------------------------------------------------------------

    /**
     * Kasir "mengambil" pesanan Self Order → menjadi owner.
     * Order yang sudah di-claim tidak bisa diambil kasir lain.
     *
     * @throws \DomainException
     */
    public function claimBy(int $userId, ?int $shiftId): void
    {
        if ($this->processed_by !== null && $this->processed_by !== $userId) {
            $ownerName = $this->processedBy?->name ?? "kasir lain";
            throw new \DomainException("Order #{$this->queue_display} sudah diambil oleh {$ownerName}.");
        }

        $this->update([
            'processed_by' => $userId,
            'shift_id'     => $shiftId,
            'claimed_at'   => $this->claimed_at ?? now(),
        ]);
    }

    /**
     * Ambil SelfOrder berdasarkan token (untuk customer tracking — aman dari IDOR).
     */
    public static function findByToken(string $token): ?self
    {
        return static::where('order_token', $token)
            ->with(['items.modifiers', 'paymentTransaction'])
            ->first();
    }

    /**
     * Ambil SelfOrder berdasarkan token dengan lock untuk update (dalam transaksi DB).
     */
    public static function findByTokenForUpdate(string $token): ?self
    {
        return static::where('order_token', $token)
            ->lockForUpdate()
            ->first();
    }

    /**
     * Cek apakah idempotency key sudah pernah digunakan.
     */
    public static function existsByIdempotencyKey(string $key): ?self
    {
        return static::where('idempotency_key', $key)->first();
    }

    /**
     * Hitung jumlah order pending per nomor HP (anti-spam).
     */
    public static function countActivePendingByPhone(string $phone): int
    {
        return static::where('customer_phone', $phone)
            ->whereIn('status', [
                SelfOrderStatus::PendingPayment->value,
                SelfOrderStatus::WaitingPayment->value,
            ])
            ->count();
    }

    /**
     * Paginated list untuk tab "Sudah Dibayar" di dashboard kasir (eager load items.modifiers & transaction).
     */
    public static function getPaginatedPaid(int $perPage = 20, ?int $userId = null): LengthAwarePaginator
    {
        return static::with(['items.modifiers', 'paymentTransaction', 'processedBy', 'transaction', 'serviceArea'])
            ->forPaidTab($userId)
            ->paginate($perPage);
    }

    /**
     * Paginated list untuk tab "Bayar di Tempat" di dashboard kasir (eager load items.modifiers & processedBy).
     */
    public static function getPaginatedWaiting(int $perPage = 20, ?int $userId = null): LengthAwarePaginator
    {
        return static::with(['items.modifiers', 'processedBy', 'paymentTransaction', 'serviceArea'])
            ->forWaitingTab($userId)
            ->paginate($perPage);
    }

    /**
     * Count untuk badge tab "Sudah Dibayar" (Paid, Processing, Ready).
     */
    public static function countPaidToday(?int $userId = null): int
    {
        return static::forPaidTab($userId)->count();
    }

    /**
     * Count untuk badge tab "Bayar di Tempat".
     */
    public static function countWaitingToday(?int $userId = null): int
    {
        return static::forWaitingTab($userId)->count();
    }

    /**
     * Paginated list untuk tab "Pesanan Diambil" (order aktif yang sudah di-claim, lintas QRIS/Cash).
     *
     * @param  int     $perPage
     * @param  int|null  $userId        Filter berdasarkan kasir yang mengambil. NULL = semua kasir.
     * @param  string|null  $statusFilter  Filter status (nilai enum: 'paid','processing','ready'). NULL = semua.
     */
    public static function getPaginatedClaimed(
        int $perPage = 20,
        ?int $userId = null,
        ?string $statusFilter = null
    ): LengthAwarePaginator {
        return static::with(['items.modifiers', 'paymentTransaction', 'processedBy', 'transaction', 'shift', 'serviceArea'])
            ->forClaimedTab($userId, $statusFilter)
            ->paginate($perPage);
    }

    /**
     * Count untuk badge tab "Pesanan Diambil" (total, tanpa filter status).
     */
    public static function countClaimedToday(?int $userId = null): int
    {
        return static::forClaimedTab($userId)->count();
    }

    /**
     * Count order di tab Diambil, dikelompokkan per status — SATU query GROUP BY.
     * Digunakan untuk badge count pada sub-filter status di tab Diambil.
     * Menghindari 4 query COUNT(*) terpisah per status.
     *
     * @return array<string, int>  Key = status value string, Value = jumlah order.
     */
    public static function countClaimedByStatusGroup(?int $userId = null): array
    {
        return static::forClaimedTab($userId)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn($v) => (int) $v)
            ->toArray();
    }

    /**
     * Ambil self order dengan full detail untuk print struk.
     */
    public static function getForPrint(int $id): ?self
    {
        return static::with(['items.modifiers', 'processedBy', 'paymentTransaction', 'transaction', 'serviceArea'])
            ->find($id);
    }

    /**
     * Ambil self orders yang expire (untuk Job cleanup).
     * QRIS: sudah melewati expired_at dari payment_transaction.
     * Cash: sudah lebih dari 30 menit tanpa dikonfirmasi.
     */
    public static function getExpiredPending(): Collection
    {
        $cashTimeout = now()->subMinutes(
            (int) config('self_order.cash_reservation_timeout_minutes', 30)
        );

        return static::where(function ($q) use ($cashTimeout) {
            // QRIS expired
            $q->where('status', SelfOrderStatus::PendingPayment->value)
              ->whereHas('paymentTransaction', fn($q) => $q->where('expired_at', '<', now()));
        })->orWhere(function ($q) use ($cashTimeout) {
            // Cash on counter timeout
            $q->where('status', SelfOrderStatus::WaitingPayment->value)
              ->where('payment_method', 'cash_on_counter')
              ->where('created_at', '<', $cashTimeout);
        })->with(['stockReservations', 'paymentTransaction'])->get();
    }

    // -----------------------------------------------------------------
    // Accessors
    // -----------------------------------------------------------------

    public function getQueueDisplayAttribute(): string
    {
        return $this->queue_number
            ? str_pad($this->queue_number, 3, '0', STR_PAD_LEFT)
            : '---';
    }

    public function getFormattedTotalAttribute(): string
    {
        return 'Rp ' . number_format($this->total, 0, ',', '.');
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'qris'            => 'QRIS',
            'cash_on_counter' => 'Bayar di Kasir',
            default           => $this->payment_method,
        };
    }

    public function getOrderTypeLabelAttribute(): string
    {
        return match ($this->order_type) {
            'dine_in'   => 'Makan di Tempat',
            'take_away' => 'Bawa Pulang',
            default     => $this->order_type ?? '-',
        };
    }

    /**
     * Catat data konfirmasi pengambil pesanan (Take Away).
     */
    public function recordPickupConfirmation(?string $name, ?string $phone): void
    {
        $this->update([
            'pickup_name'         => $name ?: $this->customer_name,
            'pickup_phone'        => $phone ?: $this->customer_phone,
            'pickup_confirmed_at' => now(),
        ]);
    }

    public function getPickupDisplayNameAttribute(): string
    {
        return $this->pickup_name ?: $this->customer_name;
    }

    public function getPickupDisplayPhoneAttribute(): string
    {
        return $this->pickup_phone ?: $this->customer_phone;
    }
}
