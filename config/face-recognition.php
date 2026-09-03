<?php

return [
    'driver' => env('FACE_RECOGNITION_DRIVER'),
    'url' => rtrim((string) env('FACE_RECOGNITION_URL', ''), '/'),
    'token' => env('FACE_RECOGNITION_TOKEN'),
    'timeout' => (int) env('FACE_RECOGNITION_TIMEOUT', 10),
    'restart_command' => env('FACE_RECOGNITION_RESTART_COMMAND'),
    'restart_timeout' => (int) env('FACE_RECOGNITION_RESTART_TIMEOUT', 30),
];
