<?php

namespace Modules\AddOn\Policies;

use Modules\AddOn\Models\AddOn;
use Modules\Admin\Models\Admin;
use Modules\Vendor\Models\Vendor;
use Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Helpers\PermissionHelper;

class AddOnPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any add-ons.
     */
    public function viewAny($user): bool
    {
        // Admins with permission
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_addon', 'admin')) {
            return true;
        }

        // Vendors with permission
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_addon', 'vendor')) {
            return true;
        }

        // Users can view active add-ons
        if ($user instanceof User) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the add-on.
     */
    public function view($user, AddOn $addOn): bool
    {
        // Admins with permission can view all
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_addon', 'admin')) {
            return true;
        }

        // Vendors can only view their own store's add-ons
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_addon', 'vendor')) {
            return $addOn->store_id === $user->store_id;
        }

        // Users can view active add-ons
        if ($user instanceof User && $addOn->is_active) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create add-ons.
     */
    public function create($user): bool
    {
        // Admins with permission
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_addon', 'admin')) {
            return true;
        }

        // Vendors with permission
        if ($user instanceof Vendor && PermissionHelper::hasPermission('create_addon', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the add-on.
     */
    public function update($user, AddOn $addOn): bool
    {
        // Admins with permission can update all
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_addon', 'admin')) {
            return true;
        }

        // Vendors can only update their own store's add-ons
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_addon', 'vendor')) {
            return $addOn->store_id === $user->store_id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the add-on.
     */
    public function delete($user, AddOn $addOn): bool
    {
        // Admins with permission can delete all
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_addon', 'admin')) {
            return true;
        }

        // Vendors can only delete their own store's add-ons
        if ($user instanceof Vendor && PermissionHelper::hasPermission('delete_addon', 'vendor')) {
            return $addOn->store_id === $user->store_id;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the add-on.
     */
    public function restore($user, AddOn $addOn): bool
    {
        // Same logic as update
        return $this->update($user, $addOn);
    }

    /**
     * Determine whether the user can permanently delete the add-on.
     */
    public function forceDelete($user, AddOn $addOn): bool
    {
        // Same logic as delete
        return $this->delete($user, $addOn);
    }

    /**
     * Determine whether the user can attach products to add-on.
     */
    public function attachProducts($user, AddOn $addOn): bool
    {
        // Same logic as update
        return $this->update($user, $addOn);
    }

    /**
     * Determine whether the user can detach products from add-on.
     */
    public function detachProducts($user, AddOn $addOn): bool
    {
        // Same logic as update
        return $this->update($user, $addOn);
    }

    /**
     * Determine whether the user can view add-on analytics/reports.
     */
    public function viewAnalytics($user, AddOn $addOn): bool
    {
        // Admins with report permission
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        // Vendors can view analytics for their own store's add-ons
        if ($user instanceof Vendor && $addOn->store_id === $user->store_id) {
            return PermissionHelper::hasPermission('view_report', 'vendor');
        }

        return false;
    }

    /**
     * Determine whether the user can manage add-on settings.
     */
    public function manageSettings($user, AddOn $addOn): bool
    {
        // Only admins can manage global settings
        if ($user instanceof Admin) {
            return PermissionHelper::hasPermission('update_addon', 'admin');
        }

        return false;
    }

    /**
     * Determine whether the user can bulk update add-ons.
     */
    public function bulkUpdate($user): bool
    {
        // Admins with update permission
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_addon', 'admin')) {
            return true;
        }

        // Vendors with update permission (limited to their store)
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_addon', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can export add-ons.
     */
    public function export($user): bool
    {
        // Admins with view permission
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_addon', 'admin')) {
            return true;
        }

        // Vendors with view permission
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_addon', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can import add-ons.
     */
    public function import($user): bool
    {
        // Only admins can import (for security)
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_addon', 'admin')) {
            return true;
        }

        return false;
    }
}
