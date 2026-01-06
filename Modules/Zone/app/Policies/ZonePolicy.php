<?php

namespace Modules\Zone\Policies;

use Modules\Zone\Models\Zone;
use Modules\Admin\Models\Admin;
use Modules\Vendor\Models\Vendor;
use Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Helpers\PermissionHelper;

class ZonePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any zones.
     */
    public function viewAny($user): bool
    {
        // Admins can view all zones
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_zone', 'admin')) {
            return true;
        }

        // Vendors can view zones
        if ($user instanceof Vendor) {
            return true;
        }

        // Users can view active zones (for delivery options)
        if ($user instanceof User) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the zone.
     */
    public function view($user, Zone $zone): bool
    {
        // Admins can view all zones
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_zone', 'admin')) {
            return true;
        }

        // Vendors can view zones
        if ($user instanceof Vendor) {
            return true;
        }

        // Users can view active zones
        if ($user instanceof User && $zone->is_active) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create zones.
     */
    public function create($user): bool
    {
        // Only admins can create zones (geographic infrastructure)
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_zone', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the zone.
     */
    public function update($user, Zone $zone): bool
    {
        // Only admins can update zones (geographic boundaries)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_zone', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the zone.
     */
    public function delete($user, Zone $zone): bool
    {
        // Only admins can delete zones (significant geographic impact)
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_zone', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the zone.
     */
    public function restore($user, Zone $zone): bool
    {
        // Same logic as update
        return $this->update($user, $zone);
    }

    /**
     * Determine whether the user can permanently delete the zone.
     */
    public function forceDelete($user, Zone $zone): bool
    {
        // Same logic as delete
        return $this->delete($user, $zone);
    }

    /**
     * Determine whether the user can manage zone boundaries.
     */
    public function manageBoundaries($user, Zone $zone): bool
    {
        // Only admins can manage geographic boundaries
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_zone', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage zone cities.
     */
    public function manageCities($user, Zone $zone): bool
    {
        // Only admins can manage city assignments
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_zone', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage zone stores.
     */
    public function manageStores($user, Zone $zone): bool
    {
        // Admins can manage any zone's store assignments
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_zone', 'admin')) {
            return true;
        }

        // Vendors can manage their store's zone assignments
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_zone', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage zone couriers.
     */
    public function manageCouriers($user, Zone $zone): bool
    {
        // Admins can manage any zone's courier assignments
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_zone', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage zone shipping prices.
     */
    public function manageShippingPrices($user, Zone $zone): bool
    {
        // Admins can manage any zone's shipping prices
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_zone', 'admin')) {
            return true;
        }

        // Vendors can manage shipping prices for their zones
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_zone', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can activate/deactivate zones.
     */
    public function toggleActive($user, Zone $zone): bool
    {
        // Only admins can activate/deactivate zones
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_zone', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can duplicate zones.
     */
    public function duplicate($user, Zone $zone): bool
    {
        // Same logic as create
        return $this->create($user);
    }

    /**
     * Determine whether the user can export zone data.
     */
    public function export($user): bool
    {
        // Admins can export all zone data
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_zone', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can import zone data.
     */
    public function import($user): bool
    {
        // Only admins can import zone data
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_zone', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk update zones.
     */
    public function bulkUpdate($user): bool
    {
        // Only admins can bulk update zones
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_zone', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk delete zones.
     */
    public function bulkDelete($user): bool
    {
        // Only admins can bulk delete zones
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_zone', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage zone translations.
     */
    public function manageTranslations($user, Zone $zone): bool
    {
        // Only admins can manage translations
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_zone', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view zone analytics.
     */
    public function viewAnalytics($user, Zone $zone): bool
    {
        // Admins can view all zone analytics
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view zone performance metrics.
     */
    public function viewPerformance($user, Zone $zone): bool
    {
        // Same logic as viewAnalytics
        return $this->viewAnalytics($user, $zone);
    }

    /**
     * Determine whether the user can perform coordinate lookups.
     */
    public function performCoordinateLookups($user): bool
    {
        // Any authenticated user can perform coordinate lookups (for delivery calculation)
        if ($user) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage zone addresses.
     */
    public function manageAddresses($user, Zone $zone): bool
    {
        // Admins can manage any zone's addresses
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_zone', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can merge zones.
     */
    public function merge($user): bool
    {
        // Only admins can merge zones (geographic restructuring)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_zone', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can split zones.
     */
    public function split($user): bool
    {
        // Only admins can split zones (geographic restructuring)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_zone', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage zone coverage.
     */
    public function manageCoverage($user, Zone $zone): bool
    {
        // Only admins can manage geographic coverage
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_zone', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage zone overlapping.
     */
    public function manageOverlapping($user): bool
    {
        // Only admins can manage zone overlapping issues
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_zone', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage zone priorities.
     */
    public function managePriorities($user, Zone $zone): bool
    {
        // Only admins can manage zone priorities
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_zone', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can generate zone reports.
     */
    public function generateReports($user): bool
    {
        // Admins can generate zone reports
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage zone templates.
     */
    public function manageTemplates($user): bool
    {
        // Only admins can manage zone templates
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_zone', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can apply zone templates.
     */
    public function applyTemplates($user): bool
    {
        // Same logic as create
        return $this->create($user);
    }

    /**
     * Determine whether the user can manage zone backups.
     */
    public function manageBackups($user): bool
    {
        // Admins can manage zone backups
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_zone', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore zone backups.
     */
    public function restoreBackups($user): bool
    {
        // Admins can restore zone backups
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_zone', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can audit zone changes.
     */
    public function auditChanges($user): bool
    {
        // Admins can audit all zone changes
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_zone', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage zone validation.
     */
    public function manageValidation($user, Zone $zone): bool
    {
        // Admins can validate zone configurations
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_zone', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage zone notifications.
     */
    public function manageNotifications($user): bool
    {
        // Admins can manage zone-related notifications
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_zone', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage zone integrations.
     */
    public function manageIntegrations($user, Zone $zone): bool
    {
        // Only admins can manage geographic integrations
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_zone', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage zone APIs.
     */
    public function manageApis($user, Zone $zone): bool
    {
        // Only admins can manage zone APIs
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_zone', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage zone caching.
     */
    public function manageCaching($user, Zone $zone): bool
    {
        // Admins can manage zone caching
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_zone', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage zone performance.
     */
    public function managePerformance($user, Zone $zone): bool
    {
        // Admins can manage zone performance settings
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_zone', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage zone monitoring.
     */
    public function manageMonitoring($user, Zone $zone): bool
    {
        // Admins can manage zone monitoring
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_zone', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage zone alerts.
     */
    public function manageAlerts($user, Zone $zone): bool
    {
        // Admins can manage zone alerts
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_zone', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage zone policies.
     */
    public function managePolicies($user): bool
    {
        // Only admins can manage zone policies
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_zone', 'admin')) {
            return true;
        }

        return false;
    }
}
