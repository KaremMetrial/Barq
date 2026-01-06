<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

trait PermissionValidationTrait
{
    /**
     * Validate single permission
     */
    protected function validatePermission(string $permission, ?string $guard = null): ?JsonResponse
    {
        $guard = $guard ?? $this->getCurrentGuard();
        $user = Auth::guard($guard)->user();

        if (!$user) {
            return $this->permissionError('Authentication required.', 'AUTH_REQUIRED', 401);
        }

        if (!$user->can($permission)) {
            return $this->permissionError(
                'You do not have permission to perform this action.',
                'INSUFFICIENT_PERMISSIONS',
                403,
                ['required_permission' => $permission]
            );
        }

        return null;
    }

    /**
     * Validate any of the permissions
     */
    protected function validateAnyPermission(array $permissions, ?string $guard = null): ?JsonResponse
    {
        $guard = $guard ?? $this->getCurrentGuard();
        $user = Auth::guard($guard)->user();

        if (!$user) {
            return $this->permissionError('Authentication required.', 'AUTH_REQUIRED', 401);
        }

        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return null;
            }
        }

        return $this->permissionError(
            'You do not have the required permissions.',
            'INSUFFICIENT_PERMISSIONS',
            403,
            ['required_permissions' => $permissions]
        );
    }

    /**
     * Validate all permissions
     */
    protected function validateAllPermissions(array $permissions, ?string $guard = null): ?JsonResponse
    {
        $guard = $guard ?? $this->getCurrentGuard();
        $user = Auth::guard($guard)->user();

        if (!$user) {
            return $this->permissionError('Authentication required.', 'AUTH_REQUIRED', 401);
        }

        $missingPermissions = [];
        foreach ($permissions as $permission) {
            if (!$user->can($permission)) {
                $missingPermissions[] = $permission;
            }
        }

        if (!empty($missingPermissions)) {
            return $this->permissionError(
                'Missing required permissions.',
                'INSUFFICIENT_PERMISSIONS',
                403,
                ['missing_permissions' => $missingPermissions]
            );
        }

        return null;
    }

    /**
     * Validate role
     */
    protected function validateRole(string $role, ?string $guard = null): ?JsonResponse
    {
        $guard = $guard ?? $this->getCurrentGuard();
        $user = Auth::guard($guard)->user();

        if (!$user) {
            return $this->permissionError('Authentication required.', 'AUTH_REQUIRED', 401);
        }

        if (!$user->hasRole($role)) {
            return $this->permissionError(
                'Insufficient role permissions.',
                'INSUFFICIENT_ROLE',
                403,
                ['required_role' => $role]
            );
        }

        return null;
    }

    /**
     * Check ownership or admin access
     */
    protected function validateOwnershipOrPermission($resource, string $permission, string $ownerField = 'user_id', ?string $guard = null): ?JsonResponse
    {
        $guard = $guard ?? $this->getCurrentGuard();
        $user = Auth::guard($guard)->user();

        if (!$user) {
            return $this->permissionError('Authentication required.', 'AUTH_REQUIRED', 401);
        }

        // Check if user owns the resource
        if ($resource->{$ownerField} == $user->id) {
            return null;
        }

        // Check if user has admin permission
        if ($user->can($permission)) {
            return null;
        }

        return $this->permissionError(
            'You can only access your own resources.',
            'OWNERSHIP_REQUIRED',
            403
        );
    }

    private function getCurrentGuard(): string
    {
        if (Auth::guard('admin')->check()) return 'admin';
        if (Auth::guard('vendor')->check()) return 'vendor';
        return 'user';
    }

    private function permissionError(string $message, string $errorCode, int $statusCode, array $data = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => $errorCode,
            'data' => $data
        ], $statusCode);
    }
}
