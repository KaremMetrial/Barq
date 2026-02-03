<?php

namespace App\Http\Controllers;

use App\Services\AdminNotificationService;
use App\Http\Requests\SendNotificationRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(AdminNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Send notification to users.
     * 
     * @OA\Post(
     *     path="/api/notifications/send",
     *     summary="Send notification to users",
     *     description="Send notification to all users, specific users, or top performing users",
     *     tags={"Notifications"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"translations", "target_type"},
     *             @OA\Property(property="translations", type="object",
     *                 @OA\Property(property="en", type="object",
     *                     @OA\Property(property="title", type="string", example="Special Offer!"),
     *                     @OA\Property(property="body", type="string", example="Get 20% off on all orders today")
     *                 ),
     *                 @OA\Property(property="ar", type="object",
     *                     @OA\Property(property="title", type="string", example="عرض خاص!"),
     *                     @OA\Property(property="body", type="string", example="احصل على خصم 20% على جميع الطلبات اليوم")
     *                 )
     *             ),
     *             @OA\Property(property="data", type="object", example={"offer_id": "123", "discount": "20%"}),
     *             @OA\Property(property="target_type", type="string", enum={"all_users", "specific_users", "top_users"}, example="all_users"),
     *             @OA\Property(property="target_data", type="object",
     *                 @OA\Property(property="user_ids", type="array", @OA\Items(type="integer"), example={1, 2, 3})
     *             ),
     *             @OA\Property(property="top_users_count", type="integer", example=100),
     *             @OA\Property(property="performance_metric", type="string", enum={"order_count", "total_spent", "loyalty_points", "avg_rating"}, example="total_spent"),
     *             @OA\Property(property="is_scheduled", type="boolean", example=false),
     *             @OA\Property(property="scheduled_at", type="string", format="date-time", example="2024-01-01T10:00:00Z")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notification sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Notification sent successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="notification", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid target type"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function send(SendNotificationRequest $request): JsonResponse
    {
        // Check if user is authenticated and has admin privileges
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }

        $translations = $request->translations;
        $data = $request->data ?? [];
        $isScheduled = $request->is_scheduled ?? false;
        $scheduledAt = $request->scheduled_at;

        switch ($request->target_type) {
            case 'all_users':
                $notification = $this->notificationService->sendToAllUsers(
                    $translations,
                    $data,
                    $isScheduled,
                    $scheduledAt
                );
                break;

            case 'specific_users':
                $notification = $this->notificationService->sendToSpecificUsers(
                    $request->target_data['user_ids'] ?? [],
                    $translations,
                    $data,
                    $isScheduled,
                    $scheduledAt
                );
                break;

            case 'top_users':
                $notification = $this->notificationService->sendToTopUsers(
                    $request->top_users_count,
                    $request->performance_metric,
                    $translations,
                    $data,
                    $isScheduled,
                    $scheduledAt
                );
                break;

            default:
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid target type'
                ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification sent successfully',
            'data' => [
                'notification' => $notification
            ]
        ], 200);
    }

    /**
     * Get notification history for the authenticated user.
     * 
     * @OA\Get(
     *     path="/api/notifications/history",
     *     summary="Get notification history",
     *     description="Get notification history for the authenticated admin",
     *     tags={"Notifications"},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of notifications per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notification history retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Notification history retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="notifications", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Authentication required",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Authentication required")
     *         )
     *     )
     * )
     */
    public function history(Request $request): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }

        $perPage = $request->input('per_page', 10);
        $notifications = $this->notificationService->getNotificationHistory(
            Auth::id(),
            $perPage
        );

        return response()->json([
            'success' => true,
            'message' => 'Notification history retrieved successfully',
            'data' => [
                'notifications' => $notifications
            ]
        ], 200);
    }

    /**
     * Get notification statistics.
     * 
     * @OA\Get(
     *     path="/api/notifications/statistics",
     *     summary="Get notification statistics",
     *     description="Get notification statistics for the authenticated admin",
     *     tags={"Notifications"},
     *     @OA\Response(
     *         response=200,
     *         description="Notification statistics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Notification statistics retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="statistics", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Authentication required",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Authentication required")
     *         )
     *     )
     * )
     */
    public function statistics(): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }

        $statistics = $this->notificationService->getStatistics();

        return response()->json([
            'success' => true,
            'message' => 'Notification statistics retrieved successfully',
            'data' => [
                'statistics' => $statistics
            ]
        ], 200);
    }

    /**
     * Process scheduled notifications (for cron job).
     * 
     * @OA\Post(
     *     path="/api/notifications/process-scheduled",
     *     summary="Process scheduled notifications",
