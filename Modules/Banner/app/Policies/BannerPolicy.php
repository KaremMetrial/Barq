<?php

namespace Modules\Banner\Policies;

use Modules\Banner\Models\Banner;
use Modules\Admin\Models\Admin;
use Modules\Vendor\Models\Vendor;
use Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Helpers\PermissionHelper;

class BannerPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any banners.
     */
    public function viewAny($user): bool
    {
        // Admins with permission
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_banner', 'admin')) {
            return true;
        }

        // Vendors with permission
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_banner', 'vendor')) {
            return true;
        }

        // Users can view active banners
        if ($user instanceof User) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the banner.
     */
    public function view($user, Banner $banner): bool
    {
        // Admins with permission can view all banners
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_banner', 'admin')) {
            return true;
        }

        // Vendors can only view banners related to their stores or general banners
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_banner', 'vendor')) {
            // Allow if banner is general (no specific bannerable) or related to vendor's store
            if (!$banner->bannerable_id || $banner->bannerable_type === 'Modules\Store\Models\Store') {
                return $banner->bannerable_id ? $banner->bannerable_id === $user->store_id : true;
            }
            return false;
        }

        // Users can view active banners
        if ($user instanceof User && $banner->is_active) {
            // Check if banner is within date range
            $now = now();
            $isWithinDateRange = (!$banner->start_date || $banner->start_date <= $now) &&
                                (!$banner->end_date || $banner->end_date >= $now);

            return $isWithinDateRange;
        }

        return false;
    }

    /**
     * Determine whether the user can create banners.
     */
    public function create($user): bool
    {
        // Admins with permission
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_banner', 'admin')) {
            return true;
        }

        // Vendors with permission can create banners for their store
        if ($user instanceof Vendor && PermissionHelper::hasPermission('create_banner', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the banner.
     */
    public function update($user, Banner $banner): bool
    {
        // Admins with permission can update all banners
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_banner', 'admin')) {
            return true;
        }

        // Vendors can only update banners related to their store
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_banner', 'vendor')) {
            if ($banner->bannerable_type === 'Modules\Store\Models\Store') {
                return $banner->bannerable_id === $user->store_id;
            }
            // Vendors cannot update general banners or banners for other types
            return false;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the banner.
     */
    public function delete($user, Banner $banner): bool
    {
        // Admins with permission can delete all banners
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_banner', 'admin')) {
            return true;
        }

        // Vendors can only delete banners related to their store
        if ($user instanceof Vendor && PermissionHelper::hasPermission('delete_banner', 'vendor')) {
            if ($banner->bannerable_type === 'Modules\Store\Models\Store') {
                return $banner->bannerable_id === $user->store_id;
            }
            return false;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the banner.
     */
    public function restore($user, Banner $banner): bool
    {
        // Same logic as update
        return $this->update($user, $banner);
    }

    /**
     * Determine whether the user can permanently delete the banner.
     */
    public function forceDelete($user, Banner $banner): bool
    {
        // Same logic as delete
        return $this->delete($user, $banner);
    }

    /**
     * Determine whether the user can attach banner to entities.
     */
    public function attachToEntity($user, Banner $banner): bool
    {
        // Same logic as update
        return $this->update($user, $banner);
    }

    /**
     * Determine whether the user can detach banner from entities.
     */
    public function detachFromEntity($user, Banner $banner): bool
    {
        // Same logic as update
        return $this->update($user, $banner);
    }

    /**
     * Determine whether the user can view banner analytics/reports.
     */
    public function viewAnalytics($user, Banner $banner): bool
    {
        // Admins with report permission
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        // Vendors can view analytics for their own store's banners
        if ($user instanceof Vendor && $banner->bannerable_type === 'Modules\Store\Models\Store') {
            return $banner->bannerable_id === $user->store_id &&
                   PermissionHelper::hasPermission('view_report', 'vendor');
        }

        return false;
    }

    /**
     * Determine whether the user can manage banner settings.
     */
    public function manageSettings($user, Banner $banner): bool
    {
        // Only admins can manage global banner settings
        if ($user instanceof Admin) {
            return PermissionHelper::hasPermission('update_banner', 'admin');
        }

        return false;
    }

    /**
     * Determine whether the user can bulk update banners.
     */
    public function bulkUpdate($user): bool
    {
        // Admins with update permission
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_banner', 'admin')) {
            return true;
        }

        // Vendors with update permission (limited to their store)
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_banner', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk delete banners.
     */
    public function bulkDelete($user): bool
    {
        // Admins with delete permission
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_banner', 'admin')) {
            return true;
        }

        // Vendors with delete permission (limited to their store)
        if ($user instanceof Vendor && PermissionHelper::hasPermission('delete_banner', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can export banners.
     */
    public function export($user): bool
    {
        // Admins with view permission
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_banner', 'admin')) {
            return true;
        }

        // Vendors with view permission
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_banner', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can import banners.
     */
    public function import($user): bool
    {
        // Only admins can import (for security and consistency)
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_banner', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can schedule banners.
     */
    public function schedule($user, Banner $banner): bool
    {
        // Same logic as update (scheduling is part of banner management)
        return $this->update($user, $banner);
    }

    /**
     * Determine whether the user can activate/deactivate banners.
     */
    public function toggleActive($user, Banner $banner): bool
    {
        // Same logic as update
        return $this->update($user, $banner);
    }

    /**
     * Determine whether the user can duplicate banners.
     */
    public function duplicate($user, Banner $banner): bool
    {
        // Same logic as create (duplicating requires create permission)
        return $this->create($user);
    }

    /**
     * Determine whether the user can reorder banners.
     */
    public function reorder($user): bool
    {
        // Admins with update permission
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_banner', 'admin')) {
            return true;
        }

        // Vendors with update permission
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_banner', 'vendor')) {
            return true;
        }

        return false;
    }
}
