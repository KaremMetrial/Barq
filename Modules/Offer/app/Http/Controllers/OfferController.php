<?php

namespace Modules\Offer\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Modules\Offer\Models\Offer;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Modules\Offer\Services\OfferService;
use App\Http\Resources\PaginationResource;
use Modules\Offer\Http\Resources\OfferResource;
use Modules\Offer\Http\Requests\CreateOfferRequest;
use Modules\Offer\Http\Requests\UpdateOfferRequest;
use Modules\Offer\Http\Resources\AdminOfferResource;

class OfferController extends Controller
{
    use ApiResponse, AuthorizesRequests;

    public function __construct(protected OfferService $offerService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Offer::class);
        $filters = request()->all();
        $offers = $this->offerService->getAllOffers($filters);
        return $this->successResponse([
            "offers" => AdminOfferResource::collection($offers->load('offerable')),
            "pagination" => new PaginationResource($offers)
        ], __('message.success'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateOfferRequest $request): JsonResponse
    {
        $this->authorize('create', Offer::class);
        $offer = $this->offerService->createOffer($request->all());
        return $this->successResponse([
            'offer' => new AdminOfferResource($offer->refresh())
        ], __('message.success'));
    }

    /**
     * Show the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $offer = $this->offerService->getOfferById($id);
        $this->authorize('view', $offer);
        return $this->successResponse([
            'offer' => auth('admin')->check() ? new AdminOfferResource($offer->load('offerable')) : new OfferResource($offer->load('offerable'))
        ], __('message.success'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOfferRequest $request, int $id): JsonResponse
    {
        $offer = $this->offerService->getOfferById($id);
        $this->authorize('update', $offer);
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
        $offer = $this->offerService->getOfferById($id);
        $this->authorize('delete', $offer);
        $isDeleted = $this->offerService->deleteOffer($id);
        return $this->successResponse(null, __('message.success'));
    }
    /**
     * Toggle the status of the specified offer.
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $offer = $this->offerService->getOfferById($id);
        $this->authorize('update', $offer);
        $offer = $this->offerService->toggleStatus($id);

        return $this->successResponse([
            'offer' => new OfferResource($offer->refresh())
        ], __('message.success'));
    }
}
