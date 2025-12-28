<?php

return [
    /*
    |--------------------------------------------------------------------------
    | General Settings
    |--------------------------------------------------------------------------
    |
    | Here you can configure various general settings for the application.
    |
    */

    'default_country' => env('DEFAULT_COUNTRY_ID', 1), // Default country by ID (or name if preferred)
    'default_currency' => env('DEFAULT_CURRENCY', 'USD'), // Default currency
    'default_language' => env('DEFAULT_LANGUAGE', 'en'), // Default language
    'timezone' => env('DEFAULT_TIMEZONE', 'UTC'), // Default timezone

    // You can add other settings like API keys, external service configs, etc.
    'api_key' => env('API_KEY', 'your-api-key-here'),

    // Enable or disable features based on your environment or requirements
    'feature_flag' => env('FEATURE_FLAG', true),
];
