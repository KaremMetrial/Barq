<?php

namespace Modules\PaymentMethod\Services;

use Modules\PaymentMethod\Models\PaymentMethod;
use Modules\PaymentMethod\Repositories\PaymentMethodRepository;

class PaymentMethodService
{
    public function __construct(protected PaymentMethodRepository $paymentMethodRepository) {}

    /**
     * Get all payment methods.
     */
    public function getAll()
    {
        return $this->paymentMethodRepository->all();
    }

    /**
     * Get active payment methods ordered by sort order.
     */
    public function getActiveOrdered()
    {
        return $this->paymentMethodRepository->getActiveOrdered();
    }

    /**
     * Create a new payment method.
     */
    public function create(array $data): PaymentMethod
    {
        return $this->paymentMethodRepository->create($data);
    }

    /**
     * Update an existing payment method.
     */
    public function update(PaymentMethod $paymentMethod, array $data): PaymentMethod
    {
        return $this->paymentMethodRepository->update($paymentMethod, $data);
    }

    /**
     * Delete a payment method.
     */
    public function delete(PaymentMethod $paymentMethod): bool
    {
        return $this->paymentMethodRepository->delete($paymentMethod);
    }

    /**
     * Find payment method by ID.
     */
    public function findById(int $id): ?PaymentMethod
    {
        return $this->paymentMethodRepository->find($id);
    }

    /**
     * Find payment method by code.
     */
    public function findByCode(string $code): ?PaymentMethod
    {
        return $this->paymentMethodRepository->findByCode($code);
    }
}
