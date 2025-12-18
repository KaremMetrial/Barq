<?php

namespace Modules\Review\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\RatingKeyResource;
use App\Models\RatingKey;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\PaginationResource;
use Modules\Review\Http\Requests\CreateRatingKeyRequest;
use Modules\Review\Http\Requests\UpdateRatingKeyRequest;

class RatingKeyController extends Controller
{
    use ApiResponse;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $ratingKeys = RatingKey::with('translations')->get();

        return $this->successResponse([
            'data' => RatingKeyResource::collection($ratingKeys),
        ],__('messages.success'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateRatingKeyRequest $request): JsonResponse
    {
        $ratingKey = RatingKey::create($request->validated());

        return $this->successResponse([
            'data' => new RatingKeyResource($ratingKey)
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $ratingKey = RatingKey::with('translations')->findOrFail($id);

        return $this->successResponse([
            'data' => new RatingKeyResource($ratingKey)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRatingKeyRequest $request, string $id): JsonResponse
    {
        $ratingKey = RatingKey::findOrFail($id);
        $data = array_filter($request->all(), fn($value) => $value != null);

        $ratingKey->update($data);

        return $this->successResponse([
            'data' => new RatingKeyResource($ratingKey)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $ratingKey = RatingKey::findOrFail($id);
        $ratingKey->delete();

        return $this->successResponse(null, 'Rating key deleted successfully.');
    }
}
