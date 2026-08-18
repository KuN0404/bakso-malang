<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class Unit extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'symbol',
        'group',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function ingredients(): HasMany
    {
        return $this->hasMany(Ingredient::class);
    }

    public function components(): HasMany
    {
        return $this->hasMany(Component::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('group')->orderBy('sort_order')->orderBy('name');
    }

    public static function getMaxSortOrder(): int
    {
        return static::max('sort_order') ?? 0;
    }

    /**
     * Get paginated units, dengan pencarian nama/simbol.
     */
    public static function getPaginated(string $search = '', int $perPage = 10): LengthAwarePaginator
    {
        return static::query()
            ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('symbol', 'like', "%{$search}%");
            }))
            ->orderBy('group')
            ->orderBy('sort_order')
            ->paginate($perPage);
    }

    public static function existsById(int $id): bool
    {
        return static::where('id', $id)->exists();
    }

    /**
     * Satuan aktif, dikelompokkan per grup, untuk membangun <optgroup> di form
     * Ingredients/Components. Satu query saja, tidak N+1.
     */
    public static function getAllGroupedForSelect(): Collection
    {
        return static::active()->ordered()->get()->groupBy('group');
    }

    /**
     * 33 satuan standar (Berat/Volume/Satuan/Kemasan), di-upsert idempoten
     * berdasarkan symbol. Dipanggil oleh migrasi create_units_table dan oleh
     * UnitSeeder, supaya `migrate` saja maupun `migrate:fresh --seed` sama-sama
     * berakhir dengan 33 baris yang sama tanpa duplikat.
     */
    public static function seedDefaults(): void
    {
        $groups = [
            'Berat' => [
                ['symbol' => 'mg', 'name' => 'Mg (Miligram)'],
                ['symbol' => 'gram', 'name' => 'Gram'],
                ['symbol' => 'ons', 'name' => 'Ons'],
                ['symbol' => 'kg', 'name' => 'Kg (Kilogram)'],
                ['symbol' => 'ton', 'name' => 'Ton'],
            ],
            'Volume / Cairan' => [
                ['symbol' => 'ml', 'name' => 'Ml (Mililiter)'],
                ['symbol' => 'liter', 'name' => 'Liter'],
                ['symbol' => 'galon', 'name' => 'Galon'],
            ],
            'Satuan / Jumlah' => [
                ['symbol' => 'pcs', 'name' => 'Pcs / Biji'],
                ['symbol' => 'butir', 'name' => 'Butir'],
                ['symbol' => 'lembar', 'name' => 'Lembar'],
                ['symbol' => 'batang', 'name' => 'Batang'],
                ['symbol' => 'ruas', 'name' => 'Ruas'],
                ['symbol' => 'keping', 'name' => 'Keping'],
                ['symbol' => 'ikat', 'name' => 'Ikat'],
                ['symbol' => 'sisir', 'name' => 'Sisir'],
                ['symbol' => 'porsi', 'name' => 'Porsi'],
                ['symbol' => 'unit', 'name' => 'Unit'],
                ['symbol' => 'pasang', 'name' => 'Pasang'],
                ['symbol' => 'lusin', 'name' => 'Lusin'],
                ['symbol' => 'kodi', 'name' => 'Kodi'],
            ],
            'Kemasan' => [
                ['symbol' => 'pack', 'name' => 'Pack / Bungkusan'],
                ['symbol' => 'sachet', 'name' => 'Sachet'],
                ['symbol' => 'botol', 'name' => 'Botol'],
                ['symbol' => 'kaleng', 'name' => 'Kaleng'],
                ['symbol' => 'tube', 'name' => 'Tube'],
                ['symbol' => 'karung', 'name' => 'Karung'],
                ['symbol' => 'kantong', 'name' => 'Kantong'],
                ['symbol' => 'box', 'name' => 'Box'],
                ['symbol' => 'dus', 'name' => 'Dus / Karton'],
                ['symbol' => 'jerigen', 'name' => 'Jerigen'],
                ['symbol' => 'drum', 'name' => 'Drum'],
                ['symbol' => 'roll', 'name' => 'Roll'],
            ],
        ];

        foreach ($groups as $groupName => $units) {
            foreach ($units as $index => $unit) {
                static::firstOrCreate(
                    ['symbol' => $unit['symbol']],
                    [
                        'name' => $unit['name'],
                        'group' => $groupName,
                        'sort_order' => $index,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
