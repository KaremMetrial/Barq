<?php

namespace Modules\PaymentMethod\Policies;

use Modules\PaymentMethod\Models\PaymentMethod;
use Modules\Admin\Models\Admin;
use Modules\Vendor\Models\Vendor;
use Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Helpers\PermissionHelper;

class PaymentMethodPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any payment methods.
     */
    public function viewAny($user): bool
    {
        // Admins can view all payment methods
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_payment_method', 'admin')) {
            return true;
        }

        // Users can view active payment methods for checkout
        if ($user instanceof User) {
            return true;
        }

        // Vendors can view payment methods
        if ($user instanceof Vendor) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the payment method.
     */
    public function view($user, PaymentMethod $paymentMethod): bool
    {
        // Admins can view all payment methods
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_payment_method', 'admin')) {
            return true;
        }

        // Users can view active payment methods
        if ($user instanceof User && $paymentMethod->is_active) {
            return true;
        }

        // Vendors can view payment methods
        if ($user instanceof Vendor) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create payment methods.
     */
    public function create($user): bool
    {
        // Only admins can create payment methods (financial infrastructure)
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_payment_method', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the payment method.
     */
    public function update($user, PaymentMethod $paymentMethod): bool
    {
        // Only admins can update payment methods (financial security)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_payment_method', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the payment method.
     */
    public function delete($user, PaymentMethod $paymentMethod): bool
    {
        // Only admins can delete payment methods (critical financial operations)
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_payment_method', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the payment method.
     */
    public function restore($user, PaymentMethod $paymentMethod): bool
    {
        // Same logic as update
        return $this->update($user, $paymentMethod);
    }

    /**
     * Determine whether the user can permanently delete the payment method.
     */
    public function forceDelete($user, PaymentMethod $paymentMethod): bool
    {
        // Same logic as delete
        return $this->delete($user, $paymentMethod);
    }

    /**
     * Determine whether the user can configure payment method settings.
     */
    public function configure($user, PaymentMethod $paymentMethod): bool
    {
        // Only admins can configure payment settings (contains API keys, secrets)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_payment_method', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can activate/deactivate payment methods.
     */
    public function toggleActive($user, PaymentMethod $paymentMethod): bool
    {
        // Only admins can activate/deactivate payment methods
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_payment_method', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can reorder payment methods.
     */
    public function reorder($user): bool
    {
        // Admins can reorder payment methods (affects checkout UI)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_payment_method', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can test payment method integration.
     */
    public function testIntegration($user, PaymentMethod $paymentMethod): bool
    {
        // Only admins can test payment integrations
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_payment_method', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view payment method analytics.
     */
    public function viewAnalytics($user, PaymentMethod $paymentMethod): bool
    {
        // Admins can view payment analytics
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view payment method transactions.
     */
    public function viewTransactions($user, PaymentMethod $paymentMethod): bool
    {
        // Admins can view payment transactions
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_payment_method', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can export payment method data.
     */
    public function export($user): bool
    {
        // Admins can export payment method data
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_payment_method', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can import payment method data.
     */
    public function import($user): bool
    {
        // Only admins can import payment methods
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_payment_method', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk update payment methods.
     */
    public function bulkUpdate($user): bool
    {
        // Only admins can bulk update payment methods
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_payment_method', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk delete payment methods.
     */
    public function bulkDelete($user): bool
    {
        // Only admins can bulk delete payment methods
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_payment_method', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can duplicate payment methods.
     */
    public function duplicate($user, PaymentMethod $paymentMethod): bool
    {
        // Same logic as create
        return $this->create($user);
    }

    /**
     * Determine whether the user can manage payment method fees.
     */
    public function manageFees($user, PaymentMethod $paymentMethod): bool
    {
        // Only admins can manage payment fees
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_payment_method', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage payment method currencies.
     */
    public function manageCurrencies($user, PaymentMethod $paymentMethod): bool
    {
        // Only admins can manage payment currencies
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_payment_method', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage payment method webhooks.
     */
    public function manageWebhooks($user, PaymentMethod $paymentMethod): bool
    {
        // Only admins can manage payment webhooks (security critical)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_payment_method', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view payment method logs.
     */
    public function viewLogs($user, PaymentMethod $paymentMethod): bool
    {
        // Admins can view payment logs
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_payment_method', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can process refunds for this payment method.
     */
    public function processRefunds($user, PaymentMethod $paymentMethod): bool
    {
        // Only admins can process refunds (financial operation)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_payment_method', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view payment method performance metrics.
     */
    public function viewPerformance($user, PaymentMethod $paymentMethod): bool
    {
        // Same logic as viewAnalytics
        return $this->viewAnalytics($user, $paymentMethod);
    }

    /**
     * Determine whether the user can manage payment method regions.
     */
    public function manageRegions($user, PaymentMethod $paymentMethod): bool
    {
        // Only admins can manage payment regions (affects availability)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_payment_method', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage payment method limits.
     */
    public function manageLimits($user, PaymentMethod $paymentMethod): bool
    {
        // Only admins can manage payment limits (transaction controls)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_payment_method', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view payment method security settings.
     */
    public function viewSecurity($user, PaymentMethod $paymentMethod): bool
    {
        // Admins can view payment security settings
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_payment_method', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage payment method security settings.
     */
    public function manageSecurity($user, PaymentMethod $paymentMethod): bool
    {
        // Only admins can manage payment security (PCI compliance, etc.)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_payment_method', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can run payment method compliance checks.
     */
    public function runComplianceChecks($user, PaymentMethod $paymentMethod): bool
    {
        // Only admins can run compliance checks
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_payment_method', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage payment method notifications.
     */
    public function manageNotifications($user, PaymentMethod $paymentMethod): bool
    {
        // Only admins can manage payment notifications
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_payment_method', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view payment method integration docs.
     */
    public function viewIntegrationDocs($user, PaymentMethod $paymentMethod): bool
    {
        // Admins can view integration documentation
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_payment_method', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update payment method integration.
     */
    public function updateIntegration($user, PaymentMethod $paymentMethod): bool
    {
        // Only admins can update payment integrations (API changes)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_payment_method', 'admin')) {
            return true;
        }

        return false;
    }
}
