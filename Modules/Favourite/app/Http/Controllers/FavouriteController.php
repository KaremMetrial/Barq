<?php

namespace Modules\Favourite\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Modules\Favourite\Http\Requests\CreateFavouriteRequest;
use Modules\Favourite\Http\Requests\UpdateFavouriteRequest;
use Modules\Favourite\Http\Resources\FavouriteResource;
use Modules\Favourite\Services\FavouriteService;
use Illuminate\Http\JsonResponse;

class FavouriteController extends Controller
{
    use ApiResponse;

    public function __construct(protected FavouriteService $favouriteService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $favourites = $this->favouriteService->getAllFavourites();
        return $this->successResponse([
            "favourites" => $favourites
        ], __("message.success"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateFavouriteRequest $request): JsonResponse
    {
        $isFavourite = $this->favouriteService->toggleFavourite($request->all());
        return $this->successResponse([
            'is_favourite' => $isFavourite
        ], __("message.success"));
    }
}
