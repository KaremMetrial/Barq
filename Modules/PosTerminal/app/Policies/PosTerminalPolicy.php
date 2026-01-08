<?php

namespace Modules\PosTerminal\Policies;

use Modules\PosTerminal\Models\PosTerminal;
use Modules\Admin\Models\Admin;
use Modules\Vendor\Models\Vendor;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Helpers\PermissionHelper;

class PosTerminalPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any POS terminals.
     */
    public function viewAny($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can view all POS terminals
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_pos_terminal', 'admin')) {
            return true;
        }

        // Vendors can view POS terminals for their stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_pos_terminal', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the POS terminal.
     */
    public function view($user, PosTerminal $posTerminal): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can view all POS terminals
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_pos_terminal', 'admin')) {
            return true;
        }

        // Vendors can view POS terminals for their stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_pos_terminal', 'vendor')) {
            return $posTerminal->store_id === $user->store_id;
        }

        return false;
    }

    /**
     * Determine whether the user can create POS terminals.
     */
    public function create($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can create POS terminals for any store
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_pos_terminal', 'admin')) {
            return true;
        }

        // Vendors can create POS terminals for their stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('create_pos_terminal', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the POS terminal.
     */
    public function update($user, PosTerminal $posTerminal): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can update any POS terminal
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_pos_terminal', 'admin')) {
            return true;
        }

        // Vendors can update POS terminals for their stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_pos_terminal', 'vendor')) {
            return $posTerminal->store_id === $user->store_id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the POS terminal.
     */
    public function delete($user, PosTerminal $posTerminal): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Only admins can delete POS terminals (infrastructure management)
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_pos_terminal', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the POS terminal.
     */
    public function restore($user, PosTerminal $posTerminal): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as update
        return $this->update($user, $posTerminal);
    }

    /**
     * Determine whether the user can permanently delete the POS terminal.
     */
    public function forceDelete($user, PosTerminal $posTerminal): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as delete
        return $this->delete($user, $posTerminal);
    }

    /**
     * Determine whether the user can activate/deactivate POS terminals.
     */
    public function toggleActive($user, PosTerminal $posTerminal): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can activate/deactivate any POS terminal
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_pos_terminal', 'admin')) {
            return true;
        }

        // Vendors can activate/deactivate terminals for their stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_pos_terminal', 'vendor')) {
            return $posTerminal->store_id === $user->store_id;
        }

        return false;
    }

    /**
     * Determine whether the user can assign POS terminals to stores.
     */
    public function assignToStore($user, PosTerminal $posTerminal): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Only admins can assign terminals between stores
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_pos_terminal', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view POS terminal analytics.
     */
    public function viewAnalytics($user, PosTerminal $posTerminal): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can view all POS terminal analytics
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        // Vendors can view analytics for their store's terminals
        if ($user instanceof Vendor) {
            if ($posTerminal->store_id === $user->store_id) {
                return PermissionHelper::hasPermission('view_report', 'vendor');
            }
            return false;
        }

        return false;
    }

    /**
     * Determine whether the user can view POS terminal performance metrics.
     */
    public function viewPerformance($user, PosTerminal $posTerminal): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as viewAnalytics
        return $this->viewAnalytics($user, $posTerminal);
    }

    /**
     * Determine whether the user can export POS terminal data.
     */
    public function export($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can export all POS terminal data
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_pos_terminal', 'admin')) {
            return true;
        }

        // Vendors can export data for their store's terminals
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_pos_terminal', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can import POS terminal data.
     */
    public function import($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Only admins can import POS terminals
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_pos_terminal', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk update POS terminals.
     */
    public function bulkUpdate($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can bulk update all POS terminals
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_pos_terminal', 'admin')) {
            return true;
        }

        // Vendors can bulk update their store's terminals
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_pos_terminal', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk delete POS terminals.
     */
    public function bulkDelete($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Only admins can bulk delete POS terminals
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_pos_terminal', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can sync POS terminal data.
     */
    public function sync($user, PosTerminal $posTerminal): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can sync any POS terminal
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_pos_terminal', 'admin')) {
            return true;
        }

        // Vendors can sync terminals for their stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_pos_terminal', 'vendor')) {
            return $posTerminal->store_id === $user->store_id;
        }

        return false;
    }

    /**
     * Determine whether the user can configure POS terminal settings.
     */
    public function configure($user, PosTerminal $posTerminal): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can configure any POS terminal
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_pos_terminal', 'admin')) {
            return true;
        }

        // Vendors can configure terminals for their stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_pos_terminal', 'vendor')) {
            return $posTerminal->store_id === $user->store_id;
        }

        return false;
    }

    /**
     * Determine whether the user can view POS terminal logs.
     */
    public function viewLogs($user, PosTerminal $posTerminal): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can view all POS terminal logs
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_pos_terminal', 'admin')) {
            return true;
        }

        // Vendors can view logs for their store's terminals
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_pos_terminal', 'vendor')) {
            return $posTerminal->store_id === $user->store_id;
        }

        return false;
    }

    /**
     * Determine whether the user can remote control POS terminals.
     */
    public function remoteControl($user, PosTerminal $posTerminal): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Only admins can remotely control POS terminals (security)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_pos_terminal', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update POS terminal software.
     */
    public function updateSoftware($user, PosTerminal $posTerminal): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can update software on any POS terminal
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_pos_terminal', 'admin')) {
            return true;
        }

        // Vendors can update software on their store's terminals
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_pos_terminal', 'vendor')) {
            return $posTerminal->store_id === $user->store_id;
        }

        return false;
    }

    /**
     * Determine whether the user can reboot POS terminals.
     */
    public function reboot($user, PosTerminal $posTerminal): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can reboot any POS terminal
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_pos_terminal', 'admin')) {
            return true;
        }

        // Vendors can reboot terminals for their stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_pos_terminal', 'vendor')) {
            return $posTerminal->store_id === $user->store_id;
        }

        return false;
    }

    /**
     * Determine whether the user can monitor POS terminal status.
     */
    public function monitorStatus($user, PosTerminal $posTerminal): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can monitor all POS terminals
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_pos_terminal', 'admin')) {
            return true;
        }

        // Vendors can monitor terminals for their stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_pos_terminal', 'vendor')) {
            return $posTerminal->store_id === $user->store_id;
        }

        return false;
    }

    /**
     * Determine whether the user can troubleshoot POS terminals.
     */
    public function troubleshoot($user, PosTerminal $posTerminal): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can troubleshoot any POS terminal
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_pos_terminal', 'admin')) {
            return true;
        }

        // Vendors can troubleshoot terminals for their stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_pos_terminal', 'vendor')) {
            return $posTerminal->store_id === $user->store_id;
        }

        return false;
    }

    /**
     * Determine whether the user can view POS terminal maintenance history.
     */
    public function viewMaintenanceHistory($user, PosTerminal $posTerminal): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can view all maintenance history
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_pos_terminal', 'admin')) {
            return true;
        }

        // Vendors can view maintenance history for their terminals
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_pos_terminal', 'vendor')) {
            return $posTerminal->store_id === $user->store_id;
        }

        return false;
    }

    /**
     * Determine whether the user can schedule POS terminal maintenance.
     */
    public function scheduleMaintenance($user, PosTerminal $posTerminal): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can schedule maintenance for any terminal
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_pos_terminal', 'admin')) {
            return true;
        }

        // Vendors can schedule maintenance for their terminals
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_pos_terminal', 'vendor')) {
            return $posTerminal->store_id === $user->store_id;
        }

        return false;
    }

    /**
     * Determine whether the user can duplicate POS terminals.
     */
    public function duplicate($user, PosTerminal $posTerminal): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as create
        return $this->create($user);
    }

    /**
     * Determine whether the user can transfer POS terminals between stores.
     */
    public function transfer($user, PosTerminal $posTerminal): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Only admins can transfer terminals between stores
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_pos_terminal', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can decommission POS terminals.
     */
    public function decommission($user, PosTerminal $posTerminal): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Only admins can decommission terminals (asset management)
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_pos_terminal', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can generate POS terminal reports.
     */
    public function generateReports($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can generate all POS terminal reports
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        // Vendors can generate reports for their terminals
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_report', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage POS terminal security.
     */
    public function manageSecurity($user, PosTerminal $posTerminal): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Only admins can manage POS terminal security (PCI compliance)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_pos_terminal', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can audit POS terminal usage.
     */
    public function auditUsage($user, PosTerminal $posTerminal): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can audit all POS terminal usage
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_pos_terminal', 'admin')) {
            return true;
        }

        // Vendors can audit their own terminals
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_pos_terminal', 'vendor')) {
            return $posTerminal->store_id === $user->store_id;
        }

        return false;
    }
}
