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

}
