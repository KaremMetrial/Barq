<?php

namespace App\Services;

use App\Models\Promotion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Store\Models\Store;
use Modules\User\Models\User;
use Modules\Order\Models\Order;
use Modules\Cart\Models\Cart;
use App\Enums\PromotionSubTypeEnum;

class PromotionEngineService
{
    public function evaluatePromotions(Cart $cart, Store $store, User $user = null): array
    {
        $context = $this->buildOrderContext($cart, $store, $user);
        
        $eligiblePromotions = $this->getEligiblePromotions($context);
        
        // تقييم الترويجات
        $evaluatedPromotions = $this->evaluatePromotions($eligiblePromotions, $context);
        
        // حل التعارضات
        $finalPromotions = $this->resolveConflicts($evaluatedPromotions);
        
        // حساب النتائج
        $result = $this->calculateResults($finalPromotions, $context);
        
        return $result;
    }

    private function buildOrderContext(Cart $cart, Store $store, User $user = null): array
    {
        return [
            'user_id' => $user?->id,
            'store_id' => $store->id,
            'country_id' => $store->country_id,
            'city_id' => $store->city_id,
            'zone_id' => $store->zone_id,
            'order_amount' => $cart->getTotal(),
            'base_delivery_cost' => $cart->getDeliveryCost(),
            'cart_items' => $cart->items,
            'user_orders_count' => $user?->orders()->count() ?? 0,
        ];
    }

    private function getEligiblePromotions(array $context): \Illuminate\Support\Collection
    {
        return Promotion::where('is_active', true)
            ->where('start_date', '<=', now())
            ->where(function($query) use ($context) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>=', now());
            })
            ->where(function($query) use ($context) {
                if ($context['country_id']) {
                    $query->where('country_id', $context['country_id'])
                          ->orWhereNull('country_id');
                }
                if ($context['city_id']) {
                    $query->where('city_id', $context['city_id'])
                          ->orWhereNull('city_id');
                }
                if ($context['zone_id']) {
                    $query->where('zone_id', $context['zone_id'])
                          ->orWhereNull('zone_id');
                }
            })
            ->where(function($query) use ($context) {
                if ($context['order_amount']) {
                    $query->where(function($subQuery) use ($context) {
                        $subQuery->where('min_order_amount', '<=', $context['order_amount'])
                                 ->orWhereNull('min_order_amount');
                    })
                    ->where(function($subQuery) use ($context) {
                        $subQuery->where('max_order_amount', '>=', $context['order_amount'])
                                 ->orWhereNull('max_order_amount');
                    });
                }
            })
            ->where(function($query) use ($context) {
                $query->where(function($subQuery) use ($context) {
                    $subQuery->whereColumn('current_usage', '<', 'usage_limit')
                             ->orWhereNull('usage_limit');
                });
                
                if ($context['user_id']) {
                    $query->whereDoesntHave('userPromotionUsage', function($subQuery) use ($context) {
                        $subQuery->where('user_id', $context['user_id'])
                                 ->where('usage_count', '>=', DB::raw('usage_limit_per_user'));
                    });
                }
            })
            ->get();
    }

    private function evaluatePromotion(Promotion $promotion, array $context): array
    {
        switch ($promotion->sub_type) {
            case PromotionSubTypeEnum::FREE_DELIVERY:
                return $this->evaluateFreeDelivery($promotion, $context);
            case PromotionSubTypeEnum::DISCOUNT_DELIVERY:
                return $this->evaluateDiscountDelivery($promotion, $context);
            case PromotionSubTypeEnum::FIXED_DELIVERY:
                return $this->evaluateFixedDelivery($promotion, $context);
            case PromotionSubTypeEnum::FIXED_PRICE:
                return $this->evaluateFixedPrice($promotion, $context);
            case PromotionSubTypeEnum::PERCENTAGE_DISCOUNT:
                return $this->evaluatePercentageDiscount($promotion, $context);
            case PromotionSubTypeEnum::FIRST_ORDER:
                return $this->evaluateFirstOrder($promotion, $context);
            default:
                return ['is_valid' => false, 'promotion' => $promotion];
        }
    }
    private function evaluateFirstOrder(Promotion $promotion, array $context): array
    {
        if (!$promotion->isValidForDelivery($context['store_id'], $context['order_amount'])) {
            return ['is_valid' => false, 'promotion' => $promotion];
        }

        return [
            'is_valid' => true,
            'promotion' => $promotion,
            'type' => 'delivery',
            'savings' => $context['base_delivery_cost'],
            'new_delivery_cost' => 0,
            'details' => [
                'original_delivery_cost' => $context['base_delivery_cost'],
                'new_delivery_cost' => 0,
                'savings' => $context['base_delivery_cost']
            ]
        ];
    }
    private function evaluateFixedDelivery(Promotion $promotion, array $context): array
    {
        if (!$promotion->isValidForDelivery($context['store_id'], $context['order_amount'])) {
            return ['is_valid' => false, 'promotion' => $promotion];
        }

        return [
            'is_valid' => true,
            'promotion' => $promotion,
            'type' => 'delivery',
            'savings' => $context['base_delivery_cost'],
            'new_delivery_cost' => $promotion->fixed_delivery_price ?? 0,
            'details' => [
                'original_delivery_cost' => $context['base_delivery_cost'],
                'new_delivery_cost' => $promotion->fixed_delivery_price ?? 0,
                'savings' => $context['base_delivery_cost']
            ]
        ];
    }
    private function evaluateDiscountDelivery(Promotion $promotion, array $context): array
    {
        if (!$promotion->isValidForDelivery($context['store_id'], $context['order_amount'])) {
            return ['is_valid' => false, 'promotion' => $promotion];
        }

        return [
            'is_valid' => true,
            'promotion' => $promotion,
            'type' => 'delivery',
            'savings' => $context['base_delivery_cost'],
            'new_delivery_cost' => $context['base_delivery_cost'] - ($promotion->discount_value ?? 0),
            'details' => [
                'original_delivery_cost' => $context['base_delivery_cost'],
                'new_delivery_cost' => $context['base_delivery_cost'] - ($promotion->discount_value ?? 0),
                'savings' => $context['base_delivery_cost']
            ]
        ];
    }
    private function evaluatePercentageDiscount(Promotion $promotion, array $context): array
    {
        if (!$promotion->isValidForDelivery($context['store_id'], $context['order_amount'])) {
            return ['is_valid' => false, 'promotion' => $promotion];
        }

        $discountPercentage = $promotion->discount_value ?? 0;
        $savings = ($context['base_delivery_cost'] * $discountPercentage) / 100;
        $newDeliveryCost = $context['base_delivery_cost'] - $savings;

        return [
            'is_valid' => true,
            'promotion' => $promotion,
            'type' => 'delivery',
            'savings' => $savings,
            'new_delivery_cost' => $newDeliveryCost,
            'details' => [
                'original_delivery_cost' => $context['base_delivery_cost'],
                'new_delivery_cost' => $newDeliveryCost,
                'savings' => $savings,
                'discount_percentage' => $discountPercentage
            ]
        ];
    }
    private function evaluateFreeDelivery(Promotion $promotion, array $context): array
    {
        if (!$promotion->isValidForDelivery($context['store_id'], $context['order_amount'])) {
            return ['is_valid' => false, 'promotion' => $promotion];
        }

        return [
            'is_valid' => true,
            'promotion' => $promotion,
            'type' => 'delivery',
            'savings' => $context['base_delivery_cost'],
            'new_delivery_cost' => 0,
            'details' => [
                'original_delivery_cost' => $context['base_delivery_cost'],
                'new_delivery_cost' => 0,
                'savings' => $context['base_delivery_cost']
            ]
        ];
    }

    private function evaluateFixedPrice(Promotion $promotion, array $context): array
    {
        $savings = 0;
        $fixedPrices = $promotion->promotionFixedPrices;
        
        foreach ($context['cart_items'] as $item) {
            $fixedPrice = $fixedPrices->where('product_id', $item->product_id)
                                   ->orWhere('store_id', $item->store_id)
                                   ->first();
            
            if ($fixedPrice && $fixedPrice->fixed_price < $item->price) {
                $savings += ($item->price - $fixedPrice->fixed_price) * $item->quantity;
            }
        }

        if ($savings > 0) {
            return [
                'is_valid' => true,
                'promotion' => $promotion,
                'type' => 'product',
                'savings' => $savings,
                'details' => [
                    'product_savings' => $savings,
                    'items_affected' => count($context['cart_items'])
                ]
            ];
        }

        return ['is_valid' => false, 'promotion' => $promotion];
    }

    private function resolveConflicts(array $promotions): array
    {
        // ترتيب حسب الأولوية
        usort($promotions, function($a, $b) {
            return $a['promotion']->priority <=> $b['promotion']->priority;
        });

        $finalPromotions = [];
        $usedTypes = [];

        foreach ($promotions as $promotion) {
            $type = $promotion['type'];
            
            // التحقق من التعارضات
            if (in_array($type, $usedTypes) && $promotion['promotion']->exclude_other_promotions) {
                continue;
            }

            $finalPromotions[] = $promotion;
            $usedTypes[] = $type;
        }

        return $finalPromotions;
    }

    private function calculateResults(array $promotions, array $context): array
    {
        $totalSavings = 0;
        $deliveryCost = $context['base_delivery_cost'];
        $productSavings = 0;

        foreach ($promotions as $promotion) {
            $totalSavings += $promotion['savings'];
            
            if ($promotion['type'] === 'delivery') {
                $deliveryCost = $promotion['new_delivery_cost'] ?? $deliveryCost;
            } elseif ($promotion['type'] === 'product') {
                $productSavings += $promotion['savings'];
            }
        }

        return [
            'promotions' => $promotions,
            'total_savings' => $totalSavings,
            'delivery_cost' => $deliveryCost,
            'product_savings' => $productSavings,
            'new_order_total' => $context['order_amount'] + $deliveryCost - $productSavings,
            'original_order_total' => $context['order_amount'] + $context['base_delivery_cost'],
        ];
    }
}
