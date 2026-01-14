<?php

namespace Modules\Address\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Modules\Address\Http\Requests\CreateAddressRequest;
use Modules\Address\Http\Requests\UpdateAddressRequest;
use Modules\Address\Http\Resources\AddressResource;
use Modules\Address\Services\AddressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stevebauman\Location\Facades\Location;
class AddressController extends Controller
{
    use ApiResponse;

    // Injecting AddressService for handling business logic
    public function __construct(protected AddressService $addressService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $addresses = $this->addressService->getAllAddresses($request->all());
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
            "address" => new AddressResource($address->load('zone')),
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
            "address" => new AddressResource($address->load('zone')),
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
    public function byLatLong(Request $request): JsonResponse
    {
        $zone = null;
        $addressName = null;
        $currentAddress = null;
        $addressId = $request->header('address-id');
        if ($addressId && $addressId !== '' && $addressId !== 'null' && is_numeric($addressId)) {
            $currentAddress = $this->addressService->getAddressById($addressId);
            $zone = $currentAddress?->zone;
            $addressName = $currentAddress?->getFullAddressAttribute();
        } else {
            $lat = $request->input('lat');
            $long = $request->input('long');
            $zone = $this->addressService->getAddressByLatLong($lat, $long);
            $addressName = $zone?->getFullAddressAttribute();
        }
        $position = Location::get(request()->ip());

        $response = [
            "address_name" => $addressName ?? ($position ? $position->cityName . ', ' . $position->countryName . ', ' . $position->regionName : null),
            "user_addresses" => [],
            "current_address_id" => null,
            "is_available" => $zone ? true : false,
        ];

        // If user is authenticated
        if ($user = auth('user')->user()) {
            $userAddresses = $user->addresses()->with('zone')->get();
            $response["user_addresses"] = AddressResource::collection($userAddresses);

            // Find current address within user's saved addresses
            if ($zone && isset($zone->id)) {
                if (!request()->header('address-id')) {
                    $currentAddress = $userAddresses
                        ->where('zone_id', $zone->id)
                        ->sortByDesc('created_at')
                        ->first();
                }
            }
            $response["current_address_id"] = $currentAddress?->id ? (string) $currentAddress->id : null;
        }

        return $this->successResponse($response, __("message.success"));
    }
}
