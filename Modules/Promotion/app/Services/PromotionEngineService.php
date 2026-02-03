<?php

namespace Modules\Promotion\Services;

use Modules\Promotion\Models\Promotion;
use Illuminate\Support\Facades\DB;
use Modules\Store\Models\Store;
use Modules\User\Models\User;
use Modules\Cart\Models\Cart;
use App\Enums\PromotionSubTypeEnum;
use App\Helpers\CurrencyHelper;

class PromotionEngineService
{
    public function evaluatePromotions(Cart $cart, Store $store, User $user = null): array
    {
        // التحقق من صحة المدخلات
        if (!$cart || !$store) {
            return [
                'promotions' => [],
                'total_savings' => 0,
                'delivery_cost' => 0,
                'product_savings' => 0,
                'new_order_total' => 0,
                'original_order_total' => 0,
                'errors' => ['Invalid cart or store provided']
            ];
        }

        try {
            $context = $this->buildOrderContext($cart, $store, $user);

            $eligiblePromotions = $this->getEligiblePromotions($context);

            // تقييم الترويجات
            $evaluatedPromotions = $eligiblePromotions->map(function ($promotion) use ($context) {
                return $this->evaluatePromotion($promotion, $context);
            })->filter(fn($res) => $res['is_valid'])->values()->toArray();

            // حل التعارضات
            $finalPromotions = $this->resolveConflicts($evaluatedPromotions);

            // حساب النتائج
            $result = $this->calculateResults($finalPromotions, $context);

            return $result;
        } catch (\Exception $e) {
            return [
                'promotions' => [],
                'total_savings' => 0,
                'delivery_cost' => $cart->getDeliveryCost(),
                'product_savings' => 0,
                'new_order_total' => $cart->getTotal() + $cart->getDeliveryCost(),
                'original_order_total' => $cart->getTotal() + $cart->getDeliveryCost(),
                'errors' => ['Error evaluating promotions: ' . $e->getMessage()]
            ];
        }
    }
    private function buildOrderContext(Cart $cart, Store $store, User $user = null): array
    {
        // الحصول على currency_factor من المتجر أو استخدام القيمة الافتراضية
        $currencyFactor = $store->currency_factor ?? 100;

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
            'currency_factor' => $currencyFactor,
        ];
    }

    private function getEligiblePromotions(array $context): \Illuminate\Support\Collection
    {
        return Promotion::where('is_active', true)
            ->where('start_date', '<=', now())
            ->where(function ($query) use ($context) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->where(function ($query) use ($context) {
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
            ->where(function ($query) use ($context) {
                if ($context['order_amount']) {
                    $query->where(function ($subQuery) use ($context) {
                        $subQuery->where('min_order_amount', '<=', $context['order_amount'])
                            ->orWhereNull('min_order_amount');
                    })
                        ->where(function ($subQuery) use ($context) {
                            $subQuery->where('max_order_amount', '>=', $context['order_amount'])
                                ->orWhereNull('max_order_amount');
                        });
                }
            })
            ->where(function ($query) use ($context) {
                $query->where(function ($subQuery) use ($context) {
                    $subQuery->whereColumn('current_usage', '<', 'usage_limit')
                        ->orWhereNull('usage_limit');
                });

                if ($context['user_id']) {
                    $query->whereDoesntHave('userPromotionUsage', function ($subQuery) use ($context) {
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
            case PromotionSubTypeEnum::DISCOUNT_DELIVERY:
            case PromotionSubTypeEnum::FIXED_DELIVERY:
            case PromotionSubTypeEnum::PERCENTAGE_DISCOUNT:
            case PromotionSubTypeEnum::FIRST_ORDER:
                return $this->evaluateDeliveryPromotion($promotion, $context);
            case PromotionSubTypeEnum::FIXED_PRICE:
                return $this->evaluateFixedPrice($promotion, $context);
            default:
                return ['is_valid' => false, 'promotion' => $promotion];
        }
    }

    private function evaluateDeliveryPromotion(Promotion $promotion, array $context): array
    {
        $store = Store::find($context['store_id']);
        if (!$promotion->isValidForDelivery($store, $context['order_amount'])) {
            return ['is_valid' => false, 'promotion' => $promotion];
        }

        $newDeliveryCost = $promotion->calculateDeliveryCost($store, $context['base_delivery_cost'], $context['order_amount']);
        $savings = $context['base_delivery_cost'] - $newDeliveryCost;

        return [
            'is_valid' => true,
            'promotion' => $promotion,
            'type' => 'delivery',
            'savings' => $savings,
            'new_delivery_cost' => $newDeliveryCost,
            'details' => [
                'original_delivery_cost' => $context['base_delivery_cost'],
                'new_delivery_cost' => $newDeliveryCost,
                'savings' => $savings
            ]
        ];
    }

    private function evaluateFixedPrice(Promotion $promotion, array $context): array
    {
        $savings = 0;
        $fixedPrices = $promotion->promotionFixedPrices;
        $currencyFactor = $context['currency_factor'];

        foreach ($context['cart_items'] as $item) {
            $fixedPrice = $fixedPrices->where('product_id', $item->product_id)
                ->orWhere('store_id', $item->store_id)
                ->first();

            if ($fixedPrice && $fixedPrice->fixed_price < $item->price) {
                // تحويل الأسعار إلى نفس الوحدة للحسابات الدقيقة
                $itemPriceInMinor = CurrencyHelper::toMinorUnits($item->price, $currencyFactor);
                $fixedPriceInMinor = $fixedPrice->fixed_price;
                $savingsPerItem = $itemPriceInMinor - $fixedPriceInMinor;

                $savings += $savingsPerItem * $item->quantity;
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
        // ترتيب حسب نوع الترويج (لحل التعارضات)
        usort($promotions, function ($a, $b) {
            // ترتيب أولويات أنواع الترويج: delivery أولاً، ثم product
            $priorityOrder = ['delivery' => 1, 'product' => 2];
            $aPriority = $priorityOrder[$a['type']] ?? 999;
            $bPriority = $priorityOrder[$b['type']] ?? 999;
            return $aPriority <=> $bPriority;
        });

        $finalPromotions = [];
        $usedTypes = [];

        foreach ($promotions as $promotion) {
            $type = $promotion['type'];

            // التحقق من التعارضات
            if (in_array($type, $usedTypes)) {
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
