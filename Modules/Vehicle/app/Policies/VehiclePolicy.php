<?php

namespace Modules\Vehicle\Policies;

use Modules\Vehicle\Models\Vehicle;
use Modules\Admin\Models\Admin;
use Modules\Vendor\Models\Vendor;
use Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Helpers\PermissionHelper;

class VehiclePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any vehicles.
     */
    public function viewAny($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Admins can view all vehicles
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_vehicle', 'admin')) {
            return true;
        }

        // Vendors can view vehicles
        if ($user instanceof Vendor) {
            return true;
        }

        // Users can view active vehicles (for delivery options)
        if ($user instanceof User) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the vehicle.
     */
    public function view($user, Vehicle $vehicle): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Admins can view all vehicles
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_vehicle', 'admin')) {
            return true;
        }

        // Vendors can view vehicles
        if ($user instanceof Vendor) {
            return true;
        }

        // Users can view active vehicles
        if ($user instanceof User && $vehicle->is_active) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create vehicles.
     */
    public function create($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can create vehicles (fleet management)
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_vehicle', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the vehicle.
     */
    public function update($user, Vehicle $vehicle): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can update vehicles (fleet management)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_vehicle', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the vehicle.
     */
    public function delete($user, Vehicle $vehicle): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can delete vehicles (fleet management)
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_vehicle', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the vehicle.
     */
    public function restore($user, Vehicle $vehicle): bool
    {
        // Same logic as update
        return $this->update($user, $vehicle);
    }

    /**
     * Determine whether the user can permanently delete the vehicle.
     */
    public function forceDelete($user, Vehicle $vehicle): bool
    {
        // Same logic as delete
        return $this->delete($user, $vehicle);
    }

    /**
     * Determine whether the user can manage vehicle status.
     */
    public function toggleActive($user, Vehicle $vehicle): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can activate/deactivate vehicles
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_vehicle', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage vehicle assignments to couriers.
     */
    public function manageCourierAssignments($user, Vehicle $vehicle): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Admins can manage any courier assignments
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_vehicle', 'admin')) {
            return true;
        }

        // Vendors can manage courier assignments for their stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_vehicle', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage vehicle shipping prices.
     */
    public function manageShippingPrices($user, Vehicle $vehicle): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can manage vehicle shipping prices (pricing impact)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_vehicle', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view vehicle analytics.
     */
    public function viewAnalytics($user, Vehicle $vehicle): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Admins can view all vehicle analytics
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view vehicle performance metrics.
     */
    public function viewPerformance($user, Vehicle $vehicle): bool
    {
        // Same logic as viewAnalytics
        return $this->viewAnalytics($user, $vehicle);
    }

    /**
     * Determine whether the user can export vehicle data.
     */
    public function export($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Admins can export all vehicle data
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_vehicle', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can import vehicle data.
     */
    public function import($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can import vehicles
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_vehicle', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk update vehicles.
     */
    public function bulkUpdate($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can bulk update vehicles
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_vehicle', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk delete vehicles.
     */
    public function bulkDelete($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can bulk delete vehicles
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_vehicle', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can duplicate vehicles.
     */
    public function duplicate($user, Vehicle $vehicle): bool
    {
        // Same logic as create
        return $this->create($user);
    }

    /**
     * Determine whether the user can manage vehicle translations.
     */
    public function manageTranslations($user, Vehicle $vehicle): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can manage translations
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_vehicle', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage vehicle icons.
     */
    public function manageIcons($user, Vehicle $vehicle): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can manage vehicle icons
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_vehicle', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view vehicle maintenance records.
     */
    public function viewMaintenance($user, Vehicle $vehicle): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Admins can view all vehicle maintenance
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_vehicle', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage vehicle maintenance.
     */
    public function manageMaintenance($user, Vehicle $vehicle): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can manage vehicle maintenance
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_vehicle', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view vehicle usage statistics.
     */
    public function viewUsageStats($user, Vehicle $vehicle): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Admins can view all vehicle usage statistics
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can optimize vehicle assignments.
     */
    public function optimizeAssignments($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can optimize vehicle assignments
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_vehicle', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage vehicle categories.
     */
    public function manageCategories($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can manage vehicle categories
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_vehicle', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can decommission vehicles.
     */
    public function decommission($user, Vehicle $vehicle): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can decommission vehicles
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_vehicle', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can reactivate decommissioned vehicles.
     */
    public function reactivate($user, Vehicle $vehicle): bool
    {
        // Same logic as decommission
        return $this->decommission($user, $vehicle);
    }

    /**
     * Determine whether the user can view vehicle fuel consumption.
     */
    public function viewFuelConsumption($user, Vehicle $vehicle): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Admins can view all vehicle fuel data
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_vehicle', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage vehicle fuel logs.
     */
    public function manageFuelLogs($user, Vehicle $vehicle): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can manage fuel logs
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_vehicle', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view vehicle insurance records.
     */
    public function viewInsurance($user, Vehicle $vehicle): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Admins can view all vehicle insurance
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_vehicle', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage vehicle insurance.
     */
    public function manageInsurance($user, Vehicle $vehicle): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can manage vehicle insurance
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_vehicle', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view vehicle registration documents.
     */
    public function viewRegistration($user, Vehicle $vehicle): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Admins can view all vehicle registrations
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_vehicle', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage vehicle registration.
     */
    public function manageRegistration($user, Vehicle $vehicle): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can manage vehicle registration
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_vehicle', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can track vehicle GPS data.
     */
    public function trackGPS($user, Vehicle $vehicle): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Admins can track any vehicle GPS
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_vehicle', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage vehicle GPS settings.
     */
    public function manageGPS($user, Vehicle $vehicle): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can manage GPS settings
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_vehicle', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can assign vehicles to routes.
     */
    public function assignToRoutes($user, Vehicle $vehicle): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Admins can assign any vehicle to routes
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_vehicle', 'admin')) {
            return true;
        }

        // Vendors can assign vehicles to routes
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_vehicle', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view vehicle route history.
     */
    public function viewRouteHistory($user, Vehicle $vehicle): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Admins can view all route history
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_vehicle', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage vehicle capacity settings.
     */
    public function manageCapacity($user, Vehicle $vehicle): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can manage vehicle capacity
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_vehicle', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view vehicle load factors.
     */
    public function viewLoadFactors($user, Vehicle $vehicle): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Admins can view all load factors
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can generate vehicle reports.
     */
    public function generateReports($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Admins can generate vehicle reports
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage vehicle compliance.
     */
    public function manageCompliance($user, Vehicle $vehicle): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can manage vehicle compliance (regulatory)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_vehicle', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can schedule vehicle inspections.
     */
    public function scheduleInspections($user, Vehicle $vehicle): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can schedule vehicle inspections
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_vehicle', 'admin')) {
            return true;
        }

        return false;
    }
}
