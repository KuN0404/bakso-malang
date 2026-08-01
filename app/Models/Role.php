<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    /**
     * Get roles excluding Super Admin with permissions and user count.
     */
    public static function getRolesForAdmin(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('name', '!=', 'Super Admin')
            ->with('permissions')
            ->withCount('users')
            ->get();
    }

    /**
     * Get all roles except Super Admin for user assignment.
     */
    public static function getAssignableRoles(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('name', '!=', 'Super Admin')->get();
    }

    /**
     * Get role by ID with its permissions eager-loaded.
     */
    public static function getWithPermissions(int $id): self
    {
        return static::with('permissions')->findOrFail($id);
    }
}
