<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    /**
     * Get all permissions sorted by name.
     */
    public static function getAllSorted(): \Illuminate\Database\Eloquent\Collection
    {
        return static::orderBy('name')->get();
    }
}
