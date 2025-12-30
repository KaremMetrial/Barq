<?php

namespace Modules\Couier\Http\Controllers;

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
                return $this->errorResponse(__('Courier not found'), 404);
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

            return $this->errorResponse(__('Failed to retrieve shift schedule'), 500);
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
                return $this->errorResponse(__('Courier not found'), 404);
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
     * Build calendar data for the given month and year
     */
    private function buildCalendarData(int $year, int $month, $shifts): array
    {
        $calendarData = [];
        $startOfMonth = \Carbon\Carbon::create($year, $month, 1);
        $endOfMonth = $startOfMonth->copy()->endOfMonth();
        $startDayOfWeek = $startOfMonth->dayOfWeek; // 0 = Sunday, 1 = Monday, etc.

        // Adjust for Arabic calendar (Sunday = 0, Saturday = 6)
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
            $date = \Carbon\Carbon::create($year, $month, $day);
            $dateKey = $date->toDateString();

            $hasShift = isset($shiftsByDate[$dateKey]);
            $shiftStatuses = [];

            if ($hasShift) {
                foreach ($shiftsByDate[$dateKey] as $shift) {
                    // Determine status for each shift
                    if ($shift->is_open) {
                        if ($shift->start_time && $shift->start_time->isFuture()) {
                            $shiftStatuses[] = 'pending';
                        } elseif (!$shift->start_time && $shift->expected_end_time && $shift->expected_end_time->isPast()) {
                            $shiftStatuses[] = 'absence';
                        } else {
                            $shiftStatuses[] = 'open';
                        }
                    } else {
                        if ($shift->start_time && $shift->end_time) {
                            $shiftStatuses[] = 'finished';
                        } else {
                            $shiftStatuses[] = 'closed';
                        }
                    }
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
                'has_shift' => $hasShift,
                'shift_count' => $hasShift ? count($shiftsByDate[$dateKey]) : 0,
                'status' => $primaryStatus,
                'statuses' => array_unique($shiftStatuses),
                'is_today' => $date->isToday(),
                'is_selected' => false // This would be set based on request parameter
            ];
        }

        return $calendarData;
    }

    /**
     * Get detailed shift information for a specific date
     */
    private function getShiftDetailsForDate($courier, string $selectedDate, $allShifts): ?array
    {
        $shiftsForDate = $allShifts->filter(function ($shift) use ($selectedDate) {
            $shiftDate = $shift->start_time ? $shift->start_time->toDateString() : $shift->expected_end_time->toDateString();
            return $shiftDate === $selectedDate;
        });

        if ($shiftsForDate->isEmpty()) {
            return null;
        }

        $formattedShifts = $shiftsForDate->map(function ($shift) use ($courier, $selectedDate) {
            // Determine status
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

            // Get zones for this shift (using courier's zones as default)
            $zones = $courier->zonesToCover->map(function ($zone) {
                return $zone->name . '، ' . ($zone->city ? $zone->city->name . '، ' : '') . ($zone->governorate ? $zone->governorate->name : '');
            })->join('، ');

            return [
                'id' => $shift->id,
                'status' => $status,
                'status_label' => $statusLabel,
                'date' => $selectedDate,
                'date_arabic' => $this->formatArabicDate($selectedDate),
                'time' => $timeDisplay,
                'zones' => $zones,
                'template_name' => $shift->shiftTemplate ? $shift->shiftTemplate->name : null,
                'total_orders' => $shift->total_orders ?? 0,
                'total_earnings' => $shift->total_earnings ?? 0,
                'notes' => $shift->notes
            ];
        });

        return [
            'date' => $selectedDate,
            'shifts' => $formattedShifts,
            'total_shifts' => $formattedShifts->count()
        ];
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
        $endTime = $shift->end_time ? $shift->end_time->format('H:i') :
                   ($shift->expected_end_time ? $shift->expected_end_time->format('H:i') : null);

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
