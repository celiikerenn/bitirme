<?php

/**
 * config/services.php içine eklenecek bölüm:
 *
 * 'fastapi' => [
 *     'url' => env('FASTAPI_URL', 'http://127.0.0.1:8001'),
 * ],
 */

return [
    'fastapi' => [
        'url' => env('FASTAPI_URL', 'http://127.0.0.1:8001'),
    ],
];
