<?php

namespace Modules\Option\Http\Controllers\Admin;

use App\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Option\Services\OptionService;
use Modules\Option\Http\Resources\OptionResource;
use Modules\Option\Http\Requests\CreateOptionRequest;
use Modules\Option\Http\Requests\UpdateOptionRequest;
use Modules\Option\Models\Option;

class OptionController extends Controller
{
    use ApiResponse, AuthorizesRequests;

    public function __construct(private OptionService $optionService) {}

    /**
     * Display a listing of the options.
     */
    public function index()
    {
        $this->authorize('viewAny', Option::class);
        $options = $this->optionService->getAllOptions();
        return $this->successResponse([
            'options' => OptionResource::collection($options),
        ], __('message.success'));
    }

    /**
     * Store a newly created option in storage.
     */
    public function store(CreateOptionRequest $request)
    {
        $this->authorize('create', Option::class);
        $option = $this->optionService->createOption($request->validated());
        return $this->successResponse([
            'option' => new OptionResource($option),
        ], __('message.success'));
    }

    /**
     * Display the specified option.
     */
    public function show($id)
    {
        $option = $this->optionService->getOptionById($id);
        $this->authorize('view', $option);
        return $this->successResponse([
            'option' => new OptionResource($option),
        ], __('message.success'));
    }

    /**
     * Update the specified option in storage.
     */
    public function update(UpdateOptionRequest $request, $id)
    {
        $option = $this->optionService->getOptionById($id);
        $this->authorize('update', $option);
        $option = $this->optionService->updateOption($id, $request->all());
        return $this->successResponse([
            'option' => new OptionResource($option),
        ], __('message.success'));
    }

    /**
     * Remove the specified option from storage.
     */
    public function destroy($id)
    {
        $option = $this->optionService->getOptionById($id);
        $this->authorize('delete', $option);
        $this->optionService->deleteOption($id);
        return $this->successResponse(null, __('message.success'));
    }
}
