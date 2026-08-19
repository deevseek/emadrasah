<?php

declare(strict_types=1);

return [
    'env_file' => env('BRI_ENV_FILE', base_path('.env')),
    'enabled' => (bool) env('BRI_ENABLED', false),
    'environment' => env('BRI_ENV', 'sandbox'),
    'base_url' => env('BRI_BASE_URL'),
    'client_id' => env('BRI_CLIENT_ID'),
    'client_secret' => env('BRI_CLIENT_SECRET'),
    'partner_id' => env('BRI_PARTNER_ID'),
    'channel_id' => env('BRI_CHANNEL_ID'),
    'private_key_path' => env('BRI_PRIVATE_KEY_PATH'),
    'public_key_path' => env('BRI_PUBLIC_KEY_PATH'),
    'registered_account_number' => env('BRI_REGISTERED_ACCOUNT_NUMBER'),
    'timestamp_tolerance_seconds' => (int) env('BRI_TIMESTAMP_TOLERANCE', 300),
    'timeout_seconds' => (int) env('BRI_TIMEOUT', 20),
    'paths' => [
        'access_token' => env('BRI_PATH_ACCESS_TOKEN', '/snap/v1.0/access-token/b2b'),
        'balance_inquiry' => env('BRI_PATH_BALANCE_INQUIRY', '/snap/v1.0/balance-inquiry'),
        // Nilai path produk berikut wajib mengikuti hasil onboarding BRI.
        'bank_statement' => env('BRI_PATH_BANK_STATEMENT'),
        'briva_inquiry' => env('BRI_PATH_BRIVA_INQUIRY'),
        'qris_generate' => env('BRI_PATH_QRIS_GENERATE'),
        'transaction_status' => env('BRI_PATH_TRANSACTION_STATUS'),
        'intrabank_transfer' => env('BRI_PATH_INTRABANK_TRANSFER'),
        'interbank_transfer' => env('BRI_PATH_INTERBANK_TRANSFER'),
        'direct_debit' => env('BRI_PATH_DIRECT_DEBIT'),
        'transfer_va' => env('BRI_PATH_TRANSFER_VA'),
    ],
    'briva' => [
        'enabled' => (bool) env('BRI_BRIVA_ENABLED', false),
        'partner_service_id' => env('BRI_BRIVA_PARTNER_SERVICE_ID'),
        'institution_code' => env('BRI_BRIVA_INSTITUTION_CODE'),
        'customer_number_prefix' => env('BRI_BRIVA_CUSTOMER_PREFIX'),
        'mode' => env('BRI_BRIVA_MODE', 'per_student'),
    ],
    'qris' => [
        'enabled' => (bool) env('BRI_QRIS_ENABLED', false),
        'merchant_id' => env('BRI_QRIS_MERCHANT_ID'),
        'terminal_id' => env('BRI_QRIS_TERMINAL_ID'),
        'service_code' => env('BRI_QRIS_SERVICE_CODE'),
    ],
    'payroll' => [
        'enabled' => (bool) env('BRI_PAYROLL_ENABLED', false),
        'source_account' => env('BRI_SOURCE_ACCOUNT'),
        'intrabank_service_code' => env('BRI_INTRABANK_SERVICE_CODE'),
        'interbank_service_code' => env('BRI_INTERBANK_SERVICE_CODE'),
        'status_inquiry_service_code' => env('BRI_STATUS_INQUIRY_SERVICE_CODE'),
    ],
    'direct_debit_enabled' => (bool) env('BRI_DIRECT_DEBIT_ENABLED', false),
    'response_codes' => [
        'briva_payment_success' => env('BRI_BRIVA_PAYMENT_SUCCESS_CODE', '2002500'),
        'qris_notification_success' => env('BRI_QRIS_NOTIFICATION_SUCCESS_CODE'),
    ],
    'schedule' => [
        'statement' => env('BRI_STATEMENT_SCHEDULE', '*/30 * * * *'),
        'pending_inquiry' => env('BRI_PENDING_INQUIRY_SCHEDULE', '*/10 * * * *'),
        'reconciliation' => env('BRI_RECONCILIATION_SCHEDULE', '*/15 * * * *'),
    ],
];
