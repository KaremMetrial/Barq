<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPrice extends Model
{
    protected $fillable = [
        'price',
        'purchase_price',
        'product_id',
        'sale_price',
        'currency_code',
        'currency_symbol',
        'price_minor',
        'sale_price_minor',
        'purchase_price_minor',
        'currency_factor',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function sale(): HasOne
    {
        return $this->hasOne(ProductPriceSale::class);
    }

    /**
     * Get the currency factor for this price
     *
     * @return int
     */
    public function getCurrencyFactor(): int
    {
        // Return the stored currency factor, or derive it from relationships, or default to 100
        return $this->currency_factor
            ?? $this->product?->store?->address?->zone?->city?->governorate?->country?->currency_factor
            ?? 100;
    }

    /**
     * Return price in minor units. If not present, compute from decimal price using store's currency_factor.
     */
    public function priceMinorValue(int $defaultFactor = 100): ?int
    {
        return $this->price;
    }

    public function salePriceMinorValue(int $defaultFactor = 100): ?int
    {
        if ($this->sale_price_minor !== null) {
            return (int) $this->sale_price_minor;
        }

        $factor = $this->getCurrencyFactor();

        return $this->sale_price !== null ? \App\Helpers\CurrencyHelper::toMinorUnits((int) $this->sale_price, (int) $factor) : null;
    }

    public function purchasePriceMinorValue(int $defaultFactor = 100): ?int
    {
        if ($this->purchase_price_minor !== null) {
            return (int) $this->purchase_price_minor;
        }

        $factor = $this->getCurrencyFactor();

        return $this->purchase_price !== null ? \App\Helpers\CurrencyHelper::toMinorUnits((int) $this->purchase_price, (int) $factor) : null;
    }
}
