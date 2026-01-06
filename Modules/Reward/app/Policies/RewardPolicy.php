<?php

namespace Modules\Reward\Policies;

use Modules\Reward\Models\Reward;
use Modules\Admin\Models\Admin;
use Modules\Vendor\Models\Vendor;
use Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Helpers\PermissionHelper;

class RewardPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any rewards.
     */
    public function viewAny($user): bool
    {
        // Admins can view all rewards
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_reward', 'admin')) {
            return true;
        }

        // Users can view available rewards for redemption
        if ($user instanceof User) {
            return true;
        }

        // Vendors can view rewards
        if ($user instanceof Vendor) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the reward.
     */
    public function view($user, Reward $reward): bool
    {
        // Admins can view all rewards
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_reward', 'admin')) {
            return true;
        }

        // Users can view active rewards
        if ($user instanceof User && $reward->isActive()) {
            return true;
        }

        // Vendors can view rewards
        if ($user instanceof Vendor) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create rewards.
     */
    public function create($user): bool
    {
        // Only admins can create rewards (loyalty program management)
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_reward', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the reward.
     */
    public function update($user, Reward $reward): bool
    {
        // Only admins can update rewards (loyalty program management)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_reward', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the reward.
     */
    public function delete($user, Reward $reward): bool
    {
        // Only admins can delete rewards (loyalty program management)
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_reward', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the reward.
     */
    public function restore($user, Reward $reward): bool
    {
        // Same logic as update
        return $this->update($user, $reward);
    }

    /**
     * Determine whether the user can permanently delete the reward.
     */
    public function forceDelete($user, Reward $reward): bool
    {
        // Same logic as delete
        return $this->delete($user, $reward);
    }

    /**
     * Determine whether the user can redeem the reward.
     */
    public function redeem($user, Reward $reward): bool
    {
        // Users can redeem active rewards they haven't exceeded limits for
        if ($user instanceof User && $reward->isActive() && !$reward->hasReachedLimit()) {
            return $reward->canUserRedeem($user->id);
        }

        return false;
    }

    /**
     * Determine whether the user can activate/deactivate rewards.
     */
    public function toggleActive($user, Reward $reward): bool
    {
        // Only admins can activate/deactivate rewards
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_reward', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage reward redemptions.
     */
    public function manageRedemptions($user, Reward $reward): bool
    {
        // Admins can manage all redemptions
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_reward', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view reward redemptions.
     */
    public function viewRedemptions($user, Reward $reward): bool
    {
        // Admins can view all redemptions
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_reward', 'admin')) {
            return true;
        }

        // Users can view their own redemptions
        if ($user instanceof User) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage reward coupons.
     */
    public function manageCoupons($user, Reward $reward): bool
    {
        // Only admins can manage reward coupons
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_reward', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage reward countries.
     */
    public function manageCountries($user, Reward $reward): bool
    {
        // Only admins can manage reward country restrictions
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_reward', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can duplicate rewards.
     */
    public function duplicate($user, Reward $reward): bool
    {
        // Same logic as create
        return $this->create($user);
    }

    /**
     * Determine whether the user can export reward data.
     */
    public function export($user): bool
    {
        // Admins can export all reward data
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_reward', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can import reward data.
     */
    public function import($user): bool
    {
        // Only admins can import rewards
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_reward', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk update rewards.
     */
    public function bulkUpdate($user): bool
    {
        // Only admins can bulk update rewards
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_reward', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk delete rewards.
     */
    public function bulkDelete($user): bool
    {
        // Only admins can bulk delete rewards
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_reward', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view reward analytics.
     */
    public function viewAnalytics($user, Reward $reward): bool
    {
        // Admins can view all reward analytics
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view reward performance metrics.
     */
    public function viewPerformance($user, Reward $reward): bool
    {
        // Same logic as viewAnalytics
        return $this->viewAnalytics($user, $reward);
    }

    /**
     * Determine whether the user can reset reward usage counters.
     */
    public function resetUsage($user, Reward $reward): bool
    {
        // Only admins can reset usage counters
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_reward', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can extend reward validity period.
     */
    public function extendValidity($user, Reward $reward): bool
    {
        // Only admins can extend reward validity
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_reward', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can modify reward points cost.
     */
    public function modifyPointsCost($user, Reward $reward): bool
    {
        // Only admins can modify points cost (loyalty economics)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_reward', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can modify reward value amount.
     */
    public function modifyValueAmount($user, Reward $reward): bool
    {
        // Only admins can modify value amounts (financial impact)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_reward', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage reward type.
     */
    public function manageType($user, Reward $reward): bool
    {
        // Only admins can change reward types
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_reward', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage reward limits.
     */
    public function manageLimits($user, Reward $reward): bool
    {
        // Only admins can manage redemption limits
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_reward', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view reward financial impact.
     */
    public function viewFinancialImpact($user, Reward $reward): bool
    {
        // Admins can view financial impact
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can archive the reward.
     */
    public function archive($user, Reward $reward): bool
    {
        // Only admins can archive rewards
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_reward', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can unarchive the reward.
     */
    public function unarchive($user, Reward $reward): bool
    {
        // Same logic as archive
        return $this->archive($user, $reward);
    }

    /**
     * Determine whether the user can feature/unfeature rewards.
     */
    public function toggleFeatured($user, Reward $reward): bool
    {
        // Only admins can feature/unfeature rewards
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_reward', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage reward images.
     */
    public function manageImages($user, Reward $reward): bool
    {
        // Only admins can manage reward images
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_reward', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage reward translations.
     */
    public function manageTranslations($user, Reward $reward): bool
    {
        // Only admins can manage translations
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_reward', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create reward templates.
     */
    public function createTemplates($user): bool
    {
        // Only admins can create reward templates
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_reward', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can apply reward templates.
     */
    public function applyTemplates($user): bool
    {
        // Same logic as create
        return $this->create($user);
    }

    /**
     * Determine whether the user can view reward statistics.
     */
    public function viewStatistics($user): bool
    {
        // Admins can view reward statistics
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage reward campaigns.
     */
    public function manageCampaigns($user): bool
    {
        // Only admins can manage reward campaigns
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_reward', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can configure auto-rewards.
     */
    public function configureAutoRewards($user): bool
    {
        // Only admins can configure auto-reward systems
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_reward', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage reward expiration.
     */
    public function manageExpiration($user, Reward $reward): bool
    {
        // Only admins can manage reward expiration
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_reward', 'admin')) {
            return true;
        }

        return false;
    }
}
