<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PromotionValidationService
{
    public function validatePromotionCreation(array $data): array
    {
        $validator = Validator::make($data, [
            'type' => 'required|string|in:delivery,product',
            'sub_type' => 'required|string',
            'is_active' => 'boolean',
            'start_date' => 'required|date_format:Y-m-d H:i:s',
            'end_date' => 'required|date_format:Y-m-d H:i:s|after_or_equal:start_date',
            'usage_limit' => 'integer|min:0',
            'usage_limit_per_user' => 'integer|min:0',
            'country_id' => 'integer|exists:countries,id',
            'city_id' => 'integer|exists:cities,id',
            'zone_id' => 'integer|exists:zones,id',
            'min_order_amount' => 'numeric|min:0',
            'max_order_amount' => 'numeric|min:0|gte:min_order_amount',
            'discount_value' => 'numeric|min:0',
            'fixed_delivery_price' => 'numeric|min:0',
            'currency_factor' => 'integer|min:1',
            'first_order_only' => 'boolean',
            'translations' => 'required|array',
            'translations.*.locale' => 'required|string',
            'translations.*.title' => 'required|string|max:255',
            'translations.*.description' => 'required|string',
            'targets' => 'array',
            'targets.*.target_type' => 'required|string|in:store,category,product',
            'targets.*.target_id' => 'required|integer',
            'targets.*.is_excluded' => 'boolean',
            'fixed_prices' => 'array',
            'fixed_prices.*.store_id' => 'required|integer',
            'fixed_prices.*.product_id' => 'required|integer',
            'fixed_prices.*.fixed_price' => 'required|numeric|min:0',
        ]);

        // Custom validation rules based on promotion type and sub-type
        $validator->after(function ($validator) use ($data) {
            $type = $data['type'] ?? null;
            $subType = $data['sub_type'] ?? null;

            // Delivery promotion validation
            if ($type === 'delivery') {
                $this->validateDeliveryPromotion($validator, $data, $subType);
            }

            // Product promotion validation
            if ($type === 'product') {
                $this->validateProductPromotion($validator, $data, $subType);
            }

            // Geographic validation
            $this->validateGeographicRestrictions($validator, $data);

            // Currency validation
            $this->validateCurrencySettings($validator, $data);
        });

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        return $validator->validated();
    }

    public function validatePromotionUpdate(array $data): array
    {
        $rules = [
            'type' => 'string|in:delivery,product',
            'sub_type' => 'string',
            'is_active' => 'boolean',
            'start_date' => 'date_format:Y-m-d H:i:s',
            'end_date' => 'date_format:Y-m-d H:i:s|after_or_equal:start_date',
            'usage_limit' => 'integer|min:0',
            'usage_limit_per_user' => 'integer|min:0',
            'country_id' => 'integer|exists:countries,id',
            'city_id' => 'integer|exists:cities,id',
            'zone_id' => 'integer|exists:zones,id',
            'min_order_amount' => 'numeric|min:0',
            'max_order_amount' => 'numeric|min:0|gte:min_order_amount',
            'discount_value' => 'numeric|min:0',
            'fixed_delivery_price' => 'numeric|min:0',
            'currency_factor' => 'integer|min:1',
            'first_order_only' => 'boolean',
            'translations' => 'array',
            'translations.*.locale' => 'string',
            'translations.*.title' => 'string|max:255',
            'translations.*.description' => 'string',
            'targets' => 'array',
            'targets.*.target_type' => 'string|in:store,category,product',
            'targets.*.target_id' => 'integer',
            'targets.*.is_excluded' => 'boolean',
            'fixed_prices' => 'array',
            'fixed_prices.*.store_id' => 'integer',
            'fixed_prices.*.product_id' => 'integer',
            'fixed_prices.*.fixed_price' => 'numeric|min:0',
        ];

        // Only validate fields that are being updated
        $rules = array_intersect_key($rules, $data);

        $validator = Validator::make($data, $rules);

        // Custom validation rules based on promotion type and sub-type
        $validator->after(function ($validator) use ($data) {
            $type = $data['type'] ?? null;
            $subType = $data['sub_type'] ?? null;

            // Delivery promotion validation
            if ($type === 'delivery') {
                $this->validateDeliveryPromotion($validator, $data, $subType);
            }

            // Product promotion validation
            if ($type === 'product') {
                $this->validateProductPromotion($validator, $data, $subType);
            }

            // Geographic validation
            $this->validateGeographicRestrictions($validator, $data);

            // Currency validation
            $this->validateCurrencySettings($validator, $data);
        });

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        return $validator->validated();
    }

    protected function validateDeliveryPromotion($validator, array $data, ?string $subType): void
    {
        switch ($subType) {
            case 'free_delivery':
                if (!isset($data['min_order_amount'])) {
                    $validator->errors()->add('min_order_amount', 'Minimum order amount is required for free delivery promotions.');
                }
                break;

            case 'discount_delivery':
                if (!isset($data['discount_value'])) {
                    $validator->errors()->add('discount_value', 'Discount value is required for discount delivery promotions.');
                }
                if (isset($data['discount_value']) && $data['discount_value'] > 100) {
                    $validator->errors()->add('discount_value', 'Discount value cannot exceed 100% for delivery promotions.');
                }
                break;

            case 'fixed_delivery':
                if (!isset($data['fixed_delivery_price'])) {
                    $validator->errors()->add('fixed_delivery_price', 'Fixed delivery price is required for fixed delivery promotions.');
                }
                break;

            default:
                $validator->errors()->add('sub_type', 'Invalid sub-type for delivery promotion.');
                break;
        }
    }

    protected function validateProductPromotion($validator, array $data, ?string $subType): void
    {
        switch ($subType) {
            case 'fixed_price':
                if (!isset($data['fixed_prices']) || empty($data['fixed_prices'])) {
                    $validator->errors()->add('fixed_prices', 'Fixed prices are required for fixed price promotions.');
                }
                break;

            case 'percentage_discount':
                if (!isset($data['discount_value'])) {
                    $validator->errors()->add('discount_value', 'Discount value is required for percentage discount promotions.');
                }
                if (isset($data['discount_value']) && $data['discount_value'] > 100) {
                    $validator->errors()->add('discount_value', 'Discount value cannot exceed 100% for product promotions.');
                }
                break;

            case 'first_order':
                if (!isset($data['discount_value'])) {
                    $validator->errors()->add('discount_value', 'Discount value is required for first order promotions.');
                }
                break;

            case 'bundle':
            case 'buy_one_get_one':
                // These types may have specific validation rules
                break;

            default:
                $validator->errors()->add('sub_type', 'Invalid sub-type for product promotion.');
                break;
        }
    }

    protected function validateGeographicRestrictions($validator, array $data): void
    {
        // Validate geographic hierarchy: country -> city -> zone
        if (isset($data['zone_id']) && !isset($data['city_id'])) {
            $validator->errors()->add('city_id', 'City ID is required when zone ID is provided.');
        }

        if (isset($data['city_id']) && !isset($data['country_id'])) {
            $validator->errors()->add('country_id', 'Country ID is required when city ID is provided.');
        }
    }

    protected function validateCurrencySettings($validator, array $data): void
    {
        if (isset($data['currency_factor']) && $data['currency_factor'] <= 0) {
            $validator->errors()->add('currency_factor', 'Currency factor must be greater than 0.');
        }

        // Validate that monetary values are consistent with currency factor
        $currencyFactor = $data['currency_factor'] ?? 1;
        
        $monetaryFields = ['min_order_amount', 'max_order_amount', 'discount_value', 'fixed_delivery_price'];
        foreach ($monetaryFields as $field) {
            if (isset($data[$field]) && $data[$field] < 0) {
                $validator->errors()->add($field, ucfirst(str_replace('_', ' ', $field)) . ' cannot be negative.');
            }
        }
    }
}