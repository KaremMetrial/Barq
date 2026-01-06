<?php

namespace Modules\Country\Policies;

use Modules\Country\Models\Country;
use Modules\Admin\Models\Admin;
use Modules\Vendor\Models\Vendor;
use Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Helpers\PermissionHelper;

class CountryPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any countries.
     */
    public function viewAny($user): bool
    {
        // Admins can view all countries
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_country', 'admin')) {
            return true;
        }

        // Users can view active countries
        if ($user instanceof User) {
            return true;
        }

        // Vendors can view countries (for store operations)
        if ($user instanceof Vendor) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the country.
     */
    public function view($user, Country $country): bool
    {
        // Admins can view all countries
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_country', 'admin')) {
            return true;
        }

        // Users can view active countries
        if ($user instanceof User && $country->is_active) {
            return true;
        }

        // Vendors can view countries (for operational purposes)
        if ($user instanceof Vendor) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create countries.
     */
    public function create($user): bool
    {
        // Only admins can create countries (geographic management)
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_country', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the country.
     */
    public function update($user, Country $country): bool
    {
        // Only admins can update countries (critical geographic data)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_country', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the country.
     */
    public function delete($user, Country $country): bool
    {
        // Only admins can delete countries (extremely sensitive operation)
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_country', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the country.
     */
    public function restore($user, Country $country): bool
    {
        // Same logic as update
        return $this->update($user, $country);
    }

    /**
     * Determine whether the user can permanently delete the country.
     */
    public function forceDelete($user, Country $country): bool
    {
        // Same logic as delete
        return $this->delete($user, $country);
    }

    /**
     * Determine whether the user can manage country governorates.
     */
    public function manageGovernorates($user, Country $country): bool
    {
        // Same logic as update (governorate management is part of country management)
        return $this->update($user, $country);
    }

    /**
     * Determine whether the user can view country governorates.
     */
    public function viewGovernorates($user, Country $country): bool
    {
        // Admins can view all governorates
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_country', 'admin')) {
            return true;
        }

        // Users can view governorates of active countries
        if ($user instanceof User && $country->is_active) {
            return true;
        }

        // Vendors can view governorates (for delivery operations)
        if ($user instanceof Vendor) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage country cities.
     */
    public function manageCities($user, Country $country): bool
    {
        // Same logic as update
        return $this->update($user, $country);
    }

    /**
     * Determine whether the user can view country cities.
     */
    public function viewCities($user, Country $country): bool
    {
        // Same logic as view governorates
        return $this->viewGovernorates($user, $country);
    }

    /**
     * Determine whether the user can manage country sections.
     */
    public function manageSections($user, Country $country): bool
    {
        // Admins can manage country sections
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_country', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view country sections.
     */
    public function viewSections($user, Country $country): bool
    {
        // Same logic as view
        return $this->view($user, $country);
    }

    /**
     * Determine whether the user can manage country loyalty settings.
     */
    public function manageLoyaltySettings($user, Country $country): bool
    {
        // Admins can manage loyalty settings per country
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_country', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view country loyalty settings.
     */
    public function viewLoyaltySettings($user, Country $country): bool
    {
        // Admins can view loyalty settings
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_country', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage country rewards.
     */
    public function manageRewards($user, Country $country): bool
    {
        // Admins can manage rewards per country
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_country', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view country rewards.
     */
    public function viewRewards($user, Country $country): bool
    {
        // Admins can view rewards
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_country', 'admin')) {
            return true;
        }

        // Users can view rewards from active countries
        if ($user instanceof User && $country->is_active) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage country currency settings.
     */
    public function manageCurrency($user, Country $country): bool
    {
        // Only admins can manage currency settings (critical financial data)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_country', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view country currency information.
     */
    public function viewCurrency($user, Country $country): bool
    {
        // Everyone can view currency information (public data)
        return true;
    }

    /**
     * Determine whether the user can activate/deactivate countries.
     */
    public function toggleActive($user, Country $country): bool
    {
        // Only admins can activate/deactivate countries
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_country', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view country analytics/reports.
     */
    public function viewAnalytics($user, Country $country): bool
    {
        // Admins can view country analytics
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage country settings.
     */
    public function manageSettings($user, Country $country): bool
    {
        // Only admins can manage country settings
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_country', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can export country data.
     */
    public function export($user): bool
    {
        // Admins can export country data
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_country', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can import country data.
     */
    public function import($user): bool
    {
        // Only admins can import countries (geographic data management)
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_country', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk update countries.
     */
    public function bulkUpdate($user): bool
    {
        // Only admins can bulk update countries
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_country', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk delete countries.
     */
    public function bulkDelete($user): bool
    {
        // Only admins can bulk delete countries
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_country', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can duplicate countries.
     */
    public function duplicate($user, Country $country): bool
    {
        // Same logic as create
        return $this->create($user);
    }

    /**
     * Determine whether the user can merge countries.
     */
    public function merge($user): bool
    {
        // Only admins can merge countries (complex geographic operation)
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_country', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can split countries.
     */
    public function split($user, Country $country): bool
    {
        // Only admins can split countries (complex geographic operation)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_country', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view country geographic hierarchy.
     */
    public function viewHierarchy($user): bool
    {
        // Admins can view geographic hierarchy
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_country', 'admin')) {
            return true;
        }

        // Users can view hierarchy of active countries
        if ($user instanceof User) {
            return true;
        }

        // Vendors can view geographic hierarchy
        if ($user instanceof Vendor) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage country translations.
     */
    public function manageTranslations($user, Country $country): bool
    {
        // Admins can manage translations
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_country', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view country statistics.
     */
    public function viewStatistics($user): bool
    {
        // Admins can view country statistics
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can configure country-specific features.
     */
    public function configureFeatures($user, Country $country): bool
    {
        // Only admins can configure country features
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_country', 'admin')) {
            return true;
        }

        return false;
    }
}
