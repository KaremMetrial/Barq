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
    public static function formatPrice(float $price, string $currencyCode, ?string $currencySymbol = null, bool $includeSymbol = true): string
    {
        $currencyCode = strtoupper($currencyCode);

        // Get cached decimal places
        $decimalPlaces = self::getDecimalPlacesForCurrency($currencyCode);

        // Format the price
        $formattedPrice = number_format($price, $decimalPlaces, '.', '');

        if ($includeSymbol) {
            // Get cached symbol position
            $symbolPosition = self::getSymbolPositionForCurrency($currencyCode);
            $symbol = $currencySymbol ?? self::getSymbolForCurrencyCode($currencyCode);

            // return $symbolPosition === 'before' ? $symbol . ' ' . $formattedPrice : $formattedPrice . ' ' . $symbol;
            return $formattedPrice;
        }

        return $formattedPrice;
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
        $currencyCode = $store->currency_code;
        $currencySymbol = $store->currency_symbol;

        // If not set on store, fall back to country-based resolution
        if (empty($currencyCode) || empty($currencySymbol)) {
            $currencyCode = $store->address?->zone?->city?->governorate?->country?->currency_name ?? 'EGP';
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
    public static function toMinorUnits(float $amount, int $factor): int
    {
        return (int) round($amount * $factor);
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
    public static function fromMinorUnits(int $minor, int $factor, ?int $decimalPlaces = null): float
    {
        $decimalPlaces = $decimalPlaces ?? 2;
        return round($minor / $factor, $decimalPlaces);
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
}
