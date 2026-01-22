<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Http\Resources\TransactionResource;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\PaginationResource;
class TransactionController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the transactions.
     */
    public function index(Request $request)
    {
        $query = Transaction::with(['transactionable', 'user']);

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by amount range
        if ($request->has('min_amount')) {
            $query->where('amount', '>=', $request->min_amount);
        }
        if ($request->has('max_amount')) {
            $query->where('amount', '<=', $request->max_amount);
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->where('created_at', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->where('created_at', '<=', $request->end_date);
        }

        // Filter by entity (user, store, courier, etc.)
        if ($request->has('entity_type') && $request->has('entity_id')) {
            $query->where('transactionable_type', $request->entity_type)
                ->where('transactionable_id', $request->entity_id);
        }

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by payment method
        if ($request->has('payment_method_id')) {
            $query->where('payment_method_id', $request->payment_method_id);
        }

        // Sort
        $sortField = $request->get('sort_field', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->where('transactionable_type', '!=','platform')->orderBy($sortField, $sortDirection);

        $transactions = $query->paginate($request->per_page);

        $currencyCode = config('settings.default_currency', 'USD');
        $currencyFactor = 100;
        $countryId = null;

        if (auth('sanctum')->check()) {
            $user = auth('sanctum')->user();
            if ($user->currentAccessToken() && $user->currentAccessToken()->country_id) {
                $countryId = $user->currentAccessToken()->country_id;
            }
            if (!$countryId) {
                $countryId = config('settings.default_country', 1);
            }
        } else {
            $countryId = config('settings.default_country', 1);
        }

        if ($countryId) {
            $country = \Modules\Country\Models\Country::find($countryId);
            if ($country) {
                $currencyCode = $country->currency_name ?? config('settings.default_currency', 'USD');
                $currencyFactor = $country->currency_factor ?? 100;
            }
        }

        return $this->successResponse([
            'currency_code' => $currencyCode,
            'currency_factor' => $currencyFactor,
            'transactions' => TransactionResource::collection($transactions),
            'pagination' => new PaginationResource($transactions)
        ], 'Transactions retrieved successfully');
    }

    /**
     * Store a newly created transaction.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'transactionable_type' => 'nullable|string',
            'transactionable_id' => 'nullable|integer',
            'payment_method_id' => 'nullable|string',
        ]);

        $transaction = Transaction::create($request->all());

        return $this->successResponse(
            new TransactionResource($transaction),
            'Transaction created successfully'
        );
    }

    /**
     * Display the specified transaction.
     */
    public function show(int $id): JsonResponse
    {
        $transaction = Transaction::with(['transactionable', 'user'])->findOrFail($id);

        return $this->successResponse(
            new TransactionResource($transaction),
            'Transaction retrieved successfully'
        );
    }

    /**
     * Update the specified transaction.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'type' => 'sometimes|string',
            'amount' => 'sometimes|numeric|min:0',
            'currency' => 'sometimes|string|max:3',
            'transactionable_type' => 'sometimes|string',
            'transactionable_id' => 'sometimes|integer',
            'payment_method_id' => 'sometimes|string',
        ]);

        $transaction = Transaction::findOrFail($id);
        $transaction->update($request->all());

        return $this->successResponse(
            new TransactionResource($transaction),
            'Transaction updated successfully'
        );
    }

    /**
     * Remove the specified transaction.
     */
    public function destroy(int $id): JsonResponse
    {
        $transaction = Transaction::findOrFail($id);
        $transaction->delete();

        return $this->successResponse(
            null,
            'Transaction deleted successfully'
        );
    }

    /**
     * Process a payment transaction
     */
    public function pay(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'description' => 'nullable|string',
            'transactionable_type' => 'nullable|string',
            'transactionable_id' => 'nullable|integer',
            'payment_method_id' => 'required|string', // Payment method is required for pay transactions
        ]);

        $data = $request->all();
        $data['type'] = 'pay'; // Set the type to pay

        $transaction = Transaction::create($data);

        return $this->successResponse(
            new TransactionResource($transaction),
            'Payment transaction created successfully'
        );
    }

    /**
     * Get transaction statistics for admin dashboard
     */
    public function stats(Request $request): JsonResponse
    {
        $query = Transaction::query();

        // Apply date filters
        if ($request->has('start_date')) {
            $query->where('created_at', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->where('created_at', '<=', $request->end_date);
        }

        $stats = [
            'total_transactions' => $query->count(),
            'total_amount' => $query->sum('amount'),
            'average_amount' => $query->avg('amount') ?? 0,
            'transactions_by_type' => $query->groupBy('type')
                ->select('type', DB::raw('count(*) as count'), DB::raw('sum(amount) as total_amount'))
                ->get(),
            'transactions_by_payment_method' => $query->groupBy('payment_method_id')
                ->select('payment_method_id', DB::raw('count(*) as count'), DB::raw('sum(amount) as total_amount'))
                ->whereNotNull('payment_method_id')
                ->get(),
        ];

        return $this->successResponse($stats, 'Transaction statistics retrieved successfully');
    }
}
