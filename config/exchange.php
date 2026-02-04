<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Banxico API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Banxico exchange rate API integration
    |
    */

    'banxico' => [
        'base_url' => 'https://www.banxico.org.mx/SieAPIRest/service/v1/',
        'token' => env('BANXICO_TOKEN', ''),
        'timeout' => 30, // seconds
        'retry_attempts' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency to Banxico Series ID Mapping
    |--------------------------------------------------------------------------
    |
    | Maps currency codes to their corresponding Banxico series IDs
    |
    */

    'currency_series' => [
        'USD' => 'SF43718', // Tipo de cambio FIX peso-dólar
        'EUR' => 'SF46410', // Tipo de cambio peso-euro  
        'GBP' => 'SF46407', // Tipo de cambio peso-libra esterlina
        'JPY' => 'SF46406', // Tipo de cambio peso-yen
        'CAD' => 'SF43720', // Tipo de cambio peso-dólar canadiense
        'CHF' => 'SF43721', // Tipo de cambio peso-franco suizo
        'UDI' => 'SP68257', // UDIs (Unidades de Inversión)
        // Agregar más monedas según necesidades
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Cache settings for exchange rates
    |
    */

    'cache' => [
        'ttl' => 86400, // 24 hours in seconds
        'prefix' => 'banxico_rate_',
        'driver' => 'file', // file, database, redis, etc.
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Lookup Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for rate lookup behavior
    |
    */

    'lookup' => [
        'max_days_back' => 10, // Aumentado a 10 días para mejor cobertura
        'decimal_places' => 4, // Number of decimal places for rates
        'weekend_strategy' => 'lookup_previous', // lookup_previous, skip, fail
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Values
    |--------------------------------------------------------------------------
    |
    | Default values for exchange rate operations
    |
    */

    'defaults' => [
        'base_currency' => 'MXN',
        'fallback_rate' => null, // null means require manual input
    ],
];