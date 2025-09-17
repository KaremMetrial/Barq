<?php

namespace Modules\Language\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Language\Http\Requests\CreateLanguageRequest;
use Modules\Language\Http\Requests\UpdateLanguageRequest;
use Modules\Language\Http\Resources\LanguageResource;
use Modules\Language\Services\LanguageService;

class LanguageController extends Controller
{
    use ApiResponse;

    public function __construct(private LanguageService $service) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $languages = $this->service->getAllLanguages();

        return $this->successResponse([
            "languages" => LanguageResource::collection($languages),
        ], __("message.success"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateLanguageRequest $request)
    {
        $language = $this->service->createLanguage($request->validated());

        return $this->successResponse([
            "language" => new LanguageResource($language),
        ], __("message.success"));
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $language = $this->service->getLanguageById($id);
        return $this->successResponse([
            "language" => new LanguageResource($language),
        ], __("message.success"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateLanguageRequest $request, $id)
    {
        $language = $this->service->updateLanguage($id, $request->validated());
        return $this->successResponse([
            "language" => new LanguageResource($language),
        ], __("message.success"));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $language = $this->service->deleteLanguage($id);
        return $this->successResponse(null, __("message.success"));
    }
}
