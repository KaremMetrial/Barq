<?php

namespace Modules\PaymentMethod\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\PaymentMethod\Models\PaymentMethod;
use Modules\PaymentMethod\Services\PaymentMethodService;
use Modules\PaymentMethod\Http\Resources\PaymentMethodResource;
use Modules\PaymentMethod\Http\Requests\CreatePaymentMethodRequest;
use Modules\PaymentMethod\Http\Requests\UpdatePaymentMethodRequest;

class PaymentMethodController extends Controller
{
    use ApiResponse;

    public function __construct(protected PaymentMethodService $paymentMethodService) {}

    /**
     * Display a listing of payment methods.
     */
    public function index(Request $request)
    {
        $paymentMethods = $this->paymentMethodService->getAll();
        return $this->successResponse( PaymentMethodResource::collection($paymentMethods));
    }
}
