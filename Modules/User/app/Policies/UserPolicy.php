<?php

namespace Modules\User\Policies;

use Modules\User\Models\User;
use Modules\Admin\Models\Admin;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Helpers\PermissionHelper;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any users.
     */
    public function viewAny($user): bool
    {
        // Only admins can view user lists (privacy)
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_user', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the user.
     */
    public function view($user, User $model): bool
    {
        // Admins can view any user
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_user', 'admin')) {
            return true;
        }

        // Users can view their own profile
        if ($user instanceof User && $user->id === $model->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create users.
     */
    public function create($user): bool
    {
        // Admins can create users
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_user', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the user.
     */
    public function update($user, User $model): bool
    {
        // Admins can update any user
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_user', 'admin')) {
            return true;
        }

        // Users can update their own profile
        if ($user instanceof User && $user->id === $model->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the user.
     */
    public function delete($user, User $model): bool
    {
        // Only admins can delete users (GDPR compliance)
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_user', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the user.
     */
    public function restore($user, User $model): bool
    {
        // Same logic as update
        return $this->update($user, $model);
    }

    /**
     * Determine whether the user can permanently delete the user.
     */
    public function forceDelete($user, User $model): bool
    {
        // Same logic as delete
        return $this->delete($user, $model);
    }

    /**
     * Determine whether the user can manage user status.
     */
    public function manageStatus($user, User $model): bool
    {
        // Only admins can manage user status
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_user', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can block/unblock users.
     */
    public function toggleBlock($user, User $model): bool
    {
        // Only admins can block/unblock users
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_user', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage user balances.
     */
    public function manageBalances($user, User $model): bool
    {
        // Only admins can manage user balances (financial impact)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_user', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage user withdrawals.
     */
    public function manageWithdrawals($user, User $model): bool
    {
        // Admins can manage any user withdrawals
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_user', 'admin')) {
            return true;
        }

        // Users can manage their own withdrawals
        if ($user instanceof User && $user->id === $model->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage user orders.
     */
    public function manageOrders($user, User $model): bool
    {
        // Admins can manage any user orders
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_order', 'admin')) {
            return true;
        }

        // Users can manage their own orders
        if ($user instanceof User && $user->id === $model->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage user addresses.
     */
    public function manageAddresses($user, User $model): bool
    {
        // Admins can manage any user addresses
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_user', 'admin')) {
            return true;
        }

        // Users can manage their own addresses
        if ($user instanceof User && $user->id === $model->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage user favourites.
     */
    public function manageFavourites($user, User $model): bool
    {
        // Admins can manage any user favourites (privacy concern)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_user', 'admin')) {
            return true;
        }

        // Users can manage their own favourites
        if ($user instanceof User && $user->id === $model->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage user loyalty points.
     */
    public function manageLoyaltyPoints($user, User $model): bool
    {
        // Only admins can manage loyalty points (financial impact)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_user', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can award loyalty points.
     */
    public function awardPoints($user, User $model): bool
    {
        // Only admins can award loyalty points
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_user', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can redeem loyalty points.
     */
    public function redeemPoints($user, User $model): bool
    {
        // Users can redeem their own points
        if ($user instanceof User && $user->id === $model->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view loyalty transactions.
     */
    public function viewLoyaltyTransactions($user, User $model): bool
    {
        // Admins can view any user loyalty transactions
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_user', 'admin')) {
            return true;
        }

        // Users can view their own loyalty transactions
        if ($user instanceof User && $user->id === $model->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage user reviews.
     */
    public function manageReviews($user, User $model): bool
    {
        // Admins can manage any user reviews
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_user', 'admin')) {
            return true;
        }

        // Users can manage their own reviews
        if ($user instanceof User && $user->id === $model->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage user conversations.
     */
    public function manageConversations($user, User $model): bool
    {
        // Admins can manage any user conversations (privacy)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_user', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage user carts.
     */
    public function manageCarts($user, User $model): bool
    {
        // Admins can manage any user carts
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_user', 'admin')) {
            return true;
        }

        // Users can manage their own carts
        if ($user instanceof User && $user->id === $model->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view user analytics.
     */
    public function viewAnalytics($user, User $model): bool
    {
        // Only admins can view user analytics (privacy)
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can export user data.
     */
    public function export($user, User $model): bool
    {
        // Admins can export any user data (GDPR compliance)
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_user', 'admin')) {
            return true;
        }

        // Users can export their own data
        if ($user instanceof User && $user->id === $model->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can import user data.
     */
    public function import($user): bool
    {
        // Only admins can import user data
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_user', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk update users.
     */
    public function bulkUpdate($user): bool
    {
        // Only admins can bulk update users
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_user', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk delete users.
     */
    public function bulkDelete($user): bool
    {
        // Only admins can bulk delete users
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_user', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage user interests.
     */
    public function manageInterests($user, User $model): bool
    {
        // Admins can manage any user interests
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_user', 'admin')) {
            return true;
        }

        // Users can manage their own interests
        if ($user instanceof User && $user->id === $model->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage user referrals.
     */
    public function manageReferrals($user, User $model): bool
    {
        // Admins can manage any user referrals
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_user', 'admin')) {
            return true;
        }

        // Users can view their own referrals
        if ($user instanceof User && $user->id === $model->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view user transactions.
     */
    public function viewTransactions($user, User $model): bool
    {
        // Admins can view any user transactions
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_user', 'admin')) {
            return true;
        }

        // Users can view their own transactions
        if ($user instanceof User && $user->id === $model->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage user coupon usages.
     */
    public function manageCouponUsages($user, User $model): bool
    {
        // Admins can manage any user coupon usages
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_user', 'admin')) {
            return true;
        }

        // Users can view their own coupon usages
        if ($user instanceof User && $user->id === $model->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can duplicate users.
     */
    public function duplicate($user, User $model): bool
    {
        // Only admins can duplicate users
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_user', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage user notifications.
     */
    public function manageNotifications($user, User $model): bool
    {
        // Admins can manage any user notifications
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_user', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can reset user passwords.
     */
    public function resetPassword($user, User $model): bool
    {
        // Admins can reset any user password
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_user', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can verify user email.
     */
    public function verifyEmail($user, User $model): bool
    {
        // Admins can verify any user email
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_user', 'admin')) {
            return true;
        }

        // Users can request their own email verification
        if ($user instanceof User && $user->id === $model->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage user sessions.
     */
    public function manageSessions($user, User $model): bool
    {
        // Admins can manage any user sessions (security)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_user', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can impersonate users.
     */
    public function impersonate($user, User $model): bool
    {
        // Only admins can impersonate users (security risk)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_user', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can block users for excessive cancellations.
     */
    public function blockForCancellations($user, User $model): bool
    {
        // Only admins can block users for fraud prevention
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_user', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage user fraud flags.
     */
    public function manageFraudFlags($user, User $model): bool
    {
        // Only admins can manage fraud flags
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_user', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view user audit logs.
     */
    public function viewAuditLogs($user, User $model): bool
    {
        // Only admins can view user audit logs
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_user', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage user data retention.
     */
    public function manageDataRetention($user, User $model): bool
    {
        // Only admins can manage data retention (GDPR compliance)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_user', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can anonymize user data.
     */
    public function anonymizeData($user, User $model): bool
    {
        // Only admins can anonymize user data (GDPR compliance)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_user', 'admin')) {
            return true;
        }

        return false;
    }
}
