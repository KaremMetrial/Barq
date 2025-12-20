<?php

namespace App\Traits;

use App\Helpers\CurrencyHelper;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * Trait for models with unsigned bigint price fields
 *
 * Provides automatic conversion between database format (unsigned bigint)
 * and display format (decimal float) for price attributes
 *
 * Usage in Model:
 * class Product extends Model {
 *     use HasBigIntPrices;
 *
 *     protected $priceAttributes = ['price', 'sale_price'];
 *
 *     public function getCurrencyUnit(): int {
 *         return $this->store->country->currency_unit ?? 100;
 *     }
 * }
 */
trait HasBigIntPrices
{
    /**
     * List of attributes that store prices as unsigned bigint
     * Override in model to specify which attributes are prices
     */
    protected array $priceAttributes = [];

    /**
     * Get currency unit for this model
     * Override in model to customize currency unit retrieval
     */
    public function getCurrencyUnit(): int
    {
        // Default implementation - override in model
        if (auth()->check() && auth()->user()->country) {
            return auth()->user()->country->currency_unit ?? 100;
        }

        // Fallback to default
        return 100; // Default to cents (USD-like)
    }

    /**
     * Automatically convert price attributes on retrieval
     */
    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        // Check if this is a price attribute and convert if needed
        if (in_array($key, $this->priceAttributes) && is_int($value)) {
            return CurrencyHelper::unsignedBigIntToPrice($value, $this->getCurrencyUnit());
        }

        return $value;
    }

    /**
     * Automatically convert price attributes on setting
     */
    public function setAttribute($key, $value)
    {
        // Check if this is a price attribute and convert if needed
        if (in_array($key, $this->priceAttributes) && is_float($value)) {
            $value = CurrencyHelper::priceToUnsignedBigInt($value, $this->getCurrencyUnit());
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * Get raw price value (unsigned bigint format)
     */
    public function getRawPrice(string $attribute): int
    {
        return (int)$this->getAttributes()[$attribute] ?? 0;
    }

    /**
     * Set raw price value (unsigned bigint format)
     */
    public function setRawPrice(string $attribute, int $value): void
    {
        $this->attributes[$attribute] = $value;
    }

    /**
     * Get formatted price for display
     */
    public function getFormattedPrice(string $attribute, string $currencyCode = 'USD'): string
    {
        $rawPrice = $this->getRawPrice($attribute);
        return CurrencyHelper::formatBigIntPrice($rawPrice, $currencyCode, $this->getCurrencyUnit());
    }

    /**
     * Add amount to price
     */
    public function addToPrice(string $attribute, float $amount): void
    {
        $raw = $this->getRawPrice($attribute);
        $addRaw = CurrencyHelper::priceToUnsignedBigInt($amount, $this->getCurrencyUnit());
        $this->setRawPrice($attribute, CurrencyHelper::addBigIntPrices($raw, $addRaw));
    }

    /**
     * Subtract amount from price
     */
    public function subtractFromPrice(string $attribute, float $amount): void
    {
        $raw = $this->getRawPrice($attribute);
        $subtractRaw = CurrencyHelper::priceToUnsignedBigInt($amount, $this->getCurrencyUnit());
        $this->setRawPrice($attribute, CurrencyHelper::subtractBigIntPrices($raw, $subtractRaw));
    }

    /**
     * Apply percentage to price
     */
    public function applyPercentageToPrice(string $attribute, float $percentage): void
    {
        $raw = $this->getRawPrice($attribute);
        $this->setRawPrice($attribute, CurrencyHelper::percentageOfBigIntPrice($raw, $percentage));
    }

    /**
     * Apply discount to price
     */
    public function applyDiscountToPrice(string $attribute, float $discountPercent): void
    {
        $raw = $this->getRawPrice($attribute);
        $this->setRawPrice($attribute, CurrencyHelper::priceAfterDiscountBigInt($raw, $discountPercent));
    }

    /**
     * Apply tax to price
     */
    public function applyTaxToPrice(string $attribute, float $taxRate): void
    {
        $raw = $this->getRawPrice($attribute);
        $this->setRawPrice($attribute, CurrencyHelper::priceWithTaxBigInt($raw, $taxRate));
    }

    /**
     * Get all prices in display format
     */
    public function getPricesForDisplay(): array
    {
        $prices = [];
        foreach ($this->priceAttributes as $attribute) {
            if ($this->hasAttribute($attribute)) {
                $prices[$attribute] = $this->getAttribute($attribute);
            }
        }
        return $prices;
    }

    /**
     * Get all prices in raw format
     */
    public function getRawPrices(): array
    {
        $prices = [];
        foreach ($this->priceAttributes as $attribute) {
            if ($this->hasAttribute($attribute)) {
                $prices[$attribute] = $this->getRawPrice($attribute);
            }
        }
        return $prices;
    }
}

