<?php

namespace Modules\Couier\Services;

use Modules\Couier\Repositories\ShiftTemplateRepository;
use Illuminate\Support\Facades\DB;
use Modules\Couier\Models\ShiftTemplate;

class ShiftTemplateService
{
    public function __construct(
        protected ShiftTemplateRepository $shiftTemplateRepository
    ) {}

    /**
     * Get all shift templates with filters
     */
    public function getAllTemplates(array $filters = [])
    {
        return $this->shiftTemplateRepository->getAll($filters);
    }

    /**
     * Get active templates only
     */
    public function getActiveTemplates(?int $storeId = null)
    {
        return $this->shiftTemplateRepository->getActiveTemplates($storeId);
    }

    /**
     * Create a new shift template
     */
    public function createTemplate(array $data): ShiftTemplate
    {
        return DB::transaction(function () use ($data) {
            // Create the template
            $template = $this->shiftTemplateRepository->create([
                'name' => $data['name'],
                'is_active' => $data['is_active'] ?? true,
                'is_flexible' => $data['is_flexible'] ?? false,
                'store_id' => $data['store_id'] ?? null,
            ]);

            // Create the days
            if (isset($data['days']) && is_array($data['days'])) {
                foreach ($data['days'] as $day) {
                    $template->days()->create([
                        'day_of_week' => $day['day_of_week'],
                        'start_time' => $day['start_time'] ?? null,
                        'end_time' => $day['end_time'] ?? null,
                        'break_duration' => $day['break_duration'] ?? 0,
                        'is_off_day' => $day['is_off_day'] ?? false,
                    ]);
                }
            }

            return $template->load('days');
        });
    }

    /**
     * Update a shift template
     */
    public function updateTemplate(int $id, array $data): ShiftTemplate
    {
        return DB::transaction(function () use ($id, $data) {
            $template = $this->shiftTemplateRepository->find($id);

            // Update template info
            $template->update([
                'name' => $data['name'] ?? $template->name,
                'is_active' => $data['is_active'] ?? $template->is_active,
                'is_flexible' => $data['is_flexible'] ?? $template->is_flexible,
                'store_id' => $data['store_id'] ?? $template->store_id,
            ]);

            // Update days if provided
            if (isset($data['days']) && is_array($data['days'])) {
                // Delete existing days
                $template->days()->delete();

                // Create new days
                foreach ($data['days'] as $day) {
                    $template->days()->create([
                        'day_of_week' => $day['day_of_week'],
                        'start_time' => $day['start_time'] ?? null,
                        'end_time' => $day['end_time'] ?? null,
                        'break_duration' => $day['break_duration'] ?? 0,
                        'is_off_day' => $day['is_off_day'] ?? false,
                    ]);
                }
            }

            return $template->load('days');
        });
    }

    /**
     * Delete a shift template
     */
    public function deleteTemplate(int $id): bool
    {
        $template = $this->shiftTemplateRepository->find($id);

        // Check if template is being used
        if ($template->courierShifts()->exists()) {
            throw new \Exception('Cannot delete template that is being used by courier shifts');
        }

        return $this->shiftTemplateRepository->delete($id);
    }

    /**
     * Toggle template status
     */
    public function toggleStatus(int $id): ShiftTemplate
    {
        $template = $this->shiftTemplateRepository->find($id);
        $template->update(['is_active' => !$template->is_active]);

        return $template;
    }

    /**
     * Get template by ID
     */
    public function getTemplate(int $id): ShiftTemplate
    {
        return $this->shiftTemplateRepository->find($id)->load('days');
    }
}
