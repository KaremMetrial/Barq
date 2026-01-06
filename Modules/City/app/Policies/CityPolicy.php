<?php

namespace Modules\City\Policies;

use Modules\City\Models\City;
use Modules\Admin\Models\Admin;
use Modules\Vendor\Models\Vendor;
use Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Helpers\PermissionHelper;

class CityPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any cities.
     */
    public function viewAny($user): bool
    {
        // Admins with permission
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_city', 'admin')) {
            return true;
        }

        // Vendors with permission (can view cities for delivery zones)
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_city', 'vendor')) {
            return true;
        }

        // Users can view active cities
        if ($user instanceof User) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the city.
     */
    public function view($user, City $city): bool
    {
        // Admins with permission can view all cities
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_city', 'admin')) {
            return true;
        }

        // Vendors can view cities (needed for delivery zones)
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_city', 'vendor')) {
            return true;
        }

        // Users can view active cities
        if ($user instanceof User && $city->is_active) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create cities.
     */
    public function create($user): bool
    {
        // Only admins can create cities (geographic management)
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_city', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the city.
     */
    public function update($user, City $city): bool
    {
        // Only admins can update cities (geographic management)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_city', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the city.
     */
    public function delete($user, City $city): bool
    {
        // Only admins can delete cities (critical geographic data)
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_city', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the city.
     */
    public function restore($user, City $city): bool
    {
        // Same logic as update
        return $this->update($user, $city);
    }

    /**
     * Determine whether the user can permanently delete the city.
     */
    public function forceDelete($user, City $city): bool
    {
        // Same logic as delete
        return $this->delete($user, $city);
    }

    /**
     * Determine whether the user can manage city zones.
     */
    public function manageZones($user, City $city): bool
    {
        // Same logic as update (zone management is part of city management)
        return $this->update($user, $city);
    }

    /**
     * Determine whether the user can view city zones.
     */
    public function viewZones($user, City $city): bool
    {
        // Admins with permission
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_city', 'admin')) {
            return true;
        }

        // Vendors can view zones for delivery
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_city', 'vendor')) {
            return true;
        }

        // Users can view zones of active cities
        if ($user instanceof User && $city->is_active) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage city banners.
     */
    public function manageBanners($user, City $city): bool
    {
        // Same logic as update (banner management is part of city management)
        return $this->update($user, $city);
    }

    /**
     * Determine whether the user can view city banners.
     */
    public function viewBanners($user, City $city): bool
    {
        // Same logic as view
        return $this->view($user, $city);
    }

    /**
     * Determine whether the user can view city analytics/reports.
     */
    public function viewAnalytics($user, City $city): bool
    {
        // Admins with report permission
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage city settings.
     */
    public function manageSettings($user, City $city): bool
    {
        // Only admins can manage city settings
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_city', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can activate/deactivate cities.
     */
    public function toggleActive($user, City $city): bool
    {
        // Same logic as update
        return $this->update($user, $city);
    }

    /**
     * Determine whether the user can bulk update cities.
     */
    public function bulkUpdate($user): bool
    {
        // Only admins can bulk update cities
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_city', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk delete cities.
     */
    public function bulkDelete($user): bool
    {
        // Only admins can bulk delete cities
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_city', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can export cities.
     */
    public function export($user): bool
    {
        // Admins with view permission
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_city', 'admin')) {
            return true;
        }

        // Vendors with view permission (for their delivery areas)
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_city', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can import cities.
     */
    public function import($user): bool
    {
        // Only admins can import cities (geographic data management)
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_city', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can duplicate cities.
     */
    public function duplicate($user, City $city): bool
    {
        // Same logic as create
        return $this->create($user);
    }

    /**
     * Determine whether the user can view city hierarchy (geographic relationships).
     */
    public function viewHierarchy($user): bool
    {
        // Admins with view permission
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_city', 'admin')) {
            return true;
        }

        // Vendors with view permission
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_city', 'vendor')) {
            return true;
        }

        // Users can view hierarchy of active cities
        if ($user instanceof User) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage city governorate relationships.
     */
    public function manageGovernorate($user, City $city): bool
    {
        // Only admins can change governorate relationships
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_city', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can merge cities.
     */
    public function merge($user): bool
    {
        // Only admins can merge cities (complex geographic operation)
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_city', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can split cities.
     */
    public function split($user, City $city): bool
    {
        // Only admins can split cities (complex geographic operation)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_city', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view delivery coverage for the city.
     */
    public function viewDeliveryCoverage($user, City $city): bool
    {
        // Admins with permission
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_city', 'admin')) {
            return true;
        }

        // Vendors can view delivery coverage for their operational areas
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_city', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage delivery zones for the city.
     */
    public function manageDeliveryZones($user, City $city): bool
    {
        // Admins can manage all delivery zones
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_city', 'admin')) {
            return true;
        }

        return false;
    }
}
