<?php

namespace Modules\Vendor\Policies;

use Modules\Vendor\Models\Vendor;
use Modules\Admin\Models\Admin;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Helpers\PermissionHelper;

class VendorPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any vendors.
     */
    public function viewAny($user): bool
    {
        // Only admins can view vendor lists
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_vendor', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the vendor.
     */
    public function view($user, Vendor $vendor): bool
    {
        // Admins can view any vendor
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_vendor', 'admin')) {
            return true;
        }

        // Vendors can view their own profile
        if ($user instanceof Vendor && $user->id === $vendor->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create vendors.
     */
    public function create($user): bool
    {
        // Admins can create vendor accounts
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_vendor', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the vendor.
     */
    public function update($user, Vendor $vendor): bool
    {
        // Admins can update any vendor
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_vendor', 'admin')) {
            return true;
        }

        // Vendors can update their own profile
        if ($user instanceof Vendor && $user->id === $vendor->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the vendor.
     */
    public function delete($user, Vendor $vendor): bool
    {
        // Only admins can delete vendor accounts
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_vendor', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the vendor.
     */
    public function restore($user, Vendor $vendor): bool
    {
        // Same logic as update
        return $this->update($user, $vendor);
    }

    /**
     * Determine whether the user can permanently delete the vendor.
     */
    public function forceDelete($user, Vendor $vendor): bool
    {
        // Same logic as delete
        return $this->delete($user, $vendor);
    }

    /**
     * Determine whether the user can manage vendor status.
     */
    public function manageStatus($user, Vendor $vendor): bool
    {
        // Only admins can manage vendor status
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_vendor', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage vendor roles.
     */
    public function manageRoles($user, Vendor $vendor): bool
    {
        // Only admins can manage vendor roles
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_vendor', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can assign roles to the vendor.
     */
    public function assignRoles($user, Vendor $vendor): bool
    {
        // Same logic as manageRoles
        return $this->manageRoles($user, $vendor);
    }

    /**
     * Determine whether the user can remove roles from the vendor.
     */
    public function removeRoles($user, Vendor $vendor): bool
    {
        // Same logic as manageRoles
        return $this->manageRoles($user, $vendor);
    }

    /**
     * Determine whether the user can manage vendor permissions.
     */
    public function managePermissions($user, Vendor $vendor): bool
    {
        // Only admins can manage vendor permissions
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_vendor', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage vendor store assignment.
     */
    public function manageStoreAssignment($user, Vendor $vendor): bool
    {
        // Only admins can manage store assignments
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_vendor', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage vendor POS shifts.
     */
    public function managePosShifts($user, Vendor $vendor): bool
    {
        // Admins can manage any vendor's POS shifts
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_vendor', 'admin')) {
            return true;
        }

        // Vendors can manage their own POS shifts
        if ($user instanceof Vendor && $user->id === $vendor->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view vendor analytics.
     */
    public function viewAnalytics($user, Vendor $vendor): bool
    {
        // Admins can view any vendor analytics
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        // Vendors can view their own analytics
        if ($user instanceof Vendor && $user->id === $vendor->id) {
            return PermissionHelper::hasPermission('view_report', 'vendor');
        }

        return false;
    }

    /**
     * Determine whether the user can export vendor data.
     */
    public function export($user, Vendor $vendor): bool
    {
        // Admins can export any vendor data
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_vendor', 'admin')) {
            return true;
        }

        // Vendors can export their own data
        if ($user instanceof Vendor && $user->id === $vendor->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can import vendor data.
     */
    public function import($user): bool
    {
        // Only admins can import vendor data
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_vendor', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk update vendors.
     */
    public function bulkUpdate($user): bool
    {
        // Only admins can bulk update vendors
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_vendor', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk delete vendors.
     */
    public function bulkDelete($user): bool
    {
        // Only admins can bulk delete vendors
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_vendor', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can reset vendor passwords.
     */
    public function resetPassword($user, Vendor $vendor): bool
    {
        // Admins can reset any vendor password
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_vendor', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage vendor notifications.
     */
    public function manageNotifications($user, Vendor $vendor): bool
    {
        // Admins can manage any vendor notifications
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_vendor', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can duplicate vendors.
     */
    public function duplicate($user, Vendor $vendor): bool
    {
        // Only admins can duplicate vendors
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_vendor', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage vendor sessions.
     */
    public function manageSessions($user, Vendor $vendor): bool
    {
        // Admins can manage any vendor sessions
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_vendor', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view vendor audit logs.
     */
    public function viewAuditLogs($user, Vendor $vendor): bool
    {
        // Admins can view any vendor audit logs
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_vendor', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage vendor commissions.
     */
    public function manageCommissions($user, Vendor $vendor): bool
    {
        // Only admins can manage vendor commissions (financial impact)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_vendor', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view vendor commission reports.
     */
    public function viewCommissionReports($user, Vendor $vendor): bool
    {
        // Admins can view any vendor commission reports
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        // Vendors can view their own commission reports
        if ($user instanceof Vendor && $user->id === $vendor->id) {
            return PermissionHelper::hasPermission('view_report', 'vendor');
        }

        return false;
    }

    /**
     * Determine whether the user can manage vendor payouts.
     */
    public function managePayouts($user, Vendor $vendor): bool
    {
        // Admins can manage any vendor payouts
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_vendor', 'admin')) {
            return true;
        }

        // Vendors can manage their own payouts
        if ($user instanceof Vendor && $user->id === $vendor->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view vendor performance metrics.
     */
    public function viewPerformance($user, Vendor $vendor): bool
    {
        // Same logic as viewAnalytics
        return $this->viewAnalytics($user, $vendor);
    }

    /**
     * Determine whether the user can manage vendor contracts.
     */
    public function manageContracts($user, Vendor $vendor): bool
    {
        // Only admins can manage vendor contracts
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_vendor', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can approve vendor applications.
     */
    public function approveApplication($user, Vendor $vendor): bool
    {
        // Only admins can approve vendor applications
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_vendor', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can suspend vendor accounts.
     */
    public function suspend($user, Vendor $vendor): bool
    {
        // Only admins can suspend vendor accounts
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_vendor', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can reactivate vendor accounts.
     */
    public function reactivate($user, Vendor $vendor): bool
    {
        // Same logic as suspend
        return $this->suspend($user, $vendor);
    }

    /**
     * Determine whether the user can transfer vendors between stores.
     */
    public function transferStore($user, Vendor $vendor): bool
    {
        // Only admins can transfer vendors between stores
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_vendor', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage vendor working hours.
     */
    public function manageWorkingHours($user, Vendor $vendor): bool
    {
        // Admins can manage any vendor's working hours
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_vendor', 'admin')) {
            return true;
        }

        // Vendors can manage their own working hours
        if ($user instanceof Vendor && $user->id === $vendor->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage vendor holidays.
     */
    public function manageHolidays($user, Vendor $vendor): bool
    {
        // Same logic as manageWorkingHours
        return $this->manageWorkingHours($user, $vendor);
    }

    /**
     * Determine whether the user can manage vendor targets.
     */
    public function manageTargets($user, Vendor $vendor): bool
    {
        // Only admins can manage vendor performance targets
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_vendor', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view vendor target reports.
     */
    public function viewTargetReports($user, Vendor $vendor): bool
    {
        // Admins can view any vendor target reports
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        // Vendors can view their own target reports
        if ($user instanceof Vendor && $user->id === $vendor->id) {
            return PermissionHelper::hasPermission('view_report', 'vendor');
        }

        return false;
    }

    /**
     * Determine whether the user can manage vendor incentives.
     */
    public function manageIncentives($user, Vendor $vendor): bool
    {
        // Only admins can manage vendor incentives
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_vendor', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage vendor training.
     */
    public function manageTraining($user, Vendor $vendor): bool
    {
        // Admins can manage any vendor training
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_vendor', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view vendor training records.
     */
    public function viewTrainingRecords($user, Vendor $vendor): bool
    {
        // Admins can view any vendor training records
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_vendor', 'admin')) {
            return true;
        }

        // Vendors can view their own training records
        if ($user instanceof Vendor && $user->id === $vendor->id) {
            return true;
        }

        return false;
    }
}
