<?php

namespace Modules\Conversation\Policies;

use Modules\Conversation\Models\Conversation;
use Modules\Admin\Models\Admin;
use Modules\Vendor\Models\Vendor;
use Modules\User\Models\User;
use Modules\Couier\Models\Couier;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Helpers\PermissionHelper;

class ConversationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any conversations.
     */
    public function viewAny($user): bool
    {
        // Admins can view all conversations
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_conversation', 'admin')) {
            return true;
        }

        // Users can view their own conversations
        if ($user instanceof User) {
            return true;
        }

        // Vendors can view conversations related to their stores
        if ($user instanceof Vendor) {
            return true;
        }

        // Couriers can view conversations related to their deliveries
        if ($user instanceof Couier) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the conversation.
     */
    public function view($user, Conversation $conversation): bool
    {
        // Admins can view all conversations
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_conversation', 'admin')) {
            return true;
        }

        // Users can only view conversations they're part of
        if ($user instanceof User && $conversation->user_id === $user->id) {
            return true;
        }

        // Vendors can view conversations for their stores or where they're participants
        if ($user instanceof Vendor) {
            if ($conversation->vendor_id === $user->id) return true;
            if ($conversation->store_id === $user->store_id) return true;
            return false;
        }

        // Couriers can view conversations where they're participants or related to their orders
        if ($user instanceof Couier && $conversation->couier_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create conversations.
     */
    public function create($user): bool
    {
        // Any authenticated user can start conversations
        if ($user instanceof User || $user instanceof Vendor || $user instanceof Couier || $user instanceof Admin) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the conversation.
     */
    public function update($user, Conversation $conversation): bool
    {
        // Admins can update any conversation
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_conversation', 'admin')) {
            return true;
        }

        // Participants can update conversations they're part of
        if ($this->isParticipant($user, $conversation)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the conversation.
     */
    public function delete($user, Conversation $conversation): bool
    {
        // Only admins can delete conversations
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_conversation', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the conversation.
     */
    public function restore($user, Conversation $conversation): bool
    {
        // Same logic as update
        return $this->update($user, $conversation);
    }

    /**
     * Determine whether the user can permanently delete the conversation.
     */
    public function forceDelete($user, Conversation $conversation): bool
    {
        // Same logic as delete
        return $this->delete($user, $conversation);
    }

    /**
     * Determine whether the user can send messages in the conversation.
     */
    public function sendMessage($user, Conversation $conversation): bool
    {
        // Admins can send messages if they have permission
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_message', 'admin')) {
            return true;
        }

        // Participants can send messages in conversations they're part of
        return $this->isParticipant($user, $conversation);
    }

    /**
     * Determine whether the user can view messages in the conversation.
     */
    public function viewMessages($user, Conversation $conversation): bool
    {
        // Same logic as view conversation
        return $this->view($user, $conversation);
    }

    /**
     * Determine whether the user can moderate the conversation.
     */
    public function moderate($user, Conversation $conversation): bool
    {
        // Only admins can moderate conversations
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_conversation', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can end/close the conversation.
     */
    public function endConversation($user, Conversation $conversation): bool
    {
        // Admins can end any conversation
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_conversation', 'admin')) {
            return true;
        }

        // Participants can end conversations they're part of
        return $this->isParticipant($user, $conversation);
    }

    /**
     * Determine whether the user can assign conversation to another participant.
     */
    public function assign($user, Conversation $conversation): bool
    {
        // Only admins can assign conversations
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_conversation', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can transfer conversation to another department/agent.
     */
    public function transfer($user, Conversation $conversation): bool
    {
        // Only admins can transfer conversations
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_conversation', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view conversation analytics.
     */
    public function viewAnalytics($user): bool
    {
        // Admins with report permission can view conversation analytics
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can export conversation data.
     */
    public function export($user): bool
    {
        // Admins can export conversation data
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_conversation', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can archive the conversation.
     */
    public function archive($user, Conversation $conversation): bool
    {
        // Admins can archive any conversation
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_conversation', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can unarchive the conversation.
     */
    public function unarchive($user, Conversation $conversation): bool
    {
        // Same logic as archive
        return $this->archive($user, $conversation);
    }

    /**
     * Determine whether the user can view conversation history.
     */
    public function viewHistory($user, Conversation $conversation): bool
    {
        // Same logic as view
        return $this->view($user, $conversation);
    }

    /**
     * Determine whether the user can mark conversation as read.
     */
    public function markAsRead($user, Conversation $conversation): bool
    {
        // Participants can mark conversations as read
        return $this->isParticipant($user, $conversation);
    }

    /**
     * Determine whether the user can add participants to the conversation.
     */
    public function addParticipant($user, Conversation $conversation): bool
    {
        // Admins can add participants to any conversation
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_conversation', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can remove participants from the conversation.
     */
    public function removeParticipant($user, Conversation $conversation): bool
    {
        // Admins can remove participants from any conversation
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_conversation', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view conversation statistics.
     */
    public function viewStatistics($user): bool
    {
        // Admins with report permission can view conversation statistics
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage conversation templates.
     */
    public function manageTemplates($user): bool
    {
        // Admins can manage conversation templates
        if ($user instanceof Admin && PermissionHelper::hasPermission('create_conversation', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can configure auto-replies.
     */
    public function configureAutoReply($user): bool
    {
        // Admins can configure auto-reply settings
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_conversation', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view conversation performance metrics.
     */
    public function viewPerformance($user): bool
    {
        // Admins with report permission can view performance metrics
        if ($user instanceof Admin && PermissionHelper::hasPermission('view_report', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can escalate the conversation.
     */
    public function escalate($user, Conversation $conversation): bool
    {
        // Admins can escalate any conversation
        if ($user instanceof Admin && PermissionHelper::hasPermission('update_conversation', 'admin')) {
            return true;
        }

        // Participants can escalate conversations they're part of
        return $this->isParticipant($user, $conversation);
    }

    /**
     * Determine whether the user can merge conversations.
     */
    public function merge($user): bool
    {
        // Only admins can merge conversations
        if ($user instanceof Admin && PermissionHelper::hasPermission('delete_conversation', 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Helper method to check if user is a participant in the conversation.
     */
    private function isParticipant($user, Conversation $conversation): bool
    {
        if ($user instanceof User && $conversation->user_id === $user->id) {
            return true;
        }

        if ($user instanceof Vendor && $conversation->vendor_id === $user->id) {
            return true;
        }

        if ($user instanceof Admin && $conversation->admin_id === $user->id) {
            return true;
        }

        if ($user instanceof Couier && $conversation->couier_id === $user->id) {
            return true;
        }

        return false;
    }
}
