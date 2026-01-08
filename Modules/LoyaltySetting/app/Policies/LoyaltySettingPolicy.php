<?php

namespace Modules\LoyaltySetting\Policies;

use Modules\LoyaltySetting\Models\LoyaltySetting;
use Modules\Admin\Models\Admin;
use Modules\Vendor\Models\Vendor;
use Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Helpers\PermissionHelper;

class LoyaltySettingPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any loyalty settings.
     */
    public function viewAny($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Only admins can view loyalty settings (financial/business data)
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_loyalty_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the loyalty setting.
     */
    public function view($user, LoyaltySetting $loyaltySetting): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Only admins can view loyalty settings
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_loyalty_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create loyalty settings.
     */
    public function create($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Only admins can create loyalty settings (affects business revenue)
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_loyalty_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the loyalty setting.
     */
    public function update($user, LoyaltySetting $loyaltySetting): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Only admins can update loyalty settings (critical financial impact)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_loyalty_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the loyalty setting.
     */
    public function delete($user, LoyaltySetting $loyaltySetting): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Only admins can delete loyalty settings (extremely sensitive operation)
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_loyalty_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the loyalty setting.
     */
    public function restore($user, LoyaltySetting $loyaltySetting): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as update
        return $this->update($user, $loyaltySetting);
    }

    /**
     * Determine whether the user can permanently delete the loyalty setting.
     */
    public function forceDelete($user, LoyaltySetting $loyaltySetting): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as delete
        return $this->delete($user, $loyaltySetting);
    }

    /**
     * Determine whether the user can manage loyalty setting country relationship.
     */
    public function manageCountry($user, LoyaltySetting $loyaltySetting): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Only admins can change country relationships
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_loyalty_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view loyalty program analytics/reports.
     */
    public function viewAnalytics($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can view loyalty analytics
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view loyalty transaction reports.
     */
    public function viewTransactions($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can view loyalty transactions
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_loyalty_transaction', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage loyalty program settings.
     */
    public function manageSettings($user, LoyaltySetting $loyaltySetting): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Only admins can manage loyalty settings
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_loyalty_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can configure earn rates.
     */
    public function configureEarnRate($user, LoyaltySetting $loyaltySetting): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Only admins can configure earn rates (direct revenue impact)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_loyalty_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can configure referral points.
     */
    public function configureReferralPoints($user, LoyaltySetting $loyaltySetting): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as update (affects referral program economics)
        return $this->update($user, $loyaltySetting);
    }

    /**
     * Determine whether the user can configure rating points.
     */
    public function configureRatingPoints($user, LoyaltySetting $loyaltySetting): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as update
        return $this->update($user, $loyaltySetting);
    }

    /**
     * Determine whether the user can enable/disable loyalty program.
     */
    public function toggleProgram($user, LoyaltySetting $loyaltySetting): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Only admins can enable/disable loyalty programs
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_loyalty_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can export loyalty data.
     */
    public function export($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can export loyalty data
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_loyalty_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can import loyalty data.
     */
    public function import($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Only admins can import loyalty settings
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_loyalty_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk update loyalty settings.
     */
    public function bulkUpdate($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Only admins can bulk update loyalty settings
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_loyalty_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can duplicate loyalty settings.
     */
    public function duplicate($user, LoyaltySetting $loyaltySetting): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as create
        return $this->create($user);
    }

    /**
     * Determine whether the user can reset loyalty program.
     */
    public function resetProgram($user, LoyaltySetting $loyaltySetting): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Only admins can reset loyalty programs (destructive operation)
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_loyalty_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view loyalty program performance metrics.
     */
    public function viewPerformance($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can view performance metrics
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage loyalty rewards.
     */
    public function manageRewards($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can manage loyalty rewards
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_loyalty_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view customer loyalty data.
     */
    public function viewCustomerData($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can view customer loyalty data
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_loyalty_transaction', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can adjust customer loyalty points.
     */
    public function adjustCustomerPoints($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Only admins can manually adjust customer points
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_loyalty_transaction', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can configure loyalty tiers.
     */
    public function configureTiers($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can configure loyalty tiers
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_loyalty_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage loyalty campaigns.
     */
    public function manageCampaigns($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can manage loyalty campaigns
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_loyalty_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view loyalty program financial impact.
     */
    public function viewFinancialImpact($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can view financial impact
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can configure auto-points awarding.
     */
    public function configureAutoPoints($user, LoyaltySetting $loyaltySetting): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can configure auto-points
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_loyalty_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage loyalty expiration settings.
     */
    public function manageExpiration($user, LoyaltySetting $loyaltySetting): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can manage point expiration
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_loyalty_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view loyalty program statistics.
     */
    public function viewStatistics($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can view loyalty statistics
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can configure country-specific loyalty settings.
     */
    public function configureCountrySpecific($user, LoyaltySetting $loyaltySetting): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can configure country-specific settings
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_loyalty_setting', 'admin')) {
            return true;
        }

        return false;
    }
}
