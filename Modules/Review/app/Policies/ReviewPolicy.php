<?php

namespace Modules\Review\Policies;

use Modules\Review\Models\Review;
use Modules\Admin\Models\Admin;
use Modules\Vendor\Models\Vendor;
use Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Helpers\PermissionHelper;

class ReviewPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any reviews.
     */
    public function viewAny($user): bool
    {
        // Everyone can view reviews (public content)
        return true;
    }

    /**
     * Determine whether the user can view the review.
     */
    public function view($user, Review $review): bool
    {
        // Everyone can view reviews (public content)
        return true;
    }

    /**
     * Determine whether the user can create reviews.
     */
    public function create($user): bool
    {
        // Users can create reviews
        if ($user instanceof User) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the review.
     */
    public function update($user, Review $review): bool
    {
        // Users can update their own reviews
        if ($user instanceof User && $review->user && $review->user->id === $user->id) {
            return true;
        }

        // Admins can update any review
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_review', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the review.
     */
    public function delete($user, Review $review): bool
    {
        // Users can delete their own reviews
        if ($user instanceof User && $review->user && $review->user->id === $user->id) {
            return true;
        }

        // Admins can delete any review
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_review', 'admin')) {
            return true;
        }

        // Vendors can delete reviews for their products/stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('delete_review', 'vendor')) {
            // Check if review is for vendor's store
            if ($review->reviewable_type === 'Modules\Store\Models\Store' && $review->reviewable_id === $user->store_id) {
                return true;
            }
            // Check if review is for vendor's products
            if ($review->reviewable_type === 'Modules\Product\Models\Product') {
                $product = $review->reviewable;
                if ($product && $product->store_id === $user->store_id) {
                    return true;
                }
            }
            return false;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the review.
     */
    public function restore($user, Review $review): bool
    {
        // Admins can restore any review
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_review', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the review.
     */
    public function forceDelete($user, Review $review): bool
    {
        // Same logic as delete for admins
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_review', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can moderate the review.
     */
    public function moderate($user, Review $review): bool
    {
        // Admins can moderate any review
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_review', 'admin')) {
            return true;
        }

        // Vendors can moderate reviews for their products/stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_review', 'vendor')) {
            // Check if review is for vendor's store
            if ($review->reviewable_type === 'Modules\Store\Models\Store' && $review->reviewable_id === $user->store_id) {
                return true;
            }
            // Check if review is for vendor's products
            if ($review->reviewable_type === 'Modules\Product\Models\Product') {
                $product = $review->reviewable;
                if ($product && $product->store_id === $user->store_id) {
                    return true;
                }
            }
            return false;
        }

        return false;
    }

    /**
     * Determine whether the user can approve the review.
     */
    public function approve($user, Review $review): bool
    {
        // Same logic as moderate
        return $this->moderate($user, $review);
    }

    /**
     * Determine whether the user can reject the review.
     */
    public function reject($user, Review $review): bool
    {
        // Same logic as moderate
        return $this->moderate($user, $review);
    }

    /**
     * Determine whether the user can flag the review.
     */
    public function flag($user, Review $review): bool
    {
        // Users can flag reviews (report inappropriate content)
        if ($user instanceof User) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can respond to the review.
     */
    public function respond($user, Review $review): bool
    {
        // Admins can respond to any review
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_review', 'admin')) {
            return true;
        }

        // Vendors can respond to reviews for their products/stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_review', 'vendor')) {
            // Check if review is for vendor's store
            if ($review->reviewable_type === 'Modules\Store\Models\Store' && $review->reviewable_id === $user->store_id) {
                return true;
            }
            // Check if review is for vendor's products
            if ($review->reviewable_type === 'Modules\Product\Models\Product') {
                $product = $review->reviewable;
                if ($product && $product->store_id === $user->store_id) {
                    return true;
                }
            }
            return false;
        }

        return false;
    }

    /**
     * Determine whether the user can view review analytics.
     */
    public function viewAnalytics($user): bool
    {
        // Admins can view review analytics
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        // Vendors can view analytics for their reviews
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_report', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can export review data.
     */
    public function export($user): bool
    {
        // Admins can export all review data
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_review', 'admin')) {
            return true;
        }

        // Vendors can export data for their reviews
        if ($user instanceof Vendor && PermissionHelper::hasPermission('view_review', 'vendor')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk update reviews.
     */
    public function bulkUpdate($user): bool
    {
        // Admins can bulk update reviews
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_review', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk delete reviews.
     */
    public function bulkDelete($user): bool
    {
        // Admins can bulk delete reviews
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_review', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view review ratings.
     */
    public function viewRatings($user, Review $review): bool
    {
        // Everyone can view review ratings (public content)
        return true;
    }

    /**
     * Determine whether the user can manage review ratings.
     */
    public function manageRatings($user, Review $review): bool
    {
        // Users can manage ratings for their own reviews
        if ($user instanceof User && $review->user && $review->user->id === $user->id) {
            return true;
        }

        // Admins can manage any review ratings
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_review', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view review images.
     */
    public function viewImages($user, Review $review): bool
    {
        // Everyone can view review images (public content)
        return true;
    }

    /**
     * Determine whether the user can manage review images.
     */
    public function manageImages($user, Review $review): bool
    {
        // Users can manage images for their own reviews
        if ($user instanceof User && $review->user && $review->user->id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can like/dislike the review.
     */
    public function like($user, Review $review): bool
    {
        // Users can like/dislike reviews (except their own)
        if ($user instanceof User && (!$review->user || $review->user->id !== $user->id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can report the review.
     */
    public function report($user, Review $review): bool
    {
        // Users can report reviews (except their own)
        if ($user instanceof User && (!$review->user || $review->user->id !== $user->id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view review reports.
     */
    public function viewReports($user, Review $review): bool
    {
        // Admins can view all review reports
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_review', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage review reports.
     */
    public function manageReports($user, Review $review): bool
    {
        // Admins can manage review reports
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_review', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can pin/unpin the review.
     */
    public function pin($user, Review $review): bool
    {
        // Admins can pin/unpin any review
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_review', 'admin')) {
            return true;
        }

        // Vendors can pin reviews for their products/stores
        if ($user instanceof Vendor && PermissionHelper::hasPermission('update_review', 'vendor')) {
            // Check if review is for vendor's store
            if ($review->reviewable_type === 'Modules\Store\Models\Store' && $review->reviewable_id === $user->store_id) {
                return true;
            }
            // Check if review is for vendor's products
            if ($review->reviewable_type === 'Modules\Product\Models\Product') {
                $product = $review->reviewable;
                if ($product && $product->store_id === $user->store_id) {
                    return true;
                }
            }
            return false;
        }

        return false;
    }

    /**
     * Determine whether the user can feature/unfeature the review.
     */
    public function feature($user, Review $review): bool
    {
        // Same logic as pin
        return $this->pin($user, $review);
    }

    /**
     * Determine whether the user can view review performance metrics.
     */
    public function viewPerformance($user, Review $review): bool
    {
        // Admins can view all review performance
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can duplicate the review.
     */
    public function duplicate($user, Review $review): bool
    {
        // Users can duplicate their own reviews (for editing)
        if ($user instanceof User && $review->user && $review->user->id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can translate the review.
     */
    public function translate($user, Review $review): bool
    {
        // Admins can translate any review
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_review', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can archive the review.
     */
    public function archive($user, Review $review): bool
    {
        // Admins can archive any review
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_review', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can unarchive the review.
     */
    public function unarchive($user, Review $review): bool
    {
        // Same logic as archive
        return $this->archive($user, $review);
    }
}
