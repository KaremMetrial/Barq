<?php

namespace Modules\Store\Policies;

use Modules\Store\Models\Store;
use Modules\Admin\Models\Admin;
use Modules\Vendor\Models\Vendor;
use Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Helpers\PermissionHelper;

class StorePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any stores.
     */
    public function viewAny($user): bool
    {
        // Everyone can view stores (public marketplace)
        return true;
    }

    /**
     * Determine whether the user can view the store.
     */
    public function view($user, Store $store): bool
    {
        // Admins can view all stores
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_store', 'admin')) {
            return true;
        }

        // Vendors can view their own stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_store', 'vendor')) {
            return $store->owner && $store->owner->id === $user->id;
        }

        // Users can view active, approved stores
        if ($user instanceof User) {
            return $store->is_active && $store->status === \App\Enums\StoreStatusEnum::APPROVED;
        }

        return false;
    }

    /**
     * Determine whether the user can create stores.
     */
    public function create($user): bool
    {
        // Admins can create stores for any vendor
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_store', 'admin')) {
            return true;
        }

        // Vendors can create their own stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('create_store', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the store.
     */
    public function update($user, Store $store): bool
    {
        // Admins can update any store
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_store', 'admin')) {
            return true;
        }

        // Vendors can update their own stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_store', 'vendor')) {
            return $store->owner && $store->owner->id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the store.
     */
    public function delete($user, Store $store): bool
    {
        // Only admins can delete stores (significant business impact)
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_store', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the store.
     */
    public function restore($user, Store $store): bool
    {
        // Same logic as update
        return $this->update($user, $store);
    }

    /**
     * Determine whether the user can permanently delete the store.
     */
    public function forceDelete($user, Store $store): bool
    {
        // Same logic as delete
        return $this->delete($user, $store);
    }

    /**
     * Determine whether the user can manage store products.
     */
    public function manageProducts($user, Store $store): bool
    {
        // Admins can manage any store's products
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_store', 'admin')) {
            return true;
        }

        // Vendors can manage their own store's products
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_store', 'vendor')) {
            return $store->owner && $store->owner->id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can manage store categories.
     */
    public function manageCategories($user, Store $store): bool
    {
        // Same logic as manageProducts
        return $this->manageProducts($user, $store);
    }

    /**
     * Determine whether the user can manage store orders.
     */
    public function manageOrders($user, Store $store): bool
    {
        // Admins can manage any store's orders
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_order', 'admin')) {
            return true;
        }

        // Vendors can manage their own store's orders
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_order', 'vendor')) {
            return $store->owner && $store->owner->id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can manage store settings.
     */
    public function manageSettings($user, Store $store): bool
    {
        // Admins can manage any store's settings
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_store', 'admin')) {
            return true;
        }

        // Vendors can manage their own store's settings
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_store', 'vendor')) {
            return $store->owner && $store->owner->id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can manage store working hours.
     */
    public function manageWorkingHours($user, Store $store): bool
    {
        // Same logic as update
        return $this->update($user, $store);
    }

    /**
     * Determine whether the user can manage store delivery zones.
     */
    public function manageDeliveryZones($user, Store $store): bool
    {
        // Admins can manage any store's delivery zones
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_store', 'admin')) {
            return true;
        }

        // Vendors can manage their own store's delivery zones
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_store', 'vendor')) {
            return $store->owner && $store->owner->id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can manage store vendors.
     */
    public function manageVendors($user, Store $store): bool
    {
        // Admins can manage any store's vendors
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_store', 'admin')) {
            return true;
        }

        // Store owners can manage their store's vendors
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_store', 'vendor')) {
            return $store->owner && $store->owner->id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can manage store couriers.
     */
    public function manageCouriers($user, Store $store): bool
    {
        // Same logic as manageVendors
        return $this->manageVendors($user, $store);
    }

    /**
     * Determine whether the user can manage store branches.
     */
    public function manageBranches($user, Store $store): bool
    {
        // Admins can manage any store's branches
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_store', 'admin')) {
            return true;
        }

        // Store owners can manage their store's branches
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_store', 'vendor')) {
            return $store->owner && $store->owner->id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can activate/deactivate stores.
     */
    public function toggleActive($user, Store $store): bool
    {
        // Admins can activate/deactivate any store
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_store', 'admin')) {
            return true;
        }

        // Vendors can activate/deactivate their own stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_store', 'vendor')) {
            return $store->owner && $store->owner->id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can feature/unfeature stores.
     */
    public function toggleFeatured($user, Store $store): bool
    {
        // Only admins can feature/unfeature stores (platform-level decision)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_store', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can approve/reject store applications.
     */
    public function approve($user, Store $store): bool
    {
        // Only admins can approve/reject store applications
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_store', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage store commissions.
     */
    public function manageCommissions($user, Store $store): bool
    {
        // Only admins can manage store commissions (financial impact)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_store', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage store balances.
     */
    public function manageBalances($user, Store $store): bool
    {
        // Admins can manage any store's balances
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_store', 'admin')) {
            return true;
        }

        // Vendors can view their own store's balances
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_store', 'vendor')) {
            return $store->owner && $store->owner->id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can manage store withdrawals.
     */
    public function manageWithdrawals($user, Store $store): bool
    {
        // Admins can manage any store's withdrawals
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_store', 'admin')) {
            return true;
        }

        // Vendors can manage their own store's withdrawals
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_store', 'vendor')) {
            return $store->owner && $store->owner->id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can duplicate stores.
     */
    public function duplicate($user, Store $store): bool
    {
        // Same logic as create
        return $this->create($user);
    }

    /**
     * Determine whether the user can export store data.
     */
    public function export($user): bool
    {
        // Admins can export all store data
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_store', 'admin')) {
            return true;
        }

        // Vendors can export their own store data
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_store', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can import store data.
     */
    public function import($user): bool
    {
        // Only admins can import store data
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_store', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk update stores.
     */
    public function bulkUpdate($user): bool
    {
        // Admins can bulk update all stores
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_store', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk delete stores.
     */
    public function bulkDelete($user): bool
    {
        // Only admins can bulk delete stores
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_store', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view store analytics.
     */
    public function viewAnalytics($user, Store $store): bool
    {
        // Admins can view all store analytics
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        // Vendors can view their own store analytics
        if ($user instanceof Vendor) {
            if ($store->owner && $store->owner->id === $user->id) {
                return PermissionHelper::hasPermission('view_report', 'vendor');
            }
            return false;
        }

        return false;
    }

    /**
     * Determine whether the user can view store reviews.
     */
    public function viewReviews($user, Store $store): bool
    {
        // Everyone can view store reviews (public content)
        return true;
    }

    /**
     * Determine whether the user can moderate store reviews.
     */
    public function moderateReviews($user, Store $store): bool
    {
        // Admins can moderate any store reviews
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_store', 'admin')) {
            return true;
        }

        // Vendors can moderate reviews for their own stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_store', 'vendor')) {
            return $store->owner && $store->owner->id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can manage store offers.
     */
    public function manageOffers($user, Store $store): bool
    {
        // Same logic as manageProducts
        return $this->manageProducts($user, $store);
    }

    /**
     * Determine whether the user can manage store coupons.
     */
    public function manageCoupons($user, Store $store): bool
    {
        // Same logic as manageProducts
        return $this->manageProducts($user, $store);
    }

    /**
     * Determine whether the user can manage store add-ons.
     */
    public function manageAddOns($user, Store $store): bool
    {
        // Same logic as manageProducts
        return $this->manageProducts($user, $store);
    }

    /**
     * Determine whether the user can manage store POS terminals.
     */
    public function managePosTerminals($user, Store $store): bool
    {
        // Same logic as update
        return $this->update($user, $store);
    }

    /**
     * Determine whether the user can manage store carts.
     */
    public function manageCarts($user, Store $store): bool
    {
        // Same logic as manageOrders
        return $this->manageOrders($user, $store);
    }

    /**
     * Determine whether the user can view store performance metrics.
     */
    public function viewPerformance($user, Store $store): bool
    {
        // Same logic as viewAnalytics
        return $this->viewAnalytics($user, $store);
    }

    /**
     * Determine whether the user can manage store addresses.
     */
    public function manageAddresses($user, Store $store): bool
    {
        // Same logic as update
        return $this->update($user, $store);
    }

    /**
     * Determine whether the user can manage store currencies.
     */
    public function manageCurrencies($user, Store $store): bool
    {
        // Only admins can manage store currencies (financial impact)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_store', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage store translations.
     */
    public function manageTranslations($user, Store $store): bool
    {
        // Same logic as update
        return $this->update($user, $store);
    }

    /**
     * Determine whether the user can close/open stores.
     */
    public function toggleClosed($user, Store $store): bool
    {
        // Admins can close/open any store
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_store', 'admin')) {
            return true;
        }

        // Vendors can close/open their own stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_store', 'vendor')) {
            return $store->owner && $store->owner->id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can manage store favourites.
     */
    public function manageFavourites($user, Store $store): bool
    {
        // Users can manage their own favourites
        if ($user instanceof User) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view store reports.
     */
    public function viewReports($user, Store $store): bool
    {
        // Admins can view all store reports
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_store', 'admin')) {
            return true;
        }

        // Vendors can view reports for their own stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_store', 'vendor')) {
            return $store->owner && $store->owner->id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can manage store reports.
     */
    public function manageReports($user, Store $store): bool
    {
        // Admins can manage any store reports
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_store', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can transfer store ownership.
     */
    public function transferOwnership($user, Store $store): bool
    {
        // Only admins can transfer store ownership
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_store', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can merge stores.
     */
    public function merge($user, Store $store): bool
    {
        // Only admins can merge stores (significant business impact)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_store', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can clone store structure.
     */
    public function cloneStructure($user, Store $store): bool
    {
        // Same logic as duplicate
        return $this->duplicate($user, $store);
    }
}
