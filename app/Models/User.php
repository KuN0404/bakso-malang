<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\SyncsToReport;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, LogsActivity, SyncsToReport;

    protected $fillable = [
        'username',
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email'])
            ->logOnlyDirty();
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function currentShift(): ?Shift
    {
        return $this->shifts()->where('status', 'open')->first();
    }

    public function hasOpenShift(): bool
    {
        return $this->shifts()->where('status', 'open')->exists();
    }

    /**
     * Open a new shift for this user.
     */
    public function openShift(float $openingCash = 0): Shift
    {
        // Close any existing open shift first
        $existingShift = $this->currentShift();
        if ($existingShift) {
            $existingShift->close($existingShift->calculateExpectedCash());
        }

        return $this->shifts()->create([
            'started_at'   => now(),
            'opening_cash' => $openingCash,
            'status'       => 'open',
        ]);
    }

    /**
     * Get paginated users list with roles.
     */
    public static function getPaginated(string $search = '', int $perPage = 10): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return static::with('roles')
            ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%")->orWhere('username', 'like', "%{$search}%"))
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Find user with roles for editing.
     */
    public static function getForEdit(int $id): self
    {
        return static::with('roles')->findOrFail($id);
    }

    /**
     * Get all users sorted by name.
     */
    public static function getAllSortedByName(): \Illuminate\Database\Eloquent\Collection
    {
        return static::orderBy('name')->get();
    }

    /**
     * Get users who have transactions (for cashier filter dropdown).
     */
    public static function getCashiersWithTransactions(): \Illuminate\Database\Eloquent\Collection
    {
        return static::whereHas('transactions')->orderBy('name')->get(['id', 'name']);
    }
}

