<?php

namespace Modules\Address\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Modules\Address\Http\Requests\CreateAddressRequest;
use Modules\Address\Http\Requests\UpdateAddressRequest;
use Modules\Address\Http\Resources\AddressResource;
use Modules\Address\Services\AddressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    use ApiResponse;

    // Injecting AddressService for handling business logic
    public function __construct(protected AddressService $addressService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $addresses = $this->addressService->getAllAddresses();
        return $this->successResponse([
            "addresses" => AddressResource::collection($addresses),
        ], __("message.success"));
    }

    /**
     * Store a newly created address in storage.
     */
    public function store(CreateAddressRequest $request): JsonResponse
    {
        $address = $this->addressService->createAddress($request->all());
        return $this->successResponse([
            "address" => new AddressResource($address),
        ], __("message.success"));
    }

    /**
     * Show the specified address.
     */
    public function show(int $id): JsonResponse
    {
        $address = $this->addressService->getAddressById($id);
        return $this->successResponse([
            "address" => new AddressResource($address),
        ], __("message.success"));
    }

    /**
     * Update the specified address in storage.
     */
    public function update(UpdateAddressRequest $request, int $id): JsonResponse
    {
        $address = $this->addressService->updateAddress($id, $request->all());
        return $this->successResponse([
            "address" => new AddressResource($address),
        ], __("message.success"));
    }

    /**
     * Remove the specified address from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->addressService->deleteAddress($id);
        return $this->successResponse(null, __("message.success"));
    }
}
