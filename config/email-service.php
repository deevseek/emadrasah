<?php

return [
    // Token rahasia untuk endpoint webhook email masuk. Simpan nilainya hanya di lingkungan deployment.
    'inbound_token' => env('INBOUND_EMAIL_TOKEN'),
];
