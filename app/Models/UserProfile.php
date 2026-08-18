<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    protected $fillable = [
        'user_id',
        'phone',
        'birth_date',
        'address',
        'gender',
        'notes',
        'photo',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Usia dihitung dari birth_date (tanggal hari ini - tanggal lahir),
     * bukan kolom tersimpan.
     */
    protected function age(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->birth_date?->age,
        );
    }
}
