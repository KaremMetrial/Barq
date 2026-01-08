<?php

namespace Modules\ContactUs\Policies;

use Modules\ContactUs\Models\ContactUs;
use Modules\Admin\Models\Admin;
use Modules\Vendor\Models\Vendor;
use Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Helpers\PermissionHelper;

class ContactUsPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any contact messages.
     */
    public function viewAny($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins with permission can view all contact messages
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_contact_us', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the contact message.
     */
    public function view($user, ContactUs $contactUs): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins with permission can view all contact messages
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_contact_us', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create contact messages.
     */
    public function create($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Any authenticated user can submit contact requests
        if ($user instanceof User || $user instanceof Vendor || $user instanceof Admin) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the contact message.
     */
    public function update($user, ContactUs $contactUs): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Only admins can update contact messages (for response management)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_contact_us', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the contact message.
     */
    public function delete($user, ContactUs $contactUs): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Only admins can delete contact messages
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_contact_us', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the contact message.
     */
    public function restore($user, ContactUs $contactUs): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as update
        return $this->update($user, $contactUs);
    }

    /**
     * Determine whether the user can permanently delete the contact message.
     */
    public function forceDelete($user, ContactUs $contactUs): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as delete
        return $this->delete($user, $contactUs);
    }

    /**
     * Determine whether the user can respond to the contact message.
     */
    public function respond($user, ContactUs $contactUs): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can respond to contact messages
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_contact_us', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can mark contact message as read.
     */
    public function markAsRead($user, ContactUs $contactUs): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can mark messages as read
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_contact_us', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can mark contact message as resolved.
     */
    public function markAsResolved($user, ContactUs $contactUs): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can mark messages as resolved
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_contact_us', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can assign contact message to another admin.
     */
    public function assign($user, ContactUs $contactUs): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can assign contact messages
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_contact_us', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view contact analytics/reports.
     */
    public function viewAnalytics($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins with report permission can view contact analytics
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can export contact messages.
     */
    public function export($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins with view permission can export contact data
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_contact_us', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk update contact messages.
     */
    public function bulkUpdate($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can bulk update contact messages
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_contact_us', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk delete contact messages.
     */
    public function bulkDelete($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can bulk delete contact messages
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_contact_us', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can categorize contact messages.
     */
    public function categorize($user, ContactUs $contactUs): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can categorize contact messages
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_contact_us', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can prioritize contact messages.
     */
    public function prioritize($user, ContactUs $contactUs): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can prioritize contact messages
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_contact_us', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view contact message history.
     */
    public function viewHistory($user, ContactUs $contactUs): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can view contact message history
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_contact_us', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can merge duplicate contact messages.
     */
    public function merge($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can merge duplicate contact messages
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_contact_us', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create templates for responses.
     */
    public function manageTemplates($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can manage response templates
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_contact_us', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view contact statistics.
     */
    public function viewStatistics($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins with report permission can view contact statistics
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can configure auto-responses.
     */
    public function configureAutoResponse($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can configure auto-response settings
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_contact_us', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage contact categories/tags.
     */
    public function manageCategories($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins can manage contact categories
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_contact_us', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view contact performance metrics.
     */
    public function viewPerformance($user): bool
    {
                // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins with report permission can view performance metrics
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        return false;
    }
}
