<?php

return [
    'default' => env('PAYMENT_DEFAULT_GATEWAY', 'sslcommerz'),
    
    'gateways' => [
        'sslcommerz' => [
            'driver' => \faysal0x1\PaymentGateway\Drivers\SSLCommerz::class,
            'store_id' => env('SSLCOMMERZ_STORE_ID'),
            'store_password' => env('SSLCOMMERZ_STORE_PASSWORD'),
            'sandbox' => env('SSLCOMMERZ_SANDBOX', true),
        ],
        'bkash' => [
            'driver' => \faysal0x1\PaymentGateway\Drivers\Bkash::class,
            'app_key' => env('BKASH_APP_KEY'),
            'app_secret' => env('BKASH_APP_SECRET'),
            'username' => env('BKASH_USERNAME'),
            'password' => env('BKASH_PASSWORD'),
            'sandbox' => env('BKASH_SANDBOX', true),
        ],
        'nagad' => [
            'driver' => \faysal0x1\PaymentGateway\Drivers\Nagad::class,
            'merchant_id' => env('NAGAD_MERCHANT_ID'),
            'merchant_number' => env('NAGAD_MERCHANT_NUMBER'),
            'private_key' => env('NAGAD_PRIVATE_KEY'),
            'sandbox' => env('NAGAD_SANDBOX', true),
        ],
    ],
];