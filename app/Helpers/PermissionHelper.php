<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;

class PermissionHelper
{
    public static function hasPermission(string $permission, ?string $guard = null): bool
    {
        $guard = $guard ?? self::getCurrentGuard();
        $user = Auth::guard($guard)->user();
        return $user && $user->can($permission);
    }

    public static function hasAnyPermission(array $permissions, ?string $guard = null): bool
    {
        $guard = $guard ?? self::getCurrentGuard();
        $user = Auth::guard($guard)->user();

        if (!$user) return false;

        foreach ($permissions as $permission) {
            if ($user->can($permission)) return true;
        }

        return false;
    }

    public static function hasAllPermissions(array $permissions, ?string $guard = null): bool
    {
        $guard = $guard ?? self::getCurrentGuard();
        $user = Auth::guard($guard)->user();

        if (!$user) return false;

        foreach ($permissions as $permission) {
            if (!$user->can($permission)) return false;
        }

        return true;
    }

    public static function hasRole(string $role, ?string $guard = null): bool
    {
        $guard = $guard ?? self::getCurrentGuard();
        $user = Auth::guard($guard)->user();
        return $user && $user->hasRole($role);
    }

    public static function hasAnyRole(array $roles, ?string $guard = null): bool
    {
        $guard = $guard ?? self::getCurrentGuard();
        $user = Auth::guard($guard)->user();

        if (!$user) return false;

        foreach ($roles as $role) {
            if ($user->hasRole($role)) return true;
        }

        return false;
    }

    public static function isSuperAdmin(?string $guard = null): bool
    {
        return self::hasRole('super_admin', $guard);
    }

    public static function getUserPermissions(?string $guard = null): array
    {
        $guard = $guard ?? self::getCurrentGuard();
        $user = Auth::guard($guard)->user();
        return $user ? $user->getAllPermissions()->pluck('name')->toArray() : [];
    }

    public static function getUserRoles(?string $guard = null): array
    {
        $guard = $guard ?? self::getCurrentGuard();
        $user = Auth::guard($guard)->user();
        return $user ? $user->getRoleNames()->toArray() : [];
    }

    public static function canAccessResource($resource, string $permission, string $ownerField = 'user_id'): bool
    {
        $user = Auth::user();

        if (!$user) return false;

        // Check ownership
        if ($resource->{$ownerField} == $user->id) return true;

        // Check permission
        return $user->can($permission);
    }

    private static function getCurrentGuard(): string
    {
        if (Auth::guard('admin')->check()) return 'admin';
        if (Auth::guard('vendor')->check()) return 'vendor';
        return 'user';
    }
}
