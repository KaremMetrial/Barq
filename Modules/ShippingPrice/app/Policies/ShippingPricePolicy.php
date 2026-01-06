<?php

namespace Modules\ShippingPrice\Policies;

use Modules\ShippingPrice\Models\ShippingPrice;
use Modules\Admin\Models\Admin;
use Modules\Vendor\Models\Vendor;
use Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Helpers\PermissionHelper;

class ShippingPricePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any shipping prices.
     */
    public function viewAny($user): bool
    {
        // Admins can view all shipping prices
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_shipping_price', 'admin')) {
            return true;
        }

        // Vendors can view shipping prices for their stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_shipping_price', 'vendor')) {
            return true;
        }

        // Users can view shipping prices (for cost calculation)
        if ($user instanceof User) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the shipping price.
     */
    public function view($user, ShippingPrice $shippingPrice): bool
    {
        // Admins can view all shipping prices
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_shipping_price', 'admin')) {
            return true;
        }

        // Vendors can view shipping prices
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_shipping_price', 'vendor')) {
            return true;
        }

        // Users can view active shipping prices
        if ($user instanceof User && $shippingPrice->is_active) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create shipping prices.
     */
    public function create($user): bool
    {
        // Admins can create shipping prices for any zones
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_shipping_price', 'admin')) {
            return true;
        }

        // Vendors can create shipping prices (limited to their operational areas)
        if ($user instanceof Vendor && PermissionHelper::hasPermission('create_shipping_price', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the shipping price.
     */
    public function update($user, ShippingPrice $shippingPrice): bool
    {
        // Admins can update any shipping price
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_shipping_price', 'admin')) {
            return true;
        }

        // Vendors can update shipping prices (business revenue impact)
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_shipping_price', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the shipping price.
     */
    public function delete($user, ShippingPrice $shippingPrice): bool
    {
        // Admins can delete any shipping price
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_shipping_price', 'admin')) {
            return true;
        }

        // Vendors can delete shipping prices
        if ($user instanceof Vendor && PermissionHelper::hasPermission('delete_shipping_price', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the shipping price.
     */
    public function restore($user, ShippingPrice $shippingPrice): bool
    {
        // Same logic as update
        return $this->update($user, $shippingPrice);
    }

    /**
     * Determine whether the user can permanently delete the shipping price.
     */
    public function forceDelete($user, ShippingPrice $shippingPrice): bool
    {
        // Same logic as delete
        return $this->delete($user, $shippingPrice);
    }

    /**
     * Determine whether the user can manage shipping price zones.
     */
    public function manageZones($user, ShippingPrice $shippingPrice): bool
    {
        // Admins can manage zone assignments
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_shipping_price', 'admin')) {
            return true;
        }

        // Vendors can manage zone assignments for their areas
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_shipping_price', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage shipping price vehicles.
     */
    public function manageVehicles($user, ShippingPrice $shippingPrice): bool
    {
        // Admins can manage vehicle assignments
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_shipping_price', 'admin')) {
            return true;
        }

        // Vendors can manage vehicle assignments
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_shipping_price', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage COD settings.
     */
    public function manageCod($user, ShippingPrice $shippingPrice): bool
    {
        // Admins can manage COD settings
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_shipping_price', 'admin')) {
            return true;
        }

        // Vendors can manage COD settings
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_shipping_price', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can configure pricing rules.
     */
    public function configurePricing($user, ShippingPrice $shippingPrice): bool
    {
        // Admins can configure all pricing rules
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_shipping_price', 'admin')) {
            return true;
        }

        // Vendors can configure pricing rules (affects their revenue)
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_shipping_price', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can activate/deactivate shipping prices.
     */
    public function toggleActive($user, ShippingPrice $shippingPrice): bool
    {
        // Admins can activate/deactivate any shipping price
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_shipping_price', 'admin')) {
            return true;
        }

        // Vendors can activate/deactivate shipping prices
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_shipping_price', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can duplicate shipping prices.
     */
    public function duplicate($user, ShippingPrice $shippingPrice): bool
    {
        // Same logic as create
        return $this->create($user);
    }

    /**
     * Determine whether the user can export shipping price data.
     */
    public function export($user): bool
    {
        // Admins can export all shipping price data
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_shipping_price', 'admin')) {
            return true;
        }

        // Vendors can export their shipping price data
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_shipping_price', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can import shipping price data.
     */
    public function import($user): bool
    {
        // Admins can import shipping price data
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_shipping_price', 'admin')) {
            return true;
        }

        // Vendors can import shipping price data
        if ($user instanceof Vendor && PermissionHelper::hasPermission('create_shipping_price', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk update shipping prices.
     */
    public function bulkUpdate($user): bool
    {
        // Admins can bulk update all shipping prices
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_shipping_price', 'admin')) {
            return true;
        }

        // Vendors can bulk update their shipping prices
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_shipping_price', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk delete shipping prices.
     */
    public function bulkDelete($user): bool
    {
        // Admins can bulk delete any shipping prices
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_shipping_price', 'admin')) {
            return true;
        }

        // Vendors can bulk delete their shipping prices
        if ($user instanceof Vendor && PermissionHelper::hasPermission('delete_shipping_price', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view shipping price analytics.
     */
    public function viewAnalytics($user, ShippingPrice $shippingPrice): bool
    {
        // Admins can view all shipping price analytics
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        // Vendors can view analytics for their shipping prices
        if ($user instanceof Vendor) {
            if (PermissionHelper::hasPermission('view_report', 'vendor')) {
                return true;
            }
            return false;
        }

        return false;
    }

    /**
     * Determine whether the user can view shipping price performance metrics.
     */
    public function viewPerformance($user, ShippingPrice $shippingPrice): bool
    {
        // Same logic as viewAnalytics
        return $this->viewAnalytics($user, $shippingPrice);
    }

    /**
     * Determine whether the user can calculate shipping costs.
     */
    public function calculateCost($user): bool
    {
        // Any authenticated user can calculate shipping costs
        if ($user) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage distance-based pricing.
     */
    public function manageDistancePricing($user, ShippingPrice $shippingPrice): bool
    {
        // Admins can manage distance-based pricing
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_shipping_price', 'admin')) {
            return true;
        }

        // Vendors can manage distance-based pricing
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_shipping_price', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage weight-based pricing.
     */
    public function manageWeightPricing($user, ShippingPrice $shippingPrice): bool
    {
        // Admins can manage weight-based pricing
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_shipping_price', 'admin')) {
            return true;
        }

        // Vendors can manage weight-based pricing
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_shipping_price', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage time-based pricing.
     */
    public function manageTimePricing($user, ShippingPrice $shippingPrice): bool
    {
        // Admins can manage time-based pricing
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_shipping_price', 'admin')) {
            return true;
        }

        // Vendors can manage time-based pricing
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_shipping_price', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage surge pricing.
     */
    public function manageSurgePricing($user, ShippingPrice $shippingPrice): bool
    {
        // Admins can manage surge pricing
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_shipping_price', 'admin')) {
            return true;
        }

        // Vendors can manage surge pricing
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_shipping_price', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can set minimum order values.
     */
    public function setMinimumOrder($user, ShippingPrice $shippingPrice): bool
    {
        // Admins can set minimum order values
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_shipping_price', 'admin')) {
            return true;
        }

        // Vendors can set minimum order values
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_shipping_price', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage free shipping thresholds.
     */
    public function manageFreeShipping($user, ShippingPrice $shippingPrice): bool
    {
        // Admins can manage free shipping thresholds
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_shipping_price', 'admin')) {
            return true;
        }

        // Vendors can manage free shipping thresholds
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_shipping_price', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage shipping discounts.
     */
    public function manageDiscounts($user, ShippingPrice $shippingPrice): bool
    {
        // Admins can manage shipping discounts
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_shipping_price', 'admin')) {
            return true;
        }

        // Vendors can manage shipping discounts
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_shipping_price', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view shipping price statistics.
     */
    public function viewStatistics($user): bool
    {
        // Admins can view shipping price statistics
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        // Vendors can view their shipping price statistics
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_report', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create shipping price templates.
     */
    public function createTemplates($user): bool
    {
        // Admins can create shipping price templates
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_shipping_price', 'admin')) {
            return true;
        }

        // Vendors can create shipping price templates
        if ($user instanceof Vendor && PermissionHelper::hasPermission('create_shipping_price', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can apply shipping price templates.
     */
    public function applyTemplates($user): bool
    {
        // Same logic as create
        return $this->create($user);
    }

    /**
     * Determine whether the user can manage shipping price rules.
     */
    public function manageRules($user, ShippingPrice $shippingPrice): bool
    {
        // Admins can manage shipping price rules
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_shipping_price', 'admin')) {
            return true;
        }

        // Vendors can manage shipping price rules
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_shipping_price', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage shipping price translations.
     */
    public function manageTranslations($user, ShippingPrice $shippingPrice): bool
    {
        // Admins can manage translations
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_shipping_price', 'admin')) {
            return true;
        }

        // Vendors can manage translations
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_shipping_price', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can optimize shipping prices.
     */
    public function optimizePricing($user, ShippingPrice $shippingPrice): bool
    {
        // Admins can optimize shipping prices
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_shipping_price', 'admin')) {
            return true;
        }

        // Vendors can optimize their shipping prices
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_shipping_price', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can test shipping price calculations.
     */
    public function testCalculations($user, ShippingPrice $shippingPrice): bool
    {
        // Admins can test shipping price calculations
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_shipping_price', 'admin')) {
            return true;
        }

        // Vendors can test their shipping price calculations
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_shipping_price', 'vendor')) {
            return true;
        }

        return false;
    }
}
