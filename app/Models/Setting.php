<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'description',
    ];

    /**
     * Memoisasi in-memory per-request, di atas Cache::remember — beberapa
     * tempat (layout + komponen Livewire) bisa memanggil key yang sama dalam
     * satu request; ini menghindari itu memicu query DB berulang kalau cache
     * store-nya kosong/miss di kedua panggilan tersebut.
     */
    protected static array $memo = [];

    /**
     * Get a setting value by key.
     */
    public static function get(string $key, mixed $default = null, string $group = 'general'): mixed
    {
        $cacheKey = "setting.{$group}.{$key}";

        if (array_key_exists($cacheKey, static::$memo)) {
            return static::$memo[$cacheKey];
        }

        return static::$memo[$cacheKey] = Cache::remember($cacheKey, 3600, function () use ($group, $key, $default) {
            $setting = static::where('group', $group)
                ->where('key', $key)
                ->first();

            if (!$setting) {
                return $default;
            }

            return static::castValue($setting->value, $setting->type);
        });
    }

    /**
     * Set a setting value.
     */
    public static function set(string $key, mixed $value, string $group = 'general', string $type = 'string'): void
    {
        $storedValue = is_array($value) ? json_encode($value) : (string) $value;
        
        static::updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => $storedValue, 'type' => $type]
        );

        Cache::forget("setting.{$group}.{$key}");
        unset(static::$memo["setting.{$group}.{$key}"]);
    }

    /**
     * Cast value based on type.
     */
    protected static function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'float' => (float) $value,
            'json', 'array' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Get all settings in a group.
     */
    public static function getGroup(string $group): array
    {
        $settings = static::where('group', $group)->get();
        
        $result = [];
        foreach ($settings as $setting) {
            $result[$setting->key] = static::castValue($setting->value, $setting->type);
        }
        
        return $result;
    }

    /**
     * Clear settings cache.
     */
    public static function clearCache(?string $group = null, ?string $key = null): void
    {
        if ($group && $key) {
            Cache::forget("setting.{$group}.{$key}");
        } else {
            Cache::flush(); // Clear all cache (use with caution)
        }
    }
}
