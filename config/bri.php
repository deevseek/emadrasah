<?php

return [
    'enabled' => (bool) env('BRI_ENABLED', false),
    'environment' => env('BRI_ENV', 'sandbox'),
    'base_url' => env('BRI_BASE_URL'),
    'client_id' => env('BRI_CLIENT_ID'),
    'client_secret' => env('BRI_CLIENT_SECRET'),
    'partner_id' => env('BRI_PARTNER_ID'),
    'channel_id' => env('BRI_CHANNEL_ID'),
    'private_key_path' => env('BRI_PRIVATE_KEY_PATH'),
    'public_key_path' => env('BRI_PUBLIC_KEY_PATH'),
    'briva' => [
        'enabled' => (bool) env('BRI_BRIVA_ENABLED', false),
        'partner_service_id' => env('BRI_BRIVA_PARTNER_SERVICE_ID'),
        'institution_code' => env('BRI_BRIVA_INSTITUTION_CODE'),
        'customer_number_prefix' => env('BRI_BRIVA_CUSTOMER_PREFIX', ''),
        'mode' => env('BRI_BRIVA_MODE', 'per_student'),
    ],
    'qris' => [
        'enabled' => (bool) env('BRI_QRIS_ENABLED', false),
        'merchant_id' => env('BRI_QRIS_MERCHANT_ID'),
        'terminal_id' => env('BRI_QRIS_TERMINAL_ID'),
        'service_code' => env('BRI_QRIS_SERVICE_CODE', '17'),
    ],
    'payroll' => [
        'enabled' => (bool) env('BRI_PAYROLL_ENABLED', false),
        'source_account' => env('BRI_SOURCE_ACCOUNT'),
        'intrabank_status_service_code' => env('BRI_INTRABANK_STATUS_SERVICE_CODE'),
        'interbank_status_service_code' => env('BRI_INTERBANK_STATUS_SERVICE_CODE'),
    ],
];
