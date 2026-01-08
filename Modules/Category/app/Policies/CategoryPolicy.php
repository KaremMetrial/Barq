<?php

namespace Modules\Category\Policies;

use Modules\Category\Models\Category;
use Modules\Admin\Models\Admin;
use Modules\Vendor\Models\Vendor;
use Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Helpers\PermissionHelper;

class CategoryPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any categories.
     */
    public function viewAny($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins with permission
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_category', 'admin')) {
            return true;
        }

        // Vendors with permission
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_category', 'vendor')) {
            return true;
        }

        // Users can view active categories
        if ($user instanceof User) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the category.
     */
    public function view($user, Category $category): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins with permission can view all categories
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_category', 'admin')) {
            return true;
        }

        // Vendors can view categories from their store or general categories
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_category', 'vendor')) {
            return $category->store_id === $user->store_id || !$category->store_id;
        }

        // Users can view active categories
        if ($user instanceof User && $category->is_active) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create categories.
     */
    public function create($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins with permission
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_category', 'admin')) {
            return true;
        }

        // Vendors with permission can create categories for their store
        if ($user instanceof Vendor && PermissionHelper::hasPermission('create_category', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the category.
     */
    public function update($user, Category $category): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins with permission can update all categories
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_category', 'admin')) {
            return true;
        }

        // Vendors can only update categories from their store
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_category', 'vendor')) {
            return $category->store_id === $user->store_id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the category.
     */
    public function delete($user, Category $category): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins with permission can delete all categories
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_category', 'admin')) {
            return true;
        }

        // Vendors can only delete categories from their store
        if ($user instanceof Vendor && PermissionHelper::hasPermission('delete_category', 'vendor')) {
            return $category->store_id === $user->store_id;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the category.
     */
    public function restore($user, Category $category): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as update
        return $this->update($user, $category);
    }

    /**
     * Determine whether the user can permanently delete the category.
     */
    public function forceDelete($user, Category $category): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as delete
        return $this->delete($user, $category);
    }

    /**
     * Determine whether the user can manage category hierarchy (parent/child relationships).
     */
    public function manageHierarchy($user, Category $category): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as update (hierarchy management is part of category management)
        return $this->update($user, $category);
    }

    /**
     * Determine whether the user can reorder categories.
     */
    public function reorder($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins with update permission
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_category', 'admin')) {
            return true;
        }

        // Vendors with update permission (limited to their store's categories)
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_category', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can attach sections to category.
     */
    public function attachSections($user, Category $category): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as update
        return $this->update($user, $category);
    }

    /**
     * Determine whether the user can detach sections from category.
     */
    public function detachSections($user, Category $category): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as update
        return $this->update($user, $category);
    }

    /**
     * Determine whether the user can attach coupons to category.
     */
    public function attachCoupons($user, Category $category): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as update
        return $this->update($user, $category);
    }

    /**
     * Determine whether the user can detach coupons from category.
     */
    public function detachCoupons($user, Category $category): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as update
        return $this->update($user, $category);
    }

    /**
     * Determine whether the user can view category analytics/reports.
     */
    public function viewAnalytics($user, Category $category): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins with report permission
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        // Vendors can view analytics for their own store's categories
        if ($user instanceof Vendor && $category->store_id === $user->store_id) {
            return PermissionHelper::hasPermission('view_report', 'vendor');
        }

        return false;
    }

    /**
     * Determine whether the user can manage category settings.
     */
    public function manageSettings($user, Category $category): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Only admins can manage global category settings
        if ($user instanceof Admin) {
            return PermissionHelper::hasPermission('update_category', 'admin');
        }

        return false;
    }

    /**
     * Determine whether the user can feature/unfeature categories.
     */
    public function toggleFeatured($user, Category $category): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as update
        return $this->update($user, $category);
    }

    /**
     * Determine whether the user can activate/deactivate categories.
     */
    public function toggleActive($user, Category $category): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as update
        return $this->update($user, $category);
    }

    /**
     * Determine whether the user can bulk update categories.
     */
    public function bulkUpdate($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins with update permission
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_category', 'admin')) {
            return true;
        }

        // Vendors with update permission (limited to their store)
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_category', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk delete categories.
     */
    public function bulkDelete($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins with delete permission
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_category', 'admin')) {
            return true;
        }

        // Vendors with delete permission (limited to their store)
        if ($user instanceof Vendor && PermissionHelper::hasPermission('delete_category', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can export categories.
     */
    public function export($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins with view permission
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_category', 'admin')) {
            return true;
        }

        // Vendors with view permission
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_category', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can import categories.
     */
    public function import($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Only admins can import (for security and data consistency)
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_category', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can duplicate categories.
     */
    public function duplicate($user, Category $category): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as create (duplicating requires create permission)
        return $this->create($user);
    }

    /**
     * Determine whether the user can manage category products.
     */
    public function manageProducts($user, Category $category): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as update (product management is part of category management)
        return $this->update($user, $category);
    }

    /**
     * Determine whether the user can view category tree/hierarchy.
     */
    public function viewHierarchy($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins with view permission
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_category', 'admin')) {
            return true;
        }

        // Vendors with view permission
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_category', 'vendor')) {
            return true;
        }

        // Users can view hierarchy of active categories
        if ($user instanceof User) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can merge categories.
     */
    public function merge($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Only admins can merge categories (complex operation)
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_category', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can move categories between stores.
     */
    public function moveBetweenStores($user, Category $category): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Only admins can move categories between stores
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_category', 'admin')) {
            return true;
        }

        return false;
    }
}
