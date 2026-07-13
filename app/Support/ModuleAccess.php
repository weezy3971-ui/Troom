<?php

namespace App\Support;

use App\Models\User;

/**
 * Single source of truth for module-level access, derived from the role
 * lines in the THE Module Specification. The `owner` role is a super-admin
 * and is always allowed (handled here and in CheckRole).
 *
 * Keys map to logical modules; values are the roles allowed to MANAGE (write)
 * that module. Master Data and Crop Cycles are readable by every role per the
 * spec, so their read routes are left open and only writes use these lists.
 */
class ModuleAccess
{
    public const MAP = [
        'master_data' => ['horticulture_manager', 'agronomist'],
        'crop_cycles' => ['horticulture_manager', 'agronomist', 'finance_officer', 'md'],
        'nursery'     => ['horticulture_manager', 'agronomist', 'farm_supervisor'],
        'daily_ops'   => ['horticulture_manager', 'agronomist', 'farm_supervisor'],
        'irrigation'  => ['horticulture_manager', 'farm_supervisor'],
        'fertigation' => ['horticulture_manager', 'agronomist', 'farm_supervisor'],
        'pest'        => ['horticulture_manager', 'agronomist', 'farm_supervisor'],
        'labour'      => ['horticulture_manager', 'farm_supervisor'],
        'inventory'   => ['horticulture_manager', 'storekeeper'],
        'harvest'     => ['horticulture_manager', 'farm_supervisor', 'packhouse_supervisor'],
        'packhouse'   => ['horticulture_manager', 'packhouse_supervisor'],
        'quality'     => ['horticulture_manager', 'quality_officer'],
        'sales'       => ['horticulture_manager', 'sales_officer'],
        'logistics'   => ['horticulture_manager', 'sales_officer', 'driver'],
        'finance'     => ['md', 'finance_officer'],
        'analytics'   => ['md', 'horticulture_manager'],
        // User administration & audit trail (owner always allowed via CheckRole).
        'admin'       => ['horticulture_manager'],
    ];

    /**
     * Roles allowed to manage the given module.
     *
     * @return array<int, string>
     */
    public static function for(string $module): array
    {
        return self::MAP[$module] ?? [];
    }

    /**
     * Comma-separated role list for use in a `role:` middleware string.
     */
    public static function middleware(string $module): string
    {
        return 'role:' . implode(',', self::for($module));
    }

    /**
     * Whether the given user may manage the module (owner is always allowed).
     */
    public static function allows(?User $user, string $module): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->role === 'owner') {
            return true;
        }

        return in_array($user->role, self::for($module), true);
    }
}
