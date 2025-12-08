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

        if (!$shift->is_open) {
            throw new \Exception('This shift is already closed');
        }

        $now = Carbon::now();

        // Calculate overtime
        $overtimeMinutes = 0;
        $overtimePay = 0;

        if ($shift->expected_end_time && $now->greaterThan($shift->expected_end_time)) {
            $overtimeMinutes = $now->diffInMinutes($shift->expected_end_time);

            // Calculate overtime pay (assuming hourly rate from courier settings)
            $hourlyRate = 50; // Default rate, should come from courier settings
            $overtimePay = ($overtimeMinutes / 60) * $hourlyRate * $overtimeRate;
        }

        // Update shift
        $this->courierShiftRepository->update([
            'end_time' => $now,
            'is_open' => false,
            'overtime_minutes' => $overtimeMinutes,
            'overtime_pay' => $overtimePay,
        ], $shiftId);

        return $shift->fresh();
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
     * Get all shifts (admin)
     */
    public function getAllShifts(array $filters = [])
    {
        return $this->courierShiftRepository->getAll($filters);
    }
}
