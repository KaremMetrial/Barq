<?php

namespace Modules\Product\Policies;

use Modules\Product\Models\Product;
use Modules\Admin\Models\Admin;
use Modules\Vendor\Models\Vendor;
use Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Helpers\PermissionHelper;

class ProductPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any products.
     */
    public function viewAny($user): bool
    {
        // Admins can view all products
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_product', 'admin')) {
            return true;
        }

        // Vendors can view products from their stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_product', 'vendor')) {
            return true;
        }

        // Users can view active products
        if ($user instanceof User) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the product.
     */
    public function view($user, Product $product): bool
    {
        // Admins can view all products
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_product', 'admin')) {
            return true;
        }

        // Vendors can view products from their stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_product', 'vendor')) {
            return $product->store_id === $user->store_id;
        }

        // Users can view active products
        if ($user instanceof User && $product->is_active && $product->status === \App\Enums\ProductStatusEnum::ACTIVE) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create products.
     */
    public function create($user): bool
    {
        // Admins can create products for any store
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_product', 'admin')) {
            return true;
        }

        // Vendors can create products for their stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('create_product', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the product.
     */
    public function update($user, Product $product): bool
    {
        // Admins can update any product
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_product', 'admin')) {
            return true;
        }

        // Vendors can update products from their stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_product', 'vendor')) {
            return $product->store_id === $user->store_id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the product.
     */
    public function delete($user, Product $product): bool
    {
        // Admins can delete any product
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_product', 'admin')) {
            return true;
        }

        // Vendors can delete products from their stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('delete_product', 'vendor')) {
            return $product->store_id === $user->store_id;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the product.
     */
    public function restore($user, Product $product): bool
    {
        // Same logic as update
        return $this->update($user, $product);
    }

    /**
     * Determine whether the user can permanently delete the product.
     */
    public function forceDelete($user, Product $product): bool
    {
        // Same logic as delete
        return $this->delete($user, $product);
    }

    /**
     * Determine whether the user can manage product images.
     */
    public function manageImages($user, Product $product): bool
    {
        // Same logic as update
        return $this->update($user, $product);
    }

    /**
     * Determine whether the user can manage product pricing.
     */
    public function managePricing($user, Product $product): bool
    {
        // Admins can manage any product pricing
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_product', 'admin')) {
            return true;
        }

        // Vendors can manage pricing for their products
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_product', 'vendor')) {
            return $product->store_id === $user->store_id;
        }

        return false;
    }

    /**
     * Determine whether the user can manage product availability/stock.
     */
    public function manageAvailability($user, Product $product): bool
    {
        // Same logic as update
        return $this->update($user, $product);
    }

    /**
     * Determine whether the user can manage product categories.
     */
    public function manageCategories($user, Product $product): bool
    {
        // Same logic as update
        return $this->update($user, $product);
    }

    /**
     * Determine whether the user can manage product tags.
     */
    public function manageTags($user, Product $product): bool
    {
        // Same logic as update
        return $this->update($user, $product);
    }

    /**
     * Determine whether the user can manage product add-ons.
     */
    public function manageAddOns($user, Product $product): bool
    {
        // Same logic as update
        return $this->update($user, $product);
    }

    /**
     * Determine whether the user can manage product options.
     */
    public function manageOptions($user, Product $product): bool
    {
        // Same logic as update
        return $this->update($user, $product);
    }

    /**
     * Determine whether the user can manage product nutrition info.
     */
    public function manageNutrition($user, Product $product): bool
    {
        // Same logic as update
        return $this->update($user, $product);
    }

    /**
     * Determine whether the user can manage product allergens.
     */
    public function manageAllergens($user, Product $product): bool
    {
        // Same logic as update
        return $this->update($user, $product);
    }

    /**
     * Determine whether the user can manage pharmacy information.
     */
    public function managePharmacyInfo($user, Product $product): bool
    {
        // Same logic as update
        return $this->update($user, $product);
    }

    /**
     * Determine whether the user can manage product watermarks.
     */
    public function manageWatermarks($user, Product $product): bool
    {
        // Same logic as update
        return $this->update($user, $product);
    }

    /**
     * Determine whether the user can activate/deactivate products.
     */
    public function toggleActive($user, Product $product): bool
    {
        // Admins can activate/deactivate any product
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_product', 'admin')) {
            return true;
        }

        // Vendors can activate/deactivate their products
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_product', 'vendor')) {
            return $product->store_id === $user->store_id;
        }

        return false;
    }

    /**
     * Determine whether the user can feature/unfeature products.
     */
    public function toggleFeatured($user, Product $product): bool
    {
        // Admins can feature/unfeature any product
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_product', 'admin')) {
            return true;
        }

        // Vendors can feature/unfeature their products
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_product', 'vendor')) {
            return $product->store_id === $user->store_id;
        }

        return false;
    }

    /**
     * Determine whether the user can duplicate products.
     */
    public function duplicate($user, Product $product): bool
    {
        // Same logic as create
        return $this->create($user);
    }

    /**
     * Determine whether the user can export product data.
     */
    public function export($user): bool
    {
        // Admins can export all product data
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_product', 'admin')) {
            return true;
        }

        // Vendors can export data for their products
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_product', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can import product data.
     */
    public function import($user): bool
    {
        // Admins can import product data
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_product', 'admin')) {
            return true;
        }

        // Vendors can import data for their stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('create_product', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk update products.
     */
    public function bulkUpdate($user): bool
    {
        // Admins can bulk update all products
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_product', 'admin')) {
            return true;
        }

        // Vendors can bulk update their products
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_product', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk delete products.
     */
    public function bulkDelete($user): bool
    {
        // Admins can bulk delete any products
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_product', 'admin')) {
            return true;
        }

        // Vendors can bulk delete their products
        if ($user instanceof Vendor && PermissionHelper::hasPermission('delete_product', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view product analytics.
     */
    public function viewAnalytics($user, Product $product): bool
    {
        // Admins can view all product analytics
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        // Vendors can view analytics for their products
        if ($user instanceof Vendor) {
            if ($product->store_id === $user->store_id) {
                return PermissionHelper::hasPermission('view_report', 'vendor');
            }
            return false;
        }

        return false;
    }

    /**
     * Determine whether the user can view product reviews.
     */
    public function viewReviews($user, Product $product): bool
    {
        // Everyone can view product reviews
        return true;
    }

    /**
     * Determine whether the user can moderate product reviews.
     */
    public function moderateReviews($user, Product $product): bool
    {
        // Admins can moderate any product reviews
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_product', 'admin')) {
            return true;
        }

        // Vendors can moderate reviews for their products
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_product', 'vendor')) {
            return $product->store_id === $user->store_id;
        }

        return false;
    }

    /**
     * Determine whether the user can view product reports.
     */
    public function viewReports($user, Product $product): bool
    {
        // Admins can view all product reports
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_product', 'admin')) {
            return true;
        }

        // Vendors can view reports for their products
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_product', 'vendor')) {
            return $product->store_id === $user->store_id;
        }

        return false;
    }

    /**
     * Determine whether the user can manage product reports.
     */
    public function manageReports($user, Product $product): bool
    {
        // Admins can manage any product reports
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_product', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view product order items.
     */
    public function viewOrderItems($user, Product $product): bool
    {
        // Admins can view all order items
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_order', 'admin')) {
            return true;
        }

        // Vendors can view order items for their products
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_order', 'vendor')) {
            return $product->store_id === $user->store_id;
        }

        return false;
    }

    /**
     * Determine whether the user can manage product favourites.
     */
    public function manageFavourites($user, Product $product): bool
    {
        // Users can manage their own favourites
        if ($user instanceof User) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view product performance metrics.
     */
    public function viewPerformance($user, Product $product): bool
    {
        // Same logic as viewAnalytics
        return $this->viewAnalytics($user, $product);
    }

    /**
     * Determine whether the user can transfer products between stores.
     */
    public function transferStore($user, Product $product): bool
    {
        // Only admins can transfer products between stores
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_product', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can clone product structure.
     */
    public function cloneStructure($user, Product $product): bool
    {
        // Same logic as duplicate
        return $this->duplicate($user, $product);
    }

    /**
     * Determine whether the user can manage product translations.
     */
    public function manageTranslations($user, Product $product): bool
    {
        // Same logic as update
        return $this->update($user, $product);
    }

    /**
     * Determine whether the user can view related products.
     */
    public function viewRelatedProducts($user, Product $product): bool
    {
        // Same logic as view
        return $this->view($user, $product);
    }

    /**
     * Determine whether the user can manage product barcode.
     */
    public function manageBarcode($user, Product $product): bool
    {
        // Same logic as update
        return $this->update($user, $product);
    }

    /**
     * Determine whether the user can manage product weight.
     */
    public function manageWeight($user, Product $product): bool
    {
        // Same logic as update
        return $this->update($user, $product);
    }

    /**
     * Determine whether the user can manage preparation time.
     */
    public function managePreparationTime($user, Product $product): bool
    {
        // Same logic as update
        return $this->update($user, $product);
    }

    /**
     * Determine whether the user can manage product status.
     */
    public function manageStatus($user, Product $product): bool
    {
        // Same logic as update
        return $this->update($user, $product);
    }

    /**
     * Determine whether the user can manage product vegetarian flag.
     */
    public function manageVegetarianFlag($user, Product $product): bool
    {
        // Same logic as update
        return $this->update($user, $product);
    }

    /**
     * Determine whether the user can manage max cart quantity.
     */
    public function manageMaxCartQuantity($user, Product $product): bool
    {
        // Same logic as update
        return $this->update($user, $product);
    }

    /**
     * Determine whether the user can manage product units.
     */
    public function manageUnits($user, Product $product): bool
    {
        // Same logic as update
        return $this->update($user, $product);
    }
}
