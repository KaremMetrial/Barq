<?php

namespace App\Services;

use App\Models\AdminNotification;
use Modules\User\Models\User;
use App\Jobs\SendFcmNotificationJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;

class AdminNotificationService
{
    protected FirebaseService $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    /**
     * Send notification to all users.
     */
    public function sendToAllUsers(array $translations, array $data = [], bool $isScheduled = false, ?string $scheduledAt = null): AdminNotification
    {
        $notification = $this->createNotification([
            'data' => $data,
            'target_type' => 'all_users',
            'is_scheduled' => $isScheduled,
            'scheduled_at' => $isScheduled ? $scheduledAt : null,
        ]);

        // Save translations
        $this->saveTranslations($notification, $translations);

        if ($isScheduled) {
            return $notification;
        }

        $this->processNotification($notification);
        return $notification;
    }

    /**
     * Send notification to specific users.
     */
    public function sendToSpecificUsers(array $userIds, array $translations, array $data = [], bool $isScheduled = false, ?string $scheduledAt = null): AdminNotification
    {
        $notification = $this->createNotification([
            'data' => $data,
            'target_type' => 'specific_users',
            'target_data' => ['user_ids' => $userIds],
            'is_scheduled' => $isScheduled,
            'scheduled_at' => $isScheduled ? $scheduledAt : null,
        ]);

        // Save translations
        $this->saveTranslations($notification, $translations);

        if ($isScheduled) {
            return $notification;
        }

        $this->processNotification($notification);
        return $notification;
    }

    /**
     * Send notification to top performing users based on performance metric.
     */
    public function sendToTopUsers(int $count, string $performanceMetric, array $translations, array $data = [], bool $isScheduled = false, ?string $scheduledAt = null): AdminNotification
    {
        $notification = $this->createNotification([
            'data' => $data,
            'target_type' => 'top_users',
            'top_users_count' => $count,
            'performance_metric' => $performanceMetric,
            'is_scheduled' => $isScheduled,
            'scheduled_at' => $isScheduled ? $scheduledAt : null,
        ]);

        // Save translations
        $this->saveTranslations($notification, $translations);

        if ($isScheduled) {
            return $notification;
        }

        $this->processNotification($notification);
        return $notification;
    }

    /**
     * Process a notification by finding target users and sending notifications.
     */
    public function processNotification(AdminNotification $notification): void
    {
        try {
            $targetUsers = $this->getTargetUsers($notification);
            $totalUsers = $targetUsers->count();

            if ($totalUsers === 0) {
                $notification->update([
                    'is_sent' => true,
                    'sent_at' => now(),
                    'total_sent' => 0,
                    'total_failed' => 0,
                    'total_delivered' => 0,
                ]);
                return;
            }

            $notification->update([
                'total_sent' => $totalUsers,
                'sent_at' => now(),
            ]);

            // Send notifications in batches to avoid overwhelming the queue
            $batchSize = 50;
            $batches = $targetUsers->chunk($batchSize);

            foreach ($batches as $batch) {
                foreach ($batch as $user) {
                    $this->sendNotificationToUser($user, $notification);
                }
            }

            // Mark as sent after processing
            $notification->update(['is_sent' => true]);

        } catch (\Exception $e) {
            Log::error('Failed to process admin notification', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
            ]);

            $notification->update([
                'is_sent' => false,
                'total_failed' => $notification->total_sent,
                'total_sent' => 0,
            ]);
        }
    }

    /**
     * Send notification to a specific user.
     */
    protected function sendNotificationToUser(User $user, AdminNotification $notification): void
    {
        try {
            $tokens = $user->tokens()
                ->where('notification_active', true)
                ->whereNotNull('fcm_device')
                ->get();

            if ($tokens->isEmpty()) {
                return;
            }

            // Group tokens by language
            $tokensByLanguage = $tokens->groupBy('language_code');

            foreach ($tokensByLanguage as $languageCode => $languageTokens) {
                $fcmDevices = $languageTokens->pluck('fcm_device');
                $localizedData = $this->getLocalizedData($notification, $languageCode);

                SendFcmNotificationJob::dispatch(
                    $fcmDevices,
                    $localizedData['title'],
                    $localizedData['body'],
                    array_merge($notification->data ?? [], [
                        'notification_id' => $notification->id,
                        'language_code' => $languageCode,
                        'notification_type' => 'admin_notification',
                    ])
                );
            }

        } catch (\Exception $e) {
            Log::error('Failed to send notification to user', [
                'user_id' => $user->id,
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get target users based on notification configuration.
     */
    protected function getTargetUsers(AdminNotification $notification): Collection
    {
        switch ($notification->target_type) {
            case 'all_users':
                return $this->getAllActiveUsers();

            case 'specific_users':
                return $this->getSpecificUsers($notification->target_data['user_ids'] ?? []);

            case 'top_users':
                return $this->getTopUsers(
                    $notification->top_users_count,
                    $notification->performance_metric
                );

            default:
                return collect();
        }
    }

    /**
     * Get all active users with valid notification tokens.
     */
    protected function getAllActiveUsers(): Collection
    {
        return User::whereHas('tokens', function ($query) {
            $query->where('notification_active', true)
                ->whereNotNull('fcm_device');
        })->get();
    }

    /**
     * Get specific users by IDs.
     */
    protected function getSpecificUsers(array $userIds): Collection
    {
        if (empty($userIds)) {
            return collect();
        }

        return User::whereIn('id', $userIds)
            ->whereHas('tokens', function ($query) {
                $query->where('notification_active', true)
                    ->whereNotNull('fcm_device');
            })
            ->get();
    }

    /**
     * Get top performing users based on performance metric.
     */
    protected function getTopUsers(int $count, ?string $performanceMetric): Collection
    {
        $query = User::whereHas('tokens', function ($query) {
            $query->where('notification_active', true)
                ->whereNotNull('fcm_device');
        });

        switch ($performanceMetric) {
            case 'order_count':
                $query->withCount('orders')
                    ->orderBy('orders_count', 'desc');
                break;

            case 'total_spent':
                $query->withSum(['orders as total_spent' => function ($query) {
                    $query->where('status', 'delivered');
                }], 'total_amount')
                    ->orderBy('total_spent', 'desc');
                break;

            case 'loyalty_points':
                $query->withSum('loyaltyTransactions', 'points')
                    ->orderBy('loyalty_transactions_sum_points', 'desc');
                break;

            case 'avg_rating':
                $query->withAvg(['reviews as avg_rating'], 'rating')
                    ->orderBy('avg_rating', 'desc');
                break;

            default:
                $query->orderBy('created_at', 'desc');
        }

        return $query->limit($count)->get();
    }

    /**
     * Get localized notification data based on language.
     */
    protected function getLocalizedData(AdminNotification $notification, ?string $languageCode): array
    {
        $languageCode = $languageCode ?: 'en';

        // Try to get translation for the requested language
        $translation = $notification->translate($languageCode);

        // If translation exists, use it
        if ($translation && $translation->title && $translation->body) {
            return [
                'title' => $translation->title,
                'body' => $translation->body,
            ];
        }

        // Fallback to English if available
        $enTranslation = $notification->translate('en');
        if ($enTranslation && $enTranslation->title && $enTranslation->body) {
            return [
                'title' => $enTranslation->title,
                'body' => $enTranslation->body,
            ];
        }

        // Fallback to Arabic if available
        $arTranslation = $notification->translate('ar');
        if ($arTranslation && $arTranslation->title && $arTranslation->body) {
            return [
                'title' => $arTranslation->title,
                'body' => $arTranslation->body,
            ];
        }

        // Final fallback to default attributes
        return [
            'title' => $notification->title,
            'body' => $notification->body,
        ];
    }

    /**
     * Save translations for a notification.
     */
    protected function saveTranslations(AdminNotification $notification, array $translations): void
    {
        foreach ($translations as $locale => $translation) {
            $notification->translateOrNew($locale)->fill([
                'title' => $translation['title'] ?? '',
                'body' => $translation['body'] ?? '',
            ]);
        }
        $notification->save();
    }

    /**
     * Create a new notification record.
     */
    protected function createNotification(array $data): AdminNotification
    {
        return AdminNotification::create(array_merge([
            'admin_id' => auth('sanctum')->id(),
            'is_sent' => false,
            'total_sent' => 0,
            'total_failed' => 0,
            'total_delivered' => 0,
        ], $data));
    }

    /**
     * Process scheduled notifications.
     */
    public function processScheduledNotifications(): void
    {
        $notifications = AdminNotification::readyToSchedule()->get();

        foreach ($notifications as $notification) {
            $this->processNotification($notification);
        }
    }

    /**
     * Get notification history for an admin.
     */
    public function getNotificationHistory(int $adminId, int $perPage = 10)
    {
        return AdminNotification::byAdmin($adminId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get notification statistics.
     */
    public function getStatistics(): array
    {
        $totalNotifications = AdminNotification::count();
        $sentNotifications = AdminNotification::where('is_sent', true)->count();
        $scheduledNotifications = AdminNotification::where('is_scheduled', true)->count();

        $totalSent = AdminNotification::sum('total_sent');
        $totalDelivered = AdminNotification::sum('total_delivered');
        $totalFailed = AdminNotification::sum('total_failed');

        $successRate = $totalSent > 0 ? round(($totalDelivered / $totalSent) * 100, 2) : 0;

        return [
            'total_notifications' => $totalNotifications,
            'sent_notifications' => $sentNotifications,
            'scheduled_notifications' => $scheduledNotifications,
            'total_sent' => $totalSent,
            'total_delivered' => $totalDelivered,
            'total_failed' => $totalFailed,
            'success_rate' => $successRate . '%',
        ];
    }
}