<?php

namespace Modules\Couier\Http\Controllers\Admin;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaginationResource;
use Modules\Couier\Http\Requests\ShiftTemplateRequest;
use Modules\Couier\Http\Resources\ShiftTemplateResource;
use Modules\Couier\Services\ShiftTemplateService;

class ShiftTemplateController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ShiftTemplateService $shiftTemplateService
    ) {}

    /**
     * Get all shift templates
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->all();

        // If authenticated as vendor, filter by their store
        if (auth('vendor')->check()) {
            $filters['store_id'] = auth('vendor')->user()->store_id;
        }

        $templates = $this->shiftTemplateService->getAllTemplates($filters);
        return $this->successResponse([
            'templates' => ShiftTemplateResource::collection($templates),
            'pagination' => new PaginationResource($templates)
        ], __('message.success'));
    }

    /**
     * Create new shift template
     */
    public function store(ShiftTemplateRequest $request): JsonResponse
    {
        // Validation and store_id injection handled by ShiftTemplateRequest
        $template = $this->shiftTemplateService->createTemplate($request->validated());

        return $this->successResponse([
            'template' => new ShiftTemplateResource($template)
        ], __('message.created'));
    }

    /**
     * Show shift template
     */
    public function show(int $id): JsonResponse
    {
        $template = $this->shiftTemplateService->getTemplate($id);

        return $this->successResponse([
            'template' => new ShiftTemplateResource($template)
        ], __('message.success'));
    }

    /**
     * Update shift template
     */
    public function update(ShiftTemplateRequest $request, int $id): JsonResponse
    {
        $template = $this->shiftTemplateService->updateTemplate($id, $request->validated());

        return $this->successResponse([
            'template' => new ShiftTemplateResource($template)
        ], __('message.updated'));
    }

    /**
     * Delete shift template
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->shiftTemplateService->deleteTemplate($id);
            return $this->successResponse(null, __('message.deleted'));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Toggle shift template status
     */
    public function toggle(int $id): JsonResponse
    {
        $template = $this->shiftTemplateService->toggleStatus($id);

        return $this->successResponse([
            'template' => new ShiftTemplateResource($template)
        ], __('message.updated'));
    }
}
