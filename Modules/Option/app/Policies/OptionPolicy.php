<?php

namespace Modules\Option\Policies;

use Modules\Option\Models\Option;
use Modules\Admin\Models\Admin;
use Modules\Vendor\Models\Vendor;
use Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Helpers\PermissionHelper;

class OptionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any options.
     */
    public function viewAny($user): bool
    {
        // Admins can view all options
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_option', 'admin')) {
            return true;
        }

        // Vendors can view options for their products
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_option', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the option.
     */
    public function view($user, Option $option): bool
    {
        // Admins can view all options
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_option', 'admin')) {
            return true;
        }

        // Vendors can view options used in their products
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_option', 'vendor')) {
            return $option->products()->where('products.store_id', $user->store_id)->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can create options.
     */
    public function create($user): bool
    {
        // Admins can create options
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_option', 'admin')) {
            return true;
        }

        // Vendors can create options for their products
        if ($user instanceof Vendor && PermissionHelper::hasPermission('create_option', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the option.
     */
    public function update($user, Option $option): bool
    {
        // Admins can update all options
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_option', 'admin')) {
            return true;
        }

        // Vendors can update options used in their products
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_option', 'vendor')) {
            return $option->products()->where('products.store_id', $user->store_id)->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can delete the option.
     */
    public function delete($user, Option $option): bool
    {
        // Admins can delete any option
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_option', 'admin')) {
            return true;
        }

        // Vendors can delete options from their products (with caution)
        if ($user instanceof Vendor && PermissionHelper::hasPermission('delete_option', 'vendor')) {
            return $option->products()->where('products.store_id', $user->store_id)->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can restore the option.
     */
    public function restore($user, Option $option): bool
    {
        // Same logic as update
        return $this->update($user, $option);
    }

    /**
     * Determine whether the user can permanently delete the option.
     */
    public function forceDelete($user, Option $option): bool
    {
        // Same logic as delete
        return $this->delete($user, $option);
    }

    /**
     * Determine whether the user can manage option values.
     */
    public function manageValues($user, Option $option): bool
    {
        // Same logic as update
        return $this->update($user, $option);
    }

    /**
     * Determine whether the user can view option values.
     */
    public function viewValues($user, Option $option): bool
    {
        // Same logic as view
        return $this->view($user, $option);
    }

    /**
     * Determine whether the user can attach options to products.
     */
    public function attachToProducts($user, Option $option): bool
    {
        // Admins can attach options to any products
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_option', 'admin')) {
            return true;
        }

        // Vendors can attach options to their own products
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_option', 'vendor')) {
            return true; // They can attach to their products
        }

        return false;
    }

    /**
     * Determine whether the user can detach options from products.
     */
    public function detachFromProducts($user, Option $option): bool
    {
        // Admins can detach options from any products
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_option', 'admin')) {
            return true;
        }

        // Vendors can detach options from their own products
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_option', 'vendor')) {
            return $option->products()->where('products.store_id', $user->store_id)->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can configure option settings.
     */
    public function configureSettings($user, Option $option): bool
    {
        // Same logic as update
        return $this->update($user, $option);
    }

    /**
     * Determine whether the user can change option input type.
     */
    public function changeInputType($user, Option $option): bool
    {
        // Same logic as update (input type affects data structure)
        return $this->update($user, $option);
    }

    /**
     * Determine whether the user can manage option translations.
     */
    public function manageTranslations($user, Option $option): bool
    {
        // Same logic as update
        return $this->update($user, $option);
    }

    /**
     * Determine whether the user can duplicate options.
     */
    public function duplicate($user, Option $option): bool
    {
        // Same logic as create
        return $this->create($user);
    }

    /**
     * Determine whether the user can export option data.
     */
    public function export($user): bool
    {
        // Admins can export all option data
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_option', 'admin')) {
            return true;
        }

        // Vendors can export data for their options
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_option', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can import option data.
     */
    public function import($user): bool
    {
        // Admins can import option data
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_option', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk update options.
     */
    public function bulkUpdate($user): bool
    {
        // Admins can bulk update all options
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_option', 'admin')) {
            return true;
        }

        // Vendors can bulk update their options
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_option', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk delete options.
     */
    public function bulkDelete($user): bool
    {
        // Admins can bulk delete any options
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_option', 'admin')) {
            return true;
        }

        // Vendors can bulk delete their options
        if ($user instanceof Vendor && PermissionHelper::hasPermission('delete_option', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view option analytics.
     */
    public function viewAnalytics($user, Option $option): bool
    {
        // Admins can view all option analytics
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        // Vendors can view analytics for their options
        if ($user instanceof Vendor) {
            if ($option->products()->where('products.store_id', $user->store_id)->exists()) {
                return PermissionHelper::hasPermission('view_report', 'vendor');
            }
            return false;
        }

        return false;
    }

    /**
     * Determine whether the user can view option usage statistics.
     */
    public function viewUsage($user, Option $option): bool
    {
        // Same logic as viewAnalytics
        return $this->viewAnalytics($user, $option);
    }

    /**
     * Determine whether the user can configure food-related options.
     */
    public function configureFoodOptions($user, Option $option): bool
    {
        // Same logic as update (food options may have special requirements)
        return $this->update($user, $option);
    }

    /**
     * Determine whether the user can manage option ordering/sorting.
     */
    public function manageOrdering($user, Option $option): bool
    {
        // Same logic as update
        return $this->update($user, $option);
    }

    /**
     * Determine whether the user can view option performance metrics.
     */
    public function viewPerformance($user, Option $option): bool
    {
        // Same logic as viewAnalytics
        return $this->viewAnalytics($user, $option);
    }

    /**
     * Determine whether the user can create option templates.
     */
    public function createTemplates($user): bool
    {
        // Admins can create option templates
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_option', 'admin')) {
            return true;
        }

        // Vendors can create templates for their use
        if ($user instanceof Vendor && PermissionHelper::hasPermission('create_option', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can apply option templates.
     */
    public function applyTemplates($user): bool
    {
        // Same logic as create (applying templates creates options)
        return $this->create($user);
    }

    /**
     * Determine whether the user can view option validation rules.
     */
    public function viewValidationRules($user, Option $option): bool
    {
        // Same logic as view
        return $this->view($user, $option);
    }

    /**
     * Determine whether the user can configure option validation rules.
     */
    public function configureValidationRules($user, Option $option): bool
    {
        // Same logic as update
        return $this->update($user, $option);
    }

    /**
     * Determine whether the user can manage option pricing.
     */
    public function managePricing($user, Option $option): bool
    {
        // Same logic as update (option values may have pricing)
        return $this->update($user, $option);
    }

    /**
     * Determine whether the user can view option pricing information.
     */
    public function viewPricing($user, Option $option): bool
    {
        // Same logic as view
        return $this->view($user, $option);
    }
}
