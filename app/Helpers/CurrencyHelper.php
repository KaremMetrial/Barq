<?php

namespace App\Helpers;

class CurrencyHelper
{
    /**
     * Static cache for currency metadata
     */
    protected static $currencyCache = [
        'decimal_places' => [],
        'symbol_positions' => [],
        'symbols' => []
    ];

    /**
     * Format price based on currency code
     *
     * @param float $price
     * @param string $currencyCode
     * @param string|null $currencySymbol
     * @param bool $includeSymbol
     * @return string
     */
    public static function formatPrice(
        int $priceInMinor,
        string $currencyCode,
        ?string $currencySymbol = null,
        int $currencyFactor = 100,
        bool $includeSymbol = false
    ): string {
        $currencyCode = strtoupper($currencyCode);

        // Get decimal places for currency
        $decimalPlaces = self::getDecimalPlacesForCurrency($currencyCode);

        // Convert from minor units → decimal
        $price = self::fromMinorUnits($priceInMinor, $currencyFactor, $decimalPlaces);

        // Format number
        $formattedPrice = number_format($price, $decimalPlaces, '.', '');

        if (! $includeSymbol) {
            return $formattedPrice;
        }

        $symbol = $currencySymbol ?? self::getSymbolForCurrencyCode($currencyCode);
        $position = self::getSymbolPositionForCurrency($currencyCode);

        return $position === 'before'
            ? $symbol . ' ' . $formattedPrice
            : $formattedPrice . ' ' . $symbol;
    }


    /**
     * Get decimal places for a currency code
     *
     * @param string $currencyCode
     * @return int
     */
    public static function getDecimalPlacesForCurrency(string $currencyCode): int
    {
        // Currencies that typically use 3 decimal places
        $threeDecimalCurrencies = ['KWD', 'BHD', 'OMR', 'JOD', 'TND', 'LYD'];

        // Currencies that use 0 decimal places
        $zeroDecimalCurrencies = ['JPY', 'KRW', 'VND', 'CLP', 'PYG', 'RWF', 'UGX', 'VUV'];

        $currencyCode = strtoupper($currencyCode);

        if (in_array($currencyCode, $threeDecimalCurrencies)) {
            return 3;
        } elseif (in_array($currencyCode, $zeroDecimalCurrencies)) {
            return 0;
        } else {
            // Default to 2 decimal places for most currencies
            return 2;
        }
    }

    /**
     * Get symbol position for a currency
     *
     * @param string $currencyCode
     * @return string 'before' or 'after'
     */
    public static function getSymbolPositionForCurrency(string $currencyCode): string
    {
        // Currencies where symbol comes after the amount
        $afterSymbolCurrencies = ['EGP', 'SAR', 'AED', 'QAR', 'KWD', 'BHD', 'OMR', 'JOD', 'TND', 'LYD'];

        $currencyCode = strtoupper($currencyCode);

        return in_array($currencyCode, $afterSymbolCurrencies) ? 'after' : 'before';
    }

    /**
     * Get currency symbol for currency code
     *
     * @param string $currencyCode
     * @return string
     */
    public static function getSymbolForCurrencyCode(string $currencyCode): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'AUD' => 'A$',
            'CAD' => 'CA$',
            'CHF' => 'CHF',
            'CNY' => '¥',
            'INR' => '₹',
            'BRL' => 'R$',
            'ZAR' => 'R',
            'MXN' => 'Mex$',
            'SGD' => 'S$',
            'HKD' => 'HK$',
            'NZD' => 'NZ$',
            'SEK' => 'kr',
            'NOK' => 'kr',
            'DKK' => 'kr',
            'RUB' => '₽',
            'TRY' => '₺',
            'AED' => 'د.إ',
            'SAR' => 'ر.س',
            'QAR' => 'ر.ق',
            'KWD' => 'د.ك',
            'BHD' => 'ب.د',
            'OMR' => 'ر.ع.',
            'JOD' => 'د.أ',
            'EGP' => 'ج.م',
            'LBP' => 'ل.ل',
            'IQD' => 'ع.د',
            'SYP' => 'ل.س',
            'YER' => '﷼',
        ];

        return $symbols[strtoupper($currencyCode)] ?? $currencyCode;
    }

    /**
     * Parse formatted price back to float
     *
     * @param string $formattedPrice
     * @return float
     */
    public static function parsePrice(string $formattedPrice): float
    {
        // Remove all non-numeric characters except decimal point
        $cleaned = preg_replace('/[^0-9.]/', '', $formattedPrice);

        // Convert to float
        return (float) $cleaned;
    }

    /**
     * Store-level currency cache
     */
    protected static $storeCurrencyCache = [];

    /**
     * Get currency information from store with caching
     *
     * @param \Modules\Store\Models\Store $store
     * @return array
     */
    public static function getCurrencyInfoFromStore($store): array
    {
        // Check if we have cached currency info for this store
        $storeId = $store->id ?? 0;
        if (isset(self::$storeCurrencyCache[$storeId])) {
            return self::$storeCurrencyCache[$storeId];
        }

        // First try to get currency from store's direct fields (denormalized data)
        $currencyCode = $store->getCurrencyCode();
        $currencySymbol = $store->currency_symbol;
        $currencyFactor = $store->getCurrencyCode();

        // If not set on store, fall back to country-based resolution
        if (empty($currencyCode) || empty($currencySymbol)) {
            $currencyCode = $store->address?->zone?->city?->governorate?->country?->currency_symbol ?? 'EGP';
            $currencySymbol = $store->address?->zone?->city?->governorate?->country?->currency_symbol ?? 'ج.م';
        }

        // Cache the result
        $currencyInfo = [
            'currency_code' => $currencyCode,
            'currency_symbol' => $currencySymbol,
            'decimal_places' => self::getDecimalPlacesForCurrency($currencyCode),
            'symbol_position' => self::getSymbolPositionForCurrency($currencyCode),
            'currency_factor' => $store->address?->zone?->city?->governorate?->country?->currency_factor ?? 100,
        ];

        self::$storeCurrencyCache[$storeId] = $currencyInfo;

        return $currencyInfo;
    }

    /**
     * Clear store currency cache (useful when store data changes)
     *
     * @param int|null $storeId Clear specific store or all stores
     */
    public static function clearStoreCurrencyCache(?int $storeId = null): void
    {
        if ($storeId === null) {
            self::$storeCurrencyCache = [];
        } else {
            unset(self::$storeCurrencyCache[$storeId]);
        }
    }

    /**
     * Preload currency information for multiple stores (batch loading)
     *
     * @param array $stores Array of store models
     */
    public static function preloadStoreCurrencies(array $stores): void
    {
        foreach ($stores as $store) {
            if (!isset(self::$storeCurrencyCache[$store->id])) {
                self::getCurrencyInfoFromStore($store);
            }
        }
    }

    /**
     * Get formatted price with store context (optimized for resources)
     *
     * @param float $price
     * @param \Modules\Store\Models\Store|null $store
     * @param string|null $currencyCode
     * @param string|null $currencySymbol
     * @return string
     */
    public static function formatPriceForStore(float $price, ?\Modules\Store\Models\Store $store = null, ?string $currencyCode = null, ?string $currencySymbol = null): string
    {
        // Use provided currency info or get from store
        if ($currencyCode && $currencySymbol) {
            return self::formatPrice($price, $currencyCode, $currencySymbol);
        }

        if ($store) {
            $currencyInfo = self::getCurrencyInfoFromStore($store);
            return self::formatPrice($price, $currencyInfo['currency_code'], $currencyInfo['currency_symbol']);
        }

        // Fallback to default
        return self::formatPrice($price, 'EGP', 'ج.م');
    }

    /**
     * Convert a decimal amount to minor units using a currency factor.
     * Example: toMinorUnits(12.34, 100) => 1234
     *
     * @param float $amount
     * @param int $factor
     * @return int
     */
    public static function toMinorUnits( $amount, $factor): int
    {
        return $amount * $factor;
    }

    /**
     * Convert minor units back to decimal amount using a currency factor.
     * Example: fromMinorUnits(1234, 100) => 12.34
     *
     * @param int $minor
     * @param int $factor
     * @param int|null $decimalPlaces If null, uses decimal places for currency when known else 2
     * @return float
     */
    public static function fromMinorUnits(int $minor, int $factor, ?int $decimalPlaces = null)
    {
        return $minor / $factor;
    }

    /**
     * Convert an amount in minor units from one currency factor to another.
     * Example: convertMinorBetweenCurrencies(1234, 100, 1000) => rounds to 12340
     *
     * @param int $amountMinor
     * @param int $srcFactor
     * @param int $dstFactor
     * @return int
     */
    public static function convertMinorBetweenCurrencies(int $amountMinor, int $srcFactor, int $dstFactor): int
    {
        if ($srcFactor === $dstFactor) {
            return $amountMinor;
        }

        return (int) round(($amountMinor / $srcFactor) * $dstFactor);
    }

    /**
     * Convert decimal price to unsigned bigint (minor units)
     * Used for storing prices in database as UNSIGNED BIGINT
     *
     * @param float $price Decimal price (e.g., 123.45)
     * @param int $currencyUnit Currency unit divisor (e.g., 100 for cents)
     * @return int Unsigned big integer in minor units (e.g., 12345)
     */
    public static function priceToUnsignedBigInt($price, $currencyUnit = 100): int
    {
        return $price * $currencyUnit;
    }

    /**
     * Convert unsigned bigint back to decimal price
     * Retrieves display value from database stored format
     *
     * @param int $priceInMinor Unsigned big integer in minor units (e.g., 12345)
     * @param int $currencyUnit Currency unit divisor (e.g., 100 for cents)
     * @return float Decimal price (e.g., 123.45)
     */
    public static function unsignedBigIntToPrice(int $priceInMinor, int $currencyUnit = 100): float
    {
        return $priceInMinor / $currencyUnit;
    }

    /**
     * Format unsigned bigint price for display
     * Combines conversion and formatting in one call
     *
     * @param int $priceInMinor Unsigned big integer in minor units
     * @param string $currencyCode Currency code (e.g., 'USD')
     * @param int $currencyUnit Currency unit divisor
     * @return string Formatted price string
     */
    public static function formatBigIntPrice(int $priceInMinor, string $currencyCode, int $currencyUnit = 100): string
    {
        $price = self::unsignedBigIntToPrice($priceInMinor, $currencyUnit);
        return self::formatPrice($price, $currencyCode);
    }

    /**
     * Add two unsigned bigint prices
     * Maintains precision by working with integers
     *
     * @param int $price1 First price in minor units
     * @param int $price2 Second price in minor units
     * @return int Sum in minor units
     */
    public static function addBigIntPrices(int $price1, int $price2): int
    {
        return $price1 + $price2;
    }

    /**
     * Subtract unsigned bigint prices
     * Returns 0 if result would be negative
     *
     * @param int $price1 Minuend in minor units
     * @param int $price2 Subtrahend in minor units
     * @return int Difference in minor units (minimum 0)
     */
    public static function subtractBigIntPrices(int $price1, int $price2): int
    {
        return max(0, $price1 - $price2);
    }

    /**
     * Multiply unsigned bigint price by factor
     * Useful for tax, discounts, or quantity calculations
     *
     * @param int $price Price in minor units
     * @param float $factor Multiplication factor
     * @return int Result in minor units
     */
    public static function multiplyBigIntPrice(int $price, float $factor): int
    {
        return (int)round($price * $factor);
    }

    /**
     * Calculate percentage of unsigned bigint price
     *
     * @param int $price Price in minor units
     * @param float $percentage Percentage value (0-100)
     * @return int Percentage amount in minor units
     */
    public static function percentageOfBigIntPrice(int $price, float $percentage): int
    {
        return (int)round($price * ($percentage / 100));
    }

    /**
     * Apply tax to unsigned bigint price
     *
     * @param int $price Price in minor units
     * @param float $taxRate Tax rate as decimal (e.g., 0.15 for 15%)
     * @return int Tax amount in minor units
     */
    public static function calculateTaxOnBigIntPrice(int $price, float $taxRate): int
    {
        return (int)round($price * $taxRate);
    }

    /**
     * Apply discount to unsigned bigint price
     *
     * @param int $price Price in minor units
     * @param float $discountPercent Discount percentage (0-100)
     * @return int Discount amount in minor units
     */
    public static function calculateDiscountOnBigIntPrice(int $price, float $discountPercent): int
    {
        return self::percentageOfBigIntPrice($price, $discountPercent);
    }

    /**
     * Get final price after tax
     *
     * @param int $price Original price in minor units
     * @param float $taxRate Tax rate as decimal
     * @return int Final price with tax in minor units
     */
    public static function priceWithTaxBigInt(int $price, float $taxRate): int
    {
        return $price + self::calculateTaxOnBigIntPrice($price, $taxRate);
    }

    /**
     * Get final price after discount
     *
     * @param int $price Original price in minor units
     * @param float $discountPercent Discount percentage
     * @return int Final price after discount in minor units
     */
    public static function priceAfterDiscountBigInt(int $price, float $discountPercent): int
    {
        return self::subtractBigIntPrices($price, self::calculateDiscountOnBigIntPrice($price, $discountPercent));
    }

    /**
     * Convert price between different currency units
     * Useful for multi-currency support
     *
     * @param int $amountInMinor Price in source minor units
     * @param int $sourceCurrencyUnit Source currency unit
     * @param int $targetCurrencyUnit Target currency unit
     * @return int Price in target minor units
     */
    public static function convertBigIntPriceBetweenUnits(int $amountInMinor, int $sourceCurrencyUnit, int $targetCurrencyUnit): int
    {
        if ($sourceCurrencyUnit === $targetCurrencyUnit) {
            return $amountInMinor;
        }
        return (int)round(($amountInMinor / $sourceCurrencyUnit) * $targetCurrencyUnit);
    }

    /**
     * Round unsigned bigint price to nearest minor unit
     * Some operations may require rounding
     *
     * @param float $priceInMinor Price value (potentially with decimals)
     * @return int Rounded to nearest minor unit
     */
    public static function roundBigIntPrice(float $priceInMinor): int
    {
        return (int)round($priceInMinor);
    }

    /**
     * Validate if value is within reasonable price bounds
     * Prevents overflow or unreasonable values
     *
     * @param int $priceInMinor Price in minor units
     * @param int $maxValue Maximum allowed value
     * @return bool True if price is valid
     */
    public static function isValidBigIntPrice(int $priceInMinor, int $maxValue = PHP_INT_MAX): bool
    {
        return $priceInMinor >= 0 && $priceInMinor <= $maxValue;
    }
}
