<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'module',
        'action',
    ];

    /**
     * Get roles with this permission.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'permission_role');
    }

    /**
     * Scope for a specific module.
     */
    public function scopeModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Scope for a specific action.
     */
    public function scopeAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Create a permission name from module and action.
     */
    public static function createName(string $module, string $action): string
    {
        return "{$module}.{$action}";
    }

    /**
     * Get all permissions for a module.
     */
    public static function getModulePermissions(string $module): array
    {
        return self::where('module', $module)->pluck('name')->toArray();
    }
}
