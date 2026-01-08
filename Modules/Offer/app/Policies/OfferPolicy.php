<?php

namespace Modules\Offer\Policies;

use Modules\Offer\Models\Offer;
use Modules\Admin\Models\Admin;
use Modules\Vendor\Models\Vendor;
use Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Helpers\PermissionHelper;

class OfferPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any offers.
     */
    public function viewAny($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can view all offers
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_offer', 'admin')) {
            return true;
        }

        // Vendors can view offers related to their stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_offer', 'vendor')) {
            return true;
        }

        // Users can view active offers
        if ($user instanceof User) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the offer.
     */
    public function view($user, Offer $offer): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can view all offers
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_offer', 'admin')) {
            return true;
        }

        // Vendors can view offers related to their stores or products
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_offer', 'vendor')) {
            // Check if offer is on vendor's store
            if ($offer->offerable_type === 'Modules\Store\Models\Store' && $offer->offerable_id === $user->store_id) {
                return true;
            }
            // Check if offer is on vendor's products
            if ($offer->offerable_type === 'Modules\Product\Models\Product') {
                $product = $offer->offerable;
                if ($product && $product->store_id === $user->store_id) {
                    return true;
                }
            }
            return false;
        }

        // Users can view active offers
        if ($user instanceof User && $offer->is_active && $offer->status === \App\Enums\OfferStatusEnum::ACTIVE) {
            // Check if offer is within date range
            $now = now();
            $isWithinDateRange = (!$offer->start_date || $offer->start_date <= $now) &&
                                (!$offer->end_date || $offer->end_date >= $now);

            return $isWithinDateRange;
        }

        return false;
    }

    /**
     * Determine whether the user can create offers.
     */
    public function create($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can create offers
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_offer', 'admin')) {
            return true;
        }

        // Vendors can create offers for their stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('create_offer', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the offer.
     */
    public function update($user, Offer $offer): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can update all offers
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_offer', 'admin')) {
            return true;
        }

        // Vendors can only update offers related to their stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_offer', 'vendor')) {
            // Check if offer is on vendor's store
            if ($offer->offerable_type === 'Modules\Store\Models\Store' && $offer->offerable_id === $user->store_id) {
                return true;
            }
            // Check if offer is on vendor's products
            if ($offer->offerable_type === 'Modules\Product\Models\Product') {
                $product = $offer->offerable;
                if ($product && $product->store_id === $user->store_id) {
                    return true;
                }
            }
            return false;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the offer.
     */
    public function delete($user, Offer $offer): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can delete any offer
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_offer', 'admin')) {
            return true;
        }

        // Vendors can only delete offers from their stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('delete_offer', 'vendor')) {
            // Check if offer is on vendor's store
            if ($offer->offerable_type === 'Modules\Store\Models\Store' && $offer->offerable_id === $user->store_id) {
                return true;
            }
            return false;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the offer.
     */
    public function restore($user, Offer $offer): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as update
        return $this->update($user, $offer);
    }

    /**
     * Determine whether the user can permanently delete the offer.
     */
    public function forceDelete($user, Offer $offer): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as delete
        return $this->delete($user, $offer);
    }

    /**
     * Determine whether the user can apply the offer.
     */
    public function apply($user, Offer $offer): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Users can apply valid offers
        if ($user instanceof User && $offer->is_active && $offer->status === \App\Enums\OfferStatusEnum::ACTIVE) {
            // Check date range
            $now = now();
            $isWithinDateRange = (!$offer->start_date || $offer->start_date <= $now) &&
                                (!$offer->end_date || $offer->end_date >= $now);

            // Check stock limit if applicable
            if ($offer->has_stock_limit && $offer->stock_limit !== null) {
                // You might want to check actual usage here
                // For now, assume it's valid if within limits
            }

            return $isWithinDateRange;
        }

        return false;
    }

    /**
     * Determine whether the user can manage offer relationships.
     */
    public function manageRelationships($user, Offer $offer): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as update
        return $this->update($user, $offer);
    }

    /**
     * Determine whether the user can configure offer discount settings.
     */
    public function configureDiscount($user, Offer $offer): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as update (discount changes affect pricing)
        return $this->update($user, $offer);
    }

    /**
     * Determine whether the user can manage offer scheduling.
     */
    public function manageSchedule($user, Offer $offer): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as update
        return $this->update($user, $offer);
    }

    /**
     * Determine whether the user can create flash sales.
     */
    public function createFlashSale($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can create flash sales
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_offer', 'admin')) {
            return true;
        }

        // Vendors can create flash sales for their stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('create_offer', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage offer stock limits.
     */
    public function manageStockLimits($user, Offer $offer): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as update
        return $this->update($user, $offer);
    }

    /**
     * Determine whether the user can view offer analytics/reports.
     */
    public function viewAnalytics($user, Offer $offer): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can view all offer analytics
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        // Vendors can view analytics for their store's offers
        if ($user instanceof Vendor) {
            if ($offer->offerable_type === 'Modules\Store\Models\Store' && $offer->offerable_id === $user->store_id) {
                return PermissionHelper::hasPermission('view_report', 'vendor');
            }
            return false;
        }

        return false;
    }

    /**
     * Determine whether the user can activate/deactivate offers.
     */
    public function toggleActive($user, Offer $offer): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as update
        return $this->update($user, $offer);
    }

    /**
     * Determine whether the user can duplicate offers.
     */
    public function duplicate($user, Offer $offer): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as create
        return $this->create($user);
    }

    /**
     * Determine whether the user can export offer data.
     */
    public function export($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can export all offer data
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_offer', 'admin')) {
            return true;
        }

        // Vendors can export data for their store's offers
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_offer', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can import offer data.
     */
    public function import($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can import offer data
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_offer', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk update offers.
     */
    public function bulkUpdate($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can bulk update all offers
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_offer', 'admin')) {
            return true;
        }

        // Vendors can bulk update their store's offers
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_offer', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk delete offers.
     */
    public function bulkDelete($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can bulk delete any offers
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_offer', 'admin')) {
            return true;
        }

        // Vendors can bulk delete their store's offers
        if ($user instanceof Vendor && PermissionHelper::hasPermission('delete_offer', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view offer performance metrics.
     */
    public function viewPerformance($user, Offer $offer): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as viewAnalytics
        return $this->viewAnalytics($user, $offer);
    }

    /**
     * Determine whether the user can manage offer status.
     */
    public function manageStatus($user, Offer $offer): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as update
        return $this->update($user, $offer);
    }

    /**
     * Determine whether the user can extend offer validity period.
     */
    public function extendValidity($user, Offer $offer): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as update
        return $this->update($user, $offer);
    }

    /**
     * Determine whether the user can view offer usage statistics.
     */
    public function viewUsage($user, Offer $offer): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as viewAnalytics
        return $this->viewAnalytics($user, $offer);
    }

    /**
     * Determine whether the user can modify offer discount amounts.
     */
    public function modifyDiscountAmount($user, Offer $offer): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as update (affects pricing)
        return $this->update($user, $offer);
    }

    /**
     * Determine whether the user can change offer discount types.
     */
    public function changeDiscountType($user, Offer $offer): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as update (affects pricing calculations)
        return $this->update($user, $offer);
    }

    /**
     * Determine whether the user can view offer financial impact.
     */
    public function viewFinancialImpact($user, Offer $offer): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can view financial impact
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        // Vendors can view financial impact of their offers
        if ($user instanceof Vendor) {
            if ($offer->offerable_type === 'Modules\Store\Models\Store' && $offer->offerable_id === $user->store_id) {
                return PermissionHelper::hasPermission('view_report', 'vendor');
            }
            return false;
        }

        return false;
    }

    /**
     * Determine whether the user can create promotional campaigns.
     */
    public function createCampaign($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can create campaigns
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_offer', 'admin')) {
            return true;
        }

        // Vendors can create campaigns for their stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('create_offer', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage offer targeting.
     */
    public function manageTargeting($user, Offer $offer): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as update (targeting affects who sees the offer)
        return $this->update($user, $offer);
    }

    /**
     * Determine whether the user can view offer statistics.
     */
    public function viewStatistics($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can view offer statistics
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can reset offer usage counters.
     */
    public function resetUsage($user, Offer $offer): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can reset usage counters
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_offer', 'admin')) {
            return true;
        }

        return false;
    }
}
