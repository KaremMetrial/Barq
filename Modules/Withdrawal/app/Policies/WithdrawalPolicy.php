<?php

namespace Modules\Withdrawal\Policies;

use Modules\Withdrawal\Models\Withdrawal;
use Modules\Admin\Models\Admin;
use Modules\Vendor\Models\Vendor;
use Modules\User\Models\User;
use Modules\Couier\Models\Couier;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Helpers\PermissionHelper;

class WithdrawalPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any withdrawals.
     */
    public function viewAny($user): bool
    {
        // Admins can view all withdrawals
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_withdrawal', 'admin')) {
            return true;
        }

        // Vendors can view their store's withdrawals
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_withdrawal', 'vendor')) {
            return true;
        }

        // Couriers can view their own withdrawals
        if ($user instanceof Couier) {
            return true;
        }

        // Users can view their own withdrawals
        if ($user instanceof User) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the withdrawal.
     */
    public function view($user, Withdrawal $withdrawal): bool
    {
        // Admins can view all withdrawals
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_withdrawal', 'admin')) {
            return true;
        }

        // Check entity ownership based on withdrawable type
        if ($this->isOwner($user, $withdrawal)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create withdrawals.
     */
    public function create($user): bool
    {
        // Vendors can create withdrawals for their stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('create_withdrawal', 'vendor')) {
            return true;
        }

        // Couriers can create their own withdrawals
        if ($user instanceof Couier) {
            return true;
        }

        // Users can create their own withdrawals
        if ($user instanceof User) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the withdrawal.
     */
    public function update($user, Withdrawal $withdrawal): bool
    {
        // Only admins can update withdrawal details (for corrections)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_withdrawal', 'admin')) {
            return true;
        }

        // Entity owners can update their pending withdrawals
        if ($this->isOwner($user, $withdrawal) && $withdrawal->status === \App\Enums\WithdrawalStatusEnum::PENDING) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the withdrawal.
     */
    public function delete($user, Withdrawal $withdrawal): bool
    {
        // Only admins can delete withdrawals (exceptional cases)
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_withdrawal', 'admin')) {
            return true;
        }

        // Entity owners can cancel their pending withdrawals
        if ($this->isOwner($user, $withdrawal) && $withdrawal->status === \App\Enums\WithdrawalStatusEnum::PENDING) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the withdrawal.
     */
    public function restore($user, Withdrawal $withdrawal): bool
    {
        // Only admins can restore withdrawals
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_withdrawal', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the withdrawal.
     */
    public function forceDelete($user, Withdrawal $withdrawal): bool
    {
        // Only admins can permanently delete withdrawals
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_withdrawal', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can approve the withdrawal.
     */
    public function approve($user, Withdrawal $withdrawal): bool
    {
        // Only admins can approve withdrawals (financial authority)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_withdrawal', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can reject the withdrawal.
     */
    public function reject($user, Withdrawal $withdrawal): bool
    {
        // Same logic as approve
        return $this->approve($user, $withdrawal);
    }

    /**
     * Determine whether the user can process the withdrawal.
     */
    public function process($user, Withdrawal $withdrawal): bool
    {
        // Only admins can process withdrawals (actual payment execution)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_withdrawal', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can cancel the withdrawal.
     */
    public function cancel($user, Withdrawal $withdrawal): bool
    {
        // Admins can cancel any withdrawal
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_withdrawal', 'admin')) {
            return true;
        }

        // Entity owners can cancel their pending withdrawals
        if ($this->isOwner($user, $withdrawal) && $withdrawal->status === \App\Enums\WithdrawalStatusEnum::PENDING) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view withdrawal analytics.
     */
    public function viewAnalytics($user): bool
    {
        // Admins can view all withdrawal analytics
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can export withdrawal data.
     */
    public function export($user): bool
    {
        // Admins can export all withdrawal data
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_withdrawal', 'admin')) {
            return true;
        }

        // Entity owners can export their own withdrawal data
        if ($this->isEntityOwner($user)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can import withdrawal data.
     */
    public function import($user): bool
    {
        // Only admins can import withdrawal data
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_withdrawal', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk update withdrawals.
     */
    public function bulkUpdate($user): bool
    {
        // Only admins can bulk update withdrawals
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_withdrawal', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk delete withdrawals.
     */
    public function bulkDelete($user): bool
    {
        // Only admins can bulk delete withdrawals
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_withdrawal', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view withdrawal audit logs.
     */
    public function viewAuditLogs($user, Withdrawal $withdrawal): bool
    {
        // Admins can view all withdrawal audit logs
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_withdrawal', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage withdrawal fees.
     */
    public function manageFees($user, Withdrawal $withdrawal): bool
    {
        // Only admins can manage withdrawal fees
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_withdrawal', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage withdrawal limits.
     */
    public function manageLimits($user, Withdrawal $withdrawal): bool
    {
        // Only admins can manage withdrawal limits
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_withdrawal', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage withdrawal methods.
     */
    public function manageMethods($user, Withdrawal $withdrawal): bool
    {
        // Only admins can manage withdrawal methods
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_withdrawal', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view withdrawal performance metrics.
     */
    public function viewPerformance($user, Withdrawal $withdrawal): bool
    {
        // Admins can view all withdrawal performance
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage withdrawal policies.
     */
    public function managePolicies($user): bool
    {
        // Only admins can manage withdrawal policies
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_withdrawal', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can override withdrawal limits.
     */
    public function overrideLimits($user, Withdrawal $withdrawal): bool
    {
        // Only admins can override withdrawal limits (exceptional cases)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_withdrawal', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage withdrawal currencies.
     */
    public function manageCurrencies($user, Withdrawal $withdrawal): bool
    {
        // Only admins can manage withdrawal currencies
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_withdrawal', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can duplicate withdrawals.
     */
    public function duplicate($user, Withdrawal $withdrawal): bool
    {
        // Only admins can duplicate withdrawals (for testing)
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_withdrawal', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage withdrawal notifications.
     */
    public function manageNotifications($user, Withdrawal $withdrawal): bool
    {
        // Admins can manage any withdrawal notifications
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_withdrawal', 'admin')) {
            return true;
        }

        // Entity owners can manage notifications for their withdrawals
        if ($this->isOwner($user, $withdrawal)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can generate withdrawal reports.
     */
    public function generateReports($user): bool
    {
        // Admins can generate all withdrawal reports
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage withdrawal compliance.
     */
    public function manageCompliance($user, Withdrawal $withdrawal): bool
    {
        // Only admins can manage withdrawal compliance (regulatory)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_withdrawal', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage withdrawal security.
     */
    public function manageSecurity($user, Withdrawal $withdrawal): bool
    {
        // Only admins can manage withdrawal security
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_withdrawal', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view withdrawal transaction details.
     */
    public function viewTransactionDetails($user, Withdrawal $withdrawal): bool
    {
        // Admins can view all transaction details
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_withdrawal', 'admin')) {
            return true;
        }

        // Entity owners can view their own transaction details
        if ($this->isOwner($user, $withdrawal)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage withdrawal priorities.
     */
    public function managePriorities($user, Withdrawal $withdrawal): bool
    {
        // Only admins can manage withdrawal priorities
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_withdrawal', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can reschedule withdrawals.
     */
    public function reschedule($user, Withdrawal $withdrawal): bool
    {
        // Admins can reschedule any withdrawal
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_withdrawal', 'admin')) {
            return true;
        }

        // Entity owners can reschedule their pending withdrawals
        if ($this->isOwner($user, $withdrawal) && $withdrawal->status === \App\Enums\WithdrawalStatusEnum::PENDING) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view withdrawal schedules.
     */
    public function viewSchedules($user): bool
    {
        // Admins can view all withdrawal schedules
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_withdrawal', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage withdrawal schedules.
     */
    public function manageSchedules($user): bool
    {
        // Only admins can manage withdrawal schedules
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_withdrawal', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Helper method to check if user is the owner of the withdrawal.
     */
    private function isOwner($user, Withdrawal $withdrawal): bool
    {
        // Check based on withdrawable type
        if ($withdrawal->withdrawable_type === 'Modules\Store\Models\Store') {
            if ($user instanceof Vendor && $withdrawal->withdrawable && $withdrawal->withdrawable->owner && $withdrawal->withdrawable->owner->id === $user->id) {
                return true;
            }
        } elseif ($withdrawal->withdrawable_type === 'Modules\Couier\Models\Couier') {
            if ($user instanceof Couier && $withdrawal->withdrawable_id === $user->id) {
                return true;
            }
        } elseif ($withdrawal->withdrawable_type === null && $withdrawal->user_id) {
            // Legacy user withdrawals
            if ($user instanceof User && $withdrawal->user_id === $user->id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Helper method to check if user is an entity owner.
     */
    private function isEntityOwner($user): bool
    {
        return $user instanceof Vendor || $user instanceof Couier || $user instanceof User;
    }
}
