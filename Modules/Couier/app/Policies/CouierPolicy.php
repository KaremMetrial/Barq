<?php

namespace Modules\Couier\Policies;

use Modules\Couier\Models\Couier;
use Modules\Admin\Models\Admin;
use Modules\Vendor\Models\Vendor;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Helpers\PermissionHelper;

class CouierPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any couriers.
     */
    public function viewAny($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can view all couriers
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_couier', 'admin')) {
            return true;
        }

        // Vendors can view couriers from their store
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_couier', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the courier.
     */
    public function view($user, Couier $couier): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can view all couriers
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_couier', 'admin')) {
            return true;
        }

        // Vendors can only view couriers from their store
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_couier', 'vendor')) {
            return $couier->store_id === $user->store_id;
        }

        // Couriers can view their own profile
        if ($couier instanceof Couier && $couier->id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create couriers.
     */
    public function create($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can create couriers
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_couier', 'admin')) {
            return true;
        }

        // Vendors can create couriers for their store
        if ($user instanceof Vendor && PermissionHelper::hasPermission('create_couier', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the courier.
     */
    public function update($user, Couier $couier): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can update all couriers
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_couier', 'admin')) {
            return true;
        }

        // Vendors can only update couriers from their store
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_couier', 'vendor')) {
            return $couier->store_id === $user->store_id;
        }

        // Couriers can update their own basic profile info
        if ($couier instanceof Couier && $couier->id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the courier.
     */
    public function delete($user, Couier $couier): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Only admins can delete couriers (sensitive operation)
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_couier', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the courier.
     */
    public function restore($user, Couier $couier): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as update
        return $this->update($user, $couier);
    }

    /**
     * Determine whether the user can permanently delete the courier.
     */
    public function forceDelete($user, Couier $couier): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as delete
        return $this->delete($user, $couier);
    }

    /**
     * Determine whether the user can manage courier shifts.
     */
    public function manageShifts($user, Couier $couier): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can manage all courier shifts
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_couier', 'admin')) {
            return true;
        }

        // Vendors can manage shifts for their store's couriers
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_couier', 'vendor')) {
            return $couier->store_id === $user->store_id;
        }

        return false;
    }

    /**
     * Determine whether the user can view courier shifts.
     */
    public function viewShifts($user, Couier $couier): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as view courier
        return $this->view($user, $couier);
    }

    /**
     * Determine whether the user can manage courier vehicles.
     */
    public function manageVehicles($user, Couier $couier): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can manage all courier vehicles
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_couier', 'admin')) {
            return true;
        }

        // Vendors can manage vehicles for their store's couriers
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_couier', 'vendor')) {
            return $couier->store_id === $user->store_id;
        }

        // Couriers can view their own vehicle info
        if ($couier instanceof Couier && $couier->id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view courier orders.
     */
    public function viewOrders($user, Couier $couier): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can view all courier orders
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_order', 'admin')) {
            return true;
        }

        // Vendors can view orders for their store's couriers
        if ($user instanceof Vendor) {
            return $couier->store_id === $user->store_id;
        }

        // Couriers can view their own orders
        if ($couier instanceof Couier && $couier->id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage courier assignments.
     */
    public function manageAssignments($user, Couier $couier): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can manage all assignments
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_couier', 'admin')) {
            return true;
        }

        // Vendors can manage assignments for their store's couriers
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_couier', 'vendor')) {
            return $couier->store_id === $user->store_id;
        }

        return false;
    }

    /**
     * Determine whether the user can view courier location.
     */
    public function viewLocation($user, Couier $couier): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can view all courier locations
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_couier', 'admin')) {
            return true;
        }

        // Vendors can view locations of their store's couriers
        if ($user instanceof Vendor) {
            return $couier->store_id === $user->store_id;
        }

        // Couriers can view their own location (but usually they update it)
        if ($couier instanceof Couier && $couier->id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update courier location.
     */
    public function updateLocation($user, Couier $couier): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Couriers can update their own location
        if ($couier instanceof Couier && $couier->id === $user->id) {
            return true;
        }

        // Admins can update any courier location
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_couier', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view courier performance/analytics.
     */
    public function viewAnalytics($user, Couier $couier): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can view all courier analytics
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        // Vendors can view analytics for their store's couriers
        if ($user instanceof Vendor) {
            return $couier->store_id === $user->store_id;
        }

        // Couriers can view their own analytics
        if ($couier instanceof Couier && $couier->id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage courier zones.
     */
    public function manageZones($user, Couier $couier): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can manage all courier zones
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_couier', 'admin')) {
            return true;
        }

        // Vendors can manage zones for their store's couriers
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_couier', 'vendor')) {
            return $couier->store_id === $user->store_id;
        }

        return false;
    }

    /**
     * Determine whether the user can view courier balance/payments.
     */
    public function viewBalance($user, Couier $couier): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can view all courier balances
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_couier', 'admin')) {
            return true;
        }

        // Vendors can view balances of their store's couriers
        if ($user instanceof Vendor) {
            return $couier->store_id === $user->store_id;
        }

        // Couriers can view their own balance
        if ($couier instanceof Couier && $couier->id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage courier balance/payments.
     */
    public function manageBalance($user, Couier $couier): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can manage all courier balances
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_couier', 'admin')) {
            return true;
        }

        // Vendors can manage balances of their store's couriers
        if ($user instanceof Vendor) {
            return $couier->store_id === $user->store_id;
        }

        return false;
    }

    /**
     * Determine whether the user can view courier documents.
     */
    public function viewDocuments($user, Couier $couier): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can view all courier documents
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_couier', 'admin')) {
            return true;
        }

        // Vendors can view documents of their store's couriers
        if ($user instanceof Vendor) {
            return $couier->store_id === $user->store_id;
        }

        // Couriers can view their own documents
        if ($couier instanceof Couier && $couier->id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage courier documents.
     */
    public function manageDocuments($user, Couier $couier): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can manage all courier documents
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_couier', 'admin')) {
            return true;
        }

        // Vendors can manage documents of their store's couriers
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_couier', 'vendor')) {
            return $couier->store_id === $user->store_id;
        }

        // Couriers can manage their own documents
        if ($couier instanceof Couier && $couier->id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can export courier data.
     */
    public function export($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can export courier data
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_couier', 'admin')) {
            return true;
        }

        // Vendors can export data for their store's couriers
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_couier', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk update couriers.
     */
    public function bulkUpdate($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can bulk update couriers
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_couier', 'admin')) {
            return true;
        }

        // Vendors can bulk update their store's couriers
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_couier', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can rate/review couriers.
     */
    public function rateCourier($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Any authenticated user can rate couriers (after receiving service)
        if ($user) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view courier ratings.
     */
    public function viewRatings($user, Couier $couier): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Everyone can view courier ratings (public info)
        return true;
    }

    /**
     * Determine whether the user can manage courier status.
     */
    public function manageStatus($user, Couier $couier): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can manage all courier statuses
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_couier', 'admin')) {
            return true;
        }

        // Vendors can manage status of their store's couriers
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_couier', 'vendor')) {
            return $couier->store_id === $user->store_id;
        }

        return false;
    }

    /**
     * Determine whether the user can manage courier availability.
     */
    public function manageAvailability($user, Couier $couier): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can manage all courier availability
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_couier', 'admin')) {
            return true;
        }

        // Vendors can manage availability of their store's couriers
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_couier', 'vendor')) {
            return $couier->store_id === $user->store_id;
        }

        // Couriers can manage their own availability
        if ($couier instanceof Couier && $couier->id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can assign orders to couriers.
     */
    public function assignOrders($user, Couier $couier): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can assign orders to any courier
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_order', 'admin')) {
            return true;
        }

        // Vendors can assign orders to their store's couriers
        if ($user instanceof Vendor) {
            return $couier->store_id === $user->store_id;
        }

        return false;
    }
}
