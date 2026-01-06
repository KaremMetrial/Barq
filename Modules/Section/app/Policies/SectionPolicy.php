<?php

namespace Modules\Section\Policies;

use Modules\Section\Models\Section;
use Modules\Admin\Models\Admin;
use Modules\Vendor\Models\Vendor;
use Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Helpers\PermissionHelper;

class SectionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any sections.
     */
    public function viewAny($user): bool
    {
        // Admins can view all sections
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_section', 'admin')) {
            return true;
        }

        // Vendors can view sections
        if ($user instanceof Vendor) {
            return true;
        }

        // Users can view sections (public content)
        if ($user instanceof User) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the section.
     */
    public function view($user, Section $section): bool
    {
        // Admins can view all sections
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_section', 'admin')) {
            return true;
        }

        // Users can view active sections
        if ($user instanceof User && $section->is_active) {
            return true;
        }

        // Vendors can view sections
        if ($user instanceof Vendor) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create sections.
     */
    public function create($user): bool
    {
        // Only admins can create sections (platform content management)
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_section', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the section.
     */
    public function update($user, Section $section): bool
    {
        // Only admins can update sections (content management)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_section', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the section.
     */
    public function delete($user, Section $section): bool
    {
        // Only admins can delete sections (platform content management)
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_section', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the section.
     */
    public function restore($user, Section $section): bool
    {
        // Same logic as update
        return $this->update($user, $section);
    }

    /**
     * Determine whether the user can permanently delete the section.
     */
    public function forceDelete($user, Section $section): bool
    {
        // Same logic as delete
        return $this->delete($user, $section);
    }

    /**
     * Determine whether the user can manage section categories.
     */
    public function manageCategories($user, Section $section): bool
    {
        // Only admins can manage section categories
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_section', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view section categories.
     */
    public function viewCategories($user, Section $section): bool
    {
        // Everyone can view section categories (public content)
        return true;
    }

    /**
     * Determine whether the user can manage section countries.
     */
    public function manageCountries($user, Section $section): bool
    {
        // Only admins can manage section countries (geographic availability)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_section', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view section countries.
     */
    public function viewCountries($user, Section $section): bool
    {
        // Admins can view section countries
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_section', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can activate/deactivate sections.
     */
    public function toggleActive($user, Section $section): bool
    {
        // Only admins can activate/deactivate sections
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_section', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can toggle home page visibility.
     */
    public function toggleHomeVisibility($user, Section $section): bool
    {
        // Only admins can control home page visibility
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_section', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage section type.
     */
    public function manageType($user, Section $section): bool
    {
        // Only admins can change section types
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_section', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can duplicate sections.
     */
    public function duplicate($user, Section $section): bool
    {
        // Same logic as create
        return $this->create($user);
    }

    /**
     * Determine whether the user can export section data.
     */
    public function export($user): bool
    {
        // Admins can export section data
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_section', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can import section data.
     */
    public function import($user): bool
    {
        // Only admins can import sections
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_section', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk update sections.
     */
    public function bulkUpdate($user): bool
    {
        // Only admins can bulk update sections
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_section', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk delete sections.
     */
    public function bulkDelete($user): bool
    {
        // Only admins can bulk delete sections
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_section', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage section translations.
     */
    public function manageTranslations($user, Section $section): bool
    {
        // Only admins can manage translations
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_section', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view section analytics.
     */
    public function viewAnalytics($user, Section $section): bool
    {
        // Admins can view section analytics
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view section performance metrics.
     */
    public function viewPerformance($user, Section $section): bool
    {
        // Same logic as viewAnalytics
        return $this->viewAnalytics($user, $section);
    }

    /**
     * Determine whether the user can manage section ordering.
     */
    public function manageOrdering($user): bool
    {
        // Admins can manage section ordering (affects UI)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_section', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can archive sections.
     */
    public function archive($user, Section $section): bool
    {
        // Only admins can archive sections
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_section', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can unarchive sections.
     */
    public function unarchive($user, Section $section): bool
    {
        // Same logic as archive
        return $this->archive($user, $section);
    }

    /**
     * Determine whether the user can manage section icons.
     */
    public function manageIcons($user, Section $section): bool
    {
        // Only admins can manage section icons
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_section', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage restaurant settings.
     */
    public function manageRestaurantSettings($user, Section $section): bool
    {
        // Only admins can manage restaurant-specific settings
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_section', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view section statistics.
     */
    public function viewStatistics($user): bool
    {
        // Admins can view section statistics
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create section templates.
     */
    public function createTemplates($user): bool
    {
        // Only admins can create section templates
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_section', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can apply section templates.
     */
    public function applyTemplates($user): bool
    {
        // Same logic as create
        return $this->create($user);
    }

    /**
     * Determine whether the user can manage section SEO settings.
     */
    public function manageSeo($user, Section $section): bool
    {
        // Only admins can manage SEO settings
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_section', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can clone section structure.
     */
    public function cloneStructure($user, Section $section): bool
    {
        // Same logic as duplicate
        return $this->duplicate($user, $section);
    }

    /**
     * Determine whether the user can manage section content.
     */
    public function manageContent($user, Section $section): bool
    {
        // Same logic as update
        return $this->update($user, $section);
    }

    /**
     * Determine whether the user can preview sections.
     */
    public function preview($user, Section $section): bool
    {
        // Admins can preview sections
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_section', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can publish sections.
     */
    public function publish($user, Section $section): bool
    {
        // Same logic as toggleActive
        return $this->toggleActive($user, $section);
    }

    /**
     * Determine whether the user can unpublish sections.
     */
    public function unpublish($user, Section $section): bool
    {
        // Same logic as toggleActive
        return $this->toggleActive($user, $section);
    }

    /**
     * Determine whether the user can manage section visibility settings.
     */
    public function manageVisibility($user, Section $section): bool
    {
        // Only admins can manage visibility settings
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_section', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view section user engagement.
     */
    public function viewEngagement($user, Section $section): bool
    {
        // Admins can view engagement metrics
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage section targeting.
     */
    public function manageTargeting($user, Section $section): bool
    {
        // Only admins can manage geographic targeting
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_section', 'admin')) {
            return true;
        }

        return false;
    }
}
