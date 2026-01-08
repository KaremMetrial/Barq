<?php

namespace Modules\Setting\Policies;

use Modules\Setting\Models\Setting;
use Modules\Admin\Models\Admin;
use Modules\Vendor\Models\Vendor;
use Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Helpers\PermissionHelper;

class SettingPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any settings.
     */
    public function viewAny($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can view system settings (security critical)
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the setting.
     */
    public function view($user, Setting $setting): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can view individual settings
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create settings.
     */
    public function create($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can create settings (system configuration)
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the setting.
     */
    public function update($user, Setting $setting): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can update settings (system configuration changes)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the setting.
     */
    public function delete($user, Setting $setting): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can delete settings (extremely sensitive operation)
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the setting.
     */
    public function restore($user, Setting $setting): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Same logic as update
        return $this->update($user, $setting);
    }

    /**
     * Determine whether the user can permanently delete the setting.
     */
    public function forceDelete($user, Setting $setting): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Same logic as delete
        return $this->delete($user, $setting);
    }

    /**
     * Determine whether the user can manage system settings.
     */
    public function manageSystem($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can manage system-level settings
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage application settings.
     */
    public function manageApplication($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can manage application settings
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage user settings.
     */
    public function manageUser($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can manage user-related settings
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage security settings.
     */
    public function manageSecurity($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can manage security settings (critical)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage payment settings.
     */
    public function managePayment($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can manage payment settings
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage notification settings.
     */
    public function manageNotification($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can manage notification settings
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage API settings.
     */
    public function manageApi($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can manage API settings
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage email settings.
     */
    public function manageEmail($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can manage email settings
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage SMS settings.
     */
    public function manageSms($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can manage SMS settings
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage social media settings.
     */
    public function manageSocial($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can manage social media settings
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage maintenance settings.
     */
    public function manageMaintenance($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can manage maintenance mode settings
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage performance settings.
     */
    public function managePerformance($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can manage performance settings
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can export settings.
     */
    public function export($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Admins can export settings for backup
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can import settings.
     */
    public function import($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can import settings (dangerous operation)
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk update settings.
     */
    public function bulkUpdate($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can bulk update settings
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can reset settings to defaults.
     */
    public function resetToDefaults($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can reset settings to defaults (destructive)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can duplicate settings.
     */
    public function duplicate($user, Setting $setting): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Same logic as create
        return $this->create($user);
    }

    /**
     * Determine whether the user can validate setting values.
     */
    public function validateValue($user, Setting $setting): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Admins can validate setting values
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can audit setting changes.
     */
    public function auditChanges($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Admins can audit setting changes
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage setting backups.
     */
    public function manageBackups($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can manage setting backups
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore settings from backup.
     */
    public function restoreFromBackup($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can restore from backup (dangerous operation)
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage setting categories.
     */
    public function manageCategories($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can manage setting categories
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view setting documentation.
     */
    public function viewDocumentation($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Admins can view setting documentation
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage setting permissions.
     */
    public function managePermissions($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can manage setting permissions
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can enable/disable maintenance mode.
     */
    public function toggleMaintenanceMode($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can toggle maintenance mode
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage rate limiting settings.
     */
    public function manageRateLimiting($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can manage rate limiting
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage cache settings.
     */
    public function manageCache($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can manage cache settings
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage logging settings.
     */
    public function manageLogging($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can manage logging settings
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage backup settings.
     */
    public function manageBackupSettings($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can manage backup settings
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage integration settings.
     */
    public function manageIntegrations($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can manage third-party integrations
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage feature flags.
     */
    public function manageFeatureFlags($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can manage feature flags
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage localization settings.
     */
    public function manageLocalization($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can manage localization settings
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage timezone settings.
     */
    public function manageTimezone($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can manage timezone settings
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage currency settings.
     */
    public function manageCurrency($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can manage currency settings
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_setting', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage tax settings.
     */
    public function manageTax($user): bool
    {
                        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }
        // Only admins can manage tax settings
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_setting', 'admin')) {
            return true;
        }

        return false;
    }
}
