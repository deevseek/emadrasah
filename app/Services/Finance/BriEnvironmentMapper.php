<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\BriIntegrationSetting;
use Illuminate\Support\Facades\Storage;

final class BriEnvironmentMapper
{
    /** @var array<string,string> */
    public const MAP = [
        'enabled'=>'BRI_ENABLED','environment'=>'BRI_ENV','base_url'=>'BRI_BASE_URL','client_id'=>'BRI_CLIENT_ID',
        'client_secret'=>'BRI_CLIENT_SECRET','partner_id'=>'BRI_PARTNER_ID','channel_id'=>'BRI_CHANNEL_ID',
        'private_key_path'=>'BRI_PRIVATE_KEY_PATH','public_key_path'=>'BRI_PUBLIC_KEY_PATH','registered_account_number'=>'BRI_REGISTERED_ACCOUNT_NUMBER',
        'timestamp_tolerance'=>'BRI_TIMESTAMP_TOLERANCE','timeout'=>'BRI_TIMEOUT','briva_enabled'=>'BRI_BRIVA_ENABLED',
        'partner_service_id'=>'BRI_BRIVA_PARTNER_SERVICE_ID','institution_code'=>'BRI_BRIVA_INSTITUTION_CODE',
        'customer_number_prefix'=>'BRI_BRIVA_CUSTOMER_PREFIX','briva_mode'=>'BRI_BRIVA_MODE','qris_enabled'=>'BRI_QRIS_ENABLED',
        'merchant_id'=>'BRI_QRIS_MERCHANT_ID','terminal_id'=>'BRI_QRIS_TERMINAL_ID','qris_service_code'=>'BRI_QRIS_SERVICE_CODE',
        'qris_notification_success_code'=>'BRI_QRIS_NOTIFICATION_SUCCESS_CODE','payroll_enabled'=>'BRI_PAYROLL_ENABLED',
        'source_account'=>'BRI_SOURCE_ACCOUNT','intrabank_service_code'=>'BRI_INTRABANK_SERVICE_CODE',
        'interbank_service_code'=>'BRI_INTERBANK_SERVICE_CODE','status_inquiry_service_code'=>'BRI_STATUS_INQUIRY_SERVICE_CODE',
        'path_bank_statement'=>'BRI_PATH_BANK_STATEMENT','path_qris_generate'=>'BRI_PATH_QRIS_GENERATE',
        'path_transaction_status'=>'BRI_PATH_TRANSACTION_STATUS','path_intrabank_transfer'=>'BRI_PATH_INTRABANK_TRANSFER',
        'path_interbank_transfer'=>'BRI_PATH_INTERBANK_TRANSFER','direct_debit_enabled'=>'BRI_DIRECT_DEBIT_ENABLED',
    ];

    public static function fromSetting(BriIntegrationSetting $setting): array
    {
        return collect(self::MAP)->mapWithKeys(function (string $env, string $column) use ($setting): array {
            $value = $setting->{$column};
            if (in_array($column, ['private_key_path','public_key_path'], true) && $value) $value = Storage::disk('bri_private')->path($value);
            return [$env => $value];
        })->all();
    }

    public static function fromConfig(): array
    {
        $keys = [
            'enabled'=>'enabled','environment'=>'environment','base_url'=>'base_url','client_id'=>'client_id','client_secret'=>'client_secret',
            'partner_id'=>'partner_id','channel_id'=>'channel_id','private_key_path'=>'private_key_path','public_key_path'=>'public_key_path',
            'registered_account_number'=>'registered_account_number','timestamp_tolerance'=>'timestamp_tolerance_seconds','timeout'=>'timeout_seconds',
            'briva_enabled'=>'briva.enabled','partner_service_id'=>'briva.partner_service_id','institution_code'=>'briva.institution_code',
            'customer_number_prefix'=>'briva.customer_number_prefix','briva_mode'=>'briva.mode','qris_enabled'=>'qris.enabled',
            'merchant_id'=>'qris.merchant_id','terminal_id'=>'qris.terminal_id','qris_service_code'=>'qris.service_code',
            'qris_notification_success_code'=>'response_codes.qris_notification_success','payroll_enabled'=>'payroll.enabled',
            'source_account'=>'payroll.source_account','intrabank_service_code'=>'payroll.intrabank_service_code',
            'interbank_service_code'=>'payroll.interbank_service_code','status_inquiry_service_code'=>'payroll.status_inquiry_service_code',
            'path_bank_statement'=>'paths.bank_statement','path_qris_generate'=>'paths.qris_generate','path_transaction_status'=>'paths.transaction_status',
            'path_intrabank_transfer'=>'paths.intrabank_transfer','path_interbank_transfer'=>'paths.interbank_transfer','direct_debit_enabled'=>'direct_debit_enabled',
        ];
        return collect($keys)->mapWithKeys(fn (string $key, string $column) => [$column => config('bri.'.$key)])->all();
    }
}
