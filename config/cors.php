<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH' , 'DELETE', 'OPTIONS'],

    'allowed_origins' => ['https://egomall.io.vn','http://localhost:3000'],

    'allowed_origins_patterns' => ['^https://([a-z0-9-]+\.)*egomall\.io\.vn(:[0-9]+)?$'],

    'allowed_headers' => ['Content-Type', 'X-Requested-With', 'Accept', 'Authorization'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
