<?php

namespace Modules\Page\Policies;

use Modules\Page\Models\Page;
use Modules\Admin\Models\Admin;
use Modules\Vendor\Models\Vendor;
use Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Helpers\PermissionHelper;

class PagePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any pages.
     */
    public function viewAny($user): bool
    {
        // Admins can view all pages (including inactive ones)
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_page', 'admin')) {
            return true;
        }

        // Users can view active pages
        if ($user instanceof User) {
            return true;
        }

        // Vendors can view pages
        if ($user instanceof Vendor) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the page.
     */
    public function view($user, Page $page): bool
    {
        // Admins can view all pages
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_page', 'admin')) {
            return true;
        }

        // Users can view active pages
        if ($user instanceof User && $page->is_active) {
            return true;
        }

        // Vendors can view pages
        if ($user instanceof Vendor) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create pages.
     */
    public function create($user): bool
    {
        // Only admins can create pages (content management)
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_page', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the page.
     */
    public function update($user, Page $page): bool
    {
        // Only admins can update pages (content management)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_page', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the page.
     */
    public function delete($user, Page $page): bool
    {
        // Only admins can delete pages (content management)
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_page', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the page.
     */
    public function restore($user, Page $page): bool
    {
        // Same logic as update
        return $this->update($user, $page);
    }

    /**
     * Determine whether the user can permanently delete the page.
     */
    public function forceDelete($user, Page $page): bool
    {
        // Same logic as delete
        return $this->delete($user, $page);
    }

    /**
     * Determine whether the user can publish/unpublish the page.
     */
    public function toggleActive($user, Page $page): bool
    {
        // Only admins can publish/unpublish pages
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_page', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage page translations.
     */
    public function manageTranslations($user, Page $page): bool
    {
        // Only admins can manage translations
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_page', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage page SEO settings.
     */
    public function manageSeo($user, Page $page): bool
    {
        // Only admins can manage SEO settings
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_page', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can duplicate pages.
     */
    public function duplicate($user, Page $page): bool
    {
        // Same logic as create
        return $this->create($user);
    }

    /**
     * Determine whether the user can export page data.
     */
    public function export($user): bool
    {
        // Admins can export page data
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_page', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can import page data.
     */
    public function import($user): bool
    {
        // Only admins can import pages
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_page', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk update pages.
     */
    public function bulkUpdate($user): bool
    {
        // Only admins can bulk update pages
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_page', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk delete pages.
     */
    public function bulkDelete($user): bool
    {
        // Only admins can bulk delete pages
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_page', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can preview the page.
     */
    public function preview($user, Page $page): bool
    {
        // Admins can preview any page
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_page', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view page analytics.
     */
    public function viewAnalytics($user, Page $page): bool
    {
        // Admins can view page analytics
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage page revisions.
     */
    public function manageRevisions($user, Page $page): bool
    {
        // Only admins can manage revisions
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_page', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can schedule page publication.
     */
    public function schedulePublication($user, Page $page): bool
    {
        // Only admins can schedule publication
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_page', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage page templates.
     */
    public function manageTemplates($user): bool
    {
        // Only admins can manage page templates
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_page', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view page performance metrics.
     */
    public function viewPerformance($user, Page $page): bool
    {
        // Same logic as viewAnalytics
        return $this->viewAnalytics($user, $page);
    }

    /**
     * Determine whether the user can manage page categories/tags.
     */
    public function manageCategories($user, Page $page): bool
    {
        // Only admins can manage page categorization
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_page', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can set page as homepage.
     */
    public function setAsHomepage($user, Page $page): bool
    {
        // Only admins can set homepage
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_page', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage page menu placement.
     */
    public function manageMenu($user, Page $page): bool
    {
        // Only admins can manage menu placement
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_page', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage page redirects.
     */
    public function manageRedirects($user, Page $page): bool
    {
        // Only admins can manage redirects
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_page', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view page revision history.
     */
    public function viewRevisions($user, Page $page): bool
    {
        // Admins can view revision history
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_page', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore page from revision.
     */
    public function restoreRevision($user, Page $page): bool
    {
        // Only admins can restore revisions
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_page', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage page comments.
     */
    public function manageComments($user, Page $page): bool
    {
        // Only admins can manage comments
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_page', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can export page as PDF.
     */
    public function exportPdf($user, Page $page): bool
    {
        // Admins can export pages as PDF
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_page', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can clone page structure.
     */
    public function cloneStructure($user, Page $page): bool
    {
        // Same logic as duplicate
        return $this->duplicate($user, $page);
    }
}
