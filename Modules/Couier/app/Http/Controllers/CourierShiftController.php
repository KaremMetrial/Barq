<?php

namespace Modules\Couier\Http\Controllers;

use Carbon\Carbon;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaginationResource;
use Stevebauman\Location\Facades\Location;
use Modules\Address\Services\AddressService;
use Modules\Couier\Services\CourierShiftService;
use Modules\Couier\Services\ShiftTemplateService;
use Modules\Couier\Http\Resources\CourierShiftResource;
use Modules\Couier\Http\Resources\ShiftTemplateResource;
use Modules\Couier\Services\CourierLocationCacheService;

class CourierShiftController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ShiftTemplateService $shiftTemplateService,
        protected CourierShiftService $courierShiftService,
        protected AddressService $addressService
    ) {}

    /**
     * Get available shift templates
     */
    public function templates(): JsonResponse
    {
        $templates = $this->shiftTemplateService->getActiveTemplates();

        return $this->successResponse([
            'templates' => ShiftTemplateResource::collection($templates)
        ], __('message.success'));
    }

    /**
     * Start a new shift
     */
    public function start(Request $request): JsonResponse
    {
        $request->validate([
            'shift_template_id' => 'required|exists:shift_templates,id'
        ]);

        try {
            $courierId = auth('sanctum')->id();
            $shift = $this->courierShiftService->startShift($courierId, $request->shift_template_id);

            return $this->successResponse([
                'shift' => new CourierShiftResource($shift)
            ], __('Shift started successfully'));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * End current shift
     */
    public function end(int $id): JsonResponse
    {
        try {
            $shift = $this->courierShiftService->endShift($id);

            $locationCache = app(CourierLocationCacheService::class);
            $locationCache->removeCourierFromCache($shift->couier_id);

            return $this->successResponse([
                'shift' => new CourierShiftResource($shift)
            ], __('Shift ended successfully'));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Start break
     */
    public function startBreak(int $id): JsonResponse
    {
        try {
            $shift = $this->courierShiftService->startBreak($id);

            return $this->successResponse([
                'shift' => new CourierShiftResource($shift)
            ], __('Break started'));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * End break
     */
    public function endBreak(int $id): JsonResponse
    {
        try {
            $shift = $this->courierShiftService->endBreak($id);

            return $this->successResponse([
                'shift' => new CourierShiftResource($shift)
            ], __('Break ended'));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Get my shifts
     */
    public function index(Request $request): JsonResponse
    {
        $courierId = auth('sanctum')->id();
        $shifts = $this->courierShiftService->getShiftHistory($courierId, $request->all());

        return $this->successResponse([
            'shifts' => CourierShiftResource::collection($shifts),
            'pagination' => new PaginationResource($shifts)
        ], __('message.success'));
    }

    /**
     * Get courier's assigned shift schedule (templates)
     */
    public function schedule(): JsonResponse
    {
        $courierId = auth('sanctum')->id();

        try {
            $courier = \Modules\Couier\Models\Couier::with('activeShiftTemplates')->find($courierId);

            if (!$courier) {
                return $this->errorResponse(__('Courier not found'), 404);
            }

            $weeklySchedule = $courier->weekly_schedule;
            $assignments = $courier->activeShiftTemplates;

            return $this->successResponse([
                'has_assigned_schedules' => !empty($weeklySchedule),
                'weekly_schedule' => $weeklySchedule,
                'assignments' => $assignments->map(function ($assignment) {
                    return [
                        'id' => $assignment->id,
                        'template_name' => $assignment->shiftTemplate->name,
                        'assigned_at' => $assignment->assigned_at,
                        'notes' => $assignment->notes,
                        'is_flexible' => $assignment->shiftTemplate->is_flexible
                    ];
                })
            ], __('Schedule retrieved successfully'));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get courier's monthly calendar with shift data for each day
     *
     * Returns complete calendar data for the specified month/year with shift information
     * for each day, including status, duration, performance metrics, and template information.
     */
    public function calendarSchedule(Request $request): JsonResponse
    {
        $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
            'month' => 'required|integer|min:1|max:12'
        ]);

        $courierId = auth('sanctum')->id();
        $year = $request->year;
        $month = $request->month;

        try {
            $courier = $this->findCourierWithRelations($courierId);
            if (!$courier) {
                return $this->errorResponse(__('Courier not found'), 404);
            }

            // Get all shifts for the specified month with template relationships
            $allShifts = $this->getShiftsForMonthWithTemplates($courier, $year, $month);
            // Build complete calendar data for the month
            $calendarData = $this->buildCalendarData($courier, $year, $month, $allShifts);

            // Get monthly statistics with template data
            $monthlyStats = $this->getMonthlyStatsWithTemplates($courier, $year, $month, $allShifts);

            return $this->successResponse([
                'year' => $year,
                'month' => $month,
                'month_name' => $this->getArabicMonthName($month),
                'calendar' => $calendarData,
                'stats' => $monthlyStats
            ], __('Calendar data retrieved successfully'));

        } catch (\Exception $e) {
            \Log::error('Failed to retrieve calendar data', [
                'courier_id' => $courierId,
                'year' => $year,
                'month' => $month,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse(__('Failed to retrieve calendar data'), 500);
        }
    }

    /**
     * Get shifts for a specific month with template relationships
     */
    private function getShiftsForMonthWithTemplates(\Modules\Couier\Models\Couier $courier, int $year, int $month): \Illuminate\Support\Collection
    {
        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        return $courier->shifts()
            ->with(['shiftTemplate'])
            ->where(function($query) use ($startOfMonth, $endOfMonth) {
                $query->whereBetween('start_time', [$startOfMonth, $endOfMonth])
                    ->orWhereBetween('expected_end_time', [$startOfMonth, $endOfMonth]);
            })
            ->orderBy('start_time', 'asc')
            ->get();
    }

    /**
     * Get monthly statistics with template data
     */
    private function getMonthlyStatsWithTemplates(\Modules\Couier\Models\Couier $courier, int $year, int $month, $shifts): array
    {
        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $totalHours = 0;
        $totalEarnings = 0;
        $statusCounts = [
            'finished' => 0,
            'open' => 0,
            'pending' => 0,
            'absence' => 0,
            'closed' => 0
        ];

        // Template-specific statistics
        $templateStats = [];
        $templateShiftCount = 0;
        $manualShiftCount = 0;

        foreach ($shifts as $shift) {
            // Calculate total hours
            if ($shift->start_time && $shift->end_time) {
                $totalHours += $shift->start_time->diffInHours($shift->end_time);
            }

            // Sum earnings
            $totalEarnings += $shift->total_earnings ?? 0;

            // Count statuses
            $status = 'closed';
            if ($shift->is_open) {
                if ($shift->start_time && $shift->start_time->isFuture()) {
                    $status = 'pending';
                } elseif (!$shift->start_time && $shift->expected_end_time && $shift->expected_end_time->isPast()) {
                    $status = 'absence';
                } else {
                    $status = 'open';
                }
            } elseif ($shift->start_time && $shift->end_time) {
                $status = 'finished';
            }

            if (isset($statusCounts[$status])) {
                $statusCounts[$status]++;
            }

            // Template statistics
            if ($shift->shiftTemplate) {
                $templateShiftCount++;
                $templateId = $shift->shiftTemplate->id;
                $templateName = $shift->shiftTemplate->name;

                if (!isset($templateStats[$templateId])) {
                    $templateStats[$templateId] = [
                        'name' => $templateName,
                        'is_flexible' => $shift->shiftTemplate->is_flexible,
                        'shift_count' => 0,
                        'total_hours' => 0,
                        'total_earnings' => 0,
                        'total_orders' => 0,
                        'status_counts' => [
                            'finished' => 0,
                            'open' => 0,
                            'pending' => 0,
                            'absence' => 0,
                            'closed' => 0
                        ]
                    ];
                }

                // Update template statistics
                $templateStats[$templateId]['shift_count']++;
                $templateStats[$templateId]['total_hours'] += $shift->start_time && $shift->end_time
                    ? $shift->start_time->diffInHours($shift->end_time)
                    : 0;
                $templateStats[$templateId]['total_earnings'] += $shift->total_earnings ?? 0;
                $templateStats[$templateId]['total_orders'] += $shift->total_orders ?? 0;
                $templateStats[$templateId]['status_counts'][$status]++;
            } else {
                $manualShiftCount++;
            }
        }

        // Format template statistics for response
        $formattedTemplateStats = [];
        foreach ($templateStats as $templateId => $stats) {
            $formattedTemplateStats[$stats['name']] = [
                'shift_count' => $stats['shift_count'],
                'total_hours' => round($stats['total_hours'], 2),
                'total_earnings' => round($stats['total_earnings'], 2),
                'avg_orders' => $stats['shift_count'] > 0 ? round($stats['total_orders'] / $stats['shift_count'], 1) : 0,
                'status_distribution' => $stats['status_counts']
            ];
        }

        return [
            'total_shifts' => $shifts->count(),
            'total_hours' => round($totalHours, 2),
            'total_earnings' => round($totalEarnings, 2),
            'status_distribution' => $statusCounts,
            'template_stats' => [
                'shifts_from_templates' => $templateShiftCount,
                'shifts_manual' => $manualShiftCount,
                'templates_used' => count($templateStats),
                'template_performance' => $formattedTemplateStats
            ]
        ];
    }

    /**
     * Build calendar data for the given month and year
     */
    private function buildCalendarData(\Modules\Couier\Models\Couier $courier, int $year, int $month, $shifts): array
    {
        $calendarData = [];
        $startOfMonth = Carbon::create($year, $month, 1);
        $endOfMonth = $startOfMonth->copy()->endOfMonth();
        $daysInMonth = $endOfMonth->day;

        // Group shifts by date
        $shiftsByDate = [];
        foreach ($shifts as $shift) {
            $dateKey = $shift->start_time ? $shift->start_time->toDateString() : $shift->expected_end_time->toDateString();
            if (!isset($shiftsByDate[$dateKey])) {
                $shiftsByDate[$dateKey] = [];
            }
            $shiftsByDate[$dateKey][] = $shift;
        }

        // Build calendar days
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::create($year, $month, $day);
            $dateKey = $date->toDateString();

            $hasShift = isset($shiftsByDate[$dateKey]);
            $shiftStatuses = [];
            $shiftDetails = [];

            if ($hasShift) {
                foreach ($shiftsByDate[$dateKey] as $shift) {
                    // Determine status for each shift
                    $status = 'closed';
                    $statusLabel = 'مغلق';
                    $statusColor = '#00835B';

                    if ($shift->is_open) {
                        if ($shift->start_time && $shift->start_time->isFuture()) {
                            $status = 'pending';
                            $statusLabel = 'معلق';
                            $statusColor = '#F59E0B';
                            $shiftStatuses[] = 'pending';
                        } elseif (!$shift->start_time && $shift->expected_end_time && $shift->expected_end_time->isPast()) {
                            $status = 'absence';
                            $statusLabel = 'غياب';
                            $statusColor = '#EF4444';
                            $shiftStatuses[] = 'absence';
                        } else {
                            $status = 'open';
                            $statusLabel = 'مفتوح';
                            $statusColor = '#10B981';
                            $shiftStatuses[] = 'open';
                        }
                    } else {
                        if ($shift->start_time && $shift->end_time) {
                            $status = 'finished';
                            $statusLabel = 'مكتمل';
                            $statusColor = '#00835B';
                            $shiftStatuses[] = 'finished';
                        } else {
                            $shiftStatuses[] = 'closed';
                        }
                    }

                    // Format time display
                    $startTime = $shift->start_time ? $shift->start_time->format('H:i') : null;
                    $endTime = $shift->end_time ? $shift->end_time->format('H:i') : ($shift->expected_end_time ? $shift->expected_end_time->format('H:i') : null);
                    $timeDisplay = '';
                    if ($startTime && $endTime) {
                        $duration = $shift->start_time->diff($shift->end_time);
                        $hours = $duration->h;
                        $minutes = $duration->i;
                        $timeDisplay = "({$hours}h {$minutes}m) {$startTime} - {$endTime}";
                    } elseif ($startTime) {
                        $timeDisplay = $startTime;
                    }

                    // Format zones - get from courier since shifts don't have direct zone relationship
                    $zones = '';
                    if ($courier && $courier->zonesToCover) {
                        $zones = $courier->zonesToCover->map(function ($zone) {
                            return $zone->name;
                        })->join('، ');
                    }

                    // Template information
                    $isFromTemplate = $shift->shiftTemplate !== null;
                    $templateName = $shift->shiftTemplate ? $shift->shiftTemplate->name : null;
                    $templateId = $shift->shiftTemplate ? $shift->shiftTemplate->id : null;
                    $templateIsFlexible = $shift->shiftTemplate ? $shift->shiftTemplate->is_flexible : false;

                    $shiftDetails[] = [
                        'id' => $shift->id,
                        'status' => [
                            'label' => $statusLabel,
                            'color' => $statusColor
                        ],
                        'date' => $date->locale('ar')->isoFormat('dddd، D MMMM YYYY'),
                        'time' => $timeDisplay,
                        'zones' => $zones,
                        'template_name' => $templateName,
                        'template_id' => $templateId,
                        'is_from_template' => $isFromTemplate,
                        'template_is_flexible' => $templateIsFlexible,
                        'total_orders' => $shift->total_orders ?? 0,
                        'total_earnings' => $shift->total_earnings ?? 0,
                        'assigned_via_template' => null // Template assignment details not available in this relationship
                    ];
                }
            }

            // Determine primary status (first one, or based on priority)
            $primaryStatus = null;
            if (!empty($shiftStatuses)) {
                // Priority: absence > open > pending > finished > closed
                $priorityOrder = ['absence', 'open', 'pending', 'finished', 'closed'];
                foreach ($priorityOrder as $status) {
                    if (in_array($status, $shiftStatuses)) {
                        $primaryStatus = $status;
                        break;
                    }
                }
            }

            $calendarData[] = [
                'day' => $day,
                'date' => $dateKey,
                'date_arabic' => $date->locale('ar')->isoFormat('dddd، D MMMM YYYY'),
                'has_shift' => $hasShift,
                'shift_count' => $hasShift ? count($shiftsByDate[$dateKey]) : 0,
                'status' => $primaryStatus,
                'is_today' => $date->isToday(),
                'is_selected' => false,
                'shifts' => $shiftDetails
            ];
        }

        return $calendarData;
    }

    /**
     * Find courier with necessary relations loaded
     */
    private function findCourierWithRelations(int $courierId): ?\Modules\Couier\Models\Couier
    {
        return \Modules\Couier\Models\Couier::with(['zonesToCover', 'shifts.shiftTemplate'])
            ->find($courierId);
    }

    /**
     * Get Arabic month name
     */
    private function getArabicMonthName(int $month): string
    {
        $months = [
            1 => 'يناير',
            2 => 'فبراير',
            3 => 'مارس',
            4 => 'أبريل',
            5 => 'مايو',
            6 => 'يونيو',
            7 => 'يوليو',
            8 => 'أغسطس',
            9 => 'سبتمبر',
            10 => 'أكتوبر',
            11 => 'نوفمبر',
            12 => 'ديسمبر'
        ];

        return $months[$month] ?? '';
    }
}
