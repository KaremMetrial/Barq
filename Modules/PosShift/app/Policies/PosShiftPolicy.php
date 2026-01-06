<?php

namespace Modules\PosShift\Policies;

use Modules\PosShift\Models\PosShift;
use Modules\Admin\Models\Admin;
use Modules\Vendor\Models\Vendor;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Helpers\PermissionHelper;

class PosShiftPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any POS shifts.
     */
    public function viewAny($user): bool
    {
        // Admins can view all POS shifts
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_pos_shift', 'admin')) {
            return true;
        }

        // Vendors can view their own POS shifts
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_pos_shift', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the POS shift.
     */
    public function view($user, PosShift $posShift): bool
    {
        // Admins can view all POS shifts
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_pos_shift', 'admin')) {
            return true;
        }

        // Vendors can only view their own POS shifts
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_pos_shift', 'vendor')) {
            return $posShift->vendor_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create POS shifts.
     */
    public function create($user): bool
    {
        // Admins can create POS shifts for any vendor
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_pos_shift', 'admin')) {
            return true;
        }

        // Vendors can create POS shifts for themselves
        if ($user instanceof Vendor && PermissionHelper::hasPermission('create_pos_shift', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the POS shift.
     */
    public function update($user, PosShift $posShift): bool
    {
        // Admins can update any POS shift
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_pos_shift', 'admin')) {
            return true;
        }

        // Vendors can only update their own POS shifts
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_pos_shift', 'vendor')) {
            return $posShift->vendor_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the POS shift.
     */
    public function delete($user, PosShift $posShift): bool
    {
        // Only admins can delete POS shifts (financial records)
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_pos_shift', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the POS shift.
     */
    public function restore($user, PosShift $posShift): bool
    {
        // Same logic as update
        return $this->update($user, $posShift);
    }

    /**
     * Determine whether the user can permanently delete the POS shift.
     */
    public function forceDelete($user, PosShift $posShift): bool
    {
        // Same logic as delete
        return $this->delete($user, $posShift);
    }

    /**
     * Determine whether the user can open a POS shift.
     */
    public function open($user): bool
    {
        // Admins can open shifts for any vendor
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_pos_shift', 'admin')) {
            return true;
        }

        // Vendors can open shifts for themselves
        if ($user instanceof Vendor && PermissionHelper::hasPermission('create_pos_shift', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can close a POS shift.
     */
    public function close($user, PosShift $posShift): bool
    {
        // Admins can close any POS shift
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_pos_shift', 'admin')) {
            return true;
        }

        // Vendors can only close their own POS shifts
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_pos_shift', 'vendor')) {
            return $posShift->vendor_id === $user->id && !$posShift->closed_at;
        }

        return false;
    }

    /**
     * Determine whether the user can view POS shift analytics.
     */
    public function viewAnalytics($user): bool
    {
        // Admins can view all POS shift analytics
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        // Vendors can view their own POS shift analytics
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_report', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view POS shift financial reports.
     */
    public function viewFinancialReports($user, PosShift $posShift): bool
    {
        // Admins can view all financial reports
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_pos_shift', 'admin')) {
            return true;
        }

        // Vendors can view financial reports for their own shifts
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_pos_shift', 'vendor')) {
            return $posShift->vendor_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can export POS shift data.
     */
    public function export($user): bool
    {
        // Admins can export all POS shift data
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_pos_shift', 'admin')) {
            return true;
        }

        // Vendors can export their own POS shift data
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_pos_shift', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage POS shift terminals.
     */
    public function manageTerminals($user, PosShift $posShift): bool
    {
        // Admins can manage any POS terminal assignment
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_pos_shift', 'admin')) {
            return true;
        }

        // Vendors can manage terminal assignments for their shifts
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_pos_shift', 'vendor')) {
            return $posShift->vendor_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can adjust POS shift balances.
     */
    public function adjustBalances($user, PosShift $posShift): bool
    {
        // Only admins can adjust shift balances (financial integrity)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_pos_shift', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view POS shift discrepancies.
     */
    public function viewDiscrepancies($user, PosShift $posShift): bool
    {
        // Admins can view all discrepancies
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_pos_shift', 'admin')) {
            return true;
        }

        // Vendors can view discrepancies in their own shifts
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_pos_shift', 'vendor')) {
            return $posShift->vendor_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can resolve POS shift discrepancies.
     */
    public function resolveDiscrepancies($user, PosShift $posShift): bool
    {
        // Only admins can resolve discrepancies (financial authority)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_pos_shift', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can force close a POS shift.
     */
    public function forceClose($user, PosShift $posShift): bool
    {
        // Only admins can force close shifts (emergency situations)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_pos_shift', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view POS shift sales data.
     */
    public function viewSalesData($user, PosShift $posShift): bool
    {
        // Same logic as view
        return $this->view($user, $posShift);
    }

    /**
     * Determine whether the user can manage POS shift carts.
     */
    public function manageCarts($user, PosShift $posShift): bool
    {
        // Admins can manage any shift cart
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_pos_shift', 'admin')) {
            return true;
        }

        // Vendors can manage carts for their own shifts
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_pos_shift', 'vendor')) {
            return $posShift->vendor_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can view POS shift performance metrics.
     */
    public function viewPerformance($user, PosShift $posShift): bool
    {
        // Admins can view all performance metrics
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        // Vendors can view performance for their shifts
        if ($user instanceof Vendor) {
            if ($posShift->vendor_id === $user->id) {
                return PermissionHelper::hasPermission('view_report', 'vendor');
            }
            return false;
        }

        return false;
    }

    /**
     * Determine whether the user can audit POS shifts.
     */
    public function audit($user, PosShift $posShift): bool
    {
        // Admins can audit any POS shift
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_pos_shift', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can void POS shift transactions.
     */
    public function voidTransactions($user, PosShift $posShift): bool
    {
        // Only admins can void transactions (financial control)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_pos_shift', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can reprint POS shift receipts.
     */
    public function reprintReceipts($user, PosShift $posShift): bool
    {
        // Admins can reprint any receipts
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_pos_shift', 'admin')) {
            return true;
        }

        // Vendors can reprint receipts from their shifts
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_pos_shift', 'vendor')) {
            return $posShift->vendor_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can manage POS shift reports.
     */
    public function manageReports($user): bool
    {
        // Admins can manage all POS reports
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        // Vendors can manage their own POS reports
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_report', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view POS shift cash flow.
     */
    public function viewCashFlow($user, PosShift $posShift): bool
    {
        // Admins can view all cash flow
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_pos_shift', 'admin')) {
            return true;
        }

        // Vendors can view cash flow for their shifts
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_pos_shift', 'vendor')) {
            return $posShift->vendor_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can manage POS shift policies.
     */
    public function managePolicies($user): bool
    {
        // Only admins can manage POS policies (business rules)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_pos_shift', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can override POS shift limits.
     */
    public function overrideLimits($user, PosShift $posShift): bool
    {
        // Only admins can override limits (emergency situations)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_pos_shift', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can transfer POS shifts between vendors.
     */
    public function transfer($user, PosShift $posShift): bool
    {
        // Only admins can transfer shifts between vendors
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_pos_shift', 'admin')) {
            return true;
        }

        return false;
    }
}
