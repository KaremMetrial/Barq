<?php

namespace Modules\Store\Http\Controllers\User;

use App\Traits\ApiResponse;
use Modules\Store\Models\Store;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Modules\Store\Services\StoreService;
use App\Http\Resources\PaginationResource;
use Illuminate\Http\Request;
use Modules\Store\Http\Resources\StoreResource;
use Modules\Store\Http\Requests\CreateStoreRequest;
use Modules\Store\Http\Requests\UpdateStoreRequest;

class StoreController extends Controller
{
    use ApiResponse;

    public function __construct(protected StoreService $StoreService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search',
            'status',
            'section_id',
            'category_id',
            'has_offer',
            'sort_by',
            'rating'
        ]);
        $Stores = $this->StoreService->getAllStores($filters);

        $label = null;
        if (!empty($filters['section_id'])) {
            $section = \Modules\Section\Models\Section::find($filters['section_id']);
            if ($section) {
                $label = __('enums.section_labels.all', ['section' => $section->name]);
            }else{
                $section = \Modules\Section\Models\Section::where('type', '!=', 'delivery_company')->latest()->first();
                $label = __('enums.section_labels.all', ['section' => $section->name]);
            }
        }

        return $this->successResponse([
            "Stores" => [
                'stores' => StoreResource::collection($Stores),
                'label' => $label,
                "pagination" => new PaginationResource($Stores)
            ]
        ], __('message.success'));
    }

    /**
     * Show the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $Store = $this->StoreService->getStoreById($id);
        return $this->successResponse([
            'Store' => new StoreResource($Store)
        ], __('message.success'));
    }

    public function home(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search',
            'status',
            'section_id',
            'category_id',
            'has_offer',
            'sort_by',
            'rating'
        ]);
        $stores = $this->StoreService->getHomeStores($filters);
        return $this->successResponse([
            "topReviews" => [
                "stores" => StoreResource::collection($stores['topReviews']['stores']),
                "label" => $stores['topReviews']['label']
            ],
            "featured" => [
                "stores" => StoreResource::collection($stores['featured']['stores']),
                "label" => $stores['featured']['label']
            ],
            "new" => [
                "stores" => StoreResource::collection($stores['newStores']['stores']),
                "label" => $stores['newStores']['label']
            ],
            "section_type" => $stores['section_type'],
            "section_label" => $stores['section_label'],
        ], __("message.success"));
    }
}
