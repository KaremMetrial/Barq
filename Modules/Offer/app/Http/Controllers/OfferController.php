<?php

namespace Modules\Offer\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Modules\Offer\Http\Requests\CreateOfferRequest;
use Modules\Offer\Http\Requests\UpdateOfferRequest;
use Modules\Offer\Http\Resources\OfferResource;
use Modules\Offer\Services\OfferService;
use Illuminate\Http\JsonResponse;
use Modules\Offer\Models\Offer;

class OfferController extends Controller
{
    use ApiResponse;

    public function __construct(protected OfferService $offerService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $offers = $this->offerService->getAllOffers();
        return $this->successResponse([
            "offers" => OfferResource::collection($offers)
        ], __('message.success'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateOfferRequest $request): JsonResponse
    {
        $offer = $this->offerService->createOffer($request->all());
        return $this->successResponse([
            'offer' => new OfferResource($offer->refresh())
        ], __('message.success'));
    }

    /**
     * Show the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $offer = $this->offerService->getOfferById($id);
        return $this->successResponse([
            'offer' => new OfferResource($offer)
        ], __('message.success'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOfferRequest $request, int $id): JsonResponse
    {
        $offer = $this->offerService->updateOffer($id, $request->all());
        return $this->successResponse([
            'offer' => new OfferResource($offer->refresh())
        ], __('message.success'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $isDeleted = $this->offerService->deleteOffer($id);
        return $this->successResponse(null, __('message.success'));
    }
}
