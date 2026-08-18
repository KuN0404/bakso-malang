<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\SyncsToReport;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, LogsActivity, SyncsToReport, SoftDeletes;

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

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function currentShift(): ?Shift
    {
        return $this->shifts()->where('status', 'open')->first();
    }

    public function hasOpenShift(): bool
    {
        return $this->shifts()->where('status', 'open')->exists();
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('Super Admin');
    }

    /**
     * Non-aktif = soft deleted. Digunakan sebagai satu-satunya sumber kebenaran
     * status user, tanpa kolom is_active terpisah.
     */
    public function isActive(): bool
    {
        return $this->deleted_at === null;
    }

    /**
     * Nonaktifkan user (soft delete) dan paksa keluar dari semua sesi yang
     * sedang aktif agar tidak bisa lanjut memakai sistem meski masih login.
     */
    public function deactivate(): void
    {
        DB::transaction(function () {
            $this->delete();
            DB::table('sessions')->where('user_id', $this->id)->delete();
        });
    }

    /**
     * Aktifkan kembali user yang sebelumnya dinonaktifkan.
     */
    public function activate(): void
    {
        $this->restore();
    }

    /**
     * Cek apakah username/email user ini sudah dipakai user aktif lain,
     * yang berarti user ini TIDAK BISA diaktifkan kembali sebelum ada
     * penyesuaian data (mencegah dua user aktif dengan username/email sama).
     */
    public function hasActiveUsernameOrEmailConflict(): bool
    {
        return static::where('id', '!=', $this->id)
            ->where(fn ($q) => $q->where('username', $this->username)->orWhere('email', $this->email))
            ->exists();
    }

    /**
     * Check if a Super Admin account already exists in the system.
     */
    public static function hasSuperAdmin(): bool
    {
        return static::role('Super Admin')->exists();
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
     *
     * @param string $status 'active' (default, hanya user aktif), 'inactive' (hanya nonaktif), atau 'all'.
     */
    public static function getPaginated(string $search = '', string $status = 'active', int $perPage = 10): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return static::query()
            ->when($status === 'inactive', fn($q) => $q->onlyTrashed())
            ->when($status === 'all', fn($q) => $q->withTrashed())
            // status === 'active' tidak perlu apa-apa: default query Eloquent sudah
            // mengecualikan baris yang di-soft-delete.
            ->with(['roles', 'profile'])
            ->when($search, fn($q) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")->orWhere('username', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Find user (including nonaktif) with roles for editing.
     */
    public static function getForEdit(int $id): self
    {
        return static::withTrashed()->with(['roles', 'profile'])->findOrFail($id);
    }

    /**
     * Get all users sorted by name.
     */
    public static function getAllSortedByName(): \Illuminate\Database\Eloquent\Collection
    {
        return static::withTrashed()->orderBy('name')->get();
    }

    /**
     * Get users who have transactions (for cashier filter dropdown).
     * Includes nonaktif users so historical report filters keep working.
     */
    public static function getCashiersWithTransactions(): \Illuminate\Database\Eloquent\Collection
    {
        return static::withTrashed()->whereHas('transactions')->orderBy('name')->get(['id', 'name']);
    }
}

