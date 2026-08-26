<?php

declare(strict_types=1);

$trustedProxies = array_values(array_filter(array_map(
    static fn (string $proxy): string => trim($proxy),
    explode(',', (string) env('TRUSTED_PROXIES', '')),
)));

return [
    /*
    | Hanya isi dengan IP/CIDR reverse proxy yang langsung menghubungi aplikasi.
    | Nilai kosong membuat Laravel tidak mempercayai header X-Forwarded-*.
    */
    'trusted_proxies' => $trustedProxies,

    'diagnostics' => [
        'enabled' => (bool) env('RFID_ATTENDANCE_DIAGNOSTICS', false),
    ],
];
