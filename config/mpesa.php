<?php

return [
    'environment' => env('MPESA_ENVIRONMENT', 'sandbox'),

    'sandbox' => [
        'base_url' => 'https://sandbox.safaricom.co.ke',
        'consumer_key' => env('MPESA_CONSUMER_KEY'),
        'consumer_secret' => env('MPESA_CONSUMER_SECRET'),
        'paybill' => env('MPESA_PAYBILL_NUMBER', '600978'),
        'shortcode' => env('LIPA_NA_MPESA_SHORTCODE', '174379'),
        'passkey' => env('LIPA_NA_MPESA_PASSKEY', 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919'),
        'callback_url' => env('MPESA_CALLBACK_URL'),
    ],

    'production' => [
        'base_url' => 'https://api.safaricom.co.ke',
        'consumer_key' => env('MPESA_CONSUMER_KEY'),
        'consumer_secret' => env('MPESA_CONSUMER_SECRET'),
        'paybill' => env('MPESA_PAYBILL_NUMBER'),
        'shortcode' => env('LIPA_NA_MPESA_SHORTCODE'),
        'passkey' => env('LIPA_NA_MPESA_PASSKEY'),
        'callback_url' => env('MPESA_CALLBACK_URL'),
    ],
];
