<?php

namespace Modules\Language\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
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
    public function store(Request $request)
    {
        //

        return response()->json([]);
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        //

        return response()->json([]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        //

        return response()->json([]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //

        return response()->json([]);
    }
}
