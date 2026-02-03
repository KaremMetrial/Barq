<?php

namespace Modules\Promotion\Services;

use Illuminate\Support\Facades\DB;
use Modules\Promotion\Models\Promotion;
use Illuminate\Database\Eloquent\Collection;
use Modules\Promotion\Repositories\PromotionRepository;
use App\Helpers\CurrencyHelper;
use App\Traits\FileUploadTrait;

class PromotionService
{
    use FileUploadTrait;
    public function __construct(
        protected PromotionRepository $promotionRepository
    ) {}

    public function getAllPromotions($filters = [])
    {
        return $this->promotionRepository->paginate($filters);
    }

    public function createPromotion(array $data): ?Promotion
    {
        return DB::transaction(function () use ($data) {
            $data = array_filter($data, fn($value) => !blank($value));

            $currencyFactor = $data['currency_factor'] ?? null;
            if ($currencyFactor) {
                if (isset($data['discount_value'])) {
                    $data['discount_value'] = CurrencyHelper::toMinorUnits($data['discount_value'], $currencyFactor);
                }
                if (isset($data['min_order_amount'])) {
                    $data['min_order_amount'] = CurrencyHelper::toMinorUnits($data['min_order_amount'], $currencyFactor);
                }
                if (isset($data['max_order_amount'])) {
                    $data['max_order_amount'] = CurrencyHelper::toMinorUnits($data['max_order_amount'], $currencyFactor);
                }
                if (isset($data['fixed_delivery_price'])) {
                    $data['fixed_delivery_price'] = CurrencyHelper::toMinorUnits($data['fixed_delivery_price'], $currencyFactor);
                }
            }

            if (isset($data['image'])) {
                $data['image'] = $this->upload(request(), 'image', 'promotions','public',[512,512]);
            }

            $promotion = $this->promotionRepository->create($data);

            if (isset($data['targets']) && is_array($data['targets'])) {
                foreach ($data['targets'] as $target) {
                    $promotion->targets()->create([
                        'target_type' => $target['target_type'],
                        'target_id' => $target['target_id'],
                        'is_excluded' => $target['is_excluded'] ?? false,
                    ]);
                }
            }

            if ($data['type'] === 'product' && $data['sub_type'] === 'fixed_price' && isset($data['fixed_prices'])) {
                foreach ($data['fixed_prices'] as $fixedPrice) {
                    $promotion->fixedPrices()->create([
                        'store_id' => $fixedPrice['store_id'],
                        'product_id' => $fixedPrice['product_id'],
                        'fixed_price' => CurrencyHelper::toMinorUnits($fixedPrice['fixed_price'], $currencyFactor),
                    ]);
                }
            }

            return $promotion;
        });
    }

    public function getPromotionById(int $id): ?Promotion
    {
        return $this->promotionRepository->find($id);
    }

    public function updatePromotion(int $id, array $data): ?Promotion
    {
        return DB::transaction(function () use ($data, $id) {
            $data = array_filter($data, fn($value) => !blank($value));

            // Convert monetary amounts to minor units if provided
            $currencyFactor = $data['currency_factor'] ?? null;
            if ($currencyFactor) {
                if (isset($data['discount_value'])) {
                    $data['discount_value'] = CurrencyHelper::toMinorUnits($data['discount_value'], (int) $currencyFactor);
                }
                if (isset($data['min_order_amount'])) {
                    $data['min_order_amount'] = CurrencyHelper::toMinorUnits($data['min_order_amount'], (int) $currencyFactor);
                }
                if (isset($data['max_order_amount'])) {
                    $data['max_order_amount'] = CurrencyHelper::toMinorUnits($data['max_order_amount'], (int) $currencyFactor);
                }
            }

            if (isset($data['image'])) {
                $data['image'] = $this->upload(request(), 'image', 'promotions','public',[512,512]);
            }

            $promotion = $this->promotionRepository->update($id, $data);

            // Update promotion targets
            if (isset($data['targets'])) {
                $promotion->targets()->delete();
                foreach ($data['targets'] as $target) {
                    $promotion->targets()->create([
                        'target_type' => $target['target_type'],
                        'target_id' => $target['target_id'],
                        'is_excluded' => $target['is_excluded'] ?? false,
                    ]);
                }
            }

            // Update fixed prices for fixed_price promotions
            if ($data['type'] === 'product' && $data['sub_type'] === 'fixed_price' && isset($data['fixed_prices'])) {
                $promotion->fixedPrices()->delete();
                foreach ($data['fixed_prices'] as $fixedPrice) {
                    $promotion->fixedPrices()->create([
                        'store_id' => $fixedPrice['store_id'],
                        'product_id' => $fixedPrice['product_id'],
                        'fixed_price' => CurrencyHelper::toMinorUnits($fixedPrice['fixed_price'], $currencyFactor),
                    ]);
                }
            }

            return $promotion;
        });
    }

    public function deletePromotion(int $id): bool
    {
        return $this->promotionRepository->delete($id);
    }

    public function getPromotionTypes(): array
    {
        return [
            'delivery' => [
                'free_delivery' => [
                    'label' => __('message.promotion_types.delivery.free_delivery.label'),
                    'description' => __('message.promotion_types.delivery.free_delivery.description'),
                    'fields' => ['min_order_amount', 'max_order_amount', 'currency_factor']
                ],
                'discount_delivery' => [
                    'label' => __('message.promotion_types.delivery.discount_delivery.label'),
                    'description' => __('message.promotion_types.delivery.discount_delivery.description'),
                    'fields' => ['discount_value', 'currency_factor']
                ],
                'fixed_delivery' => [
                    'label' => __('message.promotion_types.delivery.fixed_delivery.label'),
                    'description' => __('message.promotion_types.delivery.fixed_delivery.description'),
                    'fields' => ['fixed_delivery_price', 'currency_factor']
                ]
            ],
            'product' => [
                'fixed_price' => [
                    'label' => __('message.promotion_types.product.fixed_price.label'),
                    'description' => __('message.promotion_types.product.fixed_price.description'),
                    'fields' => ['fixed_prices']
                ],
                'percentage_discount' => [
                    'label' => __('message.promotion_types.product.percentage_discount.label'),
                    'description' => __('message.promotion_types.product.percentage_discount.description'),
                    'fields' => ['discount_value']
                ],
                'first_order' => [
                    'label' => __('message.promotion_types.product.first_order.label'),
                    'description' => __('message.promotion_types.product.first_order.description'),
                    'fields' => ['discount_value', 'currency_factor']
                ],
                'bundle' => [
                    'label' => __('message.promotion_types.product.bundle.label'),
                    'description' => __('message.promotion_types.product.bundle.description'),
                    'fields' => ['discount_value', 'currency_factor']
                ],
                'buy_one_get_one' => [
                    'label' => __('message.promotion_types.product.buy_one_get_one.label'),
                    'description' => __('message.promotion_types.product.buy_one_get_one.description'),
                    'fields' => []
                ]
            ]
        ];
    }

    public function validatePromotion(Promotion $promotion, array $context = []): array
    {
        $errors = [];

        // Check if promotion is active
        if (!$promotion->is_active) {
            $errors[] = __('message.promotion_not_active');
        }

        // Check date validity
        $now = now();
        if ($promotion->start_date && $now->lt($promotion->start_date)) {
            $errors[] = __('message.promotion_not_started');
        }
        if ($promotion->end_date && $now->gt($promotion->end_date)) {
            $errors[] = __('message.promotion_expired');
        }

        // Check usage limits
        if ($promotion->usage_limit && $promotion->current_usage >= $promotion->usage_limit) {
            $errors[] = __('message.promotion_usage_limit_reached');
        }

        // Check user-specific usage limits if user is provided
        if (isset($context['user'])) {
            $userUsage = $promotion->userUsages()
                ->where('user_id', $context['user']->id)
                ->count();

            if ($promotion->usage_limit_per_user && $userUsage >= $promotion->usage_limit_per_user) {
                $errors[] = __('message.promotion_user_usage_limit_reached');
            }
        }

        // Check geographic restrictions
        if (isset($context['country_id']) && $promotion->country_id && $promotion->country_id !== $context['country_id']) {
            $errors[] = __('message.promotion_not_available_country');
        }
        if (isset($context['city_id']) && $promotion->city_id && $promotion->city_id !== $context['city_id']) {
            $errors[] = __('message.promotion_not_available_city');
        }
        if (isset($context['zone_id']) && $promotion->zone_id && $promotion->zone_id !== $context['zone_id']) {
            $errors[] = __('message.promotion_not_available_zone');
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'promotion' => $promotion
        ];
    }
}
