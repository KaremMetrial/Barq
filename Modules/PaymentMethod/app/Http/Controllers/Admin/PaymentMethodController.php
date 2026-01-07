<?php

namespace Modules\PaymentMethod\Http\Controllers\Admin;

use App\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\PaymentMethod\Models\PaymentMethod;
use Modules\PaymentMethod\Services\PaymentMethodService;
use Modules\PaymentMethod\Http\Resources\PaymentMethodResource;
use Modules\PaymentMethod\Http\Requests\CreatePaymentMethodRequest;
use Modules\PaymentMethod\Http\Requests\UpdatePaymentMethodRequest;

class PaymentMethodController extends Controller
{
    use ApiResponse, AuthorizesRequests;

    public function __construct(protected PaymentMethodService $paymentMethodService) {}

    /**
     * Display a listing of payment methods.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', PaymentMethod::class);
        $paymentMethods = $this->paymentMethodService->getAll();
        return $this->successResponse( PaymentMethodResource::collection($paymentMethods));
    }

    /**
     * Get active payment methods ordered by sort order.
     */
    public function getActive(Request $request)
    {
        $this->authorize('viewAny', PaymentMethod::class);
        $paymentMethods = $this->paymentMethodService->getActiveOrdered();

        return $this->successResponse(new PaymentMethodResource($paymentMethods));
    }

    /**
     * Store a newly created payment method.
     */
    public function store(CreatePaymentMethodRequest $request)
    {
        $this->authorize('create', PaymentMethod::class);
        $paymentMethod = $this->paymentMethodService->create($request->validated());

        return $this->successResponse(
            new PaymentMethodResource($paymentMethod),
            'Payment method created successfully',
            201
        );
    }

    /**
     * Display the specified payment method.
     */
    public function show(PaymentMethod $paymentMethod)
    {
        $this->authorize('view', $paymentMethod);
        return $this->successResponse(new PaymentMethodResource($paymentMethod));
    }

    /**
     * Update the specified payment method.
     */
    public function update(UpdatePaymentMethodRequest $request, PaymentMethod $paymentMethod)
    {
        $this->authorize('update', $paymentMethod);
        $updatedPaymentMethod = $this->paymentMethodService->update($paymentMethod, $request->validated());

        return $this->successResponse(
            new PaymentMethodResource($updatedPaymentMethod),
            'Payment method updated successfully'
        );
    }

    /**
     * Remove the specified payment method.
     */
    public function destroy(PaymentMethod $paymentMethod)
    {
        $this->authorize('delete', $paymentMethod);
        $this->paymentMethodService->delete($paymentMethod);

        return $this->successResponse(null, 'Payment method deleted successfully');
    }
}
