<?php

namespace Modules\Coupon\Policies;

use Modules\Coupon\Models\Coupon;
use Modules\Admin\Models\Admin;
use Modules\Vendor\Models\Vendor;
use Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Helpers\PermissionHelper;

class CouponPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any coupons.
     */
    public function viewAny($user): bool
    {
        // Admins can view all coupons
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_coupon', 'admin')) {
            return true;
        }

        // Vendors can view coupons related to their stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_coupon', 'vendor')) {
            return true;
        }

        // Users can view valid coupons
        if ($user instanceof User) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the coupon.
     */
    public function view($user, Coupon $coupon): bool
    {
        // Admins can view all coupons
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_coupon', 'admin')) {
            return true;
        }

        // Vendors can view coupons related to their stores or products
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_coupon', 'vendor')) {
            // Check if coupon is related to vendor's store
            if ($coupon->stores()->where('stores.id', $user->store_id)->exists()) {
                return true;
            }
            // Check if coupon is related to vendor's products
            if ($coupon->products()->where('products.store_id', $user->store_id)->exists()) {
                return true;
            }
            return false;
        }

        // Users can view valid, active coupons
        if ($user instanceof User && $coupon->isValid()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create coupons.
     */
    public function create($user): bool
    {
        // Admins can create coupons
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_coupon', 'admin')) {
            return true;
        }

        // Vendors can create coupons for their stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('create_coupon', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the coupon.
     */
    public function update($user, Coupon $coupon): bool
    {
        // Admins can update all coupons
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_coupon', 'admin')) {
            return true;
        }

        // Vendors can only update coupons related to their stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_coupon', 'vendor')) {
            // Check store relationship
            if ($coupon->stores()->where('stores.id', $user->store_id)->exists()) {
                return true;
            }
            // Check product relationship
            if ($coupon->products()->where('products.store_id', $user->store_id)->exists()) {
                return true;
            }
            return false;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the coupon.
     */
    public function delete($user, Coupon $coupon): bool
    {
        // Admins can delete any coupon
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_coupon', 'admin')) {
            return true;
        }

        // Vendors can only delete coupons from their stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('delete_coupon', 'vendor')) {
            // Check store relationship
            if ($coupon->stores()->where('stores.id', $user->store_id)->exists()) {
                return true;
            }
            return false;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the coupon.
     */
    public function restore($user, Coupon $coupon): bool
    {
        // Same logic as update
        return $this->update($user, $coupon);
    }

    /**
     * Determine whether the user can permanently delete the coupon.
     */
    public function forceDelete($user, Coupon $coupon): bool
    {
        // Same logic as delete
        return $this->delete($user, $coupon);
    }

    /**
     * Determine whether the user can apply the coupon.
     */
    public function apply($user, Coupon $coupon): bool
    {
        // Users can apply valid coupons
        if ($user instanceof User && $coupon->isValid()) {
            // Check usage limits
            if ($coupon->usage_limit && $coupon->usage_count >= $coupon->usage_limit) {
                return false;
            }

            // Check per-user limit
            if ($coupon->usage_limit_per_user && $coupon->getUserUsageCount($user->id) >= $coupon->usage_limit_per_user) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage coupon categories.
     */
    public function manageCategories($user, Coupon $coupon): bool
    {
        // Same logic as update
        return $this->update($user, $coupon);
    }

    /**
     * Determine whether the user can manage coupon products.
     */
    public function manageProducts($user, Coupon $coupon): bool
    {
        // Same logic as update
        return $this->update($user, $coupon);
    }

    /**
     * Determine whether the user can manage coupon stores.
     */
    public function manageStores($user, Coupon $coupon): bool
    {
        // Admins can manage all store relationships
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_coupon', 'admin')) {
            return true;
        }

        // Vendors can only manage their own store relationships
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_coupon', 'vendor')) {
            return $coupon->stores()->where('stores.id', $user->store_id)->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can view coupon usage statistics.
     */
    public function viewUsage($user, Coupon $coupon): bool
    {
        // Admins can view all usage statistics
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_coupon', 'admin')) {
            return true;
        }

        // Vendors can view usage for their store's coupons
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_coupon', 'vendor')) {
            if ($coupon->stores()->where('stores.id', $user->store_id)->exists()) {
                return true;
            }
            return false;
        }

        return false;
    }

    /**
     * Determine whether the user can view coupon analytics/reports.
     */
    public function viewAnalytics($user, Coupon $coupon): bool
    {
        // Admins can view all coupon analytics
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        // Vendors can view analytics for their store's coupons
        if ($user instanceof Vendor) {
            if ($coupon->stores()->where('stores.id', $user->store_id)->exists()) {
                return PermissionHelper::hasPermission('view_report', 'vendor');
            }
            return false;
        }

        return false;
    }

    /**
     * Determine whether the user can activate/deactivate coupons.
     */
    public function toggleActive($user, Coupon $coupon): bool
    {
        // Same logic as update
        return $this->update($user, $coupon);
    }

    /**
     * Determine whether the user can duplicate coupons.
     */
    public function duplicate($user, Coupon $coupon): bool
    {
        // Same logic as create
        return $this->create($user);
    }

    /**
     * Determine whether the user can export coupon data.
     */
    public function export($user): bool
    {
        // Admins can export all coupon data
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_coupon', 'admin')) {
            return true;
        }

        // Vendors can export data for their store's coupons
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_coupon', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can import coupon data.
     */
    public function import($user): bool
    {
        // Admins can import coupon data
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_coupon', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk update coupons.
     */
    public function bulkUpdate($user): bool
    {
        // Admins can bulk update all coupons
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_coupon', 'admin')) {
            return true;
        }

        // Vendors can bulk update their store's coupons
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_coupon', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk delete coupons.
     */
    public function bulkDelete($user): bool
    {
        // Admins can bulk delete any coupons
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_coupon', 'admin')) {
            return true;
        }

        // Vendors can bulk delete their store's coupons
        if ($user instanceof Vendor && PermissionHelper::hasPermission('delete_coupon', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view coupon performance metrics.
     */
    public function viewPerformance($user, Coupon $coupon): bool
    {
        // Same logic as viewAnalytics
        return $this->viewAnalytics($user, $coupon);
    }

    /**
     * Determine whether the user can manage coupon rewards.
     */
    public function manageRewards($user, Coupon $coupon): bool
    {
        // Admins can manage coupon rewards
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_coupon', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can validate coupon codes.
     */
    public function validateCode($user): bool
    {
        // Any authenticated user can validate coupon codes
        if ($user) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can reset coupon usage counters.
     */
    public function resetUsage($user, Coupon $coupon): bool
    {
        // Admins can reset usage counters
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_coupon', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can extend coupon validity period.
     */
    public function extendValidity($user, Coupon $coupon): bool
    {
        // Same logic as update
        return $this->update($user, $coupon);
    }

    /**
     * Determine whether the user can modify coupon discount settings.
     */
    public function modifyDiscount($user, Coupon $coupon): bool
    {
        // Same logic as update (discount changes are sensitive)
        return $this->update($user, $coupon);
    }

    /**
     * Determine whether the user can view coupon financial impact.
     */
    public function viewFinancialImpact($user, Coupon $coupon): bool
    {
        // Admins can view financial impact
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        // Vendors can view financial impact of their coupons
        if ($user instanceof Vendor) {
            if ($coupon->stores()->where('stores.id', $user->store_id)->exists()) {
                return PermissionHelper::hasPermission('view_report', 'vendor');
            }
            return false;
        }

        return false;
    }
}
