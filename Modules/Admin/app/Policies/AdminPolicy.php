<?php

namespace Modules\Admin\Policies;

use Modules\Admin\Models\Admin;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Helpers\PermissionHelper;

class AdminPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any admins.
     */
    public function viewAny($user): bool
    {
        // Only admins can view admin users (security)
        if ($user instanceof Admin) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the admin.
     */
    public function view($user, Admin $admin): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can view any admin
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_admin', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create admins.
     */
    public function create($user): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Only admins can create admin accounts
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_admin', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the admin.
     */
    public function update($user, Admin $admin): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can update any admin
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_admin', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the admin.
     */
    public function delete($user, Admin $admin): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Only admins can delete admin accounts
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_admin', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the admin.
     */
    public function restore($user, Admin $admin): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as update
        return $this->update($user, $admin);
    }

    /**
     * Determine whether the user can permanently delete the admin.
     */
    public function forceDelete($user, Admin $admin): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as delete
        return $this->delete($user, $admin);
    }

    /**
     * Determine whether the user can manage admin roles.
     */
    public function manageRoles($user, Admin $admin): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Only admins can manage admin roles
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_admin', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage admin permissions.
     */
    public function managePermissions($user, Admin $admin): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Only admins can manage admin permissions
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_admin', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can reset admin passwords.
     */
    public function resetPassword($user, Admin $admin): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can reset any admin password
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_admin', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can export admin data.
     */
    public function export($user): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can export admin data
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_admin', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can import admin data.
     */
    public function import($user): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Only admins can import admin data
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_admin', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk update admins.
     */
    public function bulkUpdate($user): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Only admins can bulk update admin accounts
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_admin', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk delete admins.
     */
    public function bulkDelete($user): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Only admins can bulk delete admin accounts
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_admin', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage admin sessions.
     */
    public function manageSessions($user, Admin $admin): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can manage any admin sessions
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_admin', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view admin audit logs.
     */
    public function viewAuditLogs($user, Admin $admin): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can view admin audit logs
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_admin', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage admin notifications.
     */
    public function manageNotifications($user, Admin $admin): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can manage admin notifications
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_admin', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can duplicate admin accounts.
     */
    public function duplicate($user, Admin $admin): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Only admins can duplicate admin accounts
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_admin', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can impersonate admins.
     */
    public function impersonate($user, Admin $admin): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Only admins can impersonate other admins (security risk)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_admin', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can suspend admin accounts.
     */
    public function suspend($user, Admin $admin): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Only admins can suspend admin accounts
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_admin', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can reactivate admin accounts.
     */
    public function reactivate($user, Admin $admin): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as suspend
        return $this->suspend($user, $admin);
    }
}
