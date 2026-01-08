<?php

namespace Modules\WorkingDay\Policies;

use Modules\WorkingDay\Models\WorkingDay;
use Modules\Admin\Models\Admin;
use Modules\Vendor\Models\Vendor;
use Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Helpers\PermissionHelper;

class WorkingDayPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any working days.
     */
    public function viewAny($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Admins can view all working days
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_working_day', 'admin')) {
            return true;
        }

        // Vendors can view their store's working days
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_working_day', 'vendor')) {
            return true;
        }

        // Users can view working days (for availability checking)
        if ($user instanceof User) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the working day.
     */
    public function view($user, WorkingDay $workingDay): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Admins can view all working days
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_working_day', 'admin')) {
            return true;
        }

        // Vendors can view working days for their stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_working_day', 'vendor')) {
            return $workingDay->store && $workingDay->store->owner && $workingDay->store->owner->id === $user->id;
        }

        // Users can view working days
        if ($user instanceof User) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create working days.
     */
    public function create($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Admins can create working days for any store
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_working_day', 'admin')) {
            return true;
        }

        // Vendors can create working days for their stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('create_working_day', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the working day.
     */
    public function update($user, WorkingDay $workingDay): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Admins can update any working day
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_working_day', 'admin')) {
            return true;
        }

        // Vendors can update working days for their stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_working_day', 'vendor')) {
            return $workingDay->store && $workingDay->store->owner && $workingDay->store->owner->id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the working day.
     */
    public function delete($user, WorkingDay $workingDay): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Admins can delete any working day
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_working_day', 'admin')) {
            return true;
        }

        // Vendors can delete working days for their stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('delete_working_day', 'vendor')) {
            return $workingDay->store && $workingDay->store->owner && $workingDay->store->owner->id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the working day.
     */
    public function restore($user, WorkingDay $workingDay): bool
    {
        // Same logic as update
        return $this->update($user, $workingDay);
    }

    /**
     * Determine whether the user can permanently delete the working day.
     */
    public function forceDelete($user, WorkingDay $workingDay): bool
    {
        // Same logic as delete
        return $this->delete($user, $workingDay);
    }

    /**
     * Determine whether the user can duplicate working days.
     */
    public function duplicate($user, WorkingDay $workingDay): bool
    {
        // Same logic as create
        return $this->create($user);
    }

    /**
     * Determine whether the user can export working day data.
     */
    public function export($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Admins can export all working day data
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_working_day', 'admin')) {
            return true;
        }

        // Vendors can export their store's working day data
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_working_day', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can import working day data.
     */
    public function import($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Admins can import working day data
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_working_day', 'admin')) {
            return true;
        }

        // Vendors can import working day data for their stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('create_working_day', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk update working days.
     */
    public function bulkUpdate($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Admins can bulk update all working days
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_working_day', 'admin')) {
            return true;
        }

        // Vendors can bulk update their store's working days
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_working_day', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk delete working days.
     */
    public function bulkDelete($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Admins can bulk delete any working days
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_working_day', 'admin')) {
            return true;
        }

        // Vendors can bulk delete their store's working days
        if ($user instanceof Vendor && PermissionHelper::hasPermission('delete_working_day', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage working day templates.
     */
    public function manageTemplates($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Admins can manage working day templates
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_working_day', 'admin')) {
            return true;
        }

        // Vendors can manage templates for their stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('create_working_day', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can apply working day templates.
     */
    public function applyTemplates($user): bool
    {
        // Same logic as manageTemplates
        return $this->manageTemplates($user);
    }

    /**
     * Determine whether the user can copy working days between stores.
     */
    public function copyBetweenStores($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can copy working days between stores
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_working_day', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage store holidays.
     */
    public function manageHolidays($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Admins can manage holidays for any store
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_working_day', 'admin')) {
            return true;
        }

        // Vendors can manage holidays for their stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_working_day', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage special hours.
     */
    public function manageSpecialHours($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Admins can manage special hours for any store
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_working_day', 'admin')) {
            return true;
        }

        // Vendors can manage special hours for their stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_working_day', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage break times.
     */
    public function manageBreaks($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Admins can manage breaks for any store
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_working_day', 'admin')) {
            return true;
        }

        // Vendors can manage breaks for their stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_working_day', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage delivery hours.
     */
    public function manageDeliveryHours($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Admins can manage delivery hours for any store
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_working_day', 'admin')) {
            return true;
        }

        // Vendors can manage delivery hours for their stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_working_day', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage pickup hours.
     */
    public function managePickupHours($user): bool
    {
        // Same logic as manageDeliveryHours
        return $this->manageDeliveryHours($user);
    }

    /**
     * Determine whether the user can set store as 24/7.
     */
    public function set247Operation($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Admins can set any store as 24/7
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_working_day', 'admin')) {
            return true;
        }

        // Vendors can set their stores as 24/7
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_working_day', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage seasonal hours.
     */
    public function manageSeasonalHours($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Admins can manage seasonal hours for any store
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_working_day', 'admin')) {
            return true;
        }

        // Vendors can manage seasonal hours for their stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_working_day', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage emergency hours.
     */
    public function manageEmergencyHours($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Admins can manage emergency hours for any store
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_working_day', 'admin')) {
            return true;
        }

        // Vendors can manage emergency hours for their stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_working_day', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view working day analytics.
     */
    public function viewAnalytics($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Admins can view all working day analytics
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        // Vendors can view analytics for their stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_report', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can generate working day reports.
     */
    public function generateReports($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Admins can generate all working day reports
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        // Vendors can generate reports for their stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_report', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage working day notifications.
     */
    public function manageNotifications($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Admins can manage notifications for any store
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_working_day', 'admin')) {
            return true;
        }

        // Vendors can manage notifications for their stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_working_day', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can override working hours.
     */
    public function overrideHours($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can override working hours (emergency situations)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_working_day', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage working day policies.
     */
    public function managePolicies($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can manage working day policies
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_working_day', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can audit working day changes.
     */
    public function auditChanges($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Admins can audit all working day changes
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_working_day', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage working day backups.
     */
    public function manageBackups($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Admins can manage working day backups
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_working_day', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore working day backups.
     */
    public function restoreBackups($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Admins can restore working day backups
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_working_day', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage store timezone settings.
     */
    public function manageTimezone($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Admins can manage timezone for any store
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_working_day', 'admin')) {
            return true;
        }

        // Vendors can manage timezone for their stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_working_day', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage daylight saving time adjustments.
     */
    public function manageDST($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Admins can manage DST for any store
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_working_day', 'admin')) {
            return true;
        }

        // Vendors can manage DST for their stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_working_day', 'vendor')) {
            return true;
        }

        return false;
    }
}
