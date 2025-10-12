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

    public function __construct(protected FavouriteService $favouriteService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $favourites = $this->favouriteService->getAllFavourites();
        return $this->successResponse([
            "favourites"=> $favourites
        ], __("message.success"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateFavouriteRequest $request): JsonResponse
    {
        $favourite = $this->favouriteService->createFavourite($request->all());
        return $this->successResponse([
            "favourite" => new FavouriteResource($favourite),
        ], __("message.success"));
    }

    /**
     * Show the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $favourite = $this->favouriteService->getFavouriteById($id);
        return $this->successResponse([
            "favourite" => new FavouriteResource($favourite),
        ], __("message.success"));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->favouriteService->deleteFavourite($id);
        return $this->successResponse(null, __("message.success"));
    }
}
