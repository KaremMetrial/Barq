<?php

namespace Modules\Couier\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Enums\CouierAvaliableStatusEnum;
use App\Http\Resources\PaginationResource;
use Illuminate\Support\Facades\Log;
use Stevebauman\Location\Facades\Location;
use Modules\Address\Services\AddressService;
use Modules\Couier\Services\CourierShiftService;
use Modules\Couier\Services\ShiftTemplateService;
use Modules\Couier\Http\Resources\CourierShiftResource;
use Modules\Couier\Http\Resources\ShiftTemplateResource;
use Modules\Couier\Http\Resources\CourierShiftTemplateResource;
use Modules\Couier\Services\CourierLocationCacheService;

class CourierShiftController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ShiftTemplateService $shiftTemplateService,
        protected CourierShiftService $courierShiftService,
        protected AddressService $addressService,
        protected CourierLocationCacheService $locationCache
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
            $courier = auth('sanctum')->user();
            $shift = $this->courierShiftService->startShift($courier->id, $request->shift_template_id);
            $courier->avaliable_status = CouierAvaliableStatusEnum::AVAILABLE;
            $courier->save();

            if($request->header('lat') && $request->header('lng')) {
                // Update courier location
                $this->locationCache->updateCourierLocation(
                    $courier->id,
                    request()->header('lat'),
                    request()->header('lng'),
                    [
                        'accuracy' => $request->accuracy,
                        'speed' => $request->speed,
                        'heading' => $request->heading,
                    ]
                );
            }

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
     * Get calendar data for a specific month
     */
    public function calendar(Request $request)
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:2030',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $year = (int) $request->input('year');
        $month = (int) $request->input('month');
        $courier = auth('sanctum')->user();

        // Prepare date boundaries
        $startDate = \Carbon\Carbon::create($year, $month, 1)->startOfDay();
        $endDate = $startDate->copy()->endOfMonth()->endOfDay();
        $daysInMonth = $startDate->daysInMonth;
        $now = now();

        // Fetch all required data
        [$scheduledShiftsData, $actualShifts] = $this->fetchCalendarData($courier, $startDate, $endDate);

        // Build calendar with optimized logic
        $calendar = $this->buildCalendar(
            $startDate,
            $daysInMonth,
            $scheduledShiftsData,
            $actualShifts,
            $now
        );
        $zone = $courier->zonesToCover->map(function ($zone) {
            return [
                'id' => $zone->id,
                'name' => $zone->name
            ];
        });


        return $this->successResponse(
            [
                'calendar_data' =>
                $this->formatCalendarResponse(
                    $year,
                    $month,
                    $startDate,
                    $daysInMonth,
                    $calendar,
                    $scheduledShiftsData['has_active_templates'],
                    $now
                ),
                'zones' => $zone
            ]
        );
    }

    /**
     * Fetch all required calendar data efficiently
     */
    private function fetchCalendarData($courier, $startDate, $endDate): array
    {
        // Fetch scheduled shifts from templates
        $activeAssignments = $courier->activeShiftTemplates()
            ->with(['shiftTemplate' => function ($query) {
                $query->select(['id', 'name', 'is_flexible'])
                    ->with(['days' => function ($query) {
                        $query->select([
                            'id',
                            'shift_template_id',
                            'day_of_week',
                            'start_time',
                            'end_time',
                            'break_duration',
                            'is_off_day'
                        ])->orderBy('day_of_week');
                    }]);
            }])
            ->select(['id', 'courier_id', 'shift_template_id', 'is_active'])
            ->where('is_active', true)
            ->get();

        // Pre-organize scheduled shifts by day of week for O(1) lookup
        $scheduledShiftsByDay = $this->organizeScheduledShiftsByDay($activeAssignments);

        // Fetch actual shifts
        $actualShifts = $courier->shifts()
            ->whereBetween('start_time', [$startDate, $endDate])
            ->orderBy('start_time')
            ->get()
            ->groupBy(function ($shift) {
                return $shift->start_time->format('Y-m-d');
            });

        return [
            [
                'scheduled_shifts_by_day' => $scheduledShiftsByDay,
                'has_active_templates' => $activeAssignments->isNotEmpty(),
                'active_assignments_count' => $activeAssignments->count(),
                'active_assignments' => $activeAssignments
            ],
            $actualShifts
        ];
    }

    /**
     * Organize scheduled shifts by day of week for fast lookup
     */
    private function organizeScheduledShiftsByDay($activeAssignments): array
    {
        $scheduledShiftsByDay = array_fill(0, 7, []);

        foreach ($activeAssignments as $assignment) {
            foreach ($assignment->shiftTemplate->days as $dayConfig) {
                if (!$dayConfig->is_off_day) {
                    $dayOfWeek = $dayConfig->day_of_week;

                    // Calculate total hours correctly
                    $totalHours = $this->calculateTotalHours($dayConfig);

                    $scheduledShiftsByDay[$dayOfWeek][] = [
                        'assignment_id' => $assignment->id,
                        'template_id' => $assignment->shift_template_id,
                        'template_name' => $assignment->shiftTemplate->name,
                        'start_time' => $dayConfig->start_time?->format('H:i:s'),
                        'end_time' => $dayConfig->end_time?->format('H:i:s'),
                        'break_duration' => $dayConfig->break_duration,
                        'total_hours' => $totalHours,
                        'is_flexible' => $assignment->shiftTemplate->is_flexible,
                    ];
                }
            }
        }

        return $scheduledShiftsByDay;
    }

    /**
     * Calculate total hours from day config
     */
    private function calculateTotalHours($dayConfig): float
    {
        if ($dayConfig->is_off_day || !$dayConfig->start_time || !$dayConfig->end_time) {
            return 0.0;
        }

        $start = \Carbon\Carbon::parse($dayConfig->start_time);
        $end = \Carbon\Carbon::parse($dayConfig->end_time);

        // Make sure end time is after start time
        if ($end->lessThan($start)) {
            $end = $end->copy()->addDay();
        }

        $workMinutes = $end->diffInMinutes($start) - ($dayConfig->break_duration ?? 0);

        return round($workMinutes / 60, 2);
    }

    /**
     * Build calendar array with future shifts as pending
     */
    private function buildCalendar($startDate, $daysInMonth, $scheduledShiftsData, $actualShifts, $now)
    {
        $calendar = [];
        $currentDate = $now->format('Y-m-d');

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = $startDate->copy()->addDays($day - 1);
            $dateString = $date->format('Y-m-d');
            $dayOfWeek = $date->dayOfWeek;

            // Get scheduled shifts for this day
            $scheduledShifts = $scheduledShiftsData['scheduled_shifts_by_day'][$dayOfWeek] ?? [];

            // Get actual shifts for this date
            $actualShiftsForDate = $actualShifts->get($dateString, collect());

            // Format actual shifts
            $formattedActualShifts = $this->formatActualShifts($actualShiftsForDate, $now);

            // Add pending future shifts to actual_shifts
            if (!empty($scheduledShifts) && $date->isFuture()) {
                $formattedActualShifts = array_merge(
                    $formattedActualShifts,
                    $this->createPendingShiftsFromSchedule($scheduledShifts, $date, $now)
                );
            }

            $calendar[] = [
                'date' => $dateString,
                'day' => $day,
                'day_name' => $date->translatedFormat('l'),
                'day_short_name' => $date->translatedFormat('D'),
                'is_today' => $dateString === $currentDate,
                'is_weekend' => in_array($dayOfWeek, [5, 6], true),
                'is_off_day' => empty($scheduledShifts),
                'is_past' => $date->isPast(),
                'is_future' => $date->isFuture(),
                'scheduled_shifts' => $scheduledShifts,
                'actual_shifts' => $formattedActualShifts,
                'day_status' => $this->determineDayStatus($scheduledShifts, $formattedActualShifts, $date, $now),
            ];
        }

        return $calendar;
    }

    /**
     * Create pending shifts from scheduled shifts for future dates
     */
    private function createPendingShiftsFromSchedule(array $scheduledShifts, $date, $now): array
    {
        $pendingShifts = [];

        foreach ($scheduledShifts as $index => $scheduledShift) {
            // Create pending shift ID (negative to distinguish from real shifts)
            $pendingShiftId = - ($index + 1);

            // Calculate start and end datetime
            $startDatetime = $date->copy()->setTimeFrom(
                \Carbon\Carbon::parse($scheduledShift['start_time'] ?? '00:00:00')
            );

            $endDatetime = $date->copy()->setTimeFrom(
                \Carbon\Carbon::parse($scheduledShift['end_time'] ?? '23:59:59')
            );

            // If end time is earlier than start time, add one day (overnight shift)
            if ($endDatetime->lessThan($startDatetime)) {
                $endDatetime = $endDatetime->copy()->addDay();
            }

            $pendingShifts[] = [
                'id' => $pendingShiftId,
                'is_pending_shift' => true,
                'start_time' => $scheduledShift['start_time'],
                'end_time' => $scheduledShift['end_time'],
                'start_datetime' => $startDatetime->format('Y-m-d H:i:s'),
                'end_datetime' => $endDatetime->format('Y-m-d H:i:s'),
                'is_open' => false,
                'total_hours' => $scheduledShift['total_hours'],
                'notes' => null,
                'status' => 'pending',
                'is_active' => false,
                'has_break' => !empty($scheduledShift['break_duration']) && $scheduledShift['break_duration'] > 0,
                'break_duration' => $scheduledShift['break_duration'],
                'template_name' => $scheduledShift['template_name'],
                'assignment_id' => $scheduledShift['assignment_id'],
                'template_id' => $scheduledShift['template_id'],
                'is_flexible' => $scheduledShift['is_flexible'],
            ];
        }

        return $pendingShifts;
    }

    /**
     * Format actual shifts with status
     */
    private function formatActualShifts($shifts, $now)
    {
        return $shifts->map(function ($shift) use ($now) {
            $status = $this->determineShiftStatuss($shift, $now);

            // Calculate total hours for the shift
            $totalHours = $shift->total_hours ?? 0;

            return [
                'id' => $shift->id,
                'is_pending_shift' => false,
                'start_time' => $shift->start_time->format('H:i:s'),
                'end_time' => $shift->end_time?->format('H:i:s'),
                'start_datetime' => $shift->start_time->format('Y-m-d H:i:s'),
                'end_datetime' => $shift->end_time?->format('Y-m-d H:i:s'),
                'is_open' => (bool) $shift->is_open,
                'total_hours' => (float) $totalHours,
                'notes' => $shift->notes,
                'status' => $status,
                'is_active' => $status === 'active',
                'has_break' => !empty($shift->break_start) && !empty($shift->break_end),
                'break_start' => $shift->break_start?->format('H:i:s'),
                'break_end' => $shift->break_end?->format('H:i:s'),
                'break_duration' => $shift->break_start && $shift->break_end
                    ? $shift->break_end->diffInMinutes($shift->break_start)
                    : 0,
            ];
        })->values()->toArray();
    }

    /**
     * Determine shift status
     */
    private function determineShiftStatuss($shift, $now): string
    {
        if ($shift->is_open) {
            return 'active';
        }

        if ($shift->end_time && $shift->end_time->isPast()) {
            return 'completed';
        }

        // If shift has end_time in future but is not open
        if ($shift->end_time && $shift->end_time->isFuture()) {
            return 'pending';
        }

        // If no end_time but start_time is in past
        if ($shift->start_time->isPast() && !$shift->end_time) {
            return 'abandoned';
        }

        return 'pending';
    }

    /**
     * Determine overall day status
     */
    private function determineDayStatus($scheduledShifts, $actualShifts, $date, $now): string
    {
        // If no scheduled shifts
        if (empty($scheduledShifts)) {
            return 'free';
        }

        // If future date with scheduled shifts but no actual shifts
        if ($date->isFuture() && empty($actualShifts)) {
            return 'pending';
        }

        // Check actual shifts statuses
        $hasActive = false;
        $hasCompleted = false;

        foreach ($actualShifts as $shift) {
            if ($shift['status'] === 'active') {
                $hasActive = true;
            }
            if ($shift['status'] === 'completed') {
                $hasCompleted = true;
            }
        }

        if ($hasActive) {
            return 'active';
        }

        if ($hasCompleted) {
            return 'completed';
        }

        // Past date with scheduled shifts but no actual shifts (excluding pending shifts)
        if ($date->isPast() && !empty($scheduledShifts)) {
            $hasRealShifts = false;
            foreach ($actualShifts as $shift) {
                if (!$shift['is_pending_shift']) {
                    $hasRealShifts = true;
                    break;
                }
            }

            if (!$hasRealShifts) {
                return 'absent';
            }
        }

        return 'pending';
    }

    /**
     * Format the final calendar response
     */
    private function formatCalendarResponse($year, $month, $startDate, $daysInMonth, $calendar, $hasActiveTemplates, $now)
    {
        $statistics = $this->calculateCalendarStatistics($calendar);

        return [
            'period' => [
                'year' => $year,
                'month' => $month,
                'month_name' => $startDate->translatedFormat('F'),
                'month_year' => $startDate->translatedFormat('F Y'),
                'days_in_month' => $daysInMonth,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $startDate->copy()->endOfMonth()->format('Y-m-d'),
            ],
            'calendar' => $calendar,
            'summary' => [
                'has_active_templates' => $hasActiveTemplates,
                'total_days' => $daysInMonth,
                'scheduled_days' => $statistics['scheduled_days'],
                'worked_days' => $statistics['worked_days'],
                'absent_days' => $statistics['absent_days'],
                'free_days' => $statistics['free_days'],
                'active_days' => $statistics['active_days'],
                'completed_days' => $statistics['completed_days'],
                'total_scheduled_hours' => round($statistics['total_scheduled_hours'], 2),
                'total_worked_hours' => round($statistics['total_worked_hours'], 2),
                'total_pending_shifts' => $statistics['pending_shifts_count'],
                'total_actual_shifts' => $statistics['total_actual_shifts'],
                'active_shifts_count' => $statistics['active_shifts_count'],
                'completed_shifts_count' => $statistics['completed_shifts_count'],
                'attendance_rate' => $statistics['attendance_rate'],
                'avg_hours_per_scheduled_day' => $statistics['avg_hours_per_scheduled_day'],
                'avg_hours_per_worked_day' => $statistics['avg_hours_per_worked_day'],
            ],
            'meta' => [
                'current_date' => $now->format('Y-m-d'),
                'current_time' => $now->format('H:i:s'),
            ]
        ];
    }

    /**
     * Calculate calendar statistics - FIXED VERSION
     */
    private function calculateCalendarStatistics($calendar): array
    {
        // Initialize all possible day status counters
        $statistics = [
            'total_days' => count($calendar),
            'scheduled_days' => 0,
            'worked_days' => 0,
            'absent_days' => 0,
            'free_days' => 0,
            'active_days' => 0,
            'completed_days' => 0,
            'total_scheduled_hours' => 0.0,
            'total_worked_hours' => 0.0,
            'pending_shifts_count' => 0,
            'active_shifts_count' => 0,
            'completed_shifts_count' => 0,
            'total_actual_shifts' => 0,
        ];

        foreach ($calendar as $day) {
            // Count days by their day_status
            $dayStatus = $day['day_status'] ?? 'free';

            // Initialize counter if not exists
            if (!isset($statistics[$dayStatus . '_days'])) {
                $statistics[$dayStatus . '_days'] = 0;
            }

            // Increment the specific day status counter
            $statistics[$dayStatus . '_days']++;

            // Sum scheduled hours
            foreach ($day['scheduled_shifts'] as $scheduledShift) {
                $statistics['total_scheduled_hours'] += $scheduledShift['total_hours'] ?? 0.0;
            }

            // Count actual shifts and hours
            foreach ($day['actual_shifts'] as $actualShift) {
                if ($actualShift['is_pending_shift']) {
                    $statistics['pending_shifts_count']++;
                } else {
                    $statistics['total_actual_shifts']++;
                    $statistics['total_worked_hours'] += $actualShift['total_hours'] ?? 0.0;

                    // Count by status
                    if ($actualShift['status'] === 'active') {
                        $statistics['active_shifts_count']++;
                    } elseif ($actualShift['status'] === 'completed') {
                        $statistics['completed_shifts_count']++;
                    }
                }
            }

            // Count worked days (days with actual non-pending shifts)
            if (!empty($day['actual_shifts'])) {
                $hasNonPendingShift = false;
                foreach ($day['actual_shifts'] as $shift) {
                    if (!$shift['is_pending_shift']) {
                        $hasNonPendingShift = true;
                        break;
                    }
                }
                if ($hasNonPendingShift) {
                    $statistics['worked_days']++;
                }
            }
        }

        // Make sure all day status counters exist with at least 0
        $dayStatuses = ['free', 'scheduled', 'active', 'completed', 'absent'];
        foreach ($dayStatuses as $status) {
            $key = $status . '_days';
            if (!isset($statistics[$key])) {
                $statistics[$key] = 0;
            }
        }

        // Calculate derived statistics
        $statistics['attendance_rate'] = $statistics['scheduled_days'] > 0
            ? round(($statistics['worked_days'] / $statistics['scheduled_days']) * 100, 2)
            : 0.0;

        $statistics['avg_hours_per_scheduled_day'] = $statistics['scheduled_days'] > 0
            ? round($statistics['total_scheduled_hours'] / $statistics['scheduled_days'], 2)
            : 0.0;

        $statistics['avg_hours_per_worked_day'] = $statistics['worked_days'] > 0
            ? round($statistics['total_worked_hours'] / $statistics['worked_days'], 2)
            : 0.0;

        return $statistics;
    }
    /**
     * Get personal statistics
     */
    public function stats(Request $request): JsonResponse
    {
        $courierId = auth('sanctum')->id();
        $stats = $this->courierShiftService->getStats($courierId, $request->all());

        return $this->successResponse([
            'stats' => $stats
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
                return $this->errorResponse(__('message.courier_not_found'), 404);
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
     * Get courier's shift schedule details for a specific date
     *
     * Returns detailed shift information including status, time, zones, and performance
     * for the specified date or today's date if none provided.
     */
    public function calendarSchedule(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'nullable|date'
        ]);

        $courierId = auth('sanctum')->id();
        $targetDate = $request->filled('date') ? $request->date : now()->toDateString();

        try {
            $courier = $this->findCourierWithRelations($courierId);
            if (!$courier) {
                return $this->errorResponse(__('message.courier_not_found'), 404);
            }

            $shiftsForDate = $this->getShiftsForDate($courier, $targetDate);
            $totalShiftsThisMonth = $this->getMonthlyShiftCount($courier);

            $selectedDateDetails = $this->formatShiftDetailsForDate($courier, $targetDate, $shiftsForDate);
            $zonesCovered = $this->formatZonesCovered($courier);

            return $this->successResponse([
                'selected_date_details' => $selectedDateDetails,
                'zones_covered' => $zonesCovered,
                'total_shifts_in_month' => $totalShiftsThisMonth,
                'current_date' => now()->toDateString()
            ], __('Shift schedule retrieved successfully'));
        } catch (\Exception $e) {
            \Log::error('Failed to retrieve shift schedule', [
                'courier_id' => $courierId,
                'date' => $targetDate,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse(__('message.shift_schedule_error'), 500);
        }
    }

    public function scheduleShifts(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'nullable|date',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'status' => 'nullable|in:open,closed,pending,finished,absence,all'
        ]);

        $courierId = auth('sanctum')->id();

        try {
            $courier = \Modules\Couier\Models\Couier::with(['zonesToCover', 'shifts.shiftTemplate'])->find($courierId);

            if (!$courier) {
                return $this->errorResponse(__('message.courier_not_found'), 404);
            }

            // Build query for shifts
            $query = $courier->shifts()->with('shiftTemplate');

            // Apply date filters
            if ($request->filled('date')) {
                $query->whereDate('start_time', $request->date);
            }

            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->whereBetween('start_time', [$request->start_date, $request->end_date]);
            } elseif ($request->filled('start_date')) {
                $query->whereDate('start_time', '>=', $request->start_date);
            } elseif ($request->filled('end_date')) {
                $query->whereDate('start_time', '<=', $request->end_date);
            }

            // Apply status filter
            if ($request->filled('status') && $request->status !== 'all') {
                switch ($request->status) {
                    case 'open':
                        $query->where('is_open', true);
                        break;
                    case 'closed':
                    case 'finished':
                        $query->where('is_open', false)->whereNotNull('start_time');
                        break;
                    case 'pending':
                        $query->where('is_open', true)->where('start_time', '>', now());
                        break;
                    case 'absence':
                        $query->where(function ($q) {
                            $q->where('is_open', true)
                                ->where('start_time', '<=', now())
                                ->whereNull('end_time');
                        })->orWhere(function ($q) {
                            $q->whereNull('start_time')
                                ->where('expected_end_time', '<', now());
                        });
                        break;
                }
            }

            // Order by start time (most recent first)
            $shifts = $query->orderBy('start_time', 'desc')->get();

            // Get zones covered
            $zonesCovered = $courier->zonesToCover->map(function ($zone) {
                return [
                    'id' => $zone->id,
                    'name' => $zone->name,
                    'city' => $zone->city?->name,
                    'governorate' => $zone->governorate?->name
                ];
            });

            // Format shifts data
            $formattedShifts = $shifts->map(function ($shift) {
                // Determine status based on shift data
                $status = 'closed'; // default

                if ($shift->is_open) {
                    if ($shift->start_time && $shift->start_time->isFuture()) {
                        $status = 'pending';
                    } elseif (!$shift->start_time && $shift->expected_end_time && $shift->expected_end_time->isPast()) {
                        $status = 'absence';
                    } else {
                        $status = 'open';
                    }
                } else {
                    if ($shift->start_time && $shift->end_time) {
                        $status = 'finished';
                    }
                }

                return [
                    'id' => $shift->id,
                    'status' => $status,
                    'time' => [
                        'start_time' => $shift->start_time?->toISOString(),
                        'end_time' => $shift->end_time?->toISOString(),
                        'expected_end_time' => $shift->expected_end_time?->toISOString(),
                        'break_start' => $shift->break_start?->toISOString(),
                        'break_end' => $shift->break_end?->toISOString(),
                        'total_hours' => $shift->total_hours,
                        'overtime_minutes' => $shift->overtime_minutes
                    ],
                    'template' => $shift->shiftTemplate ? [
                        'id' => $shift->shiftTemplate->id,
                        'name' => $shift->shiftTemplate->name,
                        'is_flexible' => $shift->shiftTemplate->is_flexible
                    ] : null,
                    'performance' => [
                        'total_orders' => $shift->total_orders,
                        'total_earnings' => $shift->total_earnings,
                        'is_late' => $shift->is_late
                    ],
                    'notes' => $shift->notes,
                    'created_at' => $shift->created_at?->toISOString(),
                    'updated_at' => $shift->updated_at?->toISOString()
                ];
            });

            return $this->successResponse([
                'shifts' => $formattedShifts,
                'zones_covered' => $zonesCovered,
                'total_shifts' => $shifts->count(),
                'filters_applied' => [
                    'date' => $request->date,
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                    'status' => $request->status
                ]
            ], __('Shift schedule retrieved successfully'));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get next available shift for login prompt
     */
    public function next(Request $request): JsonResponse
    {
        $lat = $request->header('lat');
        $long = $request->header('long');
        $zone = $this->addressService->getAddressByLatLong($lat, $long);
        $addressName = $zone?->getFullAddressAttribute();
        $position = Location::get(request()->ip());

        $courierId = auth('sanctum')->id();
        $nextShift = $this->courierShiftService->getNextShift($courierId);

        return $this->successResponse([
            'next_shift' => $nextShift,
            'has_next_shift' => $nextShift !== null,
            "is_available" => $zone ? true : false,
            "address_name" => $addressName ?? ($position ? $position->cityName . ', ' . $position->countryName . ', ' . $position->regionName : null)
        ], __('message.success'));
    }
    public function current(): JsonResponse
    {
        $courierId = auth('sanctum')->id();
        $currentShift = $this->courierShiftService->getCurrentShift($courierId);

        return $this->successResponse([
            'current_shift' => $currentShift,
            'has_current_shift' => $currentShift !== null
        ], __('message.success'));
    }

    /**
     * Format date in Arabic
     */
    private function formatArabicDate(string $date): string
    {
        $carbonDate = \Carbon\Carbon::parse($date);

        $days = [
            'Sunday' => 'الأحد',
            'Monday' => 'الأثنين',
            'Tuesday' => 'الثلاثاء',
            'Wednesday' => 'الأربعاء',
            'Thursday' => 'الخميس',
            'Friday' => 'الجمعة',
            'Saturday' => 'السبت'
        ];

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

        $dayName = $days[$carbonDate->format('l')] ?? '';
        $dayNumber = $carbonDate->day;
        $monthName = $months[$carbonDate->month] ?? '';
        $year = $carbonDate->year;

        return "{$dayName}، {$dayNumber} {$monthName} {$year}";
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
     * Get shifts for a specific date
     */
    private function getShiftsForDate(\Modules\Couier\Models\Couier $courier, string $date)
    {
        return $courier->shifts()
            ->with('shiftTemplate')
            ->whereDate('start_time', $date)
            ->orWhere(function ($query) use ($date) {
                $query->whereNull('start_time')
                    ->whereDate('expected_end_time', $date);
            })
            ->orderBy('start_time', 'asc')
            ->get();
    }

    /**
     * Get total shift count for current month
     */
    private function getMonthlyShiftCount(\Modules\Couier\Models\Couier $courier): int
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        return $courier->shifts()
            ->whereBetween('start_time', [$startOfMonth, $endOfMonth])
            ->orWhere(function ($query) use ($startOfMonth, $endOfMonth) {
                $query->whereNull('start_time')
                    ->whereBetween('expected_end_time', [$startOfMonth, $endOfMonth]);
            })
            ->count();
    }

    /**
     * Format shift details for a specific date
     */
    private function formatShiftDetailsForDate(\Modules\Couier\Models\Couier $courier, string $date, $shifts): ?array
    {
        if ($shifts->isEmpty()) {
            return null;
        }

        $formattedShifts = $shifts->map(function ($shift) use ($courier, $date) {
            $shiftData = $this->determineShiftStatus($shift);
            $timeDisplay = $this->formatShiftTimeDisplay($shift);
            $zones = $this->getCourierZonesFormatted($courier);

            return array_merge($shiftData, [
                'date' => $date,
                'date_arabic' => $this->formatArabicDate($date),
                'time' => $timeDisplay,
                'zones' => $zones,
                'template_name' => $shift->shiftTemplate ? $shift->shiftTemplate->name : null,
                'total_orders' => $shift->total_orders ?? 0,
                'total_earnings' => $shift->total_earnings ?? 0,
                'notes' => $shift->notes
            ]);
        });

        return [
            'date' => $date,
            'shifts' => $formattedShifts,
            'total_shifts' => $formattedShifts->count()
        ];
    }

    /**
     * Determine shift status and label
     */
    private function determineShiftStatus($shift): array
    {
        $status = 'closed';
        $statusLabel = 'مغلق';

        if ($shift->is_open) {
            if ($shift->start_time && $shift->start_time->isFuture()) {
                $status = 'pending';
                $statusLabel = 'معلق';
            } elseif (!$shift->start_time && $shift->expected_end_time && $shift->expected_end_time->isPast()) {
                $status = 'absence';
                $statusLabel = 'غياب';
            } else {
                $status = 'open';
                $statusLabel = 'مفتوح';
            }
        } else {
            if ($shift->start_time && $shift->end_time) {
                $status = 'finished';
                $statusLabel = 'مكتمل';
            }
        }

        return [
            'id' => $shift->id,
            'status' => $status,
            'status_label' => $statusLabel
        ];
    }

    /**
     * Format shift time display with duration
     */
    private function formatShiftTimeDisplay($shift): string
    {
        $startTime = $shift->start_time ? $shift->start_time->format('H:i') : null;
        $endTime = $shift->end_time ? $shift->end_time->format('H:i') : ($shift->expected_end_time ? $shift->expected_end_time->format('H:i') : null);

        if (!$startTime || !$endTime) {
            return $startTime ?: '';
        }

        $duration = $shift->start_time->diff($shift->end_time);
        $hours = $duration->h;
        $minutes = $duration->i;

        return "({$hours}h {$minutes}m) {$startTime} - {$endTime}";
    }

    /**
     * Format zones covered by courier
     */
    private function formatZonesCovered(\Modules\Couier\Models\Couier $courier): array
    {
        return $courier->zonesToCover->map(function ($zone) {
            return [
                'id' => $zone->id,
                'name' => $zone->name,
                'city' => $zone->city?->name,
                'governorate' => $zone->governorate?->name
            ];
        })->toArray();
    }

    /**
     * Get formatted zones string for shift display
     */
    private function getCourierZonesFormatted(\Modules\Couier\Models\Couier $courier): string
    {
        return $courier->zonesToCover->map(function ($zone) {
            return $zone->name . '، ' .
                ($zone->city ? $zone->city->name . '، ' : '') .
                ($zone->governorate ? $zone->governorate->name : '');
        })->join('، ');
    }
}
