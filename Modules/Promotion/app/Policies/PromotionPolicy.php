<?php

namespace Modules\Promotion\Policies;

use Modules\Promotion\Models\Promotion;
use Modules\Admin\Models\Admin;
use Modules\Vendor\Models\Vendor;
use Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Helpers\PermissionHelper;

class PromotionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any promotions.
     */
    public function viewAny($user): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins with permission
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_promotion', 'admin')) {
            return true;
        }

        // Vendors with permission
        if ($user instanceof Vendor) {
            return true;
        }

        // Users can view active promotions
        if ($user instanceof User) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the promotion.
     */
    public function view($user, Promotion $promotion): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins with permission can view all promotions
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_promotion', 'admin')) {
            return true;
        }

        // Vendors with permission can view promotions for their regions/stores
        if ($user instanceof Vendor) {
            // Check if promotion targets vendor's regions or is general
            return $this->isPromotionAccessibleToVendor($promotion, $user);
        }

        // Users can view active promotions
        if ($user instanceof User && $promotion->is_active) {
            // Check if promotion is within date range
            $now = now();
            $isWithinDateRange = (!$promotion->start_date || $promotion->start_date <= $now) &&
                                (!$promotion->end_date || $promotion->end_date >= $now);

            return $isWithinDateRange;
        }

        return false;
    }

    /**
     * Determine whether the user can create promotions.
     */
    public function create($user): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins with permission
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_promotion', 'admin')) {
            return true;
        }

        // Vendors with permission
        if ($user instanceof Vendor) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the promotion.
     */
    public function update($user, Promotion $promotion): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins with permission can update all promotions
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_promotion', 'admin')) {
            return true;
        }

        // Vendors with permission can update promotions for their regions
        if ($user instanceof Vendor) {
            return $this->isPromotionAccessibleToVendor($promotion, $user);
        }

        return false;
    }

    /**
     * Determine whether the user can delete the promotion.
     */
    public function delete($user, Promotion $promotion): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins with permission can delete all promotions
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_promotion', 'admin')) {
            return true;
        }

        // Vendors with permission can delete promotions for their regions
        if ($user instanceof Vendor) {
            return $this->isPromotionAccessibleToVendor($promotion, $user);
        }

        return false;
    }

    /**
     * Determine whether the user can restore the promotion.
     */
    public function restore($user, Promotion $promotion): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as update
        return $this->update($user, $promotion);
    }

    /**
     * Determine whether the user can permanently delete the promotion.
     */
    public function forceDelete($user, Promotion $promotion): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as delete
        return $this->delete($user, $promotion);
    }

    /**
     * Determine whether the user can activate/deactivate promotions.
     */
    public function toggleActive($user, Promotion $promotion): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins with permission can activate/deactivate all promotions
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_promotion', 'admin')) {
            return true;
        }

        // Vendors with permission can activate/deactivate promotions for their regions
        if ($user instanceof Vendor) {
            return $this->isPromotionAccessibleToVendor($promotion, $user);
        }

        return false;
    }

    /**
     * Determine whether the user can validate promotion rules.
     */
    public function validatePromotion($user, Promotion $promotion): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins with permission can validate all promotions
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_promotion', 'admin')) {
            return true;
        }

        // Vendors with permission can validate promotions for their regions
        if ($user instanceof Vendor) {
            return $this->isPromotionAccessibleToVendor($promotion, $user);
        }

        return false;
    }

    /**
     * Determine whether the user can view promotion usage statistics.
     */
    public function viewUsage($user, Promotion $promotion): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins with report permission
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        // Vendors can view usage for promotions in their regions
        if ($user instanceof Vendor) {
            return $this->isPromotionAccessibleToVendor($promotion, $user);
        }

        return false;
    }

    /**
     * Determine whether the user can manage promotion targeting rules.
     */
    public function manageTargets($user, Promotion $promotion): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins with permission can manage all targeting rules
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_promotion', 'admin')) {
            return true;
        }

        // Vendors with permission can manage targeting for their promotions
        if ($user instanceof Vendor) {
            return $this->isPromotionAccessibleToVendor($promotion, $user);
        }

        return false;
    }

    /**
     * Determine whether the user can export promotion usage data.
     */
    public function exportUsage($user): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins with report permission
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        // Vendors with report permission
        if ($user instanceof Vendor) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage promotion types.
     */
    public function manageTypes($user): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Only admins can manage promotion types (business rules)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_promotion', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk update promotions.
     */
    public function bulkUpdate($user): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins with update permission
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_promotion', 'admin')) {
            return true;
        }

        // Vendors with update permission
        if ($user instanceof Vendor) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk delete promotions.
     */
    public function bulkDelete($user): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins with delete permission
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_promotion', 'admin')) {
            return true;
        }

        // Vendors with delete permission
        if ($user instanceof Vendor) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view promotion analytics.
     */
    public function viewAnalytics($user): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins with report permission
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        // Vendors with report permission
        if ($user instanceof Vendor) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage promotion currency settings.
     */
    public function manageCurrency($user, Promotion $promotion): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins with permission can manage currency settings
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_promotion', 'admin')) {
            return true;
        }

        // Vendors can only manage currency for promotions in their regions
        if ($user instanceof Vendor) {
            return $this->isPromotionAccessibleToVendor($promotion, $user);
        }

        return false;
    }

    /**
     * Determine whether the user can override promotion limits.
     */
    public function overrideLimits($user, Promotion $promotion): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Only admins can override limits (emergency situations)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_promotion', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Helper method to check if promotion is accessible to vendor based on geographic targeting.
     */
    protected function isPromotionAccessibleToVendor(Promotion $promotion, Vendor $vendor): bool
    {
        // If vendor has full access (super vendor or admin-level vendor)
        if (PermissionHelper::isSuperAdmin('vendor')) {
            return true;
        }

        // Check if promotion has no specific targeting (general promotion)
        if (!$promotion->country_id && !$promotion->governorate_id && !$promotion->city_id && !$promotion->zone_id) {
            return true;
        }

        // Check if vendor's store matches promotion targeting
        if ($vendor->store) {
            $store = $vendor->store;

            // Check country level
            if ($promotion->country_id && $store->country_id !== $promotion->country_id) {
                return false;
            }

            // Check governorate level
            if ($promotion->governorate_id && $store->governorate_id !== $promotion->governorate_id) {
                return false;
            }

            // Check city level
            if ($promotion->city_id && $store->city_id !== $promotion->city_id) {
                return false;
            }

            // Check zone level
            if ($promotion->zone_id && $store->zone_id !== $promotion->zone_id) {
                return false;
            }

            return true;
        }

        return false;
    }
}