<?php

namespace Modules\Order\Policies;

use Modules\Order\Models\Order;
use Modules\Admin\Models\Admin;
use Modules\Vendor\Models\Vendor;
use Modules\User\Models\User;
use Modules\Couier\Models\Couier;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Helpers\PermissionHelper;

class OrderPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any orders.
     */
    public function viewAny($user): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can view all orders
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_order', 'admin')) {
            return true;
        }

        // Vendors can view orders from their stores
        if ($user instanceof Vendor) {
            return true;
        }

        // Users can view their own orders
        if ($user instanceof User) {
            return true;
        }

        // Couriers can view orders assigned to them
        if ($user instanceof Couier) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the order.
     */
    public function view($user, Order $order): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can view all orders
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_order', 'admin')) {
            return true;
        }

        // Vendors can view orders from their stores
        if ($user instanceof Vendor) {
            return $order->store_id === $user->store_id;
        }

        // Users can view their own orders
        if ($user instanceof User && $order->user_id === $user->id) {
            return true;
        }

        // Couriers can view orders assigned to them
        if ($user instanceof Couier && $order->couier_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create orders.
     */
    public function create($user): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Any authenticated user can create orders
        if ($user instanceof User || $user instanceof Vendor || $user instanceof Admin) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the order.
     */
    public function update($user, Order $order): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can update any order
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_order', 'admin')) {
            return true;
        }

        // Vendors can update orders from their stores (limited operations)
        if ($user instanceof Vendor) {
            return $order->store_id === $user->store_id;
        }

        // Users can update their own orders (limited to certain operations like cancellation)
        if ($user instanceof User && $order->user_id === $user->id) {
            // Allow updates only for certain statuses (e.g., pending orders)
            return in_array($order->status->value, ['pending', 'confirmed']);
        }

        // Couriers can update order status during delivery
        if ($user instanceof Couier && $order->couier_id === $user->id) {
            return in_array($order->status->value, ['on_the_way', 'delivered']);
        }

        return false;
    }

    /**
     * Determine whether the user can delete the order.
     */
    public function delete($user, Order $order): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Only admins can delete orders (extremely sensitive operation)
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_order', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the order.
     */
    public function restore($user, Order $order): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as delete
        return $this->delete($user, $order);
    }

    /**
     * Determine whether the user can permanently delete the order.
     */
    public function forceDelete($user, Order $order): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as delete
        return $this->delete($user, $order);
    }

    /**
     * Determine whether the user can cancel the order.
     */
    public function cancel($user, Order $order): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can cancel any order
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_order', 'admin')) {
            return true;
        }

        // Vendors can cancel orders from their stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_order', 'vendor')) {
            return $order->store_id === $user->store_id &&
                in_array($order->status->value, ['pending', 'confirmed', 'processing']);
        }

        // Users can cancel their own orders
        if ($user instanceof User && $order->user_id === $user->id) {
            return in_array($order->status->value, ['pending', 'confirmed']);
        }

        return false;
    }

    /**
     * Determine whether the user can change order status.
     */
    public function changeStatus($user, Order $order): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can change any order status
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_order', 'admin')) {
            return true;
        }

        // Vendors can change status of their store's orders
        if ($user instanceof Vendor) {
            return $order->store_id === $user->store_id;
        }

        // Couriers can update delivery-related statuses
        if ($user instanceof Couier && $order->couier_id === $user->id) {
            return in_array($order->status->value, ['on_the_way', 'delivered']);
        }

        return false;
    }

    /**
     * Determine whether the user can assign courier to order.
     */
    public function assignCourier($user, Order $order): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can assign any courier to any order
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_order', 'admin')) {
            return true;
        }

        // Vendors can assign couriers to their store's orders
        if ($user instanceof Vendor) {
            return $order->store_id === $user->store_id;
        }

        return false;
    }

    /**
     * Determine whether the user can view order items.
     */
    public function viewItems($user, Order $order): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as view order
        return $this->view($user, $order);
    }

    /**
     * Determine whether the user can update order items.
     */
    public function updateItems($user, Order $order): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can update any order items
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_order', 'admin')) {
            return true;
        }

        // Vendors can update items in their store's orders (limited)
        if ($user instanceof Vendor) {
            return $order->store_id === $user->store_id &&
                in_array($order->status->value, ['pending', 'confirmed']);
        }

        return false;
    }

    /**
     * Determine whether the user can view order proofs.
     */
    public function viewProofs($user, Order $order): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as view order
        return $this->view($user, $order);
    }

    /**
     * Determine whether the user can manage order proofs.
     */
    public function manageProofs($user, Order $order): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can manage all proofs
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_order', 'admin')) {
            return true;
        }

        // Vendors can manage proofs for their orders
        if ($user instanceof Vendor) {
            return $order->store_id === $user->store_id;
        }

        // Couriers can add delivery proofs
        if ($user instanceof Couier && $order->couier_id === $user->id) {
            return $order->status->value === 'delivered';
        }

        return false;
    }

    /**
     * Determine whether the user can view order reviews.
     */
    public function viewReviews($user, Order $order): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Everyone can view reviews (public)
        return true;
    }

    /**
     * Determine whether the user can create reviews for the order.
     */
    public function createReview($user, Order $order): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Users can review their own delivered orders
        if ($user instanceof User && $order->user_id === $user->id) {
            return $order->status->value === 'delivered';
        }

        return false;
    }

    /**
     * Determine whether the user can view order analytics.
     */
    public function viewAnalytics($user): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can view order analytics
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        // Vendors can view analytics for their stores
        if ($user instanceof Vendor) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can export order data.
     */
    public function export($user): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can export all order data
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_order', 'admin')) {
            return true;
        }

        // Vendors can export data for their stores
        if ($user instanceof Vendor) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can process refunds.
     */
    public function processRefund($user, Order $order): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Only admins can process refunds (financial operation)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_order', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view order payment information.
     */
    public function viewPayment($user, Order $order): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can view all payment information
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_order', 'admin')) {
            return true;
        }

        // Vendors can view payment info for their orders
        if ($user instanceof Vendor) {
            return $order->store_id === $user->store_id;
        }

        // Users can view payment info for their orders
        if ($user instanceof User && $order->user_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update payment status.
     */
    public function updatePaymentStatus($user, Order $order): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can update any payment status
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_order', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view order delivery information.
     */
    public function viewDelivery($user, Order $order): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as view order
        return $this->view($user, $order);
    }

    /**
     * Determine whether the user can update delivery information.
     */
    public function updateDelivery($user, Order $order): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can update any delivery information
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_order', 'admin')) {
            return true;
        }

        // Couriers can update delivery status and timing
        if ($user instanceof Couier && $order->couier_id === $user->id) {
            return in_array($order->status->value, ['on_the_way', 'delivered']);
        }

        return false;
    }

    /**
     * Determine whether the user can use OTP for order verification.
     */
    public function useOtp($user, Order $order): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Users can use OTP for their orders
        if ($user instanceof User && $order->user_id === $user->id && $order->requires_otp) {
            return true;
        }

        // Couriers can use OTP for delivery verification
        if ($user instanceof Couier && $order->couier_id === $user->id && $order->requires_otp) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can mark order as read.
     */
    public function markAsRead($user, Order $order): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can mark any order as read
        if ($user instanceof Admin) {
            return true;
        }

        // Vendors can mark their store's orders as read
        if ($user instanceof Vendor && $order->store_id === $user->store_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view order history/timeline.
     */
    public function viewHistory($user, Order $order): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as view order
        return $this->view($user, $order);
    }

    /**
     * Determine whether the user can manage order notes.
     */
    public function manageNotes($user, Order $order): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can manage any order notes
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_order', 'admin')) {
            return true;
        }

        // Vendors can manage notes for their orders
        if ($user instanceof Vendor) {
            return $order->store_id === $user->store_id;
        }

        return false;
    }

    /**
     * Determine whether the user can duplicate orders.
     */
    public function duplicate($user, Order $order): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can duplicate any order for testing
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_order', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk update orders.
     */
    public function bulkUpdate($user): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can bulk update orders
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_order', 'admin')) {
            return true;
        }

        // Vendors can bulk update their store's orders
        if ($user instanceof Vendor) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk delete orders.
     */
    public function bulkDelete($user): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Only admins can bulk delete orders
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_order', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view order performance metrics.
     */
    public function viewPerformance($user, Order $order): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can view all order performance
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        // Vendors can view performance for their orders
        if ($user instanceof Vendor) {
            if ($order->store_id === $user->store_id) {
                return true;
            }
            return false;
        }

        return false;
    }

    /**
     * Determine whether the user can reassign orders between couriers.
     */
    public function reassignCourier($user, Order $order): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can reassign any order
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_order', 'admin')) {
            return true;
        }

        // Vendors can reassign orders in their stores
        if ($user instanceof Vendor) {
            return $order->store_id === $user->store_id;
        }

        return false;
    }

    /**
     * Determine whether the user can view order financial summary.
     */
    public function viewFinancialSummary($user, Order $order): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can view all financial summaries
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_order', 'admin')) {
            return true;
        }

        // Vendors can view financial summaries for their orders
        if ($user instanceof Vendor) {
            return $order->store_id === $user->store_id;
        }

        // Users can view financial summaries for their orders
        if ($user instanceof User && $order->user_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can apply discounts to orders.
     */
    public function applyDiscount($user, Order $order): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can apply discounts to any order
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_order', 'admin')) {
            return true;
        }

        // Vendors can apply discounts to their orders
        if ($user instanceof Vendor) {
            return $order->store_id === $user->store_id &&
                in_array($order->status->value, ['pending', 'confirmed']);
        }

        return false;
    }

    /**
     * Determine whether the user can modify order totals.
     */
    public function modifyTotal($user, Order $order): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Only admins can modify order totals (financial integrity)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_order', 'admin')) {
            return true;
        }

        return false;
    }
}
