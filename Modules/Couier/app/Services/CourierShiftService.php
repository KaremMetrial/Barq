<?php

namespace Modules\Couier\Services;

use Modules\Couier\Repositories\CourierShiftRepository;
use Modules\Couier\Repositories\ShiftTemplateRepository;
use Modules\Couier\Models\CouierShift;
use Carbon\Carbon;

class CourierShiftService
{
    public function __construct(
        protected CourierShiftRepository $courierShiftRepository,
        protected ShiftTemplateRepository $shiftTemplateRepository
    ) {}

    /**
     * Start a new shift for courier
     */
    public function startShift(int $courierId, int $templateId): CouierShift
    {
        // Check if courier has an active shift
        $activeShift = $this->courierShiftRepository->findActiveShift($courierId);

        if ($activeShift) {
            throw new \Exception('Courier already has an active shift');
        }

        // Get template
        $template = $this->shiftTemplateRepository->find($templateId);

        if (!$template->is_active) {
            throw new \Exception('This shift template is not active');
        }

        // Get today's day configuration
        $today = Carbon::now()->dayOfWeek;
        $dayConfig = $template->getDayConfig($today);

        if (!$dayConfig) {
            throw new \Exception('No shift configuration for today');
        }

        if ($dayConfig->is_off_day) {
            throw new \Exception('Today is marked as off day in this shift template');
        }

        // Calculate expected end time
        $now = Carbon::now();
        $shiftDuration = Carbon::parse($dayConfig->end_time)->diffInMinutes(Carbon::parse($dayConfig->start_time));
        $expectedEndTime = $now->copy()->addMinutes($shiftDuration);

        // Create shift
        $shift = $this->courierShiftRepository->create([
            'couier_id' => $courierId,
            'shift_template_id' => $templateId,
            'start_time' => $now,
            'expected_end_time' => $expectedEndTime,
            'is_open' => true,
        ]);

        return $shift->load('shiftTemplate');
    }

    /**
     * End a shift
     */
    public function endShift(int $shiftId, float $overtimeRate = 1.5): CouierShift
    {
        $shift = $this->courierShiftRepository->find($shiftId);

        if (!$shift) {
            throw new \Exception('Shift not found');
        }

        // Verify that the shift belongs to the authenticated courier
        $courierId = auth('sanctum')->id();
        if ($shift->couier_id != $courierId) {
            throw new \Exception('Unauthorized: This shift does not belong to you');
        }

        if (!$shift->is_open) {
            throw new \Exception('This shift is already closed');
        }

        $now = Carbon::now();

        // Calculate overtime
        $overtimeMinutes = 0;
        $overtimePay = 0;

        if ($shift->expected_end_time && $now->greaterThan($shift->expected_end_time)) {
            $overtimeMinutes = $now->diffInMinutes($shift->expected_end_time);

            // Get courier's hourly rate from settings or database
            $courier = \Modules\Couier\Models\Couier::find($courierId);
            $hourlyRate = $courier->commission_amount ?? 50; // Use commission_amount as hourly rate, fallback to 50
            $overtimePay = ($overtimeMinutes / 60) * $hourlyRate * $overtimeRate;
        }

        // Update shift - correct parameter order: ID first, then data array
        $this->courierShiftRepository->update($shiftId, [
            'end_time' => $now,
            'is_open' => false,
            'overtime_minutes' => $overtimeMinutes,
            'overtime_pay' => $overtimePay,
        ]);

        return $shift->fresh()->load('shiftTemplate');
    }

    /**
     * Start break
     */
    public function startBreak(int $shiftId): CouierShift
    {
        $shift = $this->courierShiftRepository->find($shiftId);

        if (!$shift->is_open) {
            throw new \Exception('Cannot start break on a closed shift');
        }

        if ($shift->break_start) {
            throw new \Exception('Break already started');
        }

        $this->courierShiftRepository->update(['break_start' => Carbon::now()], $shiftId);

        return $shift->fresh();
    }

    /**
     * End break
     */
    public function endBreak(int $shiftId): CouierShift
    {
        $shift = $this->courierShiftRepository->find($shiftId);

        if (!$shift->is_open) {
            throw new \Exception('Cannot end break on a closed shift');
        }

        if (!$shift->break_start) {
            throw new \Exception('Break has not been started');
        }

        if ($shift->break_end) {
            throw new \Exception('Break already ended');
        }

        $this->courierShiftRepository->update(['break_end' => Carbon::now()], $shiftId);

        return $shift->fresh();
    }

    /**
     * Get current active shift for courier
     */
    public function getCurrentShift(int $courierId): ?CouierShift
    {
        return $this->courierShiftRepository->findActiveShift($courierId);
    }

    /**
     * Get shift history for courier
     */
    public function getShiftHistory(int $courierId, array $filters = [])
    {
        return $this->courierShiftRepository->getCourierHistory($courierId, $filters);
    }

    /**
     * Get statistics for courier
     */
    public function getStats(int $courierId, array $filters = []): array
    {
        $shifts = $this->courierShiftRepository->getCourierStats($courierId, $filters);

        return [
            'total_shifts' => $shifts->count(),
            'total_hours' => round($shifts->sum(fn($s) => $s->total_hours), 2),
            'total_overtime_minutes' => $shifts->sum('overtime_minutes'),
            'total_overtime_pay' => $shifts->sum('overtime_pay'),
            'total_orders' => $shifts->sum('total_orders'),
            'total_earnings' => $shifts->sum('total_earnings'),
            'late_shifts_count' => $shifts->where('overtime_minutes', '>', 0)->count(),
            'average_hours_per_shift' => $shifts->count() > 0
                ? round($shifts->sum(fn($s) => $s->total_hours) / $shifts->count(), 2)
                : 0,
        ];
    }

    /**
     * Close shift (admin)
     */
    public function closeShift(int $shiftId): CouierShift
    {
        return $this->endShift($shiftId);
    }

    /**
     * Get next available shift for courier
     */
    public function getNextShift(int $courierId): ?array
    {
        // Check if courier has active shift
        $activeShift = $this->getCurrentShift($courierId);
        if ($activeShift) {
            return null; // Cannot show next shift if one is already active
        }
        // Get courier
        // Assuming we can get courier through repository or directly
        $courier = \Modules\Couier\Models\Couier::find($courierId);
        if (!$courier) {
            return null;
        }

        // Get active templates for courier's store
        $templates = $this->shiftTemplateRepository->getActiveTemplatesForCourierStore($courier->store_id);
        if ($templates->isEmpty()) {
            return null;
        }

        $nextShift = null;
        $earliestTime = Carbon::tomorrow()->endOfDay(); // Set to far future as default

        $now = Carbon::now();

        foreach ($templates as $template) {
            // Check next 7 days for available shifts
            for ($dayOffset = 0; $dayOffset <= 7; $dayOffset++) {
                $checkDate = $now->copy()->addDays($dayOffset);
                $dayOfWeek = $checkDate->dayOfWeek;

                // Get day configuration
                $dayConfig = $template->days()->where('day_of_week', $dayOfWeek)->first();

                if (!$dayConfig || $dayConfig->is_off_day) {
                    continue;
                }

                // Parse shift times for this day
                $startTime = Carbon::parse($dayConfig->start_time);
                $endTime = Carbon::parse($dayConfig->end_time);

                // Set the check date with shift start time
                $shiftStartDateTime = $checkDate->copy()->setTime(
                    $startTime->hour,
                    $startTime->minute,
                    $startTime->second
                );

                // Set the check date with shift end time
                $shiftEndDateTime = $checkDate->copy()->setTime(
                    $endTime->hour,
                    $endTime->minute,
                    $endTime->second
                );

                // Skip if this shift start time has passed today
                if ($dayOffset === 0 && $shiftStartDateTime->isPast()) {
                    continue;
                }

                // Check if this is earlier than current earliest
                if ($shiftStartDateTime->lt($earliestTime)) {
                    // Calculate shift duration in minutes
                    $duration = $shiftStartDateTime->diffInMinutes($shiftEndDateTime);

                    $nextShift = [
                        'shift_template' => $template,
                        'day_config' => $dayConfig,
                        'scheduled_date' => $checkDate->toDateString(),
                        'scheduled_start_time' => $shiftStartDateTime->toDateTimeString(),
                        'scheduled_end_time' => $shiftEndDateTime->toDateTimeString(),
                        'duration_minutes' => $duration,
                        'break_duration' => $dayConfig->break_duration,
                        'day_name' => $dayConfig->day_name,
                        'is_today' => $dayOffset === 0,
                        'days_from_now' => $dayOffset
                    ];
                    $earliestTime = $shiftStartDateTime;
                }
            }
        }

        return $nextShift;
    }


    /**
     * Schedule a shift for a courier without starting it immediately
     */
    public function scheduleShift(
        int $courierId,
        int $templateId,
        string $scheduledDate,
        ?string $startTime = null,
        ?string $endTime = null,
        ?string $notes = null
    ): CouierShift {
        // Check if courier already has a shift scheduled for this date
        $existingShift = $this->courierShiftRepository->findScheduledShift($courierId, $scheduledDate);
        if ($existingShift) {
            throw new \Exception('Courier already has a shift scheduled for this date');
        }

        // Get template
        $template = $this->shiftTemplateRepository->find($templateId);

        if (!$template->is_active) {
            throw new \Exception('This shift template is not active');
        }

        // Determine shift times
        if ($startTime && $endTime) {
            // Custom times provided
            $scheduledStartTime = Carbon::parse($scheduledDate . ' ' . $startTime);
            $scheduledEndTime = Carbon::parse($scheduledDate . ' ' . $endTime);
        } else {
            // Use template default for the day
            $dayOfWeek = Carbon::parse($scheduledDate)->dayOfWeek;
            $dayConfig = $template->getDayConfig($dayOfWeek);

            if (!$dayConfig || $dayConfig->is_off_day) {
                throw new \Exception('No shift configuration available for this day in the template');
            }

            $scheduledStartTime = Carbon::parse($scheduledDate . ' ' . $dayConfig->start_time);
            $scheduledEndTime = Carbon::parse($scheduledDate . ' ' . $dayConfig->end_time);
        }

        // Create scheduled shift (is_open = false, start_time null)
        $shift = $this->courierShiftRepository->create([
            'couier_id' => $courierId,
            'shift_template_id' => $templateId,
            'start_time' => $scheduledStartTime, // Store when shift should start (but won't mark as open)
            'expected_end_time' => $scheduledEndTime,
            'is_open' => false, // Scheduled, not started
            'notes' => $notes
        ]);

        return $shift->load('shiftTemplate');
    }

    /**
     * Get all shifts (admin)
     */
    public function getAllShifts(array $filters = [])
    {
        return $this->courierShiftRepository->getAll($filters);
    }
}
