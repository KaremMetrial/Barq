<?php

namespace Modules\Slider\Policies;

use Modules\Slider\Models\Slider;
use Modules\Admin\Models\Admin;
use Modules\Vendor\Models\Vendor;
use Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Helpers\PermissionHelper;

class SliderPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any sliders.
     */
    public function viewAny($user): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins with permission
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_slider', 'admin')) {
            return true;
        }

        // Vendors with permission
        if ($user instanceof Vendor) {
            return true;
        }

        // Users can view active sliders
        if ($user instanceof User) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the slider.
     */
    public function view($user, Slider $slider): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins with permission can view all sliders
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_slider', 'admin')) {
            return true;
        }

        // Vendors with permission can view all sliders
        if ($user instanceof Vendor) {
            return true;
        }

        // Users can view active sliders
        if ($user instanceof User && $slider->is_active) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create sliders.
     */
    public function create($user): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins with permission
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_slider', 'admin')) {
            return true;
        }

        // Vendors with permission
        if ($user instanceof Vendor) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the slider.
     */
    public function update($user, Slider $slider): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins with permission can update all sliders
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_slider', 'admin')) {
            return true;
        }

        // Vendors with permission can update all sliders
        if ($user instanceof Vendor) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the slider.
     */
    public function delete($user, Slider $slider): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins with permission can delete all sliders
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_slider', 'admin')) {
            return true;
        }

        // Vendors with permission can delete all sliders
        if ($user instanceof Vendor) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the slider.
     */
    public function restore($user, Slider $slider): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as update
        return $this->update($user, $slider);
    }

    /**
     * Determine whether the user can permanently delete the slider.
     */
    public function forceDelete($user, Slider $slider): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Same logic as delete
        return $this->delete($user, $slider);
    }

    /**
     * Determine whether the user can reorder sliders.
     */
    public function reorder($user): bool
    {
        // Super Admin
        if ($user instanceof Admin && PermissionHelper::isSuperAdmin('admin')) {
            return true;
        }

        // Admins with update permission
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_slider', 'admin')) {
            return true;
        }

        // Vendors with update permiss
        if ($user instanceof Vendor) {
            return true;
        }

        return false;
    }
}