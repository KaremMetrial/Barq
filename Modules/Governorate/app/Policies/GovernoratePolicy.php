<?php

namespace Modules\Governorate\Policies;

use Modules\Governorate\Models\Governorate;
use Modules\Admin\Models\Admin;
use Modules\Vendor\Models\Vendor;
use Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Helpers\PermissionHelper;

class GovernoratePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any governorates.
     */
    public function viewAny($user): bool
    {
        // Admins can view all governorates
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_governorate', 'admin')) {
            return true;
        }

        // Users can view active governorates
        if ($user instanceof User) {
            return true;
        }

        // Vendors can view governorates (for delivery operations)
        if ($user instanceof Vendor) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the governorate.
     */
    public function view($user, Governorate $governorate): bool
    {
        // Admins can view all governorates
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_governorate', 'admin')) {
            return true;
        }

        // Users can view active governorates
        if ($user instanceof User && $governorate->is_active) {
            return true;
        }

        // Vendors can view governorates (for operational purposes)
        if ($user instanceof Vendor) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create governorates.
     */
    public function create($user): bool
    {
        // Only admins can create governorates (geographic management)
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_governorate', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the governorate.
     */
    public function update($user, Governorate $governorate): bool
    {
        // Only admins can update governorates (critical geographic data)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_governorate', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the governorate.
     */
    public function delete($user, Governorate $governorate): bool
    {
        // Only admins can delete governorates (extremely sensitive operation)
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_governorate', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the governorate.
     */
    public function restore($user, Governorate $governorate): bool
    {
        // Same logic as update
        return $this->update($user, $governorate);
    }

    /**
     * Determine whether the user can permanently delete the governorate.
     */
    public function forceDelete($user, Governorate $governorate): bool
    {
        // Same logic as delete
        return $this->delete($user, $governorate);
    }

    /**
     * Determine whether the user can manage governorate cities.
     */
    public function manageCities($user, Governorate $governorate): bool
    {
        // Same logic as update (city management is part of governorate management)
        return $this->update($user, $governorate);
    }

    /**
     * Determine whether the user can view governorate cities.
     */
    public function viewCities($user, Governorate $governorate): bool
    {
        // Admins can view all cities
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_governorate', 'admin')) {
            return true;
        }

        // Users can view cities of active governorates
        if ($user instanceof User && $governorate->is_active) {
            return true;
        }

        // Vendors can view cities (for delivery operations)
        if ($user instanceof Vendor) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage governorate country relationship.
     */
    public function manageCountry($user, Governorate $governorate): bool
    {
        // Only admins can change country relationships
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_governorate', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view governorate analytics/reports.
     */
    public function viewAnalytics($user, Governorate $governorate): bool
    {
        // Admins can view governorate analytics
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage governorate settings.
     */
    public function manageSettings($user, Governorate $governorate): bool
    {
        // Only admins can manage governorate settings
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_governorate', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can activate/deactivate governorates.
     */
    public function toggleActive($user, Governorate $governorate): bool
    {
        // Only admins can activate/deactivate governorates
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_governorate', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can export governorate data.
     */
    public function export($user): bool
    {
        // Admins can export governorate data
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_governorate', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can import governorate data.
     */
    public function import($user): bool
    {
        // Only admins can import governorates (geographic data management)
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_governorate', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk update governorates.
     */
    public function bulkUpdate($user): bool
    {
        // Only admins can bulk update governorates
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_governorate', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk delete governorates.
     */
    public function bulkDelete($user): bool
    {
        // Only admins can bulk delete governorates
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_governorate', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can duplicate governorates.
     */
    public function duplicate($user, Governorate $governorate): bool
    {
        // Same logic as create
        return $this->create($user);
    }

    /**
     * Determine whether the user can merge governorates.
     */
    public function merge($user): bool
    {
        // Only admins can merge governorates (complex geographic operation)
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_governorate', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can split governorates.
     */
    public function split($user, Governorate $governorate): bool
    {
        // Only admins can split governorates (complex geographic operation)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_governorate', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view governorate geographic hierarchy.
     */
    public function viewHierarchy($user): bool
    {
        // Admins can view geographic hierarchy
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_governorate', 'admin')) {
            return true;
        }

        // Users can view hierarchy of active governorates
        if ($user instanceof User) {
            return true;
        }

        // Vendors can view geographic hierarchy
        if ($user instanceof Vendor) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage governorate translations.
     */
    public function manageTranslations($user, Governorate $governorate): bool
    {
        // Admins can manage translations
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_governorate', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view governorate statistics.
     */
    public function viewStatistics($user): bool
    {
        // Admins can view governorate statistics
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can configure governorate-specific features.
     */
    public function configureFeatures($user, Governorate $governorate): bool
    {
        // Only admins can configure governorate features
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_governorate', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view delivery coverage for the governorate.
     */
    public function viewDeliveryCoverage($user, Governorate $governorate): bool
    {
        // Admins can view all delivery coverage
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_governorate', 'admin')) {
            return true;
        }

        // Vendors can view delivery coverage
        if ($user instanceof Vendor) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage delivery zones for the governorate.
     */
    public function manageDeliveryZones($user, Governorate $governorate): bool
    {
        // Admins can manage all delivery zones
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_governorate', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view population/demographic data for the governorate.
     */
    public function viewDemographics($user, Governorate $governorate): bool
    {
        // Admins can view demographic data
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage governorate boundaries.
     */
    public function manageBoundaries($user, Governorate $governorate): bool
    {
        // Only admins can manage geographic boundaries
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_governorate', 'admin')) {
            return true;
        }

        return false;
    }
}
