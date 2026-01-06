<?php

namespace Modules\Role\Policies;

use Modules\Role\Models\Role;
use Modules\Admin\Models\Admin;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Helpers\PermissionHelper;

class RolePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any roles.
     */
    public function viewAny($user): bool
    {
        // Only admins can view roles (system security)
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_role', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the role.
     */
    public function view($user, Role $role): bool
    {
        // Only admins can view roles
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_role', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create roles.
     */
    public function create($user): bool
    {
        // Only admins can create roles (system security impact)
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_role', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the role.
     */
    public function update($user, Role $role): bool
    {
        // Only admins can update roles (security implications)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_role', 'admin')) {
            // Prevent modification of super admin roles
            if ($this->isSuperAdminRole($role)) {
                return false;
            }
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the role.
     */
    public function delete($user, Role $role): bool
    {
        // Only admins can delete roles (critical system operation)
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_role', 'admin')) {
            // Prevent deletion of essential roles
            if ($this->isEssentialRole($role)) {
                return false;
            }
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the role.
     */
    public function restore($user, Role $role): bool
    {
        // Same logic as update
        return $this->update($user, $role);
    }

    /**
     * Determine whether the user can permanently delete the role.
     */
    public function forceDelete($user, Role $role): bool
    {
        // Same logic as delete
        return $this->delete($user, $role);
    }

    /**
     * Determine whether the user can assign permissions to the role.
     */
    public function assignPermissions($user, Role $role): bool
    {
        // Only admins can assign permissions (security critical)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_role', 'admin')) {
            // Prevent permission changes to super admin roles
            if ($this->isSuperAdminRole($role)) {
                return false;
            }
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can revoke permissions from the role.
     */
    public function revokePermissions($user, Role $role): bool
    {
        // Same logic as assign permissions
        return $this->assignPermissions($user, $role);
    }

    /**
     * Determine whether the user can view role permissions.
     */
    public function viewPermissions($user, Role $role): bool
    {
        // Admins can view role permissions
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_role', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can assign users to the role.
     */
    public function assignUsers($user, Role $role): bool
    {
        // Only admins can assign users to roles
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_role', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can remove users from the role.
     */
    public function removeUsers($user, Role $role): bool
    {
        // Same logic as assign users
        return $this->assignUsers($user, $role);
    }

    /**
     * Determine whether the user can view role users.
     */
    public function viewUsers($user, Role $role): bool
    {
        // Admins can view role users
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_role', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can duplicate roles.
     */
    public function duplicate($user, Role $role): bool
    {
        // Same logic as create
        return $this->create($user);
    }

    /**
     * Determine whether the user can export role data.
     */
    public function export($user): bool
    {
        // Admins can export role data
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_role', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can import role data.
     */
    public function import($user): bool
    {
        // Only admins can import roles
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_role', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk update roles.
     */
    public function bulkUpdate($user): bool
    {
        // Only admins can bulk update roles
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_role', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk delete roles.
     */
    public function bulkDelete($user): bool
    {
        // Only admins can bulk delete roles
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_role', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage role guards.
     */
    public function manageGuards($user, Role $role): bool
    {
        // Only admins can manage role guards (system security)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_role', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view role analytics.
     */
    public function viewAnalytics($user): bool
    {
        // Admins can view role analytics
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can clone role permissions.
     */
    public function clonePermissions($user, Role $role): bool
    {
        // Same logic as create
        return $this->create($user);
    }

    /**
     * Determine whether the user can manage role hierarchy.
     */
    public function manageHierarchy($user): bool
    {
        // Only admins can manage role hierarchy (organizational structure)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_role', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can archive roles.
     */
    public function archive($user, Role $role): bool
    {
        // Only admins can archive roles
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_role', 'admin')) {
            // Cannot archive essential roles
            if ($this->isEssentialRole($role)) {
                return false;
            }
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can unarchive roles.
     */
    public function unarchive($user, Role $role): bool
    {
        // Same logic as archive
        return $this->archive($user, $role);
    }

    /**
     * Determine whether the user can view role audit logs.
     */
    public function viewAuditLogs($user, Role $role): bool
    {
        // Admins can view role audit logs
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_role', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage role templates.
     */
    public function manageTemplates($user): bool
    {
        // Only admins can manage role templates
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_role', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can apply role templates.
     */
    public function applyTemplates($user): bool
    {
        // Same logic as create
        return $this->create($user);
    }

    /**
     * Determine whether the user can validate role configurations.
     */
    public function validateConfiguration($user, Role $role): bool
    {
        // Admins can validate role configurations
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_role', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage role dependencies.
     */
    public function manageDependencies($user, Role $role): bool
    {
        // Only admins can manage role dependencies
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_role', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view role statistics.
     */
    public function viewStatistics($user): bool
    {
        // Admins can view role statistics
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage role notifications.
     */
    public function manageNotifications($user, Role $role): bool
    {
        // Only admins can manage role notifications
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_role', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can export role permissions.
     */
    public function exportPermissions($user, Role $role): bool
    {
        // Admins can export role permissions
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_role', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can import role permissions.
     */
    public function importPermissions($user, Role $role): bool
    {
        // Only admins can import role permissions
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_role', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Helper method to check if role is a super admin role.
     */
    private function isSuperAdminRole(Role $role): bool
    {
        // Define super admin role names that should not be modified
        $superAdminRoles = ['super-admin', 'super_admin', 'administrator'];

        return in_array(strtolower($role->name), $superAdminRoles);
    }

    /**
     * Helper method to check if role is essential for system operation.
     */
    private function isEssentialRole(Role $role): bool
    {
        // Define essential role names that should not be deleted
        $essentialRoles = ['admin', 'user', 'vendor', 'courier'];

        return in_array(strtolower($role->name), $essentialRoles);
    }
}
